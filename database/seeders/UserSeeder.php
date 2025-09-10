<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    if (app()->environment('local')) {
        // Permissions for web and api
        DB::table('permissions')->insert([
            // Web permissions
            ['name' => 'insert', 'guard_name' => 'web'],
            ['name' => 'update', 'guard_name' => 'web'],
            ['name' => 'delete', 'guard_name' => 'web'],
            ['name' => 'view',   'guard_name' => 'web'],

            // API permissions
            ['name' => 'insert', 'guard_name' => 'api'],
            ['name' => 'update', 'guard_name' => 'api'],
            ['name' => 'delete', 'guard_name' => 'api'],
            ['name' => 'view',   'guard_name' => 'api'],
        ]);

        // Roles for web and api
        DB::table('roles')->insert([
            ['name' => 'staff', 'guard_name' => 'api'],
            ['name' => 'admin', 'guard_name' => 'api'],
            ['name' => 'user',  'guard_name' => 'api'],
        ]);

        // Users
        DB::table('users')->insert([
            [
                'id' => 1,
                'name' => 'Staff',
                'slug' => 'staff',
                'email' => 'staff@staff.com',
                'password' => Hash::make('12345678'),
                'otp_verified_at' => now()
            ],
            [
                'id' => 2,
                'name' => 'Admin',
                'slug' => 'admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('12345678'),
                'otp_verified_at' => now()
            ],
            [
                'id' => 3,
                'name' => 'User',
                'slug' => 'user',
                'email' => 'user@user.com',
                'password' => Hash::make('12345678'),
                'otp_verified_at' => now()
            ],
        ]);

        // Profiles
        DB::table('profiles')->insert([
            ['user_id' => 1, 'dob' => '1990-01-01', 'gender' => 'male'],
            ['user_id' => 2, 'dob' => '1990-01-01', 'gender' => 'male'],
            ['user_id' => 3, 'dob' => '1990-01-01', 'gender' => 'male'],
        ]);

        // Assign roles to users
        DB::table('model_has_roles')->insert([
            ['role_id' => 1, 'model_id' => 1, 'model_type' => 'App\Models\User'], // Staff (web)
            ['role_id' => 2, 'model_id' => 2, 'model_type' => 'App\Models\User'], // Admin (web)
            ['role_id' => 3, 'model_id' => 3, 'model_type' => 'App\Models\User'], // User (api)
        ]);

        // Assign permissions
        DB::table('model_has_permissions')->insert([
            // Staff (web) full permissions
            ['permission_id' => 1, 'model_id' => 1, 'model_type' => 'App\Models\User'],
            ['permission_id' => 2, 'model_id' => 1, 'model_type' => 'App\Models\User'],
            ['permission_id' => 3, 'model_id' => 1, 'model_type' => 'App\Models\User'],
            ['permission_id' => 4, 'model_id' => 1, 'model_type' => 'App\Models\User'],

            // Admin (web) full permissions
            ['permission_id' => 1, 'model_id' => 2, 'model_type' => 'App\Models\User'],
            ['permission_id' => 2, 'model_id' => 2, 'model_type' => 'App\Models\User'],
            ['permission_id' => 3, 'model_id' => 2, 'model_type' => 'App\Models\User'],
            ['permission_id' => 4, 'model_id' => 2, 'model_type' => 'App\Models\User'],

            // User (api) full API permissions
            ['permission_id' => 5, 'model_id' => 3, 'model_type' => 'App\Models\User'],
            ['permission_id' => 6, 'model_id' => 3, 'model_type' => 'App\Models\User'],
            ['permission_id' => 7, 'model_id' => 3, 'model_type' => 'App\Models\User'],
            ['permission_id' => 8, 'model_id' => 3, 'model_type' => 'App\Models\User'],
        ]);
    }
}

}
