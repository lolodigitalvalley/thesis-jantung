<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Similarity extends Paramedis_Controller
{

    protected $page_header = 'Similarity';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array('gejala_model' => 'gejala', 'penyakit_model' => 'penyakit', 'bobot_gejala_model' => 'bobot_gejala', 'basis_kasus_model' => 'basis_kasus', 'kasus_detail_model' => 'kasus_detail', 'klaster_model' => 'klaster', 'pusat_klaster_model' => 'pusat_klaster'));
        $this->load->library('cluster_lib');
    }

    public function index()
    {
        $data['page_header']      = $this->page_header;
        $data['breadcrumb']       = $this->page_header;
        $data['panel_heading']    = 'Masukan Kasus Baru';
        $data['page']             = 'index';
        $data['centroids_som']    = $this->get_centroids_som();
        $data['centroids_dbscan'] = $this->get_centroids_dbscan();

        $data['gejala']   = $this->gejala->fields('kode_gejala, nama_gejala')->get_all();
        $data['penyakit'] = $this->penyakit->fields('kode_penyakit')->get_all();

        $this->frontend->view('similarity_v', $data);
    }

    public function retrive_non_indexing()
    {
        $this->benchmark->mark('code_start');

        $indexing = $this->input->post('indexing');
        $sim      = $this->input->post('similarity');
        $jk       = $this->input->post('jk');

        $usia        = $this->input->post('usia');
        $gejala      = unserialize($this->input->post('gejala'));
        $gejala_baru = $this->input->post('gejala_baru');

        for ($i = 1, $index = array(); $i <= count($gejala_baru); $i++) {
            $index[$i] = (int) substr($gejala_baru[$i - 1], 1);
        }

        $newCase    = array();
        $newCase[0] = $jk;
        $newCase[1] = $usia;

        for ($g = 0; $g < count($gejala); $g++) {
            $newCase[$g + 2] = (in_array($g + 1, $index)) ? 1 : 0;
        }

        $query        = $this->bobot_gejala->fields('kode_gejala, kode_penyakit, bobot')->get_all();
        $bobot_gejala = array();
        foreach ($query as $row) {
            $bobot_gejala[$row->kode_gejala][$row->kode_penyakit] = $row->bobot;
        }

        $query = $this->basis_kasus->fields('kode_kasus, jk, usia, kode_penyakit')->with_kasus_detail('fields:kode_gejala')->get_all();

        $oldCase     = array();
        $basis_kasus = array();
        $i           = 0;
        foreach ($query as $row) {
            $basis_kasus[$i][0] = $row->kode_kasus;
            $basis_kasus[$i][1] = $row->kode_penyakit;
            $basis_kasus[$i][2] = $row->jk;
            $basis_kasus[$i][3] = $row->usia;

            $oldCase[$i][0] = $row->jk;
            $oldCase[$i][1] = $row->usia;

            $kasus_detail = array();
            if (!empty($row->kasus_detail)) {
                foreach ($row->kasus_detail as $value) {
                    $kasus_detail[] = $value->kode_gejala;
                }
            }

            $j = 2;
            foreach ($gejala as $value) {
                $basis_kasus[$i][$j + 2] = $oldCase[$i][$j++] = (in_array($value->kode_gejala, $kasus_detail)) ? $bobot_gejala[$value->kode_gejala][$row->kode_penyakit] : 0;
            }
            $i++;
        }

        $similarity      = new Nearestneighbors($oldCase, $newCase, 1, $sim);
        $index           = $similarity->get_nn();
        $nearestCase     = $query[$index]->kode_kasus;
        $nearestDistance = $similarity->get_distance();

        $this->benchmark->mark('code_end');

        echo json_encode(array('nearestCluster' => 'Non Clustered', 'nearestCase' => $nearestCase, 'row' => $basis_kasus[$index], 'indexing' => $indexing, 'point' => $nearestDistance, 'elapsed_time' => $this->benchmark->elapsed_time('code_start', 'code_end'), 'memory_usage' => "{memory_usage}"));
    }

    public function retrive_with_indexing()
    {
        $sim      = $this->input->post('similarity');
        $indexing = $this->input->post('indexing');
        //$centroids = ($indexing == 'som') ? unserialize($this->input->post('centroids_som')) : unserialize($this->input->post('centroids_dbscan'));
        $centroids = ($indexing == 'som') ? $this->get_centroids_som() : $this->get_centroids_dbscan();

        $this->benchmark->mark('code_start');
        $jk   = $this->input->post('jk');
        $usia = $this->input->post('usia');

        $gejala      = unserialize($this->input->post('gejala'));
        $gejala_baru = $this->input->post('gejala_baru');

        $penyakit = unserialize($this->input->post('penyakit'));

        for ($i = 1, $index = array(); $i <= count($gejala_baru); $i++) {
            $index[$i] = (int) substr($gejala_baru[$i - 1], 1);
        }

        $query        = $this->bobot_gejala->fields('kode_gejala, kode_penyakit, bobot')->get_all();
        $bobot_gejala = array();
        foreach ($query as $row) {
            $bobot_gejala[$row->kode_gejala][$row->kode_penyakit] = $row->bobot;
        }

        $newCase       = array();
        $newCase[0][0] = $jk;
        $newCase[0][1] = $usia;

        if ($jk == 1) {
            $newCase[1][0] = 1;
            $newCase[1][1] = 0;
        } else {
            $newCase[1][0] = 0;
            $newCase[1][1] = 1;
        }

        $newCase[1][2] = $this->norm_usia($usia);

        for ($g = 0; $g < count($gejala); $g++) {
            if (in_array($g + 1, $index)) {
                $newCase[0][$g + 2] = 1;
                $newCase[1][$g + 3] = 1;
            } else {
                $newCase[0][$g + 2] = 0;
                $newCase[1][$g + 3] = 0;
            }
        }

        $knn = new knn($centroids, $newCase[1], 1, 'cosinus');
        //$knn             = new knn($centroids, $newCase[1], 1);
        $nearestDistance = $knn->get_distance();
        $nearestCluster  = ($indexing == 'som') ? $knn->get_nn() + 1 : $knn->get_nn();

        $query = $this->basis_kasus->fields('kode_kasus, jk, usia, kode_penyakit')
            ->with_kasus_detail('fields:kode_gejala')
            ->with_klaster('fields:klaster|where:metode = "' . $indexing . '" AND klaster = ' . $nearestCluster)
            ->get_all();

        $oldCase     = array();
        $basis_kasus = array();
        $jst         = array();
        $i           = 0;
        foreach ($query as $row) {
            $basis_kasus[$i][0] = $row->kode_kasus;
            $basis_kasus[$i][1] = $row->kode_penyakit;
            $basis_kasus[$i][2] = $row->jk;
            $basis_kasus[$i][3] = $row->usia;

            $oldCase[$i][0] = $row->jk;
            $oldCase[$i][1] = $row->usia;

            $kasus_detail = array();
            if (!empty($row->kasus_detail)) {
                foreach ($row->kasus_detail as $value) {                    
                    $kasus_detail[] = $value->kode_gejala;
                }
            }

            $j = 2;
            foreach ($gejala as $value) {
                $basis_kasus[$i][$j + 2] = $oldCase[$i][$j++] = (in_array($value->kode_gejala, $kasus_detail)) ? $bobot_gejala[$value->kode_gejala][$row->kode_penyakit] : 0;
            }
            $i++;
        }

        $similarity      = new Nearestneighbors($oldCase, $newCase[0], 1, $sim);
        $index           = $similarity->get_nn();
        $nearestCase     = $basis_kasus[$index][0];
        $nearestDistance = $similarity->get_distance();

        $json = array('nearestCluster' => $nearestCluster, 'nearestCase' => $nearestCase, 'row' => $basis_kasus[$index], 'indexing' => $indexing, 'point' => $nearestDistance);
        $this->benchmark->mark('code_end');

        $json['elapsed_time'] = $this->benchmark->elapsed_time('code_start', 'code_end');

        echo json_encode($json);
    }

    private function get_centroids_som()
    {
        $nCluster  = $this->pusat_klaster->group_by('klaster')->count_rows('metode', 'som');
        $centroids = array();
        for ($i = 0; $i < $nCluster; $i++) {
            $query = $this->pusat_klaster->where(array('metode' => 'som', 'klaster' => ($i + 1)))->order_by('no')->get_all();
            if ($query) {
                foreach ($query as $row) {
                    $centroids[$i][] = $row->bobot;
                }
            }
        }
        return $centroids;
    }

    private function get_centroids_dbscan()
    {
        $nCluster  = $this->pusat_klaster->group_by('klaster')->count_rows('metode', 'dbscan');
        $centroids = array();
        for ($i = 0; $i <= $nCluster; $i++) {
            $query = $this->pusat_klaster->where(array('metode' => 'dbscan', 'klaster' => $i))->order_by('no')->get_all();
            if ($query) {
                foreach ($query as $row) {
                    $centroids[$i][] = $row->bobot;
                }
            }
        }
        return $centroids;
    }

    public function save_kasus()
    {
        $this->load->model(array('diagnosis_model' => 'diagnosis', 'diagnosis_detail_model' => 'diagnosis_detail'));
        $error = array('result' => 0);

        $kode_kasus = $this->diagnosis->get_max_id();
        if ($kode_kasus == null) {
            $kode_kasus = $this->basis_kasus->get_max_id();
        }

        $id         = substr($kode_kasus, 1) + 1;
        $kode_kasus = 'K' . $id;
        //$kode_penyakit = $this->input->post('kode_penyakit');
        $usia   = $this->input->post('usia');
        $jk     = $this->input->post('jk');
        $gejala = $this->input->post('gejala_baru');

        $row    = array('kode_kasus' => $kode_kasus, 'kode_penyakit' => '', 'usia' => $usia, 'jk' => $jk);
        $result = $this->diagnosis->insert($row);

        $insert_many = array();
        foreach ($gejala as $value) {
            $insert_many[] = array('kode_kasus' => $kode_kasus, 'kode_gejala' => $value);
        }

        $result = $this->diagnosis_detail->insert($insert_many);

        echo json_encode(array('result' => 1));
        exit;
    }

    private function norm_usia($usia)
    {
        $max_usia    = $this->basis_kasus->get_max()->max_usia;
        $min_usia    = $this->basis_kasus->get_min()->min_usia;

        return ($usia - $min_usia) / ($max_usia - $min_usia);
    }
}
