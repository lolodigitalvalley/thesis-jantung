<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Visualisasi extends Pakar_Controller 
{

    protected $page_header = 'Self Organizing Map';
    protected $metode      = 'som';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array('gejala_model' => 'gejala', 'bobot_gejala_model' => 'bobot_gejala', 'basis_kasus_model' => 'basis_kasus', 'kasus_detail_model' => 'kasus_detail', 'klaster_model' => 'klaster', 'pusat_klaster_model' => 'pusat_klaster', 'inisiasi_klaster_model' => 'inisiasi_klaster', 'centroids_som_model' => 'centroid'));
        $this->load->library('cluster_lib');
    }

    public function index()
    {
        $data['page_header']   = $this->page_header;
        $data['breadcrumb']    = $this->page_header;
        $data['panel_heading'] = 'Inisiasi SOM';
        $data['page']          = 'index';
        $this->frontend->view('som_v', $data);
    }

    public function training()
    {

        $gejala = $this->gejala->fields('kode_gejala')->get_all();

        $query        = $this->bobot_gejala->get_all();
        $bobot_gejala = array();
        foreach ($query as $row) {
            $bobot_gejala[$row->kode_gejala][$row->kode_penyakit] = $row->bobot;
        }

        $query = $this->basis_kasus->with_kasus_detail('fields:kode_gejala')->get_all();

        $records = array();
        $primary = array();
        $i       = 0;
        foreach ($query as $row) {
            $primary[$i] = $row->kode_kasus;
            if ($row->jk == '1') {
                $records[0][$i] = 1;
                $records[1][$i] = 0;
            } else {
                $records[0][$i] = 0;
                $records[1][$i] = 1;
            }
            $records[2][$i] = $this->norm_age($row->usia);

            $kasus_detail = array();
            if (!empty($row->kasus_detail)) {
                foreach ($row->kasus_detail as $value) {
                    $kasus_detail[] = $value->kode_gejala;
                }
            }

            $j = 3;
            foreach ($gejala as $value) {
                if ($j!=29) $records[$j++][$i] = (in_array($value->kode_gejala, $kasus_detail)) ? $bobot_gejala[$value->kode_gejala][$row->kode_penyakit] : 0;
                if($j==30) break;
            }
            $i++;
        }
        //print_r($records); exit;
        $p = new PCA\PCA($records);
        $p->changeDimension(2);
        $p->applayingPca();

        print_r($p->getPC());
        //print_r($p->getNewData());
        print_r($p->getSimilarity());
        exit;
        $data['page_header']    = $this->page_header;
        $data['page'] = 'training';

        $this->frontend->view('som_v', $data);
    }

    public function save_training()
    {
        $clusters   = unserialize($this->input->post('clusters'));
        $centroids  = unserialize($this->input->post('centroids'));
        $weights    = unserialize($this->input->post('weights'));
        $gejala     = unserialize($this->input->post('gejala'));
        $maxIterasi = unserialize($this->input->post('maxIterasi'));

        $learningRate   = unserialize($this->input->post('learningRate'));
        $toLearningRate = unserialize($this->input->post('toLearningRate'));
        $silhoutteIndex = unserialize($this->input->post('silhoutteIndex'));

        $n = count($clusters);

        $query = $this->klaster->delete(array('metode' => $this->metode));

        $query = $this->pusat_klaster->delete(array('metode' => $this->metode));

        for ($i = 0; $i < $n; $i++) {
            $rows = array();
            foreach ($clusters[$i] as $value) {
                $rows[] = array('kode_kasus' => $value, 'metode' => $this->metode, 'klaster' => ($i + 1));
            }

            $this->klaster->insert($rows);

            $rows    = array();
            $rows[0] = array('klaster' => ($i + 1), 'no' => 1, 'metode' => $this->metode, 'atribut' => 'lk', 'bobot' => $centroids[$i][0]);
            $rows[1] = array('klaster' => ($i + 1), 'no' => 2, 'metode' => $this->metode, 'atribut' => 'pr', 'bobot' => $centroids[$i][1]);
            $rows[2] = array('klaster' => ($i + 1), 'no' => 3, 'metode' => $this->metode, 'atribut' => 'usia', 'bobot' => $centroids[$i][2]);
            $j       = 3;
            foreach ($gejala as $value) {
                $rows[$j] = array('klaster' => ($i + 1), 'no' => $j + 1, 'metode' => $this->metode, 'atribut' => $value->kode_gejala, 'bobot' => $centroids[$i][$j++]);
            }

            $this->pusat_klaster->insert($rows);
        }

        $this->inisiasi_klaster->update(array('jumlah_klaster' => count($clusters), 'max_iterasi' => $maxIterasi, 'learning_rate' => $learningRate, 'to_learning_rate' => $toLearningRate, 'silhoutte_index' => $silhoutteIndex), array('metode' => $this->metode));
        
        echo array("status" => true);
    }

    public function lihat_hasil_som($centroids, $numCluster)
    {
        $gejala = $this->gejala->fields('kode_gejala')->get_all();

        if (count($centroids) <= 0) {
            return false;
        }

        $table = '<table class="table table-striped table-bordered table-hover"><tr><th>Klaster</th><th>Lk</th><th>Pr</th><th>Usia</th>';
        foreach ($gejala as $value) {
            $table .= '<th>' . $value->kode_gejala . '</th>';
        }
        $table .= '</tr>';

        for ($a = 0; $a < $numCluster; $a++) {
            $table .= '<tr><td width="7px"><b>C ' . ($a + 1) . '</b></td>';
            for ($b = 0; $b < count($centroids[0]); $b++) {
                $table .= '<td>' . round($centroids[$a][$b], 5) . '</td>';
            }

            $table .= '</tr>';
        }
        $table .= '</table>';

        return $table;
    }

//SOM Bagus Kalo Dibagi 100
    private function norm_age($usia)
    {
        return ($usia / 100);
    }


}
