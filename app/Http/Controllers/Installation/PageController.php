<?php

namespace App\Http\Controllers\Installation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Install\PostInstallRequest;
use App\Services\I18nService;
use App\Services\InstallationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    /**
     * Display the installation page with server requirements and timezones.
     *
     * @param  string  $locale  The locale for the installation page.
     * @param  Request  $request  The incoming HTTP request.
     * @param  InstallationService  $installationService  The service for handling installation-related tasks.
     * @param  I18nService  $i18nService  The service for handling internationalization.
     * @return \Symfony\Component\HttpFoundation\Response The installation page view.
     */
    public function index($locale, Request $request, InstallationService $installationService, I18nService $i18nService)
    {
        $requirements = $installationService->getServerRequirements();
        $timezones = $i18nService->getAllTimezones();
        $translations = $i18nService->getAllTranslations();

        return view('installation.index', compact('requirements', 'timezones'));
    }

    /**
     * Handle post-installation tasks.
     *
     * @param  string  $locale  The locale for the installation page.
     * @param  PostInstallRequest  $request  The validated incoming HTTP request.
     * @param  InstallationService  $installationService  The service for handling installation-related tasks.
     * @return \Symfony\Component\HttpFoundation\Response A JSON response indicating the status of the installation.
     */
    public function postInstall($locale, PostInstallRequest $request, InstallationService $installationService)
    {
        $validated = $request->safe()->all();
        $installationService->installScript($validated);

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Download the log file.
     *
     * @param  string  $locale  The locale for the installation page.
     * @param  Request  $request  The incoming HTTP request.
     * @return \Symfony\Component\HttpFoundation\Response The log file as a download.
     */
    public function downloadLog($locale, Request $request)
    {
        $log = storage_path('logs/laravel.log');
        $headers = ['Content-Type: text/plain'];
        $saveAs = Str::slug(config('default.app_name'), '-').'-'.date('Y-m-d').'.txt';

        return response()->download($log, $saveAs, $headers);
    }
}
