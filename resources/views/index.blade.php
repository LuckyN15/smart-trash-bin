<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SMART BIN IoT Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.11.0/dist/tabler-icons.min.css" />
  <link rel="stylesheet" href="{{ asset('/assets/dashboard/style.css') }}" />
</head>
<body>

<div class="app" role="main" aria-label="SMART BIN IoT Dashboard">
  <h2 class="sr-only">SMART BIN IoT — Panel pemantauan tempat sampah cerdas</h2>

  <!-- ==================== SIDEBAR ==================== -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">🗑️</div>
      <div>
        <div class="logo-text">SMART BIN</div>
        <div class="logo-sub">IoT Dashboard</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="section-label">Utama</div>
      <div class="nav-item active" data-page="dashboard">
        <i class="ti ti-layout-dashboard nav-icon" aria-hidden="true"></i>
        <span class="nav-label">Dashboard</span>
      </div>
      <div class="nav-item" data-page="realtime">
        <i class="ti ti-scan nav-icon" aria-hidden="true"></i>
        <span class="nav-label">Klasifikasi Real-Time</span>
      </div>
      <div class="nav-item" data-page="monitoring">
        <i class="ti ti-eye nav-icon" aria-hidden="true"></i>
        <span class="nav-label">Monitoring Sampah</span>
      </div>

      <div class="section-label">Analitik</div>
      <div class="nav-item" data-page="statistik">
        <i class="ti ti-chart-bar nav-icon" aria-hidden="true"></i>
        <span class="nav-label">Statistik</span>
      </div>
      <div class="nav-item" data-page="riwayat">
        <i class="ti ti-history nav-icon" aria-hidden="true"></i>
        <span class="nav-label">Riwayat Aktivitas</span>
      </div>

      <div class="section-label">Sistem</div>
      <div class="nav-item" data-page="notifikasi" id="notif-nav">
        <i class="ti ti-bell nav-icon" aria-hidden="true"></i>
        <span class="nav-label">Notifikasi</span>
        <span class="nav-badge" id="notif-count" style="{{ $unreadNotifications === 0 ? 'display:none' : '' }}">{{ $unreadNotifications }}</span>
      </div>
      <div class="nav-item" data-page="pengaturan">
        <i class="ti ti-settings nav-icon" aria-hidden="true"></i>
        <span class="nav-label">Pengaturan</span>
      </div>
      <div class="nav-item" data-page="tentang">
        <i class="ti ti-info-circle nav-icon" aria-hidden="true"></i>
        <span class="nav-label">Tentang Sistem</span>
      </div>
    </nav>

<div class="sidebar-footer">
  <details class="user-card-toggle">
    <summary class="user-card">
      <div class="user-avatar">{{ Str::upper(Str::substr(auth()->user()->name, 0, 2)) }}</div>
      <div>
        <div class="user-name">{{ auth()->user()->name }}</div>
        <div class="user-role">{{ auth()->user()->role ?? 'Pengguna' }}</div>
      </div>
    </summary>

    <div class="user-dropdown">
      <a href="{{ route('profile.edit') }}" class="dropdown-item">
        <i class="ti ti-user-circle" aria-hidden="true"></i> Profil
      </a>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="dropdown-item dropdown-logout">
          <i class="ti ti-logout" aria-hidden="true"></i> Logout
        </button>
      </form>
    </div>
  </details>
</div>
  </aside>

  <!-- ==================== MAIN ==================== -->
  <div class="main">

    <!-- TOP BAR -->
    <div class="topbar">
      <div>
        <div class="page-title" id="page-title">Dashboard</div>
        <div class="page-sub" id="page-sub">Ringkasan kondisi seluruh smart bin</div>
      </div>
      <div class="topbar-actions">
        <button class="tb-btn" onclick="simulateRefresh()">
          <i class="ti ti-refresh" aria-hidden="true"></i> Refresh
        </button>
        @if(auth()->user()->role === 'admin')
          <form method="POST" action="{{ route('dashboard.reset') }}" style="display:inline">
            @csrf
            <button type="submit" class="tb-btn" style="background:#c92a2a;color:#fff;margin-left:8px">
              <i class="ti ti-refresh-alert" aria-hidden="true"></i> Reset Semua
            </button>
          </form>
        @endif
        <button class="tb-btn" id="time-btn"><i class="ti ti-clock" aria-hidden="true"></i> {{ now()->translatedFormat('d M Y H:i') }}</button>
        <div style="width:8px;height:8px;border-radius:50%;background:var(--sb-success);box-shadow:0 0 6px var(--sb-success)" title="Sistem online"></div>
      </div>
    </div>

    <!-- CONTENT -->
    <div class="content">

      <!-- ===================== PAGE: DASHBOARD ===================== -->
      <div class="page active" id="page-dashboard">

 <!-- Sampah Masuk Terkini -->
<div class="chart-card" style="margin-top:20px;">
    <div class="chart-header">
        <span class="chart-title">
            <i class="ti ti-recycle"></i>
            Sampah Masuk Terkini
        </span>
        <span class="badge info">Live</span>
    </div>

    <div style="padding:15px;">
        <div style="
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:12px;
            background:var(--sb-surface2);
            border-radius:10px;
            margin-bottom:10px;
        ">
            <div>
                @if($latestClassification)
                    <div style="font-size:18px;font-weight:600;" id="latest-trash">
                        {{ ucfirst($latestClassification->detected_label) }}
                    </div>

                    <div style="
                        font-size:13px;
                        color:var(--sb-accent);
                        margin-top:4px;
                    ">
                        Kategori: {{ ucfirst($latestClassification->category) }}
                    </div>

                    <div style="
                        font-size:12px;
                        color:var(--sb-muted);
                        margin-top:4px;
                    " id="latest-time">
                        Terdeteksi {{ $latestClassification->detected_at->diffForHumans() }}
                    </div>

                    <div style="
                        font-size:12px;
                        color:var(--sb-muted);
                        margin-top:2px;
                    ">
                        Confidence: {{ $latestClassification->confidence }}%
                    </div>
                @else
                    <div style="font-size:18px;font-weight:600;">
                        N/A
                    </div>

                    <div style="font-size:12px;color:var(--sb-muted);">
                        Belum ada data
                    </div>
                @endif
            </div>

            <div style="
                font-size:35px;
                color:var(--sb-accent);
            ">
                ♻️
            </div>
        </div>

        <div style="font-size:12px;color:var(--sb-muted);">
            Menampilkan jenis sampah terakhir yang berhasil diklasifikasikan oleh sistem Smart Bin.
        </div>
    </div>
