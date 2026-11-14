<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Les numéros du seeder sont enregistrés sans espaces
        DB::table('users')->whereNotNull('telephone')
            ->update(['telephone' => DB::raw("REPLACE(telephone, ' ', '')")]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->unique('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['telephone']);
            $table->string('email')->nullable(false)->change();
        });
    }
};