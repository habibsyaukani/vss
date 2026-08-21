<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\DeviceGroup;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed initial users
        $this->seedUsers();

        // Seed device groups
        $this->seedDeviceGroups();
    }

    private function seedUsers(): void
    {
        // Check if admin already exists
        if (User::where('email', 'admin@vss.com')->exists()) {
            $this->command->info('Admin user already exists. Skipping...');
            return;
        }

        // Create Admin user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@vss.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->command->info('✓ Admin user created: admin@vss.com / admin123');

        // Create Fleet Manager user
        User::create([
            'name' => 'Fleet Manager',
            'email' => 'manager@vss.com',
            'password' => Hash::make('manager123'),
            'role' => 'fleet_manager',
            'status' => 'active',
        ]);

        $this->command->info('✓ Fleet Manager user created: manager@vss.com / manager123');
    }

    private function seedDeviceGroups(): void
    {
        // Check if groups already exist
        if (DeviceGroup::exists()) {
            $this->command->info('Device groups already exist. Skipping...');
            return;
        }

        $groups = [
            ['group_code' => 'BUS', 'group_name' => 'BUS - GPE', 'total_devices' => 46],
            ['group_code' => 'DT', 'group_name' => 'DT - GPE', 'total_devices' => 125],
            ['group_code' => 'FT', 'group_name' => 'FT - GPE', 'total_devices' => 13],
            ['group_code' => 'HD', 'group_name' => 'HD - GPE', 'total_devices' => 107],
            ['group_code' => 'PATROL', 'group_name' => 'PATROL - GPE', 'total_devices' => 4],
            ['group_code' => 'WT', 'group_name' => 'WT - GPE', 'total_devices' => 2],
        ];

        foreach ($groups as $group) {
            DeviceGroup::create($group);
            $this->command->info('✓ Created device group: ' . $group['group_name']);
        }
    }
}
