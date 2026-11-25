<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('notifications.view'), 403);
        $notifications = $request->user()->notifications()->latest()->paginate(20);
        return view('admin.notifications', compact('notifications'));
    }
    public function read(Request $request, string $id)
    {
        abort_unless($request->user()->hasPermission('notifications.view'), 403);
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();
        return redirect($notification->data['url'] ?? route('notifications.index'));
    }
    public function readAll(Request $request)
    {
        abort_unless($request->user()->hasPermission('notifications.view'), 403);
        $request->user()->unreadNotifications()->update(['read_at'=>now()]);
        return back()->with('success','All notifications marked as read.');
    }
}
