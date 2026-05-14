<?php

namespace App\Services\Staff;

use App\Notifications\Staff\ResetPassword;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class AuthService
{
    /**
     * Retrieve active staff with login credentials.
     *
     * @param  array  $login  Login credentials.
     * @return object|null Staff object if active, otherwise null.
     */
    public function login(array $login)
    {
        $staffService = resolve('App\Services\Staff\StaffService');
        $staff = $staffService->findActiveByEmail($login['email']);

        $authenticated = false;

        if ($staff) {
            if (Hash::check($login['password'], $staff->password)) {
                if ($staff->is_active == 1) {
                    if (! $staff->email_verified_at) {
                        $staff->email_verified_at = Carbon::now('UTC');
                    }

                    // Update login stats
                    $staff->number_of_times_logged_in = $staff->number_of_times_logged_in + 1;
                    $staff->last_login_at = Carbon::now('UTC');
                    $staff->save();

                    // Successfully authenticated
                    $authenticated = true;
                }
            }
        }

        if ($authenticated) {
            Auth::guard('staff')->login($staff, (bool) $login['remember']);

            return $staff;
        } else {
            return null;
        }
    }

    /**
     * Send login link.
     *
     * @param  array  $login  Login credentials.
     * @return string Staff email address.
     */
    public function sendLoginLink(array $login): string
    {
        // Login link
        $url = URL::temporarySignedRoute(
            'staff.login.link',
            now()->addMinutes(30),
            [
                'email' => $login['email'],
                'intended' => redirect()->intended(route('staff.index'))->getTargetUrl(),
            ]
        );

        // Send login mail
        Notification::route('mail', $login['email'])
            ->notify(new Login($url));

        return $login['email'];
    }

    /**
     * Send link to reset password.
     *
     * @param  string  $email  Email address.
     * @return object|null Staff object if active and found, otherwise null.
     */
    public function sendResetPasswordLink(string $email)
    {
        $staffService = resolve('App\Services\Staff\StaffService');
        $staff = $staffService->findActiveByEmail($email);

        if ($staff) {
            // Set URL defaults to use the staff's preferred locale
            // This ensures the reset link includes the correct locale segment (e.g., /nl-nl/staff/reset-password)
            set_url_locale_for_user($staff);

            // Reset link
            $resetLink = URL::temporarySignedRoute(
                'staff.reset_password',
                now()->addMinutes(120),
                [
                    'email' => $email,
                ]
            );

            // Send reset link
            $staff->notify(new ResetPassword($resetLink));
        }

        return $staff;
    }

    /**
     * Update password.
     *
     * @param  string  $email  Email address.
     * @param  string  $password  New password.
     * @return object|null Staff object if active and found, otherwise null.
     */
    public function updatePassword(string $email, string $password)
    {
        $staffService = resolve('App\Services\Staff\StaffService');
        $staff = $staffService->findActiveByEmail($email);

        if ($staff) {
            $staff->password = bcrypt($password);
            $staff->save();
        }

        return $staff;
    }
}
