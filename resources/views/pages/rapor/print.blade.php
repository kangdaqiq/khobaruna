<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Hasil Belajar (Rapor) - {{ $kelas->nama_kelas }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* Standar Ukuran Kertas A4 & Margin Cetak */
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.35;
            color: #000;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Container Halaman Rapor */
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 12mm 15mm 15mm 15mm;
            margin: 15px auto;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Running Footer pada setiap lembar halaman */
        .page-footer {
            margin-top: auto;
            padding-top: 8px;
            border-top: 0.5px solid #000;
            display: flex;
            justify-content: space-between;
            font-size: 9pt;
            font-style: italic;
            font-family: "Courier New", Courier, monospace;
        }

        /* Header Identitas Siswa */
        .student-header {
            width: 100%;
            margin-bottom: 8px;
            font-size: 10pt;
        }

        .student-header table {
            width: 100%;
            border-collapse: collapse;
        }

        .student-header td {
            vertical-align: top;
            padding: 1.5px 0;
        }

        .header-line {
            border-bottom: 1px solid #000;
            margin-top: 4px;
            margin-bottom: 8px;
        }

        .rapor-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin: 6px 0 10px 0;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Tabel Rapor Utama */
        table.table-rapor {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 12px;
            border: 1.5px solid #000;
        }

        table.table-rapor th,
        table.table-rapor td {
            border: 1px solid #000;
            padding: 5.5px 7px;
            vertical-align: middle;
        }

        table.table-rapor th {
            font-weight: bold;
            text-align: center;
            background-color: #eaecf0 !important;
            border-bottom: 1.5px solid #000;
            padding: 7px 8px;
            font-size: 9.5pt;
            letter-spacing: 0.3px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .bg-group {
            font-weight: bold;
            background-color: #f1f3f5 !important;
            padding: 6px 8px;
            border-top: 1.5px solid #000 !important;
            border-bottom: 1.5px solid #000 !important;
            font-size: 9.5pt;
            letter-spacing: 0.3px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Text utility */
        .text-center { text-align: center; }
        .text-start { text-align: left; }
        .text-end { text-align: right; }
        .font-bold { font-weight: bold; }

        /* Komponen Tambahan (Kokurikuler, Ekskul, Absensi, Catatan, Kenaikan) */
        .box-section {
            border: 1px solid #000;
            margin-bottom: 10px;
        }

        .box-section-title {
            border-bottom: 1px solid #000;
            text-align: center;
            font-weight: bold;
            font-size: 9.5pt;
            padding: 5px 8px;
            background-color: #f1f3f5 !important;
            letter-spacing: 0.3px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .box-section-content {
            padding: 8px 10px;
            font-size: 9.5pt;
        }

        /* Area Tanda Tangan */
        .signature-section {
            width: 100%;
            margin-top: 15px;
            font-size: 10pt;
        }

        .signature-block {
            text-align: center;
            width: 220px;
        }

        .signature-img-container {
            position: relative;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 2px 0;
        }

        .signature-img-container img.sig-img {
            max-height: 65px;
            max-width: 170px;
            object-contain: contain;
            position: relative;
            z-index: 2;
        }

        .signature-img-container img.stamp-img {
            position: absolute;
            height: 75px;
            width: 75px;
            left: 10px;
            top: -2px;
            opacity: 0.85;
            object-fit: contain;
            z-index: 1;
        }

        /* Floating Toolbar (Hanya di Layar, Tersembunyi saat Cetak) */
        .print-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #1e293b;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 999999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-family: system-ui, -apple-system, sans-serif;
        }

        .print-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }

        .print-btn:hover { background: #1d4ed8; }

        .secondary-btn {
            background: #475569;
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .secondary-btn:hover { background: #334155; }

        /* Media Print Rules */
        @media print {
            body {
                background: transparent;
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .print-toolbar {
                display: none !important;
            }

            .page {
                width: 100%;
                min-height: auto;
                height: 100vh;
                margin: 0;
                padding: 0;
                box-shadow: none;
                page-break-after: always;
                break-after: page;
            }

            table.table-rapor {
                border: 1.5px solid #000 !important;
            }

            table.table-rapor th {
                background-color: #eaecf0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .bg-group {
                background-color: #f1f3f5 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .box-section-title {
                background-color: #f1f3f5 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body style="padding-top: 60px;">

    <!-- Floating Action Bar (Hanya tampil di browser, otomatis hilang saat cetak/PDF) -->
    <div class="print-toolbar no-print">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="{{ route('rapor.index', ['kelas_id' => $kelas->id]) }}" class="secondary-btn">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Menu Rapor
            </a>
            <span style="font-size: 13px; font-weight: 600;">
                <i class="fa-solid fa-file-invoice" style="margin-right: 6px; color: #38bdf8;"></i>
                Cetak Rapor: {{ $kelas->nama_kelas }} ({{ $siswas->count() }} Peserta Didik)
            </span>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <button type="button" onclick="toggleSignatureMode()" id="toggleSigBtn" class="secondary-btn" title="Beralih antara TTD Digital dan Blank/Manual Pen">
                <i class="fa-solid fa-signature"></i>
                <span id="sigBtnText">Mode: TTD Digital Aktif</span>
            </button>
            <button type="button" onclick="window.print()" class="print-btn">
                <i class="fa-solid fa-print"></i> Cetak Dokumen / Simpan PDF (Ctrl + P)
            </button>
        </div>
    </div>

    @php
        $titimangsaTempat = $school->titimangsa_tempat ?? 'Tumijajar';
        $titimangsaTanggal = $school->titimangsa_tanggal 
            ? \Carbon\Carbon::parse($school->titimangsa_tanggal)->locale('id')->isoFormat('D MMMM Y')
            : '13 Juni 2026';
        
        $headmasterName = $school->headmaster_name ?? 'Mahsun, M.Pd.';
        $headmasterNip = $school->headmaster_nip ?? '-';
        $headmasterSig = $school->headmaster_signature;
        $schoolStamp = $school->school_stamp;

        $walasName = $waliKelas ? $waliKelas->nama : ($kelas->wali_kelas_nama ?? '-');
        $walasNip = $waliKelas ? ($waliKelas->nip ?: '-') : '-';
        $walasSig = $waliKelas ? $waliKelas->signature : null;

        // Semester & Tahun Ajaran
        $semStr = $selectedSemester ?? ($school->semester ?? ($kelas->semester ?? '2'));
        if (strtolower($semStr) === 'ganjil') $semStr = '1';
        if (strtolower($semStr) === 'genap') $semStr = '2';
        $tahunAjaranStr = $selectedTahunAjaran ?? ($school->tahun_ajaran ?? ($kelas->tahun_ajaran ?? '2025/2026'));
    @endphp

    <!-- Loop Tiap Siswa -->
    @foreach($siswas as $siswaIndex => $s)
        @php
            $sPenilaians = $penilaians->get($s->id, collect())->keyBy('mapel_id');
            $sAbsensi = $absensis->get($s->id);
            $nisStr = $s->nis ?: '-';
            $nisnStr = $s->nisn ?: '-';
            $footerLeft = "{$kelas->nama_kelas}  |  {$s->nama}  |  {$nisStr}";
        @endphp

        <!-- ========================================== -->
        <!-- HALAMAN 1 : NILAI MATA PELAJARAN UMUM      -->
        <!-- ========================================== -->
        <div class="page">
            <div>
                <!-- Header Siswa -->
                <div class="student-header">
                    <table>
                        <tr>
                            <td style="width: 14%;">Nama Murid</td>
                            <td style="width: 2%;">:</td>
                            <td style="width: 44%; font-weight: bold;">{{ strtoupper($s->nama) }}</td>
                            <td style="width: 16%;">Kelas</td>
                            <td style="width: 2%;">:</td>
                            <td style="width: 22%; font-weight: bold;">{{ $kelas->nama_kelas }}</td>
                        </tr>
                        <tr>
                            <td>NIS/NISN</td>
                            <td>:</td>
                            <td>{{ $nisStr }} / {{ $nisnStr }}</td>
                            <td>Fase</td>
                            <td>:</td>
                            <td>{{ $kelas->fase ?? 'E' }}</td>
                        </tr>
                        <tr>
                            <td>Sekolah</td>
                            <td>:</td>
                            <td style="font-weight: bold;">{{ strtoupper($school->nama_sekolah ?? 'SMKS ASSUNIYAH') }}</td>
                            <td>Semester</td>
                            <td>:</td>
                            <td>{{ $semStr }}</td>
                        </tr>
                        <tr>
                            <td>Alamat</td>
                            <td>:</td>
                            <td>{{ $school->alamat ?? 'JL. RAYA WAY ABUNG 2' }}</td>
                            <td>Tahun Ajaran</td>
                            <td>:</td>
                            <td>{{ $tahunAjaranStr }}</td>
                        </tr>
                    </table>
                </div>

                <div class="header-line"></div>

                <!-- Judul Dokumen -->
                <div class="rapor-title">LAPORAN HASIL BELAJAR</div>

                <!-- Tabel Nilai Rapor Halaman 1 -->
                <table class="table-rapor">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 28%;">Mata Pelajaran</th>
                            <th style="width: 12%;">Nilai Akhir</th>
                            <th style="width: 55%;">Capaian Kompetensi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Header Kelompok Umum -->
                        <tr>
                            <td colspan="4" class="bg-group">Mata Pelajaran Umum</td>
                        </tr>

                        @php $noUmum = 1; @endphp
                        @forelse($groupedMapels['umum'] as $m)
                            @php
                                $nilai = $sPenilaians->get($m->id);
                                $na = $nilai && !is_null($nilai->nilai_akhir) ? round($nilai->nilai_akhir) : '-';
                                $capaian = $nilai ? $nilai->capaian_kompetensi : null;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $noUmum++ }}</td>
                                <td style="font-weight: 500;">{{ $m->nama_mapel }}</td>
                                <td class="text-center font-bold" style="font-size: 10pt;">{{ $na }}</td>
                                <td style="text-align: {{ $capaian ? 'justify' : 'center' }}; line-height: 1.35; color: {{ $capaian ? '#000' : '#64748b' }};">{{ $capaian ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center" style="font-style: italic; color: #666;">
                                    Belum ada data mata pelajaran umum.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Running Footer Halaman 1 -->
            <div class="page-footer">
                <div>{{ $footerLeft }}</div>
                <div>Halaman : 1</div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- HALAMAN 2 : MAPEL KEJURUAN, KOKURIKULER, EKSKUL, ABSENSI & CATATAN  -->
        <!-- =================================================================== -->
        <div class="page">
            <div>
                <!-- Header Siswa (Berulang di setiap halaman) -->
                <div class="student-header">
                    <table>
                        <tr>
                            <td style="width: 14%;">Nama Murid</td>
                            <td style="width: 2%;">:</td>
                            <td style="width: 44%; font-weight: bold;">{{ strtoupper($s->nama) }}</td>
                            <td style="width: 16%;">Kelas</td>
                            <td style="width: 2%;">:</td>
                            <td style="width: 22%; font-weight: bold;">{{ $kelas->nama_kelas }}</td>
                        </tr>
                        <tr>
                            <td>NIS/NISN</td>
                            <td>:</td>
                            <td>{{ $nisStr }} / {{ $nisnStr }}</td>
                            <td>Fase</td>
                            <td>:</td>
                            <td>{{ $kelas->fase ?? 'E' }}</td>
                        </tr>
                        <tr>
                            <td>Sekolah</td>
                            <td>:</td>
                            <td style="font-weight: bold;">{{ strtoupper($school->nama_sekolah ?? 'SMKS ASSUNIYAH') }}</td>
                            <td>Semester</td>
                            <td>:</td>
                            <td>{{ $semStr }}</td>
                        </tr>
                        <tr>
                            <td>Alamat</td>
                            <td>:</td>
                            <td>{{ $school->alamat ?? 'JL. RAYA WAY ABUNG 2' }}</td>
                            <td>Tahun Ajaran</td>
                            <td>:</td>
                            <td>{{ $tahunAjaranStr }}</td>
                        </tr>
                    </table>
                </div>

                <div class="header-line"></div>

                <!-- Lanjutan Tabel Nilai (Kejuruan & Muatan Lokal) -->
                <table class="table-rapor">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 28%;">Mata Pelajaran</th>
                            <th style="width: 12%;">Nilai Akhir</th>
                            <th style="width: 55%;">Capaian Kompetensi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($groupedMapels['kejuruan']->isNotEmpty())
                            <tr>
                                <td colspan="4" class="bg-group">C2. Dasar Program Keahlian</td>
                            </tr>
                        @endif

                        @php $noKejuruan = 1; @endphp
                        @forelse($groupedMapels['kejuruan'] as $m)
                            @php
                                $nilai = $sPenilaians->get($m->id);
                                $na = $nilai && !is_null($nilai->nilai_akhir) ? round($nilai->nilai_akhir) : '-';
                                $capaian = $nilai ? $nilai->capaian_kompetensi : null;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $noKejuruan++ }}</td>
                                <td style="font-weight: 500;">{{ $m->nama_mapel }}</td>
                                <td class="text-center font-bold" style="font-size: 10pt;">{{ $na }}</td>
                                <td style="text-align: {{ $capaian ? 'justify' : 'center' }}; line-height: 1.35; color: {{ $capaian ? '#000' : '#64748b' }};">{{ $capaian ?: '-' }}</td>
                            </tr>
                        @empty
                            <!-- Jika tidak ada mapel kejuruan terpisah -->
                        @endforelse

                        <!-- Muatan Lokal Jika Ada -->
                        @if($groupedMapels['mulok']->isNotEmpty())
                            <tr>
                                <td colspan="4" class="bg-group">Muatan Lokal</td>
                            </tr>
                            @php $noMulok = 1; @endphp
                            @foreach($groupedMapels['mulok'] as $m)
                                @php
                                    $nilai = $sPenilaians->get($m->id);
                                    $na = $nilai && !is_null($nilai->nilai_akhir) ? round($nilai->nilai_akhir) : '-';
                                    $capaian = $nilai ? $nilai->capaian_kompetensi : null;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $noMulok++ }}</td>
                                    <td style="font-weight: 500;">{{ $m->nama_mapel }}</td>
                                    <td class="text-center font-bold" style="font-size: 10pt;">{{ $na }}</td>
                                    <td style="text-align: {{ $capaian ? 'justify' : 'center' }}; line-height: 1.35; color: {{ $capaian ? '#000' : '#64748b' }};">{{ $capaian ?: '-' }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>

                <!-- 1. Kokurikuler Box -->
                <div class="box-section">
                    <div class="box-section-title">Kokurikuler</div>
                    <div class="box-section-content" style="min-height: 55px; text-align: justify; line-height: 1.35; padding: 7px 10px;">
                        {{ $sAbsensi && !empty($sAbsensi->catatan_kokurikuler) ? $sAbsensi->catatan_kokurikuler : '' }}
                    </div>
                </div>

                <!-- 2. Ekstrakurikuler Table -->
                <table class="table-rapor" style="margin-bottom: 10px;">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 40%;">Ekstrakurikuler</th>
                            <th style="width: 55%;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">1</td>
                            <td style="height: 22px;"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="text-center">2</td>
                            <td style="height: 22px;"></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>

                <!-- 3. Ketidakhadiran & Catatan Wali Kelas (Side by Side) -->
                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                    <!-- Tabel Ketidakhadiran -->
                    <div style="width: 40%;">
                        <table class="table-rapor" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th colspan="2">Ketidakhadiran</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="width: 55%;">Sakit</td>
                                    <td style="width: 45%;">: {{ $sAbsensi ? $sAbsensi->sakit : 0 }} hari</td>
                                </tr>
                                <tr>
                                    <td>Izin</td>
                                    <td>: {{ $sAbsensi ? $sAbsensi->izin : 0 }} hari</td>
                                </tr>
                                <tr>
                                    <td>Tanpa Keterangan</td>
                                    <td>: {{ $sAbsensi ? $sAbsensi->alpha : 0 }} hari</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Box Catatan Wali Kelas -->
                    <div style="width: 60%;">
                        <div class="box-section" style="margin-bottom: 0; height: 100%; display: flex; flex-direction: column;">
                            <div class="box-section-title">Catatan Wali Kelas</div>
                            <div class="box-section-content" style="flex: 1; min-height: 60px;">
                                {{ $sAbsensi ? $sAbsensi->catatan_wali_kelas : '' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Keterangan Kenaikan Kelas -->
                <div class="box-section" style="padding: 7px 10px; font-weight: bold; font-size: 9.5pt; text-align: center; background-color: #f8fafc;">
                    Keterangan Kenaikan Kelas : {{ $sAbsensi && $sAbsensi->kenaikan_kelas ? $sAbsensi->kenaikan_kelas : 'Naik/Tidak Naik ke kelas .......' }}
                </div>

                <!-- 5. Tanggapan Orang Tua/Wali Murid -->
                <div class="box-section">
                    <div class="box-section-title">Tanggapan Orang Tua/Wali Murid</div>
                    <div class="box-section-content" style="min-height: 45px;">
                    </div>
                </div>

                <!-- Tanda Tangan Wali Kelas di Bagian Kanan Bawah Hal 2 -->
                <div style="display: flex; justify-content: flex-end; margin-top: 15px;">
                    <div class="signature-block">
                        <div>{{ $titimangsaTempat }}, {{ $titimangsaTanggal }}</div>
                        <div>Wali Kelas,</div>
                        
                        <div class="signature-img-container">
                            @if($walasSig)
                                <img src="{{ asset($walasSig) }}" alt="TTD Walas" class="sig-img sig-digital">
                            @endif
                        </div>

                        <div style="font-weight: bold; text-decoration: underline;">{{ $walasName }}</div>
                        <div>NIP. {{ $walasNip }}</div>
                    </div>
                </div>
            </div>

            <!-- Running Footer Halaman 2 -->
            <div class="page-footer">
                <div>{{ $footerLeft }}</div>
                <div>Halaman : 2</div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- HALAMAN 3 : LEMBAR PENGESAHAN (ORANG TUA, WALI KELAS, KEPALA SEKOLAH)-->
        <!-- =================================================================== -->
        <div class="page">
            <div>
                <!-- Header Siswa (Berulang di setiap halaman) -->
                <div class="student-header">
                    <table>
                        <tr>
                            <td style="width: 14%;">Nama Murid</td>
                            <td style="width: 2%;">:</td>
                            <td style="width: 44%; font-weight: bold;">{{ strtoupper($s->nama) }}</td>
                            <td style="width: 16%;">Kelas</td>
                            <td style="width: 2%;">:</td>
                            <td style="width: 22%; font-weight: bold;">{{ $kelas->nama_kelas }}</td>
                        </tr>
                        <tr>
                            <td>NIS/NISN</td>
                            <td>:</td>
                            <td>{{ $nisStr }} / {{ $nisnStr }}</td>
                            <td>Fase</td>
                            <td>:</td>
                            <td>{{ $kelas->fase ?? 'E' }}</td>
                        </tr>
                        <tr>
                            <td>Sekolah</td>
                            <td>:</td>
                            <td style="font-weight: bold;">{{ strtoupper($school->nama_sekolah ?? 'SMKS ASSUNIYAH') }}</td>
                            <td>Semester</td>
                            <td>:</td>
                            <td>{{ $semStr }}</td>
                        </tr>
                        <tr>
                            <td>Alamat</td>
                            <td>:</td>
                            <td>{{ $school->alamat ?? 'JL. RAYA WAY ABUNG 2' }}</td>
                            <td>Tahun Ajaran</td>
                            <td>:</td>
                            <td>{{ $tahunAjaranStr }}</td>
                        </tr>
                    </table>
                </div>

                <div class="header-line"></div>

                <div style="height: 30px;"></div>

                <!-- Tanda Tangan Orang Tua & Wali Kelas -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 40px; padding: 0 20px;">
                    <!-- Orang Tua Murid -->
                    <div class="signature-block" style="text-align: start;">
                        <div style="height: 18px;"></div>
                        <div>Orang Tua Murid</div>
                        <div style="height: 70px;"></div>
                        <div>....................................</div>
                    </div>

                    <!-- Wali Kelas -->
                    <div class="signature-block">
                        <div>{{ $titimangsaTempat }}, {{ $titimangsaTanggal }}</div>
                        <div>Wali Kelas</div>
                        
                        <div class="signature-img-container">
                            @if($walasSig)
                                <img src="{{ asset($walasSig) }}" alt="TTD Walas" class="sig-img sig-digital">
                            @endif
                        </div>

                        <div style="font-weight: bold; text-decoration: underline;">{{ $walasName }}</div>
                        <div>NIP. {{ $walasNip }}</div>
                    </div>
                </div>

                <!-- Tanda Tangan Kepala Sekolah (Dengan Stempel Tumpang Tindih) -->
                <div style="display: flex; justify-content: center; margin-top: 20px;">
                    <div class="signature-block" style="width: 260px;">
                        <div style="font-size: 10pt;">Kepala Sekolah</div>
                        
                        <div class="signature-img-container" style="height: 85px; margin: 4px auto; width: 220px;">
                            @if($schoolStamp)
                                <img src="{{ asset($schoolStamp) }}" alt="Stempel Sekolah" class="stamp-img sig-digital">
                            @endif
                            @if($headmasterSig)
                                <img src="{{ asset($headmasterSig) }}" alt="TTD Kepala Sekolah" class="sig-img sig-digital" style="max-height: 80px;">
                            @endif
                        </div>

                        <div style="font-weight: bold; text-decoration: underline;">{{ $headmasterName }}</div>
                        <div>NIP. {{ $headmasterNip }}</div>
                    </div>
                </div>
            </div>

            <!-- Running Footer Halaman 3 -->
            <div class="page-footer">
                <div>{{ $footerLeft }}</div>
                <div>Halaman : 3</div>
            </div>
        </div>

    @endforeach

    <script>
        // Toggle Digital Signature Mode
        let isDigital = true;
        function toggleSignatureMode() {
            isDigital = !isDigital;
            const sigs = document.querySelectorAll('.sig-digital');
            sigs.forEach(el => {
                el.style.visibility = isDigital ? 'visible' : 'hidden';
            });
            const btnText = document.getElementById('sigBtnText');
            if (btnText) {
                btnText.textContent = isDigital ? 'Mode: TTD Digital Aktif' : 'Mode: Manual / Blank';
            }
        }
    </script>
</body>
</html>

