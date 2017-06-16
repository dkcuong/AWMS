<?php

namespace common;

class orderProperties {

    public $isTruckOrderImport = FALSE;

    public $isOrderImport = FALSE;

    public $importer = NULL;

    public $errors = [];

    public $checkNumInDB = [];

    public $scanOrderValid =[];

    public $inputValues = [];

    public $duplicate;

    public $insertOrNot = [];

    public $missingValues = [];

    public $insertSuccess;

    public $inputFields = [];

    public $radioNCheck = [];

    public $checkType = NULL;

    public $checkoutInput = 0;

    public $menu = [];

    public $dbValues = [];

    public $integerOnly = [];

    public $duplicateNumber = [];

    public $nonUTF = [];

    public $isOnHold;

    public $missingMandatoryValues = [];

    public $duplicateClientOrderNumber = [];

    public $closedOrders = [];

    public $canceledOrders = [];

    public $truckOrders = [];

    public $onlineOrders = [];

    public $productErrors = [];

    public $products = [];

    public $missingProducts = [];

    public $orderProducts = [];

    public $dbOrderProducts = [];

    public $shortageProducts = [];

    public $splitProducts = [];

    public $reprintWavePick = [];

    public $orderKeys = [];

    public $ajax = NULL;

    public $results = NULL;

    public $productRowCount = 35;

    public $rowHeight = 5;

    public $leftMargin = 10;

    public $pdf = NULL;

    public $downloadTruckOrderTemplate = NULL;

    public $importTruckOrderLink = NULL;

    public $vendorsArray = [];

    public $orderNumbers = [];

    public $pickingLocations = [];

    public $tableData = [
        1 => [
            'columnWidth' => [
                1 => 35,
                2 => 40
            ],
            'margin' => 5,
            'data' => [
                1  => ['caption' => 'User'],
                2  => ['caption' => 'Customer Name'],
                3  => ['caption' => 'Client Vendor Name'],
                4  => ['caption' => 'Client Order Number'],
                5  => ['caption' => 'Customer Order Number'],
                6  => ['caption' => 'Scan Order Number'],
                7  => ['caption' => 'Client Department ID'],
                8  => ['caption' => 'Client Pick TicKet'],
                9  => ['caption' => 'Start Ship Date'],
                10  => ['caption' => 'Cancel Date'],
                11  => ['caption' => 'Order Type'],
            ]
        ],
        2 => [
            'columnWidth' => [
                1 => 25,
                2 => 30
            ],
            'margin' => 5,
            'data' => [
                1  => ['caption' => 'Ecomerce'],
                2  => ['caption' => 'Regular'],
                3  => ['caption' => 'Standard'],
                4  => ['caption' => 'Rush (8 Hours)'],
                5  => ['caption' => 'Super-Rush (4 Hours)'],
                6  => ['text' => '# of Cartons'],
                7  => ['text' => '# of Pieces'],
                8  => ['text' => 'Total Volume'],
                9  => ['text' => 'Total Weight'],
                10 => ['caption' => 'Pick ID'],
                11 => ['caption' => 'Shipping From'],
                12 => ['caption' => 'Pick List'],
                13 => ['caption' => 'Packing List'],
                14 => ['caption' => 'Pre-Printed BOL'],
                15 => ['caption' => 'Commercial Invoice'],
                16 => ['caption' => 'Carton Content Labels'],
                17 => ['type' => 'checkbox']
            ]
        ],
        3 => [
            'columnWidth' => [
                1 => 20,
                2 => 35
            ],
            'data' => [
                1  => ['text' => 'Samples #'],
                2  => ['text' => 'Pick Pack #'],
                3  => ['caption' => 'Ship To Label'],
                4  => ['caption' => 'Edi Label'],
                5  => ['caption' => 'UPS/FEDEX'],
                6  => ['text' => '# of Pallets'],
                7  => ['text' => '# of Physical Labor Hrs'],
                8  => ['text' => '# of Over Time Labor Hrs'],
                9  => ['caption' => 'Add. Shipper Info'],
                10  => ['caption' => 'No VAS/Work-order'],
                11 => ['caption' => 'VAS/Work-order'],
            ]
        ]
    ];

