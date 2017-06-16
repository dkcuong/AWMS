<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function createCycleCountView()
    {
        $dataSubmit = [
            'reportName' => getDefault($this->post['report-name']),
            'reportDescription' => getDefault($this->post['report-description']),
            'createDate' => getDefault($this->post['create-date']),
            'warehouse' => getDefault($this->post['warehouse']),
            'assignedTo' => getDefault($this->post['assigned-to']),
            'filterBy' => getDefault($this->post['filterBy']),
            'customer' => getDefault($this->post['customer']),
            'sku' => getDefault($this->post['sku']),
            'byLocationFrom' => getDefault($this->post['by-location-from']),
            'byLocationTo' => getDefault($this->post['by-location-to']),
            'cycleCountByOUM' => getDefault($this->post['cycleCountByOUM'], 'carton'),
            'cycleCountByColorSize' => getDefault($this->post['cycleCountByColorSize'])
        ];
        ?>

        <?php if (! $this->isStaffUser) { ?>

        <form method="post" role="form" id="create-report-form">
            <h2 style="text-align: center; margin: 5px 0;">Create Cycle Count</h2>
            <hr>
            <table class="create-report-cycle-count">
                <tbody>
                <tr>
                    <td colspan="1">
                        <label>Report Name</label> (<span class="red">*</span>):
                    </td>
                    <td colspan="1">
                        <input type="text" id="report-name"
                               value="<?php echo $dataSubmit['reportName'];?>">
                    </td>
                    <td colspan="1">
                        <label>Description:</label>
                    </td>
                    <td colspan="1">
                        <input type="text" id="report-description"
                               value="<?php echo $dataSubmit['reportDescription'];?>">
                    </td>
                    <td colspan="1">
                        <label for="">Due Date</label> (<span class="red">*</span>):
                    </td>
                    <td colspan="1">
                        <input type="text"
                               name="create-date"
                               id="datepicker"
                               value="<?php echo $dataSubmit['createDate'];?>">
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="warehouse-name">
                        <label>Warehouse </label> (<span class="red">*</span>):
                        <select class="warehouse" id="warehouse-input"
                                name="warehouse" data-post>
                            <option value="0">Select a Warehouse</option>
                            <?php

                            foreach ($this->warehouse as $id => $row) {
                                $selected = $dataSubmit['warehouse'] == $id ? 'selected' : '';
                            ?>

                                <option <?php echo $selected;?> value="<?php echo $id; ?>">

                                    <?php echo $row['displayName']; ?>

                                </option>

                            <?php } ?>

                        </select>
                    </td>
                    <td colspan="1">
                        <label for="">Assigned To</label> (<span class="red">*</span>):
                    </td>
                    <td>
                        <select class="assigned" id="assigned-input" name="assigned-to" data-post>
                            <option value="0">Select a user</option>

                            <?php
                            foreach ($this->users as $id => $row) {
                                $selected = $dataSubmit['assignedTo'] == $id ? 'selected' : '';
                            ?>

                                <option <?php echo $selected;?> value="<?php echo $id; ?>">

                                    <?php echo $row['lastFirst']; ?>
                                </option>

                            <?php } ?>

                        </select>
                    </td>
                    <td colspan="1"><label for="">Type: </label></td>
                    <td>
                        <input type="radio" name="unknoenType" value="location"> RF GUN
                        <input type="radio" name="unknoenType" value="location" checked> PAPER
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>By</label> (<span class="red">*</span>):
                    </td>
                    <td>

                        <?php $check = $dataSubmit['filterBy'] == 'CS' ? 'checked' : ''; ?>

                        <input type="radio" name="filterBy" value="CS"
                            <?php echo $check;?> id="filterByCustomer"> Customer
                    </td>
                    <td colspan="2">

                        <?php $check = $dataSubmit['filterBy'] == 'SK' ? 'checked' : ''; ?>

                        <input type="radio" name="filterBy" value="SK"
                               id="filterBySKU" <?php echo $check;?>> SKU list
                    </td>
                    <td>

                        <?php $check = $dataSubmit['filterBy'] == 'LC' ? 'checked' : ''; ?>

                        <input type="radio" name="filterBy" value="LC"
                               id="filterByLocation" <?php echo $check;?>> Location
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="by-customer">
                        <label for="">Customer: </label>
                        <select class="vendorSize" id="customer-input" name="customer" data-post>
                            <option value="0">Select a Client</option>

                            <?php
                            foreach ($this->vendors as $id => $row) {
                                $selected = $dataSubmit['customer'] == $id ? 'selected' : '';
                            ?>

                                <option <?php echo $selected?> value="<?php echo $id; ?>">

                                    <?php echo $row['fullVendorName']; ?>

                                </option>

                            <?php } ?>

                        </select>
                    </td>
                    <td colspan="2" id="by-sku">
                        <label for="">SKU List: </label>
                        <textarea type="text" name="sku" id="sku-input">
                            <?php echo $this->errors ? $dataSubmit['sku'] : '';?>
                        </textarea>
                    </td>
                    <td rowspan="1">
                        <label for="">Location from: </label><br>
                        <label for="">Location to: </label>
                    </td>
                    <td rowspan="1" colspan="1" class="by-location">
                        <input type="text" name="by-location-from" id="location-input-from"
                               placeholder="(autocomplete)"
                               value="<?php echo $dataSubmit['byLocationFrom'];?>"><br>
                        <input type="text" name="by-location-to" id="location-input-to"
                               placeholder="(autocomplete)"
                               value="<?php echo $dataSubmit['byLocationTo'];?>">
                    </td>
                </tr>
                <tr>
                    <td colspan="1">
                        <label for="">Cycle Count by UOM:</label>
                    </td>
                    <td>

                        <?php $check = $dataSubmit['cycleCountByOUM'] == 'carton' ? 'checked' : ''; ?>

                        <input type="radio" name="cycleCountByOUM" value="carton"
                               id="cycleCountByCARTON" <?php echo $check;?>> CARTON

                        <?php $check = $dataSubmit['cycleCountByOUM'] == 'each' ? 'checked' : ''; ?>

                        <input type="radio" name="cycleCountByOUM" value="each"
                               id="cycleCountByEACH" <?php echo $check;?>> EACH
                    </td>
                    <td colspan="2">

                        <?php $check = $dataSubmit['cycleCountByColorSize'] ? 'checked' : ''; ?>

                        <label for="">Cycle Count by Color & Size:</label>
                        <input type="radio" name="cycleCountByColorSize" value="1" <?php echo $check;?>> Yes

                        <?php $check = ! $dataSubmit['cycleCountByColorSize'] ? 'checked' : ''; ?>

                        <input type="radio" name="cycleCountByColorSize" value="0"  <?php echo $check;?>> No
                    </td>
                </tr>
                <tr>
                    <td id="notification" colspan="5"></td>
                    <td colspan="2" id="create-report-button">
                        <button type="button" id="create-cycle">Create Report</button>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <label>Search SKU:</label>
                        <input type="text" id="search-sku-input" value="" placeholder="(autocomplete)">

                        <button type="button" id="search-sku">Search</button>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>

        <?php } ?>

        <?php
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }

    /*
    ****************************************************************************
    */

    function viewCycleCountView()
    {
        ?>

        <form method="post" role="form" id="cycle-detail">
            <div class="cycle-detail">
                <h2 class="page-title" >Detail Cycle Count

                    #<?php echo $this->data['cycle_count_id'] ?>

                </h2>
                <hr>
                <table>
                    <tbody>
                    <tr>
                        <input type="hidden" value="<?php echo $this->data['whs_id']; ?>"
                               name="warehouse-id" id="warehosue-id">
                        <td colspan="1">
                            <label for="">Cycle Name:</label>
                        </td>
                        <td colspan="1">
                            <input id="cycle-name" value="<?php echo $this->data['name_report'];?>"
                                   disabled class="input-disabled">
                        </td>
                        <td colspan="1">
                            <label for="">Description:</label>
                        </td>
                        <td colspan="1">
                            <input disabled id="cycle-description"
                                   class="input-disabled no-resize"
                                   value="<?php echo $this->data['descr'];?>">

                        </td>
                        <td rowspan="1">
                            <label for="">Status: </label>
                        </td>
                        <td rowspan="1" colspan="1" class="status">
                            <input id="cycle-status" disabled
                                   value="<?php echo $this->data['status'];?>"
                                   class="input-disabled">
                        </td>
                    </tr>
                    <tr>
                        <td rowspan="1">
                            <label for="">Assigned by: </label>
                        </td>
                        <td rowspan="1" colspan="1" class="searchByDate">
                            <input disabled value="<?php echo $this->data['assigneeBy'];?>"
                                   class="input-disabled">
                        </td>
                        <td colspan="1">
                            <label for="">Assigned to:</label>
                        </td>
                        <td colspan="1">
                            <input disabled value="<?php echo $this->data['assigner'];?>"
                                   class="input-disabled">
                        </td>
                        <td colspan="1">
                            <label for="">Due Date:</label>
                        </td>
                        <td colspan="1">
                            <input disabled id="due-date" type="date"
                                   value="<?php echo $this->data['due_dt'];?>"
                                   class="input-disabled">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="1">
                            <label for="">Cycle Type:</label>
                        </td>
                        <td colspan="1">
                            <input disabled
                                   value="<?php echo $this->data['cycleType'];?>"
                                   name="pcs" class="input-disabled">
                        </td>
                        <td colspan="1">
                            <label for="">By UOM:</label>
                        </td>
                        <td colspan="1">
                            <input disabled
                                   value="<?php echo trim(strtolower($this->data['cycle_count_by_uom'])) == 'carton'
                                       ? 'CARTON' : 'EACH'; ?>" name="pcs" class="input-disabled">
                        </td>
                        <td rowspan="1">
                            <label for="">By Color & Size: </label>
                        </td>
                        <td rowspan="1" colspan="1" class="bySizeColor">
                            <input disabled value="<?php echo $this->data['bySizeColor']
                                ? 'Yes' : 'No'; ?>" class="input-disabled">
                        </td>
                    </tr>
                    <tr>
                        <td id="notification" colspan="6"></td>
                    </tr>
                    <tr>
                        <td class="action-button-top" rowspan="1" colspan="6">

                            <?php if ($this->isEdit && $this->canUpdate) {
                                echo $this->searcherAddRowButton;
                            } ?>

                            <a href="<?php echo $this->printPdfUrl;?>" target="_blank"
                               id="print-pdf">Print</a>

                            <?php if ($this->isEdit && $this->canUpdate) { ?>

                                <a href="#" id="adjust">Save</a>

                            <?php } ?>

                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </form>

        <?php echo $this->datatablesStructureHTML;?>

        <div class="action-button-bottom">

            <?php if ($this->isEdit && $this->canUpdate) {
                echo $this->searcherAddRowFormHTML;?>

                <a href="#" id="add-new-SKU" class="add_row">Add SKU</a>

            <?php } ?>

            <a href="<?php echo $this->printPdfUrl;?>" target="_blank"
               id="print-pdf-bottom">Print</a>

            <?php if ($this->isEdit && $this->canUpdate) { ?>

                <a href="#" id="adjust-bottom">Save</a>

            <?php } ?>

        </div>
        <?php
    }

    /*
    ****************************************************************************
    */

    function auditCycleCountView()
    {

        ?>

        <div class="cycle-audit">
            <h2 style="text-align: center; margin: 5px 0;">Create Cycle Audit

                #<?php echo $this->data['cycle_count_id'];?>

            </h2>
            <hr>
            <form id="searchForm" name="searcher" method="post">

            </form>
            <table width="100%">
                <tbody>
                <tr>
                    <td colspan="1">
                        <b>Name: </b>
                    </td>
                    <td colspan="1">
                        <label><?php echo $this->data['name_report'];?></label>
                    </td>
                    <td>
                        <b>Description: </b>
                    </td>
                    <td colspan="5">

                        <?php echo $this->data['descr'];?>

                    </td>
                </tr>
                <tr>
                    <td colspan="1">
                        <b>Type: </b>
                    </td>
                    <td colspan="1">
                        <label><?php echo $this->data['cycleType'];?></label>
                    </td>

                    <td colspan="1">
                        <b>UOM: </b>
                    </td>
                    <td colspan="1">
                        <label><?php echo strtoupper($this->data['cycle_count_by_uom']);?></label>
                    </td>

                    <td colspan="1">
                        <b>Date: </b>
                    </td>
                    <td colspan="1">
                        <label><?php echo date('m-d-Y');?></label>
                    </td>

                </tr>
                <tr>
                    <td colspan="1">
                        <b>Assigned by: </b>
                    </td>
                    <td colspan="1">
                        <label><?php echo $this->data['assigneeBy'];?></label>
                    </td>
                    <td colspan="1">
                        <b>Assigned to: </b>
                    </td>
                    <td colspan="1">
                        <label><?php echo $this->data['assigner'];?></label>
                    </td>

                    <td rowspan="1">
                        <b>Cycle complete date:</b>
                    </td>
                    <td rowspan="1" colspan="1" class="searchByDate">
                        <label><?php echo date('m-d-Y', strtotime($this->data['due_dt']));?></label>
                    </td>
                </tr>
                <tr>
                </tr>

                <tr>
                    <td colspan="1">
                        <b>Display: </b>
                    </td>
                    <td>
                        <input class="display_by_input" type="radio" name="displayBy" value="discrepancies" checked id="displayByDiscrepancies">
                        <label  class="display_by_label" for="displayByDiscrepancies">Discrepancies</label>
                    </td>
                    <td>
                        <input class="display_by_input" type="radio" name="displayBy" value="accepted" id="displayByAccepted">
                        <label class="display_by_label" for="displayByAccepted">Accepted</label>
                    </td>
                    <td>
                        <input class="display_by_input" type="radio" name="displayBy" value="recount" id="displayByRecount">
                        <label class="display_by_label" for="displayByRecount">Recount</label>
                    </td>
                    <td>
                        <input class="display_by_input" type="radio" checked="true" name="displayBy" value="all" id="displayByAll">
                        <label  class="display_by_label" for="displayByAll">All</label>
                    </td>
                </tr>

                </tbody>
            </table>
        </div>
        <div class="list-button">

            <a href="#" class="button-action selectAll">Select All</a>
            <a href="#" class="button-action  deselectAll">Deselect All</a>
            <a href="<?php echo $this->printPdfUrl;?>"
               target="_blank" id="print-pdf">Print</a>

            <?php
            if (! $this->isCycleComplete) { ?>

                <a class="button-action" href="#"
                   onclick="callAccept(event);">Accept</a>
                <a class="button-action" href="#"
                   onclick="callRecount()">Recount</a>

            <?php } ?>

        </div>

        <?php
        echo $this->datatablesStructureHTML;
        ?>

        <div class="list-button">
            <a href="#" class="button-action selectAll">Select All</a>
            <a href="#" class="button-action deselectAll">Deselect All</a>
            <a href="<?php echo $this->printPdfUrl;?>"
               target="_blank" id="print-pdf">Print</a>

            <?php
            if (! $this->isCycleComplete) { ?>

                <a class="button-action" href="#"
                   onclick="callAccept()">Accept</a>
                <a class="button-action" href="#"
                   onclick="callRecount()">Recount</a>

            <?php } ?>

        </div>

        <?php
    }
}
