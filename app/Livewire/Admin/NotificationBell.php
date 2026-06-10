<?php

namespace App\Livewire\Admin;

use App\Models\Notification;
use Livewire\Component;

class NotificationBell extends Component
{
    protected $listeners = ['notificationStatusUpdated' => '$refresh'];

    public function render()
    {
        $unreadCount = Notification::where('user_id', auth()->id())
            ->where('read', false)
            ->count();

        return view('livewire.admin.notification-bell', [
            'unreadCount' => $unreadCount
        ]);
    }
}
