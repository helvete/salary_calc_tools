<?php

namespace helvete\Tools;

class WageCalc {

    const DAY = 'P1D'; // 1-day interval
    const HPD = 8; // hours-per-working-day official
    const TAXRATE = 0.15; // global tax rate
    const TAXLV = 2070; // tax leave monthly absolute (up to)
    const HLTSI = 0.09; // health insurance ratio employer
    const SOCSI = 0.25; // social insurance ratio employer
    const HLTRI = 0.045; // health insurance ratio employee
    const SOCRI = 0.065; // social insurance ratio employee

    protected $holidays;

    protected $stadd = 0.00;
    protected $vacrate = 0.00;
    protected $vacutil = 0.00;
    protected $debug = 0;

    public function __construct(
        Holidays $holidays,
        $stadd,
        $vacrate = 0,
        $vacutil = 0,
        $debug = 0
    ) {
        foreach (get_defined_vars() as $name => $val) {
            if (property_exists($this, $name)) {
                $this->$name = $val;
            }
        }
    }

    public function daysCnt($month) {
        static $cache;
        if (empty($cache[$month])) {
            $oneDay = new \DateInterval(self::DAY);
            $start = new \DateTime("${month}-01");
            $end = new \DateTime("${month}-{$start->format('t')}");
            $end = $end->add($oneDay);
            $bdCnt = 0;
            list(, $mnth) = explode('-', $month);
            $holThsMon = $this->holidays->monthly((int)$mnth);
            foreach (new \DatePeriod($start, $oneDay, $end) as $d) {
                if ($d->format('N') < 6) {
                    ++$bdCnt;
                }
            }
            $cache[$month] = $bdCnt;
            if ($this->debug) {
                echo "Working days: {$bdCnt}" . PHP_EOL;
                echo "Vacation days: {$this->vacutil}" . PHP_EOL;
                echo "Holiday days: " . count($holThsMon) . PHP_EOL;
                $this->l();
            }
        }
        return $cache[$month];
    }

    public function realRoughWage($mon, $nomiLvl) {
        if ($this->vacutil > 0) {
            $atWork = $this->woVac($this->daysCnt($mon), $nomiLvl);
            $onVac = round($this->vacutil * self::HPD * $this->vacrate, 2);
            if ($this->debug) {
                echo "Standard mode: {$atWork}" . PHP_EOL;
                echo "Vacation mode: {$onVac}" . PHP_EOL;
                $this->l();
            }
            return round($atWork + $onVac);
        }
        return $nomiLvl;
    }

    public function woVac($monDayCnt, $nomiLvl) {
         return ($monDayCnt - $this->vacutil) / $monDayCnt * $nomiLvl;
    }

    public function superRough($rough) {
        $add = 0;
        foreach ([self::HLTSI, self::SOCSI] as $ins) {
            $add += $ins * $rough;
        }
        return $rough + $add + $this->stadd;
    }

    public function taxReal($s) {
        $taxAdvance = self::TAXRATE
            * $this->ruw($s - $this->stadd, 100)
            - self::TAXLV;
        return $taxAdvance > 0
            ? $taxAdvance
            : 0;
    }

    public function pureWage($rough, $tax) {
        return $rough
            - $this->ruw(self::HLTRI * $rough, 1)
            - $this->ruw(self::SOCRI * $rough, 1)
            - $tax;
    }

    public function getDiff($nomiLvl, $mon) {
        $a = $this->realRoughWage($mon, $nomiLvl);
        $b = $this->superRough($a);
        $c = $this->taxReal($b);
        $d = $this->pureWage($a, $c);
        if ($this->debug) {
            echo "Super rough wage: {$b}" . PHP_EOL;
            echo "Real rough wage: {$a}" . PHP_EOL;
            echo "Real tax applied: {$c}" . PHP_EOL;
            echo "Real pure wage: {$d}" . PHP_EOL;
            $this->l();
        }
        return $d;
    }

    public function ruw($roundee, $level) {
        $roundeeCnt = (int)($roundee / $level);
        if ($roundee / $level > $roundeeCnt) {
            ++$roundeeCnt;
        }
        return $roundeeCnt * $level;
    }

    public function l($cols = 80) {
        for ($i = 0; $i < $cols; $i++) {
            echo "-";
        }
        echo PHP_EOL;
    }

    public function setVacutil($vacutil) {
        $this->vacutil = $vacutil;
    }

    public function getVacutil() {
        return $this->vacutil;
    }
}
