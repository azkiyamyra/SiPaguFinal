<?php
/**
 * UPLOAD DATA HONOR DOSEN - SiPagu (VERSION SIMPLE - TEMPLATE BASED)
 * Halaman untuk upload data honor dosen dari Excel template
 * Lokasi: admin/upload_honor_dosen.php
 *
 * Tabel database: t_transaksi_honor_dosen
 * Kolom: id_thd, semester, bulan, id_jadwal, jml_tm, sks_tempuh
 *
 * Relasi:
 * - t_transaksi_honor_dosen.id_jadwal -> t_jadwal.id_jdwl
 * - t_jadwal.id_user -> t_user.id_user
 */

// Include required files
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

// Include PhpSpreadsheet namespace DI AWAL
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Set page title
$page_title = "Upload Honor Dosen";

// ======================
// HELPER FUNCTIONS
// ======================

function safe_trim($value) {
    if ($value === null || $value === '') return '';
    return trim((string)$value);
}

function safe_html($value) {
    if ($value === null || $value === '') return '';
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatRupiah($number) {
    return 'Rp ' . number_format((int)$number, 0, ',', '.');
}

function clean_header($header) {
    if ($header === null || $header === '') return '';
    $header = safe_trim($header);
    $header = preg_replace('/\s+/', ' ', $header);
    return trim($header);
}

function formatSemesterDisplay($semester) {
    if (!preg_match('/^\d{4}[12]$/', $semester)) return $semester;
    $tahun = substr($semester, 0, 4);
    $kode  = substr($semester, -1);
    return $tahun . ' ' . ($kode == '1' ? 'Ganjil' : 'Genap');
}

function normalizeBulan($bulan) {
    $bulan = strtolower(trim($bulan));
    $singkatan = [
        'jan' => 'januari',  'feb' => 'februari', 'mar' => 'maret',
        'apr' => 'april',    'may' => 'mei',       'jun' => 'juni',
        'jul' => 'juli',     'aug' => 'agustus',   'sep' => 'september',
        'oct' => 'oktober',  'nov' => 'november',  'dec' => 'desember'
    ];
    if (array_key_exists($bulan, $singkatan)) return $singkatan[$bulan];
    $angka_bulan = [
        '01' => 'januari', '1' => 'januari',   '02' => 'februari', '2' => 'februari',
        '03' => 'maret',   '3' => 'maret',      '04' => 'april',    '4' => 'april',
        '05' => 'mei',     '5' => 'mei',        '06' => 'juni',     '6' => 'juni',
        '07' => 'juli',    '7' => 'juli',       '08' => 'agustus',  '8' => 'agustus',
        '09' => 'september','9' => 'september', '10' => 'oktober',
        '11' => 'november', '12' => 'desember'
    ];
    if (array_key_exists($bulan, $angka_bulan)) return $angka_bulan[$bulan];
    return $bulan;
}

function parseToInt($value) {
    if ($value === null || $value === '') return 0;
    if (is_numeric($value)) return (int)$value;
    return (int)preg_replace('/[^0-9]/', '', $value);
}

function validateBulanBySemester($bulan, $semester_code) {
    $bulan_ganjil = ['juli', 'agustus', 'september', 'oktober', 'november', 'desember'];
    $bulan_genap  = ['januari', 'februari', 'maret', 'april', 'mei', 'juni'];
    $bulan = strtolower(trim($bulan));
    if ($semester_code == '1') return in_array($bulan, $bulan_ganjil);
    if ($semester_code == '2') return in_array($bulan, $bulan_genap);
    return false;
}

function clean_old_temp_files($dir) {
    $files = glob($dir . 'upload_honor_dosen_*');
    foreach ($files as $file) {
        if (filemtime($file) < time() - 3600) unlink($file);
    }
}

// ======================
// AMBIL DATA JADWAL UNTUK DROPDOWN
// ======================
$jadwal_list = [];
$query_jadwal = mysqli_query($koneksi,
    "SELECT j.id_jdwl, j.semester, j.kode_matkul, j.nama_matkul, j.jml_mhs,
            u.nama_user, u.npp_user, u.honor_persks
     FROM t_jadwal j
     LEFT JOIN t_user u ON j.id_user = u.id_user
     ORDER BY j.semester DESC, j.kode_matkul ASC"
);
while ($row = mysqli_fetch_assoc($query_jadwal)) {
    $jadwal_list[] = $row;
}

// ======================
// FUNGSI DOWNLOAD TEMPLATE
// ======================
if (isset($_GET['action']) && $_GET['action'] == 'download_template') {
    download_template_honor_dosen($jadwal_list);
    exit();
}

function download_template_honor_dosen($jadwal_list) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Data Honor Dosen');

    $spreadsheet->getProperties()
        ->setCreator('SiPagu System')
        ->setTitle('Template Import Honor Dosen SiPagu')
        ->setDescription('Template upload data honor dosen');

    // Column widths
    $sheet->getColumnDimension('A')->setWidth(5);
    $sheet->getColumnDimension('B')->setWidth(20);
    $sheet->getColumnDimension('C')->setWidth(55);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(15);

    // ---- BARIS 1: JUDUL ----
    $sheet->mergeCells('A1:E1');
    $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA HONOR DOSEN - SIPAGU');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A5276']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(35);

    // ---- BARIS 2-6: PETUNJUK ----
    $sheet->mergeCells('A2:E6');
    $instruksi =
        "PETUNJUK PENGISIAN:\n\n" .
        "1. Isi data mulai baris ke-8 (hapus data contoh terlebih dahulu)\n" .
        "2. KOLOM WAJIB DIISI: BULAN, ID_JADWAL, JML_TM, SKS_TEMPUH\n" .
        "3. BULAN: nama lengkap (januari..desember), singkatan (jan..dec), atau angka (01..12)\n" .
        "4. ID_JADWAL: Pilih dari DROPDOWN. Semester otomatis mengikuti jadwal yang dipilih.\n" .
        "5. JML_TM: Jumlah Tatap Muka (angka bulat)\n" .
        "6. SKS_TEMPUH: Jumlah SKS yang ditempuh (angka bulat)\n" .
        "7. JANGAN ubah nama kolom header (baris ke-7)!\n" .
        "8. Satu baris = satu transaksi honor dosen per bulan per jadwal.";
    $sheet->setCellValue('A2', $instruksi);
    $sheet->getStyle('A2')->applyFromArray([
        'font' => ['size' => 10],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF5FB']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AED6F1']]],
    ]);
    $sheet->getStyle('A2')->getAlignment()->setWrapText(true);
    $sheet->getRowDimension(2)->setRowHeight(170);

    // ---- BARIS 7: HEADER KOLOM ----
    $headers = ['NO', 'BULAN', 'ID_JADWAL (Pilih dari dropdown)', 'JML_TM', 'SKS_TEMPUH'];
    foreach ($headers as $idx => $hdr) {
        $col = Coordinate::stringFromColumnIndex($idx + 1);
        $cell = $col . '7';
        $sheet->setCellValue($cell, $hdr);
        $sheet->getStyle($cell)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F618D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '154360']]],
        ]);
    }
    $sheet->getRowDimension(7)->setRowHeight(30);

    // ---- BARIS 8: DATA CONTOH ----
    $contoh = [1, 'november', '', '14', '3'];
    foreach ($contoh as $idx => $val) {
        $col = Coordinate::stringFromColumnIndex($idx + 1);
        $sheet->setCellValue($col . '8', $val);
    }
    $sheet->getStyle('A8:E8')->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D5F5E3']],
        'font' => ['italic' => true, 'color' => ['rgb' => '1D8348']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'A9DFBF']]],
    ]);

    // ---- BORDER AREA DATA ----
    $sheet->getStyle('A7:E107')->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D6DBDF']]],
    ]);

    // ---- DROPDOWN ID_JADWAL ----
    // Buat sheet dropdown tersembunyi
    $dropdownSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'DropdownJadwal');
    $spreadsheet->addSheet($dropdownSheet);

    $row_d = 1;
    foreach ($jadwal_list as $j) {
        $smLabel = formatSemesterDisplay($j['semester']);
        $bulanValid = (substr($j['semester'], -1) == '1') ? 'Juli-Des' : 'Jan-Jun';
        $label = $j['id_jdwl'] . ' - ' . $j['kode_matkul'] . ' - ' . $j['nama_matkul'] .
                 ' (' . ($j['nama_user'] ?? '-') . ') [' . $smLabel . '] [' . $bulanValid . ']';
        $dropdownSheet->setCellValue('A' . $row_d, $label);
        $row_d++;
    }
    $dropdownSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);

    // Terapkan validasi dropdown ke C8:C107
    for ($r = 8; $r <= 107; $r++) {
        $dv = $sheet->getCell('C' . $r)->getDataValidation();
        $dv->setType(DataValidation::TYPE_LIST);
        $dv->setErrorStyle(DataValidation::STYLE_STOP);
        $dv->setAllowBlank(false);
        $dv->setShowDropDown(true);
        $dv->setShowInputMessage(true);
        $dv->setShowErrorMessage(true);
        $dv->setErrorTitle('ID Jadwal Tidak Valid');
        $dv->setError('Pilih dari dropdown!');
        $dv->setPromptTitle('Pilih Jadwal');
        $dv->setPrompt('Pilih jadwal dari daftar dropdown.');
        $dv->setFormula1('DropdownJadwal!$A$1:$A$' . ($row_d - 1));
    }

    // ---- SHEET REFERENSI JADWAL ----
    $refSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'ReferensiJadwal');
    $spreadsheet->addSheet($refSheet);
    $refHeaders = ['ID Jadwal', 'Semester', 'Kode MK', 'Nama MK', 'Dosen', 'Jml Mhs', 'Honor/SKS', 'Bulan Valid'];
    foreach ($refHeaders as $i => $rh) {
        $refSheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . '1', $rh);
    }
    $refSheet->getStyle('A1:H1')->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4E6F1']],
    ]);
    $row_r = 2;
    foreach ($jadwal_list as $j) {
        $smLabel   = formatSemesterDisplay($j['semester']);
        $semCode   = substr($j['semester'], -1);
        $bulanValid = ($semCode == '1') ? 'Juli - Desember' : 'Januari - Juni';
        $refSheet->setCellValue('A' . $row_r, $j['id_jdwl']);
        $refSheet->setCellValue('B' . $row_r, $smLabel);
        $refSheet->setCellValue('C' . $row_r, $j['kode_matkul']);
        $refSheet->setCellValue('D' . $row_r, $j['nama_matkul']);
        $refSheet->setCellValue('E' . $row_r, $j['nama_user'] ?? '-');
        $refSheet->setCellValue('F' . $row_r, $j['jml_mhs']);
        $refSheet->setCellValue('G' . $row_r, $j['honor_persks'] ?? 0);
        $refSheet->setCellValue('H' . $row_r, $bulanValid);
        $row_r++;
    }
    foreach (['A'=>10,'B'=>15,'C'=>15,'D'=>30,'E'=>25,'F'=>10,'G'=>15,'H'=>20] as $col => $w) {
        $refSheet->getColumnDimension($col)->setWidth($w);
    }
    $refSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);

    // Kembali ke sheet utama
    $spreadsheet->setActiveSheetIndex(0);
    $sheet->setAutoFilter('A7:E7');
    $sheet->freezePane('A8');
    $sheet->getStyle('A8:A107')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B8:B107')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('D8:E107')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Template_Upload_HonorDosen_SiPagu.xlsx"');
    header('Cache-Control: max-age=0');
    (new Xlsx($spreadsheet))->save('php://output');
    exit;
}

