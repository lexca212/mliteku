<?php

return [
    'name'          =>  'Catatan Keuangan',
    'description'   =>  'Catatan pemasukan berdasarkan komponen tarif dan pengeluaran operasional.',
    'author'        =>  'mLITE',
    'category'      =>  'keuangan',
    'version'       =>  '1.0',
    'compatibility' =>  '6.*.*',
    'icon'          =>  'money',
    'install'       =>  function () use ($core) {
        $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `catatan_keuangan_pengeluaran` (
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
    },
    'uninstall'     =>  function () use ($core) {
    }
];
