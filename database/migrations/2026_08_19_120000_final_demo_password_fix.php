<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $newPassword = Hash::make('123456789');

        DB::table('users')
            ->where('email', 'like', '%@jawla.test')
            ->update(['password' => $newPassword]);
    }

    public function down(): void
    {
        // One-way data fix
    }
};
