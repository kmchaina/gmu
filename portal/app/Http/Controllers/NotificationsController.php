<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function markRead(\App\Models\GmuNotification $notification)
    {
        $this->authorize('update', $notification);

        $notification->markAsRead();
        return back();
    }
}
