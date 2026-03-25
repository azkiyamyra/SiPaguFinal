<?php
/**
 * UPLOAD TRANSAKSI UJIAN - SiPagu (VERSION SIMPLE - TEMPLATE BASED)
 * Halaman untuk upload data transaksi ujian dari Excel template
 * Lokasi: admin/upload_tu.php
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
$page_title = "Upload Transaksi Ujian";

// ======================
// AMBIL DATA UNTUK DROPDOWN
// ======================
$users = [];
$query_users = mysqli_query($koneksi, "SELECT id_user, npp_user, nama_user FROM t_user ORDER BY nama_user");
while ($row = mysqli_fetch_assoc($query_users)) {
    $users[$row['id_user']] = $row['npp_user'] . ' - ' . $row['nama_user'];
}

$panitia = [];
$query_panitia = mysqli_query($koneksi, "SELECT id_pnt, jbtn_pnt FROM t_panitia ORDER BY jbtn_pnt");
while ($row = mysqli_fetch_assoc($query_panitia)) {
    $panitia[$row['id_pnt']] = $row['jbtn_pnt'];
}

// ======================
// FUNGSI FORMAT SEMESTER
// ======================
function formatSemester($semester) {
    if (!preg_match('/^\d{4}[12]$/', $semester)) {
        return $semester;
    }

    $tahun = substr($semester, 0, 4);
    $kode  = substr($semester, -1);

    return $tahun . ' ' . ($kode == '1' ? 'Ganjil' : 'Genap');
}

function generateSemesterOptions() {
    $list = [];
    $currentYear = date('Y');
    
    for ($y = $currentYear - 2; $y <= $currentYear + 2; $y++) {
        $list[] = $y . '1';
        $list[] = $y . '2';
    }
    return $list;
}

$semesterList = generateSemesterOptions();

// ======================
// FUNGSI DOWNLOAD TEMPLATE
// ======================
if (isset($_GET['action']) && $_GET['action'] == 'download_template') {
    download_excel_template($users, $panitia, $semesterList);
    exit();
}

// ======================
// FUNGSI TEMPLATE EXCEL DENGAN HEADER LANGSUNG
// ======================
function download_excel_template($users, $panitia, $semesterList) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('SiPagu System')
        ->setLastModifiedBy('SiPagu System')
        ->setTitle('Template Import Data Transaksi Ujian SiPagu')
        ->setDescription('Template untuk mengimport data transaksi ujian ke sistem SiPagu');
    
    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(5);   // NO
    $sheet->getColumnDimension('B')->setWidth(20);  // semester
    $sheet->getColumnDimension('C')->setWidth(45);  // id_panitia
    $sheet->getColumnDimension('D')->setWidth(45);  // id_user
    $sheet->getColumnDimension('E')->setWidth(18);  // jml_mhs_prodi
    $sheet->getColumnDimension('F')->setWidth(15);  // jml_mhs
    $sheet->getColumnDimension('G')->setWidth(15);  // jml_koreksi
    $sheet->getColumnDimension('H')->setWidth(18);  // jml_matkul
    $sheet->getColumnDimension('I')->setWidth(18);  // jml_pgws_pagi
    $sheet->getColumnDimension('J')->setWidth(18);  // jml_pgws_sore
    $sheet->getColumnDimension('K')->setWidth(18);  // jml_koor_pagi
    $sheet->getColumnDimension('L')->setWidth(18);  // jml_koor_sore
    
    // Title (baris 1)
    $sheet->mergeCells('A1:L1');
    $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA TRANSAKSI UJIAN - SIPAGU');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E86C1']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    
    // Instructions (baris 2-6) - MERGED CELLS
    $sheet->mergeCells('A2:L6');
    $instructions = "PETUNJUK PENGISIAN:\n\n" .
                    "1. Isi data mulai baris ke-8\n" .
                    "2. SEMUA KOLOM WAJIB DIISI (kecuali NO opsional)!\n" .
                    "3. Format SEMESTER (pilih dari dropdown):\n" .
                    "   - 20241 (Tahun 2024 Semester Ganjil)\n" .
                    "   - 20242 (Tahun 2024 Semester Genap)\n" .
                    "4. id_panitia: Pilih dari dropdown (ID - Jabatan Panitia)\n" .
                    "5. id_user: Pilih dari dropdown (ID - NPP - Nama Dosen)\n" .
                    "6. Kolom jumlah diisi angka (default 0 jika kosong):\n" .
                    "   - jml_mhs_prodi : Jumlah mahasiswa per prodi\n" .
                    "   - jml_mhs        : Jumlah mahasiswa total\n" .
                    "   - jml_koreksi    : Jumlah koreksi ujian\n" .
                    "   - jml_matkul     : Jumlah mata kuliah\n" .
                    "   - jml_pgws_pagi  : Jumlah pengawas pagi\n" .
                    "   - jml_pgws_sore  : Jumlah pengawas sore\n" .
                    "   - jml_koor_pagi  : Jumlah koordinator pagi\n" .
                    "   - jml_koor_sore  : Jumlah koordinator sore\n\n" .
                    "7. JANGAN ubah nama kolom (baris ke-7)!\n" .
                    "8. HAPUS DATA CONTOH SEBELUM DIUPLOAD!";
    $sheet->setCellValue('A2', $instructions);
    $sheet->getStyle('A2')->getAlignment()->setWrapText(true);
    $sheet->getStyle('A2')->applyFromArray([
        'font' => ['size' => 10],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCF3CF']],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '7D6608']
            ]
        ]
    ]);
    $sheet->getRowDimension(2)->setRowHeight(240);
    
    // Column headers (baris 7) - LANGSUNG SESUAI FIELD DATABASE
    $headers = [
        'no', 
        'semester', 
        'id_panitia', 
        'id_user', 
        'jml_mhs_prodi', 
        'jml_mhs', 
        'jml_koreksi', 
        'jml_matkul', 
        'jml_pgws_pagi', 
        'jml_pgws_sore', 
        'jml_koor_pagi', 
        'jml_koor_sore'
    ];
    
    $col = 1;
    foreach ($headers as $header) {
        $cell = Coordinate::stringFromColumnIndex($col) . '7';
        $sheet->setCellValue($cell, $header);
        
        $sheet->getStyle($cell)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2874A6']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);
        $col++;
    }
    
    // Set row height untuk header
    $sheet->getRowDimension(7)->setRowHeight(30);
    
    // Contoh data (baris 8)
    $sample_data = [
        [
            1,                          // no
            '20241',                     // semester
            '18 - Bendahara',            // id_panitia
            '99 - 0686.11.1995.000 - Azkiya, S.Kom', // id_user
            '50',                        // jml_mhs_prodi
            '45',                        // jml_mhs
            '45',                        // jml_koreksi
            '3',                         // jml_matkul
            '6',                         // jml_pgws_pagi
            '4',                         // jml_pgws_sore
            '2',                         // jml_koor_pagi
            '2'                          // jml_koor_sore
        ],
    ];
    
    $row = 8;
    foreach ($sample_data as $data) {
        $col = 1;
        foreach ($data as $value) {
            $cell = Coordinate::stringFromColumnIndex($col) . $row;
            $sheet->setCellValue($cell, $value);
            
            // Format number untuk kolom angka
            if ($col >= 5 && $col <= 12) {
                $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('0');
            }
            
            $col++;
        }
        
        // Style untuk data contoh
        $styleRange = 'A' . $row . ':L' . $row;
        $sheet->getStyle($styleRange)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F8F5']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'AED6F1']
                ]
            ],
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '1D8348']
            ]
        ]);
        
        $row++;
    }
    
    // BUAT SHEET REFERENSI (sembunyikan)
    $refSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Referensi');
    $spreadsheet->addSheet($refSheet);
    
    // Header referensi PANITIA
    $refSheet->setCellValue('A1', 'id_panitia');
    $refSheet->setCellValue('B1', 'JABATAN PANITIA');
    $refSheet->getStyle('A1:B1')->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4E6F1']]
    ]);
    
    // Isi data referensi panitia
    $row_ref = 2;
    foreach ($panitia as $id => $nama) {
        $refSheet->setCellValue('A' . $row_ref, $id . ' - ' . $nama);
        $refSheet->setCellValue('B' . $row_ref, $nama);
        $row_ref++;
    }
    
    // Data referensi user
    $refSheet->setCellValue('D1', 'id_user');
    $refSheet->setCellValue('E1', 'NPP');
    $refSheet->setCellValue('F1', 'NAMA USER');
    
    $refSheet->getStyle('D1:F1')->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4E6F1']]
    ]);
    
    $row_ref2 = 2;
    foreach ($users as $id => $nama) {
        $parts = explode(' - ', $nama);
        $npp = $parts[0] ?? '';
        $nama_user = $parts[1] ?? $nama;
        
        $refSheet->setCellValue('D' . $row_ref2, $id . ' - ' . $nama);
        $refSheet->setCellValue('E' . $row_ref2, $npp);
        $refSheet->setCellValue('F' . $row_ref2, $nama_user);
        $row_ref2++;
    }
    
    // Set column widths untuk sheet referensi
    $refSheet->getColumnDimension('A')->setWidth(45);
    $refSheet->getColumnDimension('B')->setWidth(30);
    $refSheet->getColumnDimension('D')->setWidth(45);
    $refSheet->getColumnDimension('E')->setWidth(15);
    $refSheet->getColumnDimension('F')->setWidth(30);
    
    // Sembunyikan sheet referensi
    $refSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
    
    // BUAT SHEET DROPDOWN
    $dropdownSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'DropdownList');
    $spreadsheet->addSheet($dropdownSheet);
    
    // Tulis semua nilai semester ke kolom A
    $row_drop = 1;
    foreach ($semesterList as $value) {
        $dropdownSheet->setCellValue('A' . $row_drop, $value);
        $row_drop++;
    }
    
    // Tulis semua nilai panitia ke kolom B (ID - Nama)
    $row_drop_panitia = 1;
    foreach ($panitia as $id => $nama) {
        $dropdownSheet->setCellValue('B' . $row_drop_panitia, $id . ' - ' . $nama);
        $row_drop_panitia++;
    }
    
    // Tulis semua nilai user ke kolom C (ID - NPP - Nama)
    $row_drop_user = 1;
    foreach ($users as $id => $nama) {
        $dropdownSheet->setCellValue('C' . $row_drop_user, $id . ' - ' . $nama);
        $row_drop_user++;
    }
    
    // Sembunyikan sheet dropdown
    $dropdownSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
    
    // Terapkan validasi data ke kolom B (semester) dari baris 8 sampai 100
    for ($i = 8; $i <= 100; $i++) {
        $cellCoordinate = 'B' . $i;
        $validation = $sheet->getCell($cellCoordinate)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Semester Tidak Valid');
        $validation->setError('Pilih semester dari dropdown!');
        $validation->setPromptTitle('Pilih semester');
        $validation->setPrompt('Silakan pilih kode semester dari daftar berikut:');
        $validation->setFormula1('DropdownList!$A$1:$A$' . ($row_drop - 1));
    }
    
    // Terapkan validasi data ke kolom C (id_panitia) dari baris 8 sampai 100
    for ($i = 8; $i <= 100; $i++) {
        $cellCoordinate = 'C' . $i;
        $validation = $sheet->getCell($cellCoordinate)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('ID Panitia Tidak Valid');
        $validation->setError('Pilih id_panitia dari dropdown!');
        $validation->setPromptTitle('Pilih id_panitia');
        $validation->setPrompt('Silakan pilih ID Panitia dari daftar berikut:');
        $validation->setFormula1('DropdownList!$B$1:$B$' . ($row_drop_panitia - 1));
    }
    
    // Terapkan validasi data ke kolom D (id_user) dari baris 8 sampai 100
    for ($i = 8; $i <= 100; $i++) {
        $cellCoordinate = 'D' . $i;
        $validation = $sheet->getCell($cellCoordinate)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('ID User Tidak Valid');
        $validation->setError('Pilih id_user dari dropdown!');
        $validation->setPromptTitle('Pilih id_user');
        $validation->setPrompt('Silakan pilih ID User dari daftar berikut:');
        $validation->setFormula1('DropdownList!$C$1:$C$' . ($row_drop_user - 1));
    }
    
    // Tambahkan komentar pada sel untuk petunjuk
    $sheet->getComment('B7')->getText()->createTextRun("Klik dropdown untuk memilih kode semester.\nContoh: 20241 = Ganjil 2024, 20242 = Genap 2024");
    $sheet->getComment('C7')->getText()->createTextRun("Klik dropdown untuk memilih id_panitia.\nFormat: ID - Nama Jabatan");
    $sheet->getComment('D7')->getText()->createTextRun("Klik dropdown untuk memilih id_user.\nFormat: ID - NPP - Nama Dosen");
    
    // Kembalikan ke sheet utama
    $spreadsheet->setActiveSheetIndex(0);
    
    // Format dan border untuk area data (100 baris total)
    $dataRange = 'A7:L106';
    $sheet->getStyle($dataRange)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'D6DBDF']
            ]
        ]
    ]);
    
    // Set alignment
    $sheet->getStyle('A8:A106')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B8:D106')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('E8:L106')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // Auto filter untuk header
    $sheet->setAutoFilter('A7:L7');
    
    // Freeze pane (header tetap terlihat)
    $sheet->freezePane('A8');
    
    // Output file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Template_Transaksi_Ujian_SiPagu.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ======================
// FUNGSI HELPERS
// ======================
function safe_trim($value) {
    if ($value === null || $value === '') {
        return '';
    }
    return trim((string)$value);
}

function safe_html($value) {
    if ($value === null || $value === '') {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function parseToInt($value) {
    if ($value === null || $value === '') {
        return 0;
    }
    
    if (is_numeric($value)) {
        return (int) $value;
    }
    
    $cleaned = preg_replace('/[^0-9]/', '', $value);
    return (int) $cleaned;
}

// ======================
// FUNGSI CEK HEADER - LANGSUNG COCOK DENGAN FIELD
// ======================
function validate_headers($headers) {
    $expected_fields = [
        'no', 'semester', 'id_panitia', 'id_user', 'jml_mhs_prodi', 
        'jml_mhs', 'jml_koreksi', 'jml_matkul', 'jml_pgws_pagi', 
        'jml_pgws_sore', 'jml_koor_pagi', 'jml_koor_sore'
    ];
    
    $headers_lower = array_map('strtolower', array_map('safe_trim', $headers));
    
    $missing_fields = [];
    foreach ($expected_fields as $field) {
        if (!in_array($field, $headers_lower)) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        return [
            'valid' => false,
            'missing' => $missing_fields
        ];
    }
    
    return ['valid' => true];
}

// ======================
// FUNGSI FIND HEADER ROW
// ======================
function find_header_row($sheetData) {
    for ($i = 0; $i < min(15, count($sheetData)); $i++) {
        $row = array_map('safe_trim', $sheetData[$i]);
        $row_lower = array_map('strtolower', $row);
        
        // Cek apakah baris ini mengandung header yang diharapkan
        $expected = ['no', 'semester', 'id_panitia', 'id_user'];
        $found_count = 0;
        
        foreach ($expected as $field) {
            if (in_array($field, $row_lower)) {
                $found_count++;
            }
        }
        
        if ($found_count >= 2) {
            return $i;
        }
    }
    
    return -1;
}

// ======================
// DIREKTORI TEMP
// ======================
$temp_dir = __DIR__ . '/../temp_uploads/';
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

// ======================
// PROSES UPLOAD
// ======================
$error_message = '';
$success_message = '';
$preview_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // UPLOAD FILE EXCEL
    if (isset($_POST['submit']) && isset($_FILES['filexls'])) {
        $file_name = $_FILES['filexls']['name'];
        $file_tmp = $_FILES['filexls']['tmp_name'];
        $file_size = $_FILES['filexls']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = ['xls', 'xlsx', 'csv'];
        if (!in_array($file_ext, $allowed_ext)) {
            $error_message = 'File harus bertipe XLS, XLSX, atau CSV.';
        }
        elseif ($file_size > 10 * 1024 * 1024) {
            $error_message = 'File terlalu besar. Maksimal 10MB.';
        }
        else {
            $unique_name = 'upload_tu_' . time() . '_' . uniqid() . '.' . $file_ext;
            $temp_file_path = $temp_dir . $unique_name;
            
            if (move_uploaded_file($file_tmp, $temp_file_path)) {
                try {
                    $reader = IOFactory::createReaderForFile($temp_file_path);
                    if ($file_ext == 'csv') {
                        $reader->setReadDataOnly(true);
                        $reader->setReadEmptyCells(false);
                    }
                    
                    $spreadsheet = $reader->load($temp_file_path);
                    $sheetData = $spreadsheet->getActiveSheet()->toArray();
                    
                    $header_row = find_header_row($sheetData);
                    
                    if ($header_row === -1) {
                        $error_message = "❌ Header tidak ditemukan dalam file! Pastikan menggunakan template yang benar.";
                        unlink($temp_file_path);
                    } else {
                        $headers = array_map('safe_trim', $sheetData[$header_row]);
                        
                        // Validasi header langsung
                        $validation = validate_headers($headers);
                        
                        if (!$validation['valid']) {
                            $error_message = "❌ Header tidak sesuai dengan template! Kolom yang kurang: " . implode(', ', $validation['missing']);
                            $error_message .= "<br><a href='?action=download_template' class='btn btn-sm btn-success mt-2'><i class='fas fa-download'></i> Download Template</a>";
                            unlink($temp_file_path);
                        } else {
                            // Ambil preview data
                            $total_rows = 0;
                            $sample_data = [];
                            
                            for ($i = $header_row + 1; $i < count($sheetData); $i++) {
                                $row = array_map('safe_trim', $sheetData[$i]);
                                
                                if (!empty(array_filter($row, function($val) {
                                    return $val !== '' && $val !== null;
                                }))) {
                                    $total_rows++;
                                    if (count($sample_data) < 5) {
                                        $sample_data[] = $row;
                                    }
                                }
                            }
                            
                            $preview_data = [
                                'headers' => $headers,
                                'sample_data' => $sample_data,
                                'total_rows' => $total_rows,
                                'header_row' => $header_row,
                                'temp_file' => $unique_name
                            ];
                            
                            $_SESSION['upload_temp_file_tu'] = $unique_name;
                            $_SESSION['overwrite_option_tu'] = $_POST['overwrite'] ?? '0';
                            $success_message = "✅ Template valid! Preview data ditemukan: " . $total_rows . " baris data.";
                        }
                    }
                    
                } catch (Exception $e) {
                    $error_message = "❌ Error membaca file: " . $e->getMessage();
                    if (file_exists($temp_file_path)) {
                        unlink($temp_file_path);
                    }
                }
            } else {
                $error_message = "❌ Gagal menyimpan file sementara.";
            }
        }
    }
    
    // CONFIRM DAN IMPORT DATA
    elseif (isset($_POST['confirm_import']) && isset($_SESSION['upload_temp_file_tu'])) {
        $temp_file = $_SESSION['upload_temp_file_tu'];
        $temp_file_path = $temp_dir . $temp_file;
        $file_ext = strtolower(pathinfo($temp_file, PATHINFO_EXTENSION));
        
        if (file_exists($temp_file_path)) {
            try {
                $reader = IOFactory::createReaderForFile($temp_file_path);
                if ($file_ext == 'csv') {
                    $reader->setReadDataOnly(true);
                    $reader->setReadEmptyCells(false);
                }
                
                $spreadsheet = $reader->load($temp_file_path);
                $sheetData = $spreadsheet->getActiveSheet()->toArray();
                
                $header_row = find_header_row($sheetData);
                
                if ($header_row === -1) {
                    $error_message = "❌ Header tidak ditemukan dalam file!";
                } else {
                    $headers = array_map('safe_trim', $sheetData[$header_row]);
                    $headers_lower = array_map('strtolower', $headers);
                    
                    // Mapping langsung berdasarkan posisi header
                    $field_positions = [];
                    foreach ($headers_lower as $idx => $header) {
                        $field_positions[$header] = $idx;
                    }
                    
                    $startRow = $header_row + 1;
                    $jumlahData = 0;
                    $jumlahGagal = 0;
                    $errors = [];
                    $success_details = [];
                    $overwrite = isset($_SESSION['overwrite_option_tu']) && $_SESSION['overwrite_option_tu'] == '1';
                    
                    mysqli_begin_transaction($koneksi);
                    
                    try {
                        for ($i = $startRow; $i < count($sheetData); $i++) {
                            $rowData = $sheetData[$i];
                            
                            // Skip baris kosong
                            if (empty(array_filter($rowData, function($val) {
                                return $val !== null && $val !== '' && trim((string)$val) !== '';
                            }))) {
                                continue;
                            }
                            
                            // Ambil data berdasarkan posisi field
                            $no = isset($field_positions['no']) ? safe_trim($rowData[$field_positions['no']] ?? '') : '';
                            $semester_raw = isset($field_positions['semester']) ? safe_trim($rowData[$field_positions['semester']] ?? '') : '';
                            $id_panitia_raw = isset($field_positions['id_panitia']) ? safe_trim($rowData[$field_positions['id_panitia']] ?? '') : '';
                            $id_user_raw = isset($field_positions['id_user']) ? safe_trim($rowData[$field_positions['id_user']] ?? '') : '';
                            
                            $jml_mhs_prodi = isset($field_positions['jml_mhs_prodi']) ? parseToInt($rowData[$field_positions['jml_mhs_prodi']] ?? 0) : 0;
                            $jml_mhs = isset($field_positions['jml_mhs']) ? parseToInt($rowData[$field_positions['jml_mhs']] ?? 0) : 0;
                            $jml_koreksi = isset($field_positions['jml_koreksi']) ? parseToInt($rowData[$field_positions['jml_koreksi']] ?? 0) : 0;
                            $jml_matkul = isset($field_positions['jml_matkul']) ? parseToInt($rowData[$field_positions['jml_matkul']] ?? 0) : 0;
                            $jml_pgws_pagi = isset($field_positions['jml_pgws_pagi']) ? parseToInt($rowData[$field_positions['jml_pgws_pagi']] ?? 0) : 0;
                            $jml_pgws_sore = isset($field_positions['jml_pgws_sore']) ? parseToInt($rowData[$field_positions['jml_pgws_sore']] ?? 0) : 0;
                            $jml_koor_pagi = isset($field_positions['jml_koor_pagi']) ? parseToInt($rowData[$field_positions['jml_koor_pagi']] ?? 0) : 0;
                            $jml_koor_sore = isset($field_positions['jml_koor_sore']) ? parseToInt($rowData[$field_positions['jml_koor_sore']] ?? 0) : 0;
                            
                            // Validasi data wajib
                            if (empty($semester_raw)) {
                                $errors[] = "Baris " . ($i+1) . ": semester tidak boleh kosong";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            if (empty($id_panitia_raw)) {
                                $errors[] = "Baris " . ($i+1) . ": id_panitia tidak boleh kosong";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            if (empty($id_user_raw)) {
                                $errors[] = "Baris " . ($i+1) . ": id_user tidak boleh kosong";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            // Ekstrak ID dari format "ID - Detail"
                            $id_panitia = 0;
                            $id_user = 0;
                            $semester = '';
                            
                            // Ekstrak ID Panitia
                            if (preg_match('/^(\d+)\s*-/', $id_panitia_raw, $matches)) {
                                $id_panitia = (int)$matches[1];
                            } elseif (is_numeric($id_panitia_raw)) {
                                $id_panitia = (int)$id_panitia_raw;
                            } else {
                                $errors[] = "Baris " . ($i+1) . ": Format id_panitia tidak valid: '$id_panitia_raw'";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            // Ekstrak ID User
                            if (preg_match('/^(\d+)\s*-/', $id_user_raw, $matches)) {
                                $id_user = (int)$matches[1];
                            } elseif (is_numeric($id_user_raw)) {
                                $id_user = (int)$id_user_raw;
                            } else {
                                $errors[] = "Baris " . ($i+1) . ": Format id_user tidak valid: '$id_user_raw'";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            // Semester
                            if (preg_match('/^(\d{4}[12])$/', $semester_raw, $matches)) {
                                $semester = $semester_raw;
                            } elseif (preg_match('/^(\d{4}[12])/', $semester_raw, $matches)) {
                                $semester = $matches[1];
                            } else {
                                $errors[] = "Baris " . ($i+1) . ": Format semester tidak valid: '$semester_raw' (harus format YYYY1 atau YYYY2)";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            // Validasi ID > 0
                            if ($id_panitia <= 0) {
                                $errors[] = "Baris " . ($i+1) . ": ID Panitia harus angka positif";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            if ($id_user <= 0) {
                                $errors[] = "Baris " . ($i+1) . ": ID User harus angka positif";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            // Cek user
                            $cekUser = mysqli_query($koneksi,
                                "SELECT id_user, nama_user FROM t_user WHERE id_user = '$id_user'"
                            );
                            
                            if (mysqli_num_rows($cekUser) == 0) {
                                $errors[] = "Baris " . ($i+1) . ": ID User '$id_user' tidak ditemukan";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            $user_data = mysqli_fetch_assoc($cekUser);
                            $nama_user = $user_data['nama_user'];
                            
                            // Cek panitia
                            $cekPanitia = mysqli_query($koneksi,
                                "SELECT id_pnt, jbtn_pnt FROM t_panitia WHERE id_pnt = '$id_panitia'"
                            );
                            
                            if (mysqli_num_rows($cekPanitia) == 0) {
                                $errors[] = "Baris " . ($i+1) . ": ID Panitia '$id_panitia' tidak ditemukan";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            $panitia_data = mysqli_fetch_assoc($cekPanitia);
                            $jbtn_panitia = $panitia_data['jbtn_pnt'];
                            
                            // Cek duplikasi
                            $cekDuplikat = mysqli_query($koneksi,
                                "SELECT id_tu FROM t_transaksi_ujian 
                                 WHERE semester = '$semester' 
                                 AND id_user = '$id_user' 
                                 AND id_panitia = '$id_panitia'"
                            );
                            
                            if (mysqli_num_rows($cekDuplikat) > 0) {
                                if ($overwrite) {
                                    $update = mysqli_query($koneksi, "
                                        UPDATE t_transaksi_ujian
                                        SET jml_mhs_prodi = '$jml_mhs_prodi',
                                            jml_mhs = '$jml_mhs',
                                            jml_koreksi = '$jml_koreksi',
                                            jml_matkul = '$jml_matkul',
                                            jml_pgws_pagi = '$jml_pgws_pagi',
                                            jml_pgws_sore = '$jml_pgws_sore',
                                            jml_koor_pagi = '$jml_koor_pagi',
                                            jml_koor_sore = '$jml_koor_sore'
                                        WHERE semester = '$semester' 
                                        AND id_user = '$id_user' 
                                        AND id_panitia = '$id_panitia'
                                    ");
                                    
                                    if ($update) {
                                        $jumlahData++;
                                        $success_details[] = "Baris " . ($i+1) . ": Data untuk $nama_user ($jbtn_panitia) di semester " . formatSemester($semester) . " berhasil diupdate";
                                    } else {
                                        $errors[] = "Baris " . ($i+1) . ": Gagal mengupdate data - " . mysqli_error($koneksi);
                                        $jumlahGagal++;
                                    }
                                } else {
                                    $errors[] = "Baris " . ($i+1) . ": Data untuk $nama_user ($jbtn_panitia) di semester " . formatSemester($semester) . " sudah ada";
                                    $jumlahGagal++;
                                }
                                continue;
                            }
                            
                            // Insert data baru
                            $insert = mysqli_query($koneksi, "
                                INSERT INTO t_transaksi_ujian
                                (semester, id_panitia, id_user, jml_mhs_prodi, jml_mhs, jml_koreksi, jml_matkul, 
                                 jml_pgws_pagi, jml_pgws_sore, jml_koor_pagi, jml_koor_sore)
                                VALUES
                                ('$semester', '$id_panitia', '$id_user', '$jml_mhs_prodi', '$jml_mhs', '$jml_koreksi', '$jml_matkul',
                                 '$jml_pgws_pagi', '$jml_pgws_sore', '$jml_koor_pagi', '$jml_koor_sore')
                            ");
                            
                            if ($insert) {
                                $jumlahData++;
                                $success_details[] = "Baris " . ($i+1) . ": Data untuk $nama_user ($jbtn_panitia) di semester " . formatSemester($semester) . " berhasil disimpan";
                            } else {
                                $errors[] = "Baris " . ($i+1) . ": Gagal menyimpan data - " . mysqli_error($koneksi);
                                $jumlahGagal++;
                            }
                        }
                        
                        mysqli_commit($koneksi);
                        
                    } catch (Exception $e) {
                        mysqli_rollback($koneksi);
                        throw $e;
                    }
                    
                    unlink($temp_file_path);
                    unset($_SESSION['upload_temp_file_tu']);
                    unset($_SESSION['overwrite_option_tu']);
                    
                    if ($jumlahData > 0) {
                        $success_message = "✅ Berhasil mengimport <strong>$jumlahData</strong> data transaksi ujian.";
                        if ($jumlahGagal > 0) {
                            $success_message .= " <strong>$jumlahGagal</strong> data gagal.";
                        }
                        
                        if (!empty($success_details) && count($success_details) <= 10) {
                            $success_message .= "<br><br><strong>Detail Data Berhasil:</strong><br>" . implode('<br>', $success_details);
                        }
                        
                        if (!empty($errors)) {
                            $error_message = "⚠️ Beberapa error ditemukan:<br>" . implode('<br>', array_slice($errors, 0, 10));
                            if (count($errors) > 10) {
                                $error_message .= '<br>... dan ' . (count($errors) - 10) . ' error lainnya';
                            }
                        }
                    } else {
                        $error_message = "❌ Tidak ada data yang berhasil diimport.";
                        if (!empty($errors)) {
                            $error_message .= '<br>' . implode('<br>', array_slice($errors, 0, 10));
                        }
                    }
                }
                
            } catch (Exception $e) {
                $error_message = "❌ Terjadi kesalahan: " . $e->getMessage();
                
                if (isset($temp_file_path) && file_exists($temp_file_path)) {
                    unlink($temp_file_path);
                }
                unset($_SESSION['upload_temp_file_tu']);
                unset($_SESSION['overwrite_option_tu']);
            }
        } else {
            $error_message = "❌ File sementara tidak ditemukan.";
            unset($_SESSION['upload_temp_file_tu']);
            unset($_SESSION['overwrite_option_tu']);
        }
    }
    
    // MANUAL INPUT
    elseif (isset($_POST['submit_manual'])) {
        $manual_semester = $_POST['manual_semester'] ?? '';
        $manual_panitia = $_POST['manual_panitia'] ?? '';
        $manual_user = $_POST['manual_user'] ?? '';
        $manual_jml_mhs_prodi = $_POST['manual_jml_mhs_prodi'] ?? '0';
        $manual_jml_mhs = $_POST['manual_jml_mhs'] ?? '0';
        $manual_jml_koreksi = $_POST['manual_jml_koreksi'] ?? '0';
        $manual_jml_matkul = $_POST['manual_jml_matkul'] ?? '0';
        $manual_jml_pgws_pagi = $_POST['manual_jml_pgws_pagi'] ?? '0';
        $manual_jml_pgws_sore = $_POST['manual_jml_pgws_sore'] ?? '0';
        $manual_jml_koor_pagi = $_POST['manual_jml_koor_pagi'] ?? '0';
        $manual_jml_koor_sore = $_POST['manual_jml_koor_sore'] ?? '0';
        
        if (empty($manual_semester) || empty($manual_panitia) || empty($manual_user)) {
            $error_message = '❌ Semester, Panitia, dan User wajib diisi!';
        } elseif (!is_numeric($manual_user) || !is_numeric($manual_panitia)) {
            $error_message = '❌ ID User dan ID Panitia harus angka!';
        } elseif (!preg_match('/^\d{4}[12]$/', $manual_semester)) {
            $error_message = '❌ Format semester tidak valid! (harus format YYYY1 atau YYYY2)';
        } else {
            $cekUser = mysqli_query($koneksi,
                "SELECT id_user FROM t_user WHERE id_user = '$manual_user'"
            );
            
            if (mysqli_num_rows($cekUser) == 0) {
                $error_message = "❌ ID User tidak ditemukan!";
            } else {
                $cekPanitia = mysqli_query($koneksi,
                    "SELECT id_pnt FROM t_panitia WHERE id_pnt = '$manual_panitia'"
                );
                
                if (mysqli_num_rows($cekPanitia) == 0) {
                    $error_message = "❌ ID Panitia tidak ditemukan!";
                } else {
                    $cekDuplikat = mysqli_query($koneksi,
                        "SELECT id_tu FROM t_transaksi_ujian 
                         WHERE semester = '$manual_semester' 
                         AND id_user = '$manual_user' 
                         AND id_panitia = '$manual_panitia'"
                    );
                    
                    if (mysqli_num_rows($cekDuplikat) > 0) {
                        $error_message = "⚠️ Data untuk kombinasi ini sudah ada!";
                    } else {
                        $insert_manual = mysqli_query($koneksi, "
                            INSERT INTO t_transaksi_ujian
                            (semester, id_panitia, id_user, jml_mhs_prodi, jml_mhs, jml_koreksi, jml_matkul, 
                             jml_pgws_pagi, jml_pgws_sore, jml_koor_pagi, jml_koor_sore)
                            VALUES
                            ('$manual_semester', '$manual_panitia', '$manual_user', '$manual_jml_mhs_prodi', 
                             '$manual_jml_mhs', '$manual_jml_koreksi', '$manual_jml_matkul',
                             '$manual_jml_pgws_pagi', '$manual_jml_pgws_sore', 
                             '$manual_jml_koor_pagi', '$manual_jml_koor_sore')
                        ");
                        
                        if ($insert_manual) {
                            $success_message = "✅ Data transaksi ujian berhasil disimpan!";
                        } else {
                            $error_message = "❌ Gagal menyimpan data: " . mysqli_error($koneksi);
                        }
                    }
                }
            }
        }
    }
}

// Clean old temp files
$files = glob($temp_dir . 'upload_tu_*');
foreach ($files as $file) {
    if (filemtime($file) < time() - 3600) {
        unlink($file);
    }
}

// Ambil data terbaru dengan pagination
$tu_page = isset($_GET['tu_page']) ? max(1, (int)$_GET['tu_page']) : 1;
$tu_per_page = 5;
$total_tu_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_ujian");
$total_tu_cnt = mysqli_fetch_assoc($total_tu_q)['total'];
$total_tu_pages = max(1, ceil($total_tu_cnt / $tu_per_page));
if ($tu_page > $total_tu_pages) $tu_page = $total_tu_pages;
$tu_offset = ($tu_page - 1) * $tu_per_page;

$recent_data = [];
$query = mysqli_query($koneksi, 
    "SELECT tu.*, p.jbtn_pnt, u.nama_user
     FROM t_transaksi_ujian tu
     LEFT JOIN t_panitia p ON tu.id_panitia = p.id_pnt
     LEFT JOIN t_user u ON tu.id_user = u.id_user
     ORDER BY tu.id_tu DESC 
     LIMIT $tu_offset, $tu_per_page"
);
while ($row = mysqli_fetch_assoc($query)) {
    $recent_data[] = $row;
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-pen-alt mr-2"></i>Upload Data Transaksi Ujian</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Upload Transaksi Ujian</div>
            </div>
        </div>

        <div class="section-body">
            <!-- Messages -->
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

            <!-- Download Template Card -->
            <div class="up-step-grid" style="grid-template-columns: 1fr;">
                <div class="up-step-card">
                    <div class="up-step-num">1</div>
                    <h5><i class="fas fa-download mr-2 text-info"></i>Download Template</h5>
                    <p class="text-muted small">Download template Excel dengan dropdown untuk memudahkan pengisian data transaksi ujian.</p>
                    
                    <a href="?action=download_template" class="up-btn up-btn-download btn-block">
                        <i class="fas fa-file-excel mr-2"></i> Download Template Transaksi Ujian
                    </a>
                    
                    <div class="mt-3">
                        <small class="text-muted d-block">
                            <i class="fas fa-info-circle"></i> Header dalam template (12 kolom):
                            <code>no, semester, id_panitia, id_user, jml_mhs_prodi, jml_mhs, jml_koreksi, jml_matkul, jml_pgws_pagi, jml_pgws_sore, jml_koor_pagi, jml_koor_sore</code>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Upload Form -->
            <?php if (empty($preview_data)): ?>
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon"><i class="fas fa-upload"></i></div>
                    <h5>Upload File Transaksi Ujian</h5>
                </div>
                <div class="up-card-body">
                    <form action="" method="POST" enctype="multipart/form-data" class="mt-3">
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
                        <div class="up-check-wrap mt-3">
                            <input type="checkbox" id="overwrite" name="overwrite" value="1">
                            <div class="up-check-label">
                                <strong><i class="fas fa-sync-alt mr-1"></i>Timpa data yang sudah ada</strong>
                                <small>Jika dicentang, data dengan kombinasi semester + id_user + id_panitia yang sama akan diperbarui!</small>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="up-actions">
                            <button type="submit" name="submit" class="up-btn up-btn-primary">
                                <i class="fas fa-upload mr-2"></i> Upload & Validasi File
                            </button>
                            <button type="button" class="up-btn up-btn-secondary" onclick="clearUploadForm()">
                                <i class="fas fa-redo mr-2"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Preview Data -->
            <?php if (!empty($preview_data)): ?>
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon"><i class="fas fa-eye"></i></div>
                    <h5>Preview Data</h5>
                </div>
                <div class="up-card-body">
                    <div class="up-preview-meta mb-3">
                        <span class="up-meta-badge up-meta-badge-blue">
                            <i class="fas fa-table mr-1"></i>Baris header: <?= $preview_data['header_row'] + 1 ?>
                        </span>
                        <span class="up-meta-badge up-meta-badge-green">
                            <i class="fas fa-database mr-1"></i>Total: <?= $preview_data['total_rows'] ?> baris data
                        </span>
                    </div>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="temp_file" value="<?= safe_html($preview_data['temp_file'] ?? '') ?>">
                        
                        <div class="up-table-wrap mb-4">
                            <table class="up-table">
                                <thead>
                                    <tr>
                                        <?php foreach ($preview_data['headers'] as $header): ?>
                                        <th><?= safe_html($header) ?></th>
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
                        
                        <?php if (isset($_SESSION['overwrite_option_tu']) && $_SESSION['overwrite_option_tu'] == '1'): ?>
                        <div class="up-confirm-box warning mb-3">
                            <div class="up-confirm-box-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="up-confirm-box-content">
                                <h6>Mode TIMPA AKTIF</h6>
                                <p>Data dengan kombinasi semester + id_user + id_panitia yang sama akan <strong>ditimpa/diperbarui</strong>! Pastikan ini adalah yang Anda inginkan.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="up-confirm-box mb-3">
                            <div class="up-confirm-box-icon"><i class="fas fa-info-circle"></i></div>
                            <div class="up-confirm-box-content">
                                <h6>Informasi Import</h6>
                                <p>Akan mengimport <strong><?= $preview_data['total_rows'] ?></strong> data transaksi ujian.</p>
                            </div>
                        </div>
                        
                        <div class="up-confirm-actions">
                            <button type="submit" name="confirm_import" class="up-btn up-btn-success up-btn-lg">
                                <i class="fas fa-database mr-2"></i> Konfirmasi Import Data
                            </button>
                            <a href="upload_tu.php" class="up-btn up-btn-secondary">
                                <i class="fas fa-times mr-2"></i> Batalkan
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- DIVIDER -->
            <div class="up-divider">
                <span>ATAU</span>
            </div>

            <!-- Manual Input Form -->
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon"><i class="fas fa-keyboard"></i></div>
                    <h5>Input Manual (Single Entry)</h5>
                </div>
                <div class="up-card-body">
                    <p class="text-muted small mb-4">Untuk input data transaksi ujian satu per satu.</p>
                    
                    <form action="" method="POST" id="manualForm">
                        <div class="up-form-grid">
                            <div class="up-form-group">
                                <label class="up-form-label">Semester <span class="req">*</span></label>
                                <select class="up-select" name="manual_semester" required>
                                    <option value="">Pilih Semester</option>
                                    <?php foreach ($semesterList as $s): ?>
                                        <option value="<?= htmlspecialchars($s) ?>">
                                            <?= formatSemester($s) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Panitia (Jabatan) <span class="req">*</span></label>
                                <select class="up-select select2" name="manual_panitia" required>
                                    <option value="">Pilih Panitia</option>
                                    <?php foreach ($panitia as $id => $nama): ?>
                                        <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($nama) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">User (Dosen) <span class="req">*</span></label>
                                <select class="up-select select2" name="manual_user" required>
                                    <option value="">Pilih User</option>
                                    <?php foreach ($users as $id => $nama): ?>
                                        <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($nama) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="up-form-grid">
                            <div class="up-form-group">
                                <label class="up-form-label">Jml Mhs Prodi</label>
                                <input type="number" class="up-input" name="manual_jml_mhs_prodi" min="0" value="0">
                            </div>
                            <div class="up-form-group">
                                <label class="up-form-label">Jml Mahasiswa</label>
                                <input type="number" class="up-input" name="manual_jml_mhs" min="0" value="0">
                            </div>
                            <div class="up-form-group">
                                <label class="up-form-label">Jml Koreksi</label>
                                <input type="number" class="up-input" name="manual_jml_koreksi" min="0" value="0">
                            </div>
                        </div>
                        
                        <div class="up-form-grid">
                            <div class="up-form-group">
                                <label class="up-form-label">Jml Mata Kuliah</label>
                                <input type="number" class="up-input" name="manual_jml_matkul" min="0" value="0">
                            </div>
                            <div class="up-form-group">
                                <label class="up-form-label">Pengawas Pagi</label>
                                <input type="number" class="up-input" name="manual_jml_pgws_pagi" min="0" value="0">
                            </div>
                            <div class="up-form-group">
                                <label class="up-form-label">Pengawas Sore</label>
                                <input type="number" class="up-input" name="manual_jml_pgws_sore" min="0" value="0">
                            </div>
                        </div>
                        
                        <div class="up-form-grid">
                            <div class="up-form-group">
                                <label class="up-form-label">Koordinator Pagi</label>
                                <input type="number" class="up-input" name="manual_jml_koor_pagi" min="0" value="0">
                            </div>
                            <div class="up-form-group">
                                <label class="up-form-label">Koordinator Sore</label>
                                <input type="number" class="up-input" name="manual_jml_koor_sore" min="0" value="0">
                            </div>
                        </div>
                        
                        <div class="up-actions">
                            <button type="submit" name="submit_manual" class="up-btn up-btn-success">
                                <i class="fas fa-save mr-2"></i> Simpan Data Manual
                            </button>
                            <button type="button" class="up-btn up-btn-secondary" onclick="clearManualForm()">
                                <i class="fas fa-redo mr-2"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- DIVIDER -->
            <div class="up-divider"></div>

            <!-- Recent Data -->
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon"><i class="fas fa-history"></i></div>
                    <h5>Data Transaksi Ujian Terbaru</h5>
                    <div class="ml-auto">
                        <div class="up-search-box" style="width: 220px;">
                            <i class="fas fa-search"></i>
                            <input type="text" class="up-search-input" id="searchTu" placeholder="Cari transaksi ujian..." onkeyup="filterTableTu()">
                        </div>
                    </div>
                </div>
                <div class="up-card-body">
                    <div class="up-table-wrap">
                        <table class="up-table" id="tableTu">
                            <thead>
                                <tr>
                                    <th>Semester</th>
                                    <th>Jabatan</th>
                                    <th>User</th>
                                    <th>Mhs Prodi</th>
                                    <th>Mhs</th>
                                    <th>Koreksi</th>
                                    <th>Matkul</th>
                                    <th>Pgws Pagi</th>
                                    <th>Pgws Sore</th>
                                    <th>Koor Pagi</th>
                                    <th>Koor Sore</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_data)): ?>
                                <tr><td colspan="11" class="text-center py-4 text-muted">Belum ada data transaksi ujian</td></tr>
                                <?php else: ?>
                                <?php foreach ($recent_data as $row): ?>
                                <tr>
                                    <td><?= formatSemester($row['semester'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['jbtn_pnt'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['nama_user'] ?? '-') ?></td>
                                    <td><?= $row['jml_mhs_prodi'] ?></td>
                                    <td><?= $row['jml_mhs'] ?></td>
                                    <td><?= $row['jml_koreksi'] ?></td>
                                    <td><?= $row['jml_matkul'] ?></td>
                                    <td><?= $row['jml_pgws_pagi'] ?></td>
                                    <td><?= $row['jml_pgws_sore'] ?></td>
                                    <td><?= $row['jml_koor_pagi'] ?></td>
                                    <td><?= $row['jml_koor_sore'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_tu_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-2" id="recentTu">
                        <small class="text-muted">Total <?= $total_tu_cnt ?> data | Halaman <?= $tu_page ?> dari <?= $total_tu_pages ?></small>
                        <ul class="up-pagination mb-0">
                            <li class="up-page-item <?= ($tu_page <= 1) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?tu_page=<?= $tu_page-1 ?>#recentTu"><i class="fas fa-chevron-left"></i></a>
                            </li>
                            <?php for ($p = max(1, $tu_page-2); $p <= min($total_tu_pages, $tu_page+2); $p++): ?>
                            <li class="up-page-item <?= ($p == $tu_page) ? 'active' : '' ?>">
                                <a class="up-page-link" href="?tu_page=<?= $p ?>#recentTu"><?= $p ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="up-page-item <?= ($tu_page >= $total_tu_pages) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?tu_page=<?= $tu_page+1 ?>#recentTu"><i class="fas fa-chevron-right"></i></a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Select2 CSS dan JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// File input handling
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

function clearUploadForm() {
    const form = document.querySelector('form[enctype="multipart/form-data"]');
    if (form) {
        form.reset();
        resetDropzone();
    }
}

function clearManualForm() {
    const form = document.getElementById('manualForm');
    if (form) form.reset();
    $('.select2').val(null).trigger('change');
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

// Initialize Select2
$(document).ready(function() {
    $('.select2').select2({
        width: '100%',
        placeholder: 'Cari...',
        allowClear: true,
        dropdownParent: $('.select2').closest('.up-form-group')
    });
});

function filterTableTu() {
    var input = document.getElementById("searchTu");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("tableTu");
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