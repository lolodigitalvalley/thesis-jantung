<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dbscan_learning extends Pakar_Controller
{

    protected $page_header = 'DBSCAN';
    protected $metode      = 'dbscan';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array('gejala_model' => 'gejala', 'bobot_gejala_model' => 'bobot_gejala', 'basis_kasus_model' => 'basis_kasus', 'kasus_detail_model' => 'kasus_detail', 'klaster_model' => 'klaster', 'pusat_klaster_model' => 'pusat_klaster', 'inisiasi_klaster_model' => 'inisiasi_klaster'));
        $this->load->library('cluster_lib');
    }

    public function index()
    {
        $data['page_header']   = $this->page_header;
        $data['breadcrumb']    = $this->page_header;
        $data['panel_heading'] = 'Inisiasi DBSCAN';
        $data['page']          = 'index';
        $this->frontend->view('dbscan_v', $data);
    }

    public function training()
    {
        $epsilon   = $this->input->post('epsilon');
        $minPoints = $this->input->post('minPoints');

        if (empty($epsilon)) {
            $this->session->set_flashdata('error', 'You must fill epsilon');
            redirect(site_url('dbscan_learning'), 'refresh');
        }
        /*
        if(empty($minPoints)) $minPoints = 10;
         */

        //Training With epsilon 13 AND Minimum Points 3
        //Training With epsilon 13.5 AND Minimum Points 3
        //Jika Umur / 100 P005 salah, jika umur dibagi / 10 P005 benar
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
                $records[$i][0] = 1;
                $records[$i][1] = 0;
            } else {
                $records[$i][0] = 0;
                $records[$i][1] = 1;
            }
            $records[$i][2] = $this->norm_usia($row->usia);

            $kasus_detail = array();
            if (!empty($row->kasus_detail)) {
                foreach ($row->kasus_detail as $value) {
                    $kasus_detail[] = $value->kode_gejala;
                }
            }

            $j = 3;
            foreach ($gejala as $value) {
                $records[$i][$j++] = (in_array($value->kode_gejala, $kasus_detail)) ? $bobot_gejala[$value->kode_gejala][$row->kode_penyakit] : 0;
            }

            $i++;
        }

        /*
        $temp = array();
        $temp = $records[0];
        $records[0] = $records[4];
        $records[4] = $temp;
         */

        $this->benchmark->mark('code_start');
        $dbscan = new Dbscan($primary, $records, $epsilon, $minPoints);
        $this->benchmark->mark('code_end');

        $data['elapsed_time'] = $this->benchmark->elapsed_time('code_start', 'code_end');

        $data['clusters']       = $dbscan->getClusters();
        $data['noises']         = $dbscan->getNoises();
        $data['corePoints']     = $dbscan->getCorePoints();
        $data['centroids']      = $dbscan->getCentroids();
        $data['gejala']         = $gejala;
        $data['epsilon']        = $epsilon;
        $data['minPoints']      = $minPoints;
        $data['noiseCentroids'] = $dbscan->getNoiseCentroids();

        $centroids     = array_merge($data['noiseCentroids'], $data['centroids']);
        $data['table'] = $this->lihat_hasil_dbscan($centroids);

        $silhoutteIndex         = new Silhoutte_index();
        $data['silhoutteIndex'] = $silhoutteIndex->getValue($dbscan->getClustersValue());

        $data['page_header']   = $this->page_header;
        $data['panel_heading'] = 'Training dengan epsilon ' . $epsilon . ', Minimum Points ' . $minPoints.' dan silhoutte index '. $data['silhoutteIndex'];
        $data['page']          = 'training';

        $this->frontend->view('dbscan_v', $data);
    }

    public function save_training()
    {
        $clusters   = unserialize($this->input->post('clusters'));
        $noises     = unserialize($this->input->post('noises'));
        $centroids  = unserialize($this->input->post('centroids'));
        $corePoints = unserialize($this->input->post('corePoints'));
        $gejala     = unserialize($this->input->post('gejala'));
        $epsilon    = unserialize($this->input->post('epsilon'));
        $minPoints  = unserialize($this->input->post('minPoints'));

        $silhoutteIndex = unserialize($this->input->post('silhoutteIndex'));
        $noiseCentroids = unserialize($this->input->post('noiseCentroids'));

        $n = count($clusters);
        $this->load->model(array('data_dbscan_model' => 'data', 'centroids_dbscan_model' => 'centroids'));

        $query = $this->klaster->delete(array('metode' => $this->metode));

        $query = $this->pusat_klaster->delete(array('metode' => $this->metode));

        for ($i = 0; $i < $n; $i++) {
            if (!empty($clusters[$i])) {
                $rows = array();
                foreach ($clusters[$i] as $value) {
                    $rows[] = array('kode_kasus' => $value, 'metode' => $this->metode, 'klaster' => ($i + 1));
                }

                $this->klaster->insert($rows);

                $rows    = array();
                $rows[0] = array('klaster' => ($i + 1), 'no' => 1, 'metode' => $this->metode, 'atribut' => 'lk', 'bobot' => $centroids[$i][0]);
                $rows[1] = array('klaster' => ($i + 1), 'no' => 2, 'metode' => $this->metode, 'atribut' => 'pr', 'bobot' => $centroids[$i][1]);
                $rows[2] = array('klaster' => ($i + 1), 'no' => 3, 'metode' => $this->metode, 'atribut' => 'usia', 'bobot' => $centroids[$i][2]);

                $j = 3;
                foreach ($gejala as $value) {
                    $rows[$j] = array('klaster' => ($i + 1), 'no' => ($j + 1), 'metode' => $this->metode, 'atribut' => $value->kode_gejala, 'bobot' => $centroids[$i][$j++]);
                }

                $this->pusat_klaster->insert($rows);
            }
        }

        /* Menambahkan noises sebagai klaster dan pusat klaster 0 */
        $rows = array();
        foreach ($noises as $value) {
            $rows[] = array('kode_kasus' => $value, 'metode' => $this->metode, 'klaster' => 0);
        }
        
        $this->klaster->insert($rows);

        $rows    = array();
        $rows[0] = array('klaster' => 0, 'no' => 1, 'metode' => $this->metode, 'atribut' => 'lk', 'bobot' => $noiseCentroids[0][0]);
        $rows[1] = array('klaster' => 0, 'no' => 2, 'metode' => $this->metode, 'atribut' => 'pr', 'bobot' => $noiseCentroids[0][1]);
        $rows[2] = array('klaster' => 0, 'no' => 3, 'metode' => $this->metode, 'atribut' => 'usia', 'bobot' => $noiseCentroids[0][2]);

        $j = 3;
        foreach ($gejala as $value) {
            $rows[$j] = array('klaster' => 0, 'no' => ($j + 1), 'metode' => $this->metode, 'atribut' => $value->kode_gejala, 'bobot' => $noiseCentroids[0][$j++]);
        }

        $this->pusat_klaster->insert($rows);

        $this->inisiasi_klaster->update(array('epsilon' => $epsilon, 'min_points' => $minPoints, 'silhoutte_index' => $silhoutteIndex), array('metode' => $this->metode));

        /*
        for ($i = 0; $i < count($clusters); $i++) {
        $this->data->where('kode_kasus', $clusters[$i])->update(array('cluster' => ($i + 1)));
        }
        $this->data->where('kode_kasus', $noises)->update(array('cluster' => 0));

        for ($i=0, $row = array(); $i < count($centroids); $i++) {
        $row[$i]['id'] = $i+1;
        $row[$i]['lk'] = $centroids[$i][0];
        $row[$i]['pr'] = $centroids[$i][1];
        $row[$i]['umur'] = $centroids[$i][2];
        $row[$i]['g1'] = $centroids[$i][3];
        $row[$i]['g2'] = $centroids[$i][4];
        $row[$i]['g3'] = $centroids[$i][5];
        $row[$i]['g4'] = $centroids[$i][6];
        $row[$i]['g5'] = $centroids[$i][7];
        $row[$i]['g6'] = $centroids[$i][8];
        $row[$i]['g7'] = $centroids[$i][9];
        $row[$i]['g8'] = $centroids[$i][10];
        $row[$i]['g9'] = $centroids[$i][11];
        $row[$i]['g10'] = $centroids[$i][12];
        $row[$i]['g11'] = $centroids[$i][13];
        $row[$i]['g12'] = $centroids[$i][14];
        $row[$i]['g13'] = $centroids[$i][15];
        $row[$i]['g14'] = $centroids[$i][16];
        $row[$i]['g15'] = $centroids[$i][17];
        $row[$i]['g16'] = $centroids[$i][18];
        $row[$i]['g17'] = $centroids[$i][19];
        $row[$i]['g18'] = $centroids[$i][20];
        $row[$i]['g19'] = $centroids[$i][21];
        $row[$i]['g20'] = $centroids[$i][22];
        $row[$i]['g21'] = $centroids[$i][23];
        $row[$i]['g22'] = $centroids[$i][24];
        $row[$i]['g23'] = $centroids[$i][25];
        $row[$i]['g24'] = $centroids[$i][26];
        $row[$i]['g25'] = $centroids[$i][27];
        $row[$i]['g26'] = $centroids[$i][28];
        $row[$i]['g27'] = $centroids[$i][29];
        $row[$i]['g28'] = $centroids[$i][30];
        $row[$i]['g29'] = $centroids[$i][31];
        $row[$i]['g30'] = $centroids[$i][32];
        $row[$i]['g31'] = $centroids[$i][33];
        $row[$i]['g32'] = $centroids[$i][34];
        $row[$i]['g33'] = $centroids[$i][35];
        $row[$i]['g34'] = $centroids[$i][36];
        $row[$i]['g35'] = $centroids[$i][37];
        $row[$i]['g36'] = $centroids[$i][38];
        $row[$i]['g37'] = $centroids[$i][39];
        $row[$i]['g38'] = $centroids[$i][40];
        $row[$i]['g39'] = $centroids[$i][41];
        $row[$i]['g40'] = $centroids[$i][42];
        $row[$i]['g41'] = $centroids[$i][43];
        $row[$i]['g42'] = $centroids[$i][44];
        $row[$i]['g43'] = $centroids[$i][45];
        $row[$i]['g44'] = $centroids[$i][46];
        $row[$i]['g45'] = $centroids[$i][47];
        $row[$i]['r1'] = $centroids[$i][48];
        $row[$i]['r2'] = $centroids[$i][49];
        $row[$i]['r3'] = $centroids[$i][50];
        $row[$i]['r4'] = $centroids[$i][51];
        $row[$i]['r5'] = $centroids[$i][52];
        $row[$i]['r6'] = $centroids[$i][53];
        $row[$i]['r7'] = $centroids[$i][54];
        $row[$i]['r8'] = $centroids[$i][55];
        $row[$i]['r9'] = $centroids[$i][56];
        $row[$i]['r10'] = $centroids[$i][57];
        }

        $this->centroids->drop_data();
        $this->centroids->insert($row);
         */
        echo array("status" => true);
    }

    public function lihat_hasil_dbscan($centroids)
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

        for ($a = 0; $a < count($centroids); $a++) {
            $table .= '<tr><td><b>C ' . $a . '</b></td>';
            for ($b = 0; $b < count($centroids[0]); $b++) {
                $table .= '<td>' . round($centroids[$a][$b], 4) . '</td>';
            }

            $table .= '</tr>';
        }
        $table .= '</table>';

        return $table;
    }

    private function norm_usia($usia)
    {
        $max_usia    = $this->basis_kasus->get_max()->max_usia;
        $min_usia    = $this->basis_kasus->get_min()->min_usia;

        return ($usia - $min_usia) / ($max_usia - $min_usia);
    }
}
