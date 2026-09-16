<?php

// Private channels enforce user ownership and staff membership for Reverb broadcasts.

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{id}', fn (User $user, int $id): bool => $user->id === $id);
Broadcast::channel('operations', fn (User $user): bool => $user->isStaff());
