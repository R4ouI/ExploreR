<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('saved_routes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['route_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_likes');
    }
};
