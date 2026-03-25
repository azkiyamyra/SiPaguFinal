<?php
session_start();
require_once '../config.php';
require_once 'includes/function_helper.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role_user'] != 'staff') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$jenis = $_GET['jenis'] ?? '';
$id = $_GET['id'] ?? 0;

if (empty($jenis) || empty($id)) {
    die('Parameter tidak lengkap');
}

// Ambil data user
$query_user = "SELECT * FROM t_user WHERE id_user = ?";
$stmt = mysqli_prepare($koneksi, $query_user);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);

$detail = [];
$rincian = [];

// Query berdasarkan jenis
if ($jenis == 'Honor Mengajar') {
    $query = "
        SELECT 
            thd.*,
            jdwl.kode_matkul,
            jdwl.nama_matkul,
            jdwl.jml_mhs,
            COALESCE(u.honor_persks, 50000) as honor_persks,
            (thd.jml_tm * thd.sks_tempuh * COALESCE(u.honor_persks, 50000)) as nominal
        FROM t_transaksi_honor_dosen thd
        JOIN t_jadwal jdwl ON thd.id_jadwal = jdwl.id_jdwl
        JOIN t_user u ON jdwl.id_user = u.id_user
        WHERE thd.id_thd = ? AND jdwl.id_user = ?
    ";
    
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id, $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $detail = mysqli_fetch_assoc($result);
    
    if ($detail) {
        $rincian = [
            'Mata Kuliah' => $detail['nama_matkul'],
            'Kode MK' => $detail['kode_matkul'],
            'SKS' => $detail['sks_tempuh'],
            'Jumlah Tatap Muka' => $detail['jml_tm'] . 'x',
            'Honor per SKS' => formatRupiah($detail['honor_persks']),
            'Total Mahasiswa' => $detail['jml_mhs'] . ' orang'
        ];
    }
    
} elseif ($jenis == 'Honor PA/TA') {
    $query = "
        SELECT 
            tpt.*,
            p.jbtn_pnt,
            p.honor_std as nominal
        FROM t_transaksi_pa_ta tpt
        JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
        WHERE tpt.id_tpt = ? AND tpt.id_user = ?
    ";
    
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id, $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $detail = mysqli_fetch_assoc($result);
    
    if ($detail) {
        $rincian = [
            'Jabatan' => $detail['jbtn_pnt'],
            'Prodi' => $detail['prodi'],
            'Jumlah Mahasiswa Prodi' => $detail['jml_mhs_prodi'] . ' orang',
            'Jumlah Bimbingan' => $detail['jml_mhs_bimbingan'] . ' orang',
            'PGJI 1' => $detail['jml_pgji_1'] . ' orang',
            'PGJI 2' => $detail['jml_pgji_2'] . ' orang',
            'Ketua PGJI' => $detail['ketua_pgji']
        ];
    }
    
} elseif ($jenis == 'Honor Ujian') {
    $query = "
        SELECT 
            tu.*,
            p.jbtn_pnt,
            p.honor_std as nominal
        FROM t_transaksi_ujian tu
        JOIN t_panitia p ON tu.id_panitia = p.id_pnt
        WHERE tu.id_tu = ? AND tu.id_user = ?
    ";
    
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id, $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $detail = mysqli_fetch_assoc($result);
    
    if ($detail) {
        $rincian = [
            'Jabatan' => $detail['jbtn_pnt'],
            'Jumlah Mahasiswa Prodi' => $detail['jml_mhs_prodi'] . ' orang',
            'Jumlah Mahasiswa' => $detail['jml_mhs'] . ' orang',
            'Jumlah Koreksi' => $detail['jml_koreksi'] . 'x',
            'Jumlah Matkul' => $detail['jml_matkul'],
            'Pengawas Pagi' => $detail['jml_pgws_pagi'] . 'x',
            'Pengawas Sore' => $detail['jml_pgws_sore'] . 'x',
            'Koordinator Pagi' => $detail['jml_koor_pagi'] . 'x',
            'Koordinator Sore' => $detail['jml_koor_sore'] . 'x'
        ];
    }
}

if (!$detail) {
    die('Data tidak ditemukan');
}

