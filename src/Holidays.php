<?php

namespace helvete\Tools;

class Holidays {

    const HPATTERN = '^H_[0-9]{2}_[A-Z0-9]{2}$';

    const H_01_NY = '01-01';
    const H_05_MD = '05-01';
    const H_05_EW = '05-08';
    const H_07_CM = '07-05';
    const H_07_JH = '07-06';
    const H_09_VC = '09-28';
    const H_10_CR = '10-28';
    const H_11_FD = '11-17';
    const H_12_X0 = '12-24';
    const H_12_X1 = '12-25';
    const H_12_X2 = '12-26';

    protected $year;

    public function __construct($year) {
        $y = \DateTime::createFromFormat('Y', $year);
        if (!$y) {
            throw new \InvalidArgumentException('Inst input not valid!');
        }
        $this->year = $year;
    }

    public function monthly($month) {
        $month = (int)$month;
        switch ($month) {
            case 3:
            case 4:
                return $this->all()[$month];
            default:
                return $this->fixedOrdered()[$month];
        }
    }

    public function all() {
        return $this->calcEaster() + $this->fixedOrdered();
    }

    /**
     * TODO: refactor, perhaps utilizing https://www.assa.org.au/edm
     */
    protected function calcEaster()
    {
        $y = $this->year;

        $a = $y % 19;
        $b = $y % 4;
        $c = $y % 7;

        // for 20. and 21. century only hardcoded consts, see
        // https://cs.wikipedia.org/wiki/V%C3%BDpo%C4%8Det_data_Velikonoc
        $m = 24;
        $n = 5;

        $d = (19 * $a + $m) % 30;
        $e = ($n + 2 * $b + 4 * $c + 6 * $d) % 7;

        $u = $d + $e - 9;
        $k = 4;
        if ($u === 25 && $d === 28 && $e === 6 && $a > 10) {
            $ym = '04-18';
        } elseif ($u > 0 && $u < 26) {
            $u = $u < 10 ? "0{$u}" : $u;
            $ym = "04-{$u}";
        } elseif ($u > 25) {
            $u -= 7;
            $ym = "04-{$u}";
        } else {
            $u = 22 + $d + $e;
            $ym = "03-{$u}";
            $k = 3;
        }

        $esDt = new \DateTime("{$this->year}-{$ym}");
        $emDt = (clone $esDt)->add(new \DateInterval('P1D')); // Sun -> Mon
        $efDt = (clone $esDt)->sub(new \DateInterval('P2D')); // Sun -> Fri

        return [$k => [$efDt, $emDt,],];
    }

    protected function fixedOrdered()
    {
        $yearly = static::init();
        $self = new \ReflectionClass(self::class);

        foreach ($self->getConstants() as $constName => $constValue) {
            if (!preg_match('/' . static::HPATTERN . '/', $constName)) {
                continue;
            }
            list(, $key) = explode('_', $constName);
            $yearly[(int)$key][] = new \DateTime("{$this->year}-{$constValue}");
        }

        return $yearly;
    }

    static public function init()
    {
        return array_fill_keys(range(1, 12), []);
    }
}
