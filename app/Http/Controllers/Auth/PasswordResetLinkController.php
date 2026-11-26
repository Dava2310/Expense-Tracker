<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Handles sending the password reset link.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Send the reset link and show the result
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', 'We have emailed your password reset link.')
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => $this->statusMessage($status)]);
    }

    /**
     * Resolve the password reset link status key to an English message.
     */
    private function statusMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER => "We can't find a user with that email address.",
            Password::RESET_THROTTLED => 'Please wait before retrying.',
            default => 'Unable to send password reset link.',
        };
    }
}
