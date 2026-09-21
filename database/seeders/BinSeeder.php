<?php

namespace Database\Seeders;

use App\Models\Bin;
use App\Models\BinCompartment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample bins
        $bins = [
            ['device_id' => 'BIN-03', 'name' => 'Pasar Baru', 'location' => 'Pasar Baru Lt.1', 'area' => 'Pusat Kota'],
            ['device_id' => 'BIN-07', 'name' => 'Taman Monumen', 'location' => 'Taman Monumen', 'area' => 'Taman'],
            ['device_id' => 'BIN-12', 'name' => 'Jl. Sudirman', 'location' => 'Jl. Sudirman 45', 'area' => 'Komersial'],
            ['device_id' => 'BIN-18', 'name' => 'Kantor Walikota', 'location' => 'Kantor Walikota', 'area' => 'Pemerintah'],
            ['device_id' => 'BIN-22', 'name' => 'Mall Raya', 'location' => 'Mall Raya Gatot', 'area' => 'Komersial'],
            ['device_id' => 'BIN-09', 'name' => 'Taman Kota', 'location' => 'Taman Kota Barat', 'area' => 'Taman'],
        ];

        foreach ($bins as $binData) {
            // Create bin
            $bin = Bin::create(array_merge($binData, [
                'battery_level' => rand(20, 95),
                'status' => 'online',
                'last_reported_at' => now(),
            ]));

            // Create 3 compartments for each bin
            foreach (['organik', 'anorganik', 'b3'] as $category) {
                $capacity = rand(10, 95);
                $status = 'empty';
                if ($capacity >= 90) {
                    $status = 'full';
                } elseif ($capacity >= 70) {
                    $status = 'near';
                } elseif ($capacity > 0) {
                    $status = 'ok';
                }

                BinCompartment::create([
                    'bin_id' => $bin->id,
                    'category' => $category,
                    'capacity_percent' => $capacity,
                    'status' => $status,
                    'last_updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Bin seeder completed! Created ' . count($bins) . ' bins with 3 compartments each.');
    }
}
