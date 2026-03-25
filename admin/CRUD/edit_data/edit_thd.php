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
if (!isset($_GET['id_thd'])) {
    header("Location: ../../admin/honor_dosen.php");
    exit;
}

$id_thd = (int) $_GET['id_thd'];

/* =====================
   AMBIL DATA TRANSAKSI
===================== */
$stmt = $koneksi->prepare("
    SELECT *
    FROM t_transaksi_honor_dosen
    WHERE id_thd = ?
");
$stmt->bind_param("i", $id_thd);
$stmt->execute();

$thd = $stmt->get_result()->fetch_assoc();

if (!$thd) {
    header("Location: ../../admin/honor_dosen.php");
    exit;
}

/* =====================
   DATA PENDUKUNG
===================== */
$semesterList = generateSemester();

/* jadwal sesuai struktur tabel */
$q_jadwal = $koneksi->query("
    SELECT 
        id_jdwl,
        semester,
        kode_matkul,
        nama_matkul,
        jml_mhs
    FROM t_jadwal
    ORDER BY semester DESC, nama_matkul ASC
");

$enum = $koneksi->query("
    SELECT COLUMN_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 't_transaksi_honor_dosen'
      AND COLUMN_NAME = 'bulan'
")->fetch_assoc();

$enum_values = str_replace(["enum('", "')"], '', $enum['COLUMN_TYPE']);
$bulan_enum = explode("','", $enum_values);


/* =====================
   PROSES UPDATE
===================== */
if (isset($_POST['submit'])) {

    $semester   = $_POST['semester'];
    $bulan      = $_POST['bulan'];
    $id_jadwal  = (int) $_POST['id_jadwal'];
    $jml_tm     = (int) $_POST['jml_tm'];
    $sks_tempuh = (int) $_POST['sks_tempuh'];

    $stmt = $koneksi->prepare("
        UPDATE t_transaksi_honor_dosen SET
            semester    = ?,
            bulan       = ?,
            id_jadwal   = ?,
            jml_tm      = ?,
            sks_tempuh  = ?
        WHERE id_thd = ?
    ");

    $stmt->bind_param(
        "ssiiii",
        $semester,
        $bulan,
        $id_jadwal,
        $jml_tm,
        $sks_tempuh,
        $id_thd
    );

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Data Transaksi Honor Dosen berhasil diperbarui.";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui data Transaksi Honor Dosen.";
    }

    header("Location: ../../admin/honor_dosen.php");
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
            <h1>Edit Transaksi Honor Dosen</h1>
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
                                    <option value="<?= $s ?>" <?= ($thd['semester'] == $s) ? 'selected' : '' ?>>
                                        <?= formatSemester($s) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

<div class="form-group">
    <label>Periode Wisuda</label>
    <select name="bulan" class="form-control" required>
        <option value="">-- Pilih Periode Wisuda --</option>

        <?php foreach ($bulan_enum as $b): ?>
            <option value="<?= $b ?>"
                <?= ($thd['bulan'] === $b) ? 'selected' : '' ?>>
                <?= ucfirst($b) ?>
            </option>
        <?php endforeach; ?>

    </select>
</div>

                        <div class="form-group">
                            <label>Jadwal</label>
                            <select name="id_jadwal" class="form-control" required>
                                <option value="">-- Pilih Jadwal --</option>
                                <?php while ($j = $q_jadwal->fetch_assoc()): ?>
                                    <option
                                        value="<?= $j['id_jdwl'] ?>"
                                        <?= ($thd['id_jadwal'] == $j['id_jdwl']) ? 'selected' : '' ?>
                                    >
                                        <?= formatSemester($j['semester']) ?> |
                                        <?= $j['kode_matkul'] ?> - <?= $j['nama_matkul'] ?> |
                                        <?= $j['jml_mhs'] ?> mahasiswa
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Jumlah Tatap Muka</label>
                            <input
                                type="number"
                                name="jml_tm"
                                value="<?= $thd['jml_tm'] ?>"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>SKS Tempuh</label>
                            <input
                                type="number"
                                name="sks_tempuh"
                                value="<?= $thd['sks_tempuh'] ?>"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="text-right">
                            <a href="../../admin/honor_dosen.php" class="btn btn-secondary">
                                Batal
                            </a>
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