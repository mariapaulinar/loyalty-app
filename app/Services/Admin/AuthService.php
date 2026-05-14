<?php

namespace App\Services\Admin;

use App\Notifications\Admin\ResetPassword;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class AuthService
{
    /**
     * Retrieve active admin with login credentials.
     *
     * @param  array  $login  Login credentials.
     * @return object|null Admin object if active, otherwise null.
     */
    public function login(array $login)
    {
        $adminService = resolve('App\Services\Admin\AdminService');
        $admin = $adminService->findActiveByEmail($login['email']);

        $authenticated = false;

        if ($admin) {
            if (Hash::check($login['password'], $admin->password)) {
                if ($admin->is_active == 1 || session()->get('impersonate.admin')) {
                    if (! $admin->email_verified_at) {
                        $admin->email_verified_at = Carbon::now('UTC');
                    }

                    // Update login stats
                    $admin->number_of_times_logged_in = $admin->number_of_times_logged_in + 1;
                    $admin->last_login_at = Carbon::now('UTC');
                    $admin->save();

                    // Successfully authenticated
                    $authenticated = true;
                }
            }
        }

        if ($authenticated) {
            Auth::guard('admin')->login($admin, (bool) $login['remember']);

            return $admin;
        } else {
            return null;
        }
    }

    /**
     * Send login link.
     *
     * @param  array  $login  Login credentials.
     * @return string Admin email address.
     */
    public function sendLoginLink(array $login): string
    {
        // Login link
        $url = URL::temporarySignedRoute(
            'admin.login.link',
            now()->addMinutes(30),
            [
                'email' => $login['email'],
                'intended' => redirect()->intended(route('admin.index'))->getTargetUrl(),
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
     * @return object|null Admin object if active and found, otherwise null.
     */
    public function sendResetPasswordLink(string $email)
    {
        $adminService = resolve('App\Services\Admin\AdminService');
        $admin = $adminService->findActiveByEmail($email);

        if ($admin) {
            // Set URL defaults to use the admin's preferred locale
            // This ensures the reset link includes the correct locale segment (e.g., /nl-nl/admin/reset-password)
            set_url_locale_for_user($admin);

            // Reset link
            $resetLink = URL::temporarySignedRoute(
                'admin.reset_password',
                now()->addMinutes(120),
                [
                    'email' => $email,
                ]
            );

            // Send reset link
            $admin->notify(new ResetPassword($resetLink));
        }

        return $admin;
    }

    /**
     * Update password.
     *
     * @param  string  $email  Email address.
     * @param  string  $password  New password.
     * @return object|null Admin object if active and found, otherwise null.
     */
    public function updatePassword(string $email, string $password)
    {
        $adminService = resolve('App\Services\Admin\AdminService');
        $admin = $adminService->findActiveByEmail($email);

        if ($admin) {
            $admin->password = bcrypt($password);
            $admin->save();
        }

        return $admin;
    }
}
