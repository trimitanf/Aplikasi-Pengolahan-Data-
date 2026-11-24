<?php

namespace App\Providers;

use App\Models\Dataset;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Aliases untuk backward compatibility akses App\Services\*
        if (!class_exists('App\Services\DashboardService', false)) {
            class_alias(\App\Services\Iklan\DashboardService::class, 'App\Services\DashboardService');
        }
        if (!class_exists('App\Services\DatasetService', false)) {
            class_alias(\App\Services\Iklan\DatasetService::class, 'App\Services\DatasetService');
        }
        if (!class_exists('App\Services\ExportRekapService', false)) {
            class_alias(\App\Services\Iklan\ExportRekapService::class, 'App\Services\ExportRekapService');
        }
        if (!class_exists('App\Services\ExportStandardisasiService', false)) {
            class_alias(\App\Services\Iklan\ExportStandardisasiService::class, 'App\Services\ExportStandardisasiService');
        }
        if (!class_exists('App\Services\RekapService', false)) {
            class_alias(\App\Services\Iklan\RekapService::class, 'App\Services\RekapService');
        }
        if (!class_exists('App\Services\SiptDatasetReader', false)) {
            class_alias(\App\Services\Iklan\SiptDatasetReader::class, 'App\Services\SiptDatasetReader');
        }
        if (!class_exists('App\Services\StandardizationService', false)) {
            class_alias(\App\Services\Iklan\StandardizationService::class, 'App\Services\StandardizationService');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Daftarkan folder views/Iklan dan views/Penandaan ke View Finder
        View::addLocation(resource_path('views/Iklan'));
        View::addLocation(resource_path('views/Penandaan'));

        View::composer('components.layouts.app', function ($view) {
            $trashCount = auth()->check()
                ? (auth()->user()->isAdmin()
                    ? Dataset::onlyTrashed()->count()
                    : Dataset::onlyTrashed()->where('uploaded_by', auth()->id())->count())
                : 0;

            $view->with('trashCount', $trashCount);
        });
    }
}
