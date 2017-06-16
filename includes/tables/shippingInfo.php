<?php
/**
 * Created by PhpStorm.
 * User: rober
 * Date: 05/02/2016
 * Time: 16:28
 */

namespace tables;


class shippingInfo extends \tables\_default
{
    static $labelTitle = 'Shipping Info';

    static $labelsTitle = 'Shipping Info';

    public $ajaxModel = 'billOfLadings';

    public $primaryKey = 'si.id';

    public $fields = [
        'bolID' => [
            'select' => 'si.bolID',
            'display' => 'BOL Number',
            'noEdit' => TRUE,
        ],
        'bolLabel' => [
            'select' => 'si.bolLabel',
            'display' => 'BOL Label',
            'noEdit' => TRUE,
        ],

        'scanordernumber' => [
            'display' => 'BOL Label',
            'noEdit' => TRUE,
        ],

        'vendorName' => [
            'select' => 'CONCAT(w.shortName, "", v.vendorName)',
            'display' => 'BOL Label',
            'noEdit' => TRUE,
        ],

        'shipFromName' => [
            'select' => 'cad.companyName',
            'display' => 'Ship From Name',
            'noEdit' => TRUE,
        ],
        'shipfromaddress' => [
            'select' => 'cad.address',
            'display' => 'Ship From Address',
            'noEdit' => TRUE,
        ],
        'shipfromcity' => [
            'select' => 'CONCAT(cad.city,", ",cad.country, ", ", cad.state, ", ", cad.zip)',
            'display' => 'Ship From City',
            'noEdit' => TRUE,
        ],
        'shiptoname' => [
            'select' => 'si.shiptoname',
            'display' => 'Ship To Name',
            'noEdit' => TRUE,
        ],
        'shiptoaddress' => [
            'select' => 'si.shiptoaddress',
            'display' => 'Ship To Address',
            'noEdit' => TRUE,
        ],
        'shiptocity' => [
            'select' => 'si.shiptocity',
            'display' => 'Ship To City',
            'noEdit' => TRUE,
        ],
        'shiptotel' => [
            'select' => 'si.shiptotel',
            'display' => 'Ship To Tel',
            'noEdit' => TRUE,
        ],
        'partyname' => [
            'select' => 'si.partyname',
            'display' => '3rdParty Name',
            'noEdit' => TRUE,
        ],
        'partyaddress' => [
            'select' => 'si.partyaddress',
            'display' => '3rdParty Address',
            'noEdit' => TRUE,
        ],
        'partycity' => [
            'select' => 'si.partycity',
            'display' => '3rdParty City',
            'noEdit' => TRUE,
        ],
        'specialinstruction' => [
            'select' => 'si.specialinstruction',
            'display' => 'Special Instruction',
            'noEdit' => TRUE,
        ],
        'carriername' => [
            'select' => 'si.carriername',
            'display' => 'Carrier Name',
            'noEdit' => TRUE,
        ],
        'otherdocumentinform' => [
            'select' => 'si.otherdocumentinform',
            'display' => 'Order Documnent',
            'noEdit' => TRUE,
        ],
        'freightchargetermby' => [
            'select' => 'IF(freightchargetermby = "freightchargetermbycollect", "Collect",IF(freightchargetermby = "freightchargetermbyprepaid","Prepaid", "3rdParty"))',
            'display' => 'Freight Charge By',
            'searcherDD' => 'shipments\\freightchargeterms',
            'ddField' => 'displayName',
            'update' => 'si.freightchargetermby',
            'noEdit' => TRUE,
        ],
        'freightchargeterminfo' => [
            'display' => 'Freight Charge Cost',
            'noEdit' => TRUE,
        ],
        'carrier' => [
            'select' => 'si.carrier',
            'display' => 'Carrier',
            'noEdit' => TRUE,
        ],
        'carriernote' => [
            'select' => 'si.carriernote',
            'display' => 'Carrier Note',
            'noEdit' => TRUE,
        ],
        'commodityDesc' => [
            'select' => 'com.description',
            'display' => 'Commodity Desc',
            'noEdit' => TRUE,
        ],
        'commodityNmfc' => [
            'select' => 'com.nmfc',
            'display' => 'Commodity NMFC',
            'noEdit' => TRUE,
        ],
        'commodityClass' => [
            'select' => 'com.class',
            'display' => 'Commodity Class',
            'noEdit' => TRUE,
        ],
        'trailernumber' => [
            'display' => 'Trailer Number',
            'noEdit' => TRUE,
        ],
        'sealnumber' => [
            'display' => 'Seal Number',
            'noEdit' => TRUE,
        ],
        'scac' => [
            'display' => 'SCAC',
            'noEdit' => TRUE,
        ],
        'pronumber' => [
            'display' => 'Pro Number',
            'noEdit' => TRUE,
        ],
        'shiptype' => [
            'select' => 'si.shiptype',
            'display' => 'Ship Type',
            'noEdit' => TRUE,
        ],
        'attachbilloflading' => [
            'select' => 'IF(attachbilloflading = "YES", "YES", "NO")',
            'display' => 'Attach BOL',
            'noEdit' => TRUE,
        ],
        'acceptablecustomer' => [
            'display' => 'Accept Customer',
            'noEdit' => TRUE,
        ],
        'feetermby' => [
            'select' => 'IF(feetermby = "feetermbycollect", "Collect", "Prepaid")',
            'display' => 'Fee Term By',
            'noEdit' => TRUE,
        ],
        'trailerloadby' => [
            'select' => 'IF(trailerloadby = "trailerloadbyshipper", "By Shipper", "By Driver")',
            'display' => 'Fee Term By',
            'noEdit' => TRUE,
        ],
        'trailercountedby' => [
            'select' => 'IF(trailercountedby = "trailercountedbyshipper", "By Shipper",IF(trailercountedby = "trailercountedbydriverpallets","By Drive/Pallets", "By Drive/Pieces"))',
            'display' => 'Trailer Counted By',
            'noEdit' => TRUE,
        ],
    ];

    public $groupBy = 'si.id, n.id';
    public $orderBy = 'si.id DESC, b.id';

    /*
    ****************************************************************************
    */

    function table()
    {
        return 'shipping_info si
                JOIN shipping_orders so ON so.bolID = si.bolLabel
                JOIN neworder n ON so.orderID = n.id
                JOIN inventory_cartons ca ON ca.orderID = n.id
                JOIN inventory_batches b ON b.id = ca.batchID
                JOIN inventory_containers co ON co.recNum = b.recNum
                JOIN vendors v ON v.id = co.vendorID
                JOIN warehouses w ON w.id = v.warehouseID
                JOIN commodity com ON com.id = si.commodity
                LEFT JOIN company_address cad ON cad.id = n.location';
    }
}