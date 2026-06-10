<?php

namespace App\Livewire\Admin;

use App\Models\Notification;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationsPanel extends Component
{
    use WithPagination;

    protected $listeners = ['refreshNotifications' => '$refresh'];

    public function markAsRead($id)
    {
        $notification = Notification::find($id);
        if ($notification) {
            $notification->update(['read' => true]);
            $this->dispatch('notificationStatusUpdated');
        }
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('read', false)
            ->update(['read' => true]);
        
        $this->dispatch('notificationStatusUpdated');
    }

    public function delete($id)
    {
        $notification = Notification::find($id);
        if ($notification) {
            $notification->delete();
            $this->dispatch('notificationStatusUpdated');
        }
    }

    public function clearAll()
    {
        Notification::where('user_id', auth()->id())->delete();
        $this->dispatch('notificationStatusUpdated');
    }

    public function render()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        $unreadCount = Notification::where('user_id', auth()->id())
            ->where('read', false)
            ->count();

        return view('livewire.admin.notifications-panel', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ]);
    }
}