</div>

        <!-- Stat cards -->
        <div class="stat-grid">
          <div class="stat-card">
            <div class="stat-label"><i class="ti ti-trash" aria-hidden="true"></i> Total Smart Bin</div>
            <div class="stat-value" id="stat-total-bins" style="color:var(--sb-accent)">{{ $totalActiveBins }}</div>
            <div class="stat-change up">Terdaftar di database</div>
          </div>
          <div class="stat-card">
            <div class="stat-label"><i class="ti ti-alert-circle" style="color:var(--sb-danger)" aria-hidden="true"></i> Sampah Penuh</div>
            <div class="stat-value" id="stat-full" style="color:var(--sb-danger)">{{ $compartmentsFull }}</div>
            <div class="stat-change down">Perlu segera diangkut</div>
          </div>
          <div class="stat-card">
            <div class="stat-label"><i class="ti ti-alert-triangle" style="color:var(--sb-warn)" aria-hidden="true"></i> Hampir Penuh</div>
            <div class="stat-value" id="stat-near" style="color:var(--sb-warn)">{{ $compartmentsNear }}</div>
            <div class="stat-change" style="color:var(--sb-warn)">Pantau dalam 2 jam</div>
          </div>
          <div class="stat-card">
            <div class="stat-label"><i class="ti ti-check" style="color:var(--sb-success)" aria-hidden="true"></i> Sampah Kosong / Aman</div>
            <div class="stat-value" id="stat-safe" style="color:var(--sb-success)">{{ $compartmentsOk + $compartmentsEmpty }}</div>
            <div class="stat-change up">Kapasitas tersedia</div>
          </div>
        </div>

             <!-- Stat cards -->
  <!-- Total Sampah per Jenis -->
<div class="stat-grid" style="grid-template-columns:repeat(3,1fr)">
  <div class="stat-card" style="border-left:3px solid var(--sb-organik)">
    <div class="stat-label"><i class="ti ti-leaf" style="color:var(--sb-organik)" aria-hidden="true"></i> Total Sampah Organik</div>
    <div class="stat-value" id="stat-organik-avg" style="color:var(--sb-organik)">{{ $organikAvg }}%</div>
    <div class="stat-change" style="color:var(--sb-organik)">Sisa makanan & bahan alami</div>
  </div>
  <div class="stat-card" style="border-left:3px solid var(--sb-anorganik)">
    <div class="stat-label"><i class="ti ti-recycle" style="color:var(--sb-anorganik)" aria-hidden="true"></i> Total Sampah Anorganik</div>
    <div class="stat-value" id="stat-anorganik-avg" style="color:var(--sb-anorganik)">{{ $anorganikAvg }}%</div>
    <div class="stat-change" style="color:var(--sb-anorganik)">Plastik, kertas, logam, kaca</div>
  </div>
  <div class="stat-card" style="border-left:3px solid var(--sb-b3)">
    <div class="stat-label"><i class="ti ti-alert-triangle" style="color:var(--sb-b3)" aria-hidden="true"></i> Total Sampah B3</div>
    <div class="stat-value" id="stat-b3-avg" style="color:var(--sb-b3)">{{ $b3Avg }}%</div>
    <div class="stat-change" style="color:var(--sb-b3)">Perlu penanganan khusus</div>
  </div>
</div>
        <!-- Chart row 1 -->
        <div class="chart-row">
          <div class="chart-card">
            <div class="chart-header">
              <span class="chart-title">Volume Sampah</span>
              <div class="chart-tabs">
                <span class="chart-tab active" onclick="switchTab(this,'vol')">Harian</span>
                <span class="chart-tab" onclick="switchTab(this,'vol')">Mingguan</span>
                <span class="chart-tab" onclick="switchTab(this,'vol')">Bulanan</span>
              </div>
            </div>
            <div style="position:relative;height:110px">
              <canvas id="volChart" role="img" aria-label="Grafik volume sampah harian">Volume harian meningkat.</canvas>
            </div>
          </div>
          <div class="chart-card">
            <div class="chart-header">
              <span class="chart-title">Kategori Sampah</span>
              <span class="badge info">Hari ini</span>
            </div>
            <div style="position:relative;height:110px">
              <canvas id="catChart" role="img" aria-label="Distribusi kategori sampah">Plastik terbanyak.</canvas>
              @php
                $categoryTotal = collect($categoryChartData)->sum();
              @endphp
              @if($categoryTotal === 0)
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:var(--sb-muted);font-size:12px;pointer-events:none">
                  Tidak ada data kategori sampah hari ini
                </div>
              @endif
            </div>
          </div>
        </div>

        <!-- Status Koneksi -->
        <div class="chart-row">
          <div class="chart-card">
            <div class="chart-header"><span class="chart-title">Status Koneksi IoT</span></div>
            <div style="display:flex;flex-direction:column;gap:6px">
           @foreach($binsList as $bin)
    @php
        $online = $bin->last_reported_at &&
            \Carbon\Carbon::parse($bin->last_reported_at)
                ->gt(now()->subMinutes(2));
    @endphp

    <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--sb-border)">
        <span style="font-size:12px">
            {{ $bin->device_id }}
        </span>

        <div style="display:flex;align-items:center;gap:6px">
            <div class="prog-bar" style="width:80px">
                <div
                    class="prog-fill {{ $online ? 'ok' : 'danger' }}"
                    style="width:100%">
                </div>
            </div>

            <span
                style="
                    font-size:12px;
                    color:{{ $online ? 'var(--sb-success)' : 'var(--sb-danger)' }};
                    font-weight:500">
                {{ $online ? 'Online' : 'Offline' }}
            </span>
        </div>
    </div>
@endforeach
             @php
    $totalBins = $binsList->count();

    $onlineCount = $binsList->filter(function ($bin) {
        return $bin->last_reported_at &&
            \Carbon\Carbon::parse($bin->last_reported_at)
                ->gt(now()->subMinutes(2));
    })->count();

    $offlineCount = $totalBins - $onlineCount;

    $onlinePercent = $totalBins > 0
        ? ($onlineCount / $totalBins) * 100
        : 0;

    $offlinePercent = $totalBins > 0
        ? ($offlineCount / $totalBins) * 100
        : 0;
@endphp


<!-- ONLINE -->
<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--sb-border)">
    <span style="font-size:12px">Online</span>

    <div style="display:flex;align-items:center;gap:6px">
        <div class="prog-bar" style="width:80px">
            <div class="prog-fill ok" style="width:{{ $onlinePercent }}%"></div>
        </div>

        <span style="font-size:12px;color:var(--sb-success);font-weight:500">
            {{ $onlineCount }}
        </span>
    </div>
</div>


<!-- OFFLINE -->
<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--sb-border)">
    <span style="font-size:12px">Offline</span>

    <div style="display:flex;align-items:center;gap:6px">
        <div class="prog-bar" style="width:80px">
            <div class="prog-fill full" style="width:{{ $offlinePercent }}%"></div>
        </div>

        <span style="font-size:12px;color:var(--sb-danger);font-weight:500">
            {{ $offlineCount }}
        </span>
    </div>
