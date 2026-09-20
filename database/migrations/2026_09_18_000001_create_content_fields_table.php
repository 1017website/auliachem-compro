<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_fields', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('section');
            $table->string('type');
            $table->string('label');
            $table->json('values');
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_fields');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
