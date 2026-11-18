@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-stamp text-brand-600"></i>
                <span>Pengaturan Cetak Rapor & Tanda Tangan</span>
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Kelola titimangsa (tempat & tanggal cetak rapor), tanda tangan digital Kepala Sekolah, Stempel Sekolah, dan TTD Wali Kelas.
            </p>
        </div>
        <div>
            <a href="{{ route('rapor.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs transition">
                <i class="fa-solid fa-print"></i>
                <span>Buka Menu Cetak Rapor</span>
            </a>
        </div>
    </div>

    <!-- Alert Success / Error -->
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-medium text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300 flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-medium text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300 flex items-center gap-2">
            <i class="fa-solid fa-circle-xmark text-rose-600 dark:text-rose-400 text-sm"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-medium text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- 1. Form Titimangsa & Kepala Sekolah -->
        <div class="lg:col-span-6 space-y-6">
            <form action="{{ route('admin.rapor-setting.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Card Tahun Ajaran & Semester Aktif -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-800 mb-4">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                            <i class="fa-solid fa-graduation-cap text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-800 dark:text-white">Tahun Ajaran & Semester Aktif</h2>
                            <p class="text-[11px] text-gray-400">Periode akademik yang aktif tercetak di seluruh dokumen rapor dan rekap nilai</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                Tahun Ajaran Aktif <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="tahun_ajaran" value="{{ old('tahun_ajaran', $school->tahun_ajaran ?? '2025/2026') }}" required
                                placeholder="Contoh: 2025/2026"
                                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                            <span class="text-[10px] text-gray-400 mt-1 block">Format baku: 2024/2025, 2025/2026</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                Semester Aktif <span class="text-rose-500">*</span>
                            </label>
                            <select name="semester" required
                                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs font-semibold text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                                <option value="2" {{ old('semester', $school->semester ?? '2') == '2' || old('semester', $school->semester ?? '2') == 'Genap' ? 'selected' : '' }}>
                                    Semester 2 (Genap)
                                </option>
                                <option value="1" {{ old('semester', $school->semester ?? '2') == '1' || old('semester', $school->semester ?? '2') == 'Ganjil' ? 'selected' : '' }}>
                                    Semester 1 (Ganjil)
                                </option>
                            </select>
                            <span class="text-[10px] text-gray-400 mt-1 block">Tercetak pada header rapor: "Semester : 2"</span>
                        </div>
                    </div>
                </div>

                <!-- Card Titimangsa Rapor -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-800 mb-4">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                            <i class="fa-solid fa-calendar-day text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-800 dark:text-white">Titimangsa Rapor</h2>
                            <p class="text-[11px] text-gray-400">Lokasi & tanggal penandatanganan pada dokumen rapor</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                Tempat / Lokasi TTD <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="titimangsa_tempat" value="{{ old('titimangsa_tempat', $school->titimangsa_tempat ?? 'Tumijajar') }}" required
                                placeholder="Contoh: Tumijajar"
                                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                            <span class="text-[10px] text-gray-400 mt-1 block">Tercetak: "[Tempat], [Tanggal]"</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                Tanggal Rapor <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="titimangsa_tanggal" value="{{ old('titimangsa_tanggal', $school->titimangsa_tanggal ? $school->titimangsa_tanggal->format('Y-m-d') : '2026-06-13') }}" required
                                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                            <span class="text-[10px] text-gray-400 mt-1 block">Contoh hasil: 13 Juni 2026</span>
                        </div>
                    </div>
                </div>

                <!-- Card Kepala Sekolah & Stempel -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-800 mb-4">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <i class="fa-solid fa-user-tie text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-800 dark:text-white">Kepala Sekolah & Stempel</h2>
                            <p class="text-[11px] text-gray-400">Identitas dan tanda tangan digital Kepala Sekolah</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                    Nama Kepala Sekolah <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="headmaster_name" value="{{ old('headmaster_name', $school->headmaster_name ?? 'Mahsun, M.Pd.') }}" required
                                    placeholder="Nama lengkap beserta gelar"
                                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                    NIP Kepala Sekolah
                                </label>
                                <input type="text" name="headmaster_nip" value="{{ old('headmaster_nip', $school->headmaster_nip ?? '-') }}"
                                    placeholder="NIP jika ada, atau '-' jika non-PNS"
                                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                            </div>
                        </div>

                        <!-- Upload TTD Kepsek & Stempel -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <!-- TTD Kepsek -->
                            <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/40">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                    Tanda Tangan Kepala Sekolah (PNG Transparan)
                                </label>
                                @if($school->headmaster_signature)
                                    <div class="mb-2.5 p-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg flex items-center justify-center h-24">
                                        <img src="{{ asset($school->headmaster_signature) }}" alt="TTD Kepsek" class="max-h-20 object-contain">
                                    </div>
                                @else
                                    <div class="mb-2.5 p-2 bg-white dark:bg-gray-900 border border-dashed border-gray-300 dark:border-gray-700 rounded-lg flex items-center justify-center h-24 text-[11px] text-gray-400">
                                        Belum ada TTD (klik browse untuk upload)
                                    </div>
                                @endif
                                <input type="file" name="headmaster_signature" accept="image/png,image/jpeg,image/webp"
                                    class="block w-full text-[11px] text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-500/10 dark:file:text-brand-300">
                            </div>

                            <!-- Stempel Sekolah -->
                            <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/40">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                    Stempel Resmi Sekolah (PNG Transparan)
                                </label>
                                @if($school->school_stamp)
                                    <div class="mb-2.5 p-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg flex items-center justify-center h-24">
                                        <img src="{{ asset($school->school_stamp) }}" alt="Stempel Sekolah" class="max-h-20 object-contain">
                                    </div>
                                @else
                                    <div class="mb-2.5 p-2 bg-white dark:bg-gray-900 border border-dashed border-gray-300 dark:border-gray-700 rounded-lg flex items-center justify-center h-24 text-[11px] text-gray-400">
                                        Belum ada Stempel (klik browse untuk upload)
                                    </div>
                                @endif
                                <input type="file" name="school_stamp" accept="image/png,image/jpeg,image/webp"
                                    class="block w-full text-[11px] text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-500/10 dark:file:text-brand-300">
                            </div>
                        </div>

                        <!-- Live Preview TTD + Stempel Overlay -->
                        @if($school->headmaster_signature || $school->school_stamp)
                            <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50/40 p-3 dark:border-blue-900/30 dark:bg-blue-950/20">
                                <div class="text-[11px] font-bold text-blue-700 dark:text-blue-300 mb-2 flex items-center gap-1.5">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                    <span>Preview Tampilan Pada Rapor (Tumpang Tindih Stempel & TTD):</span>
                                </div>
                                <div class="flex justify-center py-2">
                                    <div class="text-center w-56">
                                        <div class="text-xs text-gray-600 dark:text-gray-300">Mengetahui,</div>
                                        <div class="text-xs font-semibold text-gray-800 dark:text-white">Kepala Sekolah</div>
                                        
                                        <!-- Container Stempel & TTD Overlay -->
                                        <div class="relative h-20 w-44 mx-auto my-1 flex items-center justify-center">
                                            @if($school->school_stamp)
                                                <img src="{{ asset($school->school_stamp) }}" alt="Stempel"
                                                    class="absolute start-0 top-0 h-20 w-20 object-contain opacity-80 pointer-events-none z-1">
                                            @endif
                                            @if($school->headmaster_signature)
                                                <img src="{{ asset($school->headmaster_signature) }}" alt="TTD Kepsek"
                                                    class="relative h-18 object-contain z-9">
                                            @endif
                                        </div>

                                        <div class="font-bold underline text-xs text-gray-900 dark:text-white">{{ $school->headmaster_name }}</div>
                                        <div class="text-[10px] text-gray-500">NIP. {{ $school->headmaster_nip ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="pt-2 flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 px-5 py-2.5 text-xs font-semibold text-white shadow-theme-xs transition">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Simpan Titimangsa & Kepala Sekolah</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- 2. Daftar Tanda Tangan Wali Kelas -->
        <div class="lg:col-span-6 space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800 mb-4">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">
                            <i class="fa-solid fa-signature text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-800 dark:text-white">Tanda Tangan Wali Kelas</h2>
                            <p class="text-[11px] text-gray-400">Upload scan / gambar tanda tangan digital untuk masing-masing Wali Kelas</p>
                        </div>
                    </div>
                    <span class="rounded-lg bg-gray-100 dark:bg-gray-800 px-2.5 py-1 text-[11px] font-mono text-gray-600 dark:text-gray-300">
                        {{ $waliKelasGurus->count() }} Walas
                    </span>
                </div>

                <div class="space-y-3.5 max-h-[650px] overflow-y-auto pe-1">
                    @forelse($waliKelasGurus as $walas)
                        @php
                            $kelasBinaan = $walas->kelasWali ? $walas->kelasWali->pluck('nama_kelas')->join(', ') : ($walas->kelas_nama ?? '-');
                        @endphp
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700/80 p-3.5 bg-gray-50/50 dark:bg-gray-800/40">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="font-bold text-xs text-gray-900 dark:text-white flex items-center gap-2">
                                        <span>{{ $walas->nama }}</span>
                                        <span class="rounded bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 px-1.5 py-0.5 text-[10px] font-semibold">
                                            {{ $kelasBinaan ?: 'Wali Kelas' }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        NIP: {{ $walas->nip ?: '-' }} | Kode: {{ $walas->kode_guru ?: '-' }}
                                    </div>
                                </div>

                                <!-- Preview TTD Saat Ini -->
                                <div class="shrink-0">
                                    @if($walas->signature)
                                        <div class="flex items-center gap-2">
                                            <div class="h-12 w-24 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center justify-center p-1">
                                                <img src="{{ asset($walas->signature) }}" alt="TTD {{ $walas->nama }}" class="max-h-10 object-contain">
                                            </div>
                                            <form action="{{ route('admin.rapor-setting.delete-walas-signature', $walas->id) }}" method="POST" onsubmit="return confirm('Hapus TTD guru ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-700 text-xs" title="Hapus TTD">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] text-amber-600 bg-amber-50 dark:bg-amber-500/10 px-2 py-1 rounded-md font-medium">
                                            <i class="fa-solid fa-circle-exclamation text-[9px]"></i> Belum Ada TTD
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Upload Form -->
                            <form action="{{ route('admin.rapor-setting.upload-walas', $walas->id) }}" method="POST" enctype="multipart/form-data" class="mt-2.5 pt-2 border-t border-gray-100 dark:border-gray-700/60 flex items-center gap-2">
                                @csrf
                                <input type="file" name="signature" accept="image/png,image/jpeg,image/webp" required
                                    class="block w-full text-[10px] text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-semibold file:bg-gray-200 dark:file:bg-gray-700 file:text-gray-700 dark:file:text-gray-300 hover:file:bg-gray-300">
                                <button type="submit" class="shrink-0 rounded-lg bg-gray-800 hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 px-3 py-1 text-[11px] font-semibold text-white transition">
                                    Upload
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="py-8 text-center text-xs text-gray-400">
                            Belum ada guru wali kelas terdaftar di database.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

