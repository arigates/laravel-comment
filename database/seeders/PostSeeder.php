<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = app(Generator::class);

        $user = User::query()
            ->where('role', '=', 'admin')
            ->first();

        for($i = 0; $i < 10; $i++) {
            $title = $faker->text(50);
            Post::create([
                'title' => $title,
                'slug' => Str::slug($title),
                'description' => $faker->text(),
                'media' => null,
                'user_id' => $user->id,
            ]);
        }
    }
}
