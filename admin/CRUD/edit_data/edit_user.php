<?php
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../config.php';

if (!isset($_GET['id_user'])) {
    header("Location: users.php");
    exit;
}

$id_user = (int) $_GET['id_user'];

/* =====================
   AMBIL DATA USER
===================== */
$stmt = $koneksi->prepare("SELECT * FROM t_user WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: users.php");
    exit;
}

/* =====================
   PROSES UPDATE
===================== */
if (isset($_POST['submit'])) {

    $npp   = $_POST['npp_user'];
    $nik   = $_POST['nik_user'];
    $npwp  = $_POST['npwp_user'];
    $norek = $_POST['norek_user'];
    $nama  = $_POST['nama_user'];
    $nohp  = $_POST['nohp_user'];
    $honor_persks  = $_POST['honor_persks'];
    $role  = $_POST['role_user'];

    if (!empty($_POST['pw_user'])) {

        $password = password_hash($_POST['pw_user'], PASSWORD_DEFAULT);

        $sql = "UPDATE t_user SET
                npp_user=?, nik_user=?, npwp_user=?, norek_user=?,
                nama_user=?, nohp_user=?, honor_persks=?, role_user=?, pw_user=?
                WHERE id_user=?";

        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param(
            "ssssssissi",
            $npp, $nik, $npwp, $norek, $nama, $nohp, $honor_persks, $role, $password, $id_user
        );

    } else {

        $sql = "UPDATE t_user SET
                npp_user=?, nik_user=?, npwp_user=?, norek_user=?,
                nama_user=?, nohp_user=?, honor_persks=?, role_user=?
                WHERE id_user=?";

        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param(
            "ssssssisi",
            $npp, $nik, $npwp, $norek, $nama, $nohp, $honor_persks, $role, $id_user
        );
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Data user berhasil diperbarui.";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui data user.";
    }

    header("Location: ../../admin/users.php");
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
        <h1>Edit User</h1>
    </div>

    <div class="section-body">
        <div class="card">
            <div class="card-body">
                <form method="POST">

                    <div class="form-group">
                        <label>NPP</label>
                        <input type="text" name="npp_user"
                               class="form-control"
                               value="<?= htmlspecialchars($user['npp_user']) ?>"
                               readonly>
                    </div>

                    <div class="form-group">
                        <label>Nama</label>
                        <input type="text" name="nama_user"
                               class="form-control"
                               value="<?= htmlspecialchars($user['nama_user']) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label>NIK</label>
                        <input type="text" name="nik_user"
                               class="form-control"
                               value="<?= htmlspecialchars($user['nik_user']) ?>">
                    </div>

                    <div class="form-group">
                        <label>NPWP</label>
                        <input type="text" name="npwp_user"
                               class="form-control"
                               value="<?= htmlspecialchars($user['npwp_user']) ?>">
                    </div>

                    <div class="form-group">
                        <label>No Rekening</label>
                        <input type="text" name="norek_user"
                               class="form-control"
                               value="<?= htmlspecialchars($user['norek_user']) ?>">
                    </div>

                    <div class="form-group">
                        <label>No HP</label>
                        <input type="text" name="nohp_user"
                               class="form-control"
                               value="<?= htmlspecialchars($user['nohp_user']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Honor PerSKS</label>
                        <input type="text" name="honor_persks"
                               class="form-control"
                               value="<?= htmlspecialchars($user['honor_persks']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                                            <select class="form-control" name="role_user" required>
                                                <option value="">Pilih Role</option>
                                                <option value="admin">Admin</option>
                                                <option value="koordinator">Koordinator</option>
                                                <option value="staff" selected>Staff</option>
                                            </select>

                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="pw_user"
                               class="form-control"
                               placeholder="Kosongkan jika tidak diganti">
                    </div>

                    <div class="text-right">
                        <a href="../../admin/users.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" name="submit"
                                class="btn btn-primary">
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