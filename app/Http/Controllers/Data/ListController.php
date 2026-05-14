<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Services\Data\DataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ListController extends Controller
{
    /**
     * Show the list of items for the given data definition.
     *
     * @param  string  $locale  The current locale.
     * @param  string  $dataDefinitionName  The name of the data definition to retrieve.
     * @param  Request  $request  The incoming HTTP request.
     * @param  DataService  $dataService  The data service to fetch the data definition.
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     *
     * @throws \Exception If the data definition is not found.
     */
    public function showList(string $locale, string $dataDefinitionName, Request $request, DataService $dataService)
    {
        // Find the data definition by name and instantiate it if it exists
        $dataDefinition = $dataService->findDataDefinitionByName($dataDefinitionName);
        if ($dataDefinition === null) {
            throw new \Exception('Data definition "'.$dataDefinitionName.'" not found');
        }
        $dataDefinition = new $dataDefinition;

        // Get unique ID for table
        $uniqueId = unique_code(12);

        // Retrieve settings
        $settings = $dataDefinition->getSettings([]);

        // Redirect to edit form, before checking if list view is allowed
        if ($settings['redirectListToEdit'] && $settings['redirectListToEditColumn'] !== null) {
            $userId = auth($settings['guard'])->user()->id;
            $primaryKey = $dataDefinition->model->getKeyName();
            $item = $dataDefinition->model->select($primaryKey)->where($settings['redirectListToEditColumn'], $userId)->first();
            if ($item) {
                // Redirect to edit form
                $id = $item->{$primaryKey};

                return redirect()->route($settings['guard'].'.data.edit', ['name' => $dataDefinition->name, 'id' => $id]);
            } else {
                abort(404);
            }
        }

        // Abort if the list view is not allowed based on the settings
        if (! $settings['list']) {
            abort(404);
        }

        // Retrieve the table data for the data definition
        $tableData = $dataDefinition->getData($dataDefinition->name, 'list');

        // Check if list is empty and there is an onEmptyListRedirectTo route
        if ($tableData['data']->total() === 0 && $settings['onEmptyListRedirectTo']) {
            return redirect($settings['onEmptyListRedirectTo']);
        }

        // Determine the user type from the route name (member, staff, partner, or admin)
        // and verify if the Data Definition is permitted for that user type
        $guard = explode('.', $request->route()->getName())[0];
        if ($settings['guard'] !== $guard) {
            Log::notice('app\Http\Controllers\Data\ListController.php - View not allowed for '.$guard.', '.$settings['guard'].' required ('.auth($settings['guard'])->user()->email.')');
            abort(404);
        }

        // Return the view with the required data
        return view('data.list', compact('dataDefinition', 'uniqueId', 'settings', 'tableData'));
    }

    /**
     * Provide lightweight search suggestions for the list search autocomplete.
     */
    public function suggest(string $locale, string $dataDefinitionName, Request $request, DataService $dataService): JsonResponse
    {
        $dataDefinition = $dataService->findDataDefinitionByName($dataDefinitionName);
        if ($dataDefinition === null) {
            return response()->json(['data' => []], 404);
        }

        $dataDefinition = new $dataDefinition;
        $settings = $dataDefinition->getSettings([]);

        $guard = explode('.', $request->route()->getName())[0];
        if ($settings['guard'] !== $guard) {
            Log::notice('app\Http\Controllers\Data\ListController.php - Suggest not allowed for '.$guard.', '.$settings['guard'].' required ('.auth($settings['guard'])->user()->email.')');

            return response()->json(['data' => []], 404);
        }

        if (! $settings['list'] || ! $settings['search']) {
            return response()->json(['data' => []]);
        }

        $q = (string) $request->query('q', '');

        $suggestions = $dataDefinition->getSearchSuggestions($q);

        $data = array_map(function (array $item) use ($settings, $dataDefinitionName): array {
            $id = $item['id'] ?? null;
            $label = $item['label'] ?? null;

            if (! is_string($id) || $id === '' || ! is_string($label)) {
                return [];
            }

            if ($settings['edit']) {
                $url = route($settings['guard'].'.data.edit', ['name' => $dataDefinitionName, 'id' => $id]);
            } elseif ($settings['view']) {
                $url = route($settings['guard'].'.data.view', ['name' => $dataDefinitionName, 'id' => $id]);
            } else {
                $url = route($settings['guard'].'.data.list', ['name' => $dataDefinitionName]).'?'.http_build_query([
                    'search' => $label,
                ]);
            }

            return [
                'id' => $id,
                'label' => $label,
                'url' => $url,
            ];
        }, $suggestions);

        $data = array_values(array_filter($data));

        return response()->json(['data' => $data]);
    }
}