</div>
              <div style="padding-top:6px">
                <div style="font-size:11px;color:var(--sb-muted);margin-bottom:6px">Notifikasi terbaru</div>
                <div id="recent-notifications-list">
                @forelse($recentNotifications as $notif)
                  @php
                    $isWarning = in_array($notif->level, ['warn', 'warning']);
                    $border = $notif->level === 'danger' ? 'var(--sb-danger)' : ($isWarning ? 'var(--sb-warn)' : 'var(--sb-info)');
                    $bg = $notif->level === 'danger' ? 'rgba(248,81,73,0.08)' : ($isWarning ? 'rgba(227,179,65,0.08)' : 'rgba(88,166,255,0.08)');
                  @endphp
                  <div style="font-size:12px;padding:5px 8px;background:{{ $bg }};border-radius:5px;margin-bottom:4px;border-left:2px solid {{ $border }}">{{ $notif->title }} <span style="color:var(--sb-muted)">{{ $notif->occurred_at?->diffForHumans() }}</span></div>
                @empty
                  <div style="font-size:12px;color:var(--sb-muted)">Belum ada notifikasi</div>
                @endforelse
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Efisiensi Pengangkutan -->
        <div class="full-chart">
          <div class="chart-header">
            <span class="chart-title">Efisiensi Pengangkutan</span>
            <span style="font-size:11px;color:var(--sb-muted)">Bulan ini</span>
          </div>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
            <div style="text-align:center;padding:10px;background:var(--sb-surface2);border-radius:6px">
              <div style="font-size:20px;font-weight:600;color:var(--sb-accent)">{{ $onTimePercentage }}%</div>
              <div style="font-size:11px;color:var(--sb-muted);margin-top:3px">Tepat waktu</div>
            </div>
            <div style="text-align:center;padding:10px;background:var(--sb-surface2);border-radius:6px">
              <div style="font-size:20px;font-weight:600;color:var(--sb-info)">{{ number_format((float) $totalWeightToday, 1) }} kg</div>
              <div style="font-size:11px;color:var(--sb-muted);margin-top:3px">Total diangkut</div>
            </div>
            <div style="text-align:center;padding:10px;background:var(--sb-surface2);border-radius:6px">
              <div style="font-size:20px;font-weight:600;color:var(--sb-success)">{{ $totalTripsCompleted }}</div>
              <div style="font-size:11px;color:var(--sb-muted);margin-top:3px">Trip selesai</div>
            </div>
          </div>
        </div>

      </div><!-- end page-dashboard -->

      <!-- ===================== PAGE: REALTIME ===================== -->
      <div class="page" id="page-realtime">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
          <div class="chart-card">
            <div class="chart-header">
              <span class="chart-title">Deteksi Langsung</span>
              <span class="badge ok"><span class="dot"></span> Live</span>
            </div>
            <div style="background:var(--sb-surface2);border-radius:6px;height:100px;display:flex;align-items:center;justify-content:center;margin-bottom:10px;position:relative;overflow:hidden">
              <div id="scan-box" style="width:60px;height:60px;border:2px solid var(--sb-accent);border-radius:4px;position:absolute;animation:scanBox 2s infinite"></div>
              <div style="font-size:11px;color:var(--sb-muted);position:absolute;bottom:6px">Kamera {{ $latestClassification?->compartment?->bin?->device_id ?? 'BIN-03' }} - Aktif</div>
              <div style="font-size:32px" id="detected-emoji"><i class="ti ti-scan" aria-hidden="true"></i></div>
            </div>
            <div style="text-align:center; margin-top:12px">
              <video id="tapo-camera" width="100%" style="border-radius:10px;max-height:240px;object-fit:cover;display:none" autoplay muted playsinline></video>
              <div id="tapo-error" style="font-size:12px;color:var(--sb-muted);margin-top:6px">Kamera live belum terhubung.</div>
            </div>
            <div style="text-align:center">
              <div style="font-size:14px;font-weight:600;color:var(--sb-accent)" id="detected-class">{{ $latestClassification?->detected_label ?? 'Belum ada deteksi' }}</div>
              <div style="font-size:11px;color:var(--sb-muted);margin-top:2px">{{ $latestClassification?->detected_at?->diffForHumans() ?? '-' }}</div>
            </div>
          </div>
          <div class="chart-card">
            <div class="chart-header"><span class="chart-title">Confidence Score AI</span></div>
            <div class="rt-feed" style="padding:0;background:transparent;border:none">
              @forelse($recentClassifications->take(5) as $classification)
                @php
                  $scoreColor = $classification->category === 'organik' ? '#3fb950' : ($classification->category === 'anorganik' ? '#2196f3' : '#f85149');
                @endphp
                <div class="rt-item"><span class="rt-cat">{{ $classification->detected_label ?? ucfirst($classification->category) }}</span><div class="rt-bar"><div class="rt-bar-fill" style="width:{{ $classification->confidence }}%;background:{{ $scoreColor }}"></div></div><span class="rt-score">{{ $classification->confidence }}%</span></div>
              @empty
                <div style="font-size:12px;color:var(--sb-muted);padding:8px">Belum ada confidence score</div>
              @endforelse
            </div>
            <div style="margin-top:8px;padding:6px 10px;border-radius:6px;background:rgba(63,185,80,0.1);border:1px solid rgba(63,185,80,0.3);font-size:12px;color:var(--sb-success);display:flex;align-items:center;gap:5px">
              <i class="ti ti-check" aria-hidden="true"></i> Klasifikasi berhasil
            </div>
          </div>
        </div>

        <div class="search-bar">
          <input class="search-input" placeholder="🔍  Cari histori klasifikasi...">
          <select class="filter-sel">
            <option>Semua Kategori</option>
            <option>Plastik</option><option>Organik</option><option>Kertas</option>
            <option>Logam</option><option>Kaca</option><option>Anorganik</option>
          </select>
          <select class="filter-sel">
            <option>Hari ini</option><option>Minggu ini</option><option>Bulan ini</option>
          </select>
        </div>

        <div class="chart-card">
          <div class="chart-header">
            <span class="chart-title">Histori Klasifikasi</span>
            <span style="font-size:11px;color:var(--sb-muted)">{{ $todayClassifications }} entri hari ini</span>
          </div>
          <table class="data-table">
            <thead>
              <tr><th>Waktu</th><th>Perangkat</th><th>IP Kamera</th><th>Kategori</th><th>Confidence</th><th>Status</th></tr>
            </thead>
            <tbody>
              @forelse($recentClassifications as $classification)
                <tr>
                  <td style="color:var(--sb-muted)">{{ $classification->detected_at?->format('H:i:s') ?? '-' }}</td>
                  <td>{{ $classification->compartment?->bin?->device_id ?? '-' }}</td>
                  <td>{{ $classification->compartment?->bin?->camera_ip ?? '-' }}</td>
                  <td><span class="badge info">{{ ucfirst($classification->category) }}</span></td>
                  <td>{{ $classification->confidence }}%</td>
                  <td><span class="badge {{ $classification->status === 'success' ? 'ok' : 'full' }}">{{ $classification->status === 'success' ? 'Berhasil' : 'Gagal' }}</span></td>
                </tr>
              @empty
                <tr><td colspan="5" style="text-align:center;color:var(--sb-muted);padding:16px">Belum ada histori klasifikasi</td></tr>
              @endforelse</tbody>
          </table>
        </div>
      </div><!-- end page-realtime -->

      <!-- ===================== PAGE: MONITORING ===================== -->
      <div class="page" id="page-monitoring">
        <div class="search-bar">
          <input class="search-input" placeholder="🔍  Cari ID atau lokasi sampah...">
          <select class="filter-sel"><option>Semua Status</option><option>Penuh</option><option>Hampir Penuh</option><option>Kosong/Aman</option></select>
          <select class="filter-sel"><option>Semua Area</option><option>Pusat Kota</option><option>Pasar</option><option>Taman</option></select>
          <button class="tb-btn" style="background:var(--sb-surface2)"><i class="ti ti-map" aria-hidden="true"></i> Peta</button>
        </div>

        <div class="chart-card" style="padding:0">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID Perangkat</th><th>Lokasi</th><th>IP Kamera</th><th>Kapasitas</th>
                <th>Baterai</th><th>Terakhir Update</th><th>Status</th>
              </tr>
            </thead>
            <tbody id="bin-table-body">
              @forelse($binsList as $bin)
                <tr>
                  <td style="font-weight:500">{{ $bin->device_id }}</td>
                  <td>{{ $bin->location }}</td>
                  <td>{{ $bin->camera_ip ?? '-' }}</td>
                  <td>
                    @php
                      $maxCapacity = $bin->compartments->max('capacity_percent') ?? 0;
                      $statusColor = $maxCapacity >= 90 ? 'full' : ($maxCapacity >= 70 ? 'near' : 'ok');
                    @endphp
                    <div style="display:flex;align-items:center;gap:8px">
                      <div class="prog-bar" style="width:60px">
                        <div class="prog-fill {{ $statusColor }}" style="width:{{ $maxCapacity }}%"></div>
                      </div>
                      <span style="color:var(--sb-{{ $statusColor == 'full' ? 'danger' : ($statusColor == 'near' ? 'warn' : 'success') }});font-weight:500">{{ $maxCapacity }}%</span>
                    </div>
                  </td>
                  <td>
                    @if($bin->battery_level > 50)
                      <span style="color:var(--sb-success)">🔋 {{ $bin->battery_level }}%</span>
                    @elseif($bin->battery_level > 20)
                      <span style="color:var(--sb-warn)">🔋 {{ $bin->battery_level }}%</span>
                    @else
                      <span style="color:var(--sb-danger)">🔋 {{ $bin->battery_level }}%</span>
                    @endif
                  </td>
                  <td style="color:var(--sb-muted)">{{ $bin->last_reported_at ? $bin->last_reported_at->diffForHumans() : '-' }}</td>
                  <td>
                    @php
                      $maxStatus = 'ok';
                      foreach($bin->compartments as $comp) {
                        if($comp->status == 'full') { $maxStatus = 'full'; break; }
                        elseif($comp->status == 'near') { $maxStatus = 'near'; }
                      }
                    @endphp
                    @if($maxStatus == 'full')
                      <span class="badge full"><span class="dot"></span> Penuh</span>
                    @elseif($maxStatus == 'near')
                      <span class="badge near"><span class="dot"></span> Hampir Penuh</span>
                    @else
                      <span class="badge ok"><span class="dot"></span> Kosong</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" style="text-align:center;color:var(--sb-muted);padding:20px">Belum ada data perangkat</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div><!-- end page-monitoring -->

      <!-- ===================== PAGE: STATISTIK ===================== -->
      <div class="page" id="page-statistik">
        <div style="display:flex;gap:8px;margin-bottom:12px;align-items:center">
          <select class="filter-sel"><option>Semua Lokasi</option><option>Pusat Kota</option><option>Pasar</option></select>
          <input type="date" style="padding:7px 10px;background:var(--sb-surface);border:1px solid var(--sb-border);border-radius:6px;color:var(--sb-text);font-size:12px">
          <span style="color:var(--sb-muted)">—</span>
          <input type="date" style="padding:7px 10px;background:var(--sb-surface);border:1px solid var(--sb-border);border-radius:6px;color:var(--sb-text);font-size:12px">
          <div style="flex:1"></div>
          <button class="tb-btn" onclick="exportReport('PDF')"><i class="ti ti-file-text" aria-hidden="true"></i> Ekspor PDF</button>
          <button class="tb-btn" onclick="exportReport('Excel')"><i class="ti ti-table" aria-hidden="true"></i> Ekspor Excel</button>
        </div>

        <div class="chart-row">
          <div class="chart-card">
            <div class="chart-header">
              <span class="chart-title">Volume per Kategori</span>
              <div class="chart-tabs">
                <span class="chart-tab active">Minggu ini</span>
                <span class="chart-tab">Bulan</span>
              </div>
            </div>
            <div style="position:relative;height:120px">
              <canvas id="statCatChart" role="img" aria-label="Volume sampah per kategori">Plastik mendominasi.</canvas>
            </div>
          </div>
          <div class="chart-card">
            <div class="chart-header"><span class="chart-title">Tren Volume Harian</span></div>
            <div style="position:relative;height:120px">
              <canvas id="trendChart" role="img" aria-label="Tren volume sampah harian 7 hari">Meningkat di akhir pekan.</canvas>
            </div>
          </div>
        </div>

        <div class="chart-row">
          <div class="chart-card">
            <div class="chart-header"><span class="chart-title">Akurasi Klasifikasi AI</span></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
              <div style="text-align:center;padding:12px;background:var(--sb-surface2);border-radius:6px">
                <div style="font-size:22px;font-weight:600;color:var(--sb-success)">{{ $classificationAccuracy }}%</div>
                <div style="font-size:10px;color:var(--sb-muted)">Akurasi keseluruhan</div>
              </div>
              <div style="text-align:center;padding:12px;background:var(--sb-surface2);border-radius:6px">
                <div style="font-size:22px;font-weight:600;color:var(--sb-info)">{{ $totalClassifications }}</div>
                <div style="font-size:10px;color:var(--sb-muted)">Total klasifikasi</div>
              </div>
              <div style="text-align:center;padding:12px;background:var(--sb-surface2);border-radius:6px">
                <div style="font-size:22px;font-weight:600;color:var(--sb-success)">{{ $successClassifications }}</div>
                <div style="font-size:10px;color:var(--sb-muted)">Berhasil</div>
              </div>
              <div style="text-align:center;padding:12px;background:var(--sb-surface2);border-radius:6px">
                <div style="font-size:22px;font-weight:600;color:var(--sb-danger)">{{ $failedClassifications }}</div>
                <div style="font-size:10px;color:var(--sb-muted)">Gagal</div>
              </div>
            </div>
          </div>
          <div class="chart-card">
            <div class="chart-header"><span class="chart-title">Volume per Lokasi</span></div>
            <div style="display:flex;flex-direction:column;gap:6px">
              @forelse($locationVolumes as $location)
                @php
                  $barClass = $location['capacity'] >= 90 ? 'full' : ($location['capacity'] >= 70 ? 'near' : 'ok');
                @endphp
                <div><div style="display:flex;justify-content:space-between;margin-bottom:3px;font-size:12px"><span>{{ $location['label'] }}</span><span style="color:var(--sb-muted)">{{ $location['capacity'] }}%</span></div><div class="prog-bar"><div class="prog-fill {{ $barClass }}" style="width:{{ $location['capacity'] }}%"></div></div></div>
              @empty
                <div style="font-size:12px;color:var(--sb-muted)">Belum ada data lokasi</div>
              @endforelse
            </div>
          </div>
        </div>
      </div><!-- end page-statistik -->

      <!-- ===================== PAGE: RIWAYAT ===================== -->
      <div class="page" id="page-riwayat">
        <div class="search-bar">
          <input class="search-input" placeholder="🔍  Cari aktivitas...">
          <select class="filter-sel"><option>Semua Tipe</option><option>Login/Logout</option><option>Perangkat IoT</option><option>Klasifikasi</option><option>Pengaturan</option></select>
          <select class="filter-sel"><option>Hari ini</option><option>Minggu ini</option><option>Bulan ini</option></select>
          <button class="tb-btn" onclick="exportReport('CSV')"><i class="ti ti-download" aria-hidden="true"></i> Ekspor</button>
        </div>

        <div class="chart-card">
          <div style="font-size:11px;color:var(--sb-muted);margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid var(--sb-border)">{{ $activityLogs->count() }} entri terbaru dari database</div>
          <div id="activity-log">
            @forelse($activityLogs as $log)
              @php
                $logBg = $log['level'] === 'danger' ? 'rgba(248,81,73,0.15)' : (in_array($log['level'], ['warn', 'warning']) ? 'rgba(227,179,65,0.15)' : 'rgba(88,166,255,0.15)');
                $logIcon = $log['level'] === 'danger' ? '!' : (in_array($log['level'], ['warn', 'warning']) ? '?' : 'i');
              @endphp
              <div class="log-item"><div class="log-icon-wrap" style="background:{{ $logBg }}">{{ $logIcon }}</div><div class="log-body"><div class="log-msg">{{ $log['message'] }}</div><div class="log-meta">{{ $log['time']->format('H:i:s') }} - {{ $log['type'] }} - {{ $log['meta'] }}</div></div></div>
            @empty
              <div style="text-align:center;padding:20px;color:var(--sb-muted)">Belum ada aktivitas</div>
            @endforelse
          </div>        </div>
      </div><!-- end page-riwayat -->

      <!-- ===================== PAGE: NOTIFIKASI ===================== -->
      <div class="page" id="page-notifikasi">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
          <div style="display:flex;gap:8px">
            <button class="tb-btn" onclick="markAllRead()"><i class="ti ti-check" aria-hidden="true"></i> Tandai semua dibaca</button>
            <select class="filter-sel"><option>Semua Jenis</option><option>Bin Penuh</option><option>Perangkat Offline</option><option>Baterai Lemah</option><option>Gagal Klasifikasi</option></select>
          </div>
          <span id="unread-label" style="font-size:12px;color:var(--sb-muted)">{{ $unreadNotifications }} belum dibaca</span>
        </div>

        <div id="notif-list">
          @forelse($allNotifications as $index => $notif)
            <div class="notif-item {{ $notif->status == 'unread' ? 'unread' : '' }}" id="notif-{{ $index + 1 }}" style="{{ $notif->status == 'read' ? 'opacity:0.6' : '' }}">
              @php
                $iconStyle = '';
                $icon = '🔔';
                if($notif->level == 'danger') {
                  $iconStyle = 'background:rgba(248,81,73,0.15)';
                  $icon = '🗑️';
                } elseif(in_array($notif->level, ['warn', 'warning'])) {
                  $iconStyle = 'background:rgba(227,179,65,0.15)';
                  $icon = '⚠️';
                } elseif($notif->level == 'success') {
                  $iconStyle = 'background:rgba(63,185,80,0.15)';
                  $icon = '✅';
                } else {
                  $iconStyle = 'background:rgba(139,148,158,0.15)';
                  $icon = '📡';
                }
              @endphp
              <div class="notif-icon" style="{{ $iconStyle }}">{{ $icon }}</div>
              <div class="notif-body">
                <div class="notif-title">{{ $notif->title }}</div>
                <div class="notif-desc">{{ $notif->message }}</div>
                <div class="notif-time">{{ $notif->occurred_at->diffForHumans() }}</div>
                @if($notif->status == 'unread')
                  <div class="notif-actions">
                    <button class="notif-btn" onclick="markRead('notif-{{ $index + 1 }}')">Tandai dibaca</button>
                    <button class="notif-btn" onclick="deleteNotif('notif-{{ $index + 1 }}')">Hapus</button>
                  </div>
                @else
                  <div class="notif-actions">
                    <button class="notif-btn" onclick="deleteNotif('notif-{{ $index + 1 }}')">Hapus</button>
                  </div>
                @endif
              </div>
            </div>
          @empty
            <div style="text-align:center;padding:20px;color:var(--sb-muted)">
              Tidak ada notifikasi
            </div>
          @endforelse
        </div>
      </div><!-- end page-notifikasi -->

      <!-- ===================== PAGE: PENGATURAN ===================== -->
      <div class="page" id="page-pengaturan">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <!-- Kolom kiri -->
          <div>
            <div class="settings-section">
              <div class="settings-title"><i class="ti ti-user-circle" aria-hidden="true"></i> Profil Pengguna</div>
              <div style="display:flex;flex-direction:column;gap:8px">
                <div><label style="font-size:11px;color:var(--sb-muted)">Nama Lengkap</label><input type="text" value="{{ auth()->user()->name }}" style="margin-top:3px"></div>
                <div><label style="font-size:11px;color:var(--sb-muted)">Email</label><input type="email" value="{{ auth()->user()->email }}" style="margin-top:3px"></div>
                <div><label style="font-size:11px;color:var(--sb-muted)">Password baru</label><input type="password" placeholder="••••••••" style="margin-top:3px"></div>
                <div style="display:flex;gap:6px;margin-top:4px">
                  <button class="btn-primary">Simpan perubahan</button>
                  <button class="btn-secondary">Batal</button>
                </div>
              </div>
            </div>

            <div class="settings-section">
              <div class="settings-title"><i class="ti ti-bell" aria-hidden="true"></i> Pengaturan Notifikasi</div>
              <div class="settings-row"><div><div class="settings-label">Sampah penuh</div><div class="settings-hint">Alert saat kapasitas > 80%</div></div><div class="toggle on" onclick="this.classList.toggle('on')" role="switch" aria-label="Toggle notifikasi bin penuh"></div></div>
              <div class="settings-row"><div><div class="settings-label">Perangkat offline</div><div class="settings-hint">Alert jika koneksi putus > 5 menit</div></div><div class="toggle on" onclick="this.classList.toggle('on')" role="switch" aria-label="Toggle notifikasi perangkat offline"></div></div>
              <div class="settings-row"><div><div class="settings-label">Baterai lemah</div><div class="settings-hint">Alert saat baterai < 20%</div></div><div class="toggle on" onclick="this.classList.toggle('on')" role="switch" aria-label="Toggle notifikasi baterai"></div></div>
              <div class="settings-row"><div><div class="settings-label">Gagal klasifikasi</div><div class="settings-hint">Alert jika confidence < 50%</div></div><div class="toggle" onclick="this.classList.toggle('on')" role="switch" aria-label="Toggle notifikasi gagal klasifikasi"></div></div>
            </div>

            <div class="settings-section">
              <div class="settings-title"><i class="ti ti-adjustments" aria-hidden="true"></i> Batas Maksimal Sampah per Jenis</div>
              <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                  <div>
                    <label style="font-size:11px;color:var(--sb-muted)">Organik</label>
                    <input type="number" name="max_items_organik" min="1" max="500" value="{{ $maxItemsOrganik ?? 50 }}" style="margin-top:3px;width:100%;padding:8px;border:1px solid var(--sb-border);border-radius:6px;background:var(--sb-surface);color:var(--sb-text)">
                  </div>
                  <div>
                    <label style="font-size:11px;color:var(--sb-muted)">Anorganik</label>
                    <input type="number" name="max_items_anorganik" min="1" max="500" value="{{ $maxItemsAnorganik ?? 50 }}" style="margin-top:3px;width:100%;padding:8px;border:1px solid var(--sb-border);border-radius:6px;background:var(--sb-surface);color:var(--sb-text)">
                  </div>
                  <div>
                    <label style="font-size:11px;color:var(--sb-muted)">B3</label>
                    <input type="number" name="max_items_b3" min="1" max="500" value="{{ $maxItemsB3 ?? 50 }}" style="margin-top:3px;width:100%;padding:8px;border:1px solid var(--sb-border);border-radius:6px;background:var(--sb-surface);color:var(--sb-text)">
                  </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr;gap:10px;margin-top:12px">
                  <div>
                    <label style="font-size:11px;color:var(--sb-muted)">URL RTSP Kamera Tapo</label>
                    <input type="text" name="camera_rtsp_url" value="{{ $cameraRtspUrl ?? '' }}" placeholder="rtsp://user:pass@192.168.1.100:554/stream1" style="margin-top:3px;width:100%;padding:8px;border:1px solid var(--sb-border);border-radius:6px;background:var(--sb-surface);color:var(--sb-text)">
                  </div>
                  <div style="display:flex;gap:8px;align-items:center">
                    <button type="submit" class="btn-primary">Simpan Batas & Kamera</button>
                    <span style="font-size:11px;color:var(--sb-muted)">URL RTSP diperlukan untuk live view TP-Link Tapo.</span>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Kolom kanan -->
          <div>
            <div class="settings-section">
              <div class="settings-title"><i class="ti ti-users" aria-hidden="true"></i> Manajemen Pengguna</div>
              <table class="data-table" style="font-size:11.5px">
                <thead><tr><th>Nama</th><th>Role</th><th>Status</th></tr></thead>
                <tbody>
                  @forelse($users as $user)
                    <tr><td>{{ $user->name }}</td><td><span class="badge info">{{ ucfirst($user->role ?? 'user') }}</span></td><td><span class="badge ok">Aktif</span></td></tr>
                  @empty
                    <tr><td colspan="3" style="text-align:center;color:var(--sb-muted)">Belum ada user</td></tr>
                  @endforelse</tbody>
              </table>
              <button class="tb-btn" style="margin-top:8px;width:100%;justify-content:center"><i class="ti ti-plus" aria-hidden="true"></i> Tambah pengguna</button>
            </div>

            {{-- <div class="settings-section"> --}}
              {{-- <div class="settings-title"><i class="ti ti-adjustments" aria-hidden="true"></i> Ambang Batas &amp; Integrasi</div> --}}
              {{-- <div class="settings-row"><div><div class="settings-label">Batas kapasitas warning</div></div><input type="range" min="50" max="95" value="80" style="width:80px" oninput="document.getElementById('cap-val').textContent=this.value+'%'"><span id="cap-val" style="font-size:12px;min-width:30px">80%</span></div> --}}
              {{-- <div class="settings-row"><div><div class="settings-label">MQTT Broker</div></div><span style="font-size:11px;color:var(--sb-success)">● Terhubung</span></div> --}}
              {{-- <div class="settings-row"><div><div class="settings-label">Tema antarmuka</div></div> --}}
                {{-- <select class="filter-sel" style="font-size:11px"><option>Dark (Aktif)</option><option>Light</option><option>Auto</option></select> --}}
              {{-- </div> --}}
              {{-- <div class="settings-row"><div><div class="settings-label">Bahasa</div></div> --}}
                {{-- <select class="filter-sel" style="font-size:11px"><option>Bahasa Indonesia</option><option>English</option></select> --}}
              {{-- </div> --}}
            {{-- </div> --}}

            <div class="settings-section">
              <div class="settings-title"><i class="ti ti-database" aria-hidden="true"></i> Backup &amp; Restore</div>
              <div style="display:flex;gap:8px">
                <button class="btn-primary" style="flex:1" onclick="alert('Backup database dimulai...')"><i class="ti ti-download" aria-hidden="true"></i> Backup sekarang</button>
                <button class="btn-secondary" style="flex:1" onclick="alert('Pilih file backup untuk restore')"><i class="ti ti-upload" aria-hidden="true"></i> Restore</button>
              </div>
              <div style="font-size:11px;color:var(--sb-muted);margin-top:8px">Backup terakhir: Senin, 16 Jun 2025 · 06:00 WIB</div>
            </div>
          </div>
        </div>
      </div><!-- end page-pengaturan -->

      <!-- ===================== PAGE: TENTANG ===================== -->
      <div class="page" id="page-tentang">
        <div class="about-hero">
          <div style="font-size:40px;margin-bottom:10px">🗑️</div>
          <div style="font-size:20px;font-weight:600;margin-bottom:4px">SMART BIN IoT</div>
          <div style="font-size:12px;color:var(--sb-muted);margin-bottom:12px">Sistem Manajemen Tempat Sampah Cerdas Berbasis Internet of Things</div>
          <div style="display:inline-flex;gap:8px">
            <span class="badge info">v2.4.1</span>
            <span class="badge ok">Stabil</span>
            <span class="badge" style="background:rgba(0,212,170,0.15);color:var(--sb-accent)">Open Beta</span>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
          <div class="chart-card">
            <div class="chart-title" style="margin-bottom:10px">Fitur Utama</div>
            <div style="display:flex;flex-direction:column;gap:7px;font-size:12px">
              <div style="display:flex;gap:8px;align-items:center"><i class="ti ti-scan" style="color:var(--sb-accent)" aria-hidden="true"></i> Klasifikasi sampah AI real-time</div>
              <div style="display:flex;gap:8px;align-items:center"><i class="ti ti-chart-line" style="color:var(--sb-success)" aria-hidden="true"></i> Analitik &amp; laporan komprehensif</div>
              <div style="display:flex;gap:8px;align-items:center"><i class="ti ti-bell" style="color:var(--sb-warn)" aria-hidden="true"></i> Sistem notifikasi otomatis</div>
              <div style="display:flex;gap:8px;align-items:center"><i class="ti ti-map" style="color:var(--sb-danger)" aria-hidden="true"></i> Peta lokasi bin interaktif</div>
            </div>
          </div>
          <div class="chart-card">
            <div class="chart-title" style="margin-bottom:10px">Informasi Pengembang</div>
            <div style="font-size:12px;display:flex;flex-direction:column;gap:6px">
              <div style="display:flex;justify-content:space-between"><span style="color:var(--sb-muted)">Tim</span><span>SmartCity Research Lab</span></div>
              <div style="display:flex;justify-content:space-between"><span style="color:var(--sb-muted)">Versi</span><span>2.4.1 (Build 2025.06)</span></div>
              <div style="display:flex;justify-content:space-between"><span style="color:var(--sb-muted)">Framework</span><span>Laravel</span></div>
              <div style="display:flex;justify-content:space-between"><span style="color:var(--sb-muted)">AI Model</span><span>YOLOv8-Waste v2.1</span></div>
              <div style="display:flex;justify-content:space-between"><span style="color:var(--sb-muted)">Lisensi</span><span>MIT Open Source</span></div>
              <div style="margin-top:8px;padding:6px;background:var(--sb-surface2);border-radius:5px">
                <a href="mailto:support@smartbin.id" style="color:var(--sb-accent);font-size:12px">✉ support@smartbin.id</a>
              </div>
            </div>
          </div>
        </div>

        <div style="margin-bottom:12px">
          <div style="font-size:13px;font-weight:500;margin-bottom:8px">FAQ</div>
          <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)">Bagaimana cara menambah perangkat bin baru? <i class="ti ti-chevron-down" aria-hidden="true"></i></div>
            <div class="faq-a">Masuk ke menu Pengaturan → Manajemen Perangkat → klik "Tambah Bin". Masukkan ID perangkat, lokasi, dan konfigurasikan MQTT topic yang sesuai.</div>
          </div>
          <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)">Apa yang harus dilakukan jika bin offline? <i class="ti ti-chevron-down" aria-hidden="true"></i></div>
            <div class="faq-a">Periksa koneksi jaringan perangkat, pastikan power supply normal, dan verifikasi konfigurasi MQTT broker. Jika masalah berlanjut, restart perangkat.</div>
          </div>
          <div class="faq-item">
            <div class="faq-q" onclick="toggleFaq(this)">Mengapa confidence score klasifikasi rendah? <i class="ti ti-chevron-down" aria-hidden="true"></i></div>
            <div class="faq-a">Bisa disebabkan oleh pencahayaan kurang, lensa kamera kotor, atau objek yang tidak termasuk dalam dataset training. Bersihkan kamera dan pastikan pencahayaan cukup.</div>
          </div>
        </div>
      </div><!-- end page-tentang -->

    </div><!-- end content -->
  </div><!-- end main -->
