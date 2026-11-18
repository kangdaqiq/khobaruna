@extends('layouts.app')

@section('content')
<div x-data="{
    isSyncing: false,
    isEditOpen: false,
    editData: { id: '', nama: '', nis: '', kelas: '', masuk: 0, sakit: 0, izin: 0, alpha: 0, bolos: 0, catatan: '' },
    openEdit(item) {
        this.editData = {
            id: item.id,
            nama: item.siswa ? item.siswa.nama : '-',
            nis: item.siswa ? item.siswa.nis : '-',
            kelas: item.kelas ? item.kelas.nama_kelas : '-',
            masuk: item.masuk,
            sakit: item.sakit,
            izin: item.izin,
            alpha: item.alpha,
            bolos: item.bolos,
            catatan: item.catatan || ''
        };
        this.isEditOpen = true;
    },
    setPresetRange(start, end) {
        document.getElementById('input_start_date').value = start;
        document.getElementById('input_end_date').value = end;
    }
}" class="space-y-6 pb-20">

    <!-- Header Page -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-calendar-check text-brand-600"></i>
                <span>Rekapitulasi Absensi Siswa</span>
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Sinkronisasi data rekap absensi 1 semester (Total Masuk, Sakit, Izin, Alpha, dan Bolos) langsung dari <strong>Backend Web Absensi</strong> via REST API.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            @if(!empty($isWaliKelas))
                <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 px-3.5 py-2 text-xs font-bold text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                    <i class="fa-solid fa-chalkboard-user"></i>
                    <span>Wali Kelas: <strong>{{ $kelases->pluck('nama_kelas')->implode(', ') }}</strong></span>
                </span>
            @endif
            <span class="inline-flex items-center gap-2 rounded-xl bg-purple-50 dark:bg-purple-500/10 px-3.5 py-2 text-xs font-semibold text-purple-700 dark:text-purple-400">
                <i class="fa-solid fa-school"></i>
                <span>{{ $school->name ?? 'Sekolah' }}</span>
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-xl bg-blue-50 dark:bg-blue-500/10 px-3 py-2 text-xs font-semibold text-blue-700 dark:text-blue-400">
                <i class="fa-solid fa-calendar"></i>
                <span>T.A {{ $tahunAjaran }} ({{ $semester }})</span>
            </span>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 flex items-center gap-2.5 shadow-theme-xs">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-xs font-medium text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300 flex items-center gap-2.5 shadow-theme-xs">
            <i class="fa-solid fa-circle-xmark text-rose-600 text-sm"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Status Koneksi Backend API -->
    <div class="rounded-2xl border p-4 shadow-theme-xs transition {{ $apiStatus['connected'] ? 'border-emerald-200 bg-emerald-50/40 dark:border-emerald-900/40 dark:bg-emerald-950/20' : 'border-rose-200 bg-rose-50/40 dark:border-rose-900/40 dark:bg-rose-950/20' }}">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center {{ $apiStatus['connected'] ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-400' }}">
                    <i class="fa-solid {{ $apiStatus['connected'] ? 'fa-plug-circle-check' : 'fa-plug-circle-xmark' }} text-sm"></i>
                </div>
                <div>
                    <div class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span>Jalur Integrasi: Backend REST API Web Absen</span>
                        @if($apiStatus['connected'])
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Online & Terhubung
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 dark:bg-rose-500/20 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:text-rose-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Offline
                            </span>
                        @endif
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 mt-0.5">
                        Endpoint: <code class="font-mono bg-white/80 dark:bg-gray-800 px-1.5 py-0.5 rounded text-[11px]">{{ $apiStatus['api_url'] }}</code>
                        @if($apiStatus['connected'])
                            • Layanan: <strong>{{ $apiStatus['server_name'] }}</strong> (Akses Terisolasi Tanpa Direct DB)
                        @endif
                    </p>
                </div>
            </div>
            <div class="text-end">
                <span class="text-[11px] text-gray-400">Sinkronisasi Data Murni Rekapitulasi</span>
            </div>
        </div>
    </div>

    <!-- Panel Kontrol: Form Penentuan Rentang 1 Semester & Sinkronisasi -->
    <div class="rounded-2xl border border-brand-200 bg-white p-5 shadow-theme-xs dark:border-brand-900/40 dark:bg-gray-900">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
            <div>
                <h3 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-brand-500"></i>
                    <span>Tentukan Rentang Waktu 1 Semester untuk Sinkronisasi Absensi</span>
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Admin dapat menentukan tanggal awal dan tanggal akhir semester. Sistem akan mengakumulasi total <strong>Masuk</strong>, <strong>Sakit</strong>, <strong>Izin</strong>, <strong>Alpha</strong>, dan <strong>Bolos</strong> per siswa.
                </p>
            </div>

            <!-- Preset Cepat Rentang Tanggal -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-[11px] text-gray-400 font-medium me-1">Preset Cepat:</span>
                <button type="button" @click="setPresetRange('{{ date('Y') }}-07-01', '{{ date('Y') }}-12-31')" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2.5 py-1 text-[11px] font-semibold text-gray-700 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 transition">
                    Ganjil (Jul - Des)
                </button>
                <button type="button" @click="setPresetRange('{{ date('Y') }}-01-01', '{{ date('Y') }}-06-30')" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2.5 py-1 text-[11px] font-semibold text-gray-700 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 transition">
                    Genap (Jan - Jun)
                </button>
                <button type="button" @click="setPresetRange('2026-07-01', '2026-11-01')" class="rounded-lg border border-brand-200 bg-brand-50/80 px-2.5 py-1 text-[11px] font-bold text-brand-700 hover:bg-brand-100 transition" title="Rentang tanggal yang memiliki data riil di server absensi saat ini">
                    <i class="fa-solid fa-wand-magic-sparkles text-[10px]"></i> Data Riil Server (Jul - Nov)
                </button>
            </div>
        </div>

        <form action="{{ route('admin.absensi.sync') }}" method="POST" @submit="isSyncing = true">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Tanggal Mulai Semester -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        <i class="fa-solid fa-calendar-day text-brand-500 me-1"></i>
                        <span>Tanggal Mulai Semester:</span>
                    </label>
                    <input type="date" id="input_start_date" name="start_date" required value="{{ $defaultStartDate }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs font-medium text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                </div>

                <!-- Tanggal Selesai Semester -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        <i class="fa-solid fa-calendar-check text-brand-500 me-1"></i>
                        <span>Tanggal Selesai Semester:</span>
                    </label>
                    <input type="date" id="input_end_date" name="end_date" required value="{{ $defaultEndDate }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs font-medium text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                </div>

                <!-- Filter Rombel / Kelas -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        <i class="fa-solid fa-layer-group text-indigo-500 me-1"></i>
                        <span>Target Rombel / Kelas:</span>
                    </label>
                    <select name="kelas_id" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs font-medium text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                        @if(empty($isWaliKelas))
                            <option value="">Semua Rombel (Seluruh Siswa Sekolah)</option>
                        @endif
                        @foreach($kelases as $k)
                            <option value="{{ $k->id }}" {{ $selectedKelasId == $k->id ? 'selected' : '' }}>
                                {{ $k->nama_kelas }} (Tingkat {{ $k->tingkat }})
                            </option>
                        @endforeach
                    </select>
                    @if(!empty($isWaliKelas))
                        <div class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1 font-medium">
                            <i class="fa-solid fa-lock text-[9px]"></i>
                            <span>Dibatasi khusus untuk kelas yang Anda walikan.</span>
                        </div>
                    @endif
                </div>

                <!-- Tombol Submit Sinkronisasi -->
                <div class="flex items-end">
                    <button type="submit" :disabled="isSyncing" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-theme-md hover:from-brand-600 hover:to-indigo-700 transition disabled:opacity-50">
                        <i class="fa-solid fa-arrows-rotate text-xs" :class="{ 'animate-spin': isSyncing }"></i>
                        <span x-text="isSyncing ? 'Menghubungi Backend Absen...' : 'Tarik Rekap Absensi'"></span>
                    </button>
                </div>
            </div>

            <!-- Hidden Meta -->
            <input type="hidden" name="tahun_ajaran" value="{{ $tahunAjaran }}">
            <input type="hidden" name="semester" value="{{ $semester }}">
        </form>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
        <!-- Total Siswa -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider block mb-1">Siswa Terdata</span>
            <div class="text-xl font-bold font-mono text-gray-800 dark:text-white">
                {{ number_format($stats['total_siswa']) }}
            </div>
            <span class="text-[10px] text-gray-400 mt-0.5 block">Siswa Aktif</span>
        </div>

        <!-- Total Masuk -->
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4 shadow-theme-xs dark:border-emerald-900/40 dark:bg-emerald-950/20">
            <span class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider block mb-1 flex items-center gap-1">
                <i class="fa-solid fa-check"></i> Masuk
            </span>
            <div class="text-xl font-bold font-mono text-emerald-800 dark:text-emerald-300">
                {{ number_format($stats['total_masuk']) }}
            </div>
            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5 block">Hadir & Terlambat</span>
        </div>

        <!-- Total Sakit -->
        <div class="rounded-2xl border border-blue-100 bg-blue-50/40 p-4 shadow-theme-xs dark:border-blue-900/40 dark:bg-blue-950/20">
            <span class="text-[11px] font-semibold text-blue-700 dark:text-blue-400 uppercase tracking-wider block mb-1 flex items-center gap-1">
                <i class="fa-solid fa-head-side-cough"></i> Sakit (S)
            </span>
            <div class="text-xl font-bold font-mono text-blue-800 dark:text-blue-300">
                {{ number_format($stats['total_sakit']) }}
            </div>
            <span class="text-[10px] text-blue-600 dark:text-blue-400 mt-0.5 block">Hari Sakit</span>
        </div>

        <!-- Total Izin -->
        <div class="rounded-2xl border border-amber-100 bg-amber-50/40 p-4 shadow-theme-xs dark:border-amber-900/40 dark:bg-amber-950/20">
            <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider block mb-1 flex items-center gap-1">
                <i class="fa-solid fa-envelope-open-text"></i> Izin (I)
            </span>
            <div class="text-xl font-bold font-mono text-amber-800 dark:text-amber-300">
                {{ number_format($stats['total_izin']) }}
            </div>
            <span class="text-[10px] text-amber-600 dark:text-amber-400 mt-0.5 block">Hari Izin</span>
        </div>

        <!-- Total Alpha -->
        <div class="rounded-2xl border border-rose-100 bg-rose-50/40 p-4 shadow-theme-xs dark:border-rose-900/40 dark:bg-rose-950/20">
            <span class="text-[11px] font-semibold text-rose-700 dark:text-rose-400 uppercase tracking-wider block mb-1 flex items-center gap-1">
                <i class="fa-solid fa-user-xmark"></i> Alpha (A)
            </span>
            <div class="text-xl font-bold font-mono text-rose-800 dark:text-rose-300">
                {{ number_format($stats['total_alpha']) }}
            </div>
            <span class="text-[10px] text-rose-600 dark:text-rose-400 mt-0.5 block">Tanpa Keterangan</span>
        </div>

        <!-- Total Bolos -->
        <div class="rounded-2xl border border-purple-100 bg-purple-50/40 p-4 shadow-theme-xs dark:border-purple-900/40 dark:bg-purple-950/20">
            <span class="text-[11px] font-semibold text-purple-700 dark:text-purple-400 uppercase tracking-wider block mb-1 flex items-center gap-1">
                <i class="fa-solid fa-person-running"></i> Bolos
            </span>
            <div class="text-xl font-bold font-mono text-purple-800 dark:text-purple-300">
                {{ number_format($stats['total_bolos']) }}
            </div>
            <span class="text-[10px] text-purple-600 dark:text-purple-400 mt-0.5 block">Meninggalkan KBM</span>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
        <form action="{{ route('admin.absensi.index') }}" method="GET" class="flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama siswa / NIS..." class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 ps-9 pe-3.5 py-2 text-xs font-medium text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                    <i class="fa-solid fa-magnifying-glass absolute start-3 top-2.5 text-gray-400 text-xs"></i>
                </div>

                @if(!empty($isWaliKelas) && $kelases->count() === 1)
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 dark:border-emerald-800/40 dark:bg-emerald-950/20 px-3.5 py-2 text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5 shrink-0">
                        <i class="fa-solid fa-chalkboard text-emerald-600"></i>
                        <span>Rombel: {{ $kelases->first()->nama_kelas }}</span>
                    </div>
                @else
                    <select name="kelas_id" onchange="this.form.submit()" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs font-medium text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                        @if(empty($isWaliKelas))
                            <option value="">Semua Kelas</option>
                        @endif
                        @foreach($kelases as $k)
                            <option value="{{ $k->id }}" {{ $selectedKelasId == $k->id ? 'selected' : '' }}>
                                {{ $k->nama_kelas }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <button type="submit" class="rounded-xl bg-gray-100 dark:bg-gray-800 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-200 transition">
                    Filter
                </button>

                @if($search || $selectedKelasId)
                    <a href="{{ route('admin.absensi.index') }}" class="text-xs text-rose-600 hover:underline">
                        Reset Filter
                    </a>
                @endif
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                <span>Rata-rata Kehadiran:</span>
                <span class="font-mono font-bold px-2 py-0.5 rounded-lg {{ $stats['avg_kehadiran'] >= 85 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' }}">
                    {{ $stats['avg_kehadiran'] }}%
                </span>
            </div>
        </form>
    </div>

    <!-- Tabel Rekapitulasi Absensi Siswa -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-start text-xs text-gray-600 dark:text-gray-400">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 text-[11px] uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400">
                        <th class="py-3 px-3 text-center w-12 border-e border-gray-100 dark:border-gray-800">No</th>
                        <th class="py-3 px-4 text-start min-w-[200px] border-e border-gray-100 dark:border-gray-800">Peserta Didik</th>
                        <th class="py-3 px-3 text-start w-32 border-e border-gray-100 dark:border-gray-800">Kelas</th>
                        <th class="py-3 px-3 text-center w-24 bg-emerald-50/40 dark:bg-emerald-500/5 text-emerald-800 dark:text-emerald-300 border-e border-gray-100 dark:border-gray-800">
                            Masuk
                            <span class="block text-[9px] font-normal text-emerald-600 dark:text-emerald-400">(H + T)</span>
                        </th>
                        <th class="py-3 px-2 text-center w-16 bg-blue-50/40 dark:bg-blue-500/5 text-blue-700 dark:text-blue-300 border-e border-gray-100 dark:border-gray-800">Sakit</th>
                        <th class="py-3 px-2 text-center w-16 bg-amber-50/40 dark:bg-amber-500/5 text-amber-700 dark:text-amber-300 border-e border-gray-100 dark:border-gray-800">Izin</th>
                        <th class="py-3 px-2 text-center w-16 bg-rose-50/40 dark:bg-rose-500/5 text-rose-700 dark:text-rose-300 border-e border-gray-100 dark:border-gray-800">Alpha</th>
                        <th class="py-3 px-2 text-center w-16 bg-purple-50/40 dark:bg-purple-500/5 text-purple-700 dark:text-purple-300 border-e border-gray-100 dark:border-gray-800">Bolos</th>
                        <th class="py-3 px-3 text-center w-24 border-e border-gray-100 dark:border-gray-800">Total Hari</th>
                        <th class="py-3 px-3 text-center w-28 border-e border-gray-100 dark:border-gray-800">% Kehadiran</th>
                        <th class="py-3 px-3 text-center w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($rekapList as $idx => $row)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                            <td class="py-3 px-3 text-center font-medium text-gray-400 border-e border-gray-100 dark:border-gray-800">
                                {{ $rekapList->firstItem() + $idx }}
                            </td>
                            <td class="py-3 px-4 border-e border-gray-100 dark:border-gray-800">
                                <div class="font-semibold text-gray-800 dark:text-white">{{ $row->siswa?->nama ?? 'Siswa Tidak Ditemukan' }}</div>
                                <div class="text-[11px] font-mono text-gray-400">NIS: {{ $row->siswa?->nis ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-3 border-e border-gray-100 dark:border-gray-800">
                                <span class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $row->kelas?->nama_kelas ?? ($row->siswa?->kelas_nama ?? '-') }}
                                </span>
                            </td>

                            <!-- Masuk -->
                            <td class="py-3 px-3 text-center font-mono font-bold text-xs bg-emerald-50/20 dark:bg-emerald-500/5 text-emerald-700 dark:text-emerald-400 border-e border-gray-100 dark:border-gray-800">
                                {{ $row->masuk }}
                                <div class="text-[9px] font-normal text-gray-400">
                                    ({{ $row->hadir }} H / {{ $row->terlambat }} T)
                                </div>
                            </td>

                            <!-- Sakit -->
                            <td class="py-3 px-2 text-center font-mono font-semibold text-xs border-e border-gray-100 dark:border-gray-800 {{ $row->sakit > 0 ? 'text-blue-600 dark:text-blue-400 font-bold bg-blue-50/30' : 'text-gray-400' }}">
                                {{ $row->sakit }}
                            </td>

                            <!-- Izin -->
                            <td class="py-3 px-2 text-center font-mono font-semibold text-xs border-e border-gray-100 dark:border-gray-800 {{ $row->izin > 0 ? 'text-amber-600 dark:text-amber-400 font-bold bg-amber-50/30' : 'text-gray-400' }}">
                                {{ $row->izin }}
                            </td>

                            <!-- Alpha -->
                            <td class="py-3 px-2 text-center font-mono font-semibold text-xs border-e border-gray-100 dark:border-gray-800 {{ $row->alpha > 0 ? 'text-rose-600 dark:text-rose-400 font-bold bg-rose-50/30' : 'text-gray-400' }}">
                                {{ $row->alpha }}
                            </td>

                            <!-- Bolos -->
                            <td class="py-3 px-2 text-center font-mono font-semibold text-xs border-e border-gray-100 dark:border-gray-800 {{ $row->bolos > 0 ? 'text-purple-600 dark:text-purple-400 font-bold bg-purple-50/30' : 'text-gray-400' }}">
                                {{ $row->bolos }}
                            </td>

                            <!-- Total Hari -->
                            <td class="py-3 px-3 text-center font-mono font-semibold text-xs border-e border-gray-100 dark:border-gray-800">
                                {{ $row->total_hari }} hr
                            </td>

                            <!-- Persentase Kehadiran -->
                            <td class="py-3 px-3 text-center border-e border-gray-100 dark:border-gray-800">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-mono font-bold {{ $row->persentase >= 85 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : ($row->persentase >= 75 ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400') }}">
                                    {{ $row->persentase }}%
                                </span>
                            </td>

                            <!-- Aksi Koreksi Manual -->
                            <td class="py-3 px-3 text-center">
                                <button type="button" @click="openEdit({{ json_encode($row) }})" class="p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 transition" title="Koreksi Manual">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-12 text-center">
                                <div class="max-w-sm mx-auto text-gray-400">
                                    <i class="fa-solid fa-calendar-xmark text-4xl mb-3 text-gray-300 dark:text-gray-700"></i>
                                    <div class="font-bold text-gray-700 dark:text-gray-300 text-sm">Belum Ada Data Rekap Absensi</div>
                                    <p class="text-xs text-gray-400 mt-1">
                                        Tentukan rentang tanggal 1 semester di atas, lalu klik <strong>Tarik Rekap Absensi</strong> untuk mengambil data dari backend web absen.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rekapList->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                {{ $rekapList->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Koreksi Manual Rekap Absensi Siswa -->
    <div x-show="isEditOpen" x-cloak class="fixed inset-0 z-999 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs">
        <div @click.away="isEditOpen = false" class="w-full max-w-lg rounded-2xl bg-white dark:bg-gray-900 p-6 shadow-theme-xl border border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-brand-500"></i>
                    <span>Koreksi Manual Rekap Absensi</span>
                </h3>
                <button type="button" @click="isEditOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form :action="'{{ url('admin/absensi') }}/' + editData.id" method="POST">
                @csrf
                @method('PUT')

                <!-- Info Siswa -->
                <div class="rounded-xl bg-gray-50 dark:bg-gray-800/50 p-3 mb-4 text-xs">
                    <div class="font-bold text-gray-800 dark:text-white text-sm" x-text="editData.nama"></div>
                    <div class="text-gray-500 dark:text-gray-400 mt-0.5">
                        NIS: <span class="font-mono" x-text="editData.nis"></span> • Rombel: <span class="font-semibold" x-text="editData.kelas"></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-emerald-700 dark:text-emerald-400 mb-1">Total Masuk:</label>
                        <input type="number" min="0" name="masuk" x-model.number="editData.masuk" required class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-xs font-mono font-bold text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-blue-700 dark:text-blue-400 mb-1">Sakit (S):</label>
                        <input type="number" min="0" name="sakit" x-model.number="editData.sakit" required class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-xs font-mono font-bold text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-amber-700 dark:text-amber-400 mb-1">Izin (I):</label>
                        <input type="number" min="0" name="izin" x-model.number="editData.izin" required class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-xs font-mono font-bold text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-rose-700 dark:text-rose-400 mb-1">Alpha (A):</label>
                        <input type="number" min="0" name="alpha" x-model.number="editData.alpha" required class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-xs font-mono font-bold text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-purple-700 dark:text-purple-400 mb-1">Bolos:</label>
                        <input type="number" min="0" name="bolos" x-model.number="editData.bolos" required class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-xs font-mono font-bold text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Catatan Tambahan (Opsional):</label>
                    <textarea name="catatan" x-model="editData.catatan" rows="2" placeholder="Catatan khusus wali kelas..." class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 text-xs text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5">
                    <button type="button" @click="isEditOpen = false" class="rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        Batal
                    </button>
                    <button type="submit" class="rounded-xl bg-brand-500 px-5 py-2 text-xs font-bold text-white hover:bg-brand-600 transition shadow-theme-xs">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

