<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        Role::create(['name' => Role::ADMIN]);
        Role::create(['name' => Role::COMPANY]);
        Role::create(['name' => Role::USER]);
    }
}

