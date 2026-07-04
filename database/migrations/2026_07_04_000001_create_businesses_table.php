<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // Per-business HikBridge credentials. The API key is scoped to a
            // single business server-side, which is what enforces tenant
            // isolation — a user can only ever reach their own devices.
            $table->text('hik_api_key');            // encrypted at rest (hbk_...)
            $table->string('hik_base_url')->nullable();
            $table->text('hik_webhook_secret')->nullable(); // encrypted at rest (whsec_...)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
