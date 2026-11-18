@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6" x-data="{ isSyncing: false, selectedSchoolId: '{{ $currentSchool->sync_id ?? env('SYNC_SCHOOL_ID', 4) }}' }">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Pusat Sinkronisasi Data Satuan Pendidikan</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Sinkronisasi dibatasi khusus untuk 1 sekolah (School ID) dari database atau API backend web absensi.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-xl bg-purple-50 dark:bg-purple-500/10 px-3.5 py-2 text-xs font-semibold text-purple-700 dark:text-purple-400">
                    <i class="fa-solid fa-school"></i>
                    <span>Sekolah Aktif: <strong>{{ $currentSchool->name ?? 'Belum Ditentukan' }}</strong></span>
                </span>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400 flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <div><strong>Berhasil!</strong> {{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400 flex items-center gap-2">
                <i class="fa-solid fa-circle-xmark text-rose-600 text-base"></i>
                <div><strong>Gagal:</strong> {{ session('error') }}</div>
            </div>
        @endif

        <!-- Panel Form Pemilihan Sekolah & Eksekusi Sinkronisasi -->
        <div class="rounded-2xl border border-brand-200 bg-brand-50/40 p-6 shadow-theme-xs dark:border-brand-900/50 dark:bg-brand-950/20 mb-6">
            <h3 class="text-base font-bold text-gray-800 dark:text-white mb-2">Form Sinkronisasi Berbasis School ID</h3>
            <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">
                Pilih salah satu sekolah sumber. Sistem hanya akan mengimpor guru, siswa, kelas, dan akun milik sekolah tersebut. Data sekolah lain otomatis diisolasi sehingga sistem E-Rapor bersih dan fokus pada 1 sekolah.
            </p>

            <form action="{{ route('admin.sync.run') }}" method="POST" @submit="isSyncing = true" class="flex flex-col sm:flex-row items-center gap-3">
                @csrf
                <div class="w-full sm:flex-1">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Sekolah yang Ingin Disinkronkan:</label>
                    <select name="school_id" x-model="selectedSchoolId" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-xs font-medium text-gray-800 dark:text-white focus:outline-hidden focus:border-brand-500">
                        @forelse($sourceSchools as $sc)
                            <option value="{{ $sc['id'] }}">
                                [ID: {{ $sc['id'] }}] {{ $sc['name'] }} — {{ $sc['gurus_count'] }} Guru, {{ $sc['siswas_count'] }} Siswa ({{ $sc['kelases_count'] }} Rombel)
                            </option>
                        @empty
                            <option value="4">[ID: 4] SMKN 4 PANDEGLANG</option>
                            <option value="3">[ID: 3] SMPN 2 SAMPIT</option>
                            <option value="6">[ID: 6] SMK ASSUNIYAH TUMIJAJAR</option>
                            <option value="8">[ID: 8] MTsN 1 Kota Magelang</option>
                        @endforelse
                    </select>
                </div>

                <div class="w-full sm:w-48">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Mode Jalur:</label>
                    <select name="mode" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-xs font-medium text-gray-800 dark:text-white">
                        <option value="auto">Auto (Rekomendasi)</option>
                        <option value="db">Direct Database MySQL</option>
                        <option value="api">REST API (cbt-sync)</option>
                    </select>
                </div>

                <div class="w-full sm:w-auto pt-5">
                    <button type="submit" :disabled="isSyncing" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-indigo-600 px-6 py-2.5 text-xs font-bold text-white shadow-theme-md hover:from-brand-600 hover:to-indigo-700 transition disabled:opacity-50">
                        <svg class="w-4 h-4" :class="{ 'animate-spin': isSyncing }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span x-text="isSyncing ? 'Memproses Data...' : 'Mulai Sinkronkan Sekolah Ini'"></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Connection Status Cards -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 mb-6">
            <!-- Database Connection Status -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-500 uppercase">Jalur 1: Database Sumber Lokal</span>
                    @if($sourceDbStatus)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Online & Terhubung
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">
                            <span class="h-2 w-2 rounded-full bg-rose-500"></span> Tidak Terhubung
                        </span>
                    @endif
                </div>
                <div class="text-sm font-semibold text-gray-800 dark:text-white">Database: <code class="font-mono text-brand-600 bg-brand-50 px-1.5 py-0.5 rounded text-xs dark:bg-brand-500/10 dark:text-brand-400">absen_jagat</code> (MySQL 127.0.0.1:3306)</div>
                <p class="mt-2 text-xs text-gray-500">Koneksi langsung antar-database lokal dengan performa super cepat (ribuan data dalam hitungan detik).</p>
            </div>

            <!-- CBT Exam Web Connection Status -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-500 uppercase">Jalur 2: CBT Exam Web (Database)</span>
                    @if(!empty($examSummary['connected']))
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-500/10 dark:text-purple-400">
                            <span class="h-2 w-2 rounded-full bg-purple-500"></span> CBT Online & Terhubung
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">
                            <span class="h-2 w-2 rounded-full bg-rose-500"></span> CBT Tidak Terhubung
                        </span>
                    @endif
                </div>
                <div class="text-sm font-semibold text-gray-800 dark:text-white">Database: <code class="font-mono text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded text-xs dark:bg-purple-500/10 dark:text-purple-400">exam_web</code> (MySQL 127.0.0.1:3306)</div>
                <p class="mt-2 text-xs text-gray-500">Menyediakan penarikan nilai Asesmen Akhir Semester (SAS / PAS) secara langsung dari sistem ujian online CBT.</p>
            </div>
        </div>

        <!-- Panel Khusus Sinkronisasi CBT Exam Web -->
        <div class="rounded-2xl border border-purple-200 bg-purple-50/40 p-6 shadow-theme-xs dark:border-purple-900/50 dark:bg-purple-950/20 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-laptop-file text-purple-600 text-lg"></i>
                        <span>Sinkronisasi Nilai CBT Exam Web (Ujian Akhir Semester / SAS)</span>
                    </h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        Tarik nilai siswa yang telah menyelesaikan ujian SAS dari database CBT. Nilai akan otomatis masuk ke kolom nilai SAS dan mengkalkulasi Nilai Akhir Rapor.
                    </p>
                </div>
                <form action="{{ route('admin.sync.exam') }}" method="POST">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $currentSchool->id ?? '' }}">
                    <button type="submit"
                        onclick="return confirm('Tarik seluruh nilai ujian SAS dari Exam Web ke E-Rapor?')"
                        class="inline-flex items-center gap-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 text-xs font-bold shadow-theme-sm transition">
                        <i class="fa-solid fa-cloud-arrow-down text-sm"></i>
                        <span>Tarik Nilai SAS Massal</span>
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4 pt-4 border-t border-purple-100 dark:border-purple-900/40">
                <div class="rounded-xl bg-white dark:bg-gray-900 p-3.5 border border-purple-100 dark:border-purple-900/30">
                    <div class="text-[11px] font-semibold text-gray-400 uppercase">Ujian SAS Terdeteksi</div>
                    <div class="text-lg font-bold text-gray-800 dark:text-white mt-0.5">
                        {{ $examSummary['total_ujian'] ?? 0 }} Pelaksanaan
                    </div>
                </div>
                <div class="rounded-xl bg-white dark:bg-gray-900 p-3.5 border border-purple-100 dark:border-purple-900/30">
                    <div class="text-[11px] font-semibold text-gray-400 uppercase">Siswa Selesai Ujian</div>
                    <div class="text-lg font-bold text-purple-600 dark:text-purple-400 mt-0.5">
                        {{ $examSummary['total_peserta_selesai'] ?? 0 }} Nilai Siap Ditarik
                    </div>
                </div>
                <div class="rounded-xl bg-white dark:bg-gray-900 p-3.5 border border-purple-100 dark:border-purple-900/30">
                    <div class="text-[11px] font-semibold text-gray-400 uppercase">Status Integrasi</div>
                    <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-check-circle"></i> Terhubung & Siap
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Khusus Sinkronisasi Absensi dari Web Absen (REST API) -->
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-6 shadow-theme-xs dark:border-emerald-900/50 dark:bg-emerald-950/20 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-calendar-check text-emerald-600 text-lg"></i>
                        <span>Sinkronisasi Rekap Absensi 1 Semester (Backend Web Absen)</span>
                    </h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        Tentukan rentang tanggal 1 semester (mulai tgl s/d selesai) untuk menarik rekapitulasi kehadiran (Total Masuk, Sakit, Izin, Alpha, Bolos) via REST API Backend Web Absen.
                    </p>
                </div>
                <div class="flex items-center gap-2.5 shrink-0">
                    <a href="{{ route('admin.absensi.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 text-xs font-bold shadow-theme-sm transition">
                        <i class="fa-solid fa-calendar-days text-sm"></i>
                        <span>Buka Rekap Absensi Semester</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Data Comparison Table -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 mb-6">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
                Tabel Komparasi Data Sekolah Aktif: <u>{{ $currentSchool->name ?? 'Belum Ada' }}</u>
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-gray-600 dark:text-gray-400">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 uppercase text-gray-400 font-medium">
                            <th class="py-3 px-4 text-start">Entitas Data</th>
                            <th class="py-3 px-4 text-center">Data di Web Sumber (Sekolah Terpilih)</th>
                            <th class="py-3 px-4 text-center">Data di E-Rapor</th>
                            <th class="py-3 px-4 text-center">Status Sinkron</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-gray-800 dark:text-white">
                                <i class="fa-solid fa-chalkboard-user text-brand-600 me-2"></i>Data Guru & Pendidik
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-gray-700 dark:text-gray-300">{{ number_format($sourceCounts['gurus']) }}</td>
                            <td class="py-3.5 px-4 text-center font-bold text-brand-600">{{ number_format($localCounts['gurus']) }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10">Tersinkron</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-gray-800 dark:text-white">
                                <i class="fa-solid fa-graduation-cap text-indigo-600 me-2"></i>Data Siswa (Peserta Didik)
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-gray-700 dark:text-gray-300">{{ number_format($sourceCounts['siswas']) }}</td>
                            <td class="py-3.5 px-4 text-center font-bold text-brand-600">{{ number_format($localCounts['siswas']) }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10">Tersinkron</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-gray-800 dark:text-white">
                                <i class="fa-solid fa-school text-emerald-600 me-2"></i>Rombongan Belajar (Kelas)
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-gray-700 dark:text-gray-300">{{ number_format($sourceCounts['kelases']) }}</td>
                            <td class="py-3.5 px-4 text-center font-bold text-brand-600">{{ number_format($localCounts['kelases']) }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10">Tersinkron</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-gray-800 dark:text-white">
                                <i class="fa-solid fa-book-open text-amber-600 me-2"></i>Mata Pelajaran (Mapel)
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-gray-700 dark:text-gray-300">{{ number_format($sourceCounts['mapels']) }}</td>
                            <td class="py-3.5 px-4 text-center font-bold text-brand-600">{{ number_format($localCounts['mapels']) }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10">Tersinkron</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-gray-800 dark:text-white">
                                <i class="fa-solid fa-key text-purple-600 me-2"></i>Akun Pengguna Terbuat
                            </td>
                            <td class="py-3.5 px-4 text-center text-gray-400">-</td>
                            <td class="py-3.5 px-4 text-center font-bold text-emerald-600">{{ number_format($localCounts['users']) }} Akun</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10">Otomatis Aktif</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sync History Logs -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Riwayat Log Sinkronisasi</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-gray-600 dark:text-gray-400">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 uppercase text-gray-400 font-medium">
                            <th class="py-3 px-4 text-start">Waktu</th>
                            <th class="py-3 px-4 text-start">Jalur Sumber</th>
                            <th class="py-3 px-4 text-start">Pesan Hasil</th>
                            <th class="py-3 px-4 text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($logs as $l)
                            <tr>
                                <td class="py-3 px-4 font-mono">{{ $l->created_at->format('d/m/Y H:i:s') }}</td>
                                <td class="py-3 px-4 font-medium text-gray-800 dark:text-gray-200">{{ $l->source_url ?? 'Auto' }}</td>
                                <td class="py-3 px-4">{{ $l->message }}</td>
                                <td class="py-3 px-4 text-end">
                                    @if($l->status === 'success')
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:bg-emerald-500/10">Sukses</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-600 dark:bg-rose-500/10">Gagal</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-gray-500">Belum ada riwayat sinkronisasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
