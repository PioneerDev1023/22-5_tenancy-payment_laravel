<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */

    public function run()
    {
        $allpermissions = [
            'manage-permission', 'create-permission', 'edit-permission', 'delete-permission',
            'manage-role', 'create-role', 'edit-role', 'delete-role', 'show-role',
            'manage-user', 'create-user', 'edit-user', 'delete-user', 'show-user','impersonate-user',
            'manage-module', 'create-module', 'delete-module', 'show-module', 'edit-module',
            'manage-setting',
            'manage-transaction',
            'manage-langauge', 'create-langauge', 'delete-langauge', 'show-langauge', 'edit-langauge',
            'manage-plan', 'create-plan', 'delete-plan', 'show-plan', 'edit-plan',
        ];
        $adminpermissions = [
            'manage-permission', 'create-permission', 'edit-permission', 'delete-permission',
            'manage-role', 'create-role', 'edit-role', 'delete-role', 'show-role',
            'manage-user', 'create-user', 'edit-user', 'delete-user', 'show-user','impersonate-user',
            'manage-setting',
            'manage-transaction',
            'manage-plan',
            'manage-chat'
        ];

        $modules = [
            'user', 'role', 'module', 'setting', 'langauge', 'permission', 'plan', 'chat',
        ];

        $settings = [
            ['key' => 'app_name', 'value' => 'Full Multi Tenancy Laravel Admin Saas'],
            ['key' => 'app_logo', 'value' => 'logo/app-logo.png'],
            ['key' => 'app_small_logo', 'value' => 'logo/app-small-logo.png'],
            ['key' => 'favicon_logo', 'value' => 'logo/app-favicon-logo.png'],
            ['key' => 'default_language', 'value' => 'en'],
            ['key' => 'currency', 'value' => 'usd'],
            ['key' => 'currency_symbol', 'value' => '$'],
            ['key' => 'date_format', 'value' => 'M j, Y'],
            ['key' => 'time_format', 'value' => 'g:i A'],



        ];
        foreach($settings as $setting){
            Setting::create($setting);
        }
        foreach ($allpermissions as $permission) {
            Permission::create([
                'name' => $permission
            ]);
        }
        $plan = Plan::create([
            'name' => 'Free',
            'price' => '0',
            'duration' => '1',
            'durationtype' => 'month',
            // 'max_users' => '10'
        ]);
        $permission = Permission::create([
            'name' => 'manage-chat'
        ]);
        $role = Role::create([
            'name' => 'Super Admin'
        ]);
        $adminRole = Role::create([
            'name' => 'Admin'
        ]);

        foreach ($allpermissions as $permission) {
            $per = Permission::findByName($permission);
            $role->givePermissionTo($per);
        }
        foreach ($adminpermissions as $permission) {
            $per = Permission::findByName($permission);
            $adminRole->givePermissionTo($per);
        }

        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin'),
            'avatar' => 'avatar/avatar.png',
            'type' => 'Super Admin',
            'lang' => 'en',
        ]);

        $user->assignRole($role->id);

        foreach ($modules as $module) {
            Module::create([
                'name' => $module
            ]);
        }
    }
}
