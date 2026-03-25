<?php
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../config.php';

/* =====================
   HELPER
===================== */
function formatSemester($semester)
{
    $tahun = substr($semester, 0, 4);
    $kode  = substr($semester, -1);
    return $tahun . ' ' . (($kode == '1') ? 'Ganjil' : 'Genap');
}

function generateSemester($startYear = 2020, $range = 6)
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
if (!isset($_GET['id_jdwl'])) {
    header("Location: ../../admin/jadwal.php");
    exit;
}

$id_jdwl = (int) $_GET['id_jdwl'];

/* =====================
   AMBIL DATA JADWAL
===================== */
$stmt = $koneksi->prepare("
    SELECT j.*, u.nama_user
    FROM t_jadwal j
    JOIN t_user u ON j.id_user = u.id_user
    WHERE j.id_jdwl = ?
");
$stmt->bind_param("i", $id_jdwl);
$stmt->execute();
$jadwal = $stmt->get_result()->fetch_assoc();

if (!$jadwal) {
    header("Location: ../../admin/jadwal.php");
    exit;
}

/* =====================
   DATA SEMESTER (GENERATE)
===================== */
$semesterList = generateSemester(2022, 4);

/* =====================
   DATA MATA KULIAH
===================== */
$q_matkul = $koneksi->query("
    SELECT DISTINCT kode_matkul, nama_matkul
    FROM t_jadwal
    ORDER BY nama_matkul ASC
");

/* =====================
   DATA DOSEN
===================== */
$q_user = $koneksi->query("
    SELECT id_user, nama_user
    FROM t_user
    ORDER BY nama_user ASC
");

/* =====================
   PROSES UPDATE
===================== */
if (isset($_POST['submit'])) {

    $semester = $_POST['semester'];
    $id_user  = (int) $_POST['id_user'];
    $jml_mhs  = (int) $_POST['jml_mhs'];

    list($kode_matkul, $nama_matkul) = explode('|', $_POST['matkul']);

    $stmt = $koneksi->prepare("
        UPDATE t_jadwal SET
            semester = ?,
            kode_matkul = ?,
            nama_matkul = ?,
            id_user = ?,
            jml_mhs = ?
        WHERE id_jdwl = ?
    ");
    $stmt->bind_param(
        "sssiii",
        $semester,
        $kode_matkul,
        $nama_matkul,
        $id_user,
        $jml_mhs,
        $id_jdwl
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Data jadwal berhasil diperbarui.";
    } else {
        $_SESSION['error'] = "Gagal memperbarui data jadwal.";
    }

    header("Location: ../../admin/jadwal.php");
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
        <h1>Edit Jadwal</h1>
    </div>

    <div class="section-body">
        <div class="card">
            <div class="card-body">

                <form method="POST">

                    <!-- SEMESTER -->
                    <div class="form-group">
                        <label>Semester</label>
                        <select name="semester" class="form-control" required>
                            <option value="">-- Pilih Semester --</option>
                            <?php foreach ($semesterList as $s): ?>
                                <option value="<?= $s ?>"
                                    <?= ($jadwal['semester'] == $s) ? 'selected' : ''; ?>>
                                    <?= formatSemester($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- MATA KULIAH -->
                    <div class="form-group">
                        <label>Mata Kuliah</label>
                        <select name="matkul" class="form-control" required>
                            <option value="">-- Pilih Mata Kuliah --</option>
                            <?php while ($m = $q_matkul->fetch_assoc()):
                                $value = $m['kode_matkul'].'|'.$m['nama_matkul'];
                                $selected = ($jadwal['kode_matkul'].'|'.$jadwal['nama_matkul'] === $value);
                            ?>
                                <option value="<?= $value ?>" <?= $selected ? 'selected' : '' ?>>
                                    <?= $m['kode_matkul'] ?> - <?= $m['nama_matkul'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- DOSEN -->
                    <div class="form-group">
                        <label>Dosen Pengampu</label>
                        <select name="id_user" class="form-control" required>
                            <option value="">-- Pilih Dosen --</option>
                            <?php while ($u = $q_user->fetch_assoc()): ?>
                                <option value="<?= $u['id_user'] ?>"
                                    <?= ($jadwal['id_user'] == $u['id_user']) ? 'selected' : ''; ?>>
                                    <?= $u['nama_user'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- JUMLAH MAHASISWA -->
                    <div class="form-group">
                        <label>Jumlah Mahasiswa</label>
                        <input type="number" name="jml_mhs" class="form-control"
                               value="<?= $jadwal['jml_mhs'] ?>" required>
                    </div>

                    <div class="text-right">
                        <a href="../../admin/jadwal.php" class="btn btn-secondary">Batal</a>
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