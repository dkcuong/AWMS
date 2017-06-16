<?php

namespace tables\statuses;

class invoice extends \tables\statuses
{
    
    public $primaryKey = 'id';
    
    public $fields = [
        'shortName' => [],
    ];
 
    public $where = 'category = "invoice"';
    
    public $type = 'Open';
    
    public $orderBy = 'FIELD(displayName, "Open", "Invoiced", "Paid")';
    
    public $groupby = 'id';
    
    /*
    ****************************************************************************
    */
  
    function getInvoiceMonthSelect($data) 
    {
        $count = 0;
        
        $selectMonth = [];
        $monthNames = NULL;
        
        $vendorID = $data['vendorID'];
        $status = $data['status'];
        $type = $data['invoiceType'];
        
        $invoiceType = 'invoices_'.$type;
     
        if ( $type == 'storage' ||  $type == 'receiving' ) {
            $monthNames = $this->getSelectInvoiceMonth($vendorID,
                     $status,$invoiceType);
        } elseif ( $type == 'processing' ||  $type == 'workOrders' ) {
            $monthNames = '';    
        }
        
        return $monthNames;
    }
    
    /*
    ****************************************************************************
    */
    
    function getSelectInvoiceMonth($vendor,$status,$invoiceType)
    {
        $sql = 'SELECT  DISTINCT MONTH(co.setDate) AS iMonth,
                        CONCAT(MONTHNAME(co.setDate),"  ",
                        YEAR(co.setDate)) AS sMonth
                FROM    ' . $invoiceType . ' i 
                JOIN    inventory_containers co on i.recNum = co.recNum
                JOIN    statuses s on s.id = i.statusID
                WHERE   s.displayName = "' . $status . '"
                AND     co.vendorID = ' . $vendor . '  
                AND     MONTHNAME(co.setDate) <> MONTHNAME(CURDATE())
                ORDER BY co.setDate ASC';
        
        $results = $this->app->queryResults($sql);
        
        return $results;
    }
    
    /*
    ****************************************************************************
    */
} 