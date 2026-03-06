<?php

namespace Database\Factories\Entities\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CollectionFactory extends Factory
{
    protected $model = \BookStack\Entities\Models\Collection::class;

    public function definition()
    {
        $description = $this->faker->paragraph();

        return [
            'name'             => $this->faker->sentence,
            'slug'             => Str::random(10),
            'description'      => $description,
            'description_html' => '<p>' . e($description) . '</p>',
        ];
    }
}
