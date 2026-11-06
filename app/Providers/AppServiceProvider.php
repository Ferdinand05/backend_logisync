<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
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
}
