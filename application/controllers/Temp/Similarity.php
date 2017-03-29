<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Similarity extends CI_Controller
{

    protected $page_header = 'Similarity';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array('data_som_model' => 'data_som', 'data_dbscan_model' => 'data_dbscan', 'centroids_som_model' => 'centroids_som', 'centroids_dbscan_model' => 'centroids_dbscan', 'gejala_model' => 'gejala'));
        $this->load->library('cluster_lib');
    }

    public function index()
    {
        $data['page_header']      = $this->page_header;
        $data['panel_heading']    = 'Input New Case';
        $data['page']             = 'index';
        $data['centroids_som']    = $this->get_centroids_som();
        $data['centroids_dbscan'] = $this->get_centroids_dbscan();

        $data['gejala'] = $this->gejala->get_all();
        $this->frontend->view('similarity_v', $data);
    }

    public function retrive_non_indexing()
    {
        $this->benchmark->mark('code_start');

        $indexing = $this->input->post('indexing');
        $sim      = $this->input->post('similarity');
        $sex      = ($this->input->post('sex') == 'L') ? 1 : 2;

        $age    = $this->input->post('age');
        $gejala = $this->input->post('gejala');

        for ($i = 1, $index = array(); $i <= count($gejala); $i++) {
            $index[$i] = (int) substr($gejala[$i - 1], 1);
        }

        $newCase    = array();
        $newCase[0] = $sex;
        $newCase[1] = $age;

        for ($i = 2, $j = 1; $i < 57; $i++) {
            $newCase[$i] = (in_array($j, $index)) ? 1 : 0;
            $j++;
        }

        $query = $this->data_som->fields('kode_kasus, kode_penyakit, jk, umur, g1, g2, g3, g4, g5, g6, g7, g8, g9, g10, g11, g12, g13, g14, g15, g16, g17, g18, g19, g20,
                                     g21, g22, g23, g24, g25, g26, g27, g28, g29, g30,g31, g32, g33, g34, g35, g36, g37, g38, g39, g40,
                                     g41, g42, g43, g44, g45,r1, r2, r3, r4, r5, r6, r7, r8, r9, r10')->get_all();
        $i = 0;
        foreach ($query as $row) {
            $oldCase[$i][0]  = $row->jk;
            $oldCase[$i][1]  = $row->umur;
            $oldCase[$i][2]  = $row->g1;
            $oldCase[$i][3]  = $row->g2;
            $oldCase[$i][4]  = $row->g3;
            $oldCase[$i][5]  = $row->g4;
            $oldCase[$i][6]  = $row->g5;
            $oldCase[$i][7]  = $row->g6;
            $oldCase[$i][8]  = $row->g7;
            $oldCase[$i][9]  = $row->g8;
            $oldCase[$i][10] = $row->g9;
            $oldCase[$i][11] = $row->g10;
            $oldCase[$i][12] = $row->g11;
            $oldCase[$i][13] = $row->g12;
            $oldCase[$i][14] = $row->g13;
            $oldCase[$i][15] = $row->g14;
            $oldCase[$i][16] = $row->g15;
            $oldCase[$i][17] = $row->g16;
            $oldCase[$i][18] = $row->g17;
            $oldCase[$i][19] = $row->g18;
            $oldCase[$i][20] = $row->g19;
            $oldCase[$i][21] = $row->g20;
            $oldCase[$i][22] = $row->g21;
            $oldCase[$i][23] = $row->g22;
            $oldCase[$i][24] = $row->g23;
            $oldCase[$i][25] = $row->g24;
            $oldCase[$i][26] = $row->g25;
            $oldCase[$i][27] = $row->g26;
            $oldCase[$i][28] = $row->g27;
            $oldCase[$i][29] = $row->g28;
            $oldCase[$i][30] = $row->g29;
            $oldCase[$i][31] = $row->g30;
            $oldCase[$i][32] = $row->g31;
            $oldCase[$i][33] = $row->g32;
            $oldCase[$i][34] = $row->g33;
            $oldCase[$i][35] = $row->g34;
            $oldCase[$i][36] = $row->g35;
            $oldCase[$i][37] = $row->g36;
            $oldCase[$i][38] = $row->g37;
            $oldCase[$i][39] = $row->g38;
            $oldCase[$i][40] = $row->g39;
            $oldCase[$i][41] = $row->g40;
            $oldCase[$i][42] = $row->g41;
            $oldCase[$i][43] = $row->g42;
            $oldCase[$i][44] = $row->g43;
            $oldCase[$i][45] = $row->g44;
            $oldCase[$i][46] = $row->g45;
            $oldCase[$i][47] = $row->r1;
            $oldCase[$i][48] = $row->r2;
            $oldCase[$i][49] = $row->r3;
            $oldCase[$i][50] = $row->r4;
            $oldCase[$i][51] = $row->r5;
            $oldCase[$i][52] = $row->r6;
            $oldCase[$i][53] = $row->r7;
            $oldCase[$i][54] = $row->r8;
            $oldCase[$i][55] = $row->r9;
            $oldCase[$i][56] = $row->r10;
            $i++;
        }

        $similarity      = new Nearestneighbors($oldCase, $newCase, 1, $sim);
        $index           = $similarity->get_nn();
        $nearestCase     = $query[$index]->kode_kasus;
        $nearestDistance = $similarity->get_distance();
  

        $this->benchmark->mark('code_end');

        echo json_encode(array('nearestCluster' => 'Non Clustered', 'nearestCase' => $nearestCase, 'row' => $query[$index], 'indexing' => $indexing, 'point' => $nearestDistance, 'elapsed_time' => $this->benchmark->elapsed_time('code_start', 'code_end'), 'memory_usage' => $this->benchmark->memory_usage()));
    }

    public function retrive_with_indexing()
    {
        $this->benchmark->mark('code_start');

        $sim         = $this->input->post('similarity');
        $indexing    = $this->input->post('indexing');
        $centroids   = ($indexing == 'som') ? unserialize($this->input->post('centroids_som')) : unserialize($this->input->post('centroids_dbscan'));

        $sex = ($this->input->post('sex') == 'L') ? 1 : 2;
        $age = $this->input->post('age');

        $gejala = $this->input->post('gejala');

        for ($i = 1, $index = array(); $i <= count($gejala); $i++) {
            $index[$i] = (int) substr($gejala[$i - 1], 1);
        }

        $rows = $this->gejala->fields('kode_gejala, p1, p2, p3, p4, p5, p6')->where('kode_gejala', $gejala)->get_all();

        $newCase       = array();
        $newCase[0][0] = $sex;
        $newCase[0][1] = $age;

        
        if ($sex == 1) {
            for ($i=1; $i <= 6 ; $i++) { 
                $newCase[$i][0] = 0;
                $newCase[$i][1] = 4;
            }
        } else {
            for ($i=1; $i <= 6 ; $i++) { 
                $newCase[$i][0] = 4;
                $newCase[$i][1] = 0;
            }
        }
     

        $newCase[1][2] = $age / 10;
        $newCase[2][2] = $age / 10;
        $newCase[3][2] = $age / 10;
        $newCase[4][2] = $age / 10;
        $newCase[5][2] = $age / 10;
        $newCase[6][2] = $age / 10;

        for ($i = 1, $j = 0; $i < 56; $i++) {
            if (in_array($i, $index)) {
                $newCase[0][$i + 1] = 1;
                $newCase[1][$i + 2] = $rows[$j]->p1;
                $newCase[2][$i + 2] = $rows[$j]->p2;
                $newCase[3][$i + 2] = $rows[$j]->p3;
                $newCase[4][$i + 2] = $rows[$j]->p4;
                $newCase[5][$i + 2] = $rows[$j]->p5;
                $newCase[6][$i + 2] = $rows[$j]->p6;
                $j++;
            } else {
                $newCase[0][$i + 1] = 0;
                $newCase[1][$i + 2] = 0;
                $newCase[2][$i + 2] = 0;
                $newCase[3][$i + 2] = 0;
                $newCase[4][$i + 2] = 0;
                $newCase[5][$i + 2] = 0;
                $newCase[6][$i + 2] = 0;
            }

        } 

        
        $knn             = new knn($centroids, $newCase[1], 1, 'cosinus');
        $nearestCluster  = $knn->get_nn() + 1;
        $nearestDistance = $knn->get_distance();
        for ($i = 2; $i < 7; $i++) {
            $knn = new knn($centroids, $newCase[$i], 1, 'cosinus');
            if ($knn->get_distance() < $nearestDistance) {
                $nearestCluster  = $knn->get_nn() + 1;
                $nearestDistance = $knn->get_distance();
            }
        }        
        $result = $this->similarity_with_indexing($newCase[0], $indexing, $sim, $nearestCluster);        

        
        $knn             = new knn($centroids, $newCase[1], 1);
        $nearestCluster  = $knn->get_nn() + 1;
        $nearestDistance = $knn->get_distance();
        for ($i = 2; $i < 7; $i++) {
            $knn = new knn($centroids, $newCase[$i], 1);
            if ($knn->get_distance() < $nearestDistance) {
                $nearestCluster  = $knn->get_nn() + 1;
                $nearestDistance = $knn->get_distance();
            }
        }
        $result2 = $this->similarity_with_indexing($newCase[0], $indexing, $sim, $nearestCluster);

        $json = ($result['point'] > $result2['point']) ? $result : $result2;
        
        //$json = $result;

        $this->benchmark->mark('code_end');
        
        $json['elapsed_time'] = $this->benchmark->elapsed_time('code_start', 'code_end');

        echo json_encode($json);
    }

    private function similarity_with_indexing($newCase, $indexing = 'som', $sim, $nearestCluster)
    {        
        $fields = 'kode_kasus, kode_penyakit, jk, umur, g1, g2, g3, g4, g5, g6, g7, g8, g9, g10, g11, g12, g13, g14, g15, g16, g17, g18, g19, g20, g21, g22, g23, g24, g25, g26, g27, g28, g29, g30,g31, g32, g33, g34, g35, g36, g37, g38, g39, g40, g41, g42, g43, g44, g45,r1, r2, r3, r4, r5, r6, r7, r8, r9, r10 ';
        $query = ($indexing == 'som') ? $this->data_som->fields($fields)->where('cluster', $nearestCluster)->get_all() : $this->data_dbscan->fields($fields)->where('cluster', $nearestCluster)->get_all();

        $i = 0;
        foreach ($query as $row) {
            $oldCase[$i][0]  = $row->jk;
            $oldCase[$i][1]  = $row->umur;
            $oldCase[$i][2]  = $row->g1;
            $oldCase[$i][3]  = $row->g2;
            $oldCase[$i][4]  = $row->g3;
            $oldCase[$i][5]  = $row->g4;
            $oldCase[$i][6]  = $row->g5;
            $oldCase[$i][7]  = $row->g6;
            $oldCase[$i][8]  = $row->g7;
            $oldCase[$i][9]  = $row->g8;
            $oldCase[$i][10] = $row->g9;
            $oldCase[$i][11] = $row->g10;
            $oldCase[$i][12] = $row->g11;
            $oldCase[$i][13] = $row->g12;
            $oldCase[$i][14] = $row->g13;
            $oldCase[$i][15] = $row->g14;
            $oldCase[$i][16] = $row->g15;
            $oldCase[$i][17] = $row->g16;
            $oldCase[$i][18] = $row->g17;
            $oldCase[$i][19] = $row->g18;
            $oldCase[$i][20] = $row->g19;
            $oldCase[$i][21] = $row->g20;
            $oldCase[$i][22] = $row->g21;
            $oldCase[$i][23] = $row->g22;
            $oldCase[$i][24] = $row->g23;
            $oldCase[$i][25] = $row->g24;
            $oldCase[$i][26] = $row->g25;
            $oldCase[$i][27] = $row->g26;
            $oldCase[$i][28] = $row->g27;
            $oldCase[$i][29] = $row->g28;
            $oldCase[$i][30] = $row->g29;
            $oldCase[$i][31] = $row->g30;
            $oldCase[$i][32] = $row->g31;
            $oldCase[$i][33] = $row->g32;
            $oldCase[$i][34] = $row->g33;
            $oldCase[$i][35] = $row->g34;
            $oldCase[$i][36] = $row->g35;
            $oldCase[$i][37] = $row->g36;
            $oldCase[$i][38] = $row->g37;
            $oldCase[$i][39] = $row->g38;
            $oldCase[$i][40] = $row->g39;
            $oldCase[$i][41] = $row->g40;
            $oldCase[$i][42] = $row->g41;
            $oldCase[$i][43] = $row->g42;
            $oldCase[$i][44] = $row->g43;
            $oldCase[$i][45] = $row->g44;
            $oldCase[$i][46] = $row->g45;
            $oldCase[$i][47] = $row->r1;
            $oldCase[$i][48] = $row->r2;
            $oldCase[$i][49] = $row->r3;
            $oldCase[$i][50] = $row->r4;
            $oldCase[$i][51] = $row->r5;
            $oldCase[$i][52] = $row->r6;
            $oldCase[$i][53] = $row->r7;
            $oldCase[$i][54] = $row->r8;
            $oldCase[$i][55] = $row->r9;
            $oldCase[$i][56] = $row->r10;
            $i++;
        }

        $similarity      = new Nearestneighbors($oldCase, $newCase, 1, $sim);
        $index           = $similarity->get_nn();
        $nearestCase     = $query[$index]->kode_kasus;
        $nearestDistance = $similarity->get_distance();
        

        return array('nearestCluster' => $nearestCluster, 'nearestCase' => $nearestCase, 'row' => $query[$index], 'indexing' => $indexing, 'point' => $nearestDistance);
    }


    private function get_centroids_som()
    {
        $query = $this->centroids_som->fields('lk, pr, umur, g1, g2, g3, g4, g5, g6, g7, g8, g9, g10, g11, g12, g13, g14, g15, g16, g17, g18, g19, g20,
                                     g21, g22, g23, g24, g25, g26, g27, g28, g29, g30,g31, g32, g33, g34, g35, g36, g37, g38, g39, g40,
                                     g41, g42, g43, g44, g45,r1, r2, r3, r4, r5, r6, r7, r8, r9, r10 ')->get_all();
        $i         = 0;
        $centroids = array();
        if ($query) {
            foreach ($query as $row) {
                $centroids[$i][0]  = $row->lk;
                $centroids[$i][1]  = $row->pr;
                $centroids[$i][2]  = $row->umur;
                $centroids[$i][3]  = $row->g1;
                $centroids[$i][4]  = $row->g2;
                $centroids[$i][5]  = $row->g3;
                $centroids[$i][6]  = $row->g4;
                $centroids[$i][7]  = $row->g5;
                $centroids[$i][8]  = $row->g6;
                $centroids[$i][9]  = $row->g7;
                $centroids[$i][10] = $row->g8;
                $centroids[$i][11] = $row->g9;
                $centroids[$i][12] = $row->g10;
                $centroids[$i][13] = $row->g11;
                $centroids[$i][14] = $row->g12;
                $centroids[$i][15] = $row->g13;
                $centroids[$i][16] = $row->g14;
                $centroids[$i][17] = $row->g15;
                $centroids[$i][18] = $row->g16;
                $centroids[$i][19] = $row->g17;
                $centroids[$i][20] = $row->g18;
                $centroids[$i][21] = $row->g19;
                $centroids[$i][22] = $row->g20;
                $centroids[$i][23] = $row->g21;
                $centroids[$i][24] = $row->g22;
                $centroids[$i][25] = $row->g23;
                $centroids[$i][26] = $row->g24;
                $centroids[$i][27] = $row->g25;
                $centroids[$i][28] = $row->g26;
                $centroids[$i][29] = $row->g27;
                $centroids[$i][30] = $row->g28;
                $centroids[$i][31] = $row->g29;
                $centroids[$i][32] = $row->g30;
                $centroids[$i][33] = $row->g31;
                $centroids[$i][34] = $row->g32;
                $centroids[$i][35] = $row->g33;
                $centroids[$i][36] = $row->g34;
                $centroids[$i][37] = $row->g35;
                $centroids[$i][38] = $row->g36;
                $centroids[$i][39] = $row->g37;
                $centroids[$i][40] = $row->g38;
                $centroids[$i][41] = $row->g39;
                $centroids[$i][42] = $row->g40;
                $centroids[$i][43] = $row->g41;
                $centroids[$i][44] = $row->g42;
                $centroids[$i][45] = $row->g43;
                $centroids[$i][46] = $row->g44;
                $centroids[$i][47] = $row->g45;
                $centroids[$i][48] = $row->r1;
                $centroids[$i][49] = $row->r2;
                $centroids[$i][50] = $row->r3;
                $centroids[$i][51] = $row->r4;
                $centroids[$i][52] = $row->r5;
                $centroids[$i][53] = $row->r6;
                $centroids[$i][54] = $row->r7;
                $centroids[$i][55] = $row->r8;
                $centroids[$i][56] = $row->r9;
                $centroids[$i][57] = $row->r10;
                $i++;
            }
        }
        return $centroids;
    }

    private function get_centroids_dbscan()
    {
        $query = $this->centroids_dbscan->fields('lk, pr, umur, g1, g2, g3, g4, g5, g6, g7, g8, g9, g10, g11, g12, g13, g14, g15, g16, g17, g18, g19, g20,
                                     g21, g22, g23, g24, g25, g26, g27, g28, g29, g30,g31, g32, g33, g34, g35, g36, g37, g38, g39, g40,
                                     g41, g42, g43, g44, g45,r1, r2, r3, r4, r5, r6, r7, r8, r9, r10 ')->get_all();
        $i         = 0;
        $centroids = array();
        if ($query) {
            foreach ($query as $row) {
                $centroids[$i][0]  = $row->lk;
                $centroids[$i][1]  = $row->pr;
                $centroids[$i][2]  = $row->umur;
                $centroids[$i][3]  = $row->g1;
                $centroids[$i][4]  = $row->g2;
                $centroids[$i][5]  = $row->g3;
                $centroids[$i][6]  = $row->g4;
                $centroids[$i][7]  = $row->g5;
                $centroids[$i][8]  = $row->g6;
                $centroids[$i][9]  = $row->g7;
                $centroids[$i][10] = $row->g8;
                $centroids[$i][11] = $row->g9;
                $centroids[$i][12] = $row->g10;
                $centroids[$i][13] = $row->g11;
                $centroids[$i][14] = $row->g12;
                $centroids[$i][15] = $row->g13;
                $centroids[$i][16] = $row->g14;
                $centroids[$i][17] = $row->g15;
                $centroids[$i][18] = $row->g16;
                $centroids[$i][19] = $row->g17;
                $centroids[$i][20] = $row->g18;
                $centroids[$i][21] = $row->g19;
                $centroids[$i][22] = $row->g20;
                $centroids[$i][23] = $row->g21;
                $centroids[$i][24] = $row->g22;
                $centroids[$i][25] = $row->g23;
                $centroids[$i][26] = $row->g24;
                $centroids[$i][27] = $row->g25;
                $centroids[$i][28] = $row->g26;
                $centroids[$i][29] = $row->g27;
                $centroids[$i][30] = $row->g28;
                $centroids[$i][31] = $row->g29;
                $centroids[$i][32] = $row->g30;
                $centroids[$i][33] = $row->g31;
                $centroids[$i][34] = $row->g32;
                $centroids[$i][35] = $row->g33;
                $centroids[$i][36] = $row->g34;
                $centroids[$i][37] = $row->g35;
                $centroids[$i][38] = $row->g36;
                $centroids[$i][39] = $row->g37;
                $centroids[$i][40] = $row->g38;
                $centroids[$i][41] = $row->g39;
                $centroids[$i][42] = $row->g40;
                $centroids[$i][43] = $row->g41;
                $centroids[$i][44] = $row->g42;
                $centroids[$i][45] = $row->g43;
                $centroids[$i][46] = $row->g44;
                $centroids[$i][47] = $row->g45;
                $centroids[$i][48] = $row->r1;
                $centroids[$i][49] = $row->r2;
                $centroids[$i][50] = $row->r3;
                $centroids[$i][51] = $row->r4;
                $centroids[$i][52] = $row->r5;
                $centroids[$i][53] = $row->r6;
                $centroids[$i][54] = $row->r7;
                $centroids[$i][55] = $row->r8;
                $centroids[$i][56] = $row->r9;
                $centroids[$i][57] = $row->r10;
                $i++;
            }
        }
        return $centroids;
    }
}
