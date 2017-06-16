<?php

namespace common;

class workOrders {

    const ACTUAL = 'a';
    const ESTIMATED = 'e';

    const RUSH = 'r';
    const OVERTIME = 'o';

    const STATUS_CHECK_IN = 'WOCI';
    const STATUS_CHECK_OUT = 'WOCO';

    /*
    ****************************************************************************
    */

    static function getView($app, $isCheckOut=FALSE)
    {
        $workOrderLaborTables =
                \common\workOrderLabor::getWorkOrderLaborTables($app);

        ob_start(); ?>

        <div class="woDialog">
            <strong>
                Work Order Number: <span class="woDialogWorkOrderNumber"></span>
            </strong>
            <br>
            <div class="headerErrors errors"></div>
            <strong>
                <span class="red">*</span> Mandatory Fields
            </strong>
            <table height="100" width="100%">
                <tr>
                    <td width="50%" class="tableCell">
                        <table border="1" width="100%">
                            <tr>
                                <td>Location: </td>
                                <td>
                                    <strong>
                                        <span class="woDialogLocation"></span>
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td>Client Name: </td>
                                <td>
                                    <strong>
                                        <span class="woDialogClient"></span>
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td><span class=red>*</span>
                                    <span class="woDialogRequestDateTitle">
                                        Request Date
                                    </span>:
                                </td>
                                <td>
                                    <strong>
                                        <input type="text"
                                               class="datepicker mandatory woDialogRequestDate"
                                               data-name="requestDate"
                                               data-class="woDialogRequestDate">
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td><span class=red>*</span>
                                    <span class="woDialogCompleteDateTitle">
                                        Complete Date
                                    </span>:
                                </td>
                                <td>
                                    <strong>
                                        <input type="text"
                                               class="datepicker mandatory woDialogCompleteDate"
                                               data-name="completeDate"
                                               data-class="woDialogCompleteDate">
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td><span class=red>*</span>
                                    <span class="woDialogClientWorkOrderNumberTitle">
                                        Client Work Order Number
                                    </span>:
                                </td>
                                <td>
                                    <strong>
                                        <input type="text"
                                               class="mandatory woDialogClientWorkOrderNumber"
                                               data-name="clientWorkOrderNumber"
                                               data-class="woDialogClientWorkOrderNumber">
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td width="50%" class="tableCell">
                        <table cellspacing="2" border="1" width="100%">
                            <tr>
                                <td colspan="2">
                                    <input type="checkbox"
                                           class="woRelatedToCustomer">
                                    <strong>
                                        This is related to a customer order?
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td>Order Number:</td>
                                <td>
                                    <strong>
                                        <span class="woDialogOrderNumber"></span>
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td>Ship Date:</td>
                                <td>
                                    <strong>
                                        <span class="woDialogShipDate"></span>
                                    </strong>
                                </td>
                            </tr>
                            <tr>

                                <td><span class=red>*</span>
                                    <span class="woDialogRequestByTitle">
                                        Request By
                                    </span>:
                                </td>
                                <td>
                                    <strong>
                                        <input type="text"
                                               class="datepicker mandatory woDialogRequestBy"
                                               data-name="requestBy"
                                               data-class="woDialogRequestBy">
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td>User:</td>
                                <td>
                                    <strong>
                                        <span class="woDialogUser"></span>
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td><span class=red>*</span>
                                    <span class="woDialogWorkingHoursTitle">
                                        Rush Labor Amount ($)
                                    </span>:
                                </td>
                                <td>
                                    <strong>
                                        <input type="text"
                                               class="mandatory woDialogWorkingHours"
                                               data-name="workingHours"
                                               data-class="woDialogWorkingHours">
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td><span class=red>*</span>
                                    <span class="woDialogWorkingHoursTitle">
                                        Overtime Labor Amount ($)
                                    </span>:
                                </td>
                                <td>
                                    <strong>
                                        <input type="text"
                                               class="mandatory woDialogWorkingHours"
                                               data-name="otWorkingHours"
                                               data-class="woDialogOtWorkingHours">
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <br>
            <div class="laborErrors errors"></div>
            <table class="workOrderLaborContainer" width="100%">
                <tr>
                    <td valign="top" width="50%">

        <?php

        $categories = array_keys($workOrderLaborTables);

        $rowCount = count($categories);

        $midPoint = round($rowCount / 2);

        $index = 1;

        foreach ($workOrderLaborTables as $category => $labors) { ?>

                        <table width="100%" border="1" class="workOrderLabor"
                               data-code="<?php echo $category; ?>">

                            <tr>
                                <th colspan="2" bgcolor="#CCC">
                                    <?php echo $category; ?>
                                </th>
                            </tr>

            <?php foreach ($labors as $chargeCode => $labor) { ?>

                            <tr class="quantityContainer"
                                data-code="<?php echo $chargeCode; ?>">

                                <td class="laborCheckBoxes">
                                    <input type="checkbox" class="checkBoxes">
                                    <span class="titleNames">
                                        <?php echo $labor['text']; ?>
                                    </span>
                                </td>
                                <td class="laborValues" nowrap>
                                    Qty:
                                    <input type="textbox" size="6" class="woQty"
                                           data-id="<?php echo $labor['id']; ?>">
                                </td>
                            </tr>

                <?php } ?>

                        </table>

            <?php if ($index++ == $midPoint) { ?>

                    </td>
                    <td valign="top" width="50%">

            <?php }
        } ?>

                    </td>
                </tr>
            </table>

            <br>

            <?php if ($isCheckOut) { ?>

                <span class=red>*</span>

            <?php } ?>

            <strong>Please provide work details</strong>
            <br>
            <div class="detailsErrors errors"></div>
            <textarea class="woDialogDetails" rows="2" cols="100"></textarea>
            <hr>
        </div>

        <?php

        return ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    static function getWorkingHours($app, $workOrderNumbers)
    {
        if (! $workOrderNumbers) {
            return [];
        }

        $qMarks = $app->getQMarkString($workOrderNumbers);

        $sql = 'SELECT    type,
                          amount
                FROM      wrk_hrs_wo r
                JOIN      wo_hdr h ON h.wo_id = r.wo_id
                WHERE     wo_num IN (' . $qMarks . ')
                ORDER BY  id';

        $results = $app->queryResults($sql, $workOrderNumbers);

        $labor = [
                'rushAmt'  => getDefault($results[self::RUSH]['amount']),
                'otAmt'   => getDefault($results[self::OVERTIME]['amount'])
            ];

        return array_map('floatval', $labor);
    }

    /*
    ****************************************************************************
    */

    static function storeWorkingHours($app, $data)
    {
        $actual = getDefault($data['actual']);

        $category = json_decode($actual) ? 'a' : 'e';

        $rushAmount = $data['workingHours'];
        $otAmount = $data['otWorkingHours'];

        $sql = 'INSERT INTO wrk_hrs_wo (
                    wo_id,
                    type,
                    amount,
                    cat,
                    create_by
                ) VALUES (
                    ?, ?, ?, ?, ?
                )';

        $app->runQuery($sql, [
            $data['workOrderID'],
            self::RUSH,
            $rushAmount,
            $category,
            $data['userID']
        ]);

        $app->runQuery($sql, [
            $data['workOrderID'],
            self::OVERTIME,
            $otAmount,
            $category,
            $data['userID']
        ]);
    }

    /*
    ****************************************************************************
    */

    static function updateWorkOrders($app, $data)
    {
        $workOrderHeaders = new \tables\workOrders\workOrderHeaders($app);
        $workOrderDetails = new \tables\workOrders\workOrderDetails($app);

        $workOrderNumbers = array_keys($data['workOrderNumbers']);

        $workOrderIDs = $workOrderHeaders->getByWorkOrderNumber($workOrderNumbers);
        $nextID = $workOrderHeaders->getNextID('wo_hdr');
        $userID = \access::getUserID();

        $app->beginTransaction();

        foreach ($data['workOrderNumbers'] as $workOrderNumber => $params) {

            $params['userID'] = $userID;
            $params['workOrderNumber'] = $workOrderNumber;
            $params['actual'] = getDefault($data['actual']);
            $params['workOrderID'] =
                    getDefault($workOrderIDs[$workOrderNumber]['wo_id'], $nextID++);

            $workOrderHeaders->updateHeader($params);
            $workOrderDetails->updateLabor($params);

            self::storeWorkingHours($app, $params);
        }

        $app->commit();
    }

    /*
    ****************************************************************************
    */

}
