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
            $table->integer("count_positive");
            $table->integer("count_negative");
            $table->timestamps();

            $table->primary(['word_id', 'context_id']);

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
