<?php
/**
 * UPLOAD JADWAL - SiPagu (VERSION SIMPLE - TEMPLATE BASED)
 * Halaman untuk upload jadwal dari Excel template
 * Fitur: Dropdown Dosen + Deteksi Duplikasi + TIDAK BOLEH DUPLIKAT
 * Lokasi: admin/upload_jadwal.php
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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

// Set page title
$page_title = "Upload Jadwal";

// Process form submission
$error_message = '';
$success_message = '';
$preview_data = [];

// Direktori untuk file sementara
$temp_dir = __DIR__ . '/../temp_uploads/';
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

// Helper functions
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

function formatSemester($semester) {
    if (!preg_match('/^\d{4}[12]$/', $semester)) {
        return $semester;
    }

    $tahun = substr($semester, 0, 4);
    $kode  = substr($semester, -1);

    return $tahun . ' ' . ($kode == '1' ? 'Ganjil' : 'Genap');
}

function generateSemester($startYear = 2020, $range = 6) {
    $list = [];
    $currentYear = date('Y');

    for ($y = $startYear; $y <= $currentYear + $range; $y++) {
        $list[] = $y . '1';
        $list[] = $y . '2';
    }
    return $list;
}

$semesterList = generateSemester(2022, 4);

// Clean header
function clean_header($header) {
    $header = safe_trim($header);
    $header = preg_replace('/\s+/', ' ', $header);
    return trim($header);
}

// Template column structure
$template_columns = [
    'NO',               // Kolom 0
    'SEMESTER',         // Kolom 1 - Format: 20241, 20242, dll
    'KODE_MATKUL',      // Kolom 2
    'NAMA_MATKUL',      // Kolom 3
    'NAMA_DOSEN',       // Kolom 4 - Nama Dosen (dropdown)
    'JML_MHS'           // Kolom 5 - Jumlah Mahasiswa
];

// Database field mapping
$db_fields = [
    'NO' => 'skip',
    'SEMESTER' => 'semester',
    'KODE_MATKUL' => 'kode_matkul',
    'NAMA_MATKUL' => 'nama_matkul',
    'NAMA_DOSEN' => 'nama_dosen',
    'JML_MHS' => 'jml_mhs'
];

// AMBIL DAFTAR DOSEN UNTUK DROPDOWN
$dosen_list = [];
$query_dosen = mysqli_query($koneksi, "SELECT id_user, npp_user, nama_user FROM t_user ORDER BY nama_user");
while ($row = mysqli_fetch_assoc($query_dosen)) {
    $dosen_list[] = $row['nama_user'];
}

// CHECK IF DOWNLOAD TEMPLATE REQUESTED
if (isset($_GET['action']) && $_GET['action'] == 'download_template') {
    download_excel_template($dosen_list);
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
        
        $allowed_ext = ['xls', 'xlsx', 'csv'];
        if (!in_array($file_ext, $allowed_ext)) {
            $error_message = 'File harus bertipe XLS, XLSX, atau CSV.';
        } elseif ($file_size > 10 * 1024 * 1024) {
            $error_message = 'File terlalu besar. Maksimal 10MB.';
        } else {
            $unique_name = 'upload_jadwal_' . time() . '_' . uniqid() . '.' . $file_ext;
            $temp_file_path = $temp_dir . $unique_name;
            
            if (move_uploaded_file($file_tmp, $temp_file_path)) {
                $validation_result = validate_template_structure($temp_file_path, $file_ext, $template_columns);
                
                if ($validation_result['valid']) {
                    $preview_data = get_preview_data($temp_file_path, $file_ext);
                    if ($preview_data) {
                        $preview_data['temp_file'] = $unique_name;
                        $_SESSION['upload_temp_file_jadwal'] = $unique_name;
                        $_SESSION['overwrite_option_jadwal'] = $_POST['overwrite'] ?? '0';
                        $success_message = "✅ Template valid! Preview data ditemukan: " . $preview_data['total_rows'] . " baris data.";
                    } else {
                        $error_message = "❌ Gagal membaca data dari file.";
                        unlink($temp_file_path);
                    }
                } else {
                    $error_message = "❌ Format template tidak valid! " . $validation_result['message'];
                    $error_message .= "<br><a href='?action=download_template' class='btn btn-sm btn-success ml-2'><i class='fas fa-download'></i> Download Template</a>";
                    unlink($temp_file_path);
                }
            } else {
                $error_message = "❌ Gagal menyimpan file sementara.";
            }
        }
    }
    
    // CONFIRM DAN IMPORT DATA
    elseif (isset($_POST['confirm_import']) && isset($_SESSION['upload_temp_file_jadwal'])) {
        $temp_file = $_SESSION['upload_temp_file_jadwal'];
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
                    
                    $mapped_fields = array_values($column_mapping);
                    
                    if (!in_array('semester', $mapped_fields)) {
                        $error_message = "❌ Kolom SEMESTER tidak ditemukan!";
                    } elseif (!in_array('kode_matkul', $mapped_fields)) {
                        $error_message = "❌ Kolom KODE_MATKUL tidak ditemukan!";
                    } elseif (!in_array('nama_matkul', $mapped_fields)) {
                        $error_message = "❌ Kolom NAMA_MATKUL tidak ditemukan!";
                    } elseif (!in_array('nama_dosen', $mapped_fields)) {
                        $error_message = "❌ Kolom NAMA_DOSEN tidak ditemukan!";
                    }
                    
                    if (empty($error_message)) {
                        $startRow = $header_row + 1;
                        $jumlahData = 0;
                        $jumlahGagal = 0;
                        $jumlahDuplikat = 0;
                        $jumlahUpdate = 0;
                        $errors = [];
                        $warnings = [];
                        $duplicates = [];
                        $overwrite = isset($_SESSION['overwrite_option_jadwal']) && $_SESSION['overwrite_option_jadwal'] == '1';
                        
                        mysqli_begin_transaction($koneksi);
                        
                        try {
                            for ($i = $startRow; $i < count($sheetData); $i++) {
                                $rowData = $sheetData[$i];
                                
                                if (empty(array_filter($rowData, function($val) {
                                    return $val !== null && $val !== '' && trim((string)$val) !== '';
                                }))) {
                                    continue;
                                }
                                
                                $data = [];
                                foreach ($column_mapping as $colIndex => $dbField) {
                                    if ($dbField != 'skip' && isset($rowData[$colIndex])) {
                                        $data[$dbField] = safe_trim($rowData[$colIndex]);
                                    }
                                }
                                
                                // Validasi wajib
                                if (empty($data['semester'])) {
                                    $errors[] = "Baris " . ($i+1) . ": Semester tidak boleh kosong";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                if (empty($data['kode_matkul'])) {
                                    $errors[] = "Baris " . ($i+1) . ": Kode mata kuliah tidak boleh kosong";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                if (empty($data['nama_matkul'])) {
                                    $errors[] = "Baris " . ($i+1) . ": Nama mata kuliah tidak boleh kosong";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                if (empty($data['nama_dosen'])) {
                                    $errors[] = "Baris " . ($i+1) . ": Nama dosen tidak boleh kosong";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                if (!preg_match('/^\d{4}[12]$/', $data['semester'])) {
                                    $errors[] = "Baris " . ($i+1) . ": Format semester tidak valid: '" . $data['semester'] . "'";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                $semester = mysqli_real_escape_string($koneksi, $data['semester']);
                                $kode_matkul = strtoupper(mysqli_real_escape_string($koneksi, $data['kode_matkul']));
                                $nama_matkul = mysqli_real_escape_string($koneksi, $data['nama_matkul']);
                                $nama_dosen_input = $data['nama_dosen'];
                                
                                // ============ CEK DUPLIKASI ============
                                
                                // 1. CEK KOMBINASI LENGKAP (Semester + Kode MK + Dosen)
                                $cek_kombinasi = mysqli_query($koneksi,
                                    "SELECT id_jdwl, kode_matkul, semester, id_user 
                                     FROM t_jadwal 
                                     WHERE semester = '$semester' 
                                     AND kode_matkul = '$kode_matkul' 
                                     AND id_user IN (SELECT id_user FROM t_user WHERE TRIM(nama_user) = TRIM('" . mysqli_real_escape_string($koneksi, $nama_dosen_input) . "'))"
                                );
                                
                                if (mysqli_num_rows($cek_kombinasi) > 0) {
                                    // DATA SUDAH ADA PERSIS
                                    if ($overwrite) {
                                        // UPDATE data yang sama persis
                                        // FIX: $jml_mhs harus diambil dari $data sebelum UPDATE
                                        $jml_mhs_update = isset($data['jml_mhs']) && $data['jml_mhs'] !== '' ? intval($data['jml_mhs']) : 0;
                                        if ($jml_mhs_update < 0) $jml_mhs_update = 0;
                                        $row_kombinasi = mysqli_fetch_assoc($cek_kombinasi);
                                        $id_jdwl = $row_kombinasi['id_jdwl'];
                                        
                                        $update = mysqli_query($koneksi, "
                                            UPDATE t_jadwal SET
                                                nama_matkul = '$nama_matkul',
                                                jml_mhs = '$jml_mhs_update'
                                            WHERE id_jdwl = '$id_jdwl'
                                        ");
                                        
                                        if ($update) {
                                            $jumlahUpdate++;
                                            $duplicates[] = [
                                                'baris' => $i+1,
                                                'semester' => formatSemester($semester),
                                                'kode' => $kode_matkul,
                                                'dosen' => $nama_dosen_input,
                                                'status' => 'UPDATE'
                                            ];
                                        } else {
                                            $errors[] = "Baris " . ($i+1) . ": Gagal update data '$kode_matkul'";
                                            $jumlahGagal++;
                                        }
                                    } else {
                                        // SKIP - TIDAK DIINSERT TIDAK DIUPDATE
                                        $jumlahDuplikat++;
                                        $duplicates[] = [
                                            'baris' => $i+1,
                                            'semester' => formatSemester($semester),
                                            'kode' => $kode_matkul,
                                            'dosen' => $nama_dosen_input,
                                            'status' => 'SKIP (sudah ada)'
                                        ];
                                    }
                                    continue; // SKIP PROSES SELANJUTNYA
                                }
                                
                                // 2. CEK DUPLIKASI MATA KULIAH (Semester + Kode MK)
                                $cek_matkul = mysqli_query($koneksi,
                                    "SELECT id_jdwl, kode_matkul, semester, nama_matkul as nama_matkul_existing
                                     FROM t_jadwal j
                                     WHERE semester = '$semester' 
                                     AND kode_matkul = '$kode_matkul'"
                                );
                                
                                if (mysqli_num_rows($cek_matkul) > 0) {
                                    $row_matkul = mysqli_fetch_assoc($cek_matkul);
                                    // TOLAK - TIDAK BOLEH DUPLIKAT KODE MK DALAM SEMESTER YANG SAMA
                                    $errors[] = "❌ Baris " . ($i+1) . ": Kode MK <strong>'$kode_matkul'</strong> sudah ada di semester " . formatSemester($semester) . 
                                                " dengan mata kuliah <strong>'{$row_matkul['nama_matkul_existing']}'</strong>. " .
                                                "Tidak boleh duplikat kode MK dalam semester yang sama!";
                                    $jumlahGagal++;
                                    continue;
                                }
                                
                                // Cari dosen
                                $check_user = mysqli_query($koneksi, 
                                    "SELECT id_user, npp_user, nama_user FROM t_user 
                                     WHERE TRIM(nama_user) = TRIM('" . mysqli_real_escape_string($koneksi, $nama_dosen_input) . "')"
                                );
                                
                                if (mysqli_num_rows($check_user) == 0) {
                                    // Coba cari tanpa gelar
                                    $nama_tanpa_gelar = explode(',', $nama_dosen_input)[0];
                                    $check_user = mysqli_query($koneksi, 
                                        "SELECT id_user, npp_user, nama_user FROM t_user 
                                         WHERE nama_user LIKE '%" . mysqli_real_escape_string($koneksi, trim($nama_tanpa_gelar)) . "%'
                                         LIMIT 1"
                                    );
                                    
                                    if (mysqli_num_rows($check_user) == 0) {
                                        $errors[] = "❌ Baris " . ($i+1) . ": Nama dosen '$nama_dosen_input' tidak ditemukan dalam sistem";
                                        $jumlahGagal++;
                                        continue;
                                    } else {
                                        $user_row = mysqli_fetch_assoc($check_user);
                                        $id_user = $user_row['id_user'];
                                        $nama_dosen_terdaftar = $user_row['nama_user'];
                                        // FIX: gunakan $warnings[] agar tidak tercampur dengan error fatal
                                        $warnings[] = "⚠️ Baris " . ($i+1) . ": Nama dosen '$nama_dosen_input' dicocokkan ke '$nama_dosen_terdaftar' (diimport otomatis)";
                                    }
                                } else {
                                    $user_row = mysqli_fetch_assoc($check_user);
                                    $id_user = $user_row['id_user'];
                                    $nama_dosen_terdaftar = $user_row['nama_user'];
                                }
                                
                                $jml_mhs = isset($data['jml_mhs']) && $data['jml_mhs'] !== '' ? intval($data['jml_mhs']) : 0;
                                if ($jml_mhs < 0) $jml_mhs = 0;
                                
                                // Insert data baru (SEMUA CEK DUPLIKASI SUDAH LULUS)
                                $insert = mysqli_query($koneksi, "
                                    INSERT INTO t_jadwal 
                                    (semester, kode_matkul, nama_matkul, id_user, jml_mhs)
                                    VALUES
                                    ('$semester', '$kode_matkul', '$nama_matkul', '$id_user', '$jml_mhs')
                                ");
                                
                                if ($insert) {
                                    $jumlahData++;
                                } else {
                                    $errors[] = "❌ Baris " . ($i+1) . ": Gagal menyimpan data '$kode_matkul' - " . mysqli_error($koneksi);
                                    $jumlahGagal++;
                                }
                            }
                            
                            mysqli_commit($koneksi);
                            
                        } catch (Exception $e) {
                            mysqli_rollback($koneksi);
                            throw $e;
                        }
                        
                        unlink($temp_file_path);
                        unset($_SESSION['upload_temp_file_jadwal']);
                        unset($_SESSION['overwrite_option_jadwal']);
                        
                        // BUAT PESAN HASIL IMPORT
                        if ($jumlahData > 0 || $jumlahUpdate > 0) {
                            $success_message = "✅ <strong>Import Berhasil:</strong> ";
                            if ($jumlahData > 0) $success_message .= "$jumlahData data baru, ";
                            if ($jumlahUpdate > 0) $success_message .= "$jumlahUpdate data diupdate, ";
                            $success_message = rtrim($success_message, ', ');
                            
                            if ($jumlahDuplikat > 0) {
                                $success_message .= " | <span class='text-warning'>⏭️ $jumlahDuplikat data dilewati (sudah ada)</span>";
                            }
                            if ($jumlahGagal > 0) {
                                $success_message .= " | <span class='text-danger'>❌ $jumlahGagal gagal</span>";
                            }
                        } else {
                            if ($jumlahDuplikat > 0 && $jumlahGagal == 0) {
                                $error_message = "⚠️ <strong>Tidak ada data baru.</strong> Semua data ($jumlahDuplikat baris) sudah ada dalam database.";
                            } else {
                                $error_message = "❌ Tidak ada data yang berhasil diimport.";
                            }
                        }
                        
                        // TAMPILKAN DETAIL DUPLIKAT (YANG DI-SKIP)
                        if (!empty($duplicates)) {
                            $dup_html = "<div class='mt-3'><strong>📋 Detail duplikat:</strong>";
                            $dup_html .= "<table class='table table-sm table-bordered mt-2' style='font-size:0.85em;'>";
                            $dup_html .= "<thead><tr><th>Baris</th><th>Semester</th><th>Kode MK</th><th>Dosen</th><th>Status</th></tr></thead><tbody>";
                            
                            $dup_count = 0;
                            foreach ($duplicates as $dup) {
                                if ($dup_count >= 20) {
                                    $dup_html .= "<tr><td colspan='5' class='text-center'>... dan " . (count($duplicates) - 20) . " duplikat lainnya</td></tr>";
                                    break;
                                }
                                $status_class = ($dup['status'] == 'UPDATE') ? 'badge badge-warning' : 'badge badge-secondary';
                                $dup_html .= "<tr>";
                                $dup_html .= "<td>{$dup['baris']}</td>";
                                $dup_html .= "<td>{$dup['semester']}</td>";
                                $dup_html .= "<td><strong>{$dup['kode']}</strong></td>";
                                $dup_html .= "<td>{$dup['dosen']}</td>";
                                $dup_html .= "<td><span class='{$status_class}'>{$dup['status']}</span></td>";
                                $dup_html .= "</tr>";
                                $dup_count++;
                            }
                            $dup_html .= "</tbody></table></div>";
                            
                            if (!empty($error_message)) {
                                $error_message .= $dup_html;
                            } else {
                                $error_message = "⚠️ <strong>Perhatian:</strong> Ditemukan data duplikat." . $dup_html;
                            }
                        }
                        
                        // TAMPILKAN ERROR LAINNYA
                        if (!empty($errors)) {
                            $error_display = "<div class='mt-3'><strong>❌ Error detail:</strong><ul class='mb-0 pl-3' style='max-height:300px; overflow-y:auto;'>";
                            $err_count = 0;
                            foreach ($errors as $err) {
                                if ($err_count >= 20) {
                                    $error_display .= "<li>... dan " . (count($errors) - 20) . " error lainnya</li>";
                                    break;
                                }
                                $error_display .= "<li>" . $err . "</li>";
                                $err_count++;
                            }
                            $error_display .= "</ul></div>";
                            
                            if (!empty($error_message)) {
                                $error_message .= $error_display;
                            } else {
                                $error_message = $error_display;
                            }
                        }
                        
                        // TAMPILKAN WARNINGS (cocok nama dosen, tapi berhasil diimport)
                        if (!empty($warnings)) {
                            $warn_display = "<div class='mt-2'><strong>⚠️ Catatan otomatis:</strong><ul class='mb-0 pl-3 text-warning'>";
                            foreach (array_slice($warnings, 0, 10) as $w) {
                                $warn_display .= "<li>" . $w . "</li>";
                            }
                            if (count($warnings) > 10) $warn_display .= "<li>... dan " . (count($warnings)-10) . " catatan lainnya</li>";
                            $warn_display .= "</ul></div>";
                            $success_message .= $warn_display;
                        }
                    }
                }
                
            } catch (Exception $e) {
                $error_message = "❌ Terjadi kesalahan: " . $e->getMessage();
                if (isset($temp_file_path) && file_exists($temp_file_path)) {
                    unlink($temp_file_path);
                }
                unset($_SESSION['upload_temp_file_jadwal']);
                unset($_SESSION['overwrite_option_jadwal']);
            }
        } else {
            $error_message = "❌ File sementara tidak ditemukan.";
            unset($_SESSION['upload_temp_file_jadwal']);
            unset($_SESSION['overwrite_option_jadwal']);
        }
    }
    
    // MANUAL INPUT
    elseif (isset($_POST['submit_manual'])) {
        $manual_semester = $_POST['manual_semester'] ?? '';
        $manual_kode_matkul = $_POST['manual_kode_matkul'] ?? '';
        $manual_nama_matkul = $_POST['manual_nama_matkul'] ?? '';
        $manual_user = $_POST['manual_user'] ?? '';
        $manual_jml_mhs = $_POST['manual_jml_mhs'] ?? '0';
        
        if (empty($manual_semester) || empty($manual_kode_matkul) || 
            empty($manual_nama_matkul) || empty($manual_user)) {
            $error_message = '❌ Semua field wajib diisi!';
        } elseif (!preg_match('/^\d{4}[12]$/', $manual_semester)) {
            $error_message = '❌ Format semester tidak valid! Contoh: 20241';
        } else {
            // CEK DUPLIKASI UNTUK INPUT MANUAL
            $cek_duplikat = mysqli_query($koneksi, 
                "SELECT j.id_jdwl, j.kode_matkul, j.semester, u.nama_user 
                 FROM t_jadwal j
                 LEFT JOIN t_user u ON j.id_user = u.id_user
                 WHERE j.semester = '$manual_semester' 
                 AND j.kode_matkul = '$manual_kode_matkul'"
            );
            
            if (mysqli_num_rows($cek_duplikat) > 0) {
                $row_dup = mysqli_fetch_assoc($cek_duplikat);
                $error_message = "❌ <strong>DUPLIKAT:</strong> Kode MK <strong>'$manual_kode_matkul'</strong> sudah ada di semester <strong>" . 
                                 formatSemester($manual_semester) . "</strong> dengan dosen <strong>'{$row_dup['nama_user']}'</strong>.<br>" .
                                 "Tidak boleh duplikat kode MK dalam semester yang sama!";
            } else {
                $manual_kode_matkul = strtoupper(mysqli_real_escape_string($koneksi, $manual_kode_matkul));
                $manual_nama_matkul = mysqli_real_escape_string($koneksi, $manual_nama_matkul);
                $manual_jml_mhs = intval($manual_jml_mhs);
                
                $insert_manual = mysqli_query($koneksi, "
                    INSERT INTO t_jadwal 
                    (semester, kode_matkul, nama_matkul, id_user, jml_mhs)
                    VALUES
                    ('$manual_semester', '$manual_kode_matkul', '$manual_nama_matkul', 
                     '$manual_user', '$manual_jml_mhs')
                ");
                
                if ($insert_manual) {
                    $success_message = "✅ Data jadwal berhasil disimpan!";
                } else {
                    $error_message = "❌ Gagal menyimpan data: " . mysqli_error($koneksi);
                }
            }
        }
    }
}

// ============================================================================
// FUNGSI TEMPLATE EXCEL DENGAN DROPDOWN
// ============================================================================

function download_excel_template($dosen_list) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $spreadsheet->getProperties()
        ->setCreator('SiPagu System')
        ->setLastModifiedBy('SiPagu System')
        ->setTitle('Template Import Data Jadwal SiPagu')
        ->setDescription('Template dengan dropdown dosen');
    
    $sheet->getColumnDimension('A')->setWidth(5);
    $sheet->getColumnDimension('B')->setWidth(18);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(40);
    $sheet->getColumnDimension('E')->setWidth(35);
    $sheet->getColumnDimension('F')->setWidth(15);
    
    // Title
    $sheet->mergeCells('A1:F1');
    $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA JADWAL - SIPAGU');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E86C1']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);
    
    // Instructions
    $sheet->mergeCells('A2:F5');
    
    $instructions = "PETUNJUK PENGISIAN:\n\n" .
                    "1. Isi data mulai baris ke-7\n" .
                    "2. SEMUA KOLOM WAJIB DIISI (kecuali NO opsional)!\n" .
                    "3. Format Semester: TAHUN + KODE (1=Ganjil, 2=Genap)\n" .
                    "   Contoh: 20241 = Semester Ganjil 2024\n" .
                    "4. Kolom NAMA_DOSEN: Gunakan DROPDOWN (klik panah)\n" .
                    "5. Kode Mata Kuliah: Huruf KAPITAL, BERSIFAT UNIK per semester!\n" .
                    "   TIDAK BOLEH ada kode MK yang sama dalam 1 semester\n" .
                    "6. Jumlah Mahasiswa: Isi angka tanpa titik/koma\n\n" .
                    "7. HAPUS DATA CONTOH SEBELUM DIUPLOAD!\n" .
                    "⚠️  PERINGATAN: Data duplikat (kode MK sama dalam 1 semester) akan DITOLAK sistem!";
    
    $sheet->setCellValue('A2', $instructions);
    $sheet->getStyle('A2')->getAlignment()->setWrapText(true);
    $sheet->getStyle('A2')->applyFromArray([
        'font' => ['size' => 10, 'bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCF3CF']]
    ]);
    $sheet->getRowDimension(2)->setRowHeight(200);
    
    // Column headers
    $headers = ['NO', 'SEMESTER', 'KODE_MATKUL', 'NAMA_MATKUL', 'NAMA_DOSEN', 'JML_MHS'];
    
    $col = 1;
    foreach ($headers as $header) {
        $cell = Coordinate::stringFromColumnIndex($col) . '6';
        $sheet->setCellValue($cell, $header);
        $sheet->getStyle($cell)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2874A6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $col++;
    }
    
    // Contoh data
    $sample_data = [
        [1, '20241', 'SI101', 'Algoritma dan Pemrograman', '', 40],
    ];
    
    $row = 7;
    foreach ($sample_data as $data) {
        $col = 1;
        foreach ($data as $value) {
            $cell = Coordinate::stringFromColumnIndex($col) . $row;
            $sheet->setCellValue($cell, $value);
            if ($col == 6) {
                $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
            }
            $col++;
        }
        $row++;
    }
    
    // DROPDOWN UNTUK DOSEN
    $sheetDosen = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'DaftarDosen');
    $spreadsheet->addSheet($sheetDosen);
    
    $sheetDosen->setCellValue('A1', 'DAFTAR DOSEN SIPAGU');
    $sheetDosen->getStyle('A1')->getFont()->setBold(true);
    
    $rowDosen = 2;
    foreach ($dosen_list as $index => $dosen) {
        $sheetDosen->setCellValue('A' . $rowDosen, $dosen);
        $rowDosen++;
    }
    
    $sheetDosen->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
    
    $spreadsheet->addNamedRange(
        new \PhpOffice\PhpSpreadsheet\NamedRange(
            'DaftarDosen',
            $sheetDosen,
            '$A$2:$A$' . ($rowDosen - 1)
        )
    );
    
    for ($i = 7; $i <= 105; $i++) {
        $cell = 'E' . $i;
        $validation = $sheet->getCell($cell)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP); // STOP, tidak allow blank
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Dosen tidak valid');
        $validation->setError('Pilih nama dosen dari dropdown!');
        $validation->setPromptTitle('Pilih Dosen');
        $validation->setPrompt('Klik panah untuk memilih');
        $validation->setFormula1('DaftarDosen');
    }
    
    // Style untuk kolom NAMA_DOSEN (wajib diisi)
    $sheet->getStyle('E7:E105')->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF9E6']]
    ]);
    
    // Peringatan untuk kolom KODE_MATKUL
    $sheet->getStyle('C7:C105')->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FADBD8']]
    ]);
    
    // Style data contoh
    $sheet->getStyle('A7:C9')->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F8F5']],
        'font' => ['italic' => true, 'color' => ['rgb' => '1D8348']]
    ]);
    $sheet->getStyle('D7:D9')->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F8F5']],
        'font' => ['italic' => true, 'color' => ['rgb' => '1D8348']]
    ]);
    $sheet->getStyle('F7:F9')->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F8F5']],
        'font' => ['italic' => true, 'color' => ['rgb' => '1D8348']]
    ]);
    
    $dataRange = 'A6:F105';
    $sheet->getStyle($dataRange)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'D6DBDF']
            ]
        ]
    ]);
    
    $sheet->getStyle('A7:A105')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B7:B105')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C7:C105')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F7:F105')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    $sheet->setAutoFilter('A6:F6');
    $sheet->freezePane('A7');
    
    $spreadsheet->setActiveSheetIndex(0);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Template_Jadwal_SiPagu.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ============================================================================
// FUNGSI LAINNYA
// ============================================================================

function find_header_row($sheetData) {
    for ($i = 0; $i < min(10, count($sheetData)); $i++) {
        $row = array_map('clean_header', $sheetData[$i]);
        $row_upper = array_map('strtoupper', $row);
        
        if (in_array('SEMESTER', $row_upper) && in_array('KODE_MATKUL', $row_upper) && in_array('NAMA_DOSEN', $row_upper)) {
            return $i;
        }
    }
    return -1;
}

function validate_template_structure($file_path, $file_ext, $template_columns) {
    try {
        $reader = IOFactory::createReaderForFile($file_path);
        if ($file_ext == 'csv') {
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
        }
        
        $spreadsheet = $reader->load($file_path);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        
        $header_row = find_header_row($sheetData);
        
        if ($header_row === -1) {
            return ['valid' => false, 'message' => 'Header SEMESTER, KODE_MATKUL, NAMA_DOSEN tidak ditemukan.'];
        }
        
        $file_headers = array_map('strtoupper', array_map('clean_header', $sheetData[$header_row]));
        
        $has_semester = in_array('SEMESTER', $file_headers);
        $has_kode_matkul = in_array('KODE_MATKUL', $file_headers);
        $has_nama_matkul = in_array('NAMA_MATKUL', $file_headers);
        $has_nama_dosen = in_array('NAMA_DOSEN', $file_headers);
        
        if (!$has_semester) return ['valid' => false, 'message' => 'Kolom SEMESTER tidak ditemukan.'];
        if (!$has_kode_matkul) return ['valid' => false, 'message' => 'Kolom KODE_MATKUL tidak ditemukan.'];
        if (!$has_nama_matkul) return ['valid' => false, 'message' => 'Kolom NAMA_MATKUL tidak ditemukan.'];
        if (!$has_nama_dosen) return ['valid' => false, 'message' => 'Kolom NAMA_DOSEN tidak ditemukan.'];
        
        return ['valid' => true, 'header_row' => $header_row, 'message' => 'Template valid'];
        
    } catch (Exception $e) {
        return ['valid' => false, 'message' => 'Error membaca file: ' . $e->getMessage()];
    }
}

function get_preview_data($file_path, $file_ext) {
    try {
        $reader = IOFactory::createReaderForFile($file_path);
        if ($file_ext == 'csv') {
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
        }
        
        $spreadsheet = $reader->load($file_path);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        
        $header_row = find_header_row($sheetData);
        
        if ($header_row === -1) return false;
        
        $sample_data = [];
        $total_rows = 0;
        
        for ($i = $header_row + 1; $i < min($header_row + 6, count($sheetData)); $i++) {
            $row = array_map('safe_trim', $sheetData[$i]);
            if (!empty(array_filter($row, function($val) { return $val !== ''; }))) {
                $sample_data[] = $row;
            }
        }
        
        for ($i = $header_row + 1; $i < count($sheetData); $i++) {
            $row = array_map('safe_trim', $sheetData[$i]);
            if (!empty(array_filter($row, function($val) { return $val !== ''; }))) {
                $total_rows++;
            }
        }
        
        return [
            'headers' => array_map('safe_trim', $sheetData[$header_row]),
            'sample_data' => $sample_data,
            'total_rows' => $total_rows,
            'header_row' => $header_row
        ];
        
    } catch (Exception $e) {
        return false;
    }
}

function clean_old_temp_files($dir) {
    $files = glob($dir . 'upload_jadwal_*');
    foreach ($files as $file) {
        if (filemtime($file) < time() - 3600) {
            unlink($file);
        }
    }
}
clean_old_temp_files($temp_dir);

// Ambil data dosen untuk dropdown manual
$users = [];
$query = mysqli_query($koneksi, "SELECT id_user, npp_user, nama_user FROM t_user ORDER BY nama_user");
while ($row = mysqli_fetch_assoc($query)) {
    $users[$row['id_user']] = $row['npp_user'] . ' - ' . $row['nama_user'];
}

// Ambil data jadwal terbaru dengan pagination
$jadwal_page = isset($_GET['jadwal_page']) ? max(1, (int)$_GET['jadwal_page']) : 1;
$jadwal_per_page = 5;
$jadwal_offset = ($jadwal_page - 1) * $jadwal_per_page;

$total_jadwal_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_jadwal");
$total_jadwal = mysqli_fetch_assoc($total_jadwal_q)['total'];
$total_jadwal_pages = max(1, ceil($total_jadwal / $jadwal_per_page));
if ($jadwal_page > $total_jadwal_pages) $jadwal_page = $total_jadwal_pages;
$jadwal_offset = ($jadwal_page - 1) * $jadwal_per_page;

$query_jadwal = mysqli_query($koneksi, 
    "SELECT j.semester, j.kode_matkul, j.nama_matkul, u.nama_user, j.jml_mhs
     FROM t_jadwal j
     LEFT JOIN t_user u ON j.id_user = u.id_user
     ORDER BY j.id_jdwl DESC LIMIT $jadwal_offset, $jadwal_per_page"
);
$recent_jadwal = [];
while ($row = mysqli_fetch_assoc($query_jadwal)) {
    $recent_jadwal[] = $row;
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-calendar-alt mr-2"></i>Upload Data Jadwal</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Upload Jadwal</div>
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

            <!-- Template Info & Rules -->
            <div class="up-step-grid">
                <!-- Download Template Card -->
                <div class="up-step-card">
                    <div class="up-step-num">1</div>
                    <h5><i class="fas fa-download mr-2 text-info"></i>Download Template</h5>
                    <p class="text-muted small">Download template Excel dengan dropdown dosen untuk memudahkan pengisian data.</p>
                    
                    <a href="?action=download_template" class="up-btn up-btn-download btn-block">
                        <i class="fas fa-file-excel mr-2"></i> Download Template Jadwal
                    </a>
                    
                    <span class="up-note mt-3">
                        <i class="fas fa-check-circle text-success"></i> Dropdown dosen otomatis<br>
                        <i class="fas fa-exclamation-triangle text-danger"></i> <strong>Kode MK HARUS UNIK per semester!</strong>
                    </span>
                </div>

                <!-- Aturan Duplikasi Card -->
                <div class="up-step-card up-step-info">
                    <div class="up-step-num">2</div>
                    <h5><i class="fas fa-ban mr-2 text-warning"></i>Aturan Duplikasi</h5>
                    
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td width="30"><span class="badge badge-danger">❌</span></td>
                            <td><strong>DUPLIKAT KODE MK dalam 1 semester</strong><br>
                                <small class="text-danger">TIDAK DIIZINKAN! Akan ditolak sistem</small>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-warning">↻</span></td>
                            <td><strong>Kombinasi sama persis</strong><br>
                                <small>Jika centang TIMPA → UPDATE<br>Jika TIDAK centang → SKIP</small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Upload Form -->
            <?php if (empty($preview_data)): ?>
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon"><i class="fas fa-upload"></i></div>
                    <h5>Upload File Jadwal</h5>
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
                                <small>Update data dengan kombinasi Semester + Kode MK + Dosen yang sama persis</small>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="up-actions">
                            <button type="submit" name="submit" class="up-btn up-btn-primary">
                                <i class="fas fa-upload mr-2"></i> Upload & Validasi
                            </button>
                            <button type="button" class="up-btn up-btn-secondary" onclick="resetDropzone()">
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
                        
                        <?php if (isset($_SESSION['overwrite_option_jadwal']) && $_SESSION['overwrite_option_jadwal'] == '1'): ?>
                        <div class="up-confirm-box warning mb-3">
                            <div class="up-confirm-box-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="up-confirm-box-content">
                                <h6>Mode TIMPA AKTIF</h6>
                                <p>Data dengan kombinasi Semester + Kode MK + Dosen yang sama akan <strong>ditimpa/diperbarui</strong>! Pastikan ini adalah yang Anda inginkan.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="up-confirm-box mb-3">
                            <div class="up-confirm-box-icon"><i class="fas fa-info-circle"></i></div>
                            <div class="up-confirm-box-content">
                                <h6>Konfirmasi Import</h6>
                                <p>Akan mengimport <strong><?= $preview_data['total_rows'] ?></strong> data jadwal.</p>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Perhatian:</strong> Kode MK yang sama dalam semester yang sama akan DITOLAK!
                        </div>
                        
                        <div class="up-confirm-actions">
                            <button type="submit" name="confirm_import" class="up-btn up-btn-success up-btn-lg">
                                <i class="fas fa-database mr-2"></i> Konfirmasi Import
                            </button>
                            <a href="upload_jadwal.php" class="up-btn up-btn-secondary">
                                <i class="fas fa-times mr-2"></i> Batal
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
                    <form action="" method="POST">
                        <div class="up-form-grid">
                            <div class="up-form-group">
                                <label class="up-form-label">Semester <span class="req">*</span></label>
                                <select class="up-select" name="manual_semester" required>
                                    <option value="">Pilih Semester</option>
                                    <?php foreach ($semesterList as $s): ?>
                                        <option value="<?= htmlspecialchars($s) ?>"><?= formatSemester($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Kode MK <span class="req">*</span></label>
                                <input type="text" class="up-input" name="manual_kode_matkul" placeholder="SI101" required>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Nama MK <span class="req">*</span></label>
                                <input type="text" class="up-input" name="manual_nama_matkul" placeholder="Algoritma Pemrograman" required>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Dosen <span class="req">*</span></label>
                                <select class="up-select select2" name="manual_user" required>
                                    <option value="">Pilih Dosen</option>
                                    <?php foreach ($users as $id => $nama): ?>
                                        <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($nama) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="up-form-group">
                                <label class="up-form-label">Jumlah Mahasiswa</label>
                                <input type="number" class="up-input" name="manual_jml_mhs" min="0" value="0">
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
            <div class="up-main-card" id="recentJadwal">
                <div class="up-main-card-header">
                    <div class="up-card-icon"><i class="fas fa-history"></i></div>
                    <h5>Data Jadwal Terbaru</h5>
                    <div class="ml-auto">
                        <div class="up-search-box" style="width: 220px;">
                            <i class="fas fa-search"></i>
                            <input type="text" class="up-search-input" id="searchJadwal" placeholder="Cari jadwal..." onkeyup="filterTableJadwal()">
                        </div>
                    </div>
                </div>
                <div class="up-card-body">
                    <div class="up-table-wrap">
                        <table class="up-table" id="tableJadwal">
                            <thead>
                                <tr>
                                    <th>Semester</th>
                                    <th>Kode</th>
                                    <th>Mata Kuliah</th>
                                    <th>Dosen</th>
                                    <th>Jml</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_jadwal)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data jadwal</td></tr>
                                <?php else: ?>
                                <?php foreach ($recent_jadwal as $row): ?>
                                <tr>
                                    <td><?= formatSemester($row['semester']) ?></td>
                                    <td><strong><?= htmlspecialchars($row['kode_matkul']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['nama_matkul']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_user'] ?? '-') ?></td>
                                    <td><?= number_format($row['jml_mhs']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_jadwal_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Total <?= $total_jadwal ?> data | Halaman <?= $jadwal_page ?> dari <?= $total_jadwal_pages ?></small>
                        <ul class="up-pagination mb-0">
                            <li class="up-page-item <?= ($jadwal_page <= 1) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?jadwal_page=<?= $jadwal_page-1 ?>#recentJadwal"><i class="fas fa-chevron-left"></i></a>
                            </li>
                            <?php for ($p = max(1, $jadwal_page-2); $p <= min($total_jadwal_pages, $jadwal_page+2); $p++): ?>
                            <li class="up-page-item <?= ($p == $jadwal_page) ? 'active' : '' ?>">
                                <a class="up-page-link" href="?jadwal_page=<?= $p ?>#recentJadwal"><?= $p ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="up-page-item <?= ($jadwal_page >= $total_jadwal_pages) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?jadwal_page=<?= $jadwal_page+1 ?>#recentJadwal"><i class="fas fa-chevron-right"></i></a>
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

function clearManualForm() {
    document.querySelector('form[action=""][method="POST"]:not([enctype])').reset();
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

function filterTableJadwal() {
    var input = document.getElementById("searchJadwal");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("tableJadwal");
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