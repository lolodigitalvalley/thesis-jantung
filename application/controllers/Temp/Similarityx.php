<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Similarity extends CI_Controller
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
        $data['panel_heading']    = 'Input New Case';
        $data['page']             = 'index';
        $data['centroids_som']    = $this->get_centroids_som();
        $data['centroids_dbscan'] = $this->get_centroids_dbscan();

        $data['gejala']   = $this->gejala->fields('kode_gejala, nama_gejala')->get_all();
        $data['penyakit'] = $this->penyakit->fields('kode_penyakit')->get_all();

        $query        = $this->bobot_gejala->fields('kode_gejala, kode_penyakit, bobot')->get_all();
        $bobot_gejala = array();
        foreach ($query as $row) {
            $bobot_gejala[$row->kode_gejala][$row->kode_penyakit] = $row->bobot;
        }
        $data['bobot_gejala'] = $bobot_gejala;
        $this->frontend->view('similarity_v', $data);
    }

    public function retrive_non_indexing()
    {
        $this->benchmark->mark('code_start');

        $indexing = $this->input->post('indexing');
        $sim      = $this->input->post('similarity');
        $sex      = ($this->input->post('sex') == 'L') ? 1 : 2;

        $age         = $this->input->post('age');
        $gejala      = $this->gejala->fields('kode_gejala, nama_gejala')->get_all();
        $gejala_baru = $this->input->post('gejala_baru');

        for ($i = 1, $index = array(); $i <= count($gejala_baru); $i++) {
            $index[$i] = (int) substr($gejala_baru[$i - 1], 1);
        }

        $newCase    = array();
        $newCase[0] = $sex;
        $newCase[1] = $age;

        for ($i = 2, $j = 1; $i < 57; $i++) {
            $newCase[$i] = (in_array($j, $index)) ? 1 : 0;
            $j++;
        }

        $query        = $this->bobot_gejala->fields('kode_gejala, kode_penyakit, bobot')->get_all();
        $bobot_gejala = array();
        foreach ($query as $row) {
            $bobot_gejala[$row->kode_gejala][$row->kode_penyakit] = $row->bobot;
        }

        $query = $this->basis_kasus->fields('kode_kasus, jk, usia, kode_penyakit')->with_kasus_detail('fields:kode_gejala')->get_all();

        $oldCase     = array();
        $basis_kasus = array();
        $jst         = array();
        $i           = 0;
        foreach ($query as $row) {
            $basis_kasus[$i][0] = $row->kode_kasus;
            $basis_kasus[$i][1] = $row->kode_penyakit;

            $basis_kasus[$i][2] = $oldCase[$i][0] = $row->jk;

            $basis_kasus[$i][3] = $row->usia;
            $oldCase[$i][1]     = $row->usia;

            $kasus_detail = array();
            $jst[$i]      = 2;
            if (!empty($row->kasus_detail)) {
                foreach ($row->kasus_detail as $value) {
                    $jst[$i]        = (in_array($value->kode_gejala, $gejala_baru)) ? ($jst[$i] + 1) : ($jst[$i] + 0);
                    $kasus_detail[] = $value->kode_gejala;
                }
            }

            $j = 2;
            foreach ($gejala as $value) {
                $basis_kasus[$i][$j + 2] = $oldCase[$i][$j++] = (in_array($value->kode_gejala, $kasus_detail)) ? $bobot_gejala[$value->kode_gejala][$row->kode_penyakit] : 0;
            }

            $i++;
        }
        $jt = count($gejala_baru) + 2;

        $similarity      = new Nearestneighbors($oldCase, $newCase, $jst, $jt, 1, $sim);
        $index           = $similarity->get_nn();
        $nearestCase     = $query[$index]->kode_kasus;
        $nearestDistance = $similarity->get_distance();

        $this->benchmark->mark('code_end');

        echo json_encode(array('nearestCluster' => 'Non Clustered', 'nearestCase' => $nearestCase, 'row' => $basis_kasus[$index], 'indexing' => $indexing, 'point' => $nearestDistance, 'elapsed_time' => $this->benchmark->elapsed_time('code_start', 'code_end'), 'memory_usage' => "{memory_usage}"));
    }

    public function retrive_with_indexing()
    {
        $this->benchmark->mark('code_start');

        $sim      = $this->input->post('similarity');
        $indexing = $this->input->post('indexing');
        $centroids = ($indexing == 'som') ? unserialize($this->input->post('centroids_som')) : unserialize($this->input->post('centroids_dbscan'));
        //$centroids = ($indexing == 'som') ? $this->get_centroids_som() : $this->get_centroids_dbscan();
        $sex       = ($this->input->post('sex') == 'L') ? 1 : 2;
        $age       = $this->input->post('age');

        $gejala_baru  = $this->input->post('gejala_baru');
        $gejala       = unserialize($this->input->post('gejala'));
        $bobot_gejala = unserialize($this->input->post('bobot_gejala'));
        $penyakit     = unserialize($this->input->post('penyakit'));
        $npenyakit           = count($penyakit);

        for ($i = 1, $index = array(); $i <= count($gejala_baru); $i++) {
            $index[$i] = (int) substr($gejala_baru[$i - 1], 1);
        }

        $query        = $this->bobot_gejala->fields('kode_gejala, kode_penyakit, bobot')->get_all();
        $bobot_gejala = array();
        foreach ($query as $row) {
            $bobot_gejala[$row->kode_gejala][$row->kode_penyakit] = $row->bobot;
        }

        $newCase       = array();
        $newCase[0][0] = $sex;
        $newCase[0][1] = $age;

        if ($sex == 1) {
            for ($i = 1; $i <= $npenyakit; $i++) {
                $newCase[$i][0] = 4;
                $newCase[$i][1] = 0;
                $newCase[$i][2] = $this->norm_age($age);
            }
        } else {
            for ($i = 1; $i <= $npenyakit; $i++) {
                $newCase[$i][0] = 0;
                $newCase[$i][1] = 4;
                $newCase[$i][2] = $this->norm_age($age);
            }
        }

        for ($g = 0; $g < count($gejala); $g++) {
            if (in_array($g + 1, $index)) {
                $newCase[0][$g + 2] = 1;
                for ($i = 1; $i <= $npenyakit; $i++) {
                    $newCase[$i][$g + 3] = $bobot_gejala[$gejala[$g]->kode_gejala][$penyakit[$i - 1]->kode_penyakit];
                }

            } else {
                $newCase[0][$g + 2] = 0;
                for ($i = 1; $i <= $npenyakit; $i++) {
                    $newCase[$i][$g + 3] = 0;
                }

            }
        }

        $knn             = new knn($centroids, $newCase[1], 1, 'cosinus');
        $nearestCluster  = $knn->get_nn() + 1;
        $nearestDistance = $knn->get_distance();
        for ($i = 2; $i <= $npenyakit; $i++) {
            $knn = new knn($centroids, $newCase[$i], 1, 'cosinus');
            if ($knn->get_distance() < $nearestDistance) {
                $nearestCluster  = $knn->get_nn() + 1;
                $nearestDistance = $knn->get_distance();
            }
        }

        $query = $this->basis_kasus->fields('kode_kasus, jk, usia, kode_penyakit')->with_kasus_detail('fields:kode_gejala')->with_klaster('fields:klaster|where:teknik = "' . $indexing . '" AND klaster = ' . $nearestCluster)->get_all();

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
            $jst[$i]      = 2;
            if (!empty($row->kasus_detail)) {
                foreach ($row->kasus_detail as $value) {
                    $jst[$i]        = (in_array($value->kode_gejala, $gejala_baru)) ? ($jst[$i] + 1) : ($jst[$i] + 0);
                    $kasus_detail[] = $value->kode_gejala;
                }
            }

            $j = 2;
            foreach ($gejala as $value) {
                $basis_kasus[$i][$j + 2] = $oldCase[$i][$j++] = (in_array($value->kode_gejala, $kasus_detail)) ? $bobot_gejala[$value->kode_gejala][$row->kode_penyakit] : 0;
            }
            $i++;
        }
        $jt = count($gejala_baru) + 2;

        $similarity      = new Nearestneighbors($oldCase, $newCase[0], $jst, $jt, 1, $sim);
        $index           = $similarity->get_nn();
        $nearestCase     = $basis_kasus[$index][0];
        $nearestDistance = $similarity->get_distance();

        $this->benchmark->mark('code_end');
        $json = array('nearestCluster' => $nearestCluster, 'nearestCase' => $nearestCase, 'row' => $basis_kasus[$index], 'indexing' => $indexing, 'point' => $nearestDistance);

        $json['elapsed_time'] = $this->benchmark->elapsed_time('code_start', 'code_end');

        echo json_encode($json);
    }

    private function get_centroids_som()
    {
        $nCluster  = $this->pusat_klaster->group_by('klaster')->count_rows('teknik', 'som');
        $centroids = array();
        for ($i = 0; $i < $nCluster; $i++) {
            $query = $this->pusat_klaster->where(array('teknik' => 'som', 'klaster' => ($i + 1)))->order_by('no')->get_all();
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
        $nCluster  = $this->pusat_klaster->group_by('klaster')->count_rows('teknik', 'dbscan');
        $centroids = array();
        for ($i = 0; $i < $nCluster; $i++) {
            $query = $this->pusat_klaster->where(array('teknik' => 'dbscan', 'klaster' => ($i + 1)))->order_by('no')->get_all();
            if ($query) {
                foreach ($query as $row) {
                    $centroids[$i][] = $row->bobot;
                }
            }

        }

        return $centroids;
    }

    private function norm_age($age)
    {
        return (100 - $age) / 10;
    }
}
