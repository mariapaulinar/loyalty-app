<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Services\Data\DataService;
use Illuminate\Http\Request;

class DeleteController extends Controller
{
    /**
     * Handle the deletion of one or more items.
     *
     * @param  string  $locale  The current locale.
     * @param  string  $dataDefinitionName  The name of the data definition to use for deleting records.
     * @param  Request  $request  The incoming HTTP request containing the IDs of the records to be deleted.
     * @param  DataService  $dataService  The data service instance to handle the deletion of records.
     * @param  string|null  $id  The optional ID of a single record to delete. If provided, it takes precedence over the IDs in the request.
     * @return \Illuminate\Http\RedirectResponse A redirect response to the data list view with the result message.
     *
     * @throws \Exception If the data definition is not found.
     */
    public function postDelete(string $locale, string $dataDefinitionName, Request $request, DataService $dataService, $id = null)
    {
        // Get the IDs of the records to be deleted from the request
        $ids = $id ?? $request->get('id');

        // Call the deleteRecords method on the dataService instance to delete the records
        $message = $dataService->deleteRecords($ids, $dataDefinitionName);

        // Obtain the user type from the route name (member, staff, partner, or admin)
        $guard = explode('.', $request->route()->getName())[0];

        // Redirect the user to the data list view with the result message
        return redirect(route($guard.'.data.list', ['name' => $dataDefinitionName]))->with('toast', $message);
    }
}
