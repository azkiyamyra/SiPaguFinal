<?php
/**
 * UPLOAD DATA PANITIA PA/TA - SiPagu (TEMPLATE BASED WITH DROPDOWN)
 * Halaman untuk upload data panitia PA/TA dari Excel template
 * Lokasi: admin/upload_tpata.php
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
$page_title = "Upload Panitia PA/TA";

// ======================
// AMBIL DATA UNTUK VALIDASI
// ======================

// Ambil data user (dosen)
$users = [];
$query_users = mysqli_query($koneksi, "SELECT id_user, npp_user, nama_user FROM t_user ORDER BY npp_user");
while ($row = mysqli_fetch_assoc($query_users)) {
    $users[$row['id_user']] = $row;
}

// Ambil data panitia (jabatan)
$panitia = [];
$query_panitia = mysqli_query($koneksi, "SELECT id_pnt, jbtn_pnt FROM t_panitia ORDER BY jbtn_pnt");
while ($row = mysqli_fetch_assoc($query_panitia)) {
    $panitia[$row['id_pnt']] = $row;
}

// ======================
// FUNGSI-FUNGSI HELPER
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

// Clean header dari tanda bintang dan spasi
function clean_header($header) {
    if ($header === null || $header === '') {
        return '';
    }
    $header = safe_trim($header);
    $header = preg_replace('/\s+/', ' ', $header);
    return trim($header);
}

// Format semester dari YYYY1/YYYY2 ke format yang lebih mudah dibaca
function formatSemesterDisplay($semester) {
    if (!preg_match('/^\d{4}[12]$/', $semester)) {
        return $semester;
    }

    $tahun = substr($semester, 0, 4);
    $kode  = substr($semester, -1);

    return $tahun . ' ' . ($kode == '1' ? 'Ganjil' : 'Genap');
}

// Generate list semester untuk dropdown
function generateSemesterList($startYear = 2020, $range = 6) {
    $list = [];
    $currentYear = date('Y');

    for ($y = $startYear; $y <= $currentYear + $range; $y++) {
        $list[] = ['value' => $y . '1', 'label' => $y . ' Ganjil'];
        $list[] = ['value' => $y . '2', 'label' => $y . ' Genap'];
    }
    return $list;
}

$semesterList = generateSemesterList(2022, 4);

// Format bulan dari string ke format yang konsisten
function normalizeBulan($bulan) {
    $bulan = strtolower(trim($bulan));
    
    // Mapping singkatan bulan
    $singkatan = [
        'jan' => 'januari', 'feb' => 'februari', 'mar' => 'maret',
        'apr' => 'april', 'may' => 'mei', 'jun' => 'juni',
        'jul' => 'juli', 'aug' => 'agustus', 'sep' => 'september',
        'oct' => 'oktober', 'nov' => 'november', 'dec' => 'desember'
    ];
    
    if (array_key_exists($bulan, $singkatan)) {
        return $singkatan[$bulan];
    }
    
    // Mapping angka bulan
    $angka_bulan = [
        '01' => 'januari', '1' => 'januari',
        '02' => 'februari', '2' => 'februari',
        '03' => 'maret', '3' => 'maret',
        '04' => 'april', '4' => 'april',
        '05' => 'mei', '5' => 'mei',
        '06' => 'juni', '6' => 'juni',
        '07' => 'juli', '7' => 'juli',
        '08' => 'agustus', '8' => 'agustus',
        '09' => 'september', '9' => 'september',
        '10' => 'oktober',
        '11' => 'november',
        '12' => 'desember'
    ];
    
    if (array_key_exists($bulan, $angka_bulan)) {
        return $angka_bulan[$bulan];
    }
    
    return $bulan;
}

// Konversi nilai dari string ke integer
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

// Fungsi untuk mengekstrak ID dari format dropdown
function extractIdFromDropdown($value) {
    if (empty($value)) {
        return 0;
    }
    
    // Jika sudah berupa angka langsung
    if (is_numeric($value)) {
        return (int)$value;
    }
    
    // Ekstrak ID dari format "ID - Nama" atau "ID - NPP - Nama"
    if (preg_match('/^(\d+)\s*-/', $value, $matches)) {
        return (int)$matches[1];
    }
    
    return 0;
}

// ======================
// TEMPLATE COLUMN STRUCTURE
// ======================
$template_columns = [
    'NO',                   // Kolom A
    'SEMESTER',             // Kolom B
    'PERIODE_WISUDA',       // Kolom C
    'ID_USER',              // Kolom D
    'ID_PANITIA',           // Kolom E
    'PRODI',                // Kolom F
    'JML_MHS_PRODI',        // Kolom G
    'JML_MHS_BIMBINGAN',    // Kolom H
    'JML_PGJI_1',           // Kolom I
    'JML_PGJI_2',           // Kolom J
    'KETUA_PGJI'            // Kolom K
];

// Database field mapping (urutan sesuai template)
$db_fields = [
    'NO' => 'skip',
    'SEMESTER' => 'semester',
    'PERIODE_WISUDA' => 'periode_wisuda',
    'ID_USER' => 'id_user',
    'ID_PANITIA' => 'id_panitia',
    'PRODI' => 'prodi',
    'JML_MHS_PRODI' => 'jml_mhs_prodi',
    'JML_MHS_BIMBINGAN' => 'jml_mhs_bimbingan',
    'JML_PGJI_1' => 'jml_pgji_1',
    'JML_PGJI_2' => 'jml_pgji_2',
    'KETUA_PGJI' => 'ketua_pgji'
];

// ======================
// FUNGSI DOWNLOAD TEMPLATE DENGAN DROPDOWN (VERSI DIPERBAIKI)
// ======================
if (isset($_GET['action']) && $_GET['action'] == 'download_template') {
    download_excel_template_with_dropdown();
    exit();
}

function download_excel_template_with_dropdown() {
    global $koneksi;
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // ======================
    // AMBIL DATA UNTUK DROPDOWN (REFRESH)
    // ======================
    
    // Ambil data user (dosen) untuk dropdown
    $users_dropdown = [];
    $query_users = mysqli_query($koneksi, "SELECT id_user, npp_user, nama_user FROM t_user ORDER BY npp_user");
    while ($row = mysqli_fetch_assoc($query_users)) {
        $users_dropdown[] = $row;
    }
    
    // Ambil data panitia untuk dropdown
    $panitia_dropdown = [];
    $query_panitia = mysqli_query($koneksi, "SELECT id_pnt, jbtn_pnt FROM t_panitia ORDER BY jbtn_pnt");
    while ($row = mysqli_fetch_assoc($query_panitia)) {
        $panitia_dropdown[] = $row;
    }
    
    // Generate list semester untuk dropdown
    $semester_dropdown = [];
    $startYear = 2020;
    $currentYear = date('Y');
    $range = 6;
    
    for ($y = $startYear; $y <= $currentYear + $range; $y++) {
        $semester_dropdown[] = ['value' => $y . '1', 'label' => $y . ' Ganjil'];
        $semester_dropdown[] = ['value' => $y . '2', 'label' => $y . ' Genap'];
    }
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('SiPagu System')
        ->setLastModifiedBy('SiPagu System')
        ->setTitle('Template Import Data Panitia PA/TA SiPagu')
        ->setDescription('Template untuk mengimport data panitia PA/TA ke sistem SiPagu');
    
    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(5);   // NO
    $sheet->getColumnDimension('B')->setWidth(20);  // SEMESTER
    $sheet->getColumnDimension('C')->setWidth(20);  // PERIODE_WISUDA
    $sheet->getColumnDimension('D')->setWidth(40);  // ID_USER (lebih lebar untuk dropdown)
    $sheet->getColumnDimension('E')->setWidth(35);  // ID_PANITIA (lebih lebar untuk dropdown)
    $sheet->getColumnDimension('F')->setWidth(12);  // PRODI
    $sheet->getColumnDimension('G')->setWidth(15);  // JML_MHS_PRODI
    $sheet->getColumnDimension('H')->setWidth(18);  // JML_MHS_BIMBINGAN
    $sheet->getColumnDimension('I')->setWidth(15);  // JML_PGJI_1
    $sheet->getColumnDimension('J')->setWidth(15);  // JML_PGJI_2
    $sheet->getColumnDimension('K')->setWidth(30);  // KETUA_PGJI
    
    // Title (baris 1)
    $sheet->mergeCells('A1:K1');
    $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA PANITIA PA/TA - SIPAGU');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E86C1']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    
    // Instructions (baris 2-9) - MERGED CELLS
    $sheet->mergeCells('A2:K9');
    $instructions = "PETUNJUK PENGISIAN:\n\n" .
                    "1. Isi data mulai baris ke-11\n" .
                    "2. SEMUA KOLOM WAJIB DIISI (kecuali NO opsional)!\n" .
                    "3. Cara menggunakan dropdown:\n" .
                    "   - Klik sel pada kolom SEMESTER, ID_USER, atau ID_PANITIA\n" .
                    "   - Akan muncul panah dropdown, klik untuk memilih value\n" .
                    "   - Pilih value yang sesuai dari daftar\n" .
                    "4. Format PERIODE_WISUDA (bisa menggunakan 3 format):\n" .
                    "   - Nama lengkap: januari, februari, maret, dll\n" .
                    "   - Singkatan: jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, dec\n" .
                    "   - Angka: 01, 02, 03, 04, 05, 06, 07, 08, 09, 10, 11, 12\n" .
                    "5. PRODI: Kode program studi (SI, TI, MI, dll)\n\n" .
                    "6. JANGAN ubah nama kolom (baris ke-10)!\n" .
                    "7. HAPUS DATA CONTOH PADA BARIS KE-11 SEBELUM DIUPLOAD!";
    
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
    $sheet->getRowDimension(2)->setRowHeight(380);
    
    // ======================
    // BUAT SHEET UNTUK DROPDOWN LISTS
    // ======================
    
    // Buat sheet baru untuk dropdown lists
    $dropdownSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'DropdownLists');
    $spreadsheet->addSheet($dropdownSheet);
    
    // Header untuk sheet dropdown
    $dropdownSheet->setCellValue('A1', 'SEMESTER');
    $dropdownSheet->setCellValue('B1', 'ID_USER');
    $dropdownSheet->setCellValue('C1', 'ID_PANITIA');
    
    $dropdownSheet->getStyle('A1:C1')->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4E6F1']]
    ]);
    
    // Isi data semester (kolom A)
    $row_semester = 2;
    foreach ($semester_dropdown as $semester) {
        $dropdownSheet->setCellValue('A' . $row_semester, $semester['value']);
        $row_semester++;
    }
    
    // Isi data user (kolom B) - format: "ID - NPP - Nama"
    $row_user = 2;
    foreach ($users_dropdown as $user) {
        $dropdownSheet->setCellValue('B' . $row_user, $user['id_user'] . ' - ' . $user['npp_user'] . ' - ' . $user['nama_user']);
        $row_user++;
    }
    
    // Isi data panitia (kolom C) - format: "ID - Jabatan"
    $row_panitia = 2;
    foreach ($panitia_dropdown as $p) {
        $dropdownSheet->setCellValue('C' . $row_panitia, $p['id_pnt'] . ' - ' . $p['jbtn_pnt']);
        $row_panitia++;
    }
    
    // Set column widths untuk sheet dropdown
    $dropdownSheet->getColumnDimension('A')->setWidth(15);
    $dropdownSheet->getColumnDimension('B')->setWidth(50);
    $dropdownSheet->getColumnDimension('C')->setWidth(40);
    
    // Sembunyikan sheet dropdown
    $dropdownSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
    
    // Column headers (baris 10)
    $headers = [
        'NO',
        'SEMESTER (pilih dari dropdown)',
        'PERIODE_WISUDA',
        'ID_USER (pilih dari dropdown)',
        'ID_PANITIA (pilih dari dropdown)',
        'PRODI',
        'JML_MHS_PRODI',
        'JML_MHS_BIMBINGAN',
        'JML_PGJI_1',
        'JML_PGJI_2',
        'KETUA_PGJI'
    ];
    
    $col = 1;
    foreach ($headers as $header) {
        $cell = Coordinate::stringFromColumnIndex($col) . '10';
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
    $sheet->getRowDimension(10)->setRowHeight(40);
    
    // Contoh data (baris 11)
    // Ambil contoh user pertama dan panitia pertama untuk contoh
    $sample_user = !empty($users_dropdown) ? $users_dropdown[0]['id_user'] . ' - ' . $users_dropdown[0]['npp_user'] . ' - ' . $users_dropdown[0]['nama_user'] : '1 - NPP001 - Contoh Dosen';
    $sample_panitia = !empty($panitia_dropdown) ? $panitia_dropdown[0]['id_pnt'] . ' - ' . $panitia_dropdown[0]['jbtn_pnt'] : '1 - Ketua Panitia';
    
    $sample_data = [
        [
            '1',
            '20241',
            'november',
            $sample_user,
            $sample_panitia,
            'TI',
            '50',
            '10',
            '2',
            '2',
            'Dr. Ahmad, M.Kom'
        ],
    ];
    
    $row = 11;
    foreach ($sample_data as $data) {
        $col = 1;
        foreach ($data as $value) {
            $cell = Coordinate::stringFromColumnIndex($col) . $row;
            $sheet->setCellValue($cell, $value);
            
            // Format number untuk kolom angka
            if (in_array($col, [7, 8, 9, 10])) { // JML_MHS_PRODI, JML_MHS_BIMBINGAN, JML_PGJI_1, JML_PGJI_2
                $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('0');
            }
            
            $col++;
        }
        
        // Style untuk data contoh
        $styleRange = 'A' . $row . ':K' . $row;
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
    
    // ======================
    // TERAPKAN DROPDOWN UNTUK KOLOM B (SEMESTER) - VERSI DIPERBAIKI
    // ======================
    for ($i = 11; $i <= 110; $i++) {
        $cellCoordinate = 'B' . $i;
        $validation = $sheet->getCell($cellCoordinate)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP); // Ini sudah benar
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Semester Tidak Valid');
        $validation->setError('Pilih semester dari dropdown!');
        $validation->setPromptTitle('Pilih Semester');
        $validation->setPrompt('Silakan pilih semester dari daftar berikut:');
        $validation->setFormula1('DropdownLists!$A$2:$A$' . ($row_semester - 1));
    }
    
    // ======================
    // TERAPKAN DROPDOWN UNTUK KOLOM D (ID_USER) - VERSI DIPERBAIKI
    // ======================
    for ($i = 11; $i <= 110; $i++) {
        $cellCoordinate = 'D' . $i;
        $validation = $sheet->getCell($cellCoordinate)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('ID User Tidak Valid');
        $validation->setError('Pilih user dari dropdown!');
        $validation->setPromptTitle('Pilih User');
        $validation->setPrompt('Silakan pilih user dari daftar berikut:');
        $validation->setFormula1('DropdownLists!$B$2:$B$' . ($row_user - 1));
    }
    
    // ======================
    // TERAPKAN DROPDOWN UNTUK KOLOM E (ID_PANITIA) - VERSI DIPERBAIKI
    // ======================
    for ($i = 11; $i <= 110; $i++) {
        $cellCoordinate = 'E' . $i;
        $validation = $sheet->getCell($cellCoordinate)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('ID Panitia Tidak Valid');
        $validation->setError('Pilih panitia dari dropdown!');
        $validation->setPromptTitle('Pilih Panitia');
        $validation->setPrompt('Silakan pilih panitia dari daftar berikut:');
        $validation->setFormula1('DropdownLists!$C$2:$C$' . ($row_panitia - 1));
    }
    
    // Tambahkan komentar pada sel header untuk petunjuk
    $sheet->getComment('B10')->getText()->createTextRun("Klik dropdown untuk memilih semester.\nContoh: 20241 = 2024 Ganjil, 20242 = 2024 Genap");
    $sheet->getComment('D10')->getText()->createTextRun("Klik dropdown untuk memilih user.\nFormat: ID - NPP - Nama");
    $sheet->getComment('E10')->getText()->createTextRun("Klik dropdown untuk memilih panitia.\nFormat: ID - Jabatan");
    
    // Format dan border untuk area data (100 baris total)
    $dataRange = 'A10:K110';
    $sheet->getStyle($dataRange)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'D6DBDF']
            ]
        ]
    ]);
    
    // Set alignment
    $sheet->getStyle('A11:A110')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B11:F110')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('G11:K110')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // Auto filter untuk header
    $sheet->setAutoFilter('A10:K10');
    
    // Freeze pane (header tetap terlihat)
    $sheet->freezePane('A11');
    
    // Kembalikan ke sheet utama
    $spreadsheet->setActiveSheetIndex(0);
    
    // Output file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Template_Transaksi_PA-TA_SiPagu.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ======================
// FUNGSI FIND HEADER ROW
// ======================

function find_header_row($sheetData) {
    // Cari dari baris 0 sampai 20
    for ($i = 0; $i < min(20, count($sheetData)); $i++) {
        $row = array_map('clean_header', $sheetData[$i]);
        
        // Hitung jumlah kolom yang tidak kosong di baris ini
        $non_empty_count = 0;
        foreach ($row as $cell) {
            if (!empty($cell)) {
                $non_empty_count++;
            }
        }
        
        // Baris petunjuk biasanya hanya memiliki 1 kolom yang tidak kosong (merged cells)
        // Baris header biasanya memiliki minimal 6 kolom yang tidak kosong
        if ($non_empty_count >= 6) {
            $row_upper = array_map('strtoupper', $row);
            
            $found_semester = false;
            $found_periode = false;
            $found_id_user = false;
            $found_id_panitia = false;
            $found_prodi = false;
            
            foreach ($row_upper as $header) {
                if (strpos($header, 'SEMESTER') !== false) $found_semester = true;
                if (strpos($header, 'PERIODE') !== false || strpos($header, 'WISUDA') !== false) $found_periode = true;
                if (strpos($header, 'ID_USER') !== false || (strpos($header, 'ID') !== false && strpos($header, 'USER') !== false)) $found_id_user = true;
                if (strpos($header, 'ID_PANITIA') !== false || (strpos($header, 'ID') !== false && strpos($header, 'PANITIA') !== false)) $found_id_panitia = true;
                if (strpos($header, 'PRODI') !== false) $found_prodi = true;
            }
            
            // Jika minimal 3 field wajib ditemukan, return baris ini
            $wajib_found = 0;
            if ($found_semester) $wajib_found++;
            if ($found_periode) $wajib_found++;
            if ($found_id_user) $wajib_found++;
            if ($found_id_panitia) $wajib_found++;
            if ($found_prodi) $wajib_found++;
            
            if ($wajib_found >= 3) {
                return $i;
            }
        }
    }
    
    return -1; // Tidak ditemukan
}

// ======================
// FUNGSI VALIDASI TEMPLATE
// ======================

function validate_template_structure($file_path, $file_ext) {
    try {
        $reader = IOFactory::createReaderForFile($file_path);
        if ($file_ext == 'csv') {
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
        }
        
        $spreadsheet = $reader->load($file_path);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        
        // Cari baris header
        $header_row = find_header_row($sheetData);
        
        if ($header_row === -1) {
            return ['valid' => false, 'message' => 'Header template tidak ditemukan. Pastikan file menggunakan template yang benar dan data dimulai dari baris ke-11.'];
        }
        
        // Ambil baris header yang ditemukan
        $found_headers = array_map('clean_header', $sheetData[$header_row]);
        $found_headers_upper = array_map('strtoupper', $found_headers);
        
        // Validasi jumlah kolom
        if (count($found_headers) < 6) {
            return ['valid' => false, 'message' => 'Jumlah kolom tidak mencukupi. Minimal 6 kolom (SEMESTER, PERIODE_WISUDA, ID_USER, ID_PANITIA, PRODI, dll)'];
        }
        
        // Deteksi field wajib
        $detected_fields = [];
        $missing_fields = [];
        
        $required_patterns = [
            'SEMESTER' => '/SEMESTER/',
            'PERIODE_WISUDA' => '/PERIODE|WISUDA/',
            'ID_USER' => '/ID.*USER|USER.*ID/',
            'ID_PANITIA' => '/ID.*PANITIA|PANITIA.*ID/',
            'PRODI' => '/PRODI|PROGRAM.*STUDI|JURUSAN/'
        ];
        
        foreach ($required_patterns as $field => $pattern) {
            $found = false;
            foreach ($found_headers_upper as $header) {
                if (preg_match($pattern, $header)) {
                    $found = true;
                    $detected_fields[] = $field;
                    break;
                }
            }
            if (!$found) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            return [
                'valid' => false, 
                'message' => 'Field wajib tidak ditemukan: ' . implode(', ', $missing_fields) . 
                             '<br>Header ditemukan: ' . implode(', ', $found_headers)
            ];
        }
        
        return [
            'valid' => true, 
            'header_row' => $header_row, 
            'message' => 'Template valid. Header ditemukan di baris ' . ($header_row + 1),
            'headers' => $found_headers
        ];
        
    } catch (Exception $e) {
        return ['valid' => false, 'message' => 'Error membaca file: ' . $e->getMessage()];
    }
}

// ======================
// FUNGSI GET PREVIEW DATA
// ======================

function get_preview_data($file_path, $file_ext) {
    try {
        $reader = IOFactory::createReaderForFile($file_path);
        if ($file_ext == 'csv') {
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
        }
        
        $spreadsheet = $reader->load($file_path);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        
        // Cari header row
        $header_row = find_header_row($sheetData);
        
        if ($header_row === -1) {
            return false;
        }
        
        // Ambil headers
        $headers = array_map('clean_header', $sheetData[$header_row]);
        
        // Ambil 5 baris data pertama setelah header
        $sample_data = [];
        $total_rows = 0;
        
        for ($i = $header_row + 1; $i < min($header_row + 10, count($sheetData)); $i++) {
            $row = array_map(function($cell) {
                return safe_trim($cell);
            }, $sheetData[$i]);
            
            // Skip baris yang benar-benar kosong
            if (!empty(array_filter($row, function($val) {
                return $val !== '' && $val !== null;
            }))) {
                $sample_data[] = $row;
                if (count($sample_data) >= 5) break;
            }
        }
        
        // Hitung total baris data (non-kosong)
        for ($i = $header_row + 1; $i < count($sheetData); $i++) {
            $row = array_map(function($cell) {
                return safe_trim($cell);
            }, $sheetData[$i]);
            
            if (!empty(array_filter($row, function($val) {
                return $val !== '' && $val !== null;
            }))) {
                $total_rows++;
            }
        }
        
        return [
            'headers' => $headers,
            'sample_data' => $sample_data,
            'total_rows' => $total_rows,
            'header_row' => $header_row
        ];
        
    } catch (Exception $e) {
        error_log("Error in get_preview_data: " . $e->getMessage());
        return false;
    }
}

// ======================
// PROSES UPLOAD
// ======================

// Direktori untuk file sementara
$temp_dir = __DIR__ . '/../storage/tmp_uploads/';
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

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
        
        // Validasi ekstensi file
        $allowed_ext = ['xls', 'xlsx', 'csv'];
        if (!in_array($file_ext, $allowed_ext)) {
            $error_message = 'File harus bertipe XLS, XLSX, atau CSV.';
        }
        // Validasi ukuran file (10MB max)
        elseif ($file_size > 10 * 1024 * 1024) {
            $error_message = 'File terlalu besar. Maksimal 10MB.';
        }
        else {
            // Generate unique filename
            $unique_name = 'upload_tpata_' . time() . '_' . uniqid() . '.' . $file_ext;
            $temp_file_path = $temp_dir . $unique_name;
            
            // Save file to temp directory
            if (move_uploaded_file($file_tmp, $temp_file_path)) {
                // Validate template structure
                $validation_result = validate_template_structure($temp_file_path, $file_ext);
                
                if ($validation_result['valid']) {
                    // Get preview data
                    $preview_data = get_preview_data($temp_file_path, $file_ext);
                    if ($preview_data) {
                        $preview_data['temp_file'] = $unique_name;
                        $_SESSION['upload_temp_file_tpata'] = $unique_name;
                        $_SESSION['overwrite_option_tpata'] = $_POST['overwrite'] ?? '0';
                        $success_message = "✅ Template valid! Preview data ditemukan: " . $preview_data['total_rows'] . " baris data.";
                    } else {
                        $error_message = "❌ Gagal membaca data dari file.";
                        unlink($temp_file_path);
                    }
                } else {
                    $error_message = "❌ Format template tidak valid! " . $validation_result['message'];
                    $error_message .= "<br>Download template yang benar: <a href='?action=download_template' class='btn btn-sm btn-success ml-2'><i class='fas fa-download'></i> Download Template</a>";
                    unlink($temp_file_path);
                }
            } else {
                $error_message = "❌ Gagal menyimpan file sementara.";
            }
        }
    }
    
    // CONFIRM DAN IMPORT DATA
    elseif (isset($_POST['confirm_import']) && isset($_SESSION['upload_temp_file_tpata'])) {
        $temp_file = $_SESSION['upload_temp_file_tpata'];
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
                
                // Cari header row
                $header_row = find_header_row($sheetData);
                
                if ($header_row === -1) {
                    $error_message = "❌ Header tidak ditemukan dalam file! Pastikan menggunakan template yang benar.";
                } else {
                    // Ambil headers
                    $headers = $sheetData[$header_row];
                    
                    // Buat mapping kolom
                    $column_mapping = [];
                    foreach ($headers as $colIndex => $header) {
                        $header_clean = clean_header($header);
                        $header_upper = strtoupper($header_clean);
                        
                        // Mapping berdasarkan keyword
                        if (strpos($header_upper, 'NO') !== false && strlen($header_clean) < 10) {
                            $column_mapping[$colIndex] = 'skip';
                        }
                        elseif (strpos($header_upper, 'SEMESTER') !== false) {
                            $column_mapping[$colIndex] = 'semester';
                        }
                        elseif (strpos($header_upper, 'PERIODE') !== false || strpos($header_upper, 'WISUDA') !== false) {
                            $column_mapping[$colIndex] = 'periode_wisuda';
                        }
                        elseif ((strpos($header_upper, 'ID') !== false && strpos($header_upper, 'USER') !== false)) {
                            $column_mapping[$colIndex] = 'id_user';
                        }
                        elseif ((strpos($header_upper, 'ID') !== false && strpos($header_upper, 'PANITIA') !== false)) {
                            $column_mapping[$colIndex] = 'id_panitia';
                        }
                        elseif (strpos($header_upper, 'PRODI') !== false || strpos($header_upper, 'PROGRAM') !== false) {
                            $column_mapping[$colIndex] = 'prodi';
                        }
                        elseif (strpos($header_upper, 'MHS_PRODI') !== false || (strpos($header_upper, 'MAHASISWA') !== false && strpos($header_upper, 'PRODI') !== false)) {
                            $column_mapping[$colIndex] = 'jml_mhs_prodi';
                        }
                        elseif (strpos($header_upper, 'MHS_BIMBINGAN') !== false || (strpos($header_upper, 'MAHASISWA') !== false && strpos($header_upper, 'BIMBINGAN') !== false)) {
                            $column_mapping[$colIndex] = 'jml_mhs_bimbingan';
                        }
                        elseif (strpos($header_upper, 'PGJI_1') !== false || (strpos($header_upper, 'PENGUJI') !== false && strpos($header_upper, '1') !== false)) {
                            $column_mapping[$colIndex] = 'jml_pgji_1';
                        }
                        elseif (strpos($header_upper, 'PGJI_2') !== false || (strpos($header_upper, 'PENGUJI') !== false && strpos($header_upper, '2') !== false)) {
                            $column_mapping[$colIndex] = 'jml_pgji_2';
                        }
                        elseif (strpos($header_upper, 'KETUA') !== false && strpos($header_upper, 'PGJI') !== false) {
                            $column_mapping[$colIndex] = 'ketua_pgji';
                        }
                        else {
                            $column_mapping[$colIndex] = 'skip';
                        }
                    }
                    
                    // Validasi mapping
                    $mapped_fields = array_values($column_mapping);
                    $required_fields = ['semester', 'periode_wisuda', 'id_user', 'id_panitia', 'prodi'];
                    $missing_fields = [];
                    
                    foreach ($required_fields as $field) {
                        if (!in_array($field, $mapped_fields)) {
                            $missing_fields[] = $field;
                        }
                    }
                    
                    if (!empty($missing_fields)) {
                        $error_message = "❌ Field wajib berikut tidak ditemukan dalam file: " . implode(', ', $missing_fields);
                    }
                    
                    if (empty($error_message)) {
                        $startRow = $header_row + 1;
                        $jumlahData = 0;
                        $jumlahGagal = 0;
                        $errors = [];
                        $overwrite = isset($_SESSION['overwrite_option_tpata']) && $_SESSION['overwrite_option_tpata'] == '1';
                        
                        // Mulai transaksi database
                        mysqli_begin_transaction($koneksi);
                        
                        try {
                            for ($i = $startRow; $i < count($sheetData); $i++) {
                                $rowData = $sheetData[$i];
                                
                                // Skip baris kosong
                                $isEmpty = true;
                                foreach ($rowData as $cell) {
                                    if ($cell !== null && $cell !== '' && trim((string)$cell) !== '') {
                                        $isEmpty = false;
                                        break;
                                    }
                                }
                                
                                if ($isEmpty) {
                                    continue;
                                }
                                
                                // Extract data berdasarkan mapping
                                $data = [];
                                foreach ($column_mapping as $colIndex => $dbField) {
                                    if ($dbField != 'skip' && isset($rowData[$colIndex])) {
                                        $data[$dbField] = safe_trim($rowData[$colIndex]);
                                    }
                                }
                                
                                // Validasi data wajib
                                if (empty($data['semester'])) {
                                    $errors[] = "Baris " . ($i+1) . ": Semester tidak boleh kosong";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                if (empty($data['periode_wisuda'])) {
                                    $errors[] = "Baris " . ($i+1) . ": Periode wisuda tidak boleh kosong";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                if (empty($data['id_user'])) {
                                    $errors[] = "Baris " . ($i+1) . ": ID User tidak boleh kosong";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                if (empty($data['id_panitia'])) {
                                    $errors[] = "Baris " . ($i+1) . ": ID Panitia tidak boleh kosong";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                if (empty($data['prodi'])) {
                                    $errors[] = "Baris " . ($i+1) . ": Prodi tidak boleh kosong";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                // Validasi format semester
                                $semester = $data['semester'];
                                if (!preg_match('/^\d{4}[12]$/', $semester)) {
                                    // Coba perbaiki format
                                    if (preg_match('/^\d{4}$/', $semester)) {
                                        $semester = $semester . '1'; // Default ganjil
                                    } else {
                                        $errors[] = "Baris " . ($i+1) . ": Format semester tidak valid: '$semester' (harus YYYY1 atau YYYY2)";
                                        $jumlahGagal++;
                                        continue;
                                    }
                                }
                                
                                // Normalisasi periode wisuda
                                $periode_normalized = normalizeBulan($data['periode_wisuda']);
                                
                                // Extract ID dari format dropdown
                                $id_user = extractIdFromDropdown($data['id_user']);
                                if ($id_user <= 0) {
                                    $errors[] = "Baris " . ($i+1) . ": ID User tidak valid: '" . $data['id_user'] . "'";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                // Extract ID panitia dari format dropdown
                                $id_panitia = extractIdFromDropdown($data['id_panitia']);
                                if ($id_panitia <= 0) {
                                    $errors[] = "Baris " . ($i+1) . ": ID Panitia tidak valid: '" . $data['id_panitia'] . "'";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                // Parse angka untuk field numerik
                                $jml_mhs_prodi = parseToInt($data['jml_mhs_prodi'] ?? 0);
                                $jml_mhs_bimbingan = parseToInt($data['jml_mhs_bimbingan'] ?? 0);
                                $jml_pgji_1 = parseToInt($data['jml_pgji_1'] ?? 0);
                                $jml_pgji_2 = parseToInt($data['jml_pgji_2'] ?? 0);
                                $ketua_pgji = $data['ketua_pgji'] ?? '';
                                
                                // Cek user di database
                                $cekUser = mysqli_query($koneksi,
                                    "SELECT id_user, nama_user FROM t_user WHERE id_user = '$id_user'"
                                );
                                
                                if (mysqli_num_rows($cekUser) == 0) {
                                    $errors[] = "Baris " . ($i+1) . ": ID User '$id_user' tidak ditemukan di database";
                                    $jumlahGagal++;
                                    continue;
                                }
                                $user_data = mysqli_fetch_assoc($cekUser);
                                
                                // Cek panitia di database
                                $cekPanitia = mysqli_query($koneksi,
                                    "SELECT id_pnt, jbtn_pnt FROM t_panitia WHERE id_pnt = '$id_panitia'"
                                );
                                
                                if (mysqli_num_rows($cekPanitia) == 0) {
                                    $errors[] = "Baris " . ($i+1) . ": ID Panitia '$id_panitia' tidak ditemukan di database";
                                    $jumlahGagal++;
                                    continue;
                                }
                                $panitia_data = mysqli_fetch_assoc($cekPanitia);
                                
                                $semester_escaped = mysqli_real_escape_string($koneksi, $semester);
                                $periode_escaped = mysqli_real_escape_string($koneksi, $periode_normalized);
                                $prodi_escaped = mysqli_real_escape_string($koneksi, $data['prodi']);
                                $ketua_escaped = mysqli_real_escape_string($koneksi, $ketua_pgji);
                                
                                // Cek duplikasi
                                $cekDuplikat = mysqli_query($koneksi,
                                    "SELECT id_tpt FROM t_transaksi_pa_ta 
                                     WHERE semester = '$semester_escaped' 
                                     AND id_user = '$id_user' 
                                     AND id_panitia = '$id_panitia'
                                     AND prodi = '$prodi_escaped'"
                                );
                                
                                if (mysqli_num_rows($cekDuplikat) > 0) {
                                    if ($overwrite) {
                                        // Update data
                                        $update = mysqli_query($koneksi, "
                                            UPDATE t_transaksi_pa_ta
                                            SET periode_wisuda = '$periode_escaped',
                                                jml_mhs_prodi = '$jml_mhs_prodi',
                                                jml_mhs_bimbingan = '$jml_mhs_bimbingan',
                                                jml_pgji_1 = '$jml_pgji_1',
                                                jml_pgji_2 = '$jml_pgji_2',
                                                ketua_pgji = '$ketua_escaped'
                                            WHERE semester = '$semester_escaped' 
                                            AND id_user = '$id_user' 
                                            AND id_panitia = '$id_panitia'
                                            AND prodi = '$prodi_escaped'
                                        ");
                                        
                                        if ($update) {
                                            $jumlahData++;
                                        } else {
                                            $errors[] = "Baris " . ($i+1) . ": Gagal mengupdate data - " . mysqli_error($koneksi);
                                            $jumlahGagal++;
                                        }
                                    } else {
                                        $errors[] = "Baris " . ($i+1) . ": Data untuk kombinasi ini sudah ada (semester: $semester, user: $id_user, panitia: $id_panitia, prodi: $prodi_escaped)";
                                        $jumlahGagal++;
                                    }
                                    continue;
                                }
                                
                                // Insert data baru
                                $insert = mysqli_query($koneksi, "
                                    INSERT INTO t_transaksi_pa_ta
                                    (semester, periode_wisuda, id_user, id_panitia, prodi, 
                                     jml_mhs_prodi, jml_mhs_bimbingan, jml_pgji_1, jml_pgji_2, ketua_pgji)
                                    VALUES
                                    ('$semester_escaped', '$periode_escaped', '$id_user', '$id_panitia', '$prodi_escaped',
                                     '$jml_mhs_prodi', '$jml_mhs_bimbingan', 
                                     '$jml_pgji_1', '$jml_pgji_2', '$ketua_escaped')
                                ");
                                
                                if ($insert) {
                                    $jumlahData++;
                                } else {
                                    $errors[] = "Baris " . ($i+1) . ": Gagal menyimpan data - " . mysqli_error($koneksi);
                                    $jumlahGagal++;
                                }
                            }
                            
                            // Commit transaksi
                            mysqli_commit($koneksi);
                            
                        } catch (Exception $e) {
                            mysqli_rollback($koneksi);
                            $errors[] = "Error sistem: " . $e->getMessage();
                            $jumlahGagal++;
                        }
                        
                        // Clean up temp file
                        unlink($temp_file_path);
                        unset($_SESSION['upload_temp_file_tpata']);
                        unset($_SESSION['overwrite_option_tpata']);
                        
                        if ($jumlahData > 0) {
                            $success_message = "✅ Berhasil mengimport <strong>$jumlahData</strong> data panitia PA/TA.";
                            if ($jumlahGagal > 0) {
                                $success_message .= " <strong>$jumlahGagal</strong> data gagal.";
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
                }
                
            } catch (Exception $e) {
                $error_message = "❌ Terjadi kesalahan: " . $e->getMessage();
                
                if (isset($temp_file_path) && file_exists($temp_file_path)) {
                    unlink($temp_file_path);
                }
                unset($_SESSION['upload_temp_file_tpata']);
                unset($_SESSION['overwrite_option_tpata']);
            }
        } else {
            $error_message = "❌ File sementara tidak ditemukan.";
            unset($_SESSION['upload_temp_file_tpata']);
            unset($_SESSION['overwrite_option_tpata']);
        }
    }
    
    // MANUAL INPUT
    elseif (isset($_POST['submit_manual'])) {
        $manual_semester = $_POST['manual_semester'] ?? '';
        $manual_periode = $_POST['manual_periode'] ?? '';
        $manual_user = $_POST['manual_user'] ?? '';
        $manual_panitia = $_POST['manual_panitia'] ?? '';
        $manual_prodi = $_POST['manual_prodi'] ?? '';
        $manual_jml_mhs_prodi = $_POST['manual_jml_mhs_prodi'] ?? '0';
        $manual_jml_mhs_bimbingan = $_POST['manual_jml_mhs_bimbingan'] ?? '0';
        $manual_jml_pgji_1 = $_POST['manual_jml_pgji_1'] ?? '0';
        $manual_jml_pgji_2 = $_POST['manual_jml_pgji_2'] ?? '0';
        $manual_ketua_pgji = $_POST['manual_ketua_pgji'] ?? '';

        // Validasi
        if (empty($manual_semester) || empty($manual_periode) || empty($manual_user) || 
            empty($manual_panitia) || empty($manual_prodi)) {
            $error_message = "❌ Semua field wajib harus diisi!";
        } elseif (!is_numeric($manual_user) || !is_numeric($manual_panitia)) {
            $error_message = "❌ ID User dan ID Panitia harus angka!";
        } elseif (!preg_match('/^\d{4}[12]$/', $manual_semester)) {
            $error_message = "❌ Format semester tidak valid! Harus YYYY1 atau YYYY2";
        } else {
            // Normalisasi periode
            $periode_normalized = normalizeBulan($manual_periode);
            
            // Cek user
            $cekUser = mysqli_query($koneksi,
                "SELECT id_user FROM t_user WHERE id_user = '$manual_user'"
            );
            
            if (mysqli_num_rows($cekUser) == 0) {
                $error_message = "❌ ID User tidak ditemukan!";
            } else {
                // Cek panitia
                $cekPanitia = mysqli_query($koneksi,
                    "SELECT id_pnt FROM t_panitia WHERE id_pnt = '$manual_panitia'"
                );
                
                if (mysqli_num_rows($cekPanitia) == 0) {
                    $error_message = "❌ ID Panitia tidak ditemukan!";
                } else {
                    // Cek duplikasi
                    $cekDuplikat = mysqli_query($koneksi,
                        "SELECT id_tpt FROM t_transaksi_pa_ta 
                         WHERE semester = '$manual_semester' 
                         AND id_user = '$manual_user' 
                         AND id_panitia = '$manual_panitia'
                         AND prodi = '$manual_prodi'"
                    );
                    
                    if (mysqli_num_rows($cekDuplikat) > 0) {
                        $error_message = "⚠️ Data untuk kombinasi ini sudah ada!";
                    } else {
                        $insert_manual = mysqli_query($koneksi, "
                            INSERT INTO t_transaksi_pa_ta
                            (semester, periode_wisuda, id_user, id_panitia, prodi, 
                             jml_mhs_prodi, jml_mhs_bimbingan, jml_pgji_1, jml_pgji_2, ketua_pgji)
                            VALUES
                            ('$manual_semester', '$periode_normalized', '$manual_user', '$manual_panitia', '$manual_prodi',
                             '$manual_jml_mhs_prodi', '$manual_jml_mhs_bimbingan', 
                             '$manual_jml_pgji_1', '$manual_jml_pgji_2', '$manual_ketua_pgji')
                        ");
                        
                        if ($insert_manual) {
                            $success_message = "✅ Data panitia PA/TA berhasil disimpan!";
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
if (is_dir($temp_dir)) {
    $files = glob($temp_dir . 'upload_tpata_*');
    foreach ($files as $file) {
        if (filemtime($file) < time() - 3600) {
            unlink($file);
        }
    }
}

// Ambil data terbaru untuk preview dengan pagination
$tpata_page = isset($_GET['tpata_page']) ? max(1, (int)$_GET['tpata_page']) : 1;
$tpata_per_page = 5;
$total_tpata_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_pa_ta");
$total_tpata_cnt = mysqli_fetch_assoc($total_tpata_q)['total'];
$total_tpata_pages = max(1, ceil($total_tpata_cnt / $tpata_per_page));
if ($tpata_page > $total_tpata_pages) $tpata_page = $total_tpata_pages;
$tpata_offset = ($tpata_page - 1) * $tpata_per_page;

$query = mysqli_query($koneksi, 
    "SELECT tp.*, u.nama_user, p.jbtn_pnt
     FROM t_transaksi_pa_ta tp
     LEFT JOIN t_user u ON tp.id_user = u.id_user
     LEFT JOIN t_panitia p ON tp.id_panitia = p.id_pnt
     ORDER BY tp.id_tpt DESC 
     LIMIT $tpata_offset, $tpata_per_page"
);
$recent_data = [];
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
            <h1><i class="fas fa-user-graduate mr-2"></i>Upload Data Panitia PA/TA</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Upload Panitia PA/TA</div>
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

            <!-- Template Info & Data Tersedia -->
            <div class="up-step-grid">
                <!-- Download Template Card -->
                <div class="up-step-card">
                    <div class="up-step-num">1</div>
                    <h5><i class="fas fa-download mr-2 text-info"></i>Download Template</h5>
                    <p class="text-muted small">Template Excel dengan dropdown untuk Semester, ID User, dan ID Panitia.</p>
                    
                    <a href="?action=download_template" class="up-btn up-btn-download btn-block">
                        <i class="fas fa-file-excel mr-2"></i> Download Template Panitia PA/TA
                    </a>
                    
                    <span class="up-note mt-3">
                        <i class="fas fa-info-circle"></i> Kolom SEMESTER, ID_USER, dan ID_PANITIA menggunakan dropdown
                    </span>
                </div>

                <!-- Info Data Tersedia Card -->
                <div class="up-step-card up-step-info">
                    <div class="up-step-num">2</div>
                    <h5><i class="fas fa-list mr-2 text-info"></i>Info Data Tersedia</h5>
                    
                    <p class="mb-2">Total <strong><?= count($users) ?></strong> dosen dan <strong><?= count($panitia) ?></strong> jabatan panitia:</p>
                    
                    <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>NPP</th>
                                    <th>Nama Dosen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $user_sample = array_slice($users, 0, 3);
                                foreach ($user_sample as $id => $user): 
                                ?>
                                <tr>
                                    <td><strong><?= $id ?></strong></td>
                                    <td><?= htmlspecialchars($user['npp_user']) ?></td>
                                    <td><?= htmlspecialchars($user['nama_user']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <table class="table table-sm table-bordered mt-2">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Jabatan Panitia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $panitia_sample = array_slice($panitia, 0, 3);
                                foreach ($panitia_sample as $id => $p): 
                                ?>
                                <tr>
                                    <td><strong><?= $id ?></strong></td>
                                    <td><?= htmlspecialchars($p['jbtn_pnt']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Upload Form -->
            <?php if (empty($preview_data)): ?>
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon"><i class="fas fa-upload"></i></div>
                    <h5>Upload File Panitia PA/TA</h5>
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
                                <small>Jika dicentang, data dengan kombinasi Semester + ID User + ID Panitia + Prodi yang sama akan diperbarui!</small>
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
                        
                        <?php if (isset($_SESSION['overwrite_option_tpata']) && $_SESSION['overwrite_option_tpata'] == '1'): ?>
                        <div class="up-confirm-box warning mb-3">
                            <div class="up-confirm-box-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="up-confirm-box-content">
                                <h6>Mode TIMPA AKTIF</h6>
                                <p>Data dengan kombinasi Semester + ID User + ID Panitia + Prodi yang sama akan <strong>ditimpa/diperbarui</strong>! Pastikan ini adalah yang Anda inginkan.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="up-confirm-box mb-3">
                            <div class="up-confirm-box-icon"><i class="fas fa-info-circle"></i></div>
                            <div class="up-confirm-box-content">
                                <h6>Informasi Import</h6>
                                <p>Akan mengimport <strong><?= $preview_data['total_rows'] ?></strong> data panitia PA/TA.</p>
                            </div>
                        </div>
                        
                        <div class="up-confirm-actions">
                            <button type="submit" name="confirm_import" class="up-btn up-btn-success up-btn-lg">
                                <i class="fas fa-database mr-2"></i> Konfirmasi Import Data
                            </button>
                            <a href="upload_tpata.php" class="up-btn up-btn-secondary">
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
                    <p class="text-muted small mb-4">Untuk input data panitia PA/TA satu per satu.</p>
                    
                    <form action="" method="POST" id="manualForm">
                        <div class="up-form-grid">
                            <div class="up-form-group">
                                <label class="up-form-label">Semester <span class="req">*</span></label>
                                <select class="up-select" name="manual_semester" required>
                                    <option value="">Pilih Semester</option>
                                    <?php foreach ($semesterList as $s): ?>
                                        <option value="<?= htmlspecialchars($s['value']) ?>">
                                            <?= htmlspecialchars($s['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Periode Wisuda <span class="req">*</span></label>
                                <select class="up-select" name="manual_periode" required>
                                    <option value="">Pilih Periode</option>
                                    <option value="januari">Januari</option>
                                    <option value="februari">Februari</option>
                                    <option value="maret">Maret</option>
                                    <option value="april">April</option>
                                    <option value="mei">Mei</option>
                                    <option value="juni">Juni</option>
                                    <option value="juli">Juli</option>
                                    <option value="agustus">Agustus</option>
                                    <option value="september">September</option>
                                    <option value="oktober">Oktober</option>
                                    <option value="november">November</option>
                                    <option value="desember">Desember</option>
                                </select>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">User (Dosen) <span class="req">*</span></label>
                                <select class="up-select select2" name="manual_user" required>
                                    <option value="">Pilih User</option>
                                    <?php foreach ($users as $id => $user): ?>
                                        <option value="<?= htmlspecialchars($id) ?>">
                                            <?= htmlspecialchars($id . ' - ' . $user['npp_user'] . ' - ' . $user['nama_user']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="up-form-grid">
                            <div class="up-form-group">
                                <label class="up-form-label">Panitia (Jabatan) <span class="req">*</span></label>
                                <select class="up-select select2" name="manual_panitia" required>
                                    <option value="">Pilih Panitia</option>
                                    <?php foreach ($panitia as $id => $p): ?>
                                        <option value="<?= htmlspecialchars($id) ?>">
                                            <?= htmlspecialchars($id . ' - ' . $p['jbtn_pnt']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Program Studi <span class="req">*</span></label>
                                <input type="text" class="up-input" name="manual_prodi" placeholder="SI, TI, MI, dll" required>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Jml. Mhs Prodi</label>
                                <input type="number" class="up-input" name="manual_jml_mhs_prodi" value="0" min="0">
                            </div>
                        </div>
                        
                        <div class="up-form-grid">
                            <div class="up-form-group">
                                <label class="up-form-label">Jml. Mhs Bimbingan</label>
                                <input type="number" class="up-input" name="manual_jml_mhs_bimbingan" value="0" min="0">
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Jml. Penguji 1</label>
                                <input type="number" class="up-input" name="manual_jml_pgji_1" value="0" min="0">
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Jml. Penguji 2</label>
                                <input type="number" class="up-input" name="manual_jml_pgji_2" value="0" min="0">
                            </div>
                        </div>
                        
                        <div class="up-form-grid">
                            <div class="up-form-group" style="grid-column: span 3;">
                                <label class="up-form-label">Ketua Penguji</label>
                                <input type="text" class="up-input" name="manual_ketua_pgji" placeholder="Nama ketua penguji">
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
                    <h5>Data Panitia PA/TA Terbaru</h5>
                    <div class="ml-auto">
                        <div class="up-search-box" style="width: 220px;">
                            <i class="fas fa-search"></i>
                            <input type="text" class="up-search-input" id="searchTpata" placeholder="Cari data PA/TA..." onkeyup="filterTableTpata()">
                        </div>
                    </div>
                </div>
                <div class="up-card-body">
                    <div class="up-table-wrap">
                        <table class="up-table" id="tableTpata">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Semester</th>
                                    <th>Periode Wisuda</th>
                                    <th>User</th>
                                    <th>Jabatan</th>
                                    <th>Program Studi</th>
                                    <th>Jml. Mhs Prodi</th>
                                    <th>Jml. Mhs Bimbingan</th>
                                    <th>Jml. Penguji 1</th>
                                    <th>Jml. Penguji 2</th>
                                    <th>Ketua Penguji</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_data)): ?>
                                <tr><td colspan="11" class="text-center py-4 text-muted">Belum ada data panitia PA/TA</td></tr>
                                <?php else: ?>
                                <?php 
                                $no = $tpata_offset + 1;
                                foreach ($recent_data as $row): 
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= formatSemesterDisplay($row['semester'] ?? '') ?></td>
                                    <td><?= ucfirst($row['periode_wisuda'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['nama_user'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['jbtn_pnt'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['prodi'] ?? '') ?></td>
                                    <td><?= $row['jml_mhs_prodi'] ?? 0 ?></td>
                                    <td><?= $row['jml_mhs_bimbingan'] ?? 0 ?></td>
                                    <td><?= $row['jml_pgji_1'] ?? 0 ?></td>
                                    <td><?= $row['jml_pgji_2'] ?? 0 ?></td>
                                    <td><?= htmlspecialchars($row['ketua_pgji'] ?? '') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_tpata_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-2" id="recentTpata">
                        <small class="text-muted">Total <?= $total_tpata_cnt ?> data | Halaman <?= $tpata_page ?> dari <?= $total_tpata_pages ?></small>
                        <ul class="up-pagination mb-0">
                            <li class="up-page-item <?= ($tpata_page <= 1) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?tpata_page=<?= $tpata_page-1 ?>#recentTpata"><i class="fas fa-chevron-left"></i></a>
                            </li>
                            <?php for ($p = max(1, $tpata_page-2); $p <= min($total_tpata_pages, $tpata_page+2); $p++): ?>
                            <li class="up-page-item <?= ($p == $tpata_page) ? 'active' : '' ?>">
                                <a class="up-page-link" href="?tpata_page=<?= $p ?>#recentTpata"><?= $p ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="up-page-item <?= ($tpata_page >= $total_tpata_pages) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?tpata_page=<?= $tpata_page+1 ?>#recentTpata"><i class="fas fa-chevron-right"></i></a>
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

function filterTableTpata() {
    var input = document.getElementById("searchTpata");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("tableTpata");
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