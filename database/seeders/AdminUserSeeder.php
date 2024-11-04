<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Budi',
            'image' => null,
            'role' => 'admin',
            'email' => 'budi@mail.com',
            'password' => Hash::make('P@ss1234'),
        ]);
    }
}
