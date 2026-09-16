<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/** Handles token authentication and self-service profile updates. */
class AuthController extends Controller
{
    /** Registers a public account as an active client and returns a Sanctum token. */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->safe()->only(['first_name', 'last_name', 'email', 'phone', 'password']);
        $user = User::create([...$data, 'role' => User::ROLE_CLIENT, 'is_active' => true]);

        return response()->json(['user' => $user, 'token' => $user->createToken('astra-web')->plainTextToken], 201);
    }

    /** Verifies credentials, rejects inactive accounts, and starts a fresh token session. */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', $credentials['email'])->first();
        abort_unless($user && Hash::check($credentials['password'], $user->password), 422, 'Identifiants incorrects.');
        abort_unless($user->is_active, 403, 'Ce compte est désactivé.');
        $user->tokens()->delete();

        return response()->json(['user' => $user, 'token' => $user->createToken('astra-web')->plainTextToken]);
    }

    /** Returns the authenticated user so Pinia can refresh shared identity state. */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    /** Updates only the authenticated user's basic profile fields. */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['first_name' => ['required', 'string', 'max:80'], 'last_name' => ['required', 'string', 'max:80'], 'email' => ['required', 'email', 'unique:users,email,'.$request->user()->id], 'phone' => ['nullable', 'string', 'max:30']]);
        $request->user()->update($data);

        return response()->json(['user' => $request->user()->fresh()]);
    }

    /** Changes the current password and revokes other active API tokens. */
    public function password(Request $request): JsonResponse
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'string', 'min:10', 'confirmed']]);
        $request->user()->update(['password' => $data['password']]);
        $request->user()->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Mot de passe mis à jour.']);
    }

    /** Stores a validated avatar on the persistent public disk and removes the previous file. */
    public function avatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072']]);
        $user = $request->user();
        if ($user->avatar_path) {
            $oldPath = (string) (parse_url($user->avatar_path, PHP_URL_PATH) ?: $user->avatar_path);
            if (str_starts_with($oldPath, '/storage/')) {
                Storage::disk('public')->delete(substr($oldPath, strlen('/storage/')));
            }
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        abort_unless($path && Storage::disk('public')->exists($path), 500, 'La photo n’a pas pu être enregistrée.');
        $user->update(['avatar_path' => Storage::disk('public')->url($path)]);

        return response()->json(['user' => $user->fresh()]);
    }

    /** Revokes the bearer token used for the current request. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /** Returns Google's OAuth consent URL only when server credentials exist. */
    public function googleRedirect(): JsonResponse
    {
        abort_unless(
            filled(config('services.google.client_id')) && filled(config('services.google.client_secret')),
            503,
            'Connexion Google temporairement indisponible.'
        );

        return response()->json([
            'url' => Socialite::driver('google')->stateless()->redirect()->getTargetUrl(),
        ]);
    }

    /** Requests a password-reset email without revealing whether the account exists. */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'Si ce compte existe, un lien de réinitialisation a été envoyé.']);
    }

    /** Applies a valid reset token, updates the password, and revokes existing sessions. */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string'], 'email' => ['required', 'email'], 'password' => ['required', 'string', 'min:10', 'confirmed']]);
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            event(new PasswordReset($user));
        });
        abort_unless($status === Password::PASSWORD_RESET, 422, __($status));

        return response()->json(['message' => 'Mot de passe réinitialisé.']);
    }

    /** Creates or reuses a Google user as a client, then redirects a token to the frontend. */
    public function googleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = User::where('email', $googleUser->getEmail())->first();

            if (! $user) {
                $user = User::create([
                    'first_name' => $googleUser->user['given_name'] ?? current(explode(' ', $googleUser->getName())),
                    'last_name' => $googleUser->user['family_name'] ?? '',
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(Str::random(24)),
                    'role' => User::ROLE_CLIENT,
                    'is_active' => true,
                ]);
            }

            abort_unless($user->is_active, 403, 'Ce compte est désactivé.');

            $user->tokens()->delete();
            $token = $user->createToken('astra-web')->plainTextToken;

            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

            return redirect("$frontendUrl/auth/callback?token=$token");
        } catch (\Exception $e) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

            return redirect("$frontendUrl/login?error=oauth_failed");
        }
    }
}
