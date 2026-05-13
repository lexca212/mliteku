<?php

namespace Plugins\catatan_keuangan;

use Systems\AdminModule;

class Admin extends AdminModule
{
    public function navigation()
    {
        return [
            'Ringkasan' => 'manage',
            'Pemasukan' => 'pemasukan',
            'Pengeluaran' => 'pengeluaran',
        ];
    }

    public function getManage()
    {
        $this->_addHeaderFiles();
        $this->_ensureTables();

        $tanggal = $this->_getDateRange();
        $pemasukan = $this->_getPemasukanSummary($tanggal['start'], $tanggal['end']);
        $pengeluaran = $this->_getPengeluaranSummary($tanggal['start'], $tanggal['end']);

        return $this->draw('manage.html', [
            'tanggal' => htmlspecialchars_array($tanggal),
            'pemasukan' => htmlspecialchars_array($pemasukan),
            'pengeluaran' => htmlspecialchars_array($pengeluaran),
            'saldo' => $pemasukan['total_semua'] - $pengeluaran['total'],
            'token' => $this->_adminToken(),
        ]);
    }

    public function getPemasukan()
    {
        $this->_addHeaderFiles();
        $this->_ensureTables();

        $tanggal = $this->_getDateRange();
        $pemasukan = $this->_getPemasukanSummary($tanggal['start'], $tanggal['end']);

        return $this->draw('pemasukan.html', [
            'tanggal' => htmlspecialchars_array($tanggal),
            'pemasukan' => htmlspecialchars_array($pemasukan),
            'token' => $this->_adminToken(),
        ]);
    }

    public function getPengeluaran()
    {
        $this->_addHeaderFiles();
        $this->_ensureTables();

        $tanggal = $this->_getDateRange();
        $pengeluaran = $this->_getPengeluaranSummary($tanggal['start'], $tanggal['end']);
        $list = $this->db('catatan_keuangan_pengeluaran')
            ->where('tanggal', '>=', $tanggal['start'])
            ->where('tanggal', '<=', $tanggal['end'])
            ->desc('tanggal')
            ->toArray();

        foreach ($list as &$row) {
            $row['deleteURL'] = url([ADMIN, 'catatan_keuangan', 'deletepengeluaran', $row['id']]);
        }

        return $this->draw('pengeluaran.html', [
            'tanggal' => htmlspecialchars_array($tanggal),
            'pengeluaran' => htmlspecialchars_array($pengeluaran),
            'list' => htmlspecialchars_array($list),
            'kategori' => $this->_kategoriPengeluaran(),
            'token' => $this->_adminToken(),
        ]);
    }

