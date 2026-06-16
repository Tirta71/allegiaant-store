<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()
            ->where('email', 'admin@growpet.test')
            ->where('is_admin', true)
            ->delete();

        User::query()->updateOrCreate(
            ['email' => 'allegiaant@mail.store'],
            [
                'name' => 'Allegiaant Admin',
                'password' => 'hero1234',
                'is_admin' => true,
            ],
        );

        $this->call(ProductCatalogSeeder::class);
    }
}
