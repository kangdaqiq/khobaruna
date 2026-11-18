@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
        <!-- Role Switcher Banner -->
        <x-common.role-switcher-banner />

        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Portal Hasil Belajar Siswa</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Selamat datang, <strong>{{ $siswa->nama ?? auth()->user()->name }}</strong> (NIS: {{ $siswa->nis ?? '-' }})</p>
            </div>
            <div>
                <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 shadow-theme-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Cetak / Unduh Rapor PDF
                </button>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Rata-Rata Capaian</span>
                <div class="mt-2 text-2xl font-bold text-brand-600 dark:text-brand-400">88.5</div>
                <div class="mt-2 text-xs text-emerald-600 font-medium">Predikat Sangat Baik (A)</div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Kehadiran (Absensi)</span>
                <div class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">99%</div>
                <div class="mt-2 text-xs text-gray-500">S: 1 hari | I: 0 | A: 0</div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Ekstrakurikuler</span>
                <div class="mt-2 text-2xl font-bold text-purple-600 dark:text-purple-400">Pramuka & IT</div>
                <div class="mt-2 text-xs text-gray-500">Predikat: Amat Baik</div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Status Semester</span>
                <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">Tuntas</div>
                <div class="mt-2 text-xs text-gray-500">Semester Ganjil 2026/2027</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">
            <!-- Biodata Siswa -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 lg:col-span-1">
                <div class="text-center pb-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-brand-50 text-2xl font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <h3 class="mt-3 text-base font-bold text-gray-800 dark:text-white">{{ $siswa->nama ?? auth()->user()->name }}</h3>
                    <p class="text-xs text-gray-500">{{ $siswa->kelas_nama ?? 'XII RPL 1' }}</p>
                </div>

                <div class="mt-4 space-y-3 text-xs">
                    <div class="flex justify-between py-1">
                        <span class="text-gray-500">NIS / NISN:</span>
                        <span class="font-medium text-gray-800 dark:text-white">{{ $siswa->nis ?? '-' }} / {{ $siswa->nisn ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-gray-500">Jenis Kelamin:</span>
                        <span class="font-medium text-gray-800 dark:text-white">{{ $siswa->jenis_kelamin ?? 'Laki-laki' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-gray-500">Tanggal Lahir:</span>
                        <span class="font-medium text-gray-800 dark:text-white">{{ $siswa->tgl_lahir ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-gray-500">Kontak WA:</span>
                        <span class="font-medium text-gray-800 dark:text-white">{{ $siswa->no_wa ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-gray-500">Alamat:</span>
                        <span class="font-medium text-gray-800 dark:text-white text-end max-w-[160px] truncate">{{ $siswa->alamat ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Preview Capaian Nilai Rapor -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Ringkasan Nilai Hasil Belajar</h3>
                    <span class="text-xs text-gray-400">Kurikulum Merdeka</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs text-gray-600 dark:text-gray-400">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800 uppercase text-gray-400">
                                <th class="py-2.5 text-start font-medium">Mata Pelajaran</th>
                                <th class="py-2.5 text-center font-medium">Nilai Akhir</th>
                                <th class="py-2.5 text-start font-medium">Capaian Kompetensi Tertinggi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr>
                                <td class="py-3 font-semibold text-gray-800 dark:text-white">Pendidikan Agama & Budi Pekerti</td>
                                <td class="py-3 text-center font-bold text-brand-600">92</td>
                                <td class="py-3 text-xs text-gray-600 dark:text-gray-400">Menunjukkan penguasaan sangat baik dalam memahami toleransi beragama dan implementasi akhlak mulia.</td>
                            </tr>
                            <tr>
                                <td class="py-3 font-semibold text-gray-800 dark:text-white">Bahasa Indonesia</td>
                                <td class="py-3 text-center font-bold text-brand-600">88</td>
                                <td class="py-3 text-xs text-gray-600 dark:text-gray-400">Sangat terampil dalam menyusun teks argumentasi kritis dan presentasi ilmiah.</td>
                            </tr>
                            <tr>
                                <td class="py-3 font-semibold text-gray-800 dark:text-white">Matematika</td>
                                <td class="py-3 text-center font-bold text-brand-600">86</td>
                                <td class="py-3 text-xs text-gray-600 dark:text-gray-400">Menguasai pemodelan fungsi dan statistika inferensial dengan baik.</td>
                            </tr>
                            <tr>
                                <td class="py-3 font-semibold text-gray-800 dark:text-white">Pemrograman Web & Perangkat Bergerak</td>
                                <td class="py-3 text-center font-bold text-brand-600">95</td>
                                <td class="py-3 text-xs text-gray-600 dark:text-gray-400">Sangat mahir dalam merancang arsitektur MVC, integrasi API, dan perancangan antarmuka responsif.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

