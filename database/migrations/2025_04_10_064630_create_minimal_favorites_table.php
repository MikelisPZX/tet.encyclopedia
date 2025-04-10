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
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 3); // Store cca3 country code (3-letter code)
            $table->string('country_name')->nullable(); // Store country name for display purposes
            $table->string('flag_emoji')->nullable(); // Store flag emoji for display
            $table->timestamps();
            
            // Add index for faster lookups
            $table->index('country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
