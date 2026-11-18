@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{
    modalCatatan: false,
    modalKokurikuler: false,
    selectedSiswa: null,
    catatanText: '',
    kokurikulerText: '',
    kenaikanText: '',
    actionUrl: '',

    // State untuk Kokurikuler P5 (Kurikulum Merdeka)
    p5Tema: 'Kebekerjaan',
    p5NamaProjek: '',
    p5Dimensi: ['Mandiri', 'Bergotong Royong', 'Bernalar Kritis'],
    p5Narasi: '',

    openModal(siswa, catatan, kokurikuler, kenaikan, url) {
        this.selectedSiswa = siswa;
        this.catatanText = catatan || '';
        this.kokurikulerText = kokurikuler || '';
        this.kenaikanText = kenaikan || '';
        this.actionUrl = url;
        this.modalCatatan = true;
    },

    toggleDimensi(dim) {
        if (this.p5Dimensi.includes(dim)) {
            this.p5Dimensi = this.p5Dimensi.filter(d => d !== dim);
        } else {
            this.p5Dimensi.push(dim);
        }
        this.generateP5Narasi();
    },

    generateP5Narasi() {
        let tema = this.p5Tema;
        let projek = this.p5NamaProjek.trim() || 'Projek Penguatan Profil Pelajar Pancasila';
        let dimensi = this.p5Dimensi.length > 0 ? this.p5Dimensi.join(', ') : 'Mandiri dan Bergotong Royong';
        this.p5Narasi = `Dalam projek '${projek}' dengan tema ${tema}, peserta didik menunjukkan perkembangan yang sangat baik dalam dimensi ${dimensi}, serta mampu berkolaborasi aktif dan menyelesaikan tantangan projek dengan penuh tanggung jawab.`;
    }
}">
    <!-- Header Page -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-brand-600"></i>
                <span>Cetak Dokumen Rapor (Laporan Hasil Belajar)</span>
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Generate dan cetak lembar rapor resmi per rombel kelas atau per peserta didik sesuai format standar Kurikulum Merdeka.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if($isAdminOrWaka)
                <a href="{{ route('admin.rapor-setting.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-theme-xs transition">
                    <i class="fa-solid fa-sliders text-brand-500"></i>
                    <span>Pengaturan Titimangsa & TTD</span>
                </a>
            @endif

            @if($selectedKelas && $siswas->isNotEmpty())
                <!-- Tombol Kelola Kokurikuler (P5) -->
                <button type="button" @click="modalKokurikuler = true; if(!p5Narasi) generateP5Narasi();"
                    class="inline-flex items-center gap-2 rounded-xl bg-purple-600 hover:bg-purple-700 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs transition">
                    <i class="fa-solid fa-shapes"></i>
                    <span>Kelola Kokurikuler (P5)</span>
                </button>

                <!-- Tombol Cetak 1 Kelas -->
                <a href="{{ route('rapor.print', ['kelas_id' => $selectedKelas->id, 'tahun_ajaran' => $currentTahunAjaran, 'semester' => $currentSemester]) }}" target="_blank"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs transition">
                    <i class="fa-solid fa-print"></i>
                    <span>Cetak Rapor 1 Kelas (Semua Siswa)</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Alert Messages -->
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

    <!-- Selector Kelas & Rombel Info -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
        <form method="GET" action="{{ route('rapor.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
            <div class="sm:col-span-4">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    Pilih Rombel / Kelas:
                </label>
                <select name="kelas_id" onchange="this.form.submit()"
                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs font-medium text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                    @forelse($kelasList as $k)
                        <option value="{{ $k->id }}" {{ $selectedKelas && $selectedKelas->id == $k->id ? 'selected' : '' }}>
                            {{ $k->nama_kelas }} (Tingkat {{ $k->tingkat }} - {{ $k->wali_kelas_nama ?: 'Wali Kelas Belum Diisi' }})
                        </option>
                    @empty
                        <option value="">Belum ada kelas yang dapat Anda akses</option>
                    @endforelse
                </select>
            </div>

            @if($selectedKelas)
                <div class="sm:col-span-8 flex flex-wrap items-center justify-between gap-3 bg-gray-50/70 dark:bg-gray-800/40 p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-4 text-xs">
                        <div>
                            <span class="text-gray-400 block text-[10px]">Wali Kelas</span>
                            <span class="font-bold text-gray-800 dark:text-white">{{ $selectedKelas->wali_kelas_nama ?: '-' }}</span>
                        </div>
                        <div class="border-s border-gray-200 dark:border-gray-700 ps-4">
                            <span class="text-gray-400 block text-[10px]">Tahun / Semester</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $school->tahun_ajaran ?? ($selectedKelas->tahun_ajaran ?? '2025/2026') }} (Semester {{ $school->semester ?? ($selectedKelas->semester ?? '2') }})</span>
                        </div>
                        <div class="border-s border-gray-200 dark:border-gray-700 ps-4">
                            <span class="text-gray-400 block text-[10px]">Fase / KKM</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $selectedKelas->fase ?? 'E' }} / {{ $selectedKelas->kkm ?? 75 }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 px-2.5 py-1 text-xs font-semibold">
                            {{ $summary['total_siswa'] }} Peserta Didik
                        </span>
                        <span class="rounded-lg bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-300 px-2.5 py-1 text-xs font-semibold">
                            {{ $summary['mapel_count'] }} Mata Pelajaran
                        </span>
                    </div>
                </div>
            @endif
        </form>
    </div>

    <!-- Student Table List -->
    @if($selectedKelas)
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-gray-600 dark:text-gray-400">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 text-[11px] uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400">
                            <th class="py-3 px-3 text-center w-12">No</th>
                            <th class="py-3 px-4 text-start min-w-[200px]">Peserta Didik</th>
                            <th class="py-3 px-3 text-center">Rekap Nilai Mapel</th>
                            <th class="py-3 px-3 text-center">Capaian Terisi</th>
                            <th class="py-3 px-3 text-center">Rata-rata NA</th>
                            <th class="py-3 px-3 text-center min-w-[130px]">Absensi (S/I/A)</th>
                            <th class="py-3 px-4 text-start min-w-[240px]">Catatan Walas, P5 & Kenaikan</th>
                            <th class="py-3 px-4 text-center w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($siswas as $idx => $s)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                <td class="py-3 px-3 text-center font-medium text-gray-400">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $s->nama }}</div>
                                    <div class="text-[11px] text-gray-400 font-mono mt-0.5">NIS: {{ $s->nis ?: '-' }} | NISN: {{ $s->nisn ?: '-' }}</div>
                                </td>

                                <!-- Rekap Nilai -->
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-flex items-center gap-1 font-mono font-semibold {{ $s->total_nilai_terisi >= $summary['mapel_count'] ? 'text-emerald-600' : 'text-amber-600' }}">
                                        {{ $s->total_nilai_terisi }} / {{ $summary['mapel_count'] }} Mapel
                                    </span>
                                </td>

                                <!-- Capaian Terisi -->
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-flex items-center gap-1 font-mono font-medium {{ $s->total_capaian_terisi >= $summary['mapel_count'] ? 'text-emerald-600' : 'text-gray-500' }}">
                                        {{ $s->total_capaian_terisi }} / {{ $summary['mapel_count'] }}
                                    </span>
                                </td>

                                <!-- Rata-rata Nilai Akhir -->
                                <td class="py-3 px-3 text-center">
                                    @if($s->rata_rata_akhir)
                                        <span class="rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-0.5 font-mono font-bold text-gray-800 dark:text-white">
                                            {{ $s->rata_rata_akhir }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                <!-- Absensi -->
                                <td class="py-3 px-3 text-center">
                                    @if($s->absensi)
                                        <div class="inline-flex items-center gap-2 text-[11px] font-mono">
                                            <span class="text-blue-600" title="Sakit">S: {{ $s->absensi->sakit }}</span>
                                            <span class="text-amber-600" title="Izin">I: {{ $s->absensi->izin }}</span>
                                            <span class="text-rose-600 font-bold" title="Alpha">A: {{ $s->absensi->alpha }}</span>
                                        </div>
                                    @else
                                        <span class="text-[11px] text-gray-400 italic">Belum disinkron</span>
                                    @endif
                                </td>

                                <!-- Catatan Walas, Kokurikuler (P5) & Kenaikan -->
                                <td class="py-3 px-4">
                                    <div class="text-[11px] text-gray-700 dark:text-gray-300 line-clamp-1 italic">
                                        "{{ $s->absensi && $s->absensi->catatan_wali_kelas ? $s->absensi->catatan_wali_kelas : 'Belum ada catatan...' }}"
                                    </div>
                                    
                                    <!-- Kokurikuler Badge -->
                                    <div class="mt-1">
                                        @if($s->absensi && $s->absensi->catatan_kokurikuler)
                                            <div class="text-[10px] text-purple-600 dark:text-purple-400 font-medium truncate" title="Kokurikuler: {{ $s->absensi->catatan_kokurikuler }}">
                                                <i class="fa-solid fa-shapes text-[9px] me-1"></i>P5: {{ Str::limit($s->absensi->catatan_kokurikuler, 38) }}
                                            </div>
                                        @else
                                            <div class="text-[10px] text-gray-400 italic">
                                                <i class="fa-solid fa-shapes text-[9px] me-1"></i>P5 belum diisi
                                            </div>
                                        @endif
                                    </div>

                                    @if($s->absensi && $s->absensi->kenaikan_kelas)
                                        <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">
                                            <i class="fa-solid fa-turn-up text-[9px]"></i> {{ $s->absensi->kenaikan_kelas }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Edit Catatan Walas & Kokurikuler -->
                                        <button type="button"
                                            @click="openModal('{{ addslashes($s->nama) }}', '{{ addslashes($s->absensi ? $s->absensi->catatan_wali_kelas : '') }}', '{{ addslashes($s->absensi ? $s->absensi->catatan_kokurikuler : '') }}', '{{ addslashes($s->absensi ? $s->absensi->kenaikan_kelas : '') }}', '{{ route('rapor.catatan', $s->id) }}')"
                                            class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:text-brand-600 hover:border-brand-500 transition shadow-theme-xs"
                                            title="Edit Catatan Walas, Kokurikuler, & Kenaikan">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>

                                        <!-- Cetak Rapor Per Siswa -->
                                        <a href="{{ route('rapor.print', ['siswa_id' => $s->id, 'tahun_ajaran' => $currentTahunAjaran, 'semester' => $currentSemester]) }}" target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-semibold text-[11px] transition shadow-theme-xs"
                                            title="Cetak Rapor Siswa Ini">
                                            <i class="fa-solid fa-print"></i>
                                            <span>Cetak</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-gray-400">
                                    Tidak ada peserta didik terdaftar di rombel kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- MODAL 1: Kelola Kokurikuler (P5) Massal Sesuai Panduan Kurikulum Merdeka -->
    @if($selectedKelas)
        <div x-show="modalKokurikuler" x-cloak class="fixed inset-0 z-999 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
            @keydown.escape.window="modalKokurikuler = false">
            <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-gray-900 shadow-theme-xl border border-gray-200 dark:border-gray-800 p-6 space-y-4 max-h-[90vh] overflow-y-auto"
                @click.outside="modalKokurikuler = false">
                
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">
                            <i class="fa-solid fa-shapes text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                Input Projek Kokurikuler (P5) - Rombel {{ $selectedKelas->nama_kelas }}
                            </h3>
                            <p class="text-[11px] text-gray-400">Panduan Projek Penguatan Profil Pelajar Pancasila (P5) Kurikulum Merdeka</p>
                        </div>
                    </div>
                    <button type="button" @click="modalKokurikuler = false" class="text-gray-400 hover:text-gray-600 text-sm">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form action="{{ route('rapor.kokurikuler', $selectedKelas->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="tahun_ajaran" value="{{ $currentTahunAjaran }}">
                    <input type="hidden" name="semester" value="{{ $currentSemester }}">

                    <!-- 1. Pilihan Tema P5 Resmi Kurikulum Merdeka -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Tema Projek P5 (Kemdikbudristek):
                        </label>
                        <select x-model="p5Tema" @change="generateP5Narasi()"
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs font-medium text-gray-800 dark:text-white focus:border-purple-500 focus:outline-hidden">
                            <option value="Kebekerjaan">1. Kebekerjaan (Budaya Kerja 5R, K3LH, & Etika Profesi)</option>
                            <option value="Gaya Hidup Berkelanjutan">2. Gaya Hidup Berkelanjutan (Kelestarian Alam & Pengelolaan Lingkungan)</option>
                            <option value="Kearifan Lokal">3. Kearifan Lokal (Eksplorasi Budaya & Tradisi Daerah)</option>
                            <option value="Bhinneka Tunggal Ika">4. Bhinneka Tunggal Ika (Menghargai Keberagaman & Toleransi)</option>
                            <option value="Bangunlah Jiwa dan Raganya">5. Bangunlah Jiwa dan Raganya (Kesehatan Mental & Jasmani)</option>
                            <option value="Suara Demokrasi">6. Suara Demokrasi (Keterlibatan Sosial & Partisipasi Aktif)</option>
                            <option value="Rekayasa dan Teknologi">7. Rekayasa dan Teknologi (Inovasi & Teknologi Tepat Guna)</option>
                            <option value="Kewirausahaan">8. Kewirausahaan (Mindset Bisnis, Produk Kreatif, & Pasar)</option>
                        </select>
                    </div>

                    <!-- 2. Nama / Judul Projek -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Judul / Nama Projek yang Dijalankan:
                        </label>
                        <input type="text" x-model="p5NamaProjek" @input="generateP5Narasi()"
                            placeholder="Contoh: Penerapan Budaya Kerja 5R dan Pengelolaan Limbah Bengkel"
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs text-gray-800 dark:text-white focus:border-purple-500 focus:outline-hidden">
                    </div>

                    <!-- 3. Dimensi Profil Pelajar Pancasila -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Dimensi Profil Pelajar Pancasila yang Ditargetkan:
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                            @foreach([
                                'Beriman & Bertakwa',
                                'Mandiri',
                                'Bernalar Kritis',
                                'Bergotong Royong',
                                'Kreatif',
                                'Berkebinekaan Global'
                            ] as $dim)
                                <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 cursor-pointer hover:bg-purple-50 dark:hover:bg-purple-950/20 transition">
                                    <input type="checkbox" :checked="p5Dimensi.includes('{{ $dim }}')" @change="toggleDimensi('{{ $dim }}')"
                                        class="rounded text-purple-600 focus:ring-purple-500">
                                    <span class="text-[11px] text-gray-700 dark:text-gray-300 font-medium">{{ $dim }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- 4. Textarea Narasi Kokurikuler Rapor -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Narasi Catatan Kokurikuler (Tercetak di Kotak Kokurikuler Rapor):
                            </label>
                            <button type="button" @click="generateP5Narasi()" class="text-[11px] font-semibold text-purple-600 hover:text-purple-700 flex items-center gap-1">
                                <i class="fa-solid fa-arrows-rotate text-[10px]"></i> Reset ke Format Panduan
                            </button>
                        </div>
                        <textarea name="narasi_kokurikuler" x-model="p5Narasi" rows="3" required
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs text-gray-800 dark:text-white focus:border-purple-500 focus:outline-hidden leading-relaxed"></textarea>
                        <span class="text-[10px] text-gray-400 mt-1 block">
                            Narasi ini akan dicetak pada kotak <strong>Kokurikuler</strong> di lembar rapor halaman 2 untuk seluruh siswa kelas ini. Anda tetap dapat mengedit per-siswa jika ada catatan individual.
                        </span>
                    </div>

                    <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" @click="modalKokurikuler = false" class="rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            Batal
                        </button>
                        <button type="submit" class="rounded-xl bg-purple-600 hover:bg-purple-700 px-5 py-2 text-xs font-semibold text-white shadow-theme-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-check-double text-xs"></i>
                            <span>Terapkan ke Seluruh Siswa Kelas Ini</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 2: Edit Catatan Walas, Kokurikuler (P5) & Kenaikan Per Siswa -->
    <div x-show="modalCatatan" x-cloak class="fixed inset-0 z-999 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
        @keydown.escape.window="modalCatatan = false">
        <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-gray-900 shadow-theme-xl border border-gray-200 dark:border-gray-800 p-6 space-y-4 max-h-[90vh] overflow-y-auto"
            @click.outside="modalCatatan = false">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-comment-pen text-brand-600"></i>
                    <span>Catatan Rapor Siswa: <span x-text="selectedSiswa"></span></span>
                </h3>
                <button type="button" @click="modalCatatan = false" class="text-gray-400 hover:text-gray-600 text-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form :action="actionUrl" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="tahun_ajaran" value="{{ $currentTahunAjaran }}">
                <input type="hidden" name="semester" value="{{ $currentSemester }}">
                <!-- Catatan Wali Kelas -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        Catatan Khusus Wali Kelas:
                    </label>
                    <textarea name="catatan_wali_kelas" x-model="catatanText" rows="2"
                        placeholder="Contoh: Tingkatkan terus prestasimu dan pertahankan semangat belajar di semester berikutnya."
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden"></textarea>
                </div>

                <!-- Catatan Kokurikuler (P5) Individual -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-shapes text-purple-500 text-xs"></i>
                        <span>Catatan Projek Kokurikuler (P5) Siswa Ini:</span>
                    </label>
                    <textarea name="catatan_kokurikuler" x-model="kokurikulerText" rows="2"
                        placeholder="Deskripsi pencapaian projek kokurikuler peserta didik..."
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs text-gray-800 dark:text-white focus:border-purple-500 focus:outline-hidden"></textarea>
                </div>

                <!-- Keterangan Kenaikan Kelas -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        Keterangan Kenaikan Kelas (Semester Genap):
                    </label>
                    <input type="text" name="kenaikan_kelas" x-model="kenaikanText"
                        placeholder="Contoh: Naik ke kelas XI (Sebelas) / Lulus"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-xs text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                    <span class="text-[10px] text-gray-400 mt-1 block">Kosongkan jika masih semester ganjil atau belum rapat pleno.</span>
                </div>

                <div class="flex justify-end gap-2.5 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="modalCatatan = false" class="rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        Batal
                    </button>
                    <button type="submit" class="rounded-xl bg-brand-600 hover:bg-brand-700 px-5 py-2 text-xs font-semibold text-white transition">
                        Simpan Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
