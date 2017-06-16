<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{
    function listTransfersView()
    {
        if (! $this->isClient) {?>

        <div class="transferMezzanine" id="searcher">
            <form id="formTransferMezzanine" method="post"
                  action="<?php echo $this->jsVars['urls']['urlProcessImport'] ?>"
                  enctype="multipart/form-data" target="_blank">
                Import Transfer Files
                <span style="color: #cc0000; font-weight: bold">
                    (only accept Excel file)
                </span>
                <input multiple type="file" name="file"
                       accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
                <input type="submit" value="Import" id="btn-submit">
                <a style="margin-left: 100px" href="./import/template"
                   target="_blank">Download Template Import
                </a>
            </form>
        </div>

        <?php }

        $this->ajax->multiSelectTableView($this);
    }

    /*
    ****************************************************************************
    */

    function importTransfersView()
    { ?>

        <div class="red"><?php echo $this->jsVars['messageError']; ?></div>

    <?php }

    /*
    ****************************************************************************
    */

    function displayTransfersView()
    { ?>

        <div class="red"><?php echo $this->jsVars['messageError']; ?></div>

    <?php }

    /*
    ****************************************************************************
    */
}