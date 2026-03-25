<?php
/**
 * UPLOAD DATA USER - SiPagu
 * Halaman untuk upload data user dari Excel template + download template
 * Lokasi: admin/upload_user.php
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
$page_title = "Upload Data User";

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

// Clean header dari tanda bintang dan spasi
function clean_header($header) {
    $header = safe_trim($header);
    // Hapus tanda bintang dan karakter khusus
    $header = str_replace('*', '', $header);
    $header = str_replace('(', '', $header);
    $header = str_replace(')', '', $header);
    $header = preg_replace('/\s+/', ' ', $header); // Ganti multiple spaces dengan single space
    $header = trim($header);
    return $header;
}

// Template column structure (tanpa tanda bintang)
$template_columns = [
    'NO',           // Kolom 0 - akan diabaikan
    'NPP_USER',     // Kolom 1
    'NIK_USER',     // Kolom 2
    'NPWP_USER',    // Kolom 3
    'NOREK_USER',   // Kolom 4
    'NAMA_USER',    // Kolom 5
    'NOHP_USER',    // Kolom 6
    'ROLE_USER',    // Kolom 7
    'HONOR_PERSKS'  // Kolom 8
];

// Database field mapping (berdasarkan template)
$db_fields = [
    'NO' => 'skip',
    'NPP_USER' => 'npp_user',
    'NIK_USER' => 'nik_user',
    'NPWP_USER' => 'npwp_user',
    'NOREK_USER' => 'norek_user',
    'NAMA_USER' => 'nama_user',
    'NOHP_USER' => 'nohp_user',
    'ROLE_USER' => 'role_user',
    'HONOR_PERSKS' => 'honor_persks'
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
            $unique_name = 'upload_' . time() . '_' . uniqid() . '.' . $file_ext;
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
                        $_SESSION['upload_temp_file'] = $unique_name;
                        $_SESSION['overwrite_option_user'] = $_POST['overwrite'] ?? '0'; // FIX: simpan ke SESSION
                        $success_message = "Template valid! Preview data ditemukan: " . $preview_data['total_rows'] . " baris data.";
                    } else {
                        $error_message = "Gagal membaca data dari file.";
                        unlink($temp_file_path);
                    }
                } else {
                    $error_message = "Format template tidak valid! " . $validation_result['message'];
                    $error_message .= "<br>Download template yang benar: <a href='?action=download_template' class='btn btn-sm btn-success ml-2'><i class='fas fa-download'></i> Download Template</a>";
                    unlink($temp_file_path);
                }
            } else {
                $error_message = "Gagal menyimpan file sementara.";
            }
        }
    }
    
    // CONFIRM DAN IMPORT DATA
    elseif (isset($_POST['confirm_import']) && isset($_SESSION['upload_temp_file'])) {
        $temp_file = $_SESSION['upload_temp_file'];
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
                    $error_message = "Header NPP_USER tidak ditemukan dalam file!";
                } else {
                    // DEBUG: Tampilkan header yang ditemukan
                    $debug_headers = array_map('clean_header', $sheetData[$header_row]);
                    
                    // Buat mapping kolom berdasarkan template (dengan clean header)
                    $column_mapping = [];
                    foreach ($sheetData[$header_row] as $colIndex => $header) {
                        $header_clean = clean_header($header);
                        $header_upper = strtoupper($header_clean);
                        
                        if (isset($db_fields[$header_upper])) {
                            $column_mapping[$colIndex] = $db_fields[$header_upper];
                        } else {
                            // Coba match partial
                            foreach ($db_fields as $template_col => $db_field) {
                                if (strpos($header_upper, $template_col) !== false) {
                                    $column_mapping[$colIndex] = $db_field;
                                    break;
                                }
                            }
                            
                            if (!isset($column_mapping[$colIndex])) {
                                $column_mapping[$colIndex] = 'skip';
                            }
                        }
                    }
                    
                    // Validasi mapping - berikan informasi detail jika error
                    $required_fields = ['npp_user', 'nama_user'];
                    $mapped_fields = array_values($column_mapping);
                    $missing_fields = [];
                    
                    foreach ($required_fields as $required) {
                        if (!in_array($required, $mapped_fields)) {
                            $missing_fields[] = strtoupper($required);
                        }
                    }
                    
                    if (!empty($missing_fields)) {
                        $error_message = "Kolom <strong>" . implode('</strong>, <strong>', $missing_fields) . "</strong> tidak ditemukan dalam file!<br>";
                        $error_message .= "Header yang ditemukan: " . implode(', ', $debug_headers);
                    }
                    
                    if (empty($error_message)) {
                        $startRow = $header_row + 1;
                        $jumlahData = 0;
                        $jumlahGagal = 0;
                        $errors = [];
                        $overwrite = isset($_SESSION['overwrite_option_user']) && $_SESSION['overwrite_option_user'] == '1';
                        
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
                            
                            // Set default untuk field yang tidak ada
                            $default_values = [
                                'nik_user' => '',
                                'npwp_user' => '',
                                'norek_user' => '',
                                'nohp_user' => '',
                                'role_user' => 'staff',
                                'honor_persks' => 0
                            ];
                            
                            $data = array_merge($default_values, $data);
                            
                            // Validasi data wajib
                            if (empty($data['npp_user'])) {
                                $errors[] = "Baris " . ($i+1) . ": NPP tidak boleh kosong";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            if (empty($data['nama_user'])) {
                                $errors[] = "Baris " . ($i+1) . ": Nama tidak boleh kosong";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            // Format data
                            $npp_user = mysqli_real_escape_string($koneksi, $data['npp_user']);
                            $nik_user = mysqli_real_escape_string($koneksi, $data['nik_user']);
                            $npwp_user = mysqli_real_escape_string($koneksi, $data['npwp_user']);
                            $norek_user = mysqli_real_escape_string($koneksi, $data['norek_user']);
                            $nama_user = mysqli_real_escape_string($koneksi, $data['nama_user']);
                            $nohp_user = preg_replace('/[^0-9]/', '', $data['nohp_user']);
                            $role_user = mysqli_real_escape_string($koneksi, !empty($data['role_user']) ? $data['role_user'] : 'staff');
                            
                            // Handle format honor (1,000,000 -> 1000000)
                            $honor_raw = $data['honor_persks'] ?? '0';
                            // Hapus koma pemisah ribuan
                            $honor_clean = str_replace(',', '', $honor_raw);
                            // Juga hapus titik jika ada
                            $honor_clean = str_replace('.', '', $honor_clean);
                            $honor_persks = floatval($honor_clean);
                            
                            // Validasi format NPP
                            if (!preg_match('/^\d{4}\.\d{2}\.\d{4}\.\d{3}$/', $npp_user)) {
                                $errors[] = "Baris " . ($i+1) . ": Format NPP '$npp_user' tidak valid (harus: XXXX.XX.XXXX.XXX)";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            // Cek apakah NPP sudah ada
                            $cek = mysqli_query($koneksi,
                                "SELECT id_user FROM t_user WHERE npp_user = '$npp_user'"
                            );
                            
                            if (mysqli_num_rows($cek) > 0) {
                                if ($overwrite) {
                                    $update = mysqli_query($koneksi, "
                                        UPDATE t_user SET
                                            nik_user = '$nik_user',
                                            npwp_user = '$npwp_user',
                                            norek_user = '$norek_user',
                                            nama_user = '$nama_user',
                                            nohp_user = '$nohp_user',
                                            role_user = '$role_user',
                                            honor_persks = '$honor_persks'
                                        WHERE npp_user = '$npp_user'
                                    ");
                                    
                                    if ($update) {
                                        $jumlahData++;
                                    } else {
                                        $errors[] = "Baris " . ($i+1) . ": Gagal update data '$npp_user' - " . mysqli_error($koneksi);
                                        $jumlahGagal++;
                                    }
                                } else {
                                    $errors[] = "Baris " . ($i+1) . ": NPP '$npp_user' sudah ada (gunakan opsi 'Timpa data')";
                                    $jumlahGagal++;
                                }
                                continue;
                            }
                            
                            // Insert data baru
                            $pw_user = password_hash($npp_user, PASSWORD_DEFAULT);
                            
                            $insert = mysqli_query($koneksi, "
                                INSERT INTO t_user 
                                (npp_user, nik_user, npwp_user, norek_user, nama_user, nohp_user, pw_user, role_user, honor_persks)
                                VALUES
                                ('$npp_user', '$nik_user', '$npwp_user', '$norek_user', '$nama_user', '$nohp_user', '$pw_user', '$role_user', '$honor_persks')
                            ");
                            
                            if ($insert) {
                                $jumlahData++;
                            } else {
                                $errors[] = "Baris " . ($i+1) . ": Gagal menyimpan data '$npp_user' - " . mysqli_error($koneksi);
                                $jumlahGagal++;
                            }
                        }
                        
                        // Clean up temp file
                        unlink($temp_file_path);
                        unset($_SESSION['upload_temp_file']);
                        unset($_SESSION['overwrite_option_user']); // FIX: bersihkan session overwrite
                        
                        if ($jumlahData > 0) {
                            $success_message = "✅ Berhasil mengimport <strong>$jumlahData</strong> data user.";
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
            }
        } else {
            $error_message = "❌ File sementara tidak ditemukan.";
            unset($_SESSION['upload_temp_file']);
        }
    }
    
    // MANUAL INPUT
    elseif (isset($_POST['submit_manual'])) {
        $manual_npp = isset($_POST['manual_npp']) ? mysqli_real_escape_string($koneksi, $_POST['manual_npp']) : '';
        $manual_nik = isset($_POST['manual_nik']) ? mysqli_real_escape_string($koneksi, $_POST['manual_nik']) : '';
        $manual_npwp = isset($_POST['manual_npwp']) ? mysqli_real_escape_string($koneksi, $_POST['manual_npwp']) : '';
        $manual_norek = isset($_POST['manual_norek']) ? mysqli_real_escape_string($koneksi, $_POST['manual_norek']) : '';
        $manual_nama = isset($_POST['manual_nama']) ? mysqli_real_escape_string($koneksi, $_POST['manual_nama']) : '';
        $manual_nohp = isset($_POST['manual_nohp']) ? mysqli_real_escape_string($koneksi, $_POST['manual_nohp']) : '';
        $manual_role = isset($_POST['manual_role']) ? mysqli_real_escape_string($koneksi, $_POST['manual_role']) : 'staff';
        $manual_honor = isset($_POST['manual_honor']) ? floatval($_POST['manual_honor']) : 0;
        
        // Clean phone number
        $manual_nohp = preg_replace('/[^0-9]/', '', $manual_nohp);
        
        // Validasi
        $errors = [];
        
        if (empty($manual_npp) || !preg_match('/^\d{4}\.\d{2}\.\d{4}\.\d{3}$/', $manual_npp)) {
            $errors[] = 'Format NPP tidak valid!';
        }
        
        if (empty($manual_nik) || !preg_match('/^\d{16}$/', $manual_nik)) {
            $errors[] = 'NIK harus 16 digit angka!';
        }
        
        if (empty($manual_nama)) {
            $errors[] = 'Nama tidak boleh kosong!';
        }
        
        if (empty($manual_npwp)) {
            $errors[] = 'NPWP tidak boleh kosong!';
        }
        
        if (empty($errors)) {
            // Check if NPP already exists
            $check = mysqli_query($koneksi, 
                "SELECT id_user FROM t_user WHERE npp_user = '$manual_npp'"
            );
            
            if (mysqli_num_rows($check) > 0) {
                $error_message = "⚠️ NPP sudah terdaftar!";
            } else {
                $manual_pw = password_hash($manual_npp, PASSWORD_DEFAULT);
                
                $insert_manual = mysqli_query($koneksi, "
                    INSERT INTO t_user 
                    (npp_user, nik_user, npwp_user, norek_user, nama_user, nohp_user, pw_user, role_user, honor_persks)
                    VALUES
                    ('$manual_npp', '$manual_nik', '$manual_npwp', '$manual_norek', '$manual_nama', '$manual_nohp', '$manual_pw', '$manual_role', '$manual_honor')
                ");
                
                if ($insert_manual) {
                    $success_message = "✅ Data user berhasil disimpan!";
                } else {
                    $error_message = "❌ Gagal menyimpan data: " . mysqli_error($koneksi);
                }
            }
        } else {
            $error_message = "❌ " . implode('<br>', $errors);
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
        ->setTitle('Template Import Data User SiPagu')
        ->setDescription('Template untuk mengimport data user ke sistem SiPagu');
    
    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(5);
    $sheet->getColumnDimension('B')->setWidth(20);
    $sheet->getColumnDimension('C')->setWidth(20);
    $sheet->getColumnDimension('D')->setWidth(25);
    $sheet->getColumnDimension('E')->setWidth(20);
    $sheet->getColumnDimension('F')->setWidth(35);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(15);
    $sheet->getColumnDimension('I')->setWidth(15);
    
    // Title (baris 1)
    $sheet->mergeCells('A1:I1');
    $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA USER - SIPAGU');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E86C1']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    
    // Instructions (baris 2-4)
    $sheet->mergeCells('A2:I4');
    $instructions = "PETUNJUK PENGISIAN:\n\n" .
                    "1. Isi data mulai baris ke-6\n" .
                    "2. SEMUA KOLOM WAJIB DIISI (kecuali NO opsional)!\n" .
                    "3. Format NPP: XXXX.XX.XXXX.XXX (contoh: 0686.11.1995.071)\n" .
                    "4. NIK harus 16 digit angka\n" .
                    "5. NPWP format: XX.XXX.XXX.X-XXX.XXX\n" .
                    "6. No HP format: 08XXXXXXXXX\n" .
                    "7. Role: pilih dari dropdown (koordinator/staff)\n" .
                    "8. Honor/SKS: angka TANPA pemisah (contoh: 500000 atau 1000000)\n\n" .
                    "9. JANGAN ubah nama kolom (baris ke-5)!\n" .
                    "10. HAPUS DATA CONTOH PADA BARIS KE-6 SEBELUM DIUPLOAD!";
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
    $sheet->getRowDimension(2)->setRowHeight(80);
    $sheet->getRowDimension(3)->setRowHeight(20);
    $sheet->getRowDimension(4)->setRowHeight(20);
    
    // Column headers (baris 5) - TANPA tanda bintang di Excel
    $headers = [
        'NO', 
        'NPP_USER', 
        'NIK_USER', 
        'NPWP_USER', 
        'NOREK_USER', 
        'NAMA_USER', 
        'NOHP_USER', 
        'ROLE_USER', 
        'HONOR_PERSKS'
    ];
    
    $col = 1;
    foreach ($headers as $header) {
        $cell = Coordinate::stringFromColumnIndex($col) . '5';
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
    $sheet->getRowDimension(5)->setRowHeight(30);
    
    // Contoh data (baris 6-8) - TANPA koma/titik di honor
    $sample_data = [
        [1, '0686.11.1995.071', '3374010101950001', '12.345.678.9-012.000', 
         '1234567890', 'Dr. Andi Prasetyo, M.Kom', '081234567890', 'staff', '500000'],
    ];
    
    $row = 6;
    foreach ($sample_data as $data) {
        $col = 1;
        foreach ($data as $value) {
            $cell = Coordinate::stringFromColumnIndex($col) . $row;
            $sheet->setCellValue($cell, $value);
            
            // Format khusus untuk kolom honor - TANPA pemisah
            if ($col == 9) {
                $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('0');
            }
            
            $col++;
        }
        
        // Style untuk data contoh
        $styleRange = 'A' . $row . ':I' . $row;
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
    
    // Data validation untuk kolom ROLE (hanya koordinator/staff)
    $validation = $sheet->getCell('H6')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(true);
    $validation->setShowInputMessage(true);
    $validation->setShowErrorMessage(true);
    $validation->setShowDropDown(true);
    $validation->setErrorTitle('Input error');
    $validation->setError('Pilih salah satu: koordinator atau staff');
    $validation->setPromptTitle('Pilih Role');
    $validation->setPrompt('Pilih role dari dropdown');
    $validation->setFormula1('"koordinator,staff"');
    
    // Copy validation ke 100 baris berikutnya
    for ($i = 6; $i <= 105; $i++) {
        $sheet->getCell('H' . $i)->setDataValidation(clone $validation);
    }
    
    // Format dan border untuk area data
    $dataRange = 'A5:I105';
    $sheet->getStyle($dataRange)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'D6DBDF']
            ]
        ]
    ]);
    
    // Set alignment
    $sheet->getStyle('A6:A105')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('H6:H105')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('I6:I105')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // Auto filter untuk header
    $sheet->setAutoFilter('A5:I5');
    
    // Freeze pane (header tetap terlihat)
    $sheet->freezePane('A6');
    
    // Set active sheet
    $spreadsheet->setActiveSheetIndex(0);
    
    // Output file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Template_User_SiPagu.xlsx"');
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
    // Cari dari baris 0 sampai 20
    for ($i = 0; $i < min(20, count($sheetData)); $i++) {
        $row = array_map('clean_header', $sheetData[$i]);
        $row_upper = array_map('strtoupper', $row);
        
        // Cek jika baris ini mengandung header yang kita cari (dengan clean header)
        if (in_array('NPP USER', $row_upper) || in_array('NPP_USER', $row_upper)) {
            return $i;
        }
        
        // Juga cek variasi lain
        if (in_array('NPP', $row_upper)) {
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
            return ['valid' => false, 'message' => 'Header NPP_USER tidak ditemukan. Pastikan file menggunakan template yang benar.'];
        }
        
        // Validasi kolom header dengan clean header
        $file_headers = array_map('strtoupper', array_map('clean_header', $sheetData[$header_row]));
        
        // Minimal harus ada NPP_USER dan NAMA_USER
        $has_npp = in_array('NPP USER', $file_headers) || in_array('NPP_USER', $file_headers);
        $has_nama = in_array('NAMA USER', $file_headers) || in_array('NAMA_USER', $file_headers);
        
        if (!$has_npp) {
            return ['valid' => false, 'message' => 'Kolom NPP_USER tidak ditemukan. Header ditemukan: ' . implode(', ', $file_headers)];
        }
        
        if (!$has_nama) {
            return ['valid' => false, 'message' => 'Kolom NAMA_USER tidak ditemukan. Header ditemukan: ' . implode(', ', $file_headers)];
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
    $files = glob($dir . 'upload_*');
    foreach ($files as $file) {
        if (filemtime($file) < time() - 3600) {
            unlink($file);
        }
    }
}

// Ambil data user terbaru dengan pagination
$user_page = isset($_GET['user_page']) ? max(1, (int)$_GET['user_page']) : 1;
$user_per_page = 5;
$total_users_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_user");
$total_users_cnt = mysqli_fetch_assoc($total_users_q)['total'];
$total_user_pages = max(1, ceil($total_users_cnt / $user_per_page));
if ($user_page > $total_user_pages) $user_page = $total_user_pages;
$user_offset = ($user_page - 1) * $user_per_page;

$recent_users = [];
$query = mysqli_query($koneksi, 
    "SELECT npp_user, nama_user, nik_user, npwp_user, norek_user, nohp_user, honor_persks, role_user 
     FROM t_user 
     ORDER BY id_user DESC 
     LIMIT $user_offset, $user_per_page"
);
while ($row = mysqli_fetch_assoc($query)) {
    $recent_users[] = $row;
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-users mr-2"></i>Upload Data User</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Upload Data User</div>
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
                    <p class="text-muted small">Download template Excel yang sudah disiapkan untuk memudahkan pengisian data user.</p>
                    
                    <a href="?action=download_template" class="up-btn up-btn-download btn-block">
                        <i class="fas fa-file-excel mr-2"></i> Download Template Excel
                    </a>
                    
                    <span class="up-note mt-3">Template berisi format dan contoh pengisian</span>
                </div>

                <!-- Format Data Card -->
                <div class="up-step-card up-step-info">
                    <div class="up-step-num">2</div>
                    <h5><i class="fas fa-info-circle mr-2 text-info"></i>Format Data Wajib</h5>
                    
                    <ul class="mb-0 pl-0">
                        <li><strong>NPP:</strong> XXXX.XX.XXXX.XXX</li>
                        <li><strong>NIK:</strong> 16 digit angka</li>
                        <li><strong>NPWP:</strong> XX.XXX.XXX.X-XXX.XXX</li>
                        <li><strong>No HP:</strong> 08XXXXXXXXX</li>
                        <li><strong>Role:</strong> koordinator/staff</li>
                        <li><strong>Honor:</strong> angka <span class="text-danger">TANPA</span> pemisah (contoh: 500000)</li>
                    </ul>
                </div>
            </div>

            <!-- Upload Form -->
            <?php if (empty($preview_data)): ?>
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon"><i class="fas fa-upload"></i></div>
                    <h5>Upload File User</h5>
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
                                <small>Jika dicentang, data user dengan NPP yang sama akan diperbarui/ditimpa!</small>
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
                        
                        <div class="up-confirm-box mb-3">
                            <div class="up-confirm-box-icon"><i class="fas fa-info-circle"></i></div>
                            <div class="up-confirm-box-content">
                                <h6>Informasi Mapping</h6>
                                <p>Sistem akan memetakan kolom secara otomatis berdasarkan nama kolom. Pastikan nama kolom sesuai dengan template.</p>
                            </div>
                        </div>
                        
                        <?php if (isset($_SESSION['overwrite_option_user']) && $_SESSION['overwrite_option_user'] == '1'): ?>
                        <div class="up-confirm-box warning mb-3">
                            <div class="up-confirm-box-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="up-confirm-box-content">
                                <h6>Mode TIMPA AKTIF</h6>
                                <p>Data dengan NPP yang sama akan <strong>ditimpa/diperbarui</strong>! Pastikan ini adalah yang Anda inginkan.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="up-confirm-box mb-3">
                            <div class="up-confirm-box-icon"><i class="fas fa-database"></i></div>
                            <div class="up-confirm-box-content">
                                <h6>Konfirmasi Import</h6>
                                <p>Akan mengimport <strong><?= $preview_data['total_rows'] ?></strong> data user.</p>
                            </div>
                        </div>
                        
                        <div class="up-confirm-actions">
                            <button type="submit" name="confirm_import" class="up-btn up-btn-success up-btn-lg">
                                <i class="fas fa-database mr-2"></i> Konfirmasi Import Data
                            </button>
                            <a href="upload_user.php" class="up-btn up-btn-secondary">
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
                    <form action="" method="POST" class="mt-3" id="manualForm">
                        <div class="up-form-grid">
                            <div class="up-form-group">
                                <label class="up-form-label">NPP <span class="req">*</span></label>
                                <input type="text" class="up-input" name="manual_npp" placeholder="0686.11.1995.071" pattern="\d{4}\.\d{2}\.\d{4}\.\d{3}" required>
                                <span class="up-form-hint">Format: XXXX.XX.XXXX.XXX</span>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">NIK <span class="req">*</span></label>
                                <input type="text" class="up-input" name="manual_nik" placeholder="3374010101950001" minlength="16" maxlength="16" required>
                                <span class="up-form-hint">16 digit Nomor Induk Kependudukan</span>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">NPWP <span class="req">*</span></label>
                                <input type="text" class="up-input" name="manual_npwp" placeholder="12.345.678.9-012.000" required>
                            </div>
                        </div>
                        
                        <div class="up-form-grid">
                            <div class="up-form-group">
                                <label class="up-form-label">Nomor Rekening <span class="req">*</span></label>
                                <input type="text" class="up-input" name="manual_norek" placeholder="1410001234567" required>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Nama Lengkap <span class="req">*</span></label>
                                <input type="text" class="up-input" name="manual_nama" placeholder="Dr. Andi Prasetyo, M.Kom" required>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Nomor HP <span class="req">*</span></label>
                                <input type="text" class="up-input" name="manual_nohp" placeholder="081234567890" required>
                            </div>
                        </div>
                        
                        <div class="up-form-grid">
                            <div class="up-form-group">
                                <label class="up-form-label">Role <span class="req">*</span></label>
                                <select class="up-select" name="manual_role" required>
                                    <option value="staff" selected>Staff</option>
                                    <option value="koordinator">Koordinator</option>
                                </select>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Honor/SKS <span class="req">*</span></label>
                                <input type="number" class="up-input" name="manual_honor" value="0" min="0" step="1000" required>
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
            <div class="up-main-card" id="recentUser">
                <div class="up-main-card-header">
                    <div class="up-card-icon"><i class="fas fa-history"></i></div>
                    <h5>Data User Terbaru</h5>
                    <div class="ml-auto">
                        <div class="up-search-box" style="width: 220px;">
                            <i class="fas fa-search"></i>
                            <input type="text" class="up-search-input" id="searchUser" placeholder="Cari user..." onkeyup="filterTableUser()">
                        </div>
                    </div>
                </div>
                <div class="up-card-body">
                    <div class="up-table-wrap">
                        <table class="up-table" id="tableUser">
                            <thead>
                                <tr>
                                    <th>NPP</th>
                                    <th>Nama</th>
                                    <th>NIK</th>
                                    <th>NPWP</th>
                                    <th>No Rekening</th>
                                    <th>No HP</th>
                                    <th>Honor/SKS</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_users)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data user</td></tr>
                                <?php else: ?>
                                <?php foreach ($recent_users as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['npp_user']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_user']) ?></td>
                                    <td><?= htmlspecialchars($row['nik_user']) ?></td>
                                    <td><?= htmlspecialchars($row['npwp_user']) ?></td>
                                    <td><?= htmlspecialchars($row['norek_user']) ?></td>
                                    <td><?= htmlspecialchars($row['nohp_user']) ?></td>
                                    <td>Rp <?= number_format($row['honor_persks'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="up-badge <?= 
                                            $row['role_user'] === 'admin' ? 'up-badge-admin' :
                                            ($row['role_user'] === 'koordinator' ? 'up-badge-koordinator' : 'up-badge-staff')
                                        ?>">
                                            <?= ucfirst($row['role_user']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_user_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Total <?= $total_users_cnt ?> data | Halaman <?= $user_page ?> dari <?= $total_user_pages ?></small>
                        <ul class="up-pagination mb-0">
                            <li class="up-page-item <?= ($user_page <= 1) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?user_page=<?= $user_page-1 ?>#recentUser"><i class="fas fa-chevron-left"></i></a>
                            </li>
                            <?php for ($p = max(1, $user_page-2); $p <= min($total_user_pages, $user_page+2); $p++): ?>
                            <li class="up-page-item <?= ($p == $user_page) ? 'active' : '' ?>">
                                <a class="up-page-link" href="?user_page=<?= $p ?>#recentUser"><?= $p ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="up-page-item <?= ($user_page >= $total_user_pages) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?user_page=<?= $user_page+1 ?>#recentUser"><i class="fas fa-chevron-right"></i></a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.up-badge-admin {
    background: #fef2f2;
    color: var(--danger);
    border: 1px solid #fecaca;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.up-badge-koordinator {
    background: #fffbeb;
    color: #d97706;
    border: 1px solid #fde68a;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.up-badge-staff {
    background: #f0f9ff;
    color: var(--info);
    border: 1px solid #bae6fd;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}
</style>

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
        const nppInput = document.querySelector('input[name="manual_npp"]');
        const nikInput = document.querySelector('input[name="manual_nik"]');
        const namaInput = document.querySelector('input[name="manual_nama"]');
        
        if (!nppInput.value.trim()) {
            e.preventDefault();
            alert('❌ NPP wajib diisi!');
            nppInput.focus();
            return false;
        }
        
        if (!nikInput.value.trim()) {
            e.preventDefault();
            alert('❌ NIK wajib diisi!');
            nikInput.focus();
            return false;
        }
        
        if (!namaInput.value.trim()) {
            e.preventDefault();
            alert('❌ Nama wajib diisi!');
            namaInput.focus();
            return false;
        }
    }
});

function filterTableUser() {
    var input = document.getElementById("searchUser");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("tableUser");
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