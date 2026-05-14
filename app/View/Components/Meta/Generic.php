<?php

namespace App\View\Components\Meta;

use App\Services\I18nService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\View\Component;

/**
 * Class Generic
 *
 * Represents meta information such as locale, language, currency, and timezone settings in the Blade head.
 */
class Generic extends Component
{
    /**
     * @var I18nService
     */
    protected $i18nService;

    // Public properties for the component
    public $locale;

    public $localeSlug;

    public $language;

    public $currency;

    public $timezone;

    /**
     * Create a new component instance.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  I18nService  $i18nService  The i18n service instance.
     */
    public function __construct(Request $request, I18nService $i18nService)
    {
        $this->i18nService = $i18nService;

        // Retrieve i18n data either from the container or the service
        $i18n = $this->getI18nInstance($request);

        // Initialize component properties with i18n data
        $this->initializeProperties($i18n);
    }

    /**
     * Get the view or contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.meta.generic');
    }

    /**
     * Retrieve the i18n data either from the application container or generate it using the i18n service.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return object The i18n data as an object.
     */
    protected function getI18nInstance(Request $request)
    {
        if (App::bound('i18n')) {
            // Retrieve the i18n data from the application container
            return App::make('i18n');
        }

        // Handle the case where the i18n class does not exist
        $i18nArray = $this->getI18nData($this->getAuthenticatedUser(), $request);

        return $this->arrayToObject($i18nArray);
    }

    /**
     * Initialize the component properties with i18n data.
     *
     * @param  object  $i18n  The i18n data.
     */
    protected function initializeProperties($i18n)
    {
        $this->locale = $i18n->language->current->locale;
        $this->localeSlug = $i18n->language->current->localeSlug;
        $this->language = explode('_', $i18n->language->current->locale)[0];
        $this->currency = $i18n->currency->id;
        $this->timezone = $i18n->time_zone;
    }

    /**
     * Identify the authenticated user from the possible guards.
     *
     * @return null|\Illuminate\Contracts\Auth\Authenticatable The authenticated user or null if none.
     */
    protected function getAuthenticatedUser()
    {
        $guards = ['admin', 'partner', 'affiliate', 'staff', 'member'];

        // Get the second URL segment
        $segment = request()->segment(2);

        // If the segment matches a guard name and the user is authenticated under that guard, return the user
        if (! empty($segment) && in_array($segment, $guards) && auth($segment)->check()) {
            return auth($segment)->user();
        }

        // If no match found, no segment exists, or user is not authenticated under the matched guard,
        // check under 'member' guard
        if (auth('member')->check()) {
            return auth('member')->user();
        }

        return null;
    }

    /**
     * Prepare i18n data for the authenticated user or default settings.
     *
     * @param  null|\Illuminate\Contracts\Auth\Authenticatable  $user  The authenticated user.
     * @param  Request  $request  The incoming HTTP request.
     * @return array The i18n data.
     */
    protected function getI18nData($user, Request $request): array
    {
        // Use user's preferences if available, else use default settings
        $currency_code = $user ? $user->currency : config('default.currency');
        $time_zone = $user ? $user->time_zone : config('default.time_zone');

        return [
            'language' => $this->i18nService->getAllTranslations(null, $request),
            'currency' => $this->i18nService->getCurrencyDetails($currency_code),
            'time_zone' => $time_zone,
        ];
    }

    /**
     * Convert an array to an object.
     *
     * @param  array  $array  The array to convert.
     * @return object The converted object.
     */
    protected function arrayToObject(array $array)
    {
        return json_decode(json_encode($array));
    }
}