</div><!-- end app -->

<!-- ==================== SCRIPTS ==================== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
/* ---- Navigation ---- */
const pages = {
  'dashboard':   { title: 'Dashboard',                  sub: 'Ringkasan kondisi seluruh smart bin' },
  'realtime':    { title: 'Klasifikasi Real-Time',       sub: 'Deteksi dan klasifikasi sampah secara langsung' },
  'monitoring':  { title: 'Monitoring Tempat Sampah',   sub: 'Status dan kondisi seluruh perangkat bin' },
  'statistik':   { title: 'Statistik & Analitik',       sub: 'Grafik dan laporan performa sistem' },
  'riwayat':     { title: 'Riwayat Aktivitas',          sub: 'Log seluruh aktivitas sistem dan pengguna' },
  'notifikasi':  { title: 'Notifikasi',                 sub: 'Peringatan dan pemberitahuan sistem' },
  'pengaturan':  { title: 'Pengaturan',                 sub: 'Konfigurasi sistem dan manajemen pengguna' },
  'tentang':     { title: 'Tentang Sistem',             sub: 'Informasi aplikasi dan dukungan teknis' }
};

document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', () => {
    const p = item.dataset.page;
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.querySelectorAll('.page').forEach(pg => pg.classList.remove('active'));
    item.classList.add('active');
    document.getElementById('page-' + p).classList.add('active');
    document.getElementById('page-title').textContent = pages[p].title;
    document.getElementById('page-sub').textContent   = pages[p].sub;
    if (p === 'statistik') setTimeout(initStatCharts, 100);
    if (p === 'realtime') initTapoCamera();
  });
});

