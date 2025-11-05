<div
    class="h-[calc(100vh-8rem)]"
    x-data="chatApp()"
    x-init="init()"
    @message-sent.window="handleMessageSent()"
>
    <div class="flex h-full bg-base-100 rounded-xl shadow-xl overflow-hidden">
        <!-- Conversations List Sidebar -->
        <div class="w-full md:w-96 border-r border-base-300 flex flex-col">
            <!-- Header -->
            <div class="p-4 border-b border-base-300 bg-base-200">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xl font-bold text-base-content">Messages</h2>
                    <button wire:click="$set('showNewChatModal', true)" class="btn btn-primary btn-sm btn-circle">
                        <x-icon name="o-plus" class="w-5 h-5" />
                    </button>
                </div>

                <!-- Search -->
                <x-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search conversations..."
                    icon="o-magnifying-glass"
                    class="input-sm"
                />
            </div>

            <!-- Conversations List -->
            <div class="flex-1 overflow-y-auto">
                @forelse($this->conversations as $conversation)
                    @php
                        $otherUser = $conversation->getOtherUser(auth()->id());
                        $unreadCount = $conversation->getUnreadCount(auth()->id());
                        $latestMessage = $conversation->latestMessage;
                    @endphp

                    <div
                        wire:click="selectConversation({{ $conversation->id }})"
                        class="p-4 border-b border-base-300 cursor-pointer hover:bg-base-200 transition-colors {{ $selectedConversationId == $conversation->id ? 'bg-primary/10' : '' }}"
                    >
                        <div class="flex items-start gap-3">
                            <div class="avatar">
                                <div class="w-12 h-12 rounded-full">
                                    <img src="{{ $otherUser->avatar_url }}" alt="{{ $otherUser->name }}" />
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="font-semibold text-base-content truncate">{{ $otherUser->name }}</h3>
                                    @if($latestMessage)
                                        <span class="text-xs text-base-content/60">
                                            {{ $latestMessage->created_at->diffForHumans(null, true) }}
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between">
                                    <p class="text-sm text-base-content/70 truncate">
                                        @if($latestMessage)
                                            @if($latestMessage->user_id === auth()->id())
                                                <span class="text-base-content/50">You: </span>
                                            @endif
                                            {{ $latestMessage->body ?? '📎 Attachment' }}
                                        @else
                                            <span class="text-base-content/50">No messages yet</span>
                                        @endif
                                    </p>

                                    @if($unreadCount > 0)
                                        <span class="badge badge-primary badge-sm">{{ $unreadCount }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full p-8 text-center">
                        <x-icon name="o-chat-bubble-left-right" class="w-16 h-16 text-base-content/30 mb-4" />
                        <p class="text-base-content/60 mb-4">No conversations yet</p>
                        <button wire:click="$set('showNewChatModal', true)" class="btn btn-primary btn-sm">
                            Start a conversation
                        </button>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Chat Area -->
        <div class="flex-1 flex flex-col">
            @if($selectedConversationId && $this->selectedConversation)
                @php
                    $otherUser = $this->selectedConversation->getOtherUser(auth()->id());
                @endphp

                <!-- Chat Header -->
                <div class="p-4 border-b border-base-300 bg-base-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="avatar">
                                <div class="w-10 h-10 rounded-full">
                                    <img src="{{ $otherUser->avatar_url }}" alt="{{ $otherUser->name }}" />
                                </div>
                            </div>
                            <div>
                                <h3 class="font-semibold text-base-content">{{ $otherUser->name }}</h3>
                                <p class="text-xs text-base-content/60">{{ $otherUser->email }}</p>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button class="btn btn-ghost btn-sm btn-circle">
                                <x-icon name="o-magnifying-glass" class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Typing Indicator -->
                <div x-show="isTyping" class="px-4 py-2 bg-base-200 border-b border-base-300" x-cloak>
                    <div class="flex items-center gap-2 text-sm text-base-content/70">
                        <span class="loading loading-dots loading-xs"></span>
                        <span x-text="typingUserName + ' is typing...'"></span>
                    </div>
                </div>

                <!-- Messages -->
                <div
                    class="flex-1 overflow-y-auto p-4 space-y-4 bg-base-100"
                    id="message-container"
                    x-init="$el.scrollTop = $el.scrollHeight"
                    @scroll-to-bottom.window="$el.scrollTop = $el.scrollHeight"
                >
                    @forelse($this->messages as $message)
                        @php
                            $isOwn = $message->user_id === auth()->id();
                        @endphp

                        <div class="flex {{ $isOwn ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}" data-is-own="{{ $isOwn ? 'true' : 'false' }}">
                            <div class="flex gap-2 max-w-[75%] {{ $isOwn ? 'flex-row-reverse' : 'flex-row' }}">
                                @if(!$isOwn)
                                    <div class="avatar">
                                        <div class="w-8 h-8 rounded-full">
                                            <img src="{{ $message->user->avatar_url }}" alt="{{ $message->user->name }}" />
                                        </div>
                                    </div>
                                @endif

                                <div class="flex flex-col gap-1 {{ $isOwn ? 'items-end' : 'items-start' }}">
                                    @if($message->parent)
                                        <div class="text-xs text-base-content/60 px-3 py-1 bg-base-200 rounded-lg border-l-2 border-primary">
                                            <span class="font-semibold">{{ $message->parent->user->name }}</span>:
                                            {{ Str::limit($message->parent->body ?? 'Attachment', 50) }}
                                        </div>
                                    @endif

                                    <div class="relative group">
                                        <div class="px-4 py-2 rounded-2xl {{ $isOwn ? 'bg-primary text-primary-content' : 'bg-base-200 text-base-content' }}">
                                            @if(!$isOwn)
                                                <p class="text-xs font-semibold mb-1">{{ $message->user->name }}</p>
                                            @endif

                                            @if($message->body)
                                                <p class="text-sm break-words">{{ $message->body }}</p>
                                            @endif

                                            @if($message->attachments->count() > 0)
                                                <div class="mt-2 space-y-2">
                                                    @foreach($message->attachments as $attachment)
                                                        @if($attachment->isImage())
                                                            <img
                                                                src="{{ $attachment->url }}"
                                                                alt="{{ $attachment->file_name }}"
                                                                class="max-w-xs rounded-lg cursor-pointer hover:opacity-90 transition"
                                                                onclick="window.open('{{ $attachment->url }}', '_blank')"
                                                            />
                                                        @else
                                                            <a
                                                                href="{{ $attachment->url }}"
                                                                target="_blank"
                                                                class="flex items-center gap-2 p-2 bg-base-300/50 rounded-lg hover:bg-base-300 transition"
                                                            >
                                                                <x-icon name="o-document" class="w-5 h-5" />
                                                                <div class="flex-1 min-w-0">
                                                                    <p class="text-xs font-medium truncate">{{ $attachment->file_name }}</p>
                                                                    <p class="text-xs opacity-70">{{ $attachment->formatted_size }}</p>
                                                                </div>
                                                            </a>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if($message->reactions->count() > 0)
                                                <div class="flex flex-wrap gap-1 mt-2">
                                                    @php
                                                        $groupedReactions = $message->reactions->groupBy('emoji');
                                                    @endphp
                                                    @foreach($groupedReactions as $emoji => $reactions)
                                                        <button
                                                            @click="addReaction({{ $message->id }}, '{{ $emoji }}')"
                                                            class="px-2 py-0.5 text-xs rounded-full bg-base-300/50 hover:bg-base-300 transition flex items-center gap-1"
                                                        >
                                                            <span>{{ $emoji }}</span>
                                                            <span class="font-semibold">{{ $reactions->count() }}</span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <div class="flex items-center gap-1 mt-1">
                                                <span class="text-xs opacity-70">
                                                    {{ $message->created_at->format('g:i A') }}
                                                </span>
                                                @if($isOwn)
                                                    @if($message->read_at)
                                                        <span class="text-xs text-blue-500" title="Read at {{ $message->read_at->format('g:i A') }}">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M0 11l2-2 5 5L18 3l2 2L7 18z"/>
                                                                <path d="M0 11l2-2 5 5L18 3l2 2L7 18z" transform="translate(4, 0)"/>
                                                            </svg>
                                                        </span>
                                                    @else
                                                        <span class="text-xs opacity-50" title="Sent">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M0 11l2-2 5 5L18 3l2 2L7 18z"/>
                                                            </svg>
                                                        </span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Quick Actions -->
                                        <div class="absolute {{ $isOwn ? 'left-0' : 'right-0' }} top-0 hidden group-hover:flex gap-1 bg-base-200 rounded-lg shadow-lg p-1">
                                            <button
                                                @click="addReaction({{ $message->id }}, '👍')"
                                                class="btn btn-ghost btn-xs"
                                            >
                                                👍
                                            </button>
                                            <button
                                                wire:click="setReplyingTo({{ $message->id }})"
                                                class="btn btn-ghost btn-xs"
                                            >
                                                <x-icon name="o-arrow-uturn-left" class="w-4 h-4" />
                                            </button>
                                            @if($isOwn)
                                                <button
                                                    wire:click="deleteMessage({{ $message->id }})"
                                                    wire:confirm="Delete this message?"
                                                    class="btn btn-ghost btn-xs text-error"
                                                >
                                                    <x-icon name="o-trash" class="w-4 h-4" />
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center h-full text-center">
                            <x-icon name="o-chat-bubble-left-right" class="w-16 h-16 text-base-content/20 mb-4" />
                            <p class="text-base-content/60">No messages yet. Start the conversation!</p>
                        </div>
                    @endforelse
                </div>

                <!-- Message Input -->
                <div class="p-4 border-t border-base-300 bg-base-200">
                    @if($replyingTo)
                        @php
                            $replyMessage = $this->messages->firstWhere('id', $replyingTo);
                        @endphp
                        @if($replyMessage)
                            <div class="mb-2 p-2 bg-base-300 rounded-lg flex items-center justify-between">
                                <div class="flex items-center gap-2 text-sm">
                                    <x-icon name="o-arrow-uturn-left" class="w-4 h-4 text-primary" />
                                    <span class="text-base-content/70">
                                        Replying to <span class="font-semibold">{{ $replyMessage->user->name }}</span>:
                                        {{ Str::limit($replyMessage->body ?? 'Attachment', 50) }}
                                    </span>
                                </div>
                                <button wire:click="cancelReply" class="btn btn-ghost btn-xs btn-circle">
                                    <x-icon name="o-x-mark" class="w-4 h-4" />
                                </button>
                            </div>
                        @endif
                    @endif

                    @if(!empty($attachments))
                        <div class="mb-2 flex flex-wrap gap-2">
                            @foreach($attachments as $index => $attachment)
                                <div class="relative group">
                                    @if(str_starts_with($attachment->getMimeType(), 'image/'))
                                        <img
                                            src="{{ $attachment->temporaryUrl() }}"
                                            alt="Preview"
                                            class="w-20 h-20 object-cover rounded-lg"
                                        />
                                    @else
                                        <div class="w-20 h-20 bg-base-300 rounded-lg flex items-center justify-center">
                                            <x-icon name="o-document" class="w-8 h-8 text-base-content/50" />
                                        </div>
                                    @endif
                                    <button
                                        wire:click="removeAttachment({{ $index }})"
                                        class="absolute -top-2 -right-2 btn btn-error btn-xs btn-circle"
                                    >
                                        <x-icon name="o-x-mark" class="w-3 h-3" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form wire:submit.prevent="sendMessage" class="flex items-end gap-2">
                        <div>
                            <input
                                type="file"
                                wire:model="attachments"
                                id="file-input"
                                class="hidden"
                                multiple
                            />
                            <label for="file-input" class="btn btn-ghost btn-circle">
                                <x-icon name="o-paper-clip" class="w-5 h-5" />
                            </label>
                        </div>

                        <div class="flex-1">
                            <textarea
                                wire:model="body"
                                placeholder="Type a message..."
                                rows="1"
                                class="textarea textarea-bordered w-full resize-none"
                                @keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage(); }"
                                @input.debounce.500ms="whisperTyping()"
                            ></textarea>
                        </div>

                        <x-button spinner="sendMessage"
                            type="submit" icon="o-paper-airplane"
                            class="btn btn-primary btn-circle"
                            wire:loading.attr="disabled"
                        >
                        </x-button>
                    </form>
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-full text-center p-8">
                    <x-icon name="o-chat-bubble-left-ellipsis" class="w-24 h-24 text-base-content/20 mb-4" />
                    <h3 class="text-xl font-semibold text-base-content/70 mb-2">Select a conversation</h3>
                    <p class="text-base-content/50">Choose a conversation from the list to start messaging</p>
                </div>
            @endif
        </div>
    </div>

    <!-- New Chat Modal -->
    @if($showNewChatModal)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg mb-4">Start New Conversation</h3>

                <x-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search users..."
                    icon="o-magnifying-glass"
                    class="mb-4"
                />

                <div class="max-h-96 overflow-y-auto">
                    @forelse($this->availableUsers as $user)
                        <div
                            wire:click="startNewChat({{ $user->id }})"
                            class="flex items-center gap-3 p-3 hover:bg-base-200 rounded-lg cursor-pointer transition-colors"
                        >
                            <div class="avatar">
                                <div class="w-10 h-10 rounded-full">
                                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" />
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold text-base-content">{{ $user->name }}</h4>
                                <p class="text-sm text-base-content/60">{{ $user->email }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-base-content/60 py-8">No users found</p>
                    @endforelse
                </div>

                <div class="modal-action">
                    <button wire:click="$set('showNewChatModal', false)" class="btn">Close</button>
                </div>
            </div>
            <div class="modal-backdrop" wire:click="$set('showNewChatModal', false)"></div>
        </div>
    @endif

@script

<script>
  Alpine.data('chatApp', () => ({

    conversationId: @js($selectedConversationId),
    echoChannel: null,
    isTyping: false,
    typingUserName: @js($this->otherUser ? $this->otherUser->name : ''),
    typingTimeout: null,

    init() {
      console.log('🚀 Chat app initialized, conversation:', this.conversationId);

      // Setup Echo listener for real-time updates
      this.setupEcho();

      // Listen for conversation changes
      Livewire.on('conversationSelected', (data) => {
        console.log('📱 Conversation selected:', data.conversationId);
        this.conversationId = data.conversationId;

        // Leave old channel and join new one
        if (this.echoChannel) {
          Echo.leave(`chat.${this.conversationId}`);
        }
        this.setupEcho();
      });

      // Listen for message sent event
      Livewire.on('message-sent', (data) => {
        console.log('📨 Message sent, whispering to other user...');
        this.whisperNewMessage(data.messageId);
      });

      // Listen for messages marked as read
      Livewire.on('messages-marked-read', () => {
        console.log('✓✓ Messages marked as read, whispering...');
        if (this.echoChannel) {
          this.echoChannel.whisper('read', {
            conversationId: this.conversationId,
            readBy: {{ auth()->id() }},
            readAt: Date.now()
          });
        }
      });
    },

    setupEcho() {
      if (!window.Echo || !this.conversationId) {
        console.warn('⚠️ Echo not available or no conversation selected');
        return;
      }

      console.log('🔌 Setting up Echo for conversation:', this.conversationId);

      const channelName = `chat.${this.conversationId}`;
      this.echoChannel = Echo.private(channelName);

      this.echoChannel
        .listenForWhisper('new-message', (e) => {
          console.log('📨 New message (whisper):', e);
          this.refreshMessages();
        })
        .listen('.MessageUpdated', (e) => {
          console.log('✏️ Message updated:', e);
          this.refreshMessages();
        })
        .listen('.MessageDeleted', (e) => {
          console.log('🗑️ Message deleted:', e);
          this.refreshMessages();
        })
        .listenForWhisper('typing', (e) => {
          console.log('⌨️ User typing (whisper):', e);
          this.handleWhisperTyping(e);
        })
        .listenForWhisper('reaction', (e) => {
          console.log('😊 Reaction (whisper):', e);
          this.handleWhisperReaction(e);
        })
        .listenForWhisper('read', (e) => {
          console.log('👁️ Messages read (whisper):', e);
          // Just refresh UI to show blue checkmarks
          this.$wire.$refresh();
        });

      // Log subscription events
      this.echoChannel.subscription.bind('pusher:subscription_succeeded', () => {
        console.log('✅ Successfully subscribed to ' + channelName);
      });

      this.echoChannel.subscription.bind('pusher:subscription_error', (error) => {
        console.error('❌ Subscription error:', error);
      });
    },

    refreshMessages() {
      console.log('🔄 Refreshing messages...');
      // Call Livewire method to refresh
      this.$wire.call('refreshMessages');

      // Scroll to bottom after refresh
      setTimeout(() => {
        this.scrollToBottom();
      }, 200);
    },

    handleMessageSent() {
      console.log('✉️ Message sent locally');
      setTimeout(() => {
        this.scrollToBottom();
      }, 100);
    },

    scrollToBottom() {
      const container = document.getElementById('message-container');
      if (container) {
        container.scrollTop = container.scrollHeight;
        console.log('📜 Scrolled to bottom');
      }
    },

    whisperTyping() {
      if (!this.echoChannel) return;

      // Send whisper to other users
      this.echoChannel.whisper('typing', {
        typing: true,
        userName: '{{ auth()->user()->name }}'
      });

      console.log('📤 Whispered typing event');
    },

    whisperNewMessage(messageId) {
      if (!this.echoChannel) return;

      // Whisper to other user that new message arrived
      this.echoChannel.whisper('new-message', {
        messageId: messageId,
        senderId: {{ auth()->id() }},
        timestamp: Date.now()
      });

      console.log('📤 Whispered new message:', messageId);

      // Refresh own messages
      this.refreshMessages();
    },

    handleWhisperTyping(e) {
      console.log('📥 Received whisper typing:', e);

      // Show typing indicator
      this.isTyping = true;

      // Clear existing timeout
      if (this.typingTimeout) {
        clearTimeout(this.typingTimeout);
      }

      // Hide typing indicator after 3 seconds
      this.typingTimeout = setTimeout(() => {
        this.isTyping = false;
        console.log('⏱️ Typing indicator hidden');
      }, 3000);
    },

    addReaction(messageId, emoji) {
      // Call Livewire to save reaction
      this.$wire.call('toggleReaction', messageId, emoji);

      // Whisper to other user
      if (this.echoChannel) {
        this.echoChannel.whisper('reaction', {
          messageId: messageId,
          emoji: emoji,
          userId: {{ auth()->id() }}
        });
        console.log('📤 Whispered reaction:', emoji);
      }
    },

    handleWhisperReaction(e) {
      console.log('📥 Received reaction whisper:', e);
      // Refresh messages to show new reaction
      this.refreshMessages();
    },

    destroy() {
      if (this.echoChannel) {
        console.log('👋 Leaving channel:', this.conversationId);
        Echo.leave(`chat.${this.conversationId}`);
      }
      if (this.typingTimeout) {
        clearTimeout(this.typingTimeout);
      }
    }
  }));
</script>
@endscript
</div>
