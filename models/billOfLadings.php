<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

use \common\order;
use \common\pdf;

class model extends base
{
    const BOL_STATUS = 'BOL';

    public $checkNumInDB = [];

    public $scanOrderValid = [];

    public $inputValues = [];

    public $duplicate;

    public $success;

    public $insertOrNot = [];

    public $missingValues = [];

    public $missingMandatoryValues = [];

    public $insertSuccess;

    public $inputFields = [];

    public $checkOutMainPage = 0;

    public $checkType = NULL;

    public $checkoutInput = 0;

    public $menu = [];

    public $dbValues = [];

    public $integerOnly = [];

    public $duplicateNumber = [];

    public $nonUTF = [];

    public $isOnHold;

    public $closedOrders = [];

    public $canceledOrders = [];

    public $onlineOrders = [];

    public $productErrors = [];

    public $products = [];

    public $missingProducts = [];

    public $orderProducts = [];

    public $dbOrderProducts = [];

    public $shortageProducts = [];

    public $splitProducts = [];

    public $ajax = NULL;

    public $results = NULL;

    public $productRowCount = 35;

    public $rowHeight = 5;

    public $leftMargin = 10;

    public $pdf = NULL;

    public $tableData = [];

    public $productTableData = [];

    public $totalColumns = [3, 9, 14, 15];

    public $tableWidth = 0;

    public $descriptionWidth = 0;

    public $radioNCheck = [
        'fedex',
        'UPS',
        'LTL',
        'routing',
        'willcall',
        'specificcarrier',
        'shiptolabels',
        'ediasn',
        'freightchargetermby',
        'freightchargetermbycollect',
        'freightchargetermbyprepaid',
        'freightchargetermby3rdparty',
        'feetermby',
        'feetermbycollect',
        'feetermbyprepaid',
        'trailerloadby',
        'trailerloadbyshipper',
        'trailerloadbydriver',
        'trailercountedby',
        'trailercountedbyshipper',
        'trailercountedbydriverpallets',
        'trailercountedbydriverpieces'
    ];

    public $radio = [
        'fedex',
        'UPS',
        'LTL',
        'routing',
        'willcall',
        'specificcarrier',
        'shiptolabels',
        'ediasn',
        'carrier',
        'freightchargetermby',
        'freightchargetermbycollect',
        'freightchargetermbyprepaid',
        'freightchargetermby3rdparty',
        'feetermby',
        'feetermbycollect',
        'feetermbyprepaid',
        'trailerloadby',
        'trailerloadbyshipper',
        'trailerloadbydriver',
        'trailercountedby',
        'trailercountedbyshipper',
        'trailercountedbydriverpallets',
        'trailercountedbydriverpieces',
    ];