const dailyChartLabels = @json($dailyChartLabels);
const dailyChartData = @json($dailyChartData);
const categoryChartLabels = @json($categoryChartLabels);
const categoryChartData = @json($categoryChartData);
const cameraRtspUrl = @json($cameraRtspUrl ?? '');
/* ---- Dashboard Charts ---- */
let chartsInit = false;
function initCharts() {
  if (chartsInit) return;
  chartsInit = true;

  new Chart(document.getElementById('volChart'), {
    type: 'bar',
    data: {
      labels: dailyChartLabels,
      datasets: [{ label: 'Jumlah Sampah', data: dailyChartData, backgroundColor: 'rgba(0,212,170,0.7)', borderRadius: 3 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: '#8b949e', font: { size: 10 } }, grid: { display: false } },
        y: { ticks: { color: '#8b949e', font: { size: 10 } }, grid: { color: 'rgba(48,54,61,0.5)' } }
      }
    }
  });

  new Chart(document.getElementById('catChart'), {
    type: 'doughnut',
    data: {
      labels: categoryChartLabels,
      datasets: [{ data: categoryChartData, backgroundColor: ['#2196f3','#3fb950','#e3b341','#8b949e','#58a6ff'], borderWidth: 0 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      cutout: '60%'
    }
  });
}


/* ---- Statistik Charts ---- */
let statChartsInit = false;
function initStatCharts() {
  if (statChartsInit) return;
  statChartsInit = true;

  new Chart(document.getElementById('statCatChart'), {
    type: 'bar',
    data: {
      labels: categoryChartLabels,
      datasets: [{ data: categoryChartData, backgroundColor: ['#2196f3','#3fb950','#e3b341','#8b949e','#58a6ff','#f85149'], borderRadius: 3 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: '#8b949e', font: { size: 10 } }, grid: { display: false } },
        y: { ticks: { color: '#8b949e', font: { size: 10 } }, grid: { color: 'rgba(48,54,61,0.5)' } }
      }
    }
  });

  new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
      labels: dailyChartLabels,
      datasets: [{
        label: 'Jumlah Sampah', data: dailyChartData,
        borderColor: '#00d4aa', backgroundColor: 'rgba(0,212,170,0.08)',
        fill: true, tension: 0.4, pointBackgroundColor: '#00d4aa', pointRadius: 3
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: '#8b949e', font: { size: 10 } }, grid: { display: false } },
        y: { ticks: { color: '#8b949e', font: { size: 10 } }, grid: { color: 'rgba(48,54,61,0.5)' } }
      }
    }
  });
}

