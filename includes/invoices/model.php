<?php

namespace invoices;

use DateTime;

class model
{
    public $db;
    public $rates = [];
    public $custUOMs = [];
    public $volRates = [];
    public $customerUOMs = [];


    /*
    ****************************************************************************
    */

    static function init($dbObj)
    {
        $self = new static();
        $self->db = $dbObj->getDB();
        return $self;
    }

    /*
    ****************************************************************************
    */

    function setRates($uomRes)
    {
        // Get each customers charge codes
        $this->rates = getDefault($uomRes['rates'], []);

        return $this;
    }

    /*
    ****************************************************************************
    */

    function customerUOMs($getCat=FALSE)
    {
        if ($getCat) {
            return getDefault($this->customerUOMs[$getCat], []);
        }

        foreach ($this->rates as $type => $custs) {
            foreach ($custs as $custID => $uoms) {
                foreach ($uoms as $uom) {
                    $this->customerUOMs[$type][$uom][] = $custID;
                }
            }
        }

        return $this;
    }

    /*
    ****************************************************************************
    */

    function custUOMs($getCat)
    {
        if (isset($this->custUOMs[$getCat])) {
            return $this->custUOMs[$getCat];
        }

        foreach ($this->customerUOMs as $cat => $uomCusts) {
            if (! $uomCusts) {
                continue;
            }

            foreach ($uomCusts as $uom => $custs) {
                if (! $custs) {
                    continue;
                }

                foreach ($custs as $custID) {
                    $this->custUOMs[$cat][$custID][$uom] = TRUE;
                }
            }
        }

        return getDefault($this->custUOMs[$getCat], []);
    }

    /*
    ****************************************************************************
    */

    function rates()
    {
        return $this->rates;
    }

    /*
    ****************************************************************************
    */

    function custVolumeRates($category, $custID)
    {
        return getDefault($this->volRates[$category][$custID], []);
    }

    /*
    ****************************************************************************
    */

    function volumeRates($type)
    {
        if (! getDefault($this->customerUOMs[$type]['MONTHLY_SMALL_CARTON'])) {
            return;
        }

        if (! $this->volRates) {
            // Get the volume bracets
             $sql = 'SELECT c.inv_cost_id,
                            c.cust_id,
                            chg_cd_price,
                            r.min_vol,
                            r.max_vol,
                            r.uom,
                            category
                    FROM    inv_vol_rates r
                    JOIN    charge_cd_mstr m ON chg_cd_uom = r.uom
                    JOIN    invoice_cost c ON c.chg_cd_id = m.chg_cd_id
                    WHERE   chg_cd_sts = "active"
                    AND     c.cust_id = r.cust_id
                    AND     c.status != "d"';
           
            foreach ($this->db->query($sql, \PDO::FETCH_ASSOC) as $row) {
                $uom = $row['uom'];
                $custID = $row['cust_id'];
                $category = $row['category'];
                $this->volRates[$category][$custID][$uom] = $row;
            }
        }

        return $this;
    }

    /*
    ****************************************************************************
    */

    function volClause($type, $params, $volField, $custField='cust_id')
    {
        $uom = $params['uom'];
        $custs = $params['custs'];
        $clauses = [];

        $volRates = getDefault($this->volRates[$type]);
        if (! $volRates) {
            return NULL;
        }

        foreach ($custs as $custID) {

            if (! isset($volRates[$custID][$uom])) {
                continue;
            }

            $max = getDefault($volRates[$custID][$uom]['max_vol']);
            
            $maxClause = floatVal($max) ? 'AND '.$volField.' <= '.$max : NULL;
            
            $clauses[] = '
                    '.$custField.' = '.intVal($custID).'
                AND '.$volField.' > '.$volRates[$custID][$uom]['min_vol'].'
                '.$maxClause.'
            ';
        }

        return $clauses ? 'AND ('.implode(' OR ', $clauses).')' : NULL;
    }

    /*
    ****************************************************************************
    */

    function getBillableDts($params)
    {
        $custs = $params['custs'];
        $details = getDefault($params['details']);

        if (! $custs) {
            return [];
        }

        $curDate = \models\config::getDateTime('date');

        $curDtObj = new DateTime($curDate);
        $endDtObj = new DateTime($params['dates']['endDate']);

        $pInitial = $params['period'] == 'daily' ? 'D' : 'M';
        $format = $params['period'] == 'daily' ? 'Y-m-d' : 'Y-m';

        $daterange = new \DatePeriod(
            new DateTime($params['dates']['startDate']),
            new \DateInterval('P1'.$pInitial),
            $endDtObj
        );


        $months = [];
        foreach($daterange as $date){
            $months[] = $date->format($format);
        }

        // Incase end date is the first of a month
        $months[] = $endDtObj->format($format);

        $curDTFormatted = $curDtObj->format($format);
        $diff = array_diff($months, [$curDTFormatted]);

        $unique = array_unique($diff);
        if (! $unique) {
            return [];
        }

        $custQMarks = $this->db->getQMarkString($custs);
        $dateQMarks = $this->db->getQMarkString($unique);

        $select = $params['period'] == 'daily' ?
            'inv_date' : 'DATE_FORMAT(inv_date, "%Y-%m")';

        $sql = 'SELECT cust_id,
                       '.$select.' AS dt
                FROM   inv_his_month
                WHERE  inv_sts
                AND    cust_id IN ('.$custQMarks.')
                AND    '.$select.' IN ('.$dateQMarks.')
                AND    type = ?';

        $qParams = array_merge($custs, $unique);

        array_push($qParams, $params['cat']);

        $monthResults = $this->db->queryResults($sql, $qParams, \PDO::FETCH_ASSOC);

        $billedCustDts = [];
        foreach ($monthResults as $row) {
            $custID = $row['cust_id'];
            $billedCustDts[$custID][] = $row['dt'];
        }

        if (isset($params['getBilled'])) {
            return $billedCustDts;
        }

        $day = $params['period'] == 'daily' ? NULL : '-01';

        $sums = $dts = [];
        foreach ($custs as $custID) {
            $custBilled = getDefault($billedCustDts[$custID], []);
            $found = array_diff($unique, $custBilled);

            $sums[$custID]['rcvCount'] = 0;

            foreach ($found as $dt) {
                $dt = $dt.$day;
                $dts[$custID][$dt] = 1;
                $sums[$custID]['rcvCount'] += 1;
            }
        }

        return $details ? $dts : $sums;
    }

    /*
    ****************************************************************************
    */

    static function formatDtsQty(&$custDts)
    {
        if (! $custDts) {
            return;
        }

        foreach ($custDts as &$details) {
            array_walk($details, function (&$row) {
                if (is_numeric($row['quantity'])) {
                    $row['quantity'] = number_format($row['quantity'], 2);
                }
            });
        }
    }

    /*
    ****************************************************************************
    */
}