// ======================
// FUNGSI BACA FILE EXCEL
// ======================

function find_header_row_thd($sheetData) {
    for ($i = 0; $i < min(15, count($sheetData)); $i++) {
        $row = array_map('clean_header', $sheetData[$i]);
        $non_empty = count(array_filter($row, fn($c) => $c !== ''));
        if ($non_empty < 3) continue;
        $upper = array_map('strtoupper', $row);
        $hasBulan  = false;
        $hasJadwal = false;
        foreach ($upper as $h) {
            // BULAN: mengandung kata BULAN (tidak perlu batasan panjang ketat)
            if (strpos($h, 'BULAN') !== false) $hasBulan = true;
            // ID_JADWAL: mengandung JADWAL saja sudah cukup (header bisa panjang karena ada keterangan tambahan)
            if (strpos($h, 'JADWAL') !== false) $hasJadwal = true;
        }
        if ($hasBulan && $hasJadwal) return $i;
    }
    return -1;
}

function get_preview_data_thd($file_path, $file_ext) {
    try {
        $reader = IOFactory::createReaderForFile($file_path);
        if ($file_ext == 'csv') { $reader->setReadDataOnly(true); $reader->setReadEmptyCells(false); }
        $sheetData = $reader->load($file_path)->getActiveSheet()->toArray();
        $header_row = find_header_row_thd($sheetData);
        if ($header_row === -1) return false;
        $headers = array_map('clean_header', $sheetData[$header_row]);
        $sample  = [];
        $total   = 0;
        for ($i = $header_row + 1; $i < count($sheetData); $i++) {
            $row = array_map('safe_trim', $sheetData[$i]);
            if (empty(array_filter($row, fn($v) => $v !== ''))) continue;
            $total++;
            if (count($sample) < 5) $sample[] = $row;
        }
        return ['headers' => $headers, 'sample_data' => $sample, 'total_rows' => $total, 'header_row' => $header_row];
    } catch (Exception $e) { return false; }
}