    public function postSavePengeluaran()
    {
        $this->_ensureTables();

        if (checkEmptyFields(['tanggal', 'kategori', 'jumlah'], $_POST)) {
            $this->notify('failure', 'Tanggal, kategori, dan jumlah wajib diisi.');
            redirect(url([ADMIN, 'catatan_keuangan', 'pengeluaran']));
        }

        $query = $this->db('catatan_keuangan_pengeluaran')->save([
            'tanggal' => $_POST['tanggal'],
            'kategori' => $_POST['kategori'],
            'keterangan' => $_POST['keterangan'] ?? '',
            'jumlah' => (float) ($_POST['jumlah'] ?? 0),
            'petugas' => $this->core->getUserInfo('fullname', null, true),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->notify($query ? 'success' : 'failure', $query ? 'Pengeluaran berhasil disimpan.' : 'Pengeluaran gagal disimpan.');
        redirect(url([ADMIN, 'catatan_keuangan', 'pengeluaran']));
    }

    public function getDeletePengeluaran($id)
    {
        $this->_ensureTables();
        $query = $this->db('catatan_keuangan_pengeluaran')->where('id', $id)->delete();
        $this->notify($query ? 'success' : 'failure', $query ? 'Pengeluaran berhasil dihapus.' : 'Pengeluaran gagal dihapus.');
        redirect(url([ADMIN, 'catatan_keuangan', 'pengeluaran']));
    }

    private function _getPemasukanSummary($start, $end)
    {
        $sql = "
            SELECT sumber,
                   SUM(transaksi) AS transaksi,
                   SUM(material) AS material,
                   SUM(bhp) AS bhp,
                   SUM(jasa_dokter) AS jasa_dokter,
                   SUM(jasa_perawat) AS jasa_perawat,
                   SUM(kso) AS kso,
                   SUM(manajemen) AS manajemen,
                   SUM(obat) AS obat,
                   SUM(total) AS total
            FROM (
                SELECT 'Rawat Jalan Dokter' AS sumber, COUNT(*) AS transaksi,
                       COALESCE(SUM(material),0) AS material, COALESCE(SUM(bhp),0) AS bhp,
                       COALESCE(SUM(tarif_tindakandr),0) AS jasa_dokter, 0 AS jasa_perawat,
                       COALESCE(SUM(kso),0) AS kso, COALESCE(SUM(menejemen),0) AS manajemen,
                       0 AS obat, COALESCE(SUM(biaya_rawat),0) AS total
                FROM rawat_jl_dr WHERE tgl_perawatan BETWEEN ? AND ?
                UNION ALL
                SELECT 'Rawat Jalan Perawat', COUNT(*),
                       COALESCE(SUM(material),0), COALESCE(SUM(bhp),0),
                       0, COALESCE(SUM(tarif_tindakanpr),0),
                       COALESCE(SUM(kso),0), COALESCE(SUM(menejemen),0),
                       0, COALESCE(SUM(biaya_rawat),0)
                FROM rawat_jl_pr WHERE tgl_perawatan BETWEEN ? AND ?
                UNION ALL
                SELECT 'Rawat Jalan Dokter & Perawat', COUNT(*),
                       COALESCE(SUM(material),0), COALESCE(SUM(bhp),0),
                       COALESCE(SUM(tarif_tindakandr),0), COALESCE(SUM(tarif_tindakanpr),0),
                       COALESCE(SUM(kso),0), COALESCE(SUM(menejemen),0),
                       0, COALESCE(SUM(biaya_rawat),0)
                FROM rawat_jl_drpr WHERE tgl_perawatan BETWEEN ? AND ?
                UNION ALL
                SELECT 'Rawat Inap Dokter', COUNT(*),
                       COALESCE(SUM(material),0), COALESCE(SUM(bhp),0),
                       COALESCE(SUM(tarif_tindakandr),0), 0,
                       COALESCE(SUM(kso),0), COALESCE(SUM(menejemen),0),
                       0, COALESCE(SUM(biaya_rawat),0)
                FROM rawat_inap_dr WHERE tgl_perawatan BETWEEN ? AND ?
                UNION ALL
                SELECT 'Rawat Inap Perawat', COUNT(*),
                       COALESCE(SUM(material),0), COALESCE(SUM(bhp),0),
                       0, COALESCE(SUM(tarif_tindakanpr),0),
                       COALESCE(SUM(kso),0), COALESCE(SUM(menejemen),0),
                       0, COALESCE(SUM(biaya_rawat),0)
                FROM rawat_inap_pr WHERE tgl_perawatan BETWEEN ? AND ?
                UNION ALL
                SELECT 'Rawat Inap Dokter & Perawat', COUNT(*),
                       COALESCE(SUM(material),0), COALESCE(SUM(bhp),0),
                       COALESCE(SUM(tarif_tindakandr),0), COALESCE(SUM(tarif_tindakanpr),0),
                       COALESCE(SUM(kso),0), COALESCE(SUM(menejemen),0),
                       0, COALESCE(SUM(biaya_rawat),0)
                FROM rawat_inap_drpr WHERE tgl_perawatan BETWEEN ? AND ?
                UNION ALL
                SELECT 'Obat & BHP Pasien', COUNT(*),
                       0, 0, 0, 0, 0, 0,
                       COALESCE(SUM(total),0), COALESCE(SUM(total),0)
                FROM detail_pemberian_obat WHERE tgl_perawatan BETWEEN ? AND ?
            ) pemasukan
            GROUP BY sumber
            ORDER BY sumber
        ";

        $params = [];
        for ($i = 0; $i < 7; $i++) {
            $params[] = $start;
            $params[] = $end;
        }

        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $total = [
            'transaksi' => 0,
            'material' => 0,
            'bhp' => 0,
            'jasa_dokter' => 0,
            'jasa_perawat' => 0,
            'kso' => 0,
            'manajemen' => 0,
            'obat' => 0,
            'total_semua' => 0,
        ];

        foreach ($rows as $row) {
            $total['transaksi'] += (int) $row['transaksi'];
            $total['material'] += (float) $row['material'];
            $total['bhp'] += (float) $row['bhp'];
            $total['jasa_dokter'] += (float) $row['jasa_dokter'];
            $total['jasa_perawat'] += (float) $row['jasa_perawat'];
            $total['kso'] += (float) $row['kso'];
            $total['manajemen'] += (float) $row['manajemen'];
            $total['obat'] += (float) $row['obat'];
            $total['total_semua'] += (float) $row['total'];
        }

        return [
            'list' => $rows,
            'total' => $total,
            'total_semua' => $total['total_semua'],
        ];
    }

    private function _getPengeluaranSummary($start, $end)
    {
        $stmt = $this->db()->pdo()->prepare("SELECT kategori, COUNT(*) AS transaksi, COALESCE(SUM(jumlah),0) AS total FROM catatan_keuangan_pengeluaran WHERE tanggal BETWEEN :start AND :end GROUP BY kategori ORDER BY kategori");
        $stmt->execute([
            ':start' => $start,
            ':end' => $end,
        ]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $total = 0;
        foreach ($rows as $row) {
            $total += (float) $row['total'];
        }

        return [
            'list' => $rows,
            'total' => $total,
        ];
    }

    private function _adminToken()
    {
        return isset_or($_SESSION['token'], '');
    }

    private function _getDateRange()
    {
        return [
            'start' => $_GET['start'] ?? date('Y-m-01'),
            'end' => $_GET['end'] ?? date('Y-m-d'),
        ];
    }

    private function _kategoriPengeluaran()
    {
        return [
            'Belanja Obat dan BHP',
            'Operasional',
            'Gaji / Honor',
            'Pemeliharaan',
            'Lain-lain',
        ];
    }

    private function _ensureTables()
    {
        $this->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `catatan_keuangan_pengeluaran` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `tanggal` date NOT NULL,
          `kategori` varchar(100) NOT NULL,
          `keterangan` text,
          `jumlah` double NOT NULL DEFAULT 0,
          `petugas` varchar(100) DEFAULT '-',
          `created_at` datetime NOT NULL,
          PRIMARY KEY (`id`),
          KEY `tanggal` (`tanggal`),
          KEY `kategori` (`kategori`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    private function _addHeaderFiles()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'), 'footer');
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'), 'footer');
    }
}