$perhitungan = hitungHonorStaff($detail['nominal']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Honor - <?= $jenis ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background: #f0f2f5; padding: 20px; }
        .print-container { max-width: 800px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .no-print { padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); text-align: center; }
        .btn-print { background: white; color: #667eea; border: none; padding: 12px 30px; border-radius: 50px; font-size: 16px; font-weight: bold; cursor: pointer; margin: 0 10px; transition: all 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-print:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
        .btn-back { background: transparent; color: white; border: 2px solid white; padding: 12px 30px; border-radius: 50px; font-size: 16px; font-weight: bold; cursor: pointer; margin: 0 10px; transition: all 0.3s; }
        .btn-back:hover { background: white; color: #667eea; }
        .slip-content { padding: 40px; }
        @media print { .no-print { display: none; } body { background: white; padding: 0; } .print-container { box-shadow: none; border-radius: 0; } .slip-content { padding: 20px; } }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #667eea; padding-bottom: 20px; }
        .header h1 { color: #667eea; font-size: 32px; margin-bottom: 5px; }
        .header h3 { color: #666; font-weight: normal; margin-bottom: 10px; }
        .header h2 { color: #333; font-size: 24px; }
        .info-box { background: #f8f9fc; border: 1px solid #e3e6f0; border-radius: 10px; padding: 20px; margin-bottom: 30px; }
        .info-table { width: 100%; }
        .info-table td { padding: 8px; }
        .section-title { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 20px; margin: 25px 0 15px 0; border-radius: 10px; font-weight: bold; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f2f2f2; padding: 12px; text-align: left; border: 1px solid #ddd; font-weight: 600; }
        td { padding: 10px 12px; border: 1px solid #ddd; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .total-box { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 2px solid #28a745; border-radius: 15px; padding: 20px 25px; margin-top: 30px; }
        .total-row { display: flex; justify-content: space-between; align-items: center; }
        .total-row h4 { margin: 0; color: #155724; font-size: 20px; }
        .total-row h2 { margin: 0; color: #155724; font-size: 28px; font-weight: bold; }
        .tax-box { margin-top: 20px; display: flex; justify-content: space-between; gap: 20px; }
        .tax-item { flex: 1; padding: 15px; border-radius: 10px; text-align: center; }
        .tax-nominal { background: #e3f2fd; border: 1px solid #90caf9; }
        .tax-pajak { background: #ffebee; border: 1px solid #ef5350; }
        .tax-potongan { background: #fff3e0; border: 1px solid #ffb74d; }
        .tax-bersih { background: #e8f5e9; border: 1px solid #66bb6a; }
        .tax-label { font-size: 14px; margin-bottom: 5px; color: #666; }
        .tax-value { font-size: 20px; font-weight: bold; }
        .signature { margin-top: 70px; display: flex; justify-content: space-between; }
        .signature div { width: 45%; }
        .footer { margin-top: 50px; text-align: right; font-size: 11px; color: #666; border-top: 1px solid #ddd; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="no-print">
            <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> Cetak / Simpan PDF</button>
            <button onclick="window.close()" class="btn-back"><i class="fas fa-times"></i> Tutup</button>
        </div>
        
        <div class="slip-content">
            <div class="header">
                <h1>SiPagu</h1>
                <h3>Sistem Informasi Honor Dosen</h3>
                <h2>SLIP HONOR STAFF</h2>
                <h3><?= $jenis ?> - <?= $detail['semester'] ?></h3>
            </div>
            
            <div class="info-box">
                <table class="info-table">
                    <tr>
                        <td width="120"><strong>Nama</strong></td>
                        <td width="10">:</td>
                        <td><?= htmlspecialchars($user['nama_user'] ?? '') ?></td>
                        <td width="120"><strong>Semester</strong></td>
                        <td width="10">:</td>
                        <td><?= $detail['semester'] ?></td>
                    </tr>
                    <tr>
                        <td><strong>NPP</strong></td>
                        <td>:</td>
                        <td><?= htmlspecialchars($user['npp_user'] ?? '') ?></td>
                        <?php if (isset($detail['bulan']) && $detail['bulan']): ?>
                        <td><strong>Bulan</strong></td>
                        <td>:</td>
                        <td><?= ucfirst($detail['bulan']) ?></td>
                        <?php endif; ?>
                    </tr>
                </table>
            </div>

            <div class="section-title">RINCIAN KEGIATAN</div>
            <table>
                <tbody>
                    <?php foreach($rincian as $key => $value): ?>
                    <tr>
                        <th width="250"><?= $key ?></th>
                        <td><?= $value ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="section-title">PERHITUNGAN HONOR</div>
            
            <div class="tax-box">
                <div class="tax-item tax-nominal">
                    <div class="tax-label">Nominal</div>
                    <div class="tax-value"><?= formatRupiah($perhitungan['nominal']) ?></div>
                </div>
                <div class="tax-item tax-pajak">
                    <div class="tax-label">Pajak (5%)</div>
                    <div class="tax-value"><?= formatRupiah($perhitungan['pajak']) ?></div>
                </div>
                <div class="tax-item tax-potongan">
                    <div class="tax-label">Potongan (5%)</div>
                    <div class="tax-value"><?= formatRupiah($perhitungan['potongan']) ?></div>
                </div>
                <div class="tax-item tax-bersih">
                    <div class="tax-label">Honor Bersih</div>
                    <div class="tax-value"><?= formatRupiah($perhitungan['bersih']) ?></div>
                </div>
            </div>

            <div style="margin: 20px 0; padding: 15px; background: #f8f9fc; border-radius: 10px;">
                <strong>Detail Perhitungan:</strong><br>
                <?= formatRupiah($perhitungan['nominal']) ?> - Pajak 5% (<?= formatRupiah($perhitungan['pajak']) ?>) = <?= formatRupiah($perhitungan['sisa']) ?><br>
                <?= formatRupiah($perhitungan['sisa']) ?> - Potongan 5% (<?= formatRupiah($perhitungan['potongan']) ?>) = <strong><?= formatRupiah($perhitungan['bersih']) ?></strong>
            </div>

            <div class="total-box">
                <div class="total-row">
                    <h4>HONOR BERSIH DITERIMA</h4>
                    <h2><?= formatRupiah($perhitungan['bersih']) ?></h2>
                </div>
            </div>

            <div class="signature">
                <div>
                    <p>Staff,</p>
                    <br><br><br>
                    <p><strong><?= htmlspecialchars($user['nama_user'] ?? '') ?></strong></p>
                    <p><?= htmlspecialchars($user['npp_user'] ?? '') ?></p>
                </div>
                <div>
                    <p>Mengetahui,</p>
                    <p>Koordinator</p>
                    <br><br><br>
                    <p><strong>( _________________ )</strong></p>
                </div>
            </div>

            <div style="margin-top: 20px; font-size: 10px; color: #666; text-align: center;">
                <p><em>* Pajak 5% dari nominal dan potongan 5% dari sisa</em></p>
            </div>

            <div class="footer">
                <p>Dokumen ini digenerate pada <?= date('d/m/Y H:i:s') ?></p>
            </div>
        </div>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>
<?php
mysqli_close($koneksi);
?>