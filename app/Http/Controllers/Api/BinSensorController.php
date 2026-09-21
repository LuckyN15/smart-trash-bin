<?php

namespace App\Http\Controllers\Api;

use App\Models\Bin;
use App\Models\BinCompartment;
use App\Models\BinNotification;
use App\Models\Classification;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class BinSensorController extends Controller
{
    private const VIRTUAL_COMPARTMENT_CAPACITY = 50;

    private const SERVO_COMMANDS = [
        'organik' => 1,
        'anorganik' => 2,
        'b3' => 3,
    ];

    /**
     * Log deteksi dari sistem kamera (YOLO)
     * POST /api/bins/{device_id}/detection
     */
    public function logDetection(Request $request, string $device_id): JsonResponse
    {
        $validated = $request->validate([
            'nama_sampah' => 'required|string|max:100',
            'jenis_sampah' => 'required|in:organik,anorganik,b3',
            'confidence' => 'sometimes|numeric|min:0|max:1',
            'model_version' => 'sometimes|nullable|string|max:50',
        ]);

        $result = DB::transaction(function () use ($validated, $device_id) {
            $bin = Bin::where('device_id', $device_id)->lockForUpdate()->first();
            if (!$bin) {
                return [
                    'status' => 404,
                    'body' => [
                        'success' => false,
                        'message' => 'Device tidak ditemukan',
                    ],
                ];
            }

            $category = $validated['jenis_sampah'];

            $compartment = BinCompartment::firstOrCreate(
                ['bin_id' => $bin->id, 'category' => $category],
                ['capacity_percent' => 0, 'status' => 'empty']
            );

            $compartment->refresh();
            if ($compartment->status === 'full') {
                $bin->update(['last_reported_at' => now()]);

                return [
                    'status' => 200,
                    'body' => [
                        'success' => true,
                        'accepted' => false,
                        'message' => 'Kompartemen sudah penuh, deteksi diabaikan',
                        'device_id' => $bin->device_id,
                        'category' => $category,
                        'trigger_servo' => 0,
                    ],
                ];
            }

            // YOLO kirim confidence 0.0-1.0, tabel classifications nyimpan 0-100.
            $confidencePercent = isset($validated['confidence'])
                ? (int) round($validated['confidence'] * 100)
                : 0;

            $currentTrashCount = Classification::where('bin_compartment_id', $compartment->id)
                ->where('status', 'success')
                ->count();
            $newTrashCount = $currentTrashCount + 1;

            $maxItems = (int) Setting::valueFor("max_items_{$category}", self::VIRTUAL_COMPARTMENT_CAPACITY);
            if ($maxItems <= 0) {
                $maxItems = self::VIRTUAL_COMPARTMENT_CAPACITY;
            }

            $capacityPercent = min(100, (int) ceil(($newTrashCount / $maxItems) * 100));
            $status = $capacityPercent >= 90 ? 'full' : ($capacityPercent >= 70 ? 'near' : ($capacityPercent > 0 ? 'ok' : 'empty'));
            $previousStatus = $compartment->status;

            $compartment->update([
                'capacity_percent' => $capacityPercent,
                'status' => $status,
                'last_updated_at' => now(),
            ]);

            if ($status !== $previousStatus && in_array($status, ['near', 'full'], true)) {
                BinNotification::create([
                    'bin_id' => $bin->id,
                    'type' => 'bin_full',
                    'title' => "BIN-{$bin->device_id} Kompartemen " . ucfirst($category) . ($status === 'full' ? ' Penuh' : ' Hampir Penuh'),
                    'message' => "Kapasitas " . ucfirst($category) . " mencapai {$capacityPercent}%.",
                    'level' => $status === 'full' ? 'danger' : 'warning',
                    'status' => 'unread',
                    'occurred_at' => now(),
                ]);
            }

            $servoCommand = self::SERVO_COMMANDS[$category];
            $bin->update([
                'pending_servo_command' => $servoCommand,
                'last_reported_at' => now(),
            ]);

            $classification = Classification::create([
                'bin_compartment_id' => $compartment->id,
                'category' => $category,
                'detected_label' => $validated['nama_sampah'],
                'confidence' => $confidencePercent,
                'status' => 'success',
                'model_version' => $validated['model_version'] ?? 'v1',
                'detected_at' => now(),
            ]);

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'accepted' => true,
                    'message' => 'Deteksi tercatat dan perintah servo dibuat',
                    'device_id' => $bin->device_id,
                    'category' => $category,
                    'nama_sampah' => $classification->detected_label,
                    'confidence' => $confidencePercent,
                    'trash_count' => $newTrashCount,
                    'capacity_percent' => $capacityPercent,
                    'compartment_status' => $status,
                    'trigger_servo' => $servoCommand,
                    'classification_id' => $classification->id,
                ],
            ];
        });

        return response()->json($result['body'], $result['status']);
    }

    /**
     * Update bin compartment capacity dari ESP32
     * POST /api/bins/{device_id}/update
     */
    public function updateCompartment(Request $request, string $device_id): JsonResponse
    {
        $validated = $request->validate([
            'organik' => 'sometimes|required|integer|min:0|max:100',
            'anorganik' => 'sometimes|required|integer|min:0|max:100',
            'b3' => 'sometimes|required|integer|min:0|max:100',
            'battery' => 'sometimes|required|integer|min:0|max:100',
            'temperature' => 'sometimes|nullable|integer',
            'humidity' => 'sometimes|nullable|integer',
        ]);

        // Cari bin berdasarkan device_id
        $bin = Bin::where('device_id', $device_id)->first();
        if (!$bin) {
            return response()->json([
                'success' => false,
                'message' => 'Device tidak ditemukan',
            ], 404);
        }

        // Update battery level
        if (isset($validated['battery'])) {
            $bin->update(['battery_level' => $validated['battery']]);
        }

        // Update last reported time
        $bin->update(['last_reported_at' => now()]);

        // Update compartments
        $compartments = ['organik', 'anorganik', 'b3'];
        $alerts = [];

        foreach ($compartments as $category) {
            if (isset($validated[$category])) {
                $capacity = $validated[$category];

                // Tentukan status berdasarkan kapasitas
                $status = 'empty';
                if ($capacity >= 90) {
                    $status = 'full';
                } elseif ($capacity >= 70) {
                    $status = 'near';
                } elseif ($capacity > 0) {
                    $status = 'ok';
                }

                // Update atau buat compartment
                $compartment = BinCompartment::updateOrCreate(
                    ['bin_id' => $bin->id, 'category' => $category],
                    [
                        'capacity_percent' => $capacity,
                        'status' => $status,
                        'last_updated_at' => now(),
                    ]
                );

                // Buat notifikasi jika full atau near
                if ($status === 'full') {
                    BinNotification::create([
                        'bin_id' => $bin->id,
                        'type' => 'bin_full',
                        'title' => "BIN-{$bin->device_id} Kompartemen " . ucfirst($category) . " Penuh",
                        'message' => "Kapasitas " . ucfirst($category) . " mencapai {$capacity}%. Segera jadwalkan pengangkutan.",
                        'level' => 'danger',
                        'status' => 'unread',
                        'occurred_at' => now(),
                    ]);

                    $alerts[] = [
                        'category' => $category,
                        'capacity' => $capacity,
                        'alert_type' => 'full',
                    ];
                } elseif ($status === 'near') {
                    BinNotification::create([
                        'bin_id' => $bin->id,
                        'type' => 'bin_full',
                        'title' => "BIN-{$bin->device_id} Kompartemen " . ucfirst($category) . " Hampir Penuh",
                        'message' => "Kapasitas " . ucfirst($category) . " mencapai {$capacity}%. Monitor dalam 2 jam ke depan.",
                        'level' => 'warning',
                        'status' => 'unread',
                        'occurred_at' => now(),
                    ]);

                    $alerts[] = [
                        'category' => $category,
                        'capacity' => $capacity,
                        'alert_type' => 'near',
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diupdate',
            'device_id' => $bin->device_id,
            'battery_level' => $bin->battery_level,
            'alerts' => $alerts,
            'compartments' => [
                'organik' => $validated['organik'] ?? null,
                'anorganik' => $validated['anorganik'] ?? null,
                'b3' => $validated['b3'] ?? null,
            ],
        ]);
    }

    /**
     * Get bin status
     * GET /api/bins/{device_id}/status
     */
    public function getStatus(string $device_id): JsonResponse
    {
        $result = DB::transaction(function () use ($device_id) {
            $bin = Bin::where('device_id', $device_id)
                ->with('compartments')
                ->lockForUpdate()
                ->first();

            if (!$bin) {
                return [
                    'status' => 404,
                    'body' => [
                        'success' => false,
                        'message' => 'Device tidak ditemukan',
                    ],
                ];
            }

            $triggerServo = (int) $bin->pending_servo_command;

            if ($triggerServo !== 0) {
                $bin->update([
                    'pending_servo_command' => 0,
                    'last_reported_at' => now(),
                ]);
            }

        $compartmentsData = [];
        foreach ($bin->compartments as $comp) {
            $compartmentsData[$comp->category] = [
                'capacity_percent' => $comp->capacity_percent,
                'status' => $comp->status,
                'can_accept' => $comp->status !== 'full',
            ];
        }
            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'device_id' => $bin->device_id,
                    'name' => $bin->name,
                    'location' => $bin->location,
                    'battery_level' => $bin->battery_level,
                    'online_status' => $bin->status,
                    'trigger_servo' => $triggerServo,
                    'compartments' => $compartmentsData,
                    'last_reported_at' => $bin->last_reported_at,
                ],
            ];
        });

        return response()->json($result['body'], $result['status']);
    }

    /**
     * Batch update compartments
     * POST /api/bins/{device_id}/batch-update
     */
    public function batchUpdate(Request $request, string $device_id): JsonResponse
    {
        $validated = $request->validate([
            'data' => 'required|array',
            'data.*.category' => 'required|in:organik,anorganik,b3',
            'data.*.capacity' => 'required|integer|min:0|max:100',
        ]);

        $bin = Bin::where('device_id', $device_id)->first();
        if (!$bin) {
            return response()->json([
                'success' => false,
                'message' => 'Device tidak ditemukan',
            ], 404);
        }

        $alerts = [];
        foreach ($validated['data'] as $item) {
            $capacity = $item['capacity'];
            $category = $item['category'];

            // Tentukan status
            $status = $capacity >= 90 ? 'full' : ($capacity >= 70 ? 'near' : ($capacity > 0 ? 'ok' : 'empty'));

            // Update compartment
            BinCompartment::updateOrCreate(
                ['bin_id' => $bin->id, 'category' => $category],
                ['capacity_percent' => $capacity, 'status' => $status, 'last_updated_at' => now()]
            );

            if ($status === 'full') {
                $alerts[] = ['category' => $category, 'status' => 'full'];
            }
        }

        $bin->update(['last_reported_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Batch update berhasil',
            'alerts_count' => count($alerts),
            'alerts' => $alerts,
        ]);
    }
}