setTimeout(initCharts, 300);

/* ---- Notifications ---- */
let unreadCount = {{ $unreadNotifications }};

function markRead(id) {
  const el = document.getElementById(id);
  if (el && el.classList.contains('unread')) {
    el.classList.remove('unread');
    el.style.opacity = '0.6';
    unreadCount = Math.max(0, unreadCount - 1);
    document.getElementById('notif-count').textContent = unreadCount;
    document.getElementById('unread-label').textContent = unreadCount + ' belum dibaca';
    if (unreadCount === 0) document.getElementById('notif-count').style.display = 'none';
  }
}

function deleteNotif(id) {
  const el = document.getElementById(id);
  if (el) {
    if (el.classList.contains('unread')) {
      unreadCount = Math.max(0, unreadCount - 1);
      document.getElementById('notif-count').textContent = unreadCount;
      document.getElementById('unread-label').textContent = unreadCount + ' belum dibaca';
    }
    el.style.transition = 'opacity 0.3s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 300);
  }
}

function markAllRead() {
  document.querySelectorAll('.notif-item.unread').forEach(el => {
    el.classList.remove('unread');
    el.style.opacity = '0.6';
  });
  unreadCount = 0;
  document.getElementById('notif-count').style.display = 'none';
  document.getElementById('unread-label').textContent = 'Semua sudah dibaca';
}