    public $productTableData = [
        1 => [
            'width' => 10,
            'header' => [
                2 => '#'
            ]
        ],
        2 => [
            'width' => 15,
            'header' => [
                2 => 'UPCID'
            ]
        ],
        3 => [
            'width' => 15,
            'header' => [
                1 => 'Cartons'
            ]
        ],
        4 => [
            'width' => 30,
            'header' => [
                1 => 'Descriptions:',
                2 => 'Style'
            ]
        ],
        5 => [
            'width' => 18,
            'header' => [
                2 => 'Size'
            ]
        ],
        6 => [
            'width' => 20,
            'header' => [
                2 => 'Color'
            ]
        ],
        7 => [
            'width' => 25,
            'header' => [
                2 => 'UPC'
            ]
        ],
        8 => [
            'width' => 17,
            'header' => [
                2 => 'UOM'
            ]
        ],
        9 => [
            'width' => 15,
            'header' => [
                1 => 'Quantity',
                3 => 0
            ]
        ],
        10 => [
            'width' => 20,
            'header' => [
                2 => 'Location'
            ]
        ],
        11 => [
            'width' => 18,
            'header' => [
                2 => 'Prefix'
            ]
        ],
        12 => [
            'width' => 18,
            'header' => [
                2 => 'Suffix'
            ]
        ],
        13 => [
            'width' => 15,
            'header' => [
                1 => 'Pieces',
                2 => 'Availabe'
            ]
        ],
        14 => [
            'width' => 13,
            'header' => [
                1 => 'Volume'
            ]
        ],
        15 => [
            'width' => 13,
            'header' => [
                1 => 'Weight'
            ]
        ]
    ];

    public $totalColumns = [3, 9, 14, 15];

    public $tableWidth = 0;

    public $landscapePageWidth = 262;

    public $truckTableColumnAmount = 0;

    public $truckTableColumnWidth = 0;

    public $truckTableLastColumnWidth = 0;

    public $descriptionWidth = 0;

    public $checkBoxes = [
        'picklist',
        'packinglist',
        'prebol',
        'commercialinvoice',
        'cartoncontent',
        'otherlabel',
        'otherdocument'
    ];

    public $radio = [
        'ecommerce',
        'regular',
        'EcoOrReg',
        'standard',
        'rush',
        'superrush',
        'shiptolabel',
        'eri',
        'UFlabels',
        'service',
        'label',
        'noVAS',
        'yesVAS',
    ];

    public $checkEmptyFill = [
        'vendor',
        'clientordernumber',
        'customerordernumber',
        'scanOrderNumber',
        'startshipdate',
        'canceldate',
        'type'
    ];

    public $noCheck = [
        'otherlabelnocheck' => [
            'otherlabel',
            'otherlabelinform'
        ],
    ];

    public $noFill = [
        'otherlabelnofill' => [
            'otherlabel',
            'otherlabelinform'
        ],
    ];

    public $checkFields = [
        'vendor' => 'Client Vendor Name is missing!',
        'clientordernumber' => 'Client Order Number is missing!',
        'customerordernumber' => 'Customer Order Number is missing!',
        'scanOrderNumber' => 'Scan Order Number is missing!',
        'scanOrderValid' => 'Scan Order Number Invalid!',
        'startshipdate' => 'Start Ship Date is missing!',
        'canceldate' => 'Cancel Date is missing!',
        'type' => 'Order Type is missing!',
        'startvscancel' => 'Start Ship Date Can not be later than Cancel Date!',
        'otherlabelnocheck' => '"Other Label" is filled but not checked!',
        'otherlabelnofill' => '"Other Label" is checked but not filled
                              or filled with 0 !',
        'otherdocnocheck' => '"Other Document" is filled but not checked!',
        'otherdocnofill' => '"Other Document" is checked but not filled
                            or filled with 0 !',
        'shiptolabel' => '"Ship To Label" is checked but not filled!',
        'eri' => '"Edi Label" is checked but not filled!',
        'UFlabels' => '"UPS/FEDEX" is checked but not filled!',
        'checkAllFields' => 'There is(are) mandatory field(s) missing!',
        'PickListColNoCheck' => 'Must choose at least one from "Pick List",
                                 "Packing List","Pre-Printed BOL"
                                 and "Commercial Invoice"!',
        'cartonLabelsColNoCheck' => 'Must choose at least one from
                                  "Carton Content Labels" and "Other Label"!'
    ];

