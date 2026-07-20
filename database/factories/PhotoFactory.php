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
            'path' => 'photos/'.$this->faker->uuid().'.jpg',
            'original_name' => 'photo.jpg',
            'size' => $this->faker->numberBetween(1000, 500000),
        ];
    }
}
