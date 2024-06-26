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
        Schema::create('searches', function (Blueprint $table) {
            $table->unsignedBigInteger("word_id");
            $table->unsignedBigInteger("context_id");
            $table->unsignedInteger("count_pages");
            $table->unsignedInteger("items_per_page");
            $table->unsignedInteger("count_positive");
            $table->unsignedInteger("count_negative");
            $table->timestamps();

            $table->primary(['word_id', 'context_id', 'count_pages', 'items_per_page']);

            $table->foreign('word_id')->references('id')->on('words')->onDelete('restrict');
            $table->foreign('context_id')->references('id')->on('contexts')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('searches');
    }
};
