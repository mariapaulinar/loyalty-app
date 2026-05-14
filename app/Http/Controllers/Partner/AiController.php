<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Services\AiService;
use App\Services\Data\DataService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AiController extends Controller
{
    protected $aiService;

    protected $dataService;

    /**
     * AiController constructor.
     */
    public function __construct(AiService $aiService, DataService $dataService)
    {
        $this->aiService = $aiService;
        $this->dataService = $dataService;
    }

    /**
     * Handle AI response requests.
     *
     * @param  string  $locale  The locale for the request.
     * @param  Request  $request  The incoming HTTP request.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getResponse(string $locale, Request $request)
    {
        try {
            $chatInput = $request->input('chatInput');
            $action = $request->input('action');
            $meta = $request->input('meta', []);

            // Validate meta inputs
            if (empty($meta['field']) || empty($meta['guard']) || empty($meta['name']) || empty($meta['view'])) {
                return response()->json(['error' => 'Meta inputs are missing or empty'], 400);
            }

            // Get the DataDefinition instance based on the meta 'name'
            $dataDefinition = $this->getDataDefinitionInstance($meta['name']);

            // Get field details from the DataDefinition instance
            $fieldData = $this->getFieldData($dataDefinition->fields, $meta['field']);

            if (empty($fieldData['ai'])) {
                return response()->json(['error' => 'No AI settings found for the specified field'], 400);
            }

            // Validate the incoming request
            $request->validate([
                'chatInput' => 'nullable|string',
                'action' => 'required|string', // 'required|string|in:complete,shorten,rephrase,translate',
                'meta' => 'nullable|array',
            ], [],
                [
                    'chatInput' => $fieldData['text'],
                ]);

            // Override meta with AI-specific settings from the field data
            $specifiedLocale = (isset($meta['locale'])) ? $meta['locale'] : null;
            $translateToLocale = (isset($meta['translate_to_locale'])) ? $meta['translate_to_locale'] : null;
            $meta = $fieldData['ai'];
            $meta['guard'] = $dataDefinition->settings['guard'];
            $meta['locale'] = $specifiedLocale;
            $meta['translate_to_locale'] = $translateToLocale;

            // \Log::info(json_decode(json_encode($dataDefinition), true));

            // Call AI service with the specified action
            $response = $this->aiService->handleRequest($locale, $chatInput ?? '', $action, $meta);

            return response()->json(['response' => $response]);

        } catch (ValidationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Exception $e) {
            // Log the exception message
            \Log::error('AI Service Error: '.$e->getMessage());

            return response()->json(['error' => 'An error occurred while processing your request. Please try again later.'], 500);
        }
    }

    /**
     * Get the data definition instance by name.
     *
     * @param  string  $dataDefinitionName  The name of the data definition to retrieve.
     * @return object The instantiated data definition object.
     *
     * @throws \Exception If the data definition is not found.
     */
    private function getDataDefinitionInstance(string $dataDefinitionName)
    {
        // Find the data definition by name
        $dataDefinition = $this->dataService->findDataDefinitionByName($dataDefinitionName);

        // If the data definition is not found, return a JSON error response
        if ($dataDefinition === null) {
            throw new Exception('Data definition "'.$dataDefinitionName.'" not found');
        }

        // Instantiate and return the data definition object
        return new $dataDefinition;
    }

    /**
     * Retrieve the specified field data from the given fields array.
     *
     * @param  array  $fieldsArray  The array of fields to search within.
     * @param  string  $field  The field key to search for.
     * @return array|null The field data if found, or null if not found.
     */
    private function getFieldData(array $fieldsArray, string $field): ?array
    {
        // Iterate over each tab in the fields array
        foreach ($fieldsArray as $tab) {
            // Check if the desired field exists directly in the fields array
            if (array_key_exists($field, $fieldsArray)) {
                return $fieldsArray[$field];
            }
            // Check if 'fields' key exists and is an array within the current tab
            if (isset($tab['fields']) && is_array($tab['fields'])) {
                // Check if the desired field exists within the 'fields' array of the current tab
                if (array_key_exists($field, $tab['fields'])) {
                    return $tab['fields'][$field];
                }
            }
        }

        // Return null if the desired field is not found in any tab
        return null;
    }
}
