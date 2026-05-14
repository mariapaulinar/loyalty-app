<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Facades\Auth;
use OpenAI;

class AiService
{
    /**
     * The OpenAI client instance (lazy-loaded).
     *
     * @var \OpenAI\Client|null
     */
    protected $client;

    /**
     * The OpenAI model to be used.
     *
     * @var string
     */
    protected $model;

    /**
     * Create a new AiService instance.
     *
     * The OpenAI client is NOT instantiated here to avoid crashes
     * when the API key is not configured. The client is lazy-loaded
     * on first use via getClient().
     */
    public function __construct()
    {
        $this->model = config('openai.model');
    }

    /**
     * Get the OpenAI client, instantiating it on first use.
     *
     * @return \OpenAI\Client
     *
     * @throws \RuntimeException If the OpenAI API key is not configured.
     */
    protected function getClient(): \OpenAI\Client
    {
        if ($this->client === null) {
            $apiKey = config('openai.api_key');

            if (empty($apiKey)) {
                throw new \RuntimeException(
                    'OpenAI API key is not configured. Set OPENAI_API_KEY in your .env file.'
                );
            }

            $this->client = OpenAI::factory()
                ->withApiKey($apiKey)
                ->withOrganization(config('openai.organization'))
                ->withBaseUri(config('openai.base_uri'))
                ->make();
        }

        return $this->client;
    }

    /**
     * Handle AI requests for various actions.
     *
     * @param  string  $locale  The locale for the request.
     * @param  string  $chatInput  The input text for the AI.
     * @param  string  $action  The action to be performed by the AI.
     * @param  array  $meta  Additional meta data for the request.
     * @return string The AI response.
     */
    public function handleRequest(string $locale, string $chatInput, string $action, array $meta = []): string
    {
        $prompt = $this->generatePrompt($action, $chatInput, $meta);

        $messages = [
            ['role' => 'system', 'content' => config('prompts.system', 'You are a helpful assistant.')],
            ['role' => 'user', 'content' => $prompt],
        ];

        // Unique identifier for user executing prompt
        $user = Auth::guard($meta['guard'])->user()->email;

        // Define chat settings for OpenAI API
        $chatSettings = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => (isset($meta['max_tokens'])) ? $meta['max_tokens'] : config('prompts.max_tokens', 20),
            'temperature' => (isset($meta['temperature'])) ? $meta['temperature'] : config('prompts.temperature', 0.6),
            'top_p' => 1.0,
            'n' => 1,
            'stream' => false,
            'stop' => null,
            'logprobs' => null,
            'presence_penalty' => 0.0,
            'frequency_penalty' => 0.0,
            'logit_bias' => null,
            'user' => $user,
        ];

        // Deepseek
        $isDeepseek = (config('openai.model') == 'deepseek-reasoner' || config('openai.model') == 'deepseek-chat') ? true : false;
        if ($isDeepseek) {
            $chatSettings['response_format'] = [
                'type' => 'text',
            ];
        }

        $response = $this->getClient()->chat()->create($chatSettings);

        // \Log::info($prompt);
        // \Log::info(json_decode(json_encode($response), true));

        $responseData = $this->sanitizeInput($response->choices[0]->message->content);

        return $responseData;
    }

    /**
     * Generate the prompt for the AI request based on the action.
     *
     * @param  string  $action  The action to be performed.
     * @param  string  $chatInput  The input text for the AI.
     * @param  array  $meta  Additional meta data for the request.
     * @return string The generated prompt.
     */
    private function generatePrompt(string $action, string $chatInput, array $meta = []): string
    {
        $template = ($action == 'autofill') ? $meta['autoFillPrompt'] : config('prompts.prompts.'.$action.'.template');

        if ($template) {
            // Retrieve the i18n data from the application container
            $i18n = app()->make('i18n');
            // Initialize component properties with i18n data
            $locale = $i18n->language->current->locale;
            $localeSlug = $i18n->language->current->localeSlug;
            $language = explode('_', $i18n->language->current->locale)[0];
            $currency = $i18n->currency->id;
            $timezone = $i18n->time_zone;

            // Parse the string with variables
            $prompt = trans($template, [
                'locale' => isset($meta['locale']) ? $meta['locale'] : $locale,
                'localeSlug' => $localeSlug,
                'language' => $language,
                'currency' => $currency,
                'timezone' => $timezone,
                'user_input' => $chatInput,
                'translate_to_locale' => isset($meta['translate_to_locale']) ? $meta['translate_to_locale'] : $locale,
            ]);
        } else {
            $prompt = $chatInput;
        }

        return $prompt;
    }

    /**
     * Sanitize input data.
     *
     * @param  string  $inputData  The data to be sanitized.
     * @return string The sanitized data.
     */
    private function sanitizeInput(string $inputData): string
    {
        // Instantiate a new HTML Purifier
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));
        $config->set('HTML.Allowed', ''); // Do not allow any HTML tags
        $purifier = new HTMLPurifier($config);

        return $purifier->purify($inputData);
    }
}