    public $noFill = [
        'otherdocnofill' => [
            'otherdocument',
            'otherdocumentinform'
        ]
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

    public $checkBoxes = [
        'acceptablecustomer',
    ];

    public $freightchargetermby = [
        'freightchargetermbycollectcost',
        'freightchargetermbyprepaidcost',
        'freightchargetermby3rdpartycost',
    ];

    public $menus = [
        'commodity' => 'commodity'
    ];

    public $dbFields = [
        'shipfromid',
        'shipfromname',
        'shipfromaddress',
        'shipfromcity',
        'shiptoname',
        'shiptoaddress',
        'shiptocity',
        'carriername',
        'partyname',
        'partyaddress',
        'partycity',
        'specialinstruction',
        'commodity',
        'carriernote',
        'carrier',
        'bolid',
        'bollabel',
        'otherdocument',
        'otherdocumentinform',
        'freightchargetermby',
        'freightchargeterminfo',
        'feetermby',
        'trailerloadby',
        'trailercountedby',
        'acceptablecustomer',
        'scanOrderNumbers'
    ];
    public $dbInoreFields = [
        'scanOrderNumbers',
        'shipfromname',
        'shipfromaddress',
        'shipfromcity'
    ];

    public $dbInoreMissingFields =  [
        'scanOrderNumbers',
        'otherdocument',
        'otherdocumentinform',
        'acceptablecustomer',
        'partyname',
        'partyaddress',
        'partycity'
    ];

    public $forView = [
        'bolColumn' => [
            'Bill Of Lading Number' => 'bolid',
            'Bill Of Lading Label' => 'bollabel',
        ],
        'shipFromArea' => [
            'Name' => 'shipfromname',
            'Address' => 'shipfromaddress',
            'City/State/Country/Zip' => 'shipfromcity'
        ],
        'shiptoArea' => [
            'Name' => 'shiptoname',
            'Address' => 'shiptoaddress',
            'City/State/Country/Zip' => 'shiptocity'
        ],

        '3rdPartyArea' => [
            'Name' => 'partyname',
            'Address' => 'partyaddress',
            'City/State/Country/Zip' => 'partycity'
        ],

        'sampleColumn' => [
            'Ship To Label' => [
                'shiptolabel',
                'shiptolabelinfo'
            ],
            'Edi Label ' => [
                'eri',
                'eriinfo'
            ],
            'UPS/FEDEX' => [
                'UFlabels',
                'UFlabelsinfo'
            ]
        ],

        'freightchargetermby' => [
            'Collect' => [
                'freightchargetermbycollect',
                'freightchargetermbycollectcost'
            ],
            'Prepaid' => [
                'freightchargetermbyprepaid',
                'freightchargetermbyprepaidcost'
            ],
            '3rd Party' => [
                'freightchargetermby3rdparty',
                'freightchargetermby3rdpartycost'
            ],

        ],
        'feetermby' => [
            'Collect' => 'feetermbycollect',
            'Prepaid' => 'feetermbyprepaid',
        ],
        'trailerloadby' => [
            'By Shipper' => 'trailerloadbyshipper',
            'By Driver' => 'trailerloadbydriver',
        ], 'trailercountedby' => [
            'By Shipper' => 'trailercountedbyshipper',
            'By Driver/Pallets said contain' => 'trailercountedbydriverpallets',
            'By Driver/Pieces' => 'trailercountedbydriverpieces',
        ],

        'fedexColumn' => [
            'FedEx' => [
                'fedex',
                '3rd Party Account',
                'fedexaccount'
            ],
            'UPS' => [
                'UPS',
                '3rd Party Account',
                'upsaccount'
            ],
            'LTL' => [
                'LTL',
                '3rd Party Account',
                'ltlaccount'
            ]
        ],
    ];

    public $checkAllFields = [
        'carriername' => '"Carrier Name"',
        'carrier' => '"Carrier"',
        'commodity' => '"Commodity Description"',
        'specialinstruction' => '"Special Instructions"',
        'carriernote' => '"Carrier Note"',
        'freightchargetermby' => '"Freight Charge Terms"',
        'trailerloadby' => '"Trailer Load"',
        'trailercountedby' => '"Freight Counted"',
        'feetermby' => '"Fee Terms"',
        'bolid' => '"Bill Of Lading Number"',
        'freightchargeterminfo' => '"Freight Charge Terms Cost"'
    ];

    public $errorMessages = [
        'checkFieldsInDB' => '  Exist!',
        'checkFields' => '',
        'checkAllFields' => ' information is missing!',
        'nonUTFCheck' => ' has bad character(s)!',
        'integerValuesOnly' => ' only positive integer values are allowed!',
        'duplicateOrderNumbers' => ' duplicate values are not allowed!'
    ];

    public $otherDocument = [
        'otherdocumentinform'
    ];


    public $restoreCanceledOrder = [
        'preserveSpan' => ['scanOrderNumber', 'numberofcarton', 'numberofpiece',
            'totalVolume', 'totalWeight', 'pickid'],
        'restoreMenu' => ['type', 'location', 'commodity']
    ];

    public $integerValuesOnly = [
        'bolid' => 'Bill Of Lading Number',
        'freightchargeterminfo' => 'Freight Charge Term Cost',
    ];

    /*
    ****************************************************************************
    */

    function getBOLStatusData($BOLs, $BOLLabels)
    {

        foreach ($BOLLabels as $BOLLabel) {
            $this->closedOrders[$BOLLabel] = FALSE;
            $this->jsVars['processedOrders'][$BOLLabel] = FALSE;
        }
    }

    /*
    ****************************************************************************
    */

    function formSubmit($BOLs, $BOLLabels, $updateOrders)
    {
        foreach ($BOLLabels as $page => $BOLLabel) {
            $this->checkSubmittedValues();
            if (!$BOLLabel) {
                continue;
            }
            if (! $this->duplicateNumber && ! $this->missingValues
                && ! $this->missingMandatoryValues && ! $this->integerOnly
                && ! $this->nonUTF && ! $this->isOnHold) {

                $bolID = $this->insertNewOrderQuery();
                if (isset ($this->post['scanOrderNumbers'])) {
                    $this->insertShippingOrders($bolID, $this->post['scanOrderNumbers']);
                }
            }


        }
    }

    /*
    ****************************************************************************
    */

    function checkSubmittedValues()
    {
        $post = $this->post;

        $model = new tables\billOfLadings($this);

        $page = 0;
        $bolLabel = $post['bollabel'][$page];
        $dbFields = array_diff($this->dbFields , $this->dbInoreMissingFields);

        foreach ($dbFields as $fieldName) {
            $input = trim($this->inputValues[$fieldName][$page]);

            if ($input == "") {
                $this->missingMandatoryValues[$fieldName][$page] = TRUE;
            }
        }

        $this->processSubmittedValues($page, $bolLabel);
    }

    /*
    ****************************************************************************
    */

    function processSubmittedValues($page, $bolLabel) {
        $post = $this->post;

        foreach ($this->noFill as $noFillField => $pair) {
            if (isset($post[$pair[0]][$page]) && ! $post[$pair[1]][$page]) {
                $this->missingValues[$noFillField][$page] = TRUE;
            }
        }
        foreach ($this->integerValuesOnly as $field => $caption) {
            $value = $this->inputValues[$field][$page];
            if (($value && ! ctype_digit($value)) || $value == "") {
                $this->integerOnly[$field][$page] = TRUE;
            }
        }

    }

    /*
    ****************************************************************************
    */

    function getTDClass($key, $type=NULL)
    {
        if (isset($this->missingMandatoryValues[$key])
            || $type == 'missingValues' && isset($this->missingValues[$key])
            || $type == 'integerOnly' && isset($this->integerOnly[$key])) {

            return 'missField';
        }

        return NULL;
    }

    /*
    ****************************************************************************
    */

    function insertShippingOrders($bolID, $scanOrderNumbers)
    {
        $status = new \tables\statuses($this);
        $orders = new \tables\orders($this);
        $statusID = $status->getStatusID(self::BOL_STATUS);

        $orderIDs = $this->getOrderIDs($scanOrderNumbers);
        $this->beginTransaction();
        foreach ($orderIDs as $orderID => $orderNumber)
        {
            $sql = 'INSERT INTO shipping_orders (
                   bolID, orderID
                    ) VALUES (?, ?)';

            $this->runQuery($sql, [$bolID, $orderID]);

            $this->success = $bolID;
        }
        $this->commit();

        common\order::updateAndLogStatus([
            'orderIDs' => array_keys($orderIDs),
            'statusID' => $statusID,
            'tableClass' => $orders,
        ]);
    }