/* ---- FAQ ---- */
function toggleFaq(el) {
  el.nextElementSibling.classList.toggle('open');
}

/* ---- Utilities ---- */
function exportReport(type) {
  if (type === 'PDF') {
    const doc = new jspdf.jsPDF({ unit: 'pt', format: 'a4' });
    doc.text('Laporan Klasifikasi Smart Bin', 40, 40);
    const columns = ['Waktu', 'Perangkat', 'IP Kamera', 'Kategori', 'Confidence', 'Status'];
    const rows = Array.from(document.querySelectorAll('#page-realtime table.data-table tbody tr'))
      .filter(row => row.querySelectorAll('td').length > 0)
      .map(row => Array.from(row.querySelectorAll('td')).map(td => td.textContent.trim()));
    if (rows.length === 0) {
      alert('Tidak ada data klasifikasi untuk diekspor.');
      return;
    }
    doc.autoTable({ head: [columns], body: rows, startY: 60, styles: { fontSize: 9, cellPadding: 4 } });
    doc.save('laporan-klasifikasi.pdf');
    return;
  }

  if (type === 'Excel') {
    const wb = XLSX.utils.book_new();
    const wsData = [['Waktu', 'Perangkat', 'IP Kamera', 'Kategori', 'Confidence', 'Status']];
    Array.from(document.querySelectorAll('#page-realtime table.data-table tbody tr'))
      .filter(row => row.querySelectorAll('td').length > 0)
      .forEach(row => {
        wsData.push(Array.from(row.querySelectorAll('td')).map(td => td.textContent.trim()));
      });
    if (wsData.length === 1) {
      alert('Tidak ada data klasifikasi untuk diekspor.');
      return;
    }
    const ws = XLSX.utils.aoa_to_sheet(wsData);
    XLSX.utils.book_append_sheet(wb, ws, 'Klasifikasi');
    XLSX.writeFile(wb, 'laporan-klasifikasi.xlsx');
    return;
  }

  alert('Tipe ekspor tidak didukung: ' + type);
}

