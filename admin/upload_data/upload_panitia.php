<?php
/**
 * UPLOAD DATA PANITIA - SiPagu (VERSION SIMPLE - TEMPLATE BASED)
 * Halaman untuk upload data panitia dari Excel template
 * Lokasi: admin/upload_panitia.php
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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Set page title
$page_title = "Upload Data Panitia";

// Process form submission
$error_message = '';
$success_message = '';
$preview_data = [];

// Direktori untuk file sementara
$temp_dir = __DIR__ . '/../temp_uploads/';
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

// Helper functions yang FIX untuk PHP 8+
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

function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

// Clean header dari tanda bintang dan spasi
function clean_header($header) {
    $header = safe_trim($header);
    $header = preg_replace('/\s+/', ' ', $header); // Ganti multiple spaces dengan single space
    return trim($header);
}

// Normalisasi jabatan dengan format Title Case
function normalizeJabatan($jabatan) {
    $trimmed = trim($jabatan);
    
    if (empty($trimmed)) {
        return '';
    }
    
    // Daftar kata penghubung yang tetap lowercase (kecuali di awal)
    $small_words = ['dan', 'atau', 'dari', 'untuk', 'pada', 'dengan', 'di', 'ke', 'dalam', 'panitia'];
    
    $words = explode(' ', strtolower($trimmed));
    $result = [];
    
    foreach ($words as $index => $word) {
        // Capitalize jika kata pertama atau bukan kata penghubung kecil
        if ($index === 0 || !in_array($word, $small_words)) {
            $result[] = ucfirst($word);
        } else {
            $result[] = $word;
        }
    }
    
    return implode(' ', $result);
}

// Konversi nilai honor dari format manusiawi ke integer
function parseHonorToInt($value) {
    if ($value === null || $value === '') {
        return 0;
    }
    
    // Jika sudah angka murni, langsung return
    if (is_numeric($value)) {
        return (int) $value;
    }
    
    // Hapus semua karakter non-numeric
    $cleaned = preg_replace('/[^0-9]/', '', $value);
    
    // Konversi ke integer
    return (int) $cleaned;
}

// Template column structure (SAMA PERSIS dengan template)
$template_columns = [
    'NO',           // Kolom 0
    'JBTN_PNT',     // Kolom 1 - Jabatan Panitia
    'HONOR_STD',    // Kolom 2 - Honor Standar
    'HONOR_P1',     // Kolom 3 - Honor Periode 1
    'HONOR_P2'      // Kolom 4 - Honor Periode 2
];

// Database field mapping (berdasarkan template)
$db_fields = [
    'NO' => 'skip',
    'JBTN_PNT' => 'jbtn_pnt',
    'HONOR_STD' => 'honor_std',
    'HONOR_P1' => 'honor_p1',
    'HONOR_P2' => 'honor_p2'
];

// CHECK IF DOWNLOAD TEMPLATE REQUESTED
if (isset($_GET['action']) && $_GET['action'] == 'download_template') {
    download_excel_template();
    exit();
}

// Process file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // UPLOAD FILE EXCEL/CSV
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
            $unique_name = 'upload_panitia_' . time() . '_' . uniqid() . '.' . $file_ext;
            $temp_file_path = $temp_dir . $unique_name;
            
            // Save file to temp directory
            if (move_uploaded_file($file_tmp, $temp_file_path)) {
                // Validate template structure
                $validation_result = validate_template_structure($temp_file_path, $file_ext, $template_columns);
                
                if ($validation_result['valid']) {
                    // Get preview data
                    $preview_data = get_preview_data($temp_file_path, $file_ext);
                    if ($preview_data) {
                        $preview_data['temp_file'] = $unique_name;
                        $_SESSION['upload_temp_file_panitia'] = $unique_name;
                        $_SESSION['overwrite_option_panitia'] = $_POST['overwrite'] ?? '0';
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
    elseif (isset($_POST['confirm_import']) && isset($_SESSION['upload_temp_file_panitia'])) {
        $temp_file = $_SESSION['upload_temp_file_panitia'];
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
                
                // Cari header row - CARI DARI ATAS KE BAWAH
                $header_row = find_header_row($sheetData);
                
                if ($header_row === -1) {
                    $error_message = "❌ Header tidak ditemukan dalam file! Pastikan menggunakan template yang benar.";
                } else {
                    // Buat mapping kolom berdasarkan template (dengan clean header)
                    $column_mapping = [];
                    foreach ($sheetData[$header_row] as $colIndex => $header) {
                        $header_clean = clean_header($header);
                        $header_upper = strtoupper($header_clean);
                        
                        if (isset($db_fields[$header_upper])) {
                            $column_mapping[$colIndex] = $db_fields[$header_upper];
                        } else {
                            $column_mapping[$colIndex] = 'skip';
                        }
                    }
                    
                    // Validasi mapping - harus ada JBTN_PNT
                    $mapped_fields = array_values($column_mapping);
                    
                    if (!in_array('jbtn_pnt', $mapped_fields)) {
                        $error_message = "❌ Kolom <strong>JBTN_PNT</strong> tidak ditemukan dalam file!";
                    }
                    
                    if (empty($error_message)) {
                        $startRow = $header_row + 1;
                        $jumlahData = 0;
                        $jumlahGagal = 0;
                        $errors = [];
                        $overwrite = isset($_SESSION['overwrite_option_panitia']) && $_SESSION['overwrite_option_panitia'] == '1';
                        
                        // Mulai transaksi database untuk konsistensi
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
                                
                                // Extract data berdasarkan mapping
                                $data = [];
                                foreach ($column_mapping as $colIndex => $dbField) {
                                    if ($dbField != 'skip' && isset($rowData[$colIndex])) {
                                        $data[$dbField] = safe_trim($rowData[$colIndex]);
                                    }
                                }
                                
                                // Validasi data wajib
                                if (empty($data['jbtn_pnt'])) {
                                    $errors[] = "Baris " . ($i+1) . ": Jabatan panitia tidak boleh kosong";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                // Normalisasi jabatan (Title Case)
                                $jabatan_normalized = normalizeJabatan($data['jbtn_pnt']);
                                $jbtn_pnt_escaped = mysqli_real_escape_string($koneksi, $jabatan_normalized);
                                
                                // Parse honor menjadi integer
                                $honor_std = parseHonorToInt($data['honor_std'] ?? 0);
                                $honor_p1 = parseHonorToInt($data['honor_p1'] ?? 0);
                                $honor_p2 = parseHonorToInt($data['honor_p2'] ?? 0);
                                
                                // Cek apakah jabatan sudah ada (case-insensitive)
                                $cek = mysqli_query($koneksi,
                                    "SELECT id_pnt, jbtn_pnt FROM t_panitia WHERE LOWER(TRIM(jbtn_pnt)) = LOWER('$jbtn_pnt_escaped')"
                                );
                                
                                if (mysqli_num_rows($cek) > 0) {
                                    if ($overwrite) {
                                        $row = mysqli_fetch_assoc($cek);
                                        $id_pnt = $row['id_pnt'];
                                        $jbtn_original = $row['jbtn_pnt'];
                                        
                                        $update = mysqli_query($koneksi, "
                                            UPDATE t_panitia SET
                                                honor_std = '$honor_std',
                                                honor_p1 = '$honor_p1',
                                                honor_p2 = '$honor_p2'
                                            WHERE id_pnt = '$id_pnt'
                                        ");
                                        
                                        if ($update) {
                                            $jumlahData++;
                                        } else {
                                            $errors[] = "Baris " . ($i+1) . ": Gagal update data '$jbtn_original' - " . mysqli_error($koneksi);
                                            $jumlahGagal++;
                                        }
                                    } else {
                                        $errors[] = "Baris " . ($i+1) . ": Jabatan <strong>'{$jabatan_normalized}'</strong> sudah ada (gunakan opsi 'Timpa data')";
                                        $jumlahGagal++;
                                    }
                                    continue;
                                }
                                
                                // Insert data baru
                                $insert = mysqli_query($koneksi, "
                                    INSERT INTO t_panitia 
                                    (jbtn_pnt, honor_std, honor_p1, honor_p2)
                                    VALUES
                                    ('$jbtn_pnt_escaped', '$honor_std', '$honor_p1', '$honor_p2')
                                ");
                                
                                if ($insert) {
                                    $jumlahData++;
                                } else {
                                    $errors[] = "Baris " . ($i+1) . ": Gagal menyimpan data '$jabatan_normalized' - " . mysqli_error($koneksi);
                                    $jumlahGagal++;
                                }
                            }
                            
                            // Commit transaksi jika semua sukses
                            mysqli_commit($koneksi);
                            
                        } catch (Exception $e) {
                            // Rollback jika ada error
                            mysqli_rollback($koneksi);
                            throw $e;
                        }
                        
                        // Clean up temp file
                        unlink($temp_file_path);
                        unset($_SESSION['upload_temp_file_panitia']);
                        unset($_SESSION['overwrite_option_panitia']);
                        
                        if ($jumlahData > 0) {
                            $success_message = "✅ Berhasil mengimport <strong>$jumlahData</strong> data panitia.";
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
                
                // Clean up jika masih ada file sementara
                if (isset($temp_file_path) && file_exists($temp_file_path)) {
                    unlink($temp_file_path);
                }
                unset($_SESSION['upload_temp_file_panitia']);
                unset($_SESSION['overwrite_option_panitia']);
            }
        } else {
            $error_message = "❌ File sementara tidak ditemukan.";
            unset($_SESSION['upload_temp_file_panitia']);
            unset($_SESSION['overwrite_option_panitia']);
        }
    }
    
    // MANUAL INPUT
    elseif (isset($_POST['submit_manual'])) {
        $manual_jbtn = $_POST['manual_jbtn'] ?? '';
        $manual_honor_std = $_POST['manual_honor_std'] ?? '0';
        $manual_honor_p1 = $_POST['manual_honor_p1'] ?? '0';
        $manual_honor_p2 = $_POST['manual_honor_p2'] ?? '0';
        
        // Validasi
        if (empty($manual_jbtn)) {
            $error_message = '❌ Jabatan panitia wajib diisi!';
        } 
        // Validasi angka (menggunakan parseHonorToInt untuk konsistensi)
        else {
            // Parse honor
            $honor_std = parseHonorToInt($manual_honor_std);
            $honor_p1 = parseHonorToInt($manual_honor_p1);
            $honor_p2 = parseHonorToInt($manual_honor_p2);
            
            // Normalisasi jabatan (Title Case)
            $jabatan_normalized = normalizeJabatan($manual_jbtn);
            $jbtn_pnt_escaped = mysqli_real_escape_string($koneksi, $jabatan_normalized);
            
            // Check if data already exists (case-insensitive)
            $check = mysqli_query($koneksi,
                "SELECT id_pnt FROM t_panitia WHERE LOWER(TRIM(jbtn_pnt)) = LOWER('$jbtn_pnt_escaped')"
            );
            
            if (mysqli_num_rows($check) > 0) {
                $error_message = "⚠️ Jabatan '<strong>$jabatan_normalized</strong>' sudah ada!";
            } else {
                $insert_manual = mysqli_query($koneksi, "
                    INSERT INTO t_panitia 
                    (jbtn_pnt, honor_std, honor_p1, honor_p2)
                    VALUES
                    ('$jbtn_pnt_escaped', '$honor_std', '$honor_p1', '$honor_p2')
                ");
                
                if ($insert_manual) {
                    $success_message = "✅ Data panitia berhasil disimpan!";
                } else {
                    $error_message = "❌ Gagal menyimpan data: " . mysqli_error($koneksi);
                }
            }
        }
    }
}

// ============================================================================
// FUNGSI TEMPLATE EXCEL
// ============================================================================

function download_excel_template() {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('SiPagu System')
        ->setLastModifiedBy('SiPagu System')
        ->setTitle('Template Import Data Panitia SiPagu')
        ->setDescription('Template untuk mengimport data panitia ke sistem SiPagu');
    
    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(5);
    $sheet->getColumnDimension('B')->setWidth(30);
    $sheet->getColumnDimension('C')->setWidth(20);
    $sheet->getColumnDimension('D')->setWidth(20);
    $sheet->getColumnDimension('E')->setWidth(20);
    
    // Title (baris 1)
    $sheet->mergeCells('A1:E1');
    $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA PANITIA - SIPAGU');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E86C1']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    
    // Instructions (baris 2-5)
    $sheet->mergeCells('A2:E5');
    $instructions = "PETUNJUK PENGISIAN:\n\n" .
                    "1. Isi data mulai baris ke-7\n" .
                    "2. Kolom JBTN_PNT dan HONOR_STD wajib diisi (Jabatan Panitia dan Honor Standar)\n" .
                    "3. Kolom honor: isi angka tanpa pemisah (contoh: 500000)\n" .
                    "4. Format honor: Tanpa titik/koma/Rp\n" .
                    "5. Contoh jabatan: Ketua Panitia, Sekretaris, Anggota\n\n" .
                    "6. JANGAN ubah nama kolom (baris ke-6)!\n" .
                    "7. HAPUS DATA CONTOH SEBELUM DIUPLOAD!";
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
    $sheet->getRowDimension(2)->setRowHeight(100);
    
    // Column headers (baris 6) - NAMA PERSIS seperti yang diharapkan
    $headers = ['NO', 'JBTN_PNT', 'HONOR_STD', 'HONOR_P1', 'HONOR_P2'];
    
    $col = 1;
    foreach ($headers as $header) {
        $cell = Coordinate::stringFromColumnIndex($col) . '6';
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
    $sheet->getRowDimension(6)->setRowHeight(30);
    
    // Contoh data (baris 7-9)
    $sample_data = [
        [1, 'Ketua Panitia', '750000', '150000', '100000'],
    ];
    
    $row = 7;
    foreach ($sample_data as $data) {
        $col = 1;
        foreach ($data as $value) {
            $cell = Coordinate::stringFromColumnIndex($col) . $row;
            $sheet->setCellValue($cell, $value);
            
            // Format number untuk kolom honor
            if ($col >= 3 && $col <= 5) {
                $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
            }
            
            $col++;
        }
        
        // Style untuk data contoh
        $styleRange = 'A' . $row . ':E' . $row;
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
    
    // Format dan border untuk area data (100 baris total)
    $dataRange = 'A6:E105';
    $sheet->getStyle($dataRange)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'D6DBDF']
            ]
        ]
    ]);
    
    // Set alignment
    $sheet->getStyle('A7:A105')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C7:E105')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // Auto filter untuk header
    $sheet->setAutoFilter('A6:E6');
    
    // Freeze pane (header tetap terlihat)
    $sheet->freezePane('A7');
    
    // Set active sheet
    $spreadsheet->setActiveSheetIndex(0);
    
    // Output file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Template_Panitia_SiPagu.xlsx"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ============================================================================
// FUNGSI LAINNYA
// ============================================================================

// Function untuk mencari header row secara fleksibel
function find_header_row($sheetData) {
    // Cari dari baris 0 sampai 10
    for ($i = 0; $i < min(10, count($sheetData)); $i++) {
        $row = array_map('clean_header', $sheetData[$i]);
        $row_upper = array_map('strtoupper', $row);
        
        // Cek jika baris ini mengandung JBTN_PNT
        if (in_array('JBTN_PNT', $row_upper)) {
            return $i;
        }
    }
    
    return -1; // Tidak ditemukan
}

// Function untuk validasi struktur template
function validate_template_structure($file_path, $file_ext, $template_columns) {
    try {
        $reader = IOFactory::createReaderForFile($file_path);
        if ($file_ext == 'csv') {
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
        }
        
        $spreadsheet = $reader->load($file_path);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        
        // Cari baris header dengan fungsi baru
        $header_row = find_header_row($sheetData);
        
        if ($header_row === -1) {
            return ['valid' => false, 'message' => 'Header JBTN_PNT tidak ditemukan. Pastikan file menggunakan template yang benar.'];
        }
        
        // Validasi kolom header dengan clean header
        $file_headers = array_map('strtoupper', array_map('clean_header', $sheetData[$header_row]));
        
        // Minimal harus ada JBTN_PNT
        $has_jbtn = in_array('JBTN_PNT', $file_headers);
        
        if (!$has_jbtn) {
            return ['valid' => false, 'message' => 'Kolom JBTN_PNT tidak ditemukan. Header ditemukan: ' . implode(', ', $file_headers)];
        }
        
        return ['valid' => true, 'header_row' => $header_row, 'message' => 'Template valid'];
        
    } catch (Exception $e) {
        return ['valid' => false, 'message' => 'Error membaca file: ' . $e->getMessage()];
    }
}

// Function untuk mendapatkan preview data
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
        
        // Ambil 5 baris data pertama
        $sample_data = [];
        $total_rows = 0;
        
        for ($i = $header_row + 1; $i < min($header_row + 6, count($sheetData)); $i++) {
            $row = array_map(function($cell) {
                return safe_trim($cell);
            }, $sheetData[$i]);
            
            if (!empty(array_filter($row, function($val) {
                return $val !== '';
            }))) {
                $sample_data[] = $row;
            }
        }
        
        // Hitung total baris data
        for ($i = $header_row + 1; $i < count($sheetData); $i++) {
            $row = array_map(function($cell) {
                return safe_trim($cell);
            }, $sheetData[$i]);
            
            if (!empty(array_filter($row, function($val) {
                return $val !== '';
            }))) {
                $total_rows++;
            }
        }
        
        return [
            'headers' => array_map(function($cell) {
                return safe_trim($cell);
            }, $sheetData[$header_row]),
            'sample_data' => $sample_data,
            'total_rows' => $total_rows,
            'header_row' => $header_row
        ];
        
    } catch (Exception $e) {
        return false;
    }
}

// Clean old temp files
clean_old_temp_files($temp_dir);

function clean_old_temp_files($dir) {
    $files = glob($dir . 'upload_panitia_*');
    foreach ($files as $file) {
        if (filemtime($file) < time() - 3600) {
            unlink($file);
        }
    }
}

// Ambil data panitia terbaru untuk preview
$panitia_page = isset($_GET['panitia_page']) ? max(1, (int)$_GET['panitia_page']) : 1;
$panitia_per_page = 5;
$total_panitia_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_panitia");
$total_panitia_cnt = mysqli_fetch_assoc($total_panitia_q)['total'];
$total_panitia_pages = max(1, ceil($total_panitia_cnt / $panitia_per_page));
if ($panitia_page > $total_panitia_pages) $panitia_page = $total_panitia_pages;
$panitia_offset = ($panitia_page - 1) * $panitia_per_page;

$query = mysqli_query($koneksi, 
    "SELECT jbtn_pnt, honor_std, honor_p1, honor_p2 
     FROM t_panitia 
     ORDER BY id_pnt DESC 
     LIMIT $panitia_offset, $panitia_per_page"
);
$recent_panitia = [];
while ($row = mysqli_fetch_assoc($query)) {
    $recent_panitia[] = $row;
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-users-cog mr-2"></i>Upload Data Panitia</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Upload Panitia</div>
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

            <!-- Template Info & Format Data -->
            <div class="up-step-grid">
                <!-- Download Template Card -->
                <div class="up-step-card">
                    <div class="up-step-num">1</div>
                    <h5><i class="fas fa-download mr-2 text-info"></i>Download Template</h5>
                    <p class="text-muted small">Download template Excel yang sudah disiapkan untuk memudahkan pengisian data panitia.</p>
                    
                    <a href="?action=download_template" class="up-btn up-btn-download btn-block">
                        <i class="fas fa-file-excel mr-2"></i> Download Template Excel
                    </a>
                    
                    <span class="up-note mt-3">Template berisi format dan contoh pengisian</span>
                </div>

                <!-- Format Data Card -->
                <div class="up-step-card up-step-info">
                    <div class="up-step-num">2</div>
                    <h5><i class="fas fa-info-circle mr-2 text-info"></i>Format Data Wajib</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-danger"><i class="fas fa-asterisk mr-1"></i> Data Wajib</h6>
                            <ul class="mb-0 pl-0">
                                <li><strong>JBTN_PNT</strong> (Jabatan Panitia)<br>
                                    <small class="text-muted">Contoh: Ketua Panitia, Sekretaris</small>
                                </li>
                                <li><strong>HONOR_STD</strong> (Honor Standar)<br>
                                    <small class="text-muted">Contoh: 500000 (tanpa pemisah)</small>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success"><i class="far fa-check-circle mr-1"></i> Data Opsional</h6>
                            <ul class="mb-0 pl-0">
                                <li><strong>HONOR_P1</strong> (Honor Periode 1)<br>
                                    <small class="text-muted">Isi jika ada tambahan honor</small>
                                </li>
                                <li><strong>HONOR_P2</strong> (Honor Periode 2)<br>
                                    <small class="text-muted">Isi jika ada tambahan honor</small>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3 mb-0 p-2 small">
                        <i class="fas fa-lightbulb mr-2"></i>
                        <strong>Tip:</strong> Kolom <strong>NO</strong> diabaikan sistem, cukup diisi angka berurutan
                    </div>
                </div>
            </div>

            <!-- Upload Form -->
            <?php if (empty($preview_data)): ?>
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon"><i class="fas fa-upload"></i></div>
                    <h5>Upload File Panitia</h5>
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
                                <small>Jika dicentang, data dengan jabatan yang sama akan diperbarui!</small>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="up-actions">
                            <button type="submit" name="submit" class="up-btn up-btn-primary">
                                <i class="fas fa-upload mr-2"></i> Upload & Validasi
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
                        
                        <?php if (isset($_SESSION['overwrite_option_panitia']) && $_SESSION['overwrite_option_panitia'] == '1'): ?>
                        <div class="up-confirm-box warning mb-3">
                            <div class="up-confirm-box-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="up-confirm-box-content">
                                <h6>Mode TIMPA AKTIF</h6>
                                <p>Data dengan jabatan yang sama akan <strong>ditimpa/diperbarui</strong>! Pastikan ini adalah yang Anda inginkan.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="up-confirm-box mb-3">
                            <div class="up-confirm-box-icon"><i class="fas fa-info-circle"></i></div>
                            <div class="up-confirm-box-content">
                                <h6>Informasi Import</h6>
                                <p>Akan mengimport <strong><?= $preview_data['total_rows'] ?></strong> data panitia. Format honor akan dikonversi otomatis ke angka.</p>
                            </div>
                        </div>
                        
                        <div class="up-confirm-actions">
                            <button type="submit" name="confirm_import" class="up-btn up-btn-success up-btn-lg">
                                <i class="fas fa-database mr-2"></i> Konfirmasi Import Data
                            </button>
                            <a href="upload_panitia.php" class="up-btn up-btn-secondary">
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
                    <p class="text-muted small mb-4">Untuk input data panitia satu per satu</p>
                    
                    <form action="" method="POST" id="manualForm">
                        <div class="up-form-grid">
                            <div class="up-form-group">
                                <label class="up-form-label">Jabatan Panitia <span class="req">*</span></label>
                                <input type="text" class="up-input" name="manual_jbtn" placeholder="Ketua Panitia" required>
                                <span class="up-form-hint">Contoh: Ketua Panitia, Sekretaris, Bendahara</span>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Honor Standar <span class="req">*</span></label>
                                <input type="text" class="up-input" name="manual_honor_std" placeholder="500000" required>
                                <span class="up-form-hint">Format: 500000 (tanpa titik/koma)</span>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Honor Periode 1</label>
                                <input type="text" class="up-input" name="manual_honor_p1" placeholder="750000">
                                <span class="up-form-hint">Opsional, isi jika ada tambahan honor</span>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Honor Periode 2</label>
                                <input type="text" class="up-input" name="manual_honor_p2" placeholder="1000000">
                                <span class="up-form-hint">Opsional, isi jika ada tambahan honor</span>
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
            <div class="up-main-card" id="recentPanitia">
                <div class="up-main-card-header">
                    <div class="up-card-icon"><i class="fas fa-history"></i></div>
                    <h5>Data Panitia Terbaru</h5>
                    <div class="ml-auto">
                        <div class="up-search-box" style="width: 220px;">
                            <i class="fas fa-search"></i>
                            <input type="text" class="up-search-input" id="searchPanitia" placeholder="Cari panitia..." onkeyup="filterTablePanitia()">
                        </div>
                    </div>
                </div>
                <div class="up-card-body">
                    <div class="up-table-wrap">
                        <table class="up-table" id="tablePanitia">
                            <thead>
                                <tr>
                                    <th>Jabatan</th>
                                    <th>Honor Standar</th>
                                    <th>Honor P1</th>
                                    <th>Honor P2</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_panitia)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data panitia</td></tr>
                                <?php else: ?>
                                <?php foreach ($recent_panitia as $row): 
                                    $total = $row['honor_std'] + $row['honor_p1'] + $row['honor_p2'];
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['jbtn_pnt']) ?></strong></td>
                                    <td><?= formatRupiah($row['honor_std']) ?></td>
                                    <td><?= formatRupiah($row['honor_p1']) ?></td>
                                    <td><?= formatRupiah($row['honor_p2']) ?></td>
                                    <td><strong class="text-primary"><?= formatRupiah($total) ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_panitia_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Total <?= $total_panitia_cnt ?> data | Halaman <?= $panitia_page ?> dari <?= $total_panitia_pages ?></small>
                        <ul class="up-pagination mb-0">
                            <li class="up-page-item <?= ($panitia_page <= 1) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?panitia_page=<?= $panitia_page-1 ?>#recentPanitia"><i class="fas fa-chevron-left"></i></a>
                            </li>
                            <?php for ($p = max(1, $panitia_page-2); $p <= min($total_panitia_pages, $panitia_page+2); $p++): ?>
                            <li class="up-page-item <?= ($p == $panitia_page) ? 'active' : '' ?>">
                                <a class="up-page-link" href="?panitia_page=<?= $p ?>#recentPanitia"><?= $p ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="up-page-item <?= ($panitia_page >= $total_panitia_pages) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?panitia_page=<?= $panitia_page+1 ?>#recentPanitia"><i class="fas fa-chevron-right"></i></a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

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

// Validasi manual form
document.getElementById('manualForm')?.addEventListener('submit', function(e) {
    if (e.submitter && e.submitter.name === 'submit_manual') {
        const jabatanInput = document.querySelector('input[name="manual_jbtn"]');
        const honorStdInput = document.querySelector('input[name="manual_honor_std"]');
        
        if (!jabatanInput.value.trim()) {
            e.preventDefault();
            alert('❌ Jabatan Panitia wajib diisi!');
            jabatanInput.focus();
            return false;
        }
        
        if (!honorStdInput.value.trim()) {
            e.preventDefault();
            alert('❌ Honor Standar wajib diisi!');
            honorStdInput.focus();
            return false;
        }
        
        // Validasi angka
        const honorRegex = /^[\d.,]+$/;
        if (!honorRegex.test(honorStdInput.value.replace(/\./g, ''))) {
            e.preventDefault();
            alert('❌ Honor harus berupa angka! Contoh: 500000 atau 500.000');
            honorStdInput.focus();
            return false;
        }
    }
});

function filterTablePanitia() {
    var input = document.getElementById("searchPanitia");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("tablePanitia");
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