    /*
    ****************************************************************************
    */

    function getOrderIDs($scanOrderNumbers)
    {
        $qMarks = $this->getQMarkString($scanOrderNumbers);
        $sql = 'SELECT
                      id,
                      scanordernumber
                FROM  neworder
                WHERE scanordernumber IN ('. $qMarks . ')';
        $results = $this->queryResults($sql, $scanOrderNumbers);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function insertNewOrderQuery()
    {
        $checkBoxes = array_flip($this->checkBoxes);
        $fieldDBs = array_diff($this->dbFields, $this->dbInoreFields);
        for ($page = 0; $page <= 0; $page++) {
            foreach($fieldDBs as $field) {
                $sets[] = 'o.' . $field . ' = ?';

                if (isset($checkBoxes[$field])) {
                    $param[] = $this->inputValues[$field][$page] == 'checked' ?
                        1 : 0;
                } else {
                    $param[] = $this->inputValues[$field][$page];
                    $bolID = getDefault($this->inputValues['bollabel'][$page]);
                }
            }
        }

        $fields = implode(', ', $fieldDBs);
        $qMarks = $this->getQMarkString($fieldDBs);

        $sql = 'INSERT INTO shipping_info (
                   ' . $fields . '
                    ) VALUES (' . $qMarks . ')';
        $this->runQuery($sql, $param);

        return $bolID;
    }

