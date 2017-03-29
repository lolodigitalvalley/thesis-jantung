<?php

class Nearestneighbors
{

    protected $newCase   = array();
    protected $oldCase   = array();
    protected $distances = array();

    public function __construct(array $oldCase, array $newCase, $k = 1, $similarity = 'NEAREST_NEIGHBORS')
    {
        $num = count($oldCase);

        switch ($similarity) {
            case 'NEAREST_NEIGHBORS':
                for ($i = 0; $i < $num; $i++) {
                    $this->distances['D' . $i] = $this->nearest_neighbors($oldCase[$i], $newCase);
                }

                break;
            case 'EUCLIDEAN_NEIGHBORS':
                for ($i = 0; $i < $num; $i++) {
                    $this->distances['D' . $i] = $this->euclidean_neighbors($oldCase[$i], $newCase);
                }

                break;
            case 'MINKOWSKI':
                for ($i = 0; $i < $num; $i++) {
                    $this->distances['D' . $i] = $this->minkowski($oldCase[$i], $newCase);
                }

                break;
        }
        asort($this->distances); //Sort Dari kecil ke besar

        $start           = count($this->distances) - $k;
        $this->distances = array_slice($this->distances, $start, $k); //ambil K terbaik dari bawah
    }

    public function get_distance()
    {
        if (count($this->distances) > 1) {
            $distance = array();
            foreach ($this->distances as $key => $value) {
                $distance = $value;
            }

        } else {
            $distance = max($this->distances);
        }

        return $distance;
    }

    private function minkowski(array $oldCase, array $newCase)
    {
        if (($n = count($newCase)) !== count($oldCase)) {
            return false;
        }

        $sum = ($oldCase[0] == $newCase[0]) ? 64 : 0;

        $age = 4 * (1 - (abs($oldCase[1] - $newCase[1]) / 100));
        $sum = $sum + pow($age, 3);
        $sumOldcase = 128;

        for ($i = 2; $i < $n; $i++) {
            $sum += pow($oldCase[$i] * $newCase[$i], 3);
            $sumOldcase += pow($oldCase[$i], 3);
        }
        $euclidean = ($sum / $sumOldcase);

        return pow($euclidean, 1/3);
    }

    private function euclidean_neighbors(array $oldCase, array $newCase)
    {
        if (($n = count($newCase)) !== count($oldCase)) {
            return false;
        }

        $sum = ($oldCase[0] == $newCase[0]) ? 16 :0;

        $age        = 4 * (1 - (abs($oldCase[1] - $newCase[1]) / 100));
        $sum        = $sum + pow($age, 2);
        $sumOldcase = 32;

        for ($i = 2; $i < $n; $i++) {
            $sum += pow($oldCase[$i] * $newCase[$i], 2);
            $sumOldcase += pow($oldCase[$i], 2);
        }
        $euclidean = ($sum / $sumOldcase);

        return sqrt($euclidean);
    }

    private function nearest_neighbors(array $oldCase, array $newCase)
    {

        if (($n = count($newCase)) !== count($oldCase)) {
            return false;
        }

        $sum = ($oldCase[0] == $newCase[0]) ? 4 :0;

        $sum        = $sum + (4 * (1 - (abs($oldCase[1] - $newCase[1]) / 100)));
        $sumOldcase = 8;

        for ($i = 2; $i < $n; $i++) {
            $sum += $oldCase[$i] * $newCase[$i];
            $sumOldcase += $oldCase[$i];
        }

        $euclidean = ($sum / $sumOldcase);
        
        return $euclidean;
    }

    public function get_nn()
    {
        $rank = max($this->distances);
        $rank = array_search($rank, $this->distances);
        return substr($rank, 1);
    }
}
