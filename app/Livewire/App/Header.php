<?php

namespace App\Livewire\App;

use App\Models\Conversation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class Header extends Component
{
    public function switchLanguage($locale)
    {
        if (in_array($locale, ['en', 'ar', 'bn'])) {
            App::setLocale($locale);
            Session::put('locale', $locale);
            //          $this->redirect(back(), navigate: true);
            $this->redirect(url()->previous(), navigate: true);
        }
    }

    public function getUnreadConversationsProperty()
    {
        return auth()->user()
            ->conversations()
            ->with(['userOne', 'userTwo', 'latestMessage'])
            ->get()
            ->filter(function ($conversation) {
                return $conversation->getUnreadCount(auth()->id()) > 0;
            })
            ->sortByDesc(function ($conversation) {
                return $conversation->latestMessage?->created_at;
            })
            ->take(5);
    }

    public function getTotalUnreadCountProperty()
    {
        return $this->unreadConversations->sum(function ($conversation) {
            return $conversation->getUnreadCount(auth()->id());
        });
    }

    public function render()
    {
        return view('livewire.app.header');
    }
}
