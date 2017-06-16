<?php

namespace reports;

class containers extends \tables\containersReceived
{

    public $db;
    
    public $info = [];
    
    public $logs = [];
    
    /*
    ****************************************************************************
    */
 
    static function sendEmails($app)
    {
        $self = new static($app);

        $self->getUnreported();
        
        $isReport = $self->forEachContainer();

        if ($isReport) {
            $self->recordReportsSent();            
        }
        
        return $self->logs;
    }
    
    /*
    ****************************************************************************
    */
 
    function forEachContainer()
    {
        foreach ($this->containers as $recNum => $row) {
            
            $subject = 'Container '.$row['container'].' has been received on ' 
                    .$row['setDate'];

            $sql = 'SELECT '.$this->primaryKey.',
                           '.$this->getSelectFields().'
                    FROM   '.$this->table.'
                    WHERE  b.recNum = ?
                    '.$this->getQueryPiece('groupBy').'
                    '.$this->getQueryPiece('orderBy');

            $results = $this->app->queryResults($sql, [$recNum]);

            if ($results) {
                $title = 'Receiving number '.$recNum.'<br><br>';

                $body = $this->createReport($results);

                $text = $title.$body;

                \PHPMailer\send::mail([
                    'recipient' => $row['email'],
                    'subject' => $subject,
                    'body' => $text,
                ]);

                $log = 'Emailed report for container '.$recNum.': '
                     . $row['container'];

                $this->logs[] = $log;
                
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    /*
    ****************************************************************************
    */
 
    function getUnreported()
    {
        // Get a list of all containers that have not been reported from today

        $sql = 'SELECT    co.recNum, 
                          SUM(
                            IF(s.shortName = "RCNT" AND s.category = "reports", 1, 0)
                          ) AS reported,
                          co.name AS container,
                          v.id AS vendorID,
                          setDate,
                          v.email
                FROM      inventory_containers co 
                LEFT JOIN reports_data rd ON rd.primeKey = co.recNum
                LEFT JOIN statuses s ON s.id = rd.statusID
                JOIN      vendors v ON v.id = co.vendorID
                JOIN      tallies t ON t.recNum = co.recNum
                WHERE     1
                AND       v.email IS NOT NULL
                AND       t.locked
                AND       co.setDate > DATE_SUB(NOW(), INTERVAL 1 WEEK)
                GROUP BY  co.recNum
                HAVING    reported = 0';
        
        $this->containers = $this->app->queryResults($sql);
    }
    
    /*
    ****************************************************************************
    */
    
    function recordReportsSent()
    {
        // Record that the email was sent
        
        $primeKeys = array_keys($this->containers);
                
        \common\report::recordReportsSent($this->app, $primeKeys, 'RCNT');
    }
    
    /*
    ****************************************************************************
    */
    
    function createReport($results)
    {
        ob_start();
        
        $firstRow = reset($results);
        
        ?> 
        <table border="1" style="border-collapse: collapse;">
            <tr><?php
            foreach ($firstRow as $key => $value) {
                $caption = $this->fields[$key]['display']; ?> 
                <th style="text-align: center; white-space: nowrap; padding: 3px;
                    padding: 3px;"><?php echo $caption; ?></th><?php
            } ?> 
            </tr><?php

            foreach ($results as $fields) { ?> 
                <tr><?php
                    foreach ($fields as $key => $value) { ?> 
                        <td style="white-space: nowrap; padding: 3px;">
                            <?php echo $value; ?></td><?php
                    } ?> 
                </tr><?php
            } ?>
        </table><?php

        return ob_get_clean();
    }
    
    /*
    ****************************************************************************
    */
}
