@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <x-common.role-switcher-banner activeRole="guru" />

    <!-- Breadcrumb & Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <x-common.page-breadcrumb :pageTitle="'Penilaian Mata Pelajaran'" />

        @if($isGuru && $guru)
            <div class="rounded-xl border border-brand-200 bg-brand-50/60 dark:border-brand-500/20 dark:bg-brand-500/10 px-3.5 py-2 text-xs text-brand-800 dark:text-brand-300 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                <span>Guru: <strong>{{ $guru->nama }}</strong> (NIP: {{ $guru->nip ?? '-' }})</span>
            </div>
        @endif
    </div>

    @if(!empty($isWaka))
        <!-- Toggle Mode untuk Waka Kurikulum yang juga Mengajar -->
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 dark:border-gray-800 pb-3">
            <a href="{{ route('penilaian.index') }}"
                class="px-4 py-2 text-xs font-semibold rounded-xl transition inline-flex items-center gap-2 {{ empty($mode) ? 'bg-indigo-600 text-white shadow-theme-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                <i class="fa-solid fa-list-check text-xs"></i>
                <span>Supervisi Semua Rombel (Waka Kurikulum)</span>
            </a>
            <a href="{{ route('penilaian.index', ['mode' => 'mengajar']) }}"
                class="px-4 py-2 text-xs font-semibold rounded-xl transition inline-flex items-center gap-2 {{ $mode === 'mengajar' ? 'bg-brand-500 text-white shadow-theme-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                <i class="fa-solid fa-chalkboard-user text-xs"></i>
                <span>Kelas Yang Saya Ajar (Guru Mapel)</span>
            </a>
        </div>
    @endif

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 text-xs font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(!empty($error))
        <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4 text-xs font-medium text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300 flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
            <span>{{ $error }}</span>
        </div>
    @endif

    <!-- Alert Informasi Hak Akses Penilaian -->
    <div class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4.5 dark:border-blue-500/20 dark:bg-blue-500/10">
        <div class="flex items-start gap-3">
            <span class="text-xl text-blue-600 dark:text-blue-400">
                <i class="fa-solid fa-shield-halved"></i>
            </span>
            <div>
                <h4 class="text-xs font-bold text-blue-900 dark:text-blue-300">Sistem Otorisasi Penilaian Mandiri</h4>
                <p class="text-xs text-blue-700 dark:text-blue-400 mt-0.5">
                    Sesuai kebijakan akademik, guru hanya dapat mengisi dan mengubah nilai untuk <strong>kelas dan mata pelajaran yang tercatat resmi di SK Pembagian Tugas Mengajar</strong>. Rombel atau mapel di luar penugasan Anda akan diproteksi otomatis.
                </p>
            </div>
        </div>
    </div>

    <!-- Filter untuk Admin / Waka Kurikulum -->
    @if(!$isGuru)
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <form method="GET" action="{{ route('penilaian.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <select name="guru_id" class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-800 dark:text-white">
                        <option value="">Semua Guru</option>
                        @foreach($gurus as $g)
                            <option value="{{ $g->id }}" {{ request('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="mapel_id" class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-800 dark:text-white">
                        <option value="">Semua Mapel</option>
                        @foreach($mapels as $m)
                            <option value="{{ $m->id }}" {{ request('mapel_id') == $m->id ? 'selected' : '' }}>{{ $m->nama_mapel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="kelas_id" class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-800 dark:text-white">
                        <option value="">Semua Kelas</option>
                        @foreach($kelases as $k)
                            <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-brand-500 text-white text-xs font-medium py-2">
                        Filter Supervisi
                    </button>
                    @if(request('guru_id') || request('mapel_id') || request('kelas_id'))
                        <a href="{{ route('penilaian.index') }}" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-xs text-gray-500 hover:bg-gray-50 flex items-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    @endif

    <!-- Cards Grid Penugasan Mengajar & Penilaian -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($assignments as $assign)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 flex flex-col justify-between hover:border-brand-500/50 transition">
                <div>
                    <!-- Badge Kelas & Kelompok -->
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="rounded-lg bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400 px-3 py-1 font-bold text-xs">
                            {{ $assign->kelas?->nama_kelas ?? 'Kelas -' }}
                        </span>
                        <span class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:text-gray-400">
                            {{ $assign->mapel?->kelompok ?? 'Umum' }}
                        </span>
                    </div>

                    <!-- Judul Mapel -->
                    <h3 class="font-bold text-gray-800 dark:text-white text-base">
                        {{ $assign->mapel?->nama_mapel ?? 'Mata Pelajaran' }}
                    </h3>
                    <div class="text-xs text-gray-400 mt-0.5">Kode: {{ $assign->mapel?->kode_mapel ?? '-' }}</div>

                    <!-- Info Guru (jika view admin/waka) -->
                    @if(!$isGuru)
                        <div class="mt-3 text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-chalkboard-user text-brand-500"></i>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $assign->guru?->nama }}</span>
                        </div>
                    @endif

                    <!-- Progress Penilaian -->
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-gray-500 dark:text-gray-400">Progres Penilaian:</span>
                            <span class="font-semibold {{ $assign->progress == 100 ? 'text-emerald-600' : 'text-brand-600' }}">
                                {{ $assign->filled_count }} / {{ $assign->total_siswa }} Siswa ({{ $assign->progress }}%)
                            </span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <div class="h-full rounded-full {{ $assign->progress == 100 ? 'bg-emerald-500' : 'bg-brand-500' }}" style="width: {{ $assign->progress }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi Input Nilai -->
                <div class="mt-5 pt-3">
                    <a href="{{ route('penilaian.input', [$assign->kelas_id, $assign->mapel_id]) }}" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>Input & Kelola Nilai</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="text-3xl text-gray-400 mb-2">
                    <i class="fa-regular fa-folder-open"></i>
                </div>
                <p class="text-xs text-gray-500">Anda belum memiliki penugasan mata pelajaran atau kelas yang diampu.</p>
                <p class="text-[11px] text-gray-400">Silakan hubungi Waka Kurikulum atau Administrator untuk pemetaan tugas mengajar.</p>
            </div>
        @endforelse
    </div>

</div>
@endsection

