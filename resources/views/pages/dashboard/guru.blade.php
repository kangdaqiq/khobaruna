@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6 space-y-6">
        <!-- Role Switcher Banner -->
        <x-common.role-switcher-banner activeRole="guru" />

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Dashboard Guru Mata Pelajaran</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pendidik: <strong>{{ $guru->nama ?? auth()->user()->name }}</strong> (NIP: {{ $guru->nip ?? '-' }})</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                    <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                    Tahun Ajaran 2024/2025 (Ganjil)
                </span>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Kelas Diampu</span>
                <div class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ $kelasesCount }} Rombel</div>
                <div class="mt-2 text-xs text-brand-500">Berdasarkan SK Mengajar</div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Mata Pelajaran Diampu</span>
                <div class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $mapelsCount }} Mapel</div>
                <div class="mt-2 text-xs text-gray-500">Total bidang keilmuan</div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Total Peserta Didik</span>
                <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($totalSiswa) }} Siswa</div>
                <div class="mt-2 text-xs text-gray-500">Akumulasi siswa diampu</div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Penugasan Aktif</span>
                <div class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $assignments->count() }} Jadwal</div>
                <div class="mt-2 text-xs text-gray-500">Kombinasi Mapel & Rombel</div>
            </div>
        </div>

        <!-- Rombel & Mapel Mengajar yang Diampu -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Mata Pelajaran & Kelas yang Anda Ampu</h3>
                    <p class="text-xs text-gray-500">Hanya rombel dan mata pelajaran ini yang memiliki izin untuk Anda isi nilainya.</p>
                </div>
                <a href="{{ route('penilaian.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                    Lihat Semua Penilaian →
                </a>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse($assignments as $a)
                    <div class="rounded-xl border border-gray-200 p-4 transition hover:border-brand-500 dark:border-gray-800 dark:hover:border-brand-500 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">
                                    {{ $a->kelas?->nama_kelas }}
                                </span>
                                <span class="text-xs text-gray-400 font-medium">
                                    {{ $a->kelas?->siswas->count() ?? 0 }} Siswa
                                </span>
                            </div>
                            <h4 class="text-base font-bold text-gray-800 dark:text-white mt-1">
                                {{ $a->mapel?->nama_mapel }}
                            </h4>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Kode: {{ $a->mapel?->kode_mapel }} • {{ $a->mapel?->kelompok }}
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center gap-2">
                            <a href="{{ route('penilaian.input', [$a->kelas_id, $a->mapel_id]) }}" class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg bg-brand-500 py-2 text-xs font-semibold text-white hover:bg-brand-600 transition">
                                <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                <span>Input Nilai Siswa</span>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-8 text-center text-gray-500">
                        Belum ada data penugasan kelas yang diampu untuk akun ini.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
