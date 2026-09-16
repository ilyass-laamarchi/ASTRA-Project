<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Accepts public contact requests and notifies active ASTRA staff. */
class ContactController extends Controller
{
    /** Receives the notification service used after a message is stored. */
    public function __construct(private NotificationService $notifications) {}

    /** Validates and stores a public inquiry, then alerts staff. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:160'], 'phone' => ['nullable', 'string', 'max:30'], 'message' => ['required', 'string', 'max:4000']]);
        $inquiry = ContactInquiry::create($data);
        $this->notifications->staff('contact.created', 'Nouveau message de contact', 'Un message de '.$inquiry->name.' attend une réponse.', ['contact_inquiry_id' => $inquiry->id]);

        return response()->json(['message' => 'Votre message a bien été transmis.'], 201);
    }
}
