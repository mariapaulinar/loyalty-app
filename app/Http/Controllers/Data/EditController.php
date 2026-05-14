<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Services\Data\DataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

class EditController extends Controller
{
    /**
     * Show the edit form for a specific item.
     *
     * @param  string  $locale  The application locale (e.g., 'en')
     * @param  string  $dataDefinitionName  The name of the data definition to be edited
     * @param  string  $id  The ID of the item to edit
     * @param  Request  $request  The incoming request object
     * @param  DataService  $dataService  The DataService instance
     * @return \Illuminate\View\View The edit view with the data definition, form, and settings
     */
    public function showEditItem(string $locale, string $dataDefinitionName, string $id, Request $request, DataService $dataService)
    {
        // Get the DataDefinition instance based on the dataDefinitionName
        $dataDefinition = $this->getDataDefinitionInstance($dataDefinitionName, $dataService);

        // Get the form data for the specified item
        $form = $this->getFormData($dataDefinition, $id, 'edit');

        // Get the settings for the data definition
        $settings = $dataDefinition->getSettings([]);

        // Validate user access to the edit form
        $this->validateAccess($dataDefinition, $id, $settings, $request);

        // Return the edit view with the data definition, form, and settings
        return view('data.edit', compact('dataDefinition', 'form', 'settings'));
    }

    /**
     * Process the submitted edit form for a specific item.
     *
     * @param  string  $locale  The application locale (e.g., 'en')
     * @param  string  $dataDefinitionName  The name of the data definition to be edited
     * @param  string  $id  The ID of the item to edit
     * @param  Request  $request  The incoming request object
     * @param  DataService  $dataService  The DataService instance
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse The edit view with updated data, or a redirect response in case of validation errors
     */
    public function postEditItem(string $locale, string $dataDefinitionName, string $id, Request $request, DataService $dataService)
    {
        // Get the DataDefinition instance based on the dataDefinitionName
        $dataDefinition = $this->getDataDefinitionInstance($dataDefinitionName, $dataService);

        // Get the form data for the specified item
        $form = $this->getFormData($dataDefinition, $id, 'edit');

        // Retrieve settings for the data definition
        $settings = $dataDefinition->getSettings([]);

        // Validate user access to the edit form
        $this->validateAccess($dataDefinition, $id, $settings, $request);

        // Update the record with the provided form data
        $message = $dataService->updateRecord($id, $request, $form, $settings);

        // Check if the message is an instance of Validator, indicating validation errors
        if ($message instanceof Validator) {
            // Redirect back to the edit form with input data and validation errors
            return back()->withInput($request->all())->withErrors($message);
        }

        // Flash the success message to the session
        session()->flash('toast', $message);

        // Flash selected tab
        session()->flash('current_tab_index', $request->current_tab_index);

        // Return the edit view with the updated data definition, form, and settings
        return $this->showEditItem($locale, $dataDefinitionName, $id, $request, $dataService);
    }

    /**
     * Get the instance of the DataDefinition.
     *
     * @param  string  $dataDefinitionName  The name of the data definition to be retrieved
     * @param  DataService  $dataService  The DataService instance
     * @return object The instance of the DataDefinition class
     *
     * @throws \Exception If the data definition is not found
     */
    private function getDataDefinitionInstance(string $dataDefinitionName, DataService $dataService)
    {
        // Find the data definition by its name using the DataService
        $dataDefinition = $dataService->findDataDefinitionByName($dataDefinitionName);

        // Check if the data definition is not found
        if ($dataDefinition === null) {
            // Throw an exception if the data definition is not found
            throw new \Exception('Data definition "'.$dataDefinitionName.'" not found');
        }

        // Create a new instance of the found data definition and return it
        return new $dataDefinition;
    }

    /**
     * Get the form data for the given DataDefinition and ID.
     *
     * @param  object  $dataDefinition  The DataDefinition instance
     * @param  string  $id  The ID of the item to edit
     * @param  string  $mode  The mode in which the form is being used (e.g., 'edit')
     * @return array The form data including the data for the specified item
     */
    private function getFormData($dataDefinition, string $id, string $mode)
    {
        // Set the options with the ID of the item to edit
        $options = ['id' => $id];

        // Call the getData method on the DataDefinition instance with the given mode and options
        $form = $dataDefinition->getData($dataDefinition->name, $mode, $options);

        // Check if the form data is null, which means the item was not found
        if ($form['data'] === null) {
            // Abort with a 404 error if the item is not found
            abort(404);
        }

        // Return the form data
        return $form;
    }

    /**
     * Validate if the user has access to perform the action.
     *
     * @param  object  $dataDefinition  The DataDefinition instance
     * @param  string  $id  The ID of the item to edit
     * @param  array  $settings  The settings array containing access configurations
     * @param  Request  $request  The incoming request object
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException If access is denied
     */
    private function validateAccess($dataDefinition, string $id, array $settings, Request $request)
    {
        // Check if edit is allowed in settings
        if (! $settings['edit']) {
            // Abort with a 404 error if edit is not allowed
            Log::notice('app\Http\Controllers\Data\EditController.php - Edit not allowed ('.auth($settings['guard'])->user()->email.')');
            abort(404);
        }

        // Get the guard name from the route
        $guard = explode('.', $request->route()->getName())[0];

        // Check if the current guard matches the required guard in settings
        if ($settings['guard'] !== $guard) {
            // Abort with a 404 error if the guard is not allowed
            Log::notice('app\Http\Controllers\Data\EditController.php - View not allowed for '.$guard.', '.$settings['guard'].' required ('.auth($settings['guard'])->user()->email.')');
            abort(404);
        }

        // Check if this record is allowed if list view is allowed
        if ($settings['redirectListToEdit'] && $settings['redirectListToEditColumn'] !== null) {
            $userId = auth($settings['guard'])->user()->id;
            $item = $dataDefinition->model->select($settings['redirectListToEditColumn'])->where($settings['redirectListToEditColumn'], $id)->first();

            if (! $item || $item->{$settings['redirectListToEditColumn']} !== $userId) {
                abort(404);
            }
        }
    }
}
