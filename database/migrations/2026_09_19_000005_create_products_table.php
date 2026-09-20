<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name_id');
            $table->string('name_en')->nullable();
            $table->string('name_zh')->nullable();
            $table->string('slug')->unique();
            $table->string('category', 40)->index();
            $table->string('sku')->nullable();
            $table->string('grade')->nullable();
            $table->string('packaging')->nullable();
            $table->string('application')->nullable();
            $table->text('summary_id')->nullable();
            $table->text('summary_en')->nullable();
            $table->text('summary_zh')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
