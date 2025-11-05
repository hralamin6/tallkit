<?php

namespace App\Livewire;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageReaction;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Storage;

class ChatComponent extends Component
{
    use WithFileUploads, WithPagination;

    // State
    public $selectedConversationId = null;
    public $search = '';
    public $showNewChatModal = false;
    public $messageSearch = '';

    // Message input
    public $body = '';
    public $attachments = [];
    public $replyingTo = null;

    protected $rules = [
        'body' => 'required_without:attachments|string|max:5000',
        'attachments.*' => 'file|max:10240',
    ];

    public function mount($conversation = null)
    {
        // If conversation ID provided in URL, select it
        if ($conversation) {
            $conv = Conversation::find($conversation);
            if ($conv && $conv->hasUser(auth()->id())) {
                $this->selectedConversationId = $conv->id;
                $this->markAsRead();
                $this->markUnreadMessagesAsRead();
                return;
            }
        }

        // Otherwise, auto-select first conversation
        $firstConversation = $this->getConversationsProperty()->first();
        if ($firstConversation) {
            $this->selectedConversationId = $firstConversation->id;
            $this->markAsRead();
            $this->markUnreadMessagesAsRead();
        }
    }

    public function getConversationsProperty()
    {
        return auth()->user()
            ->conversations()
            ->with(['userOne', 'userTwo', 'latestMessage.user'])
            ->when($this->search, function ($query) {
                $query->whereHas('userOne', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('userTwo', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->get();
    }

    public function getMessagesProperty()
    {
        if (!$this->selectedConversationId) {
            return collect([]);
        }

        $query = Message::where('conversation_id', $this->selectedConversationId)
            ->with(['user', 'parent.user', 'attachments', 'reactions.user'])
            ->where('is_deleted', false);

        if ($this->messageSearch) {
            $query->where('body', 'like', '%' . $this->messageSearch . '%');
        }

        return $query->orderBy('created_at', 'asc')->limit(50)->get();
    }

    public function selectConversation($conversationId)
    {
        $this->selectedConversationId = $conversationId;
        $this->messageSearch = '';
        $this->replyingTo = null;
        $this->resetPage();
        $this->markAsRead();
        $this->markUnreadMessagesAsRead();
        $this->dispatch('conversationSelected', conversationId: $conversationId);
    }

    public function startNewChat($userId)
    {
        $conversation = Conversation::findOrCreateBetween(auth()->id(), $userId);
        $this->selectedConversationId = $conversation->id;
        $this->showNewChatModal = false;
        $this->markAsRead();
        $this->dispatch('conversationSelected', conversationId: $conversation->id);
    }

    public function sendMessage()
    {
        $this->validate();

        if (!$this->selectedConversationId) {
            return;
        }

        $conversation = Conversation::find($this->selectedConversationId);

        if (!$conversation || !$conversation->hasUser(auth()->id())) {
            return;
        }

        // Create message
        $message = Message::create([
            'conversation_id' => $this->selectedConversationId,
            'user_id' => auth()->id(),
            'parent_id' => $this->replyingTo,
            'body' => $this->body,
        ]);

        // Handle attachments
        if (!empty($this->attachments)) {
            foreach ($this->attachments as $attachment) {
                $path = $attachment->store('chat-attachments', 'public');

                MessageAttachment::create([
                    'message_id' => $message->id,
                    'file_name' => $attachment->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $this->getFileType($attachment->getMimeType()),
                    'mime_type' => $attachment->getMimeType(),
                    'file_size' => $attachment->getSize(),
                ]);
            }
        }

        // Update conversation
        $conversation->update(['last_message_at' => now()]);

        // No broadcast needed - using whisper instead

        // Send notification
        $otherUser = $conversation->getOtherUser(auth()->id());
        $otherUser->notify(new \App\Notifications\NewMessageNotification($message));

        // Reset form
        $this->reset(['body', 'attachments', 'replyingTo']);

        // Dispatch to Alpine to trigger whisper
        $this->dispatch('message-sent', messageId: $message->id);
    }

    public function toggleReaction($messageId, $emoji)
    {
        MessageReaction::toggle($messageId, auth()->id(), $emoji);
        // No broadcast needed - using whisper instead
    }

    public function setReplyingTo($messageId)
    {
        $this->replyingTo = $messageId;
    }

    public function cancelReply()
    {
        $this->replyingTo = null;
    }

    public function deleteMessage($messageId)
    {
        $message = Message::find($messageId);

        if ($message && $message->user_id === auth()->id()) {
            $message->softDeleteMessage();
            broadcast(new \App\Events\MessageDeleted($message))->toOthers();
        }
    }

    public function removeAttachment($index)
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function refreshMessages()
    {
        // Mark unread messages as read when refreshing
        $this->markUnreadMessagesAsRead();
//        $this->getConversationsProperty();

        // Trigger a re-render
        $this->dispatch('messagesRefreshed');
    }

    public function refreshConversations()
    {
        // Just trigger a re-render
    }

    private function markUnreadMessagesAsRead()
    {
        if (!$this->selectedConversationId) {
            return;
        }

        // Mark all unread messages in this conversation as read
        $updated = Message::where('conversation_id', $this->selectedConversationId)
            ->where('user_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // If messages were marked as read, whisper to sender and refresh conversations
        if ($updated > 0) {
            $this->dispatch('messages-marked-read');
            // Force refresh conversations list to update unread count
            $this->dispatch('$refresh');
        }
    }

    public function getOtherUserProperty()
    {
        if (!$this->selectedConversationId) {
            return null;
        }

        $conversation = Conversation::find($this->selectedConversationId);
        if (!$conversation) {
            return null;
        }

        return $conversation->getOtherUser(auth()->id());
    }

    private function markAsRead()
    {
        if (!$this->selectedConversationId) {
            return;
        }

        $conversation = Conversation::find($this->selectedConversationId);
        if ($conversation) {
            $conversation->markAsRead(auth()->id());
        }
    }

    private function getFileType($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } else {
            return 'document';
        }
    }

    public function getAvailableUsersProperty()
    {
        return User::where('id', '!=', auth()->id())
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->limit(20)
            ->get();
    }

    public function getSelectedConversationProperty()
    {
        if (!$this->selectedConversationId) {
            return null;
        }

        return Conversation::with(['userOne', 'userTwo'])->find($this->selectedConversationId);
    }

    public function render()
    {
        return view('livewire.chat-component')->layout('layouts.app');
    }
}