// ======================
// DIREKTORI TEMP
// ======================
$temp_dir = __DIR__ . '/../temp_uploads/';
if (!file_exists($temp_dir)) mkdir($temp_dir, 0755, true);
clean_old_temp_files($temp_dir);

// ======================
// PROSES FORM
// ======================
$error_message   = '';
$success_message = '';
$preview_data    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---- 1. UPLOAD FILE ----
    if (isset($_POST['submit_upload']) && isset($_FILES['filexls'])) {
        $file_name = $_FILES['filexls']['name'];
        $file_tmp  = $_FILES['filexls']['tmp_name'];
        $file_size = $_FILES['filexls']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, ['xls', 'xlsx', 'csv'])) {
            $error_message = 'File harus bertipe XLS, XLSX, atau CSV.';
        } elseif ($file_size > 10 * 1024 * 1024) {
            $error_message = 'Ukuran file melebihi batas maksimal 10MB.';
        } else {
            $unique_name   = 'upload_honor_dosen_' . time() . '_' . uniqid() . '.' . $file_ext;
            $temp_file_path = $temp_dir . $unique_name;

            if (move_uploaded_file($file_tmp, $temp_file_path)) {
                // Cek header
                try {
                    $reader = IOFactory::createReaderForFile($temp_file_path);
                    $sheetData = $reader->load($temp_file_path)->getActiveSheet()->toArray();
                    $hr = find_header_row_thd($sheetData);
                    if ($hr === -1) {
                        $error_message = '❌ Format template tidak valid! Header BULAN dan ID_JADWAL tidak ditemukan.<br>'
                            . 'Gunakan template resmi: <a href="?action=download_template" class="btn btn-sm btn-success ml-1"><i class="fas fa-download"></i> Download Template</a>';
                        unlink($temp_file_path);
                    } else {
                        $preview_data = get_preview_data_thd($temp_file_path, $file_ext);
                        if ($preview_data) {
                            $preview_data['temp_file'] = $unique_name;
                            $_SESSION['thd_temp_file']  = $unique_name;
                            $_SESSION['thd_overwrite']  = $_POST['overwrite'] ?? '0';
                            $success_message = '✅ File valid! Ditemukan <strong>' . $preview_data['total_rows'] . '</strong> baris data. Periksa preview lalu konfirmasi import.';
                        } else {
                            $error_message = '❌ Gagal membaca isi file.';
                            unlink($temp_file_path);
                        }
                    }
                } catch (Exception $e) {
                    $error_message = '❌ Error membaca file: ' . $e->getMessage();
                    if (file_exists($temp_file_path)) unlink($temp_file_path);
                }
            } else {
                $error_message = '❌ Gagal menyimpan file sementara. Periksa izin direktori.';
            }
        }
    }

    // ---- 2. KONFIRMASI IMPORT ----
    elseif (isset($_POST['confirm_import']) && isset($_SESSION['thd_temp_file'])) {
        $temp_file      = $_SESSION['thd_temp_file'];
        $temp_file_path = $temp_dir . $temp_file;
        $file_ext       = strtolower(pathinfo($temp_file, PATHINFO_EXTENSION));
        $overwrite      = ($_SESSION['thd_overwrite'] ?? '0') == '1';

        if (!file_exists($temp_file_path)) {
            $error_message = '❌ File sementara tidak ditemukan. Silakan upload ulang.';
            unset($_SESSION['thd_temp_file'], $_SESSION['thd_overwrite']);
        } else {
            try {
                $reader = IOFactory::createReaderForFile($temp_file_path);
                if ($file_ext == 'csv') { $reader->setReadDataOnly(true); $reader->setReadEmptyCells(false); }
                $sheetData  = $reader->load($temp_file_path)->getActiveSheet()->toArray();
                $header_row = find_header_row_thd($sheetData);

                if ($header_row === -1) {
                    $error_message = '❌ Header tidak ditemukan.';
                } else {
                    // Buat mapping kolom
                    // URUTAN PENTING: paling spesifik dulu, agar tidak bentrok antar kolom
                    $col_map = [];
                    foreach ($sheetData[$header_row] as $ci => $hdr) {
                        $hu = strtoupper(clean_header($hdr));
                        if (empty($hu)) {
                            $col_map[$ci] = 'skip';
                        } elseif (strpos($hu, 'BULAN') !== false) {
                            // Kolom B: BULAN
                            $col_map[$ci] = 'bulan';
                        } elseif (strpos($hu, 'JADWAL') !== false) {
                            // Kolom C: ID_JADWAL (Pilih dari dropdown) — bisa header panjang
                            $col_map[$ci] = 'id_jadwal';
                        } elseif (strpos($hu, 'SKS') !== false) {
                            // Kolom E: SKS_TEMPUH — cek lebih dulu sebelum TM
                            $col_map[$ci] = 'sks_tempuh';
                        } elseif (strpos($hu, 'JML') !== false || strpos($hu, '_TM') !== false || $hu === 'TM') {
                            // Kolom D: JML_TM
                            $col_map[$ci] = 'jml_tm';
                        } else {
                            $col_map[$ci] = 'skip';
                        }
                    }

                    $mapped = array_values($col_map);
                    if (!in_array('bulan', $mapped)) {
                        $error_message = '❌ Kolom <strong>BULAN</strong> tidak ditemukan.';
                    } elseif (!in_array('id_jadwal', $mapped)) {
                        $error_message = '❌ Kolom <strong>ID_JADWAL</strong> tidak ditemukan.';
                    } else {
                        $sukses = 0;
                        $gagal  = 0;
                        $errors = [];

                        mysqli_begin_transaction($koneksi);
                        try {
                            for ($i = $header_row + 1; $i < count($sheetData); $i++) {
                                $rowData = $sheetData[$i];
                                if (empty(array_filter($rowData, fn($v) => trim((string)$v) !== ''))) continue;

                                $data = [];
                                foreach ($col_map as $ci => $field) {
                                    if ($field !== 'skip' && isset($rowData[$ci])) $data[$field] = safe_trim($rowData[$ci]);
                                }

                                // Validasi wajib
                                if (empty($data['bulan'])) {
                                    $errors[] = "Baris " . ($i + 1) . ": Kolom BULAN kosong."; $gagal++; continue;
                                }
                                if (empty($data['id_jadwal'])) {
                                    $errors[] = "Baris " . ($i + 1) . ": Kolom ID_JADWAL kosong. (Pastikan memilih dari dropdown, bukan mengetik manual)"; $gagal++; continue;
                                }

                                // Ekstrak ID jadwal dari berbagai format:
                                // - "2 - SI101 - Nama MK (Dosen) [Semester] [Bulan]"  → ambil angka pertama sebelum " -"
                                // - "[2] SI101 - ..."  → angka dalam kurung siku
                                // - "2" saja  → angka langsung
                                $id_jadwal_raw = $data['id_jadwal'];
                                $id_jadwal = 0;
                                if (preg_match('/^\[?(\d+)\]?\s*[-\s]/', $id_jadwal_raw, $m)) {
                                    // Format: "2 - ..." atau "[2] ..."
                                    $id_jadwal = (int)$m[1];
                                } elseif (preg_match('/^(\d+)$/', trim($id_jadwal_raw), $m)) {
                                    // Angka murni
                                    $id_jadwal = (int)$m[1];
                                } elseif (preg_match('/(\d+)/', $id_jadwal_raw, $m)) {
                                    // Fallback: ambil angka pertama yang ditemukan
                                    $id_jadwal = (int)$m[1];
                                } else {
                                    $errors[] = "Baris " . ($i + 1) . ": Format ID_JADWAL tidak valid: '$id_jadwal_raw'"; $gagal++; continue;
                                }

                                if ($id_jadwal <= 0) {
                                    $errors[] = "Baris " . ($i + 1) . ": ID_JADWAL harus angka positif."; $gagal++; continue;
                                }

                                // Ambil data jadwal untuk semester
                                $qJdwl = mysqli_query($koneksi, "SELECT id_jdwl, semester FROM t_jadwal WHERE id_jdwl = '$id_jadwal'");
                                if (mysqli_num_rows($qJdwl) == 0) {
                                    $errors[] = "Baris " . ($i + 1) . ": ID Jadwal $id_jadwal tidak ditemukan."; $gagal++; continue;
                                }
                                $rowJdwl   = mysqli_fetch_assoc($qJdwl);
                                $semester  = $rowJdwl['semester'];
                                $sem_code  = substr($semester, -1);

                                // Normalisasi & validasi bulan
                                $bulan_norm = normalizeBulan($data['bulan']);
                                if (!validateBulanBySemester($bulan_norm, $sem_code)) {
                                    $valid_range = ($sem_code == '1') ? 'Juli-Desember' : 'Januari-Juni';
                                    $errors[] = "Baris " . ($i + 1) . ": Bulan '$bulan_norm' tidak sesuai semester " . formatSemesterDisplay($semester) . " (harus $valid_range).";
                                    $gagal++; continue;
                                }

                                $jml_tm     = parseToInt($data['jml_tm']     ?? 0);
                                $sks_tempuh = parseToInt($data['sks_tempuh'] ?? 0);
                                $bulan_esc  = mysqli_real_escape_string($koneksi, $bulan_norm);

                                // Cek duplikat
                                $qDup = mysqli_query($koneksi,
                                    "SELECT id_thd FROM t_transaksi_honor_dosen
                                     WHERE bulan = '$bulan_esc' AND id_jadwal = '$id_jadwal'"
                                );
                                $isDup = mysqli_num_rows($qDup) > 0;

                                if ($isDup && !$overwrite) {
                                    $errors[] = "Baris " . ($i + 1) . ": Data bulan '$bulan_norm' + jadwal #$id_jadwal sudah ada (aktifkan mode Timpa).";
                                    $gagal++; continue;
                                }

                                if ($isDup && $overwrite) {
                                    $ok = mysqli_query($koneksi,
                                        "UPDATE t_transaksi_honor_dosen
                                         SET semester='$semester', jml_tm='$jml_tm', sks_tempuh='$sks_tempuh'
                                         WHERE bulan='$bulan_esc' AND id_jadwal='$id_jadwal'"
                                    );
                                } else {
                                    $ok = mysqli_query($koneksi,
                                        "INSERT INTO t_transaksi_honor_dosen
                                         (semester, bulan, id_jadwal, jml_tm, sks_tempuh)
                                         VALUES ('$semester','$bulan_esc','$id_jadwal','$jml_tm','$sks_tempuh')"
                                    );
                                }

                                if ($ok) $sukses++;
                                else { $errors[] = "Baris " . ($i + 1) . ": Gagal simpan - " . mysqli_error($koneksi); $gagal++; }
                            }

                            mysqli_commit($koneksi);
                        } catch (Exception $e) {
                            mysqli_rollback($koneksi);
                            throw $e;
                        }

                        unlink($temp_file_path);
                        unset($_SESSION['thd_temp_file'], $_SESSION['thd_overwrite']);

                        if ($sukses > 0) {
                            $success_message = "✅ Berhasil mengimport <strong>$sukses</strong> data honor dosen.";
                            if ($gagal > 0) $success_message .= " <strong>$gagal</strong> data gagal.";
                        } else {
                            $error_message = "❌ Tidak ada data yang berhasil diimport.";
                        }
                        if (!empty($errors)) {
                            $err_list = array_slice($errors, 0, 10);
                            $msg = "<ul class='mb-0'><li>" . implode('</li><li>', $err_list) . "</li></ul>";
                            if (count($errors) > 10) $msg .= "<small>...dan " . (count($errors) - 10) . " error lainnya</small>";
                            if ($sukses > 0) $error_message = "⚠️ Beberapa baris gagal:<br>" . $msg;
                            else $error_message .= '<br>' . $msg;
                        }
                    }
                }
            } catch (Exception $e) {
                mysqli_rollback($koneksi);
                $error_message = '❌ Terjadi kesalahan: ' . $e->getMessage();
                if (isset($temp_file_path) && file_exists($temp_file_path)) unlink($temp_file_path);
                unset($_SESSION['thd_temp_file'], $_SESSION['thd_overwrite']);
            }
        }
    }

    // ---- 3. BATALKAN UPLOAD ----
    elseif (isset($_POST['cancel_upload'])) {
        if (isset($_SESSION['thd_temp_file'])) {
            $tp = $temp_dir . $_SESSION['thd_temp_file'];
            if (file_exists($tp)) unlink($tp);
            unset($_SESSION['thd_temp_file'], $_SESSION['thd_overwrite']);
        }
        header('Location: upload_honor_dosen.php');
        exit;
    }

    // ---- 4. INPUT MANUAL ----
    elseif (isset($_POST['submit_manual'])) {
        $m_bulan   = $_POST['manual_bulan']  ?? '';
        $m_jadwal  = $_POST['manual_jadwal'] ?? '';
        $m_jml_tm  = $_POST['manual_jml_tm'] ?? '0';
        $m_sks     = $_POST['manual_sks']    ?? '0';

        if (empty($m_bulan)) {
            $error_message = '❌ Bulan wajib dipilih!';
        } elseif (empty($m_jadwal)) {
            $error_message = '❌ Jadwal wajib dipilih!';
        } else {
            $id_jadwal  = parseToInt($m_jadwal);
            $jml_tm     = parseToInt($m_jml_tm);
            $sks_tempuh = parseToInt($m_sks);

            $qJdwl = mysqli_query($koneksi, "SELECT id_jdwl, semester FROM t_jadwal WHERE id_jdwl = '$id_jadwal'");
            if (mysqli_num_rows($qJdwl) == 0) {
                $error_message = "❌ Jadwal dengan ID $id_jadwal tidak ditemukan!";
            } else {
                $rowJdwl    = mysqli_fetch_assoc($qJdwl);
                $semester   = $rowJdwl['semester'];
                $sem_code   = substr($semester, -1);
                $bulan_norm = normalizeBulan($m_bulan);

                if (!validateBulanBySemester($bulan_norm, $sem_code)) {
                    $valid_range   = ($sem_code == '1') ? 'Juli-Desember' : 'Januari-Juni';
                    $error_message = "❌ Bulan '$bulan_norm' tidak sesuai dengan semester " . formatSemesterDisplay($semester) . " (harus $valid_range).";
                } else {
                    $bulan_esc = mysqli_real_escape_string($koneksi, $bulan_norm);
                    $qDup = mysqli_query($koneksi,
                        "SELECT id_thd FROM t_transaksi_honor_dosen WHERE bulan='$bulan_esc' AND id_jadwal='$id_jadwal'"
                    );
                    if (mysqli_num_rows($qDup) > 0) {
                        $error_message = "⚠️ Data untuk bulan '$bulan_norm' dan jadwal ini sudah ada!";
                    } else {
                        $ok = mysqli_query($koneksi,
                            "INSERT INTO t_transaksi_honor_dosen (semester, bulan, id_jadwal, jml_tm, sks_tempuh)
                             VALUES ('$semester','$bulan_esc','$id_jadwal','$jml_tm','$sks_tempuh')"
                        );
                        if ($ok) {
                            $success_message = "✅ Data honor dosen berhasil disimpan! (Semester: " . formatSemesterDisplay($semester) . ", Bulan: " . ucfirst($bulan_norm) . ")";
                        } else {
                            $error_message = "❌ Gagal menyimpan data: " . mysqli_error($koneksi);
                        }
                    }
                }
            }
        }
    }
}

