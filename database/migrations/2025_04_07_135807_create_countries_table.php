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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('cca2', 2)->unique(); // ISO 3166-1 alpha-2 code
            $table->string('cca3', 3)->unique(); // ISO 3166-1 alpha-3 code
            $table->string('name_common');
            $table->string('name_official');
            $table->unsignedBigInteger('population')->default(0);
            $table->integer('population_rank')->nullable();
            $table->string('flag_url')->nullable();
            $table->string('flag_emoji')->nullable();
            $table->double('area', 15, 2)->nullable();
            $table->json('translations')->nullable();
            $table->json('borders')->nullable(); // Array of cca3 codes
            $table->json('languages')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
