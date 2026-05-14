<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Services\Data\DataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ViewController extends Controller
{
    /**
     * Show the view of a record for the given data definition.
     *
     * @param  string  $locale  The current locale.
     * @param  string  $dataDefinitionName  The name of the data definition to retrieve.
     * @param  string  $id  The ID of the record to view.
     * @param  Request  $request  The incoming HTTP request.
     * @param  DataService  $dataService  The data service to fetch the data definition.
     *
     * @throws \Exception If the data definition is not found.
     */
    public function showViewItem(string $locale, string $dataDefinitionName, string $id, Request $request, DataService $dataService): View
    {
        // Find the data definition by name and instantiate it if it exists
        $dataDefinition = $dataService->findDataDefinitionByName($dataDefinitionName);
        if ($dataDefinition === null) {
            throw new \Exception('Data definition "'.$dataDefinitionName.'" not found');
        }

        $dataDefinition = new $dataDefinition;

        // Fetch the data for the given data definition and options
        $form = $dataDefinition->getData($dataDefinition->name, 'view', ['id' => $id]);
        if ($form['data'] === null) {
            abort(404);
        }

        // Get settings for the data definition and abort if viewing is not allowed
        $settings = $dataDefinition->getSettings([]);
        if (! $settings['view']) {
            Log::notice('app\Http\Controllers\Data\ViewController.php - View not allowed ('.auth($settings['guard'])->user()->email.')');
            abort(404);
        }

        // Obtain the user type from the route name (member, staff, partner, or admin)
        // and verify if the Data Definition is permitted for that user type
        $guard = explode('.', $request->route()->getName())[0];
        if ($settings['guard'] !== $guard) {
            Log::notice('app\Http\Controllers\Data\ViewController.php - View not allowed for '.$guard.', '.$settings['guard'].' required ('.auth($settings['guard'])->user()->email.')');
            abort(404);
        }

        // Return the view with the required data
        return view('data.view', compact('dataDefinition', 'form', 'settings'));
    }
}
