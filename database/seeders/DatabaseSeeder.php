<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Product::factory(100)->create();

        User::factory()->create([
            'name' => 'Shod',
            'email' => 'texus.tj@gmail.com',
            'phone' => '005335051',
            'password' => 'Shod63mm',
            'role' => 'admin',
        ]);
        User::factory()->create([
            'name' => '926463735',
            'email' => '926463735@gmail.com',
            'phone' => '926463735',
            'password' => '926463735',
            'role' => 'audit',
        ]);
    }
}
