<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\ForgotPasswordRequest;
use App\Http\Requests\Staff\LoginLinkRequest;
use App\Http\Requests\Staff\LoginRequest;
use App\Http\Requests\Staff\ResetPasswordRequest;
use App\Services\Staff\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Display the login view or redirect to staff index page if already logged in.
     */
    public function login(Request $request): View|RedirectResponse
    {
        // Retrieve encrypted email and password from the request, default to null if not present
        $email = $request->get('e', null);
        $password = $request->get('p', null);

        // If both email and password are present, decrypt them
        if ($email && $password) {
            $email = Crypt::decryptString($email);
            $password = Crypt::decryptString($password);
        }

        // Check if the staff user is already authenticated
        if (auth()->guard('staff')->check()) {
            // Retrieve the intended URL from the session
            $intendedUrl = session('url.intended');

            // Only parse URL if it exists
            if ($intendedUrl) {
                // Parse the intended URL to extract its segments
                $segments = explode('/', parse_url($intendedUrl, PHP_URL_PATH) ?? '');

                // Check if the second segment is not 'staff'
                if (isset($segments[2]) && $segments[2] !== 'staff') {
                    // If not 'staff', redirect to the staff index page
                    return redirect()->route('staff.index');
                }
            }

            // Proceed with the intended redirect (or default to staff.index)
            return redirect()->intended(route('staff.index'));
        }

        // If not authenticated, display the login view with the decrypted email and password
        return view('staff.auth.login', compact('email', 'password'));
    }

    /**
     * Authenticate the staff user and log them in.
     */
    public function postLogin(LoginRequest $request, AuthService $authService): RedirectResponse
    {
        // Get validated request fields
        $input = $request->validated();

        // Attempt to authenticate the staff user using the AuthService
        $success = $authService->login($input);

        // Check if the authentication was successful
        if ($success) {
            // Retrieve the intended URL from the session
            $intendedUrl = session('url.intended');

            // Only parse URL if it exists
            if ($intendedUrl) {
                // Parse the intended URL to extract its segments
                $segments = explode('/', parse_url($intendedUrl, PHP_URL_PATH) ?? '');

                // Check if the second segment is not 'staff'
                if (isset($segments[2]) && $segments[2] !== 'staff') {
                    // If not 'staff', redirect to the staff index page
                    return redirect()->route('staff.index');
                }
            }

            // Proceed with the intended redirect (or default to staff.index)
            return redirect()->intended(route('staff.index'));
        }

        // If authentication was not successful, redirect back to the login page with an error message and preserve input (except password)
        return redirect()->route('staff.login')->with('error', trans('common.login_not_recognized'))->withInput($request->except('password'));
    }

    /**
     * Display the forgot password view or redirect to the staff index page if the user is already logged in.
     *
     * @param  Request  $request  The incoming request instance.
     * @return View|RedirectResponse Returns the forgot password view or a redirect response to the staff index.
     */
    public function forgotPassword(Request $request): View|RedirectResponse
    {
        return (auth()->guard('staff')->check())
            ? redirect()->route('staff.index')
            : view('staff.auth.forgot-password');
    }

    /**
     * Send a password reset link to the user's email.
     *
     * @param  ForgotPasswordRequest  $request  The incoming request instance containing the email.
     * @param  AuthService  $authService  The authentication service instance.
     * @return RedirectResponse Returns a redirect response based on the success of sending the reset link.
     */
    public function postForgotPassword(ForgotPasswordRequest $request, AuthService $authService): RedirectResponse
    {
        // Get validated request fields
        $input = $request->validated();

        // Send reset link
        $success = $authService->sendResetPasswordLink($input['email']);

        return ($success)
            ? redirect()->route('staff.forgot_password')->with('success', trans('common.reset_link_has_been_sent_to_email', ['email' => '<u>'.$input['email'].'</u>']))
            : redirect()->route('staff.forgot_password')->with('error', trans('common.user_not_found'))->withInput();
    }

    /**
     * Display the reset password view or redirect to the staff index page if the user is already logged in.
     *
     * @param  Request  $request  The incoming request instance.
     * @return View|RedirectResponse Returns the reset password view or a redirect response to the staff index.
     */
    public function resetPassword(Request $request): View|RedirectResponse
    {
        // Post reset link
        $postResetLink = URL::temporarySignedRoute(
            'staff.reset_password.post',
            now()->addMinutes(120),
            [
                'email' => $request->email,
            ]
        );

        return (auth()->guard('staff')->check())
            ? redirect()->route('staff.index')
            : view('staff.auth.reset-password', compact('postResetLink'));
    }

    /**
     * Update the user's password.
     *
     * @param  ResetPasswordRequest  $request  The incoming request instance containing the new password.
     * @param  AuthService  $authService  The authentication service instance.
     * @return RedirectResponse Returns a redirect response based on the success of the password update.
     */
    public function postResetPassword(ResetPasswordRequest $request, AuthService $authService): RedirectResponse
    {
        // Get validated request fields
        $input = $request->validated();

        // Send reset link
        $success = $authService->updatePassword($input['email'], $input['password']);

        return ($success)
            ? redirect()->route('staff.login')->with('success', trans('common.login_with_new_password'))->withInput(['email' => $input['email']])
            : redirect($request->getRequestUri())->with('error', trans('common.unknown_error'));
    }

    /**
     * Authenticate and log in a staff user using a login link.
     *
     * @param  LoginLinkRequest  $request  The incoming request instance containing the email.
     * @param  AuthService  $authService  The authentication service instance.
     * @return RedirectResponse Returns a redirect response based on the success of the login.
     */
    public function loginLink(LoginLinkRequest $request, AuthService $authService): RedirectResponse
    {
        if (! auth()->guard('staff')->check()) {
            $user = $authService->login($request->email);
        } else {
            $user = auth()->guard('staff')->user();
        }

        $redir = $request->intended;

        return $user
            ? redirect($redir)
            : redirect()->route('staff.login');
    }

    /**
     * Log out the currently authenticated staff user and redirect them to the login page.
     *
     * @param  Request  $request  The incoming request instance.
     * @return RedirectResponse Returns a redirect response to the staff login page.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('staff')->logout();

        $request->session()->flash('success', trans('common.logout_success'));

        return redirect()->route('staff.login');
    }
}
