<?php
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../config.php';

/* =====================
   VALIDASI ID
===================== */
if (!isset($_GET['id_pnt'])) {
    header("Location: panitia.php");
    exit;
}

$id_pnt = (int) $_GET['id_pnt'];

/* =====================
   AMBIL DATA PANITIA (1 DATA)
===================== */
$stmt = $koneksi->prepare("SELECT * FROM t_panitia WHERE id_pnt = ?");
$stmt->bind_param("i", $id_pnt);
$stmt->execute();
$panitia = $stmt->get_result()->fetch_assoc();

if (!$panitia) {
    header("Location: panitia.php");
    exit;
}

/* =====================
   AMBIL DAFTAR JABATAN (UNTUK SELECT)
===================== */
$q_jabatan = $koneksi->query("
    SELECT DISTINCT TRIM(jbtn_pnt) AS jbtn_pnt
    FROM t_panitia
    WHERE jbtn_pnt IS NOT NULL
    AND jbtn_pnt != ''
    ORDER BY jbtn_pnt ASC
");

/* =====================
   PROSES UPDATE
===================== */
if (isset($_POST['submit'])) {

    $jbtn_pnt   = $_POST['jbtn_pnt'];
    $honor_std  = (int) $_POST['honor_std'];
    $honor_p1   = (int) $_POST['honor_p1'];
    $honor_p2   = (int) $_POST['honor_p2'];

    $sql = "UPDATE t_panitia SET
            jbtn_pnt=?, honor_std=?, honor_p1=?, honor_p2=?
            WHERE id_pnt=?";

    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param(
        "siiii",
        $jbtn_pnt, $honor_std, $honor_p1, $honor_p2, $id_pnt
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Data panitia berhasil diperbarui.";
    } else {
        $_SESSION['error'] = "Gagal memperbarui data panitia.";
    }

    header("Location: ../../admin/panitia.php");
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
        <h1>Edit Panitia</h1>
    </div>

    <div class="section-body">
        <div class="card">
            <div class="card-body">
                <form method="POST">

                    <!-- JABATAN -->
                    <div class="form-group">
                        <label>Jabatan Panitia</label>
                        <select name="jbtn_pnt" class="form-control" required>
                            <option value="">-- Pilih Jabatan --</option>
                            <?php while ($row = $q_jabatan->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['jbtn_pnt']); ?>"
                                    <?= ($panitia['jbtn_pnt'] == $row['jbtn_pnt']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($row['jbtn_pnt']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- HONOR -->
                    <div class="form-group">
                        <label>Honor Standar</label>
                        <input type="number" name="honor_std" class="form-control"
                               value="<?= htmlspecialchars($panitia['honor_std']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Honor P1</label>
                        <input type="number" name="honor_p1" class="form-control"
                               value="<?= htmlspecialchars($panitia['honor_p1']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Honor P2</label>
                        <input type="number" name="honor_p2" class="form-control"
                               value="<?= htmlspecialchars($panitia['honor_p2']) ?>">
                    </div>

                    <div class="text-right">
                        <a href="../../admin/panitia.php" class="btn btn-secondary">Batal</a>
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