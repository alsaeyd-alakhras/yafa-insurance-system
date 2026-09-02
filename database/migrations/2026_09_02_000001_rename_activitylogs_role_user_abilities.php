<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('role_user')
            ->where('role_name', 'like', 'activitylogs.%')
            ->get()
            ->each(function ($row): void {
                $newRoleName = str_replace('activitylogs.', 'activity_logs.', $row->role_name);

                DB::table('role_user')
                    ->where('user_id', $row->user_id)
                    ->where('role_name', $row->role_name)
                    ->update([
                        'role_name' => $newRoleName,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('role_user')
            ->where('role_name', 'like', 'activity_logs.%')
            ->get()
            ->each(function ($row): void {
                $newRoleName = str_replace('activity_logs.', 'activitylogs.', $row->role_name);

                DB::table('role_user')
                    ->where('user_id', $row->user_id)
                    ->where('role_name', $row->role_name)
                    ->update([
                        'role_name' => $newRoleName,
                    ]);
            });
    }
};