    /*
    ****************************************************************************
    */

    function getValues($page)
    {
        $post = $this->post;

        foreach ($this->radioNCheck as $field) {
            $this->inputValues[$field][$page] = NULL;
        }

        $param = [
            'post' => $post,
            'page' => $page,
        ];
        $param['field'] = 'feetermby';

        $this->checkRadio($param);

        $param['field'] = 'trailerloadby';

        $this->checkRadio($param);

        $param['field'] = 'trailercountedby';

        $this->checkRadio($param);

        //check if it is Ecommerce or Regular
        $param['field'] = 'freightchargetermby';
        $param['input'] = 'freightchargeterminfo';
        $param['suffix'] = 'cost';

        $this->checkRadio($param);

        // check which carrier is used
        $param['field'] = 'carrier';
        $param['input'] = 'carriernote';
        $param['suffix'] = NULL;

        $this->checkRadio($param);

        // add "checked" to the checkboxes
        foreach ($this->checkBoxes as $field) {
            if (isset($post[$field][$page])) {
                $this->inputValues[$field][$page] = 'checked';
            }
        }

        $this->processGetValues($post, $page);
    }

    /*
    ****************************************************************************
    */

    function checkRadio($data)
    {
        $post = $data['post'];
        $page = $data['page'];
        $field = $data['field'];
        $input = getDefault($data['input'], NULL);
        $suffix = getDefault($data['suffix'], NULL);

        if (isset($post[$field][$page])) {

            $radio = $this->inputValues[$field][$page] = $post[$field][$page];

            $this->inputValues[$radio][$page] = 'checked';

            if ($input) {

                $note = $field == 'carrier' ? $this->carrierAndNote[$radio] :
                    $radio . $suffix;

                $this->inputValues[$input][$page] = $post[$note][$page];
            }
        } else {
            $this->inputValues[$input][$page] = NULL;
        }
    }

    /*
    ****************************************************************************
    */

    function processGetValues($post, $page)
    {
        // for the normal fill-in fields
        $specialFieldsOrig = ['carriernote', 'freightchargeterminfo'];
        $specialFields = array_merge($this->radioNCheck, $specialFieldsOrig);

        foreach ($this->inputFields as $field) {
            if (! in_array($field, $specialFields) && $field != 'statusID') {
                $this->inputValues[$field][$page] = getDefault($post[$field][$page]);
            }
        }
        $this->menuLists($page);
    }

    /*
    ****************************************************************************
    */

    public function menuLists ($page)
    {
        //choose user, vendor, shipping from and type
        foreach ($this->menus as $property => $index) {

            $target = isset($this->post[$index][$page]) ?
                $this->post[$index][$page] : $this->dbValues[$page][$index];

            $fieldKeys = array_keys($this->$property);

            foreach ($fieldKeys as $id) {
                $this->menu[$index][$id][$page] = $id == $target ?
                    'selected' : NULL;
            }
        }
    }

}
