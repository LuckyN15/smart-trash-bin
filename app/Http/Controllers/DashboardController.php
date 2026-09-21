<?php

namespace App\Http\Controllers;

use App\Models\Bin;
use App\Models\BinCompartment;
use App\Models\BinNotification;
use App\Models\Classification;
use App\Models\Setting;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function admin()
    {
        return $this->dashboard();
    }

    /**
     * Show the user dashboard.
     */
    public function user()
    {
        return $this->dashboard();
    }

    /**
     * Reset all compartment capacities to zero for admin
     */
    public function resetAll(Request $request)
    {
        BinCompartment::query()->update([
            'capacity_percent' => 0,
            'status' => 'empty',
            'last_updated_at' => now(),
        ]);
        Classification::query()->delete();
        BinNotification::query()->delete();
        Bin::query()->update(['pending_servo_command' => 0]);

        return redirect()->route('dashboard.admin')
            ->with('status', 'Semua kapasitas, riwayat klasifikasi, notifikasi, dan antrean servo berhasil direset.');
    }

    /**
     * Save dashboard settings
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'max_items_organik' => 'required|integer|min:1|max:500',
            'max_items_anorganik' => 'required|integer|min:1|max:500',
            'max_items_b3' => 'required|integer|min:1|max:500',
            'camera_rtsp_url' => 'nullable|string|max:500',
        ]);

        Setting::setValue('max_items_organik', $validated['max_items_organik']);
        Setting::setValue('max_items_anorganik', $validated['max_items_anorganik']);
        Setting::setValue('max_items_b3', $validated['max_items_b3']);
        Setting::setValue('camera_rtsp_url', $validated['camera_rtsp_url'] ?? '');

        return redirect()->route('dashboard.admin')
            ->with('status', 'Batas maksimal sampah per jenis berhasil diperbarui.');
    }

    /**
     * Data ringan buat polling real-time dari frontend (dipanggil lewat fetch()
     * tiap beberapa detik). Sengaja dipisah dari dashboard() supaya query-nya
     * ringan dan cepat, tidak perlu hitung ulang chart/statistik yang berat.
     */
    public function liveData()
    {
        $bins = Bin::with('compartments')->orderBy('device_id')->get();

        $compartmentsFull = BinCompartment::where('status', 'full')->count();
        $compartmentsNear = BinCompartment::where('status', 'near')->count();
        $compartmentsOk = BinCompartment::where('status', 'ok')->count();
        $compartmentsEmpty = BinCompartment::where('status', 'empty')->count();

        $categoryAvg = function (string $category) {
            $total = BinCompartment::where('category', $category)->where('status', '!=', 'empty')->sum('capacity_percent');
            $count = BinCompartment::where('category', $category)->where('status', '!=', 'empty')->count();
            return $count > 0 ? round($total / $count) : 0;
        };

        $binsData = $bins->map(function ($bin) {
            $maxCapacity = (int) ($bin->compartments->max('capacity_percent') ?? 0);
            $maxStatus = 'ok';
            foreach ($bin->compartments as $comp) {
                if ($comp->status === 'full') {
                    $maxStatus = 'full';
                    break;
                }
                if ($comp->status === 'near') {
                    $maxStatus = 'near';
                }
            }

            return [
                'device_id' => $bin->device_id,
                'location' => $bin->location,
                'camera_ip' => $bin->camera_ip,
                'battery_level' => $bin->battery_level,
                'max_capacity' => $maxCapacity,
                'status' => $maxStatus,
                'last_reported_at' => $bin->last_reported_at?->diffForHumans(),
            ];
        });

        $recentNotifications = BinNotification::with('bin')->latest('occurred_at')->limit(3)->get()
            ->map(fn ($n) => [
                'title' => $n->title,
                'level' => $n->level,
                'occurred_at' => $n->occurred_at?->diffForHumans(),
            ]);

        $unreadNotifications = BinNotification::where('status', 'unread')->count();

        return response()->json([
            'stats' => [
                'total_bins' => $bins->count(),
                'compartments_full' => $compartmentsFull,
                'compartments_near' => $compartmentsNear,
                'compartments_safe' => $compartmentsOk + $compartmentsEmpty,
                'organik_avg' => $categoryAvg('organik'),
                'anorganik_avg' => $categoryAvg('anorganik'),
                'b3_avg' => $categoryAvg('b3'),
            ],
            'bins' => $binsData,
            'recent_notifications' => $recentNotifications,
            'unread_notifications' => $unreadNotifications,
        ]);
    }

    /**
     * Get dashboard data from database
     */
    private function dashboard()
    {
        $bins = Bin::with('compartments')->orderBy('device_id')->get();

        // Stats calculations
        $totalActiveBins = $bins->count();
        
        // Count compartments by status
        $compartmentsFull = BinCompartment::where('status', 'full')->count();
        $compartmentsNear = BinCompartment::where('status', 'near')->count();
        $compartmentsOk = BinCompartment::where('status', 'ok')->count();
        $compartmentsEmpty = BinCompartment::where('status', 'empty')->count();

        // Count by category
        $organikTotal = BinCompartment::where('category', 'organik')
            ->where('status', '!=', 'empty')
            ->sum('capacity_percent');
        $organikCount = BinCompartment::where('category', 'organik')
            ->where('status', '!=', 'empty')
            ->count();
        $organikAvg = $organikCount > 0 ? round($organikTotal / $organikCount) : 0;
        $anorganikTotal = BinCompartment::where('category', 'anorganik')
            ->where('status', '!=', 'empty')
            ->sum('capacity_percent');
        $anorganikCount = BinCompartment::where('category', 'anorganik')
            ->where('status', '!=', 'empty')
            ->count();
        $anorganikAvg = $anorganikCount > 0 ? round($anorganikTotal / $anorganikCount) : 0;

        $b3Total = BinCompartment::where('category', 'b3')
            ->where('status', '!=', 'empty')
            ->sum('capacity_percent');
        $b3Count = BinCompartment::where('category', 'b3')
            ->where('status', '!=', 'empty')
            ->count();
        $b3Avg = $b3Count > 0 ? round($b3Total / $b3Count) : 0;

        // Latest classifications
        $maxItemsOrganik = (int) Setting::valueFor('max_items_organik', 50);
        $maxItemsAnorganik = (int) Setting::valueFor('max_items_anorganik', 50);
        $maxItemsB3 = (int) Setting::valueFor('max_items_b3', 50);

        $latestClassification = Classification::latest('detected_at')->first();

        $recentNotifications = BinNotification::with('bin')->latest('occurred_at')->limit(3)->get();
        $allNotifications = BinNotification::with('bin')->latest('occurred_at')->limit(20)->get();
        $unreadNotifications = BinNotification::where('status', 'unread')->count();

        // All bins for monitoring table
        $binsList = $bins;

        // Trip efficiency
        $todayTrips = Trip::whereDate('trip_date', today())->get();
        $onTimeTrips = $todayTrips->where('on_time', true)->count();
        $totalTripsToday = $todayTrips->count();
        $onTimePercentage = $totalTripsToday > 0 ? round(($onTimeTrips / $totalTripsToday) * 100, 1) : 0;
        $totalWeightToday = $todayTrips->sum('weight_kg');
        $totalTripsCompleted = $todayTrips->where('completed', true)->count();

        // Classification stats
        $totalClassifications = Classification::count();
        $successClassifications = Classification::where('status', 'success')->count();
        $failedClassifications = Classification::where('status', 'failed')->count();
        $classificationAccuracy = $totalClassifications > 0 ? round(($successClassifications / $totalClassifications) * 100, 1) : 0;
        $recentClassifications = Classification::with('compartment.bin')
            ->latest('detected_at')
            ->limit(10)
            ->get();
        $todayClassifications = Classification::whereDate('detected_at', today())->count();

        $categoryCounts = Classification::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $chartCategories = ['organik', 'anorganik', 'b3'];
        $categoryChartLabels = collect($chartCategories)->map(fn ($category) => ucfirst($category))->values();
        $categoryChartData = collect($chartCategories)->map(fn ($category) => (int) ($categoryCounts[$category] ?? 0))->values();

        $dailyChartLabels = collect(range(6, 0))->map(fn ($daysAgo) => now()->subDays($daysAgo)->translatedFormat('D'))->values();
        $dailyChartData = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();

            return Classification::whereDate('detected_at', $date)->count();
        })->values();

        $cameraRtspUrl = Setting::valueFor('camera_rtsp_url', '');

        $locationVolumes = $bins->map(function ($bin) {
            return [
                'label' => $bin->name ?: $bin->device_id,
                'capacity' => (int) ($bin->compartments->max('capacity_percent') ?? 0),
            ];
        })->sortByDesc('capacity')->take(5)->values();

        $activityLogs = collect();
        foreach ($recentClassifications->take(5) as $classification) {
            $bin = $classification->compartment?->bin;
            $activityLogs->push([
                'time' => $classification->detected_at,
                'type' => 'Klasifikasi AI',
                'message' => 'Klasifikasi berhasil: ' . ($classification->detected_label ?? ucfirst($classification->category)) . ' (' . $classification->confidence . '%)',
                'meta' => ($bin?->device_id ?? '-') . ' - Model ' . ($classification->model_version ?? 'v1'),
                'level' => 'info',
            ]);
        }
        foreach ($allNotifications->take(5) as $notification) {
            $activityLogs->push([
                'time' => $notification->occurred_at,
                'type' => 'Notifikasi Sistem',
                'message' => $notification->title,
                'meta' => $notification->bin?->device_id . ' - ' . $notification->message,
                'level' => $notification->level,
            ]);
        }
        $activityLogs = $activityLogs
            ->filter(fn ($item) => $item['time'])
            ->sortByDesc('time')
            ->take(10)
            ->values();

        $users = User::orderBy('name')->get();

        return view('index', [
            'bins' => $bins,
            'totalActiveBins' => $totalActiveBins,
            'compartmentsFull' => $compartmentsFull,
            'compartmentsNear' => $compartmentsNear,
            'compartmentsOk' => $compartmentsOk,
            'compartmentsEmpty' => $compartmentsEmpty,
            'organikAvg' => $organikAvg,
            'anorganikAvg' => $anorganikAvg,
            'b3Avg' => $b3Avg,
            'latestClassification' => $latestClassification,
            'recentNotifications' => $recentNotifications,
            'allNotifications' => $allNotifications,
            'unreadNotifications' => $unreadNotifications,
            'binsList' => $binsList,
            'onTimePercentage' => $onTimePercentage,
            'totalWeightToday' => $totalWeightToday,
            'totalTripsCompleted' => $totalTripsCompleted,
            'totalClassifications' => $totalClassifications,
            'successClassifications' => $successClassifications,
            'failedClassifications' => $failedClassifications,
            'classificationAccuracy' => $classificationAccuracy,
            'recentClassifications' => $recentClassifications,
            'todayClassifications' => $todayClassifications,
            'categoryChartLabels' => $categoryChartLabels,
            'categoryChartData' => $categoryChartData,
            'dailyChartLabels' => $dailyChartLabels,
            'dailyChartData' => $dailyChartData,
            'locationVolumes' => $locationVolumes,
            'activityLogs' => $activityLogs,
            'users' => $users,
            'maxItemsOrganik' => $maxItemsOrganik,
            'maxItemsAnorganik' => $maxItemsAnorganik,
            'maxItemsB3' => $maxItemsB3,
            'cameraRtspUrl' => $cameraRtspUrl,
        ]);
    }
}