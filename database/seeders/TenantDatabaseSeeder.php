<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */

    public function run()
    {
        $allpermissions = [
            'manage-user', 'create-user', 'edit-user', 'delete-user', 'show-user','impersonate-user',
            'manage-role', 'create-role', 'edit-role', 'delete-role', 'show-role',
            'manage-setting',
            'manage-chat',
            'manage-transaction',
            'manage-plan', 'create-plan', 'delete-plan', 'show-plan', 'edit-plan',
        ];
        $adminpermissions = [
            'manage-user', 'create-user', 'edit-user', 'delete-user', 'show-user','impersonate-user',
            'manage-role', 'create-role', 'edit-role', 'delete-role', 'show-role',
            'manage-setting',
            'manage-chat',
            'manage-transaction',
            'manage-plan', 'create-plan', 'delete-plan', 'show-plan', 'edit-plan',
        ];

        $modules = [
            'user','role','setting','plan', 'chat',
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
        tenancy()->central(function ($tenant) {
            Storage::copy('logo/app-logo.png', $tenant->id . '/logo/app-logo.png');
            Storage::copy('logo/app-small-logo.png', $tenant->id . '/logo/app-small-logo.png');
            Storage::copy('logo/app-favicon-logo.png', $tenant->id . '/logo/app-favicon-logo.png');
            Storage::copy('avatar/avatar.png', $tenant->id . '/avatar.png');
        });

        foreach($settings as $setting){
            Setting::create($setting);
        }
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ($allpermissions as $permission) {
            Permission::create([
                'name' => $permission
            ]);
        }

        $adminRole = Role::create([
            'name' => 'Admin'
        ]);

        foreach ($adminpermissions as $permission) {
            $per = Permission::findByName($permission);
            $adminRole->givePermissionTo($per);
        }

        $centralUser = tenancy()->central(function ($tenant) {
            return User::find($tenant->id);
        });

        $user = User::create([
            'name' => $centralUser->name,
            'email' =>  $centralUser->email,
            'password' =>  $centralUser->password,
            'avatar' => 'avatar/avatar.png',
            'type' => 'Admin',
            'lang' => 'en',
            'plan_id' => 1,
            'plan_expired_date' => null,
        ]);

        $user->assignRole($adminRole->id);

        foreach ($modules as $module) {
            Module::create([
                'name' => $module
            ]);
        }

        $plan = Plan::create([
            'name' => 'Free',
            'price' => '0',
            'duration' => '1',
            'durationtype' => 'Year',
            'max_users' => '10'
        ]);
    }
}