    public $checkFieldsInDB = [
        'checkClientOrderNum' => 'Client Order Number',
        'checkScanOrderNum' => 'Order Number'
    ];

    public $carrierAndNote = [
        'fedex' => 'fedexaccount',
        'UPS' => 'upsaccount',
        'LTL' => 'ltlaccount',
        'routing' => 'routebydate',
        'willcall' => 'willcallnote',
        'specificcarrier' => 'specificnote',
        'shiptolabels' => 'shiptolabelsnote',
        'ediasn' => 'ediasnnote'
    ];

    public $label = [
        'shiptolabelinfo',
        'eriinfo',
        'UFlabelsinfo'
    ];

    public $payBy = [
        'clientcost',
        'seldatcost',
        '3rdpartycost',
        'collectcost'
    ];

    public $duplicateOrderNumbers = [
        'scanOrderNumber' => '"Scan Order Number"'
    ];

    public $duplicateClientOrderNumbers = [
        'clientOrderNumber' => '"Client Order Number"'
    ];

    public $integerValuesOnly = [
        'numberofcarton' => '"#Of Cartons"',
        'numberofpiece' => '"#Of Pieces"',
        'pickid' => '"Pick ID"',
        'samples' => '"Samples #"',
        'pickpack' => '"Pick Pack #"',
        'shiptolabelinfo' => '"Ship To Label"',
        'eriinfo' => 'Edi Label"',
        'UFlabelsinfo' => '"UPS/FEDEX"',
        'NOpallets' => '"# of Pallets"',
        'physicalhours' => '"# of Physical Hrs"',
        'overtimehours' => '"# of Over Time Hrs"'
    ];

    public $menus = [
        'user' => 'userid',
        'vendor' => 'vendor',
        'locationtable' => 'location',
        'orderType' => 'type',
        'dcPerson' => 'dcUserID',
    ];

    public $dbFields = [
        'userid',
        'first_name',
        'last_name',
        'clientordernumber',
        'customerordernumber',
        'scanOrderNumber',
        'deptid',
        'clientpickticket',
        'location',
        'numberofcarton',
        'additionalshipperinformation',
        'numberofpiece',
        'totalVolume',
        'totalWeight',
        'startshipdate',
        'canceldate',
        'type',
        'EcoOrReg',
        'service',
        'picklist',
        'packinglist',
        'prebol',
        'commercialinvoice',
        'cartoncontent',
        'otherlabelinform',
        'dcUserID',
        'pickid',
        'samples',
        'pickpack',
        'label',
        'labelinfo',
        'NOpallets',
        'physicalhours',
        'overtimehours',
        'statusID',
        'dateentered',
        'ordernotes',
        'isVAS',
    ];

    public $dbInoreFields = [
        'deptid',
        'clientpickticket',
        'additionalshipperinformation'
    ];

    public $forView = [
        'userColumn' => [
            'Client Order Number' => 'clientordernumber',
            'Customer Order Number' => 'customerordernumber',
            'Scan Order Number' => 'scanOrderNumber'
        ],

        'EcoOrReg' => [
            'Ecommerce' => 'ecommerce',
            'Regular' => 'regular'
        ],

        'StandardColumn' => [
            'Standard' => 'standard',
            'Rush (8 Hours)' => 'rush',
            'Super-Rush (4 Hours)' => 'superrush'
        ],

        'sampleColumn' => [
            'Ship To Label' => [
                'shiptolabel',
                'shiptolabelinfo'
            ],
            'Edi Label '=> [
                'eri',
                'eriinfo'
            ],
            'UPS/FEDEX' => [
                'UFlabels',
                'UFlabelsinfo'
            ]
        ],

        'isVAS' => [
            'No VAS/Work-order' => 'noVAS',
            'VAS/Work-order' => 'yesVAS',
        ],

        'picklistColumn' => [
            'Pick List'=>'picklist',
            'Packing List'=>'packinglist',
            'Pre-Printed BOL'=>'prebol',
            'Commercial Invoice'=>'commercialinvoice'
        ],

        'shiptoColumn' => [
            'Carton Content Labels' => 'cartoncontent'
        ],
    ];

