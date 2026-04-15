<?php

return [
    'name'          =>  'Farmasi',
    'description'   =>  'Pengelolaan data gudang farmasi.',
    'author'        =>  'Basoro',
    'category'      =>  'farmasi', 
    'version'       =>  '1.1',
    'compatibility' =>  '6.*.*',
    'icon'          =>  'medkit',
    'install'       =>  function () use ($core) {
        $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `farmasi_pengajuan_obat` (
          `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
          `tanggal_pengajuan` date NOT NULL,
          `kode_brng` varchar(15) NOT NULL,
          `jumlah` int(11) NOT NULL DEFAULT 0,
          `status` varchar(20) NOT NULL DEFAULT 'Menunggu',
          `catatan` text,
          `dibuat_oleh` varchar(100) DEFAULT '-',
          `created_at` datetime NOT NULL
        )");
        $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `farmasi_pemesanan_obat` (
          `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
          `pengajuan_id` int(11) NOT NULL,
          `tanggal_pemesanan` date NOT NULL,
          `supplier` varchar(150) NOT NULL,
          `jumlah_pesan` int(11) NOT NULL DEFAULT 0,
          `status_pemesanan` varchar(20) NOT NULL DEFAULT 'Draft',
          `catatan` text,
          `dibuat_oleh` varchar(100) DEFAULT '-',
          `created_at` datetime NOT NULL,
          CONSTRAINT `fk_farmasi_pemesanan_pengajuan` FOREIGN KEY (`pengajuan_id`) REFERENCES `farmasi_pengajuan_obat`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
        )");
        $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `farmasi_penerimaan_obat` (
          `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
          `pemesanan_id` int(11) NOT NULL,
          `tanggal_penerimaan` date NOT NULL,
          `jumlah_terima` int(11) NOT NULL DEFAULT 0,
          `jenis_pembayaran` varchar(10) NOT NULL DEFAULT 'Cash',
          `tanggal_jatuh_tempo` date DEFAULT NULL,
          `nomor_faktur` varchar(100) DEFAULT NULL,
          `catatan` text,
          `dibuat_oleh` varchar(100) DEFAULT '-',
          `created_at` datetime NOT NULL,
          CONSTRAINT `fk_farmasi_penerimaan_pemesanan` FOREIGN KEY (`pemesanan_id`) REFERENCES `farmasi_pemesanan_obat`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
        )");
        $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('farmasi', 'deporalan', '-')");
        $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('farmasi', 'igd', '-')");
        $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('farmasi', 'deporanap', '-')");
        $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('farmasi', 'gudang', '-')");
        $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('farmasi', 'keterangan_etiket', '')");
        $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('farmasi', 'embalase', '')");
        $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('farmasi', 'tuslah', '')");
    },
    'uninstall'     =>  function () use ($core) {
        $core->db()->pdo()->exec("DELETE FROM `mlite_settings` WHERE `module` = 'farmasi'");
    }
];
