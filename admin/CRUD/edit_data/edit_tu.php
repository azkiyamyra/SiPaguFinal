<?php
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../config.php';

/* =====================
   HELPER SEMESTER
===================== */
function formatSemester($semester)
{
    $tahun = substr($semester, 0, 4);
    $kode  = substr($semester, -1);
    return $tahun . ' ' . (($kode == '1') ? 'Ganjil' : 'Genap');
}

function generateSemester($startYear = 2022, $range = 4)
{
    $list = [];
    $currentYear = date('Y');

    for ($y = $startYear; $y <= $currentYear + $range; $y++) {
        $list[] = $y . '1';
        $list[] = $y . '2';
    }
    return $list;
}

/* =====================
   VALIDASI ID
===================== */
if (!isset($_GET['id_tu'])) {
    header("Location: ../../admin/transaksi_ujian.php");
    exit;
}

$id_tu = (int) $_GET['id_tu'];

/* =====================
   AMBIL DATA TRANSAKSI
===================== */
$stmt = $koneksi->prepare("
    SELECT *
    FROM t_transaksi_ujian
    WHERE id_tu = ?
");
$stmt->bind_param("i", $id_tu);
$stmt->execute();
$tu = $stmt->get_result()->fetch_assoc();

if (!$tu) {
    header("Location: ../../admin/transaksi_ujian.php");
    exit;
}

/* =====================
   DATA PENDUKUNG
===================== */
$semesterList = generateSemester();

// dosen
$q_user = $koneksi->query("
    SELECT id_user, nama_user
    FROM t_user
    ORDER BY nama_user ASC
");

// panitia
$q_panitia = $koneksi->query("
    SELECT id_pnt, jbtn_pnt
    FROM t_panitia
    ORDER BY jbtn_pnt ASC
");

/* =====================
   PROSES UPDATE
===================== */
if (isset($_POST['submit'])) {

    $semester        = $_POST['semester'];
    $id_panitia      = (int) $_POST['id_panitia'];
    $id_user         = (int) $_POST['id_user'];

    $jml_mhs_prodi   = (int) $_POST['jml_mhs_prodi'];
    $jml_mhs         = (int) $_POST['jml_mhs'];
    $jml_koreksi    = (int) $_POST['jml_koreksi'];
    $jml_matkul     = (int) $_POST['jml_matkul'];

    $jml_pgws_pagi  = (int) $_POST['jml_pgws_pagi'];
    $jml_pgws_sore  = (int) $_POST['jml_pgws_sore'];
    $jml_koor_pagi  = (int) $_POST['jml_koor_pagi'];
    $jml_koor_sore  = (int) $_POST['jml_koor_sore'];

    $stmt = $koneksi->prepare("
        UPDATE t_transaksi_ujian SET
            semester = ?,
            id_panitia = ?,
            id_user = ?,
            jml_mhs_prodi = ?,
            jml_mhs = ?,
            jml_koreksi = ?,
            jml_matkul = ?,
            jml_pgws_pagi = ?,
            jml_pgws_sore = ?,
            jml_koor_pagi = ?,
            jml_koor_sore = ?
        WHERE id_tu = ?
    ");

    $stmt->bind_param(
        "siiiiiiiiiii",
        $semester,
        $id_panitia,
        $id_user,
        $jml_mhs_prodi,
        $jml_mhs,
        $jml_koreksi,
        $jml_matkul,
        $jml_pgws_pagi,
        $jml_pgws_sore,
        $jml_koor_pagi,
        $jml_koor_sore,
        $id_tu
    );

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Data Transaksi Ujian berhasil diperbarui.";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui data Transaksi Ujian.";
    }

    header("Location: ../../admin/transaksi_ujian.php");
    exit;
}

/* =====================
   TEMPLATE
===================== */
include __DIR__ . '/../../admin/includes/header.php';
include __DIR__ . '/../../admin/includes/navbar.php';
include __DIR__ . '/../../admin/includes/sidebar_admin.php';
?>

<div class="main-content">
<section class="section">
    <div class="section-header">
        <h1>Edit Transaksi Ujian</h1>
    </div>

    <div class="section-body">
        <div class="card">
            <div class="card-body">

                <form method="POST">

                    <div class="form-group">
                        <label>Semester</label>
                        <select name="semester" class="form-control" required>
                            <option value="">-- Pilih Semester --</option>
                            <?php foreach ($semesterList as $s): ?>
                                <option value="<?= $s ?>" <?= ($tu['semester'] == $s) ? 'selected' : '' ?>>
                                    <?= formatSemester($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Dosen</label>
                        <select name="id_user" class="form-control" required>
                            <option value="">-- Pilih Dosen --</option>
                            <?php while ($u = $q_user->fetch_assoc()): ?>
                                <option value="<?= $u['id_user'] ?>" <?= ($tu['id_user'] == $u['id_user']) ? 'selected' : '' ?>>
                                    <?= $u['nama_user'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Panitia</label>
                        <select name="id_panitia" class="form-control" required>
                            <option value="">-- Pilih Panitia --</option>
                            <?php while ($p = $q_panitia->fetch_assoc()): ?>
                                <option value="<?= $p['id_pnt'] ?>" <?= ($tu['id_panitia'] == $p['id_pnt']) ? 'selected' : '' ?>>
                                    <?= $p['jbtn_pnt'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <?php
                    $fields = [
                        'jml_mhs_prodi' => 'Jumlah Mahasiswa Prodi',
                        'jml_mhs'       => 'Jumlah Mahasiswa',
                        'jml_koreksi'   => 'Jumlah Koreksi',
                        'jml_matkul'    => 'Jumlah Mata Kuliah',
                        'jml_pgws_pagi' => 'Pengawas Pagi',
                        'jml_pgws_sore' => 'Pengawas Sore',
                        'jml_koor_pagi' => 'Koordinator Pagi',
                        'jml_koor_sore' => 'Koordinator Sore'
                    ];

                    foreach ($fields as $name => $label):
                    ?>
                        <div class="form-group">
                            <label><?= $label ?></label>
                            <input type="number" name="<?= $name ?>" value="<?= $tu[$name] ?>" class="form-control">
                        </div>
                    <?php endforeach; ?>

                    <div class="text-right">
                        <a href="../../admin/transaksi_ujian.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" name="submit" class="btn btn-primary">
                            Simpan Perubahan
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</section>
</div>

<?php include __DIR__ . '/../../admin/includes/footer.php'; ?>