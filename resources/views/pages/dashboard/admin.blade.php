@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
        <!-- Role Switcher Banner -->
        <x-common.role-switcher-banner />

        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Dashboard Administrator</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Ringkasan operasional dan status sinkronisasi sistem e-rapor.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.sync.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Pusat Sinkronisasi
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 mb-6">
            <!-- Guru Card -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Data Guru</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-brand-500 dark:bg-brand-500/10">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($counts['gurus'] ?? 0) }}</div>
                <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                    <a href="{{ route('admin.gurus.index') }}" class="text-brand-500 hover:underline">Kelola Guru &rarr;</a>
                </div>
            </div>

            <!-- Siswa Card -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Data Siswa</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500 dark:bg-emerald-500/10">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($counts['siswas'] ?? 0) }}</div>
                <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                    <a href="{{ route('admin.siswas.index') }}" class="text-emerald-500 hover:underline">Kelola Siswa &rarr;</a>
                </div>
            </div>

            <!-- Kelas Card -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Rombel / Kelas</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-500 dark:bg-purple-500/10">
                        <i class="fa-solid fa-school"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($counts['kelases'] ?? 0) }}</div>
                <div class="mt-2 text-xs text-gray-500">Rombongan Belajar</div>
            </div>

            <!-- Mapel Card -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Mata Pelajaran</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-500 dark:bg-amber-500/10">
                        <i class="fa-solid fa-book-open"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($counts['mapels'] ?? 0) }}</div>
                <div class="mt-2 text-xs text-gray-500">Struktur Kurikulum</div>
            </div>

            <!-- Users Card -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Akun Pengguna</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-500 dark:bg-rose-500/10">
                        <i class="fa-solid fa-key"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($counts['users'] ?? 0) }}</div>
                <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                    <a href="{{ route('admin.users.index') }}" class="text-rose-500 hover:underline">Kelola Akun &rarr;</a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">
            <!-- Profil Sekolah & Status Sistem -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 lg:col-span-1">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Informasi Satuan Pendidikan</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Nama Sekolah</div>
                        <div class="font-semibold text-gray-800 dark:text-white">{{ $school->name ?? 'SMK / SMA Negeri' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">NPSN / Kode</div>
                        <div class="text-gray-700 dark:text-gray-300">{{ $school->npsn ?? '-' }} ({{ $school->code ?? '-' }})</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Kepala Sekolah</div>
                        <div class="text-gray-700 dark:text-gray-300">{{ $school->headmaster_name ?? 'Belum Diatur' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Status Database Sumber</div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Terhubung (absen_jagat)
                        </span>
                    </div>
                </div>
            </div>

            <!-- Riwayat Sinkronisasi Terkini -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Riwayat Sinkronisasi Terakhir</h3>
                    <a href="{{ route('admin.sync.index') }}" class="text-xs font-medium text-brand-500 hover:underline">Lihat Semua</a>
                </div>

                @if($recentSync->isEmpty())
                    <div class="py-8 text-center text-sm text-gray-500">
                        Belum ada riwayat sinkronisasi data.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-start text-xs text-gray-600 dark:text-gray-400">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-800 text-gray-400 uppercase">
                                    <th class="py-2.5 text-start font-medium">Waktu</th>
                                    <th class="py-2.5 text-start font-medium">Sumber</th>
                                    <th class="py-2.5 text-start font-medium">Hasil Import</th>
                                    <th class="py-2.5 text-end font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($recentSync as $log)
                                    <tr>
                                        <td class="py-2.5">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="py-2.5 font-medium text-gray-700 dark:text-gray-300">{{ $log->source_url ?? 'Auto' }}</td>
                                        <td class="py-2.5">
                                            {{ $log->total_gurus }} Guru, {{ $log->total_siswas }} Siswa, {{ $log->total_kelases }} Kelas
                                        </td>
                                        <td class="py-2.5 text-end">
                                            @if($log->status === 'success')
                                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-600 dark:bg-emerald-500/10">Sukses</span>
                                            @elseif($log->status === 'in_progress')
                                                <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-600 dark:bg-amber-500/10">Berjalan</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-600 dark:bg-rose-500/10">Gagal</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

