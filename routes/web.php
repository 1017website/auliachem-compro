<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CmsController;
use App\Http\Controllers\DeveloperToolsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\QuoteManagementController;
use App\Http\Controllers\QuoteRequestController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ConversionController;
use App\Http\Controllers\UserManagementController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureDeveloper;
use App\Http\Middleware\TrackPageVisit;
use App\Services\CompanyPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request, CompanyPage $page) {
    $locale = $request->query('lang', 'id');
    abort_unless(in_array($locale, ['id', 'en', 'zh'], true), 404);

    return response($page->render($locale));
})->middleware(TrackPageVisit::class)->name('home');
Route::post('/quote-requests', [QuoteRequestController::class, 'store'])->middleware('throttle:5,1')->name('quotes.store');
Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->middleware('throttle:30,1')->name('documents.download');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::post('/track', [ConversionController::class, 'store'])->middleware('throttle:30,1')->name('analytics.track');

Route::middleware('guest')->group(function () {
    Route::view('/admin/login', 'cms.login')->name('login');
    Route::post('/admin/login', [CmsController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/admin/two-factor', [CmsController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/admin/two-factor', [CmsController::class, 'verifyTwoFactor'])->middleware('throttle:5,1')->name('two-factor.verify');
});
Route::middleware(['auth', EnsureAdmin::class])->prefix('admin')->group(function () {
    Route::get('/', [CmsController::class, 'edit'])->name('cms.edit');
    Route::get('/analytics', AnalyticsController::class)->name('cms.analytics');
    Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('cms.analytics.export');
    Route::get('/profile', [ProfileController::class, 'show'])->name('cms.profile');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('cms.profile.password');
    Route::post('/profile/two-factor', [ProfileController::class, 'begin2fa'])->name('cms.profile.2fa.begin');
    Route::put('/profile/two-factor', [ProfileController::class, 'confirm2fa'])->name('cms.profile.2fa.confirm');
    Route::delete('/profile/two-factor', [ProfileController::class, 'disable2fa'])->name('cms.profile.2fa.disable');
    Route::get('/activity', [ProfileController::class, 'activity'])->name('cms.activity');
    Route::get('/preview', function (Request $request, CompanyPage $page) {
        $locale = $request->query('lang', 'id');
        abort_unless(in_array($locale, ['id', 'en', 'zh'], true), 404);

        return response($page->render($locale, true))->header('Cache-Control', 'private, no-store')->header('X-Frame-Options', 'SAMEORIGIN');
    })->name('cms.preview');
    Route::put('/content', [CmsController::class, 'update'])->name('cms.update');
    Route::get('/quote-requests', [QuoteManagementController::class, 'index'])->name('cms.quotes.index');
    Route::patch('/quote-requests/{quote}', [QuoteManagementController::class, 'update'])->name('cms.quotes.update');
    Route::delete('/quote-requests/{quote}', [QuoteManagementController::class, 'destroy'])->name('cms.quotes.destroy');
    Route::get('/documents', [DocumentController::class, 'index'])->name('cms.documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('cms.documents.store');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('cms.documents.edit');
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('cms.documents.update');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('cms.documents.destroy');
    Route::resource('products', ProductController::class)->except('show')->names('cms.products');
    Route::post('/logout', [CmsController::class, 'logout'])->name('logout');
    Route::middleware(EnsureDeveloper::class)->group(function () {
        Route::get('/developer', [DeveloperToolsController::class, 'index'])->name('cms.developer');
        Route::post('/developer/run', [DeveloperToolsController::class, 'run'])->middleware('throttle:10,1')->name('cms.developer.run');
        Route::get('/users', [UserManagementController::class, 'index'])->name('cms.users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('cms.users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('cms.users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('cms.users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('cms.users.destroy');
    });
});
