@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6 space-y-6" x-data="{
    defaultKkm: 75,
    setAll(val) {
        document.querySelectorAll('.kkm-input').forEach(el => {
            el.value = val;
        });
    }
}">

    <!-- Header Section -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-sliders text-brand-500"></i>
                <span>Pengaturan KKM / Batas Ketercapaian Kelas</span>
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Atur Kriteria Ketercapaian Tujuan Pembelajaran (KKTP / KKM) untuk masing-masing kelas. Setiap kelas dapat memiliki batas KKM yang berbeda sesuai ketetapan kurikulum.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-xl bg-purple-50 dark:bg-purple-500/10 px-3.5 py-2 text-xs font-semibold text-purple-700 dark:text-purple-400">
                <i class="fa-solid fa-school"></i>
                <span>{{ $school->name ?? 'SMK Assuniyah' }}</span>
            </span>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 flex items-center gap-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Information & Quick Preset Box -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                <i class="fa-solid fa-circle-info text-base"></i>
            </div>
            <div>
                <h4 class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">Peran KKM dalam Rapor</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">
                    KKM kelas ini digunakan oleh sistem generator rapor untuk menentukan apakah siswa telah <strong>mencapai kompetensi</strong> ($\ge \text{KKM}$) atau <strong>memerlukan bimbingan/peningkatan</strong> ($< \text{KKM}$) pada setiap materi Tujuan Pembelajaran (TP).
                </p>
            </div>
        </div>

        <!-- Quick Presets -->
        <div class="flex items-center gap-2 shrink-0">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Set Massal:</span>
            <button type="button" @click="setAll(70)" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2.5 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100">
                70
            </button>
            <button type="button" @click="setAll(75)" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2.5 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100">
                75
            </button>
            <button type="button" @click="setAll(78)" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2.5 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100">
                78
            </button>
            <button type="button" @click="setAll(80)" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2.5 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100">
                80
            </button>
        </div>
    </div>

    <!-- Form Setting KKM Tiap Kelas -->
    <form action="{{ route('admin.kkm.update') }}" method="POST">
        @csrf

        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-gray-600 dark:text-gray-400">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 text-[11px] uppercase font-semibold text-gray-400">
                            <th class="py-3.5 px-4 text-center w-12">No</th>
                            <th class="py-3.5 px-4 text-start">Nama Rombel / Kelas</th>
                            <th class="py-3.5 px-3 text-center w-28">Tingkat</th>
                            <th class="py-3.5 px-4 text-start">Konsentrasi Keahlian / Jurusan</th>
                            <th class="py-3.5 px-4 text-start">Wali Kelas</th>
                            <th class="py-3.5 px-3 text-center w-28">Total Siswa</th>
                            <th class="py-3.5 px-4 text-center w-48 bg-brand-50/40 dark:bg-brand-500/5 text-brand-700 dark:text-brand-300">
                                Batas KKM Kelas *
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($kelases as $idx => $k)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                <td class="py-3.5 px-4 text-center font-medium text-gray-400">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="py-3.5 px-4 font-bold text-gray-800 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-chalkboard text-brand-500"></i>
                                        <span>{{ $k->nama_kelas }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Kelas {{ $k->tingkat }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                        {{ $k->jurusan ?: '-' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-gray-600 dark:text-gray-300">
                                        {{ $k->waliKelas?->nama ?: ($k->wali_kelas_nama ?: 'Belum Ditentukan') }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-center font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $k->siswas_count ?? ($k->siswas()->count() ?: $k->jumlah_siswa) }} Siswa
                                </td>
                                <td class="py-3 px-4 text-center bg-brand-50/20 dark:bg-brand-500/5">
                                    <div class="flex items-center justify-center gap-2">
                                        <input type="number" min="50" max="100" required
                                            name="kkm[{{ $k->id }}]"
                                            value="{{ $k->kkm ?? 75 }}"
                                            class="kkm-input w-24 text-center rounded-xl border border-brand-200 dark:border-brand-800 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-bold font-mono text-brand-600 dark:text-brand-400 shadow-sm focus:border-brand-500 focus:outline-hidden">
                                        <span class="text-xs font-semibold text-gray-400">/ 100</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-gray-400">
                                    Belum ada data rombel/kelas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Table Footer Action -->
            <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 flex items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Perubahan batas KKM akan langsung menjadi acuan generator deskripsi capaian rapor guru.
                </span>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-6 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    <span>Simpan Pengaturan KKM Seluruh Kelas</span>
                </button>
            </div>
        </div>
    </form>

</div>
@endsection

