<?php

namespace App\Providers;

use App\Models\User;
use App\Providers\TelescopeServiceProvider as LocalTelescopeServiceProvider;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->isTelescopeEnabled() && class_exists(TelescopeServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(LocalTelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->forceHttpsWhenConfigured();
        $this->registerApiDocsGate();

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
     * Pastikan URL aset dan route memakai HTTPS saat aplikasi diakses lewat
     * reverse proxy TLS, agar tidak terjadi mixed content.
     */
    private function forceHttpsWhenConfigured(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Izinkan akses dokumentasi API untuk admin, environment lokal, atau
     * saat API_DOCS_ENABLED=true (misalnya ketika frontend masih development).
     */
    private function registerApiDocsGate(): void
    {
        Gate::define('viewApiDocs', function (?User $user): bool {
            if ($user?->role === 'admin') {
                return true;
            }

            if ($this->app->environment('local')) {
                return true;
            }

            return (bool) config('app.api_docs_enabled', false);
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

        return (bool) config('telescope.enabled', false);
    }
}
