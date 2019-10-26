<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use Faker\Generator as Faker;

$factory->define(\App\Models\Payment::class, function (Faker $faker) {
    return [
        'amount' => rand(1, 10000),
        'created_at' => $faker->dateTimeThisMonth(\Carbon\Carbon::now()->subMonth())
    ];
});
