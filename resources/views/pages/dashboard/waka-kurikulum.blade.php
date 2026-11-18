@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
        <!-- Role Switcher Banner -->
        <x-common.role-switcher-banner />

        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Dashboard Waka Kurikulum</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Monitoring pengisian asesmen, capaian pembelajaran, dan pembagian rapor.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Semester Ganjil 2026/2027 (Aktif)
                </span>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Total Tenaga Pendidik</span>
                <div class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($counts['gurus'] ?? 0) }} Guru</div>
                <div class="mt-2 text-xs text-brand-500">Guru Mapel & Wali Kelas</div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Total Peserta Didik</span>
                <div class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($counts['siswas'] ?? 0) }} Siswa</div>
                <div class="mt-2 text-xs text-emerald-500">Terdaftar Aktif</div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Rombel / Kelas</span>
                <div class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($counts['kelases'] ?? 0) }} Kelas</div>
                <div class="mt-2 text-xs text-purple-500">Tingkat X, XI, XII</div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Mata Pelajaran</span>
                <div class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($counts['mapels'] ?? 0) }} Mapel</div>
                <div class="mt-2 text-xs text-amber-500">Kurikulum Merdeka</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
            <!-- Monitoring Rombongan Belajar -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Progres Rombongan Belajar (Rombel)</h3>
                    <span class="text-xs text-gray-400">10 Kelas Teratas</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs text-gray-600 dark:text-gray-400">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800 uppercase text-gray-400">
                                <th class="py-2.5 text-start font-medium">Kelas</th>
                                <th class="py-2.5 text-start font-medium">Jurusan</th>
                                <th class="py-2.5 text-start font-medium">Wali Kelas</th>
                                <th class="py-2.5 text-end font-medium">Siswa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($kelases as $k)
                                <tr>
                                    <td class="py-2.5 font-semibold text-gray-800 dark:text-white">{{ $k->nama_kelas }}</td>
                                    <td class="py-2.5">{{ $k->jurusan ?? '-' }}</td>
                                    <td class="py-2.5 text-gray-700 dark:text-gray-300">{{ $k->wali_kelas_nama ?? 'Belum Ditentukan' }}</td>
                                    <td class="py-2.5 text-end font-medium">{{ $k->siswas_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Daftar Mata Pelajaran -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Struktur Mata Pelajaran</h3>
                    <span class="text-xs text-gray-400">Kurikulum Merdeka</span>
                </div>
                <div class="space-y-3">
                    @foreach($mapels as $m)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                            <div>
                                <div class="text-sm font-semibold text-gray-800 dark:text-white">{{ $m->nama_mapel }}</div>
                                <div class="text-xs text-gray-500">Kelompok: {{ $m->kelompok ?? 'Umum' }}</div>
                            </div>
                            <span class="rounded-lg bg-white dark:bg-gray-700 px-2.5 py-1 text-xs font-semibold text-brand-600 dark:text-brand-400 shadow-theme-xs">
                                {{ $m->kode_mapel ?? 'MAPEL' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

