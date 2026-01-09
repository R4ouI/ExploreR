<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('saved_routes', function (Blueprint $table) {
            $table->id();

            // daca vrei trasee salvate doar pentru user logat, lasa nullable() OFF
            // eu il las nullable ca sa nu te blochezi daca vreti share si fara cont
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->uuid('slug')->unique();     // link-ul de share
            $table->json('payload');            // tot traseul (start/end/route/mode/etc)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_routes');
    }
};
