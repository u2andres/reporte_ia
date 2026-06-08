<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // OJO: NO ejecutar PofReportSeeder aquí.
        // Los modelos PofP* ahora usan la conexión 'doctrine' (MySQL con datos
        // REALES). Correr el seeder haría truncate/insert sobre esa base.
        // El seeder se conserva solo como fixture histórico para SQLite.
        // $this->call(PofReportSeeder::class);
    }
}
