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
if (!isset($_GET['id_tpt'])) {
    header("Location: ../../admin/pa_ta.php");
    exit;
}

$id_tpt = (int) $_GET['id_tpt'];

/* =====================
   AMBIL DATA TPTA
===================== */
$stmt = $koneksi->prepare("
    SELECT *
    FROM t_transaksi_pa_ta
    WHERE id_tpt = ?
");
$stmt->bind_param("i", $id_tpt);
$stmt->execute();
$tpata = $stmt->get_result()->fetch_assoc();

if (!$tpata) {
    header("Location: ../../admin/pa_ta.php");
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

$enum = $koneksi->query("
    SELECT COLUMN_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 't_transaksi_pa_ta'
      AND COLUMN_NAME = 'periode_wisuda'
")->fetch_assoc();

$enum_values = str_replace(["enum('", "')"], '', $enum['COLUMN_TYPE']);
$bulan_enum = explode("','", $enum_values);


/* =====================
   PROSES UPDATE
===================== */
if (isset($_POST['submit'])) {

    $semester          = $_POST['semester'];
    $periode_wisuda    = $_POST['periode_wisuda'];
    $id_user           = (int) $_POST['id_user'];
    $id_panitia        = (int) $_POST['id_panitia'];
    $jml_mhs_prodi     = (int) $_POST['jml_mhs_prodi'];
    $jml_mhs_bimbingan = (int) $_POST['jml_mhs_bimbingan'];
    $prodi             = $_POST['prodi'];
    $jml_pgji_1        = (int) $_POST['jml_pgji_1'];
    $jml_pgji_2        = (int) $_POST['jml_pgji_2'];
    $ketua_pgji        = $_POST['ketua_pgji'];

    $stmt = $koneksi->prepare("
        UPDATE t_transaksi_pa_ta SET
            semester = ?,
            periode_wisuda = ?,
            id_user = ?,
            id_panitia = ?,
            jml_mhs_prodi = ?,
            jml_mhs_bimbingan = ?,
            prodi = ?,
            jml_pgji_1 = ?,
            jml_pgji_2 = ?,
            ketua_pgji = ?
        WHERE id_tpt = ?
    ");

$stmt->bind_param(
    "ssiiiisiisi",
    $semester,
    $periode_wisuda,
    $id_user,
    $id_panitia,
    $jml_mhs_prodi,
    $jml_mhs_bimbingan,
    $prodi,
    $jml_pgji_1,
    $jml_pgji_2,
    $ketua_pgji,
    $id_tpt
);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Data Transaksi PA/TA berhasil diperbarui.";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui data Transaksi PA/TA.";
    }

    header("Location: ../../admin/pa_ta.php");
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
        <h1>Edit TPTA</h1>
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
                                <option value="<?= $s ?>"
                                    <?= ($tpata['semester'] == $s) ? 'selected' : '' ?>>
                                    <?= formatSemester($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

<div class="form-group">
    <label>Periode Wisuda</label>
    <select name="periode_wisuda" class="form-control" required>
        <option value="">-- Pilih Periode Wisuda --</option>

        <?php foreach ($bulan_enum as $bulan): ?>
            <option value="<?= $bulan ?>"
                <?= ($tpata['periode_wisuda'] === $bulan) ? 'selected' : '' ?>>
                <?= ucfirst($bulan) ?>
            </option>
        <?php endforeach; ?>

    </select>
</div>
                    <div class="form-group">
                        <label>Dosen</label>
                        <select name="id_user" class="form-control" required>
                            <option value="">-- Pilih Dosen --</option>
                            <?php while ($u = $q_user->fetch_assoc()): ?>
                                <option value="<?= $u['id_user'] ?>"
                                    <?= ($tpata['id_user'] == $u['id_user']) ? 'selected' : '' ?>>
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
                                <option value="<?= $p['id_pnt'] ?>"
                                    <?= ($tpata['id_panitia'] == $p['id_pnt']) ? 'selected' : '' ?>>
                                    <?= $p['jbtn_pnt'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Jumlah Mahasiswa Prodi</label>
                        <input type="number" name="jml_mhs_prodi"
                               value="<?= $tpata['jml_mhs_prodi'] ?>"
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Jumlah Mahasiswa Bimbingan</label>
                        <input type="number" name="jml_mhs_bimbingan"
                               value="<?= $tpata['jml_mhs_bimbingan'] ?>"
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Program Studi</label>
                        <input type="text" name="prodi"
                               value="<?= $tpata['prodi'] ?>"
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Jumlah PGJI 1</label>
                        <input type="number" name="jml_pgji_1"
                               value="<?= $tpata['jml_pgji_1'] ?>"
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Jumlah PGJI 2</label>
                        <input type="number" name="jml_pgji_2"
                               value="<?= $tpata['jml_pgji_2'] ?>"
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Ketua PGJI</label>
                        <input type="text" name="ketua_pgji"
                               value="<?= $tpata['ketua_pgji'] ?>"
                               class="form-control">
                    </div>

                    <div class="text-right">
                        <a href="../../admin/pa_ta.php" class="btn btn-secondary">Batal</a>
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