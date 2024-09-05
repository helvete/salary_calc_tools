<?php

namespace helvete\Tools;

class WageCalc {

    const DAY = 'P1D'; // 1-day interval
    const HPD = 8; // hours-per-working-day official
    const TAXRATE = 0.15; // global tax rate
    const TAXLV = 2570; // tax leave monthly absolute (up to)
    const HLTSI = 0.09; // health insurance ratio employer
    const SOCSI = 0.248; // social insurance ratio employer
    const HLTRI = 0.045; // health insurance ratio employee
    const SOCRI = 0.065; // social insurance ratio employee
    const ILLRI = 0.006; // sickness insurance ratio employee
    const OTRATE = 0.25;

    protected $holidays;

    protected $stadd = 0.00;
    protected $vacrate = 0.00;
    protected $vacutil = 0.00;
    protected $debug = 0;
    protected $ot = 0;
    protected $debugStack = [];

    public function __construct(
        Holidays $holidays,
        $stadd,
        $vacrate = 0,
        $vacutil = 0,
        $debug = 0,
        $ot = 0
    ) {
        foreach (get_defined_vars() as $name => $val) {
            if (property_exists($this, $name)) {
                $this->$name = $val;
            }
        }
        if ($this->ot && !$this->vacrate) {
            throw new \InvalidArgumentException(implode(',', [
                $this->ot,
                $this->vacrate,
            ]));
        }
    }

    public function daysCnt($month) {
        static $cache;
        if (empty($cache[$month])) {
            $oneDay = new \DateInterval(self::DAY);
            try {
                $start = new \DateTime("${month}-01");
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(sprintf(
                    "invalid yearmonth input: %s",
                    $month
                ));
            }
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
                $this->debugStack["Working days: %s"] = $bdCnt;
                $this->debugStack["Vacation days: %s"] = $this->vacutil;
                $this->debugStack["Holiday days: %s"] = count($holThsMon);
            }
        }
        return $cache[$month];
    }

    public function realRoughWage($mon, $nomiLvl) {
        if ($this->vacutil > 0 || $this->ot > 0) {
            $daysCnt = $this->daysCnt($mon);
            $atWork = $this->woVac($daysCnt, $nomiLvl);
            $onVac = floor($this->vacutil * self::HPD * $this->vacrate);
            if ($this->ot) {
                $htMon = $nomiLvl / (static::HPD * $daysCnt);
                $otRate = $htMon * $this->ot;
                $otBonus = static::OTRATE * $this->ot * $this->vacrate;
                if ($this->debug) {
                    $this->debugStack["Overtime base: %s"] = round($otRate, 1);
                    $this->debugStack["Overtime bonus: %s"] = round($otBonus, 1);
                }
                $atWork += $otRate + $otBonus;
            }
            $atWork = $this->ruw($atWork, 1);

            if ($this->debug) {
                $this->debugStack["Standard mode: %s"] = $atWork;
                $this->debugStack["Vacation mode: %s"] = $onVac;
            }
            return round($atWork + $onVac);
        }
        return $nomiLvl;
    }

    public function woVac($monDayCnt, $nomiLvl) {
         return ($monDayCnt - $this->vacutil) / $monDayCnt * $nomiLvl;
    }

    public function employerBase($rough) {
        $add = 0;
        foreach ([self::HLTSI, self::SOCSI] as $ins) {
            $add += $ins * $rough;
        }
        return $rough + $add;
    }

    public function taxReal($s) {
        $taxAdvance = self::TAXRATE * static::ruw($s, 100) - self::TAXLV;
        return $taxAdvance > 0
            ? $taxAdvance
            : 0;
    }

    public function pureWage($rough, $tax) {
        $hltri = static::ruw(self::HLTRI * $rough, 1);
        $socri = static::ruw(self::SOCRI * $rough, 1);
        $illri = static::ruw(self::ILLRI * $rough, 1);
        if ($this->debug) {
            $this->debugStack[" > Health insurance: %s"] = $hltri;
            $this->debugStack[" > Social insurance: %s"] = $socri;
            $this->debugStack[" > Sickness insurance: %s"] = $illri;
        }
        return $rough
            - $hltri
            - $socri
            - $illri
            - $tax;
    }

    public function getDiff($nomiLvl, $mon) {
        $a = $this->realRoughWage($mon, $nomiLvl);
        $b = $this->employerBase($a);
        $c = $this->taxReal($a);
        if ($this->debug) {
            $b = static::ruw($b, 1);
            $this->debugStack["Total employer cost: %s"] = $b + $this->stadd;
            $this->debugStack["Real rough wage: %s"] = $a;
            $this->debugStack["Real tax applied: %s"] = $c;
        }
        $d = $this->pureWage($a, $c);
        if ($this->debug) {
            $this->debugStack["Real pure wage: %s"] = $d;
            $this->debugStack["Taxes effectively: %s"] = round((1 - $d / $b) * 100) . '%';
        }
        return $d;
    }

    static public function ruw($roundee, $level) {
        $roundeeCnt = (int)($roundee / $level);
        if ($roundee / $level > $roundeeCnt) {
            ++$roundeeCnt;
        }
        return $roundeeCnt * $level;
    }

    public function setVacutil($vacutil) {
        $this->vacutil = $vacutil;
    }

    public function getVacutil() {
        return $this->vacutil;
    }

    public function getDebugStack() {
        return $this->debugStack;
    }
}
