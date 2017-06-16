<?php

namespace tables;

class onlineOrdersFailsUpdate extends _default
{
    
    const ORDER_NUMBER = 2;
    
    public $ajaxModel = 'onlineOrdersFailsUpdate';
        
    public $primaryKey = 'f.id';
    
    public $fields = [
        'submitTime' => [
            'display' => 'Submit Time',
        ],
        'reference_id' => [
            'display' => 'reference ID',
        ],
        'order_id' => [
            'display' => 'Order ID',
        ],
        'from_company' => [
            'display' => 'From Company',
        ],
        'from_name' => [
            'display' => 'From Name',
        ],
        'from_address_1' => [
            'display' => 'From Address 1',
        ],
        'from_address_2' => [
            'display' => 'From Address 2',
        ],
        'from_city' => [
            'display' => 'From City',
        ],
        'from_state' => [
            'display' => 'From State',
        ],
        'from_postal' => [
            'display' => 'From Postal',
        ],
        'from_country' => [
            'display' => 'From Country',
        ],
        'from_phone' => [
            'display' => 'From Phone',
        ],
        'from_email' => [
            'display' => 'From Email',
        ],
        'from_notify_on_shipment' => [
            'select' => 'IF(from_notify_on_shipment, "YES", "NO")',
            'display' => 'From Notify On Shipment',
        ],
        'from_notify_on_exception' => [
            'select' => 'IF(from_notify_on_exception, "YES", "NO")',
            'display' => 'From Notify On Exception',
        ],
        'from_notify_on_delivery' => [
            'select' => 'IF(from_notify_on_delivery, "YES", "NO")',
            'display' => 'From Notify On Delivery',
        ],
        'to_company' => [
            'display' => 'To Company',
        ],
        'to_name' => [
            'display' => 'To Name',
        ],
        'to_address_1' => [
            'display' => 'To Address 1',
        ],
        'to_address_2' => [
            'display' => 'To Address 2',
        ],
        'to_address_3' => [
            'display' => 'To Address 3',
        ],
        'to_city' => [
            'display' => 'To City',
        ],
        'to_state' => [
            'display' => 'To State',
        ],
        'to_postal' => [
            'display' => 'To Postal',
        ],
        'to_country' => [
            'display' => 'To Country',
        ],
        'to_phone' => [
            'display' => 'To Phone',
        ],
        'to_email' => [
            'display' => 'To Email',
        ],
        'to_notify_on_shipment' => [
            'select' => 'IF(to_notify_on_shipment, "YES", "NO")',
            'display' => 'To Notify On Shipment',
        ],
        'to_notify_on_exception' => [
            'select' => 'IF(to_notify_on_exception, "YES", "NO")',
            'display' => 'To Notify On Exception',
        ],
        'to_notify_on_delivery' => [
            'select' => 'IF(to_notify_on_delivery, "YES", "NO")',
            'display' => 'To Notify On Delivery',
        ],
        'signature' => [
            'display' => 'Signature',
        ],
        'saturday_elivery' => [
            'select' => 'IF(saturday_delivery, "YES", "NO")',
            'display' => 'Saturday Delivery',
        ],
        'reference_1' => [
            'display' => 'Reference 1',
        ],
        'reference_2' => [
            'display' => 'Reference 2',
        ],
        'provider' => [
            'display' => 'Provider',
        ],
        'package_type' => [
            'display' => 'Package Type',
        ],
        'service' => [
            'display' => 'Service',
        ],
        'bill_to' => [
            'display' => 'Bill To',
        ],
        'third_party_acc_num' => [
            'display' => '3rd Party ACC Num',
        ],
        'third_party_postal_code' => [
            'display' => '3rd Party Postal Code',
        ],
        'third_party_country_code' => [
            'display' => '3rd Party Country Code',
        ],
        'package_weight' => [
            'display' => 'Package Weight LB',
        ],
        'package_length' => [
            'display' => 'Package Length',
        ],
        'package_width' => [
            'display' => 'Package Width',
        ],
        'package_height' => [
            'display' => 'Package Height',
        ],
        'package_insured_value' => [
            'display' => 'Package Insured Value',
        ],
        'can_be_merged' => [
            'select' => 'IF(can_be_merged, "YES", "NO")',
            'display' => 'Can Be Merged',
        ],
    ];

    public $table = 'online_orders_fails_update f';

    /*
    ****************************************************************************
    */
    
    function listFailTable($onlineOrders, $export=FALSE)
    { 
        unset($this->fields['upsLink']);
        ?>
        <table cellspacing="0" width="100%">
            <thead>
                <tr>
                <?php foreach ($this->fields as $field) { ?>
                    <th><?php echo $field['display']; ?></th>
                <?php } ?>
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