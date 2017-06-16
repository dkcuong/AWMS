<?php

namespace tables;

class onlineOrdersFails extends _default
{
    
    const ORDER_NUMBER = 2;
    
    public $ajaxModel = 'onlineOrdersFails';
        
    public $primaryKey = 'f.id';
    
    public $fields = [
        'submitTime' => [
            'display' => 'Submit Time',
        ],
        'reference_id' => [
            'display' => 'Reference ID',
            'required' => TRUE,
        ],
        'order_id' => [
            'display' => 'Order ID',
            'required' => TRUE,
        ],
        'shipment_id' => [
            'display' => 'Shipment ID',
            'required' => TRUE,
        ],
        'first_name' => [
            'display' => 'Shipping First Name',
            'required' => TRUE,
        ],      
        'last_name' => [
            'display' => 'Shipping Last Name',
        ],       
        'shipping_address_street' => [
            'display' => 'Shipping Address Street',
            'required' => TRUE,
        ],  
        'shipping_address_street_cont' => [
            'display' => 'Shipping Address Street Cont',
        ],
        'shipping_city' => [
            'display' => 'Shipping City',
            'required' => TRUE,
        ],
        'shipping_state' => [
            'display' => 'Shipping State',
            'required' => TRUE,
        ],
        'shipping_postal_code' => [
            'display' => 'Shipping Postal Code',
            'required' => TRUE,
        ],   
        'shipping_country' => [
            'display' => 'Shipping Country',
            'required' => TRUE,
        ],      
        'shipping_country_name' => [
            'display' => 'Shipping Country Name',
        ],
        'product_sku' => [
            'display' => 'Product SKU',
        ],            
        'upc' => [
            'display' => 'UPC',
            'required' => TRUE,
        ],                  
        'warehouse_id' => [
            'display' => 'Warehouse ID',
        ],         
        'warehouse_name' => [
            'display' => 'Warehouse Name',
        ],         
        'product_quantity' => [
            'display' => 'Product Quantity',
            'required' => TRUE,
        ],       
        'product_name' => [
            'display' => 'Product Name',
        ],           
        'product_description' => [
            'display' => 'Product Description',
        ],
        'product_cost' => [
            'display' => 'Product Cost',
        ],
        'customer_phone_number' => [
            'display' => 'Customer Phone Number',
        ],
        'order_date' => [
            'display' => 'Order Date',
            'required' => TRUE,
        ],
        'carrier' => [
            'display' => 'Carrier',
        ],
        'account_number' => [
            'display' => 'Account Number',
        ],
        'seldat_third_party' => [
            'display' => 'Seldat/Third Party',
        ],
    ];

    public $table = 'online_orders_fails f';

    /*
    ****************************************************************************
    */
    
    function listFailTable($onlineOrders, $export=FALSE)
    { 
        unset($this->fields['upsLink']);
        ?>
        <table cellspacing="0" width="100%">
            <thead>
                <tr><?php 
                
                $firstColumn = TRUE;
                
                foreach ($this->fields as $field) { 
                    if (! $firstColumn) {   ?>
                    
                        <th><?php echo $field['display']; ?></th><?php
                        
                    }
                    
                    $firstColumn = FALSE;                   
                } ?>
                        
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($export) {
                    foreach ($onlineOrders as $rowID => $row) { ?>
                        <tr >
                        <?php foreach ($row as $cellID => $cell) { 
                            if ($cellID != 0) {?>
                                <td><?php echo $cell; ?></td>
                            <?php } ?>
                        <?php } ?>
                        </tr><?php 
                    } 
                }?>
            </tbody>
        </table><?php        
    }

    /*
    ****************************************************************************
    */

    function customDTInfo($data)
    {
        $sql = 'SELECT   order_id
                FROM     online_orders_fails
                WHERE    order_id = ?
                GROUP BY order_id, submitTime
                HAVING   COUNT(id) > 1';

        $multiRows = [];
        foreach ($data as $rowID => $row) { 
            $orderNumber = $row[onlineOrders::ORDER_NUMBER];
            $result = $this->app->queryResult($sql, [$orderNumber]);
            $multiRows[$rowID] = $result ? TRUE : FALSE;        
        }
        
        return $multiRows;
    }
    
    /*
    ****************************************************************************
    */

    function getFails($searchTime=FALSE, $assoc=FALSE)
    {   
        $params = $searchTime ? [$searchTime] : [];
        $clause = $searchTime ? 'submitTime = ?' : 1;
        $selectField = $this->getFieldValues($this->fields);

        $sql = 'SELECT   ' . $selectField . '
                FROM     ' . $this->table . '
                WHERE    ' . $clause;

        $result = $assoc ? $this->app->queryResults($sql, $params) :
            $this->app->ajaxQueryResults($sql, $params);

        return $result;
    }

}