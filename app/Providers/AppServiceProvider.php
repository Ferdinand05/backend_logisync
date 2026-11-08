<?php

namespace App\Providers;

use App\Providers\TelescopeServiceProvider as LocalTelescopeServiceProvider;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->isTelescopeEnabled()) {
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(LocalTelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $document) {
                // Mengubah judul utama
                $document->info->title = 'Logisync API Documentation';

                // Mengubah deskripsi Overview (Mendukung Markdown)
                $document->info->description = '
                    Selamat datang di dokumentasi resmi **API Logisync Backend**.
                    
                    ### 🔑 Panduan Autentikasi
                    Gunakan token Bearer untuk mengakses endpoint terproteksi.
                ';
            });
    }

    /**
     * Telescope hanya dimuat saat environment lokal atau bila diaktifkan eksplisit
     * melalui TELESCOPE_ENABLED, sehingga tidak ikut terpasang di production.
     */
    private function isTelescopeEnabled(): bool
    {
        if ($this->app->environment('local')) {
            return true;
        }

        return filter_var($this->app['config']->get('telescope.enabled'), FILTER_VALIDATE_BOOL);
    }
}