function simulateRefresh() {
  const btn = document.querySelector('.tb-btn');
  btn.innerHTML = '<i class="ti ti-loader" aria-hidden="true"></i> Memuat...';
  setTimeout(() => { btn.innerHTML = '<i class="ti ti-refresh" aria-hidden="true"></i> Refresh'; }, 1500);
}






function switchTab(el, group) {
  el.closest('.chart-tabs').querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
}

/* ---- Realtime Detection Cycle ---- */
const emojis = ['🧴','📄','🥬','🥫','🍾','📦'];
const names  = ['Plastik','Kertas','Organik','Logam','Kaca','Anorganik'];
let ei = 0;
setInterval(() => {
  ei = (ei + 1) % emojis.length;
  const e = document.getElementById('detected-emoji');
  const c = document.getElementById('detected-class');
  if (e && c) { e.textContent = emojis[ei]; c.textContent = names[ei]; }
}, 3000);

/* ---- Live Polling (auto-update tanpa refresh halaman) ---- */
const LIVE_DATA_URL = '{{ route("dashboard.live-data") }}';
const LIVE_POLL_INTERVAL = 4000; // ms

function statusBadgeHtml(status) {
  if (status === 'full') return '<span class="badge full"><span class="dot"></span> Penuh</span>';
  if (status === 'near') return '<span class="badge near"><span class="dot"></span> Hampir Penuh</span>';
  return '<span class="badge ok"><span class="dot"></span> Kosong</span>';
}

function batteryHtml(level) {
  const color = level > 50 ? 'var(--sb-success)' : (level > 20 ? 'var(--sb-warn)' : 'var(--sb-danger)');
  return `<span style="color:${color}">🔋 ${level}%</span>`;
}

function capacityHtml(capacity) {
  const statusColor = capacity >= 90 ? 'full' : (capacity >= 70 ? 'near' : 'ok');
  const textColor = statusColor === 'full' ? 'var(--sb-danger)' : (statusColor === 'near' ? 'var(--sb-warn)' : 'var(--sb-success)');
  return `<div style="display:flex;align-items:center;gap:8px">
      <div class="prog-bar" style="width:60px"><div class="prog-fill ${statusColor}" style="width:${capacity}%"></div></div>
      <span style="color:${textColor};font-weight:500">${capacity}%</span>
    </div>`;
}

function renderBinTable(bins) {
  const tbody = document.getElementById('bin-table-body');
  if (!tbody) return;

  if (!bins.length) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--sb-muted);padding:20px">Belum ada data perangkat</td></tr>';
    return;
  }

  tbody.innerHTML = bins.map(bin => `
    <tr>
      <td style="font-weight:500">${bin.device_id}</td>
      <td>${bin.location ?? '-'}</td>
      <td>${bin.camera_ip ?? '-'}</td>
      <td>${capacityHtml(bin.max_capacity)}</td>
      <td>${batteryHtml(bin.battery_level)}</td>
      <td style="color:var(--sb-muted)">${bin.last_reported_at ?? '-'}</td>
      <td>${statusBadgeHtml(bin.status)}</td>
    </tr>
  `).join('');
}

function renderRecentNotifications(list) {
  const container = document.getElementById('recent-notifications-list');
  if (!container) return;

  if (!list.length) {
    container.innerHTML = '<div style="font-size:12px;color:var(--sb-muted)">Belum ada notifikasi</div>';
    return;
  }

  container.innerHTML = list.map(n => {
    const isWarning = n.level === 'warn' || n.level === 'warning';
    const border = n.level === 'danger' ? 'var(--sb-danger)' : (isWarning ? 'var(--sb-warn)' : 'var(--sb-info)');
    const bg = n.level === 'danger' ? 'rgba(248,81,73,0.08)' : (isWarning ? 'rgba(227,179,65,0.08)' : 'rgba(88,166,255,0.08)');
    return `<div style="font-size:12px;padding:5px 8px;background:${bg};border-radius:5px;margin-bottom:4px;border-left:2px solid ${border}">${n.title} <span style="color:var(--sb-muted)">${n.occurred_at ?? ''}</span></div>`;
  }).join('');
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

async function pollLiveData() {
  try {
    const res = await fetch(LIVE_DATA_URL, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) return;
    const data = await res.json();

    setText('stat-total-bins', data.stats.total_bins);
    setText('stat-full', data.stats.compartments_full);
    setText('stat-near', data.stats.compartments_near);
    setText('stat-safe', data.stats.compartments_safe);
    setText('stat-organik-avg', data.stats.organik_avg + '%');
    setText('stat-anorganik-avg', data.stats.anorganik_avg + '%');
    setText('stat-b3-avg', data.stats.b3_avg + '%');

    const notifCount = document.getElementById('notif-count');
    if (notifCount) {
      notifCount.textContent = data.unread_notifications;
      notifCount.style.display = data.unread_notifications === 0 ? 'none' : '';
    }
    const unreadLabel = document.getElementById('unread-label');
    if (unreadLabel) unreadLabel.textContent = data.unread_notifications + ' belum dibaca';

    renderBinTable(data.bins);
    renderRecentNotifications(data.recent_notifications);
  } catch (e) {
    console.error('Gagal polling live data:', e);
  }
}

setInterval(pollLiveData, LIVE_POLL_INTERVAL);
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/hls.js/1.5.13/hls.min.js"></script>
</body>
</html>