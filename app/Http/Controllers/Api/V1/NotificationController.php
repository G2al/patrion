<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;

final class NotificationController extends ApiController
{
    public function index(Request $request)
    {
        return $this->ok(['unread_count' => $request->user()->unreadNotifications()->count(), 'notifications' => $request->user()->notifications()->latest()->limit(50)->get()]);
    }

    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->ok(['read' => true]);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->ok(['read' => true]);
    }
}
