<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForgotPasswordRequest;
use App\Http\Requests\Admin\LoginLinkRequest;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Services\Admin\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Class AuthController
 *
 * Handles authentication related tasks for the admin users.
 */
class AuthController extends Controller
{
    /**
     * Display the login view or redirect to admin index page if already logged in.
     *
     * This method checks if the admin user is already logged in. If so, it redirects
     * them to the admin index page. Otherwise, it displays the login view.
     */
    public function login(Request $request): View|RedirectResponse
    {
        // Check if the admin user is already logged in
        if (auth()->guard('admin')->check()) {
            // Get the intended URL from the session
            $intendedUrl = session('url.intended');

            // Only parse URL if it exists
            if ($intendedUrl) {
                // Parse the intended URL to get its segments
                $segments = explode('/', trim(parse_url($intendedUrl, PHP_URL_PATH) ?? '', '/'));

                // Check if the second segment of the intended URL is not 'admin'
                if (isset($segments[1]) && $segments[1] !== 'admin') {
                    // If not 'admin', redirect to the admin index page
                    return redirect()->route('admin.index');
                }
            }

            // Proceed with the intended redirect (or default to admin.index)
            return redirect()->intended(route('admin.index'));
        }

        // If not logged in, display the login view
        return view('admin.auth.login');
    }

    /**
     * Authenticate user and log them in.
     *
     * This method attempts to log in the admin user using the credentials provided
     * in the request. If successful, the user is redirected to the admin index page.
     * If not, the user is redirected back to the login page with an error message.
     */
    public function postLogin(LoginRequest $request, AuthService $authService): RedirectResponse
    {
        // Get validated request fields
        $input = $request->validated();

        // Attempt to log in the admin user using the AuthService
        $success = $authService->login($input);

        // Check if the login was successful
        if ($success) {
            // Get the intended URL from the session
            $intendedUrl = session('url.intended');

            // Only parse URL if it exists
            if ($intendedUrl) {
                // Parse the intended URL to get its segments
                $segments = explode('/', trim(parse_url($intendedUrl, PHP_URL_PATH) ?? '', '/'));

                // Check if the second segment of the intended URL is not 'admin'
                if (isset($segments[1]) && $segments[1] !== 'admin') {
                    // If not 'admin', redirect to the admin index page
                    return redirect()->route('admin.index');
                }
            }

            // Proceed with the intended redirect (or default to admin.index)
            return redirect()->intended(route('admin.index'));
        }

        // If login was not successful, redirect back to the login page with an error message and preserve input (except password)
        return redirect()->route('admin.login')
            ->with('error', trans('common.login_not_recognized'))
            ->withInput($request->except('password'));

        // Login link
        /*
        $authService->sendLoginLink($input);

        return view('admin.auth.link-sent', [
            'email' =>  $request->email
        ]);
        */
    }

    /**
     * Display the forgot password view or redirect to admin index page if already logged in.
     *
     * This method checks if the admin user is already logged in. If so, it redirects
     * them to the admin index page. Otherwise, it displays the forgot password view.
     */
    public function forgotPassword(Request $request): View|RedirectResponse
    {
        // Check if the admin user is already logged in
        if (auth()->guard('admin')->check()) {
            // If logged in, redirect to the admin index page
            return redirect()->route('admin.index');
        } else {
            // If not logged in, display the forgot password view
            return view('admin.auth.forgot-password');
        }
    }

    /**
     * Send password reset link to the user's email.
     *
     * This method attempts to send a password reset link to the user's email using
     * the AuthService. If successful, a success message is displayed, otherwise
     * an error message is shown and input is preserved.
     */
    public function postForgotPassword(ForgotPasswordRequest $request, AuthService $authService): RedirectResponse
    {
        // Get validated request fields
        $input = $request->validated();

        // Attempt to send a password reset link to the user's email using the AuthService
        $success = $authService->sendResetPasswordLink($input['email']);

        // Check if the password reset link was sent successfully
        if ($success) {
            // If successful, redirect back to the forgot password page with a success message
            return redirect()->route('admin.forgot_password')
                ->with('success', trans('common.reset_link_has_been_sent_to_email', ['email' => '<u>'.$input['email'].'</u>']));
        } else {
            // If not successful, redirect back to the forgot password page with an error message and preserve input
            return redirect()->route('admin.forgot_password')
                ->with('error', trans('common.user_not_found'))
                ->withInput();
        }
    }

