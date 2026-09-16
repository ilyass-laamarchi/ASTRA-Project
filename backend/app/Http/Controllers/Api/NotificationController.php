<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AstraNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Provides private notification feeds, read state, and per-user preferences. */
class NotificationController extends Controller
{
    /** Lists only the authenticated user's notifications, optionally unread only. */
    public function index(Request $request): JsonResponse
    {
        $query = AstraNotification::where('user_id', $request->user()->id)->latest();
        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        return response()->json(['data' => $query->paginate(20)]);
    }

    /** Marks one owned notification as read. */
    public function read(Request $request, AstraNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

        return response()->json(['data' => $notification->fresh()]);
    }

    /** Marks all unread notifications owned by the current user as read. */
    public function readAll(Request $request): JsonResponse
    {
        AstraNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'Notifications marquées comme lues.']);
    }

    /** Returns saved notification preferences with safe defaults. */
    public function preferences(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->notification_preferences ?? ['reservations' => true, 'payments' => true, 'system' => true]]);
    }

    /** Validates and saves the current user's notification channel preferences. */
    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate(['reservations' => ['required', 'boolean'], 'payments' => ['required', 'boolean'], 'system' => ['required', 'boolean']]);
        $request->user()->update(['notification_preferences' => $data]);

        return response()->json(['data' => $data]);
    }
}
