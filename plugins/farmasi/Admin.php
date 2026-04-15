<?php

namespace Plugins\Farmasi;

use Systems\AdminModule;

class Admin extends AdminModule
{
    public $assign = [];

    public function navigation()
    {
        return [
            'Kelola' => 'manage',
            'Mutasi Obat & BHP' => 'mutasi',
            'Pengajuan Obat' => 'pengajuanobat',
            'Pemesanan Obat' => 'pemesananobat',
            'Penerimaan Obat' => 'penerimaanobat',
            'Stok Opname' => 'opname',
            'Darurat Stok' => 'daruratstok',
            'Detail Pemberian Obat' => 'detailpemberianobat',
            'Riwayat Barang Medis' => 'riwayatbarangmedis',
            'Pengaturan' => 'settings',
        ];
    }

    public function getManage()
    {
      $sub_modules = [
        ['name' => 'Mutasi Obat & BHP', 'url' => url([ADMIN, 'farmasi', 'mutasi']), 'icon' => 'medkit', 'desc' => 'Data obat dan barang habis pakai'],
        ['name' => 'Pengajuan Obat', 'url' => url([ADMIN, 'farmasi', 'pengajuanobat']), 'icon' => 'file-text', 'desc' => 'Pengajuan kebutuhan obat oleh farmasi'],
        ['name' => 'Pemesanan Obat', 'url' => url([ADMIN, 'farmasi', 'pemesananobat']), 'icon' => 'shopping-cart', 'desc' => 'Pemesanan obat berdasarkan pengajuan yang disetujui'],
        ['name' => 'Penerimaan Obat', 'url' => url([ADMIN, 'farmasi', 'penerimaanobat']), 'icon' => 'archive', 'desc' => 'Penerimaan obat cash / tempo dari pemesanan'],
        ['name' => 'Stok Opname', 'url' => url([ADMIN, 'farmasi', 'opname']), 'icon' => 'medkit', 'desc' => 'Tambah stok opname'],
        ['name' => 'Darurat Stok', 'url' => url([ADMIN, 'farmasi', 'daruratstok']), 'icon' => 'warning', 'desc' => 'Monitoring stok darurat obat dan BHP'],
        ['name' => 'Detail Pemberian Obat', 'url' => url([ADMIN, 'farmasi', 'detailpemberianobat']), 'icon' => 'medkit', 'desc' => 'Detail pemberian obat pasien'],
        ['name' => 'Riwayat Barang Medis', 'url' => url([ADMIN, 'farmasi', 'riwayatbarangmedis']), 'icon' => 'medkit', 'desc' => 'Riwayat pergerakan barang medis'],
        ['name' => 'Pengaturan', 'url' => url([ADMIN, 'farmasi', 'settings']), 'icon' => 'medkit', 'desc' => 'Pengaturan farmasi dan depo'],
      ];
      return $this->draw('manage.html', ['sub_modules' => htmlspecialchars_array($sub_modules)]);
    }

    public function getMutasi($status = '1')
    {
        $this->_addHeaderFiles();
        $databarang['title'] = 'Kelola Mutasi Obat';
        $databarang['bangsal']  = $this->db('bangsal')->toArray();
        $databarang['list'] = $this->_databarangList($status);
        return $this->draw('mutasi.html', ['databarang' => htmlspecialchars_array($databarang), 'tab' => $status]);
    }

    private function _databarangList($status)
    {
        $result = [];

        foreach ($this->db('databarang')->where('status', $status)->toArray() as $row) {
            $row['delURL']  = url([ADMIN, 'farmasi', 'delete', $row['kode_brng']]);
            $row['restoreURL']  = url([ADMIN, 'farmasi', 'restore', $row['kode_brng']]);
            $row['gudangbarang'] = $this->db('gudangbarang')->join('bangsal', 'bangsal.kd_bangsal=gudangbarang.kd_bangsal')->where('kode_brng', $row['kode_brng'])->toArray();
            $result[] = $row;
        }
        return $result;
    }

    public function getDelete($id)
    {
        if ($this->db('databarang')->where('kode_brng', $id)->update('status', '0')) {
            $this->notify('success', 'Hapus sukses');
        } else {
            $this->notify('failure', 'Hapus gagal');
        }
        redirect(url([ADMIN, 'farmasi', 'mutasi']));
    }

    public function getRestore($id)
    {
        if ($this->db('databarang')->where('kode_brng', $id)->update('status', '1')) {
            $this->notify('success', 'Restore sukses');
        } else {
            $this->notify('failure', 'Restore gagal');
        }
        redirect(url([ADMIN, 'farmasi', 'mutasi']));
    }

