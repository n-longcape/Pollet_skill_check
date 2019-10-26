<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
            factory(App\Models\User::class, 10)->create()->each(function ($user) {
                $user->payments()->saveMany(factory(App\Models\Payment::class, 10)->create(['user_id' => $user->id]));
            });
    }

}
