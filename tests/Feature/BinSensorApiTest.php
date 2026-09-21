<?php

namespace Tests\Feature;

use App\Models\Bin;
use App\Models\BinCompartment;
use App\Models\Classification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BinSensorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_detection_updates_virtual_capacity_and_queues_servo_command(): void
    {
        $bin = Bin::create([
            'device_id' => 'BIN-03',
            'name' => 'Test Bin',
            'location' => 'Lab',
            'status' => 'online',
        ]);

        $compartment = BinCompartment::create([
            'bin_id' => $bin->id,
            'category' => 'anorganik',
            'capacity_percent' => 0,
            'status' => 'empty',
        ]);

        $response = $this->postJson('/api/bins/BIN-03/detection', [
            'nama_sampah' => 'Botol Plastik',
            'jenis_sampah' => 'anorganik',
            'confidence' => 0.86,
        ]);

        $response->assertOk()
            ->assertJsonPath('accepted', true)
            ->assertJsonPath('trigger_servo', 2)
            ->assertJsonPath('capacity_percent', 2);

        $this->assertDatabaseHas('bins', [
            'id' => $bin->id,
            'pending_servo_command' => 2,
        ]);

        $this->assertDatabaseHas('bin_compartments', [
            'id' => $compartment->id,
            'capacity_percent' => 2,
            'status' => 'ok',
        ]);

        $this->assertDatabaseHas('classifications', [
            'bin_compartment_id' => $compartment->id,
            'category' => 'anorganik',
            'detected_label' => 'Botol Plastik',
            'confidence' => 86,
        ]);
    }

    public function test_status_returns_pending_servo_once_then_resets_it(): void
    {
        $bin = Bin::create([
            'device_id' => 'BIN-03',
            'name' => 'Test Bin',
            'location' => 'Lab',
            'status' => 'online',
            'pending_servo_command' => 2,
        ]);

        BinCompartment::create([
            'bin_id' => $bin->id,
            'category' => 'anorganik',
            'capacity_percent' => 10,
            'status' => 'ok',
        ]);

        $this->getJson('/api/bins/BIN-03/status')
            ->assertOk()
            ->assertJsonPath('trigger_servo', 2);

        $this->assertDatabaseHas('bins', [
            'id' => $bin->id,
            'pending_servo_command' => 0,
        ]);

        $this->getJson('/api/bins/BIN-03/status')
            ->assertOk()
            ->assertJsonPath('trigger_servo', 0);
    }

    public function test_full_compartment_ignores_detection_without_queueing_servo_or_history(): void
    {
        $bin = Bin::create([
            'device_id' => 'BIN-03',
            'name' => 'Test Bin',
            'location' => 'Lab',
            'status' => 'online',
        ]);

        BinCompartment::create([
            'bin_id' => $bin->id,
            'category' => 'anorganik',
            'capacity_percent' => 90,
            'status' => 'full',
        ]);

        $this->postJson('/api/bins/BIN-03/detection', [
            'nama_sampah' => 'Botol Plastik',
            'jenis_sampah' => 'anorganik',
            'confidence' => 0.86,
        ])
            ->assertOk()
            ->assertJsonPath('accepted', false)
            ->assertJsonPath('trigger_servo', 0);

        $this->assertSame(0, Classification::count());
        $this->assertDatabaseHas('bins', [
            'id' => $bin->id,
            'pending_servo_command' => 0,
        ]);
    }
}
