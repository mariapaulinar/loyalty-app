<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Services\Data\DataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Impersonate a user with the specified ID and guard.
     *
     * @param  string  $locale  The current locale.
     * @param  string  $dataDefinitionName  The name of the data definition associated with the user model.
     * @param  Request  $request  The incoming HTTP request.
     * @param  DataService  $dataService  The data service instance to find the data definition.
     * @param  string  $guard  The authentication guard used for the impersonated user.
     * @param  string  $id  The ID of the user to impersonate.
     * @return \Illuminate\Http\RedirectResponse A redirect response to the impersonated user's index with a success message.
     *
     * @throws \Exception If the data definition is not found.
     */
    public function impersonate(string $locale, string $dataDefinitionName, Request $request, DataService $dataService, string $guard, string $id)
    {
        // Find the data definition by name
        $dataDefinition = $dataService->findDataDefinitionByName($dataDefinitionName);

        // If the data definition is found, create a new instance, otherwise throw an exception
        if ($dataDefinition !== null) {
            $dataDefinition = new $dataDefinition;
        } else {
            throw new \Exception('Data definition "'.$dataDefinitionName.'" not found');
        }

        // Retrieve settings for the data definition
        $settings = $dataDefinition->getSettings([]);

        // Obtain the user type from the route name (member, staff, partner, or admin)
        // and verify if the Data Definition is permitted for that user type
        $routeGuard = explode('.', $request->route()->getName())[0];
        if ($settings['guard'] !== $routeGuard) {
            Log::notice('app\Http\Controllers\Data\AuthController.php - Impersonate not allowed for '.$routeGuard.', '.$settings['guard'].' required');
            abort(404);
        }

        // Store the current user ID and guard in the session to return later
        session()->put('impersonate.'.$routeGuard, [
            'user_id' => Auth::guard($guard)->id(),
            'guard' => Auth::guard($guard)->name,
        ]);

        // Login as the impersonated user using the specified guard
        $impersonatedUser = Auth::guard($guard)->loginUsingId($id, true);

        // Prepare a success message for the impersonation
        $message = [
            'type' => 'success',
            'size' => 'lg',
            'text' => trans('common.impersonated_as_user', ['user' => $impersonatedUser->name.' ('.$impersonatedUser->email.')']),
        ];

        // Redirect to the impersonated user's index with a success message
        return redirect(route($guard.'.index'))->with('toast', $message);
    }
}
