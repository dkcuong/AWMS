<?php

namespace tables;

class orderLabels extends _default
{
    static $labelTitle = 'Order Label';
    static $labelsTitle = 'Order Labels';
    public $ajaxModel = 'orderLabels';
   
    public $labelWidth = 40;

    public $fields = [
        'barcode' => [
            'select' => 'LPAD(
                            CONCAT(userID, o.assignNumber),
                            10, 0
                        )',
            'display' => 'Order Label',
        ],
        'dateEntered' => [
            'select' => 'DATE(dateEntered)',
            'display' => 'Date Entered',
        ],
        'batch' => [
            'display' => 'Label Batch',
        ],
        'assignNumber' => [
            'display' => 'Order ID',
        ],
        'fullName' => [
            'select' => 'IF (o.name IS NOT NULL, 
                             o.name,
                             CONCAT(firstName, " ", lastName)
                         )',
            'display' => 'Creator',
        ],
        'username' => [
            'select' => 'IF (o.username IS NOT NULL, 
                             o.username,
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

        return 'neworderlabel o
               LEFT JOIN '.$userDB.'.info u ON u.id = userID';
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
            'labelType' => 'order',
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
        $imagePath = makeLink('barcode', 'display', ['text', $orderLable, 'noText']);
        
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
        <?php 
        $this->app->pdf->html = ob_get_clean();
        $this->app->pdf->writePDFPage()->writePDFPage()
                       ->writePDFPage()->writePDFPage();
        
        return $this;
    }

    /*
    ****************************************************************************
    */
}