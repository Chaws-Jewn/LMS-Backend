<?php

namespace Database\Seeders;

use App\Models\Material;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // DB::table('patrons')->insert([
        //     [
        //         'patron' => 'student (online)',
        //         'fine' => 500.00,
        //         'materials_allowed' => 3,
        //         'hours_allowed' => 72,
        //         'description' => 'GC students'
        //     ],
        //     [
        //         'patron' => 'student (face-to-face)',
        //         'fine' => 250.00,
        //         'materials_allowed' => 3,
        //         'hours_allowed' => 3,
        //         'description' => 'GC faculty members'
        //     ],
        //     [
        //         'patron' => 'faculty',
        //         'fine' => 250.00,
        //         'materials_allowed' => 5,
        //         'hours_allowed' => 168,
        //         'description' => 'GC faculty members'
        //     ],
        //     [
        //         'patron' => 'admin',
        //         'fine' => 250.00,
        //         'materials_allowed' => 5,
        //         'hours_allowed' => 168,
        //         'description' => 'GC Admin members'
        //     ],
        // ]);


        $this->call([
            // ProgramSeeder::class,
            // UserSeeder::class,
            // LocationSeeder::class,
            // ProjectSeeder::class,
            // AnnouncementSeeder::class,
            MaterialSeeder::class,
            ProjectSeeder::class,
        ]);

        // Material::factory()->count(20000)->create();
    }
}
