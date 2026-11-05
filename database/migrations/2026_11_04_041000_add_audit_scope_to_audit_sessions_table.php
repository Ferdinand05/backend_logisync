<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_sessions', function (Blueprint $table): void {
            $table->string('audit_method')->default('location')->after('audit_number');
            $table->foreignId('category_id')->nullable()->after('location_id')->constrained()->restrictOnDelete();
            $table->json('item_ids')->nullable()->after('category_id');
            $table->foreignId('location_id')->nullable()->change();
        });

        Schema::table('audit_items', function (Blueprint $table): void {
            $table->foreignId('location_id')->nullable()->after('item_id')->constrained()->nullOnDelete();
        });

        DB::statement('UPDATE audit_items SET location_id = audit_sessions.location_id FROM audit_sessions WHERE audit_sessions.id = audit_items.audit_session_id AND audit_items.location_id IS NULL');

        DB::table('audit_items')->whereNull('location_id')->delete();

        Schema::table('audit_items', function (Blueprint $table): void {
            $table->dropForeign(['location_id']);
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
            $table->unique(['audit_session_id', 'item_id', 'location_id'], 'audit_items_session_item_location_unique');
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_items', function (Blueprint $table): void {
            $table->dropUnique('audit_items_session_item_location_unique');
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::table('audit_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['audit_method', 'item_ids']);
            $table->foreignId('location_id')->nullable(false)->change();
        });
    }
};
