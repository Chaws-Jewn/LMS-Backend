<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory()->create([
            'role' => json_encode(['maintenance']),
            'position' => 'Head',
            'username' => 'superadmin',
            'password' => Hash::make('Admin123')
        ]);

        User::factory()->create([
            'role' => json_encode(['cataloging']),
            'position' => 'Chief',
            'username' => 'cataloging',
            'password' => Hash::make('Admin123')
        ]);

        User::factory()->create([
            'role' => json_encode(['circulation']),
            'position' => 'Front Desk',
            'username' => 'circulation',
            'password' => Hash::make('Admin123')
        ]);

        User::factory()->create([
            'role' => json_encode(['opac']),
            'position' => 'OPAC User',
            'username' => 'opac',
            'password' => Hash::make('Admin123')
        ]);

        /* MULTI ACCESS USER */
        // User::factory()->create([
        //     'role' => json_encode(['locker', 'circulation']),
        //     'position' => 'Front Desk',
        //     'username' => 'frontdesk',
        //     'password' => Hash::make('Admin123')
        // ]);

        /* RANDOM USERS */
        User::factory()->count(20)->create([
            'role' => json_encode(['student'])
        ]);
        
        User::factory()->create([
            'role' => json_encode(['student']),
            'id' => 202110194,
            'program' => 'BSIT',
            'gender' => 1,
            'first_name' => 'Charles John',
            'last_name' => 'Malit',
            'username' => '202110194',
            'password' => Hash::make('123')
        ]);

        User::factory()->create([
            'role' => json_encode(['student']),
            'id' => 202111304,
            'program' => 'BSCS',
            'gender' => 1,
            'first_name' => 'Don Ace',
            'last_name' => 'Rañada',
            'username' => '202111304',
            'password' => Hash::make('123')
        ]);

        User::factory()->create([
            'role' => json_encode(['student']),
            'id' => 202110876,
            'program' => 'BSBA-MKT',
            'gender' => 1,
            'first_name' => 'John Laurenze',
            'last_name' => 'Leguidleguid',
            'username' => '202110876',
            'password' => Hash::make('123')
        ]);

        User::factory()->create([
            'role' => json_encode(['student']),
            'id' => 202110878,
            'program' => 'BSTM',
            'gender' => 0,
            'first_name' => 'Eunice',
            'last_name' => 'Protasio',
            'username' => '202110878',
            'password' => Hash::make('123')
        ]);

        User::factory()->create([
            'role' => json_encode(['student']),
            'id' => 202110265,
            'program' => 'BACOMM',
            'gender' => 1,
            'first_name' => 'Nichole',
            'last_name' => 'Velasco',
            'username' => '202110265',
            'password' => Hash::make('123')
        ]);

    
        User::factory()->create([
            'role' => json_encode(['student']),
            'id' => 202110259,
            'program' => 'BSEMC',
            'patron_id' => 2,
            'gender' => 1,
            'first_name' => 'Venerson',
            'last_name' => 'Tabinas',
            'username' => '202110259',
            'password' => Hash::make('123')
        ]);
    }
}