    /**
     * Display the reset password view or redirect to admin index page if already logged in.
     *
     * This method checks if the admin user is already logged in. If so, it redirects
     * them to the admin index page. Otherwise, it creates a temporary signed route
     * for resetting the password and displays the reset password view.
     */
    public function resetPassword(Request $request): View|RedirectResponse
    {
        // Create a temporary signed route for resetting the password
        $postResetLink = URL::temporarySignedRoute(
            'admin.reset_password.post',
            now()->addMinutes(120),
            [
                'email' => $request->email,
            ]
        );

        // Check if the admin user is already logged in
        if (auth()->guard('admin')->check()) {
            // If logged in, redirect to the admin index page
            return redirect()->route('admin.index');
        } else {
            // If not logged in, display the reset password view with the temporary signed route
            return view('admin.auth.reset-password', compact('postResetLink'));
        }
    }

    /**
     * Update the user's password.
     *
     * This method attempts to update the user's password using the AuthService.
     * If the password update is successful, the user is redirected to the admin
     * login page with a success message. Otherwise, they are redirected back to
     * the reset password page with an error message.
     */
    public function postResetPassword(ResetPasswordRequest $request, AuthService $authService): RedirectResponse
    {
        // Get validated request fields
        $input = $request->validated();

        // Attempt to update the user's password using the AuthService
        $success = $authService->updatePassword($input['email'], $input['password']);

        // Check if the password update was successful
        if ($success) {
            // If successful, redirect to the admin login page with a success message and the updated email
            return redirect()->route('admin.login')
                ->with('success', trans('common.login_with_new_password'))
                ->withInput(['email' => $input['email']]);
        } else {
            // If not successful, redirect back to the reset password page with an error message
            return redirect($request->getRequestUri())
                ->with('error', trans('common.unknown_error'));
        }
    }

    /**
     * Log in an admin user using a login link.
     *
     * This method checks if the admin user is already logged in. If not, it attempts
     * to log them in using the email from the request. If the login is successful,
     * the user is redirected to the intended URL. Otherwise, they are redirected to
     * the admin login page.
     */
    public function loginLink(LoginLinkRequest $request, AuthService $authService): RedirectResponse
    {
        // Check if the admin is already logged in
        if (! auth()->guard('admin')->check()) {
            // If not logged in, attempt to log them in using the email from the request
            $user = $authService->login($request->email);
        } else {
            // If logged in, get the authenticated admin user
            $user = auth()->guard('admin')->user();
        }

        // Get the intended redirect URL from the request
        $redir = $request->intended;

        // Check if the login was successful
        if ($user) {
            // If successful, redirect the user to the intended URL
            return redirect($redir);
        } else {
            // If not successful, redirect to the admin login page
            return redirect()->route('admin.login');
        }
    }

    /**
     * Log out an admin user and redirect to the login page or the original user's dashboard.
     *
     * This method logs out the currently authenticated admin user, flashes a success
     * message to the session, and then redirects the user to the admin login page
     * or the original user's dashboard if the admin was impersonating another user.
     */
    public function logout(Request $request): RedirectResponse
    {
        // Get the original user ID and guard from the session if impersonating
        $originalData = session()->get('impersonate.admin');

        // If impersonating, log out impersonated user and log back in as original user
        if ($originalData) {
            $originalUserId = $originalData['user_id'];
            $originalGuard = $originalData['guard'];

            // Logout the impersonated user using the 'admin' guard
            Auth::guard('admin')->logout();

            // Login as the original user using the original guard
            Auth::guard($originalGuard)->loginUsingId($originalUserId);

            // Remove the impersonate key from the session
            session()->forget('impersonate.admin');

            // Redirect to the original user's dashboard
            return redirect()->route($originalGuard.'.index');
        }

        // Log out the currently authenticated admin user
        Auth::guard('admin')->logout();

        // Flash a success message to the session indicating a successful logout
        $request->session()->flash('success', trans('common.logout_success'));

        // Redirect the user to the admin login page
        return redirect()->route('admin.login');
    }
}
