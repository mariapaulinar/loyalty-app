<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Services\Data\DataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

class InsertController extends Controller
{
    /**
     * Show the create form for the given data definition.
     *
     * @param  string  $locale  The current locale.
     * @param  string  $dataDefinitionName  The name of the data definition to retrieve.
     * @param  Request  $request  The incoming HTTP request.
     * @param  DataService  $dataService  The data service to fetch the data definition.
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function showInsertItem(string $locale, string $dataDefinitionName, Request $request, DataService $dataService)
    {
        // Get the data definition instance
        $dataDefinition = $this->getDataDefinitionInstance($dataDefinitionName, $dataService);
        // Fetch the column data for the given data definition (insert mode)
        $form = $dataDefinition->getData($dataDefinition->name, 'insert');
        // Get settings for the data definition
        $settings = $dataDefinition->getSettings([]);
        // Validate user access based on settings and request
        $this->validateAccess($settings, $request);

        // Return the insert view with the required data
        return view('data.insert', compact('dataDefinition', 'form', 'settings'));
    }

    /**
     * Create the record with the submitted data.
     *
     * @param  string  $locale  The current locale.
     * @param  string  $dataDefinitionName  The name of the data definition to retrieve.
     * @param  Request  $request  The incoming HTTP request.
     * @param  DataService  $dataService  The data service to fetch the data definition.
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function postInsertItem(string $locale, string $dataDefinitionName, Request $request, DataService $dataService)
    {
        // Get the data definition instance
        $dataDefinition = $this->getDataDefinitionInstance($dataDefinitionName, $dataService);
        // Fetch the column data for the given data definition (insert mode)
        $form = $dataDefinition->getData($dataDefinition->name, 'insert');
        // Retrieve settings for the data definition
        $settings = $dataDefinition->getSettings([]);
        // Validate user access based on settings and request
        $this->validateAccess($settings, $request);

        // Call the insertRecord method on the dataService instance to create the record
        $message = $dataService->insertRecord($request, $form, $settings);

        // Check if validation has failed
        if ($message instanceof Validator) {
            // Return the insert view with the required data and validation errors
            return back()->withInput($request->all())->withErrors($message);
        }

        // Redirect the user to the data list view with the result message
        return redirect(route($settings['guard'].'.data.list', ['name' => $dataDefinitionName]))->with('toast', $message);
    }

    /**
     * Get the data definition instance by name.
     *
     * @param  string  $dataDefinitionName  The name of the data definition to retrieve.
     * @param  DataService  $dataService  The data service to fetch the data definition.
     * @return object The instantiated data definition object.
     *
     * @throws \Exception If the data definition is not found.
     */
    private function getDataDefinitionInstance(string $dataDefinitionName, DataService $dataService)
    {
        // Find the data definition by name
        $dataDefinition = $dataService->findDataDefinitionByName($dataDefinitionName);

        // If the data definition is not found, throw an exception
        if ($dataDefinition === null) {
            throw new \Exception('Data definition "'.$dataDefinitionName.'" not found');
        }

        // Instantiate and return the data definition object
        return new $dataDefinition;
    }

    /**
     * Validate user access based on settings and request.
     *
     * @param  array  $settings  The settings for the data definition.
     * @param  Request  $request  The incoming HTTP request.
     * @return void
     */
    private function validateAccess(array $settings, Request $request)
    {
        // Check if insert is allowed based on the settings
        if (! $settings['insert']) {
            // If limit was reached, redirect with a friendly message instead of 404
            if (! empty($settings['limitReached'])) {
                $message = $settings['limitReachedMessage'] ?? trans('common.limit_reached');
                $guard = $settings['guard'];
                $name = request()->route('name');
                
                // Use HttpResponseException to cleanly redirect with toast message
                throw new \Illuminate\Http\Exceptions\HttpResponseException(
                    redirect(route($guard.'.data.list', ['name' => $name]))
                        ->with('toast', [
                            'type' => 'warning',
                            'text' => $message,
                        ])
                );
            }
            
            Log::notice('app\Http\Controllers\Data\InsertController.php - Insert not allowed ('.auth($settings['guard'])->user()->email.')');
            abort(404);
        }

        // Obtain the user type from the route name (member, staff, partner, or admin)
        $guard = explode('.', $request->route()->getName())[0];

        // Check if the user type is allowed based on the settings
        if ($settings['guard'] !== $guard) {
            Log::notice('app\Http\Controllers\Data\InsertController.php - View not allowed for '.$guard.', '.$settings['guard'].' required ('.auth($settings['guard'])->user()->email.')');
            abort(404);
        }
    }
}
