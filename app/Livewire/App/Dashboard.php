<?php

namespace App\Livewire\App;

use App\Models\GuestSubscription;
use App\Notifications\WelcomeNotification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Minishlink\WebPush\WebPush;

#[Title('Dashboard')]
#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function mount(): void
    {
        $this->authorize('dashboard.view');
    }

    public function render()
    {
//      \auth()->user()->notify(new WelcomeNotification('te'));

      return view('livewire.app.dashboard');
    }
}
