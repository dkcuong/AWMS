<?php

namespace tables;

class workOrderLabels extends _default
{
    static $labelTitle = 'Work Order Label';
    static $labelsTitle = 'Work Order Labels';

    public $barcodePage = 'barcodeneworder.php?labelType=work';

    public $ajaxModel = 'workOrderLabels';
    
    public $labelWidth = 40;
    
    public $fields = [
        'barcode' => [
            'select' => 'LPAD(
                            CONCAT(userID, w.assignNumber),
                            10, 0
                        )',
            'display' => 'Work Order Label',
        ],
        'dateEntered' => [
            'select' => 'DATE(dateEntered)',
            'display' => 'Date Entered',
        ],
        'batch' => [
            'display' => 'Label Batch',
        ],
        'assignNumber' => [
            'display' => 'Work Order ID',
        ],
        'fullName' => [
            'select' => 'IF (w.name IS NOT NULL, 
                             w.name,
                             CONCAT(firstName, " ", lastName)
                        )',
            'display' => 'Creator',
        ],
        'username' => [
            'select' => 'IF (w.username IS NOT NULL, 
                             w.username,
                             u.username
                        )',
            'display' => 'Username',
        ],
    ];

    /*
    ****************************************************************************
    */
    
    function table()
    {
        $userDB = $this->app->getDBName('users');

        return $table = 'workOrderLabel w
            JOIN '.$userDB.'.info u ON u.id = userID';
    }

    /*
    ****************************************************************************
    */
    
    function insert($userID, $quantity)
    {
        return \common\labelMaker::inserts([
            'model' => $this,
            'userID' => $userID,
            'quantity' => $quantity,
            'labelType' => 'work',
        ]);
    }

    /*
    ****************************************************************************
    */

    function getInfo(&$orderLabel)
    {
        $sql = 'SELECT  '.$this->getSelectFields().' 
                FROM     '.$this->table.'
                WHERE    '.$this->fields['barcode']['select'].' = ?';

        $orderLabel = $this->app->queryResult($sql, [$orderLabel]);
    }


    /*
    ****************************************************************************
    */

    function addToPDF($orderLable, $date)
    {
        $imagePath = makeLink('barcodes', 'display', ['text', $orderLable, 'noText']);
        $orderLable = [$orderLable];
        ob_start(); ?>
        <html>
        <head>
        <style>
            table {
            width: 100%;
                border-collapse: collapse;
            }       
            tr {

            }
            td {
                width: 38mm;
                height: 21.2mm;
                margin: 0 1mm;
                text-align: center;
                vertical-align:middle; 
            }
            img {
                width: 135px;
                height: 40px;
            }
        </style>
        </head>
        <body>
        <table border="1" >
            <?php
            foreach ($orderLable as $oneLabel) { ?>
                <tr><?php
                for ($x=0; $x<=2; $x++) { ?>
                    <td align="center" height="105">
                        <font size="4"> New Order <BR><?php echo $oneLabel; ?> 
                        <?php echo $date; ?><BR></font>
                        <img src="<?php echo $imagePath; ?>">
                    </td>
                    <?php
                } ?>
                </tr><?php    	      
            } ?>
        </table>
        </body>
        </html>        
        <?php die;
        $this->app->pdf->html = ob_get_clean();
        $this->app->pdf->writePDFPage()->writePDFPage()
                       ->writePDFPage()->writePDFPage();
        
        return $this;
    }

    /*
    ****************************************************************************
    */

}