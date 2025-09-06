<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;
use App\Services\CacheService;

class AppSettingsComposer
{
    /**
     * Create a new profile composer.
     */
    public function __construct(

    ) {}

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        // Always set default values, regardless of installation status
        $appSetting = null;
        $footerText = null;
        $fevicon = null;
        $colored_logo = null;
        $appDirection = 'ltr';
        $themeMode = 'light-theme';
        $themeBgColor = 'bg-white';

        if(env('INSTALLATION_STATUS')){
            $appSetting = CacheService::get('appSetting');

            /**
             * appSetting show footer text else null
            */
            $footerText = $appSetting?->footer_text ?? null;
            $fevicon = $appSetting?->fevicon ?? null;
            $colored_logo = $appSetting?->colored_logo ?? null;

            /**
             * Cookie Setting
             * */
            $cookie = Cookie::get('language_data'); // Get json data
            $cookieArrayData = json_decode($cookie, true);
            $appDirection = isset($cookieArrayData['direction'])? $cookieArrayData['direction'] : 'ltr';

            /**
             * Theme Mode Settings
             * */
            $themeModeCookie = Cookie::get('theme_mode');
            $themeMode = $themeModeCookie ?? 'light-theme';

            /**
             * Theme Manual settings for some pages
             * Like POS, Login, Register Pages
             * */
            $themeBgColor = ($themeModeCookie == 'dark-theme') ? 'bg-dark' : 'bg-white';
        }

        // Always bind the variables to the view
        $view->with('footerText', $footerText);
        $view->with('fevicon', $fevicon);
        $view->with('colored_logo', $colored_logo);
        $view->with('appDirection', $appDirection);
        $view->with('themeMode', $themeMode);
        $view->with('themeBgColor', $themeBgColor);
    }
}