    public function postSetStok()
    {
      $databarang = $this->db('databarang')->where('kode_brng', $_POST['kode_brng'])->oneArray();

      if($this->db('gudangbarang')->where('kode_brng', $_POST['kode_brng'])->where('kd_bangsal', $_POST['kd_bangsal'])->oneArray()) {

        $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $_POST['kode_brng'])->where('kd_bangsal', $this->settings->get('farmasi.gudang'))->oneArray();
        $gudangbarang = $this->db('gudangbarang')->where('kode_brng', $_POST['kode_brng'])->where('kd_bangsal', $_POST['kd_bangsal'])->oneArray();

        if($_POST['kd_bangsal'] == $this->settings->get('farmasi.gudang')) {
          $query = $this->db('riwayat_barang_medis')
            ->save([
              'kode_brng' => $_POST['kode_brng'],
              'stok_awal' => $get_gudangbarang['stok'],
              'masuk' => $_POST['stok'],
              'keluar' => '0',
              'stok_akhir' => $get_gudangbarang['stok'] + $_POST['stok'],
              'posisi' => 'Pengadaan',
              'tanggal' => date('Y-m-d'),
              'jam' => date('H:i:s'),
              'petugas' => $this->core->getUserInfo('fullname', null, true),
              'kd_bangsal' => $this->settings->get('farmasi.gudang'),
              'status' => 'Simpan',
              'no_batch' => '0',
              'no_faktur' => '0',
              'keterangan' => '-'
            ]);
            if($query) {
              $query2 = $this->db('gudangbarang')
                ->where('kode_brng', $_POST['kode_brng'])
                ->where('kd_bangsal', $this->settings->get('farmasi.gudang'))
                ->save([
                  'stok' => $get_gudangbarang['stok'] + $_POST['stok']
              ]);
            }
        } else {

          $query = $this->db('riwayat_barang_medis')
            ->save([
              'kode_brng' => $_POST['kode_brng'],
              'stok_awal' => $get_gudangbarang['stok'],
              'masuk' => '0',
              'keluar' => $_POST['stok'],
              'stok_akhir' => $get_gudangbarang['stok'] - $_POST['stok'],
              'posisi' => 'Mutasi',
              'tanggal' => date('Y-m-d'),
              'jam' => date('H:i:s'),
              'petugas' => $this->core->getUserInfo('fullname', null, true),
              'kd_bangsal' => $this->settings->get('farmasi.gudang'),
              'status' => 'Simpan',
              'no_batch' => '0',
              'no_faktur' => '0',
              'keterangan' => '-'
            ]);

          $query2 = $this->db('riwayat_barang_medis')
            ->save([
              'kode_brng' => $_POST['kode_brng'],
              'stok_awal' => $gudangbarang['stok'],
              'masuk' => $_POST['stok'],
              'keluar' => '0',
              'stok_akhir' => $gudangbarang['stok'] + $_POST['stok'],
              'posisi' => 'Mutasi',
              'tanggal' => date('Y-m-d'),
              'jam' => date('H:i:s'),
              'petugas' => $this->core->getUserInfo('fullname', null, true),
              'kd_bangsal' => $_POST['kd_bangsal'],
              'status' => 'Simpan',
              'no_batch' => '0',
              'no_faktur' => '0',
              'keterangan' => '-'
            ]);
        }

        if($query) {
          $this->db('gudangbarang')
            ->where('kode_brng', $_POST['kode_brng'])
            ->where('kd_bangsal', $this->settings->get('farmasi.gudang'))
            ->save([
              'stok' => $get_gudangbarang['stok'] - $_POST['stok']
          ]);
        }
        if($query2) {
          $this->db('gudangbarang')
            ->where('kode_brng', $_POST['kode_brng'])
            ->where('kd_bangsal', $_POST['kd_bangsal'])
            ->save([
              'stok' => $gudangbarang['stok'] + $_POST['stok']
          ]);
        }

        $this->db('mutasibarang')->save([
          'kode_brng' => $_POST['kode_brng'],
          'jml' => $_POST['stok'],
          'harga' => $_POST['harga'] ?? $databarang['dasar'],
          'kd_bangsaldari' => $this->settings->get('farmasi.gudang'),
          'kd_bangsalke' => $_POST['kd_bangsal'],
          'tanggal' => date('Y-m-d H:i:s'),
          'keterangan' => $_POST['keterangan'] ?? 'Set Stok - Mutasi',
          'no_batch' => $_POST['no_batch'] ?? '0',
          'no_faktur' => $_POST['no_faktur'] ?? '0'
        ]);

      } else {

        $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $_POST['kode_brng'])->where('kd_bangsal', $this->settings->get('farmasi.gudang'))->oneArray();
        $stok = '0';
        if($get_gudangbarang) {
          $stok = $get_gudangbarang['stok'];
        }
        if($_POST['kd_bangsal'] == $this->settings->get('farmasi.gudang')) {
          $query = $this->db('riwayat_barang_medis')
            ->save([
              'kode_brng' => $_POST['kode_brng'],
              'stok_awal' => '0',
              'masuk' => $_POST['stok'],
              'keluar' => '0',
              'stok_akhir' => $_POST['stok'],
              'posisi' => 'Pengadaan',
              'tanggal' => date('Y-m-d'),
              'jam' => date('H:i:s'),
              'petugas' => $this->core->getUserInfo('fullname', null, true),
              'kd_bangsal' => $this->settings->get('farmasi.gudang'),
              'status' => 'Simpan',
              'no_batch' => '0',
              'no_faktur' => '0',
              'keterangan' => '222'
            ]);
            if($query) {
              $this->db('gudangbarang')->save([
                'kode_brng' => $_POST['kode_brng'],
                'kd_bangsal' => $this->settings->get('farmasi.gudang'),
                'stok' => $_POST['stok'],
                'no_batch' => '0',
                'no_faktur' => '0'
              ]);
            }

        } else {

          $query = $this->db('riwayat_barang_medis')
            ->save([
              'kode_brng' => $_POST['kode_brng'],
              'stok_awal' => $stok,
              'masuk' => '0',
              'keluar' => $_POST['stok'],
              'stok_akhir' => $stok - $_POST['stok'],
              'posisi' => 'Mutasi',
              'tanggal' => date('Y-m-d'),
              'jam' => date('H:i:s'),
              'petugas' => $this->core->getUserInfo('fullname', null, true),
              'kd_bangsal' => $this->settings->get('farmasi.gudang'),
              'status' => 'Simpan',
              'no_batch' => '0',
              'no_faktur' => '0',
              'keterangan' => '-'
            ]);

          $query2 = $this->db('riwayat_barang_medis')
            ->save([
              'kode_brng' => $_POST['kode_brng'],
              'stok_awal' => '0',
              'masuk' => $_POST['stok'],
              'keluar' => '0',
              'stok_akhir' => $_POST['stok'],
              'posisi' => 'Mutasi',
              'tanggal' => date('Y-m-d'),
              'jam' => date('H:i:s'),
              'petugas' => $this->core->getUserInfo('fullname', null, true),
              'kd_bangsal' => $_POST['kd_bangsal'],
              'status' => 'Simpan',
              'no_batch' => '0',
              'no_faktur' => '0',
              'keterangan' => ''
            ]);
          if($query) {
            $this->db('gudangbarang')
              ->where('kode_brng', $_POST['kode_brng'])
              ->where('kd_bangsal', $this->settings->get('farmasi.gudang'))
              ->save([
                'stok' => $get_gudangbarang['stok'] - $_POST['stok']
            ]);
          }
          if($query2) {
            $this->db('gudangbarang')->save([
              'kode_brng' => $_POST['kode_brng'],
              'kd_bangsal' => $_POST['kd_bangsal'],
              'stok' => $_POST['stok'],
              'no_batch' => '0',
              'no_faktur' => '0'
            ]);
          }

          $this->db('mutasibarang')->save([
            'kode_brng' => $_POST['kode_brng'],
            'jml' => $_POST['stok'],
            'harga' => $_POST['harga'] ?? $databarang['dasar'],
            'kd_bangsaldari' => $this->settings->get('farmasi.gudang'),
            'kd_bangsalke' => $_POST['kd_bangsal'],
            'tanggal' => date('Y-m-d H:i:s'),
            'keterangan' => $_POST['keterangan'] ?? 'Set Stok - Mutasi',
            'no_batch' => $_POST['no_batch'] ?? '0',
            'no_faktur' => $_POST['no_faktur'] ?? '0'
          ]);

        }
      }

      exit();
    }

    public function postReStok()
    {

      $databarang = $this->db('databarang')->where('kode_brng', $_POST['kode_brng'])->oneArray();

      $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $_POST['kode_brng'])->where('kd_bangsal', $this->settings->get('farmasi.gudang'))->oneArray();
      $gudangbarang = $this->db('gudangbarang')->where('kode_brng', $_POST['kode_brng'])->where('kd_bangsal', $_POST['kd_bangsal'])->oneArray();

      $query = $this->db('riwayat_barang_medis')
        ->save([
          'kode_brng' => $_POST['kode_brng'],
          'stok_awal' => $get_gudangbarang['stok'],
          'masuk' => $_POST['stok'],
          'keluar' => '0',
          'stok_akhir' => $get_gudangbarang['stok'] + $_POST['stok'],
          'posisi' => 'Mutasi',
          'tanggal' => date('Y-m-d'),
          'jam' => date('H:i:s'),
          'petugas' => $this->core->getUserInfo('fullname', null, true),
          'kd_bangsal' => $this->settings->get('farmasi.gudang'),
          'status' => 'Simpan',
          'no_batch' => '0',
          'no_faktur' => '0',
          'keterangan' => ''
        ]);

      if($query) {
        $this->db('gudangbarang')
          ->where('kode_brng', $_POST['kode_brng'])
          ->where('kd_bangsal', $this->settings->get('farmasi.gudang'))
          ->save([
            'stok' => $get_gudangbarang['stok'] + $_POST['stok']
        ]);
      }

      $query2 = $this->db('riwayat_barang_medis')
        ->save([
          'kode_brng' => $_POST['kode_brng'],
          'stok_awal' => $gudangbarang['stok'],
          'masuk' => '0',
          'keluar' => $_POST['stok'],
          'stok_akhir' => $gudangbarang['stok'] - $_POST['stok'],
          'posisi' => 'Mutasi',
          'tanggal' => date('Y-m-d'),
          'jam' => date('H:i:s'),
          'petugas' => $this->core->getUserInfo('fullname', null, true),
          'kd_bangsal' => $_POST['kd_bangsal'],
          'status' => 'Simpan',
          'no_batch' => '0',
          'no_faktur' => '0',
          'keterangan' => ''
        ]);

      if($query2) {
        $this->db('gudangbarang')
          ->where('kode_brng', $_POST['kode_brng'])
          ->where('kd_bangsal', $_POST['kd_bangsal'])
          ->save([
            'stok' => $gudangbarang['stok'] - $_POST['stok']
        ]);
      }      

      $this->db('mutasibarang')->save([
        'kode_brng' => $_POST['kode_brng'],
        'jml' => $_POST['stok'],
        'harga' => $_POST['harga'] ?? $databarang['dasar'],
        'kd_bangsaldari' => $this->settings->get('farmasi.gudang'),
        'kd_bangsalke' => $_POST['kd_bangsal'],
        'tanggal' => date('Y-m-d H:i:s'),
        'keterangan' => $_POST['keterangan'] ?? 'Set restok - Mutasi',
        'no_batch' => $_POST['no_batch'] ?? '0',
        'no_faktur' => $_POST['no_faktur'] ?? '0'
      ]);

      exit();
    }

    /* End Databarang Section */

    public function getOpname($data='')
    {
      $this->_addHeaderFiles();
      if($data == 'data') {
        return $this->draw('opname.data.html');
      } else {
        return $this->draw('opname.html');
      }
    }

    public function postOpnameAll()
    {
      $gudangbarang = $this->db('gudangbarang')
        ->join('databarang', 'databarang.kode_brng=gudangbarang.kode_brng')
        ->join('bangsal', 'bangsal.kd_bangsal=gudangbarang.kd_bangsal')
        ->where('databarang.status', '1')
        ->toJson();
      echo $gudangbarang;
      exit();
    }

    public function postOpnameData()
    {
      $opname = $this->db('opname')
        ->join('databarang', 'databarang.kode_brng=opname.kode_brng')
        ->join('bangsal', 'bangsal.kd_bangsal=opname.kd_bangsal')
        ->where('databarang.status', '1')
        ->toJson();
      echo $opname;
      exit();
    }

    public function postOpnameUpdate()
    {
      $kode_brng =$_POST['kode_brng'];
      $real = $_POST['real'];
      $stok = $_POST['stok'];
      $kd_bangsal = $_POST['kd_bangsal'];
      $tanggal = $_POST['tanggal'];
      $h_beli = $_POST['h_beli'];
      $keterangan = $_POST['keterangan'];
      $no_batch = $_POST['no_batch'];
      $no_faktur = $_POST['no_faktur'];
      for($count = 0; $count < count($kode_brng); $count++){
        $selisih = $real[$count] - $stok[$count];
        $nomihilang = $selisih * $h_beli[$count];
        $lebih = 0;
        $nomilebih = 0;
        if($selisih < 0) {
          $selisih = 0;
          $nomihilang = 0;
          $lebih = $stok[$count] - $real[$count];
          $nomilebih = $lebih * $h_beli[$count];
        }

        $query2 = "INSERT INTO `opname` (`kode_brng`, `h_beli`, `tanggal`, `stok`, `real`, `selisih`, `nomihilang`, `lebih`, `nomilebih`, `keterangan`, `kd_bangsal`, `no_batch`, `no_faktur`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $opname2 = $this->db()->pdo()->prepare($query2);
        $opname2->execute([$kode_brng[$count], $h_beli[$count], $tanggal[$count], $real[$count], $stok[$count], $selisih, $nomihilang, $lebih, $nomilebih, $keterangan[$count], $kd_bangsal[$count], $no_batch[$count], $no_faktur[$count]]);

        if ($opname2->errorInfo()[2] == ''){
          $query = "UPDATE gudangbarang SET stok=?, no_batch=?, no_faktur=? WHERE kode_brng=? AND kd_bangsal=?";
          $opname = $this->db()->pdo()->prepare($query);              
          $opname->execute([$real[$count], $no_batch[$count], $no_faktur[$count], $kode_brng[$count], $kd_bangsal[$count]]);
          $keluar = '0';
          $masuk = '0';
          if($real[$count]>$stok[$count]) {
          $masuk = $real[$count]-$stok[$count];
          }
          if($real[$count]<$stok[$count]) {
          $keluar = $stok[$count]-$real[$count];
          }
          $this->db('riwayat_barang_medis')
          ->save([
            'kode_brng' => $kode_brng[$count],
            'stok_awal' => $stok[$count],
            'masuk' => $masuk,
            'keluar' => $keluar,
            'stok_akhir' => $real[$count],
            'posisi' => 'Opname',
            'tanggal' => $tanggal[$count],
            'jam' => date('H:i:s'),
            'petugas' => $this->core->getUserInfo('fullname', null, true),
            'kd_bangsal' => $kd_bangsal[$count],
            'status' => 'Simpan',
            'no_batch' => $no_batch[$count],
            'no_faktur' => $no_faktur[$count],
            'keterangan' => $keterangan[$count]
          ]);   
                    
          $data = array(
            'status' => 'success', 
            'msg' => $this->db('databarang')->select('nama_brng')->where('kode_brng', $kode_brng[$count])->oneArray()['nama_brng'], 
            'info' => htmlspecialchars(json_encode($opname2->errorInfo()[2]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
          );

        } else {
          $data = array(
            'status' => 'error', 
            'msg' => $this->db('databarang')->select('nama_brng')->where('kode_brng', $kode_brng[$count])->oneArray()['nama_brng'], 
            'info' => htmlspecialchars(json_encode($opname2->errorInfo()[2]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
          );
        }
        echo json_encode(htmlspecialchars_array($data));   

      }
      exit();
    }

    /* Settings Farmasi Section */
    public function getSettings()
    {
        $this->assign['title'] = 'Pengaturan Modul Farmasi';
        $this->assign['bangsal'] = $this->db('bangsal')->toArray();
        $this->assign['farmasi'] = htmlspecialchars_array($this->settings('farmasi'));
        return $this->draw('settings.html', ['settings' => htmlspecialchars_array($this->assign)]);
    }

    public function postSaveSettings()
    {
        foreach ($_POST['farmasi'] as $key => $val) {
            $this->settings('farmasi', $key, $val);
        }
        $this->notify('success', 'Pengaturan telah disimpan');
        redirect(url([ADMIN, 'farmasi', 'settings']));
    }
    /* End Settings Farmasi Section */

    /* Detail Pemberian Obat Section */
    public function getDetailpemberianobat()
    {
        $this->_addHeaderFiles();
        $this->core->addJS(url([ADMIN, 'farmasi', 'detailpemberianobatjs']), 'footer');
        return $this->draw('detailpemberianobat.html');
    }

    public function postDetailpemberianobatData()
    {
        $draw = $_POST['draw'];
        $row1 = $_POST['start'];
        $rowperpage = $_POST['length']; // Rows display per page
        $columnIndex = $_POST['order'][0]['column']; // Column index
        $columnName = $_POST['columns'][$columnIndex]['data']; // Column name
        $columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
        $searchValue = $_POST['search']['value']; // Search value

        ## Custom Field value
        $search_field_detail_pemberian_obat= $_POST['search_field_detail_pemberian_obat'];
        $search_text_detail_pemberian_obat = $_POST['search_text_detail_pemberian_obat'];

        $allowed_fields = ['no_rawat', 'kode_brng', 'tgl_perawatan', 'jam', 'kd_bangsal', 'no_batch', 'no_faktur'];
        if (!in_array($search_field_detail_pemberian_obat, $allowed_fields)) {
            $search_field_detail_pemberian_obat = 'no_rawat';
        }

        $allowed_sort = ['asc', 'desc'];
        if (!in_array(strtolower($columnSortOrder), $allowed_sort)) {
            $columnSortOrder = 'asc';
        }
        if (!in_array($columnName, $allowed_fields)) {
            $columnName = 'no_rawat';
        }

        $tgl_awal = isset_or($_POST['tgl_awal'], date('Y-m-d'));
        $tgl_akhir = isset_or($_POST['tgl_akhir'], date('Y-m-d'));

        $searchQuery = " ";
        $params = [];
        if($search_text_detail_pemberian_obat != ''){
            $searchQuery .= " and (".$search_field_detail_pemberian_obat." like ? ) ";
            $params[] = "%".$search_text_detail_pemberian_obat."%";
        }

        $searchQuery .= " and (tgl_perawatan between ? and ?) ";
        $params[] = $tgl_awal;
        $params[] = $tgl_akhir;

        ## Total number of records without filtering
        $sel = $this->db()->pdo()->prepare("select count(*) as allcount from detail_pemberian_obat");
        $sel->execute();
        $records = $sel->fetch();
        $totalRecords = $records['allcount'];

        ## Total number of records with filtering
        $sel = $this->db()->pdo()->prepare("select count(*) as allcount from detail_pemberian_obat WHERE 1 ".$searchQuery);
        $sel->execute($params);
        $records = $sel->fetch();
        $totalRecordwithFilter = $records['allcount'];

        ## Fetch records
        $sel = $this->db()->pdo()->prepare("select * from detail_pemberian_obat WHERE 1 ".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".(int)$row1.",".(int)$rowperpage);
        $sel->execute($params);
        $result = $sel->fetchAll(\PDO::FETCH_ASSOC);

        $data = array();
        foreach($result as $row) {
            $databarang = $this->db('databarang')->select('nama_brng')->where('kode_brng', $row['kode_brng'])->oneArray();
            $bangsal = $this->db('bangsal')->select('nm_bangsal')->where('kd_bangsal', $row['kd_bangsal'])->oneArray();
            $no_rkm_medis = $this->core->getRegPeriksaInfo('no_rkm_medis', $row['no_rawat']);
            $data[] = array(
                'tgl_perawatan'=>$row['tgl_perawatan'],
                'jam'=>$row['jam'],
                'no_rkm_medis' => $no_rkm_medis,
                'nm_pasien' => $this->core->getPasienInfo('nm_pasien', $no_rkm_medis),
                'no_rawat'=>$row['no_rawat'],
                'kode_brng'=>$row['kode_brng'],
                'nama_brng'=>$databarang['nama_brng'],
                'h_beli'=>$row['h_beli'],
                'biaya_obat'=>$row['biaya_obat'],
                'jml'=>$row['jml'],
                'embalase'=>$row['embalase'],
                'tuslah'=>$row['tuslah'],
                'total'=>$row['total'],
                'status'=>$row['status'],
                'kd_bangsal'=>$row['kd_bangsal'],
                'nm_bangsal'=>$bangsal['nm_bangsal'],
                'no_batch'=>$row['no_batch'],
                'no_faktur'=>$row['no_faktur']
            );
        }

        ## Response
        $response = array(
            "draw" => intval(htmlspecialchars($_POST['draw'] ?? 0, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data
        );

        echo json_encode(htmlspecialchars_array($response));
        exit();
    }

    public function getDetailpemberianobatJS()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/farmasi/js/admin/detailpemberianobat.js');
        exit();
    }
    /* End Detail Pemberian Obat Section */

    /* Riwayat Barang Medis Section */
    public function getRiwayatbarangmedis()
    {
        $this->_addHeaderFiles();
        $this->core->addJS(url([ADMIN, 'farmasi', 'riwayatbarangmedisjs']), 'footer');
        return $this->draw('riwayatbarangmedis.html');
    }

    public function postRiwayatbarangmedisData()
    {
        $draw = $_POST['draw'];
        $row1 = $_POST['start'];
        $rowperpage = $_POST['length']; // Rows display per page
        $columnIndex = $_POST['order'][0]['column']; // Column index
        $columnName = $_POST['columns'][$columnIndex]['data']; // Column name
        $columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
        $searchValue = $_POST['search']['value']; // Search value

        ## Custom Field value
        $search_field_riwayat_barang_medis= $_POST['search_field_riwayat_barang_medis'];
        $search_text_riwayat_barang_medis = $_POST['search_text_riwayat_barang_medis'];

        $allowed_fields = ['kode_brng', 'stok_awal', 'masuk', 'keluar', 'stok_akhir', 'posisi', 'tanggal', 'jam', 'petugas', 'kd_bangsal', 'status', 'no_batch', 'no_faktur', 'keterangan'];
        if (!in_array($search_field_riwayat_barang_medis, $allowed_fields)) {
            $search_field_riwayat_barang_medis = 'kode_brng';
        }

        $allowed_sort = ['asc', 'desc'];
        if (!in_array(strtolower($columnSortOrder), $allowed_sort)) {
            $columnSortOrder = 'asc';
        }
        if (!in_array($columnName, $allowed_fields)) {
            $columnName = 'kode_brng';
        }

        $tgl_awal = isset_or($_POST['tgl_awal'], date('Y-m-d'));
        $tgl_akhir = isset_or($_POST['tgl_akhir'], date('Y-m-d'));

        $searchQuery = " ";
        $params = [];
        if($search_text_riwayat_barang_medis != ''){
            $searchQuery .= " and (".$search_field_riwayat_barang_medis." like ? ) ";
            $params[] = "%".$search_text_riwayat_barang_medis."%";
        }

        $searchQuery .= " and (tanggal between ? and ?) ";
        $params[] = $tgl_awal;
        $params[] = $tgl_akhir;

        ## Total number of records without filtering
        $sel = $this->db()->pdo()->prepare("select count(*) as allcount from riwayat_barang_medis");
        $sel->execute();
        $records = $sel->fetch();
        $totalRecords = $records['allcount'];

        ## Total number of records with filtering
        $sel = $this->db()->pdo()->prepare("select count(*) as allcount from riwayat_barang_medis WHERE 1 ".$searchQuery);
        $sel->execute($params);
        $records = $sel->fetch();
        $totalRecordwithFilter = $records['allcount'];

        ## Fetch records
        $sel = $this->db()->pdo()->prepare("select * from riwayat_barang_medis WHERE 1 ".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".(int)$row1.",".(int)$rowperpage);
        $sel->execute($params);
        $result = $sel->fetchAll(\PDO::FETCH_ASSOC);

        $data = array();
        foreach($result as $row) {
            $databarang = $this->db('databarang')->select('nama_brng')->where('kode_brng', $row['kode_brng'])->oneArray();
            $bangsal = $this->db('bangsal')->select('nm_bangsal')->where('kd_bangsal', $row['kd_bangsal'])->oneArray();
            $data[] = array(
                'kode_brng'=>$row['kode_brng'],
                'nama_brng'=>$databarang['nama_brng'],
                'stok_awal'=>$row['stok_awal'],
                'masuk'=>$row['masuk'],
                'keluar'=>$row['keluar'],
                'stok_akhir'=>$row['stok_akhir'],
                'posisi'=>$row['posisi'],
                'tanggal'=>$row['tanggal'],
                'jam'=>$row['jam'],
                'petugas'=>$row['petugas'],
                'kd_bangsal'=>$row['kd_bangsal'],
                'nm_bangsal'=>$bangsal['nm_bangsal'],
                'status'=>$row['status'],
                'no_batch'=>$row['no_batch'],
                'no_faktur'=>$row['no_faktur'],
                'keterangan'=>$row['keterangan']
            );
        }

        ## Response
        $response = array(
            "draw" => intval(htmlspecialchars($_POST['draw'] ?? 0, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data
        );

        echo json_encode(htmlspecialchars_array($response));
        exit();
    }

    public function getRiwayatbarangmedisJS()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/farmasi/js/admin/riwayatbarangmedis.js');
        exit();
    }
    /* End Riwayat Barang Medis Section */

    /* Darurat Stok Section */
    public function getDaruratStok()
    {
        $this->_addHeaderFiles();
        $this->core->addJS(url([ADMIN, 'farmasi', 'daruratstokjs']), 'footer');
        return $this->draw('daruratstok.html');
    }

    public function postDaruratStokData()
    {
        try {
            $draw           = $_POST['draw'] ?? 1;
            $row1           = $_POST['start'] ?? 0;
            $rowperpage     = $_POST['length'] ?? 10;
            $columnIndex    = $_POST['order'][0]['column'] ?? 0;
            $columnName     = $_POST['columns'][$columnIndex]['data'] ?? 'kode_brng';
            $columnSortOrder= $_POST['order'][0]['dir'] ?? 'asc';
            $search_text    = $_POST['search_text_databarang'] ?? '';
            $search_field   = $_POST['search_field_databarang'] ?? '';

            // Validasi: mencegah SQL Injection via column name
            $allowedColumns = [
                'kode_brng','nama_brng','stokminimal','kode_satbesar',
                'kode_sat','dasar','h_beli','isi','kapasitas','expire'
            ];

            if (!in_array($columnName, $allowedColumns)) {
                $columnName = 'kode_brng';
            }

            // Build search query
            $searchQuery = "";
            $params = [];

            if ($search_text !== '' && in_array($search_field, $allowedColumns)) {
                $searchQuery = " AND d.$search_field LIKE :search_text ";
                $params[':search_text'] = "%$search_text%";
            }

            // -------------------------
            // Hitung total records
            // -------------------------
            $sqlTotal = "SELECT COUNT(*) AS allcount FROM databarang";
            $stmt = $this->db()->pdo()->prepare($sqlTotal);
            $stmt->execute();
            $totalRecords = $stmt->fetch()['allcount'];

            // -------------------------
            // Hitung total filtered
            // -------------------------
            $sqlFiltered = "SELECT COUNT(*) AS allcount FROM databarang d WHERE 1 $searchQuery";
            $stmt = $this->db()->pdo()->prepare($sqlFiltered);
            if(!empty($params)){
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            $totalRecordwithFilter = $stmt->fetch()['allcount'];

            // -------------------------
            // Ambil data JOIN stok gudang (1 kali query saja)
            // -------------------------
            // Use LIMIT length OFFSET start for better compatibility
            $sqlData = "
                SELECT d.kode_brng, d.nama_brng, d.stokminimal, d.kode_satbesar,
                      d.kode_sat, d.dasar, d.h_beli, d.isi, d.kapasitas, d.expire,
                      COALESCE(SUM(g.stok), 0) AS stok
                FROM databarang d
                LEFT JOIN gudangbarang g ON g.kode_brng = d.kode_brng
                WHERE 1 $searchQuery
                GROUP BY d.kode_brng
                ORDER BY d.$columnName $columnSortOrder
                LIMIT :length OFFSET :start
            ";

            $stmt = $this->db()->pdo()->prepare($sqlData);

            // bind parameter
            if(!empty($params)){
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
            }

            $stmt->bindValue(":start", intval($row1), \PDO::PARAM_INT);
            $stmt->bindValue(":length", intval($rowperpage), \PDO::PARAM_INT);

            $stmt->execute();
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $data = [];
            foreach ($result as $row) {
                $data[] = $row;
            }

            // -------------------------
            // Response JSON
            // -------------------------
            $response = [
                "draw" => intval(htmlspecialchars($draw ?? $_POST['draw'] ?? 1, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
                "recordsTotal" => intval($totalRecords),
                "recordsFiltered" => intval($totalRecordwithFilter),
                "data" => $data
            ];
        } catch (\Exception $e) {
            $response = [
                "draw" => intval(htmlspecialchars($_POST['draw'] ?? 1, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => [],
                "error" => htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            ];
        }

        header('Content-Type: application/json');
        echo json_encode(htmlspecialchars_array($response));
        exit();
    }

    public function getDaruratStokJS()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/farmasi/js/admin/daruratstok.js');
        exit();
    }
    /* End Darurat Stok Section */

    /* Pengadaan Obat Section */
    public function getPengajuanObat()
    {
        $this->_addHeaderFiles();
        $this->_ensurePengadaanTables();

        $rows = $this->db()->pdo()->query("
            SELECT po.*, db.nama_brng
            FROM farmasi_pengajuan_obat po
            LEFT JOIN databarang db ON db.kode_brng = po.kode_brng
            ORDER BY po.id DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        return $this->draw('pengajuanobat.html', [
            'title' => 'Pengajuan Kebutuhan Obat',
            'items' => htmlspecialchars_array($this->db('databarang')->where('status', '1')->toArray()),
            'list' => htmlspecialchars_array($rows),
        ]);
    }

    public function postSavePengajuanObat()
    {
        $this->_ensurePengadaanTables();
        if (checkEmptyFields(['tanggal_pengajuan', 'kode_brng', 'jumlah'], $_POST)) {
            $this->notify('failure', 'Isian pengajuan wajib diisi.');
            redirect(url([ADMIN, 'farmasi', 'pengajuanobat']));
        }

        $payload = [
            'tanggal_pengajuan' => $_POST['tanggal_pengajuan'],
            'kode_brng' => $_POST['kode_brng'],
            'jumlah' => (int) $_POST['jumlah'],
            'status' => $_POST['status'] ?? 'Menunggu',
            'catatan' => $_POST['catatan'] ?? '',
            'dibuat_oleh' => $this->core->getUserInfo('fullname', null, true),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $query = $this->db('farmasi_pengajuan_obat')->save($payload);
        $this->notify($query ? 'success' : 'failure', $query ? 'Pengajuan obat berhasil disimpan.' : 'Pengajuan obat gagal disimpan.');
        redirect(url([ADMIN, 'farmasi', 'pengajuanobat']));
    }

    public function getPemesananObat()
    {
        $this->_addHeaderFiles();
        $this->_ensurePengadaanTables();

        $pengajuan = $this->db()->pdo()->query("
            SELECT po.id, po.tanggal_pengajuan, po.kode_brng, po.jumlah, po.status, db.nama_brng
            FROM farmasi_pengajuan_obat po
            LEFT JOIN databarang db ON db.kode_brng = po.kode_brng
            WHERE po.status = 'Disetujui'
            ORDER BY po.id DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $rows = $this->db()->pdo()->query("
            SELECT pmo.*, po.kode_brng, po.jumlah AS jumlah_pengajuan, db.nama_brng
            FROM farmasi_pemesanan_obat pmo
            LEFT JOIN farmasi_pengajuan_obat po ON po.id = pmo.pengajuan_id
            LEFT JOIN databarang db ON db.kode_brng = po.kode_brng
            ORDER BY pmo.id DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        return $this->draw('pemesananobat.html', [
            'title' => 'Pemesanan Obat',
            'pengajuan' => htmlspecialchars_array($pengajuan),
            'list' => htmlspecialchars_array($rows),
        ]);
    }

    public function postSavePemesananObat()
    {
        $this->_ensurePengadaanTables();
        if (checkEmptyFields(['pengajuan_id', 'tanggal_pemesanan', 'supplier', 'jumlah_pesan'], $_POST)) {
            $this->notify('failure', 'Isian pemesanan wajib diisi.');
            redirect(url([ADMIN, 'farmasi', 'pemesananobat']));
        }

        $query = $this->db('farmasi_pemesanan_obat')->save([
            'pengajuan_id' => (int) $_POST['pengajuan_id'],
            'tanggal_pemesanan' => $_POST['tanggal_pemesanan'],
            'supplier' => $_POST['supplier'],
            'jumlah_pesan' => (int) $_POST['jumlah_pesan'],
            'status_pemesanan' => $_POST['status_pemesanan'] ?? 'Dipesan',
            'catatan' => $_POST['catatan'] ?? '',
            'dibuat_oleh' => $this->core->getUserInfo('fullname', null, true),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->notify($query ? 'success' : 'failure', $query ? 'Pemesanan obat berhasil disimpan.' : 'Pemesanan obat gagal disimpan.');
        redirect(url([ADMIN, 'farmasi', 'pemesananobat']));
    }

    public function getPenerimaanObat()
    {
        $this->_addHeaderFiles();
        $this->_ensurePengadaanTables();

        $pemesanan = $this->db()->pdo()->query("
            SELECT pmo.id, pmo.tanggal_pemesanan, pmo.jumlah_pesan, pmo.supplier, po.kode_brng, db.nama_brng
            FROM farmasi_pemesanan_obat pmo
            LEFT JOIN farmasi_pengajuan_obat po ON po.id = pmo.pengajuan_id
            LEFT JOIN databarang db ON db.kode_brng = po.kode_brng
            WHERE pmo.status_pemesanan IN ('Dipesan', 'Selesai')
            ORDER BY pmo.id DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $rows = $this->db()->pdo()->query("
            SELECT pto.*, pmo.supplier, pmo.jumlah_pesan, po.kode_brng, db.nama_brng
            FROM farmasi_penerimaan_obat pto
            LEFT JOIN farmasi_pemesanan_obat pmo ON pmo.id = pto.pemesanan_id
            LEFT JOIN farmasi_pengajuan_obat po ON po.id = pmo.pengajuan_id
            LEFT JOIN databarang db ON db.kode_brng = po.kode_brng
            ORDER BY pto.id DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        return $this->draw('penerimaanobat.html', [
            'title' => 'Penerimaan Obat',
            'pemesanan' => htmlspecialchars_array($pemesanan),
            'list' => htmlspecialchars_array($rows),
        ]);
    }

    public function postSavePenerimaanObat()
    {
        $this->_ensurePengadaanTables();
        if (checkEmptyFields(['pemesanan_id', 'tanggal_penerimaan', 'jumlah_terima', 'jenis_pembayaran'], $_POST)) {
            $this->notify('failure', 'Isian penerimaan wajib diisi.');
            redirect(url([ADMIN, 'farmasi', 'penerimaanobat']));
        }

        $pemesanan = $this->db()->pdo()->prepare("
            SELECT pmo.*, po.kode_brng
            FROM farmasi_pemesanan_obat pmo
            LEFT JOIN farmasi_pengajuan_obat po ON po.id = pmo.pengajuan_id
            WHERE pmo.id = :id
            LIMIT 1
        ");
        $pemesanan->execute([':id' => (int) $_POST['pemesanan_id']]);
        $pemesanan = $pemesanan->fetch(\PDO::FETCH_ASSOC);

        if (empty($pemesanan)) {
            $this->notify('failure', 'Data pemesanan tidak ditemukan.');
            redirect(url([ADMIN, 'farmasi', 'penerimaanobat']));
        }

        $jatuhTempo = null;
        if (($_POST['jenis_pembayaran'] ?? 'Cash') === 'Tempo' && !empty($_POST['tanggal_jatuh_tempo'])) {
            $jatuhTempo = $_POST['tanggal_jatuh_tempo'];
        }

        $query = $this->db('farmasi_penerimaan_obat')->save([
            'pemesanan_id' => (int) $_POST['pemesanan_id'],
            'tanggal_penerimaan' => $_POST['tanggal_penerimaan'],
            'jumlah_terima' => (int) $_POST['jumlah_terima'],
            'jenis_pembayaran' => $_POST['jenis_pembayaran'],
            'tanggal_jatuh_tempo' => $jatuhTempo,
            'nomor_faktur' => $_POST['nomor_faktur'] ?? '',
            'catatan' => $_POST['catatan'] ?? '',
            'dibuat_oleh' => $this->core->getUserInfo('fullname', null, true),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($query) {
            $this->db('farmasi_pemesanan_obat')
                ->where('id', (int) $_POST['pemesanan_id'])
                ->update('status_pemesanan', 'Selesai');

            $gudang = $this->settings->get('farmasi.gudang');
            $kodeBrng = $pemesanan['kode_brng'] ?? '';
            if (!empty($gudang) && $gudang !== '-' && !empty($kodeBrng)) {
                $stokGudang = $this->db('gudangbarang')
                    ->where('kode_brng', $kodeBrng)
                    ->where('kd_bangsal', $gudang)
                    ->oneArray();
                $stokAwal = (int) ($stokGudang['stok'] ?? 0);
                $stokAkhir = $stokAwal + (int) $_POST['jumlah_terima'];

                $this->db('riwayat_barang_medis')->save([
                    'kode_brng' => $kodeBrng,
                    'stok_awal' => $stokAwal,
                    'masuk' => (int) $_POST['jumlah_terima'],
                    'keluar' => 0,
                    'stok_akhir' => $stokAkhir,
                    'posisi' => 'Penerimaan',
                    'tanggal' => date('Y-m-d'),
                    'jam' => date('H:i:s'),
                    'petugas' => $this->core->getUserInfo('fullname', null, true),
                    'kd_bangsal' => $gudang,
                    'status' => 'Simpan',
                    'no_batch' => '0',
                    'no_faktur' => $_POST['nomor_faktur'] ?? '0',
                    'keterangan' => 'Penerimaan obat dari supplier',
                ]);

                if ($stokGudang) {
                    $this->db('gudangbarang')
                        ->where('kode_brng', $kodeBrng)
                        ->where('kd_bangsal', $gudang)
                        ->update('stok', $stokAkhir);
                } else {
                    $this->db('gudangbarang')->save([
                        'kode_brng' => $kodeBrng,
                        'kd_bangsal' => $gudang,
                        'stok' => $stokAkhir,
                        'no_batch' => '0',
                        'no_faktur' => $_POST['nomor_faktur'] ?? '0',
                    ]);
                }
            }
        }

        $this->notify($query ? 'success' : 'failure', $query ? 'Penerimaan obat berhasil disimpan.' : 'Penerimaan obat gagal disimpan.');
        redirect(url([ADMIN, 'farmasi', 'penerimaanobat']));
    }

    private function _ensurePengadaanTables()
    {
        $this->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `farmasi_pengajuan_obat` (
          `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
          `tanggal_pengajuan` date NOT NULL,
          `kode_brng` varchar(15) NOT NULL,
          `jumlah` int(11) NOT NULL DEFAULT 0,
          `status` varchar(20) NOT NULL DEFAULT 'Menunggu',
          `catatan` text,
          `dibuat_oleh` varchar(100) DEFAULT '-',
          `created_at` datetime NOT NULL
        )");
        $this->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `farmasi_pemesanan_obat` (
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
        $this->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `farmasi_penerimaan_obat` (
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
    }
    /* End Pengadaan Obat Section */

    public function getCSS()
    {
        header('Content-type: text/css');
        echo $this->draw(MODULES.'/farmasi/css/admin/farmasi.css');
        exit();
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/farmasi/js/admin/farmasi.js');
        exit();
    }

    private function _addHeaderFiles()
    {
        // CSS
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));

        // JS
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'), 'footer');
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'), 'footer');

        $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
        $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
        $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));

        // MODULE SCRIPTS
        $this->core->addCSS(url([ADMIN, 'farmasi', 'css']));
        $this->core->addJS(url([ADMIN, 'farmasi', 'javascript']), 'footer');
    }

}
