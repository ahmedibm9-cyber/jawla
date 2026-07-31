<?php

namespace Database\Factories;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        return [
            'disk' => 'public',
            'path' => 'photos/'.\Faker\Factory::create()->uuid().'.jpg',
            'original_name' => 'photo.jpg',
            'size' => \Faker\Factory::create()->numberBetween(1000, 500000),
        ];
    }
}
