<?php

namespace Database\Factories;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'path' => 'photos/'.Str::uuid()->toString().'.jpg',
            'original_name' => 'photo.jpg',
            'size' => 50000,
        ];
    }
}
