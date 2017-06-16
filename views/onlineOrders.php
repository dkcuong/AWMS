<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function importOnlineOrdersView()
    {
        ?>
        <span class="message">
            Upload an online orders excel file here:
            <form id="importOrders" method="post" enctype="multipart/form-data">
                <select id="vendorID" name="vendorID">
                <option value="0">Select Vendor</option>
                <?php foreach ($this->vendors as $id => $vendor) { ?>
                    <option value="<?php echo $id; ?>">
                        <?php echo $vendor['fullVendorName']; ?></option>
                <?php } ?>
                </select>
                <select id="dealSiteID" name="dealSiteID">
                <option value="0">Select Deal Site</option>
                <?php foreach ($this->dealSites as $id => $site) { ?>
                    <option value="<?php echo $id; ?>">
                        <?php echo $site['displayName']; ?></option>
                <?php } ?>
                </select>
                <input type="file" name="file" id="file">
                <input type="submit" name="submit" value="Submit">
            </form> <?php

            if ($this->fileSubmitted && $this->importError()
                    && getDefault($this->importer->badRows)) { ?>

                <form method="post">
                    <input type="submit" name="exportBad"
                           value="Export Failed Rows">
                </form>

            <?php }

            if ($this->fileSubmitted &&
                    getDefault($this->importer->errorOrders)) { ?>

                <form method="post">
                    <input type="submit" name="exportErrorOrders"
                           value="Export Error Orders">
                </form>

        <?php } ?>

        </span>
        <form method="post">
            <?php echo datatables\structure::tableHTML(); ?>
            <input type="submit" name="export" value="Export Excel">
            <input type="submit" name="template" value="Download Import Template">
        </form>
        <?php
    }

    /*
    ****************************************************************************
    */

    function listExportedOnlineOrdersView()
    {
        if ($this->shortages) { ?>

        <table id="rejected">
            <tr>
                <td colspan="4"><b>Export was stopped due to lack of inventory:</b></td>
            </tr>
            <tr>
            <tr>
              <td>UPC</td>
              <td>Seldat Order Number</td>
              <td>Client Order Number</td>
              <td>Shortage</td>
            </tr> <?php

            foreach ($this->shortages as $values) { ?>

            <tr>
              <td><?php echo $values['upc']; ?></td>
              <td><?php echo $values['scanOrderNumber']; ?></td>
              <td><?php echo $values['clientOrderNumber']; ?></td>
              <td><?php echo $values['quantity']; ?></td>
            </tr> <?php

            } ?>

            </tr>
        </table> <?php

        } else {
            echo $this->searcherHTML;
            echo $this->datatablesStructureHTML;
            echo $this->searcherExportButton;
        }
    }

    /*
    ****************************************************************************
    */

    function listFailsOnlineOrdersView()
    {
        ?>
        <form method="post">
            <?php echo datatables\structure::tableHTML('onlineOrdersFails'); ?>
            <input type="submit" name="export" value="Export Excel">
        </form>
        <?php
    }

    /*
    ****************************************************************************
    */



    function incorrectOnlineOrdersView()
    {
        ?>

            <?php echo datatables\structure::tableHTML('incorrectOnlineOrders'); ?>

        <?php
    }

    /*
    ****************************************************************************
    */

    function importCarrierOnlineOrdersView()
    {
        ?>
        <span class="message">
            Import order information from a carrier:
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="file" id="file">
                <input type="submit" name="submit" value="Submit">
            </form> <?php

            if ($this->fileSubmitted && $this->importer->importError()
                    && getDefault($this->importer->badRows)) { ?>

                <form method="post">
                    <input type="submit" name="exportBad"
                           value="Export Failed Rows">
                </form>
      <?php } ?>
        </span>
        <form method="post">
            <?php echo datatables\structure::tableHTML(); ?>
            <input type="submit" name="export" value="Export Excel">
        </form>
        <?php
    }

    /*
    ****************************************************************************
    */

    function searchOnlineOrdersView()
    {
        $this->ajax->multiSelectTableView($this);
    }

    /*
    ****************************************************************************
    */

    function editDirectoriesOnlineOrdersView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
        echo $this->searcherAddRowButton;
        echo $this->searcherAddRowFormHTML;
    }

    /*
    ****************************************************************************
    */

    function wavePicksOnlineOrdersView()
    {
        echo $this->searcherHTML;
        echo datatables\structure::tableHTML('create');
        echo $this->searcherExportButton;
    }

    /*
    ****************************************************************************
    */

    function listUpdateFailsOnlineOrdersView()
    {
        ?>
        <form method="post">
            <?php echo datatables\structure::tableHTML('onlineOrdersFailsUpdate'); ?>
            <input type="submit" name="export" value="Export Excel">
        </form>
        <?php
    }

    /*
    ****************************************************************************
    */

    function openOnlineOrdersReportOnlineOrdersView()
    {
        $this->ajax->warehouseVendorMultiSelectTableView($this, 'openOnlineOrdersReportOnlineOrders');
    }

    /*
    ****************************************************************************
    */
}