// ======================
// DATA TERBARU (untuk tabel bawah) dengan pagination
// ======================
$thd_page = isset($_GET['thd_page']) ? max(1, (int)$_GET['thd_page']) : 1;
$thd_per_page = 5;
$total_thd_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_honor_dosen");
$total_thd_cnt = mysqli_fetch_assoc($total_thd_q)['total'];
$total_thd_pages = max(1, ceil($total_thd_cnt / $thd_per_page));
if ($thd_page > $total_thd_pages) $thd_page = $total_thd_pages;
$thd_offset = ($thd_page - 1) * $thd_per_page;

$recent_data = [];
$q_recent = mysqli_query($koneksi,
    "SELECT h.id_thd, h.semester, h.bulan, h.jml_tm, h.sks_tempuh,
            j.id_jdwl, j.kode_matkul, j.nama_matkul, j.jml_mhs,
            u.nama_user, u.npp_user, u.honor_persks
     FROM t_transaksi_honor_dosen h
     LEFT JOIN t_jadwal j ON h.id_jadwal = j.id_jdwl
     LEFT JOIN t_user u ON j.id_user = u.id_user
     ORDER BY h.id_thd DESC
     LIMIT $thd_offset, $thd_per_page"
);
while ($row = mysqli_fetch_assoc($q_recent)) {
    $recent_data[] = $row;
}

