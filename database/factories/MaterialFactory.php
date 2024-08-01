<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Str;
use App\Models\Material;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Material>
 */
class MaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        
        $accession = Str::random(15); // Generate a random string of length 5

        // Ensure uniqueness
        while (Material::where('accession', $accession)->exists()) {
            $accession = Str::random(15);
        }

        $authors = [ 
            fake()->name(),
            fake()->name(),
            fake()->name()
        ];

        $type = fake()->numberBetween(1, 3);

        // if ($type == 0) {
        //     $material = [
        //         'material_type' => $type,
        //         'accession' => $accession,
        //         'title' => Str::title(fake()->words(3, true)), 
        //         'authors' => json_encode($authors),
        //         'language' => fake()->randomElement(['English', 'Tagalog']),
        //         'acquired_date' => fake()->date(),   
        //         'publisher' => fake()->company(),
        //         'copyright' => fake()->year(),
        //         'volume' => fake()->numberBetween(1, 3),
        //         'edition' => fake()->numberBetween(1, 10),
        //         'issue' => fake()->numberBetween(1, 5),
        //         'pages' => fake()->randomNumber(3),
        //         'remarks' => fake()->sentence(),
        //         'source_of_fund' => fake()->randomElement(['Donated', 'Replacement', 'Purchased']),
        //         'price' => fake()->numberBetween(1, 1000),
        //         'status' => fake()->numberBetween(0, 3),
        //         'inventory_status' => fake()->numberBetween(0, 4),
        //         'location' => fake()->randomElement(['ABCOMM', 'CAD', 'COM', 'EDUC', 'FIL', 'FOR', 'FS', 'HM']),
        //         'call_number' => Str::random(5),
        //         'author_number' => Str::random(5)  
        //     ];
        // } else if ($type == 1) {
        if($type == 1) {
            $material = [
                'material_type' => $type,
                'accession' => $accession,
                'periodical_type' => fake()->numberBetween(0, 2),
                'title' => Str::title(fake()->words(3, true)), 
                'authors' => json_encode($authors),
                'language' => fake()->randomElement(['English', 'Tagalog']),
                'acquired_date' => fake()->date(),   
                'publisher' => fake()->company(),
                'copyright' => fake()->year(),
                'volume' => fake()->numberBetween(1, 3),
                'issue' => fake()->numberBetween(1, 5),
                'pages' => fake()->randomNumber(3),
                'remarks' => fake()->sentence(),
                'date_published' => fake()->date()      
            ];
        } elseif ($type == 2) {
            $material = [
                'material_type' => $type,
                'accession' => $accession,
                'periodical_type' => fake()->numberBetween(0, 2),
                'title' => Str::title(fake()->words(3, true)), 
                'authors' => json_encode($authors),
                'language' => fake()->randomElement(['English', 'Tagalog']),
                'subject' => fake()->sentence(7, true),
                'date_published' => fake()->date(),
                'publisher' => fake()->company(),
                'volume' => fake()->numberBetween(1, 3),
                'issue' => fake()->numberBetween(1, 3),
                'pages' => fake()->randomNumber(3),
                'abstract' => fake()->sentences(2, true),
                'remarks' => fake()->sentence()
            ];
        } elseif ($type == 3) {
            $material = [
                'material_type' => $type,
                'accession' => $accession,
                'title' => Str::title(fake()->words(3, true)), 
                'authors' => json_encode($authors),
                'call_number' => 'aav' . Str::random(5)
            ];
        }

        return $material;
    }
}
