<?php

namespace App\Providers;

use App\Models\ContentField;
use App\Models\QuoteRequest;
use App\Services\CompanyPage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('cms.*', function ($view) {
            $view->with('cmsLogo', ContentField::where('section', 'branding')->where('label', 'Logo utama / header')->first()?->values['id'] ?? asset('images/auliachem-logo.webp'));
            $view->with('cmsFavicon', ContentField::where('key', 'brand_favicon')->first()?->values['id'] ?? asset('images/auliachem-logo.webp'));
            $view->with('cmsSections', CompanyPage::SECTIONS);
            $view->with('newQuoteCount', QuoteRequest::where('status', 'new')->count());
        });
    }
}
