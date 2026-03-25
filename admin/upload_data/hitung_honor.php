<?php
/**
 * HITUNG HONOR DOSEN - SiPagu
 * Halaman untuk menghitung honor dosen berdasarkan jadwal
 * Lokasi: admin/hitung_honor.php
 */

// Include required files
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

$page_title = "Hitung Honor Dosen";

$error_message = '';
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hitung_honor'])) {
    $semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
    $bulan = mysqli_real_escape_string($koneksi, $_POST['bulan']);
    $tarif_per_sks = mysqli_real_escape_string($koneksi, $_POST['tarif_per_sks']);
    
    if (empty($semester) || empty($bulan) || empty($tarif_per_sks)) {
        $error_message = "Semua field wajib diisi!";
    } else {
        // Ambil semua jadwal untuk semester tersebut
        $query_jadwal = mysqli_query($koneksi, 
            "SELECT j.*, u.honor_persks 
             FROM t_jadwal j
             LEFT JOIN t_user u ON j.id_user = u.id_user
             WHERE j.semester = '$semester'"
        );
        
        $jumlah_data = 0;
        $total_honor = 0;
        
        while ($jadwal = mysqli_fetch_assoc($query_jadwal)) {
            $id_jadwal = $jadwal['id_jdwl'];
            $sks_tempuh = 3; // Default SKS, bisa disesuaikan
            $jml_tm = 14; // Default 14 TM per semester
            
            // Hitung honor
            $honor_dasar = $jadwal['honor_persks'] > 0 ? $jadwal['honor_persks'] : $tarif_per_sks;
            $total_honor_per_dosen = $honor_dasar * $sks_tempuh;
            
            // Cek apakah sudah ada transaksi untuk bulan ini
            $cek = mysqli_query($koneksi,
                "SELECT id_thd FROM t_transaksi_honor_dosen 
                 WHERE semester = '$semester' 
                 AND bulan = '$bulan'
                 AND id_jadwal = '$id_jadwal'"
            );
            
            if (mysqli_num_rows($cek) > 0) {
                // Update jika sudah ada
                $update = mysqli_query($koneksi, "
                    UPDATE t_transaksi_honor_dosen SET
                        jml_tm = '$jml_tm',
                        sks_tempuh = '$sks_tempuh'
                    WHERE semester = '$semester' 
                    AND bulan = '$bulan'
                    AND id_jadwal = '$id_jadwal'
                ");
            } else {
                // Insert baru
                $insert = mysqli_query($koneksi, "
                    INSERT INTO t_transaksi_honor_dosen 
                    (semester, bulan, id_jadwal, jml_tm, sks_tempuh)
                    VALUES
                    ('$semester', '$bulan', '$id_jadwal', '$jml_tm', '$sks_tempuh')
                ");
            }
            
            $jumlah_data++;
        }
        
        if ($jumlah_data > 0) {
            $success_message = "Berhasil menghitung honor untuk <strong>$jumlah_data</strong> data jadwal.";
        } else {
            $error_message = "Tidak ada data jadwal untuk semester $semester";
        }
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/sidebar_admin.php';
?>

<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Hitung Honor Dosen</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Hitung Honor Dosen</div>
            </div>
        </div>

        <div class="section-body">
            <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible show fade">
                <div class="alert-body">
                    <button class="close" data-dismiss="alert"><span>×</span></button>
                    <i class="fas fa-exclamation-circle mr-2"></i><?= $error_message ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible show fade">
                <div class="alert-body">
                    <button class="close" data-dismiss="alert"><span>×</span></button>
                    <i class="fas fa-check-circle mr-2"></i><?= $success_message ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Hitung Honor Berdasarkan Jadwal</h4>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="form-group">
                                    <label>Semester <span class="text-danger">*</span></label>
                                    <select class="form-control" name="semester" required>
                                        <option value="">Pilih Semester</option>
                                        <option value="20241">2024 Ganjil (20241)</option>
                                        <option value="20242">2024 Genap (20242)</option>
                                        <option value="20251">2025 Ganjil (20251)</option>
                                        <option value="20252">2025 Genap (20252)</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Bulan <span class="text-danger">*</span></label>
                                    <select class="form-control" name="bulan" required>
                                        <option value="">Pilih Bulan</option>
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
                                
                                <div class="form-group">
                                    <label>Tarif per SKS (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="tarif_per_sks" value="100000" min="0" required>
                                </div>
                                
                                <button type="submit" name="hitung_honor" class="btn btn-primary btn-block">
                                    <i class="fas fa-calculator mr-2"></i>Hitung Honor
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-md-6">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4 class="mb-0">Data Honor Terbaru</h4>
                            <div class="ml-auto">
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="searchHitungHonor" placeholder="Cari..." onkeyup="filterTableHitungHonor()" style="width:150px;">
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="tableHitungHonor">
                                    <thead>
                                        <tr>
                                            <th>Semester</th>
                                            <th>Bulan</th>
                                            <th>Jumlah TM</th>
                                            <th>SKS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = mysqli_query($koneksi, 
                                            "SELECT semester, bulan, jml_tm, sks_tempuh
                                             FROM t_transaksi_honor_dosen
                                             ORDER BY id_thd DESC 
                                             LIMIT 10"
                                        );
                                        while ($row = mysqli_fetch_assoc($query)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['semester']) ?></td>
                                            <td><?= ucfirst(htmlspecialchars($row['bulan'])) ?></td>
                                            <td><?= $row['jml_tm'] ?></td>
                                            <td><?= $row['sks_tempuh'] ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
function filterTableHitungHonor() {
    var input = document.getElementById("searchHitungHonor");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("tableHitungHonor");
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