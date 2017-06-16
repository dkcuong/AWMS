<?php 
    $invoiceID = '#' . $data['invoiceID'];
    $type = $data['type'];
    $dateDue = $data['dateDue'];
    $amountDue = '$' . $data['amountDue'];
    $companyName = $data['companyName'];
    $phone = $data['phone'];
?>
<table width="100%" style="border: 2px solid #c4d0de; font-size: 16px;" 
       cellpadding="0" cellspacing="0"> 
    <tr style="background: #6b8da8; ">
        <td style="height: 32px; padding: 0px 22px;">
            <span style="text-transform: uppercase; color: #fff;
                  font-size: 16px;">
                <?php echo $companyName; ?></span>
        </td>
    </tr>
    <tr style="background: #e5ecf4; height: 70px;">
        <td style="padding: 0px 22px;">
            <table width="100%">
                <tr>
                    <td>
                        <div style="display: block; float: left; width: 40%;">
                                <span style="color: #3e4146; font-weight: bold;
                                      font-size: 22px; display: block;">
                                    Invoice
                                </span>	
                                <span style="color: #89a3bc; font-size: 18px;
                                      font-style: italic;">
                                    <?php echo $invoiceID; ?></span>
                        </div>
                        <div style="display: block; float: left; width: 60%;">
                                <span style="color: #f4b572;;font-size: 13px;
                                      font-style: italic; float: left; 
                                      margin-top: 6px;">
                                    Due <?php echo $dateDue; ?></span>	
                        </div>
                    </td>
                    <td>
                        <div  style="float: right;">
                                <span style="color: #848b9d;">Amount Due:</span>
                                <span style="font-size: 30px; color: #494e52;">
                                    <?php echo $amountDue; ?></span>
                        </div> 
                    </td>
                </tr>

            </table>
        </td>
    </tr>
    <tr>
        <td style="padding: 0px 22px;">
            <table style ="font-size: 17px;">
                <tr>
                    <td>
                            <br><span >Dear Customer:</span><br><br>
                    </td>
                </tr>
                <tr>
                    <td>
                            Your invoice is attached. Please remit payment at 
                            your earliest convenience.<br><br>
                    </td>
                </tr>
                <tr>
                    <td>
                            Thank you for your business - we appreciate it 
                            very much.<br><br>
                    </td>
                </tr>
                <tr>
                    <td>
                            Sincerely,<br><br>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span style="text-transform: uppercase;">
                            <?php echo $companyName; ?></span><br>
                        <a href="tel:732-750-0505"><?php echo $phone; ?></a>
                        <br><br>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <div style="display: block;height: 20px; width: 100%; 
                 background: #fff; border-top: 4px solid #e5e9f4;">
                
            </div>
        </td>			
    </tr>
</table>
<div style="display: block; height: 20px; width: 100%; 
     background: #1e3346; float: left;"></div>