    public $nonUTFCheck = [
        'first_name' => '"First Name"',
        'last_name' => '"Customer Name"',
        'clientordernumber' => '"Client Order Number"',
        'customerordernumber' => '"Customer Order Number"',
        'scanOrderNumber' => '"Scan Order Number"',
    ];

    public $checkAllFields = [
        'userid' => '"User"',
        'vendor' => '"Client Vendor Name"',
        'first_name' => '"First Name"',
        'last_name' => '"Last Name/Customer Name"',
        'clientordernumber' => '"Client Order Number"',
        'customerordernumber' => '"Customer Order Number"',
        'scanOrderNumber' => '"Scan Order Number"',
        'location' => '"Shipping From"',
        'numberofcarton' => '"#Of Cartons"',
        'carrierName' => '"Carrier Name"',
        'numberofpiece' => '"#Of Pieces"',
        'totalVolume' => '"Total Volume"',
        'totalWeight' => '"Total Weight"',
        'startshipdate' => '"Start Ship Date"',
        'canceldate' => '"Cancel Date"',
        'type' => 'Order Type',
        'EcoOrReg' => '"Ecommerce"/"Regular"',
        'service' => '"Standard"/"Rush"/"Super-rush"',
        'carrier' => '"Carrier"',
        'ordernotes' => '"Order Processing Notes"',
        'dcUserID' => '"DC Person"',
        'pickid' => '"Pick ID"',
        'samples' => '"Samples #"',
        'pickpack' => '"Pick Pack #"',
        'label' => '"Ship To Label"/"Edi Label"/"UPS/FEDEX"',
        'labelinfo' => 'the information for "Ship To Label"/"Edi Label"/"UPS/FEDEX"',
        'NOpallets' => '"# of Pallets"',
        'physicalhours' => '"# of Physical Labor Hrs"',
        'overtimehours' => '"# of Over Time Labor Hrs"',
        'specialinstruction' => '"Special Instructions"',
        'carriernote' => '"Carrier Note"',
        'isVAS' => '"VAS/Work-order/No VAS/Work-order"',
    ];

    public $statusOrders = [
        'WMCI' => 'Work Order Check-In',
        'WOCO' => 'Work Order Check-Out',
        'NOHO' =>  'Off Hold',
        'ONHO' => 'Hold'
    ];

    public $errorMessages = [
        'checkFieldsInDB' => '  Exist!',
        'checkFields' => '',
        'checkAllFields' => ' information is missing!',
        'nonUTFCheck' => ' has bad character(s)!',
        'integerValuesOnly' => ' only positive integer values are allowed!',
        'duplicateOrderNumbers' => ' duplicate values are not allowed!'
    ];

    public $pickListColumn = [
        'picklist',
        'packinglist',
        'prebol',
        'commercialinvoice'
    ];

    public $cartonContentLabelsColumn = [
        'cartoncontent',
        'otherlabel',
        'otherlabelinform'
    ];

    public $allowZero = [
        'samples',
        'pickpack',
        'physicalhours',
        'overtimehours'
    ];

    public $checkNFill = [
        'otherlabel',
    ];

    public $restoreCanceledOrder = [
        'preserveSpan' => ['scanOrderNumber', 'numberofcarton', 'numberofpiece',
            'totalVolume', 'totalWeight', 'pickid'],
        'restoreMenu' => ['type', 'location', 'commodity', 'dcUserID']
    ];

    public $workOrderNumbres = [];

    public $badRows = [];

    public $lenLimIndexes = [];

    public $validationIndexes = [];

    public $reqIndexes = [];

    public $dateKeys = [];

    public $ignoredIndexes = [];

    public $inputNames = [];

    public $exceedIndexes = [];

    /*
    ****************************************************************************
    */

    static function setFields(&$obj)
    {
        foreach (get_class_vars(__CLASS__) as $name => $value) {
            $obj->$name = $value;
        }
    }

    /*
    ****************************************************************************
    */
}