// Statistik singkat
$q_stat = mysqli_query($koneksi, "SELECT COUNT(*) as total, COUNT(DISTINCT id_jadwal) as jdwl FROM t_transaksi_honor_dosen");
$stat = mysqli_fetch_assoc($q_stat);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-chalkboard-teacher mr-2"></i>Upload Data Honor Dosen</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Upload Honor Dosen</div>
            </div>
        </div>

        <div class="section-body">
            <!-- ALERT MESSAGES dengan class up-alert -->
            <?php if ($error_message): ?>
            <div class="up-alert up-alert-danger up-alert-dismissible">
                <div class="up-alert-icon"><i class="fas fa-exclamation-circle"></i></div>
                <div class="up-alert-content"><?= $error_message ?></div>
                <button class="up-alert-close" onclick="this.closest('.up-alert').remove()"><span>×</span></button>
            </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
            <div class="up-alert up-alert-success up-alert-dismissible">
                <div class="up-alert-icon"><i class="fas fa-check-circle"></i></div>
                <div class="up-alert-content"><?= $success_message ?></div>
                <button class="up-alert-close" onclick="this.closest('.up-alert').remove()"><span>×</span></button>
            </div>
            <?php endif; ?>

            <!-- STATISTIK SINGKAT dengan class up-stat-row -->
            <div class="up-stat-row">
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= $stat['total'] ?? 0 ?></div>
                    <div class="up-stat-label"><i class="fas fa-database mr-1"></i>Total Transaksi Honor</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= $stat['jdwl'] ?? 0 ?></div>
                    <div class="up-stat-label"><i class="fas fa-book mr-1"></i>Jadwal Diinput</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= count($jadwal_list) ?></div>
                    <div class="up-stat-label"><i class="fas fa-calendar-alt mr-1"></i>Jadwal Tersedia</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= count(array_unique(array_column($jadwal_list, 'semester'))) ?></div>
                    <div class="up-stat-label"><i class="fas fa-graduation-cap mr-1"></i>Semester Aktif</div>
                </div>
            </div>

            <!-- STEP 1: DOWNLOAD TEMPLATE & INFO JADWAL dengan class up-step-grid -->
            <div class="up-step-grid">
                <!-- Download Template Card -->
                <div class="up-step-card">
                    <div class="up-step-num">1</div>
                    <h5><i class="fas fa-download mr-2 text-info"></i>Download Template</h5>
                    <p class="text-muted small mb-3">
                        Template Excel berisi dropdown ID Jadwal dengan info lengkap dosen, kode MK, nama MK, semester, dan rentang bulan yang valid.
                    </p>
                    
                    <a href="?action=download_template" class="up-btn up-btn-download btn-block">
                        <i class="fas fa-file-excel mr-2"></i>Download Template Honor Dosen
                    </a>
                    
                    <div class="mt-3">
                        <h6><i class="fas fa-columns mr-1"></i>Kolom Template</h6>
                        <ul class="small">
                            <li><strong>BULAN</strong> – Nama/singkatan/angka bulan</li>
                            <li><strong>ID_JADWAL</strong> – Dropdown detail lengkap</li>
                            <li><strong>JML_TM</strong> – Jumlah Tatap Muka</li>
                            <li><strong>SKS_TEMPUH</strong> – Jumlah SKS</li>
                        </ul>
                    </div>
                    
                    <span class="up-note">
                        <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                        <strong>Ganjil</strong> = Juli–Desember &nbsp;|&nbsp;
                        <strong>Genap</strong> = Januari–Juni
                    </span>
                </div>

                <!-- Daftar Jadwal Card -->
                <div class="up-step-card up-step-info">
                    <div class="up-step-num">2</div>
                    <h5><i class="fas fa-list mr-2 text-info"></i>Daftar Jadwal Tersedia (<?= count($jadwal_list) ?>)</h5>
                    
                    <div class="table-responsive" style="max-height:280px; overflow-y:auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light sticky-top">
                                <tr>
                                    <th>ID</th>
                                    <th>Semester</th>
                                    <th>Kode</th>
                                    <th>Nama MK</th>
                                    <th>Dosen</th>
                                    <th class="text-center">Mhs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($jadwal_list)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">Belum ada jadwal terdaftar</td></tr>
                                <?php else: ?>
                                <?php foreach ($jadwal_list as $jdwl): ?>
                                <?php $smCode = substr($jdwl['semester'], -1); ?>
                                <tr>
                                    <td><strong class="text-primary"><?= $jdwl['id_jdwl'] ?></strong></td>
                                    <td>
                                        <span class="badge badge-semester <?= $smCode == '1' ? 'badge-ganjil' : 'badge-genap' ?>">
                                            <?= formatSemesterDisplay($jdwl['semester']) ?>
                                        </span>
                                    </td>
                                    <td><?= safe_html($jdwl['kode_matkul']) ?></td>
                                    <td><?= safe_html($jdwl['nama_matkul']) ?></td>
                                    <td><?= safe_html($jdwl['nama_user'] ?? '-') ?></td>
                                    <td class="text-center"><?= $jdwl['jml_mhs'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- MAIN CARD UPLOAD -->
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon"><i class="fas fa-upload"></i></div>
                    <h5>Upload File Data Honor Dosen</h5>
                </div>

                <div class="up-card-body">

                    <!-- STEP 3: UPLOAD FILE -->
                    <?php if (empty($preview_data)): ?>
                    <div>
                        <form method="POST" enctype="multipart/form-data" id="formUpload">
                            <div class="row">
                                <div class="col-md-8">
                                    <!-- Dropzone Modern -->
                                    <div class="up-dropzone" id="dropZone" onclick="document.getElementById('filexls').click()">
                                        <div class="up-drop-icon-wrap">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <h4>Klik atau drag & drop file di sini</h4>
                                        <p>Format: XLS, XLSX, CSV — Maks. 10MB</p>
                                        
                                        <input type="file" id="filexls" name="filexls" accept=".xls,.xlsx,.csv"
                                               class="up-file-input" required onchange="showFileName(this)">
                                        
                                        <div class="up-format-badges">
                                            <span class="up-format-badge xls">XLS</span>
                                            <span class="up-format-badge xlsx">XLSX</span>
                                            <span class="up-format-badge csv">CSV</span>
                                        </div>
                                    </div>

                                    <!-- File Selected Info -->
                                    <div id="fileSelectedInfo" class="up-file-selected">
                                        <div class="up-file-selected-icon"><i class="fas fa-file-excel"></i></div>
                                        <div class="up-file-selected-info">
                                            <div id="fileNameDisplay" class="up-file-selected-name"></div>
                                            <div id="fileSizeDisplay" class="up-file-selected-size"></div>
                                        </div>
                                        <button type="button" class="up-file-remove" onclick="resetDropzone()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>

                                    <!-- Overwrite Checkbox Modern -->
                                    <div class="up-check-wrap">
                                        <input type="checkbox" id="overwrite" name="overwrite" value="1">
                                        <div class="up-check-label">
                                            <strong><i class="fas fa-sync-alt mr-1"></i>Timpa data yang sudah ada</strong>
                                            <small>Jika dicentang, baris dengan kombinasi <strong>Bulan + ID Jadwal</strong> yang sama akan diperbarui.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="up-step-card" style="padding: 16px; margin-bottom: 0;">
                                        <h6 class="mb-2"><i class="fas fa-info-circle text-info mr-1"></i>Format Bulan</h6>
                                        <ul class="small" style="padding-left: 18px;">
                                            <li>Nama: <em>januari, februari, ...</em></li>
                                            <li>Singkatan: <em>jan, feb, mar, ...</em></li>
                                            <li>Angka: <em>01, 02, 03, ...</em></li>
                                        </ul>
                                        <hr class="my-2">
                                        <h6 class="mb-2"><i class="fas fa-shield-alt text-success mr-1"></i>Cek Duplikat</h6>
                                        <p class="small mb-0">Sistem otomatis mendeteksi duplikasi berdasarkan <strong>Bulan + ID Jadwal</strong>.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="up-actions">
                                <button type="submit" name="submit_upload" class="up-btn up-btn-primary up-btn-lg">
                                    <i class="fas fa-upload mr-2"></i>Upload & Validasi
                                </button>
                                <button type="button" class="up-btn up-btn-secondary" onclick="resetDropzone()">
                                    <i class="fas fa-redo mr-2"></i>Reset
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- STEP 3B: PREVIEW & KONFIRMASI IMPORT -->
                    <?php else: ?>
                    <div class="up-preview-wrap">
                        <div class="up-preview-meta">
                            <span class="up-meta-badge up-meta-badge-blue">
                                <i class="fas fa-table mr-1"></i>Baris header: <?= $preview_data['header_row'] + 1 ?>
                            </span>
                            <span class="up-meta-badge up-meta-badge-green">
                                <i class="fas fa-database mr-1"></i>Total: <?= $preview_data['total_rows'] ?> baris data
                            </span>
                        </div>

                        <div class="up-table-wrap mb-3">
                            <table class="up-table">
                                <thead>
                                    <tr>
                                        <?php foreach ($preview_data['headers'] as $h): ?>
                                        <th><?= safe_html($h) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($preview_data['sample_data'] as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $cell): ?>
                                        <td><?= safe_html($cell) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (isset($_SESSION['thd_overwrite']) && $_SESSION['thd_overwrite'] == '1'): ?>
                        <div class="up-confirm-box warning mb-3">
                            <div class="up-confirm-box-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="up-confirm-box-content">
                                <h6>Mode TIMPA AKTIF</h6>
                                <p>Data yang sudah ada (berdasarkan Bulan + ID Jadwal) akan <strong>ditimpa/diperbarui</strong>.</p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="up-confirm-box mb-3">
                            <div class="up-confirm-box-icon"><i class="fas fa-info-circle"></i></div>
                            <div class="up-confirm-box-content">
                                <h6>Konfirmasi Import</h6>
                                <p>Akan mengimport <strong><?= $preview_data['total_rows'] ?></strong> data honor dosen.</p>
                            </div>
                        </div>

                        <form method="POST">
                            <div class="up-confirm-actions">
                                <button type="submit" name="confirm_import" class="up-btn up-btn-success up-btn-lg"
                                        onclick="return confirm('Yakin ingin mengimport <?= $preview_data['total_rows'] ?> data honor dosen?')">
                                    <i class="fas fa-database mr-2"></i>Konfirmasi Import Data
                                </button>
                                <button type="submit" name="cancel_upload" class="up-btn up-btn-secondary">
                                    <i class="fas fa-times mr-2"></i>Batalkan
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- DIVIDER -->
                    <div class="up-divider">
                        <span>ATAU</span>
                    </div>

                    <!-- INPUT MANUAL -->
                    <div class="up-recent">
                        <div class="up-section-label">
                            <i class="fas fa-keyboard"></i>
                            <span>Input Manual (Single Entry)</span>
                        </div>
                        
                        <p class="text-muted small mb-4">
                            Untuk menambahkan satu data honor dosen tanpa file Excel. Semester otomatis mengikuti jadwal yang dipilih.
                        </p>

                        <form method="POST" id="formManual">
                            <div class="up-form-grid">
                                <!-- Bulan -->
                                <div class="up-form-group">
                                    <label class="up-form-label">Bulan <span class="req">*</span></label>
                                    <select class="up-select" name="manual_bulan" id="selBulan" required>
                                        <option value="">-- Pilih Bulan --</option>
                                        <?php
                                        $bulan_list = ['januari','februari','maret','april','mei','juni','juli','agustus','september','oktober','november','desember'];
                                        foreach ($bulan_list as $bl):
                                        ?>
                                        <option value="<?= $bl ?>"><?= ucfirst($bl) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Jadwal -->
                                <div class="up-form-group" style="grid-column: span 2;">
                                    <label class="up-form-label">Jadwal <span class="req">*</span></label>
                                    <select class="up-select select2" name="manual_jadwal" id="selJadwal" required>
                                        <option value="">-- Cari dan Pilih Jadwal --</option>
                                        <?php foreach ($jadwal_list as $jdwl):
                                            $smCode  = substr($jdwl['semester'], -1);
                                            $smLabel = formatSemesterDisplay($jdwl['semester']);
                                            $bValid  = ($smCode == '1') ? 'Bulan: Juli-Des' : 'Bulan: Jan-Jun';
                                        ?>
                                        <option value="<?= $jdwl['id_jdwl'] ?>" data-semester="<?= $jdwl['semester'] ?>">
                                            [<?= $jdwl['id_jdwl'] ?>] <?= safe_html($jdwl['kode_matkul']) ?> -
                                            <?= safe_html($jdwl['nama_matkul']) ?>
                                            (<?= safe_html($jdwl['nama_user'] ?? '-') ?>) |
                                            <?= $smLabel ?> | <?= $bValid ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="up-form-hint">Pilih jadwal – semester otomatis terisi</span>
                                </div>
                            </div>

                            <!-- Info semester -->
                            <div id="infoSemester" class="alert alert-info py-2 small d-none mb-3" style="border-left: 4px solid var(--info);">
                                <i class="fas fa-info-circle mr-1"></i>
                                Semester jadwal: <strong id="textSemester">-</strong> &nbsp;|&nbsp;
                                Bulan yang valid: <strong id="textBulanValid">-</strong>
                            </div>

                            <div class="up-form-grid" style="grid-template-columns: repeat(2, 1fr);">
                                <div class="up-form-group">
                                    <label class="up-form-label">Jumlah TM</label>
                                    <input type="number" class="up-input" name="manual_jml_tm" min="0" value="0">
                                    <span class="up-form-hint">Tatap Muka</span>
                                </div>
                                <div class="up-form-group">
                                    <label class="up-form-label">SKS Tempuh</label>
                                    <input type="number" class="up-input" name="manual_sks" min="0" value="0">
                                    <span class="up-form-hint">Bobot SKS</span>
                                </div>
                            </div>

                            <div class="up-actions">
                                <button type="submit" name="submit_manual" class="up-btn up-btn-success">
                                    <i class="fas fa-save mr-2"></i>Simpan Data Manual
                                </button>
                                <button type="button" class="up-btn up-btn-secondary" onclick="document.getElementById('formManual').reset(); $('#selJadwal').val(null).trigger('change'); document.getElementById('infoSemester').classList.add('d-none');">
                                    <i class="fas fa-redo mr-2"></i>Reset
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- DIVIDER -->
                    <div class="up-divider"></div>

                    <!-- DATA TERBARU -->
                    <div class="up-recent">
                        <div class="up-section-label d-flex align-items-center">
                            <i class="fas fa-history"></i>
                            <span>Data Honor Dosen Terbaru</span>
                            <small class="text-muted ml-2">(10 data terakhir)</small>
                            <div class="ml-auto">
                                <div class="up-search-box" style="width: 200px;">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="up-search-input" id="searchHonor" placeholder="Cari honor..." onkeyup="filterTableHonor()">
                                </div>
                            </div>
                        </div>

                        <div class="up-table-wrap">
                            <table class="up-table" id="tableHonor">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Semester</th>
                                        <th>Bulan</th>
                                        <th>ID Jadwal</th>
                                        <th>Kode MK</th>
                                        <th>Nama MK</th>
                                        <th>Dosen</th>
                                        <th class="text-center">Jml TM</th>
                                        <th class="text-center">SKS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_data)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-2x d-block mb-2 text-light"></i>
                                            Belum ada data honor dosen
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($recent_data as $no => $row):
                                        $smCode = substr($row['semester'] ?? '', -1);
                                    ?>
                                    <tr>
                                        <td><?= $thd_offset + $no + 1 ?></td>
                                        <td>
                                            <span class="badge badge-semester <?= $smCode == '1' ? 'badge-ganjil' : 'badge-genap' ?>">
                                                <?= formatSemesterDisplay($row['semester'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td><?= ucfirst($row['bulan']) ?></td>
                                        <td><strong><?= $row['id_jdwl'] ?? '-' ?></strong></td>
                                        <td><?= safe_html($row['kode_matkul'] ?? '') ?></td>
                                        <td><?= safe_html($row['nama_matkul'] ?? '') ?></td>
                                        <td><?= safe_html($row['nama_user'] ?? '-') ?></td>
                                        <td class="text-center"><span class="up-badge up-badge-default"><?= $row['jml_tm'] ?></span></td>
                                        <td class="text-center"><span class="up-badge up-badge-info"><?= $row['sks_tempuh'] ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_thd_pages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center mt-2" id="recentThd">
                            <small class="text-muted">Total <?= $total_thd_cnt ?> data | Halaman <?= $thd_page ?> dari <?= $total_thd_pages ?></small>
                            <ul class="up-pagination mb-0">
                                <li class="up-page-item <?= ($thd_page <= 1) ? 'disabled' : '' ?>">
                                    <a class="up-page-link" href="?thd_page=<?= $thd_page-1 ?>#recentThd"><i class="fas fa-chevron-left"></i></a>
                                </li>
                                <?php for ($p = max(1, $thd_page-2); $p <= min($total_thd_pages, $thd_page+2); $p++): ?>
                                <li class="up-page-item <?= ($p == $thd_page) ? 'active' : '' ?>">
                                    <a class="up-page-link" href="?thd_page=<?= $p ?>#recentThd"><?= $p ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="up-page-item <?= ($thd_page >= $total_thd_pages) ? 'disabled' : '' ?>">
                                    <a class="up-page-link" href="?thd_page=<?= $thd_page+1 ?>#recentThd"><i class="fas fa-chevron-right"></i></a>
                                </li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>

                </div><!-- up-card-body -->
            </div><!-- up-main-card -->

        </div><!-- section-body -->
    </section>
</div><!-- main-content -->

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

<!-- Custom styles untuk badge-info -->
<style>
.up-badge-info {
    background: #eff6ff;
    color: var(--info);
    border: 1px solid #bfdbfe;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.up-badge-default {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}
</style>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// ---- Dropzone file picker ----
function showFileName(input) {
    if (input.files.length > 0) {
        var file = input.files[0];
        var fileSize = (file.size / 1024).toFixed(2) + ' KB';
        if (file.size > 1024 * 1024) {
            fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
        }
        
        document.getElementById('fileNameDisplay').textContent = file.name;
        document.getElementById('fileSizeDisplay').textContent = fileSize;
        document.getElementById('fileSelectedInfo').classList.add('show');
    }
}

function resetDropzone() {
    document.getElementById('filexls').value = '';
    document.getElementById('fileSelectedInfo').classList.remove('show');
    document.getElementById('fileNameDisplay').textContent = '';
    document.getElementById('fileSizeDisplay').textContent = '';
}

// Drag & drop support
(function() {
    var dz = document.getElementById('dropZone');
    if (!dz) return;
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(ev) {
        dz.addEventListener(ev, function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });
    
    ['dragenter', 'dragover'].forEach(function(ev) {
        dz.addEventListener(ev, function(e) {
            dz.classList.add('dragover');
        });
    });
    
    ['dragleave', 'drop'].forEach(function(ev) {
        dz.addEventListener(ev, function(e) {
            dz.classList.remove('dragover');
        });
    });
    
    dz.addEventListener('drop', function(e) {
        var file = e.dataTransfer.files[0];
        if (file) {
            var input = document.getElementById('filexls');
            var dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            showFileName(input);
        }
    });
})();

// ---- Select2 ----
$(document).ready(function() {
    $('#selJadwal').select2({
        width: '100%',
        placeholder: 'Cari jadwal berdasarkan nama MK, kode, atau dosen...',
        allowClear: true,
        dropdownParent: $('#selJadwal').closest('.up-form-group')
    });

    // Info semester saat pilih jadwal
    $('#selJadwal').on('change', function() {
        var opt  = $(this).find(':selected');
        var sem  = opt.data('semester') || '';
        var info = document.getElementById('infoSemester');
        if (!sem) { info.classList.add('d-none'); return; }
        var kode  = sem.slice(-1);
        var label = sem.slice(0, 4) + ' ' + (kode == '1' ? 'Ganjil' : 'Genap');
        var range = kode == '1' ? 'Juli – Desember' : 'Januari – Juni';
        document.getElementById('textSemester').textContent  = label;
        document.getElementById('textBulanValid').textContent = range;
        info.classList.remove('d-none');
    });
});

// ---- Validasi manual sebelum submit ----
document.getElementById('formManual')?.addEventListener('submit', function(e) {
    if (!e.submitter || e.submitter.name !== 'submit_manual') return;
    var bulan  = document.getElementById('selBulan').value;
    var jadwal = document.getElementById('selJadwal').value;
    if (!bulan)  { e.preventDefault(); alert('❌ Pilih bulan terlebih dahulu!');  return; }
    if (!jadwal) { e.preventDefault(); alert('❌ Pilih jadwal terlebih dahulu!'); return; }

    // Validasi bulan vs semester di sisi client
    var opt   = document.querySelector('#selJadwal option:checked');
    var sem   = opt ? (opt.dataset.semester || '') : '';
    var kode  = sem ? sem.slice(-1) : '';
    var bGanjil = ['juli','agustus','september','oktober','november','desember'];
    var bGenap  = ['januari','februari','maret','april','mei','juni'];

    if (kode == '1' && !bGanjil.includes(bulan)) {
        e.preventDefault();
        alert('❌ Untuk semester Ganjil, bulan harus Juli – Desember!');
        return;
    }
    if (kode == '2' && !bGenap.includes(bulan)) {
        e.preventDefault();
        alert('❌ Untuk semester Genap, bulan harus Januari – Juni!');
        return;
    }
});

function filterTableHonor() {
    var input = document.getElementById("searchHonor");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("tableHonor");
    var tr = table.getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td");
        var found = false;
        for (var j = 0; j < td.length; j++) {
            if (td[j]) {
                var txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        tr[i].style.display = found ? "" : "none";
    }
}
</script>

<?php
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>