<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['login' => 'admin'],
            [
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'full_name' => 'Администратор системы',
                'is_active' => true,
            ],
        );

        User::query()->firstOrCreate(
            ['login' => 'dispatcher'],
            [
                'password' => Hash::make('dispatcher123'),
                'role' => 'dispatcher',
                'full_name' => 'Диспетчер',
                'is_active' => true,
            ],
        );

        User::query()->firstOrCreate(
            ['login' => 'medic'],
            [
                'password' => Hash::make('medic123'),
                'role' => 'medic',
                'full_name' => 'Медицинский работник',
                'is_active' => true,
            ],
        );

        User::query()->firstOrCreate(
            ['login' => 'mechanic'],
            [
                'password' => Hash::make('mechanic123'),
                'role' => 'mechanic',
                'full_name' => 'Механик',
                'is_active' => true,
            ],
        );

        $driverUser = User::query()->firstOrCreate(
            ['login' => 'driver1'],
            [
                'password' => Hash::make('driver123'),
                'role' => 'driver',
                'full_name' => 'Иванов Иван Иванович',
                'phone' => '+7 900 000-00-01',
                'is_active' => true,
            ],
        );

        $driver = Driver::query()->firstOrCreate(
            ['user_id' => $driverUser->id],
            [
                'full_name' => 'Иванов Иван Иванович',
                'phone' => '+7 900 000-00-01',
                'license_number' => '99 99 123456',
                'license_category' => 'B, C',
                'status' => 'active',
            ],
        );

        $vehicle = Vehicle::query()->firstOrCreate(
            ['plate_number' => 'А123ВС102'],
            [
                'brand' => 'ГАЗ',
                'model' => 'ГАЗель Next',
                'vin' => 'X96TEST0000000001',
                'year' => 2020,
                'fuel_type' => 'diesel',
                'current_mileage' => 125000,
                'status' => 'available',
            ],
        );

        WorkOrder::query()->firstOrCreate(
            [
                'date' => now()->toDateString(),
                'shift' => 'day',
                'driver_id' => $driver->id,
            ],
            [
                'vehicle_id' => $vehicle->id,
                'route_name' => 'Склад - торговые точки города',
                'dispatcher_comment' => 'Учебный план-наряд для демонстрации workflow.',
                'status' => 'planned',
                'created_by' => $admin->id,
            ],
        );
    }
}

