<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Image\Exceptions\CouldNotLoadImage;

/**
 * Handles the signed-in user's profile and account deletion.
 */
class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('userzone.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Updates name, email and photo; an unreadable photo leaves the profile unchanged.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // The photo goes first: if it cannot be read, nothing else in the profile is saved
        if ($request->hasFile('photo')) {
            try {
                $request->user()->setProfilePhoto($request->file('photo'));
            } catch (CouldNotLoadImage) {
                throw ValidationException::withMessages(['photo' => 'We could not read that image. Try another photo.']);
            }
        }

        $request->user()->fill($request->safe()->except('photo'));

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Deletes the account after confirming the password.
     */
    public function destroyPhoto(Request $request): RedirectResponse
    {
        $request->user()->removeProfilePhoto();

        return Redirect::route('profile.edit')->with('status', 'photo-removed');
    }

    /**
     * Delete the user's account, after asking for the password again, and end the session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->deleteWithData();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
