<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hik_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->index();
            $table->string('person_code')->nullable()->index();
            $table->unsignedInteger('device_id')->nullable()->index();
            $table->string('device_name')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->json('payload');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hik_events');
    }
};
