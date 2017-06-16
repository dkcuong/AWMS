<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function addNewUPCsSeldatOriginalView()
    {?>
        <span class="message">
            Upload an UPC(s) original file here:
            <form id="form-upload" method="post" enctype="multipart/form-data">
                <input type="file" name="file"/>
                <input type="submit" value="Upload file" name="import" />
                <input type="submit" value="Download template" name="template"/>
            </form>
        </span><br><br><?php
        echo $this->searcherHTML;
        $this->generalError()?>
        <table class="seldatOriginal">
                <tr>
                    <td>
                        <form id="scanUPCsForm" method="post">
                            <table id="scanUPC">
                                <tr>
                                    <td id="instructions" colspan="2">
                                        Input UPC(s) Original <br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <textarea name="scan-upcs" id="scans"
                                                  placeholder="One UPC per line"
                                                  rows="25" cols="30"></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <br><input type="submit" id="scanUPCSubmit"
                                                   value="Submit">
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </td>
                    <td><?php
                        echo $this->datatablesStructureHTML;
                        echo $this->searcherExportButton;?>
                    </td>
                </tr>
            </table><?php
    }
    
    /*
    ****************************************************************************
    */

    private function generalError()
    {
        if ($this->upcsAddSuccess) {
            $message = '<div class="successMessage">
                <div class="alert">' . $this->upcsAddSuccess
                .' UPC(s) added success!</div></div><br>';
            echo $message;
        }
        if ($this->errors) {
            $message = '<div class="failedMessage"><div class="alert">';
            if (isset($this->errors['missFile'])) {
                $message .= $this->errors['missFile'] . "<br>";
            }
            if (isset($this->errors['format'])) {
                $message .= $this->errors['format'] . "<br>";
            }
            if (isset($this->errors['duplicate'])) {
                $message .= $this->errors['duplicate']."<br>";
            }
            if (isset($this->errors['upcInvalid'])) {
                $message .= 'UPC(s) Invalid: ';
                if (count($this->errors['upcInvalid']) < 40) {
                    foreach ($this->errors['upcInvalid'] as $key => $value) {
                        $message .= "\n" . $value;
                    }
                } else {
                    $message .= count($this->errors['upcInvalid']) . ' UPCs';
                }
            }
            if (isset($this->errors['badUPC'])) {
                $message .= '<br>UPC(s) exist: ';
                if (count($this->errors['badUPC']) < 40) {
                    foreach ($this->errors['badUPC'] as $key => $value) {
                        $message .= "\n" . $value;
                    }
                } else {
                    $message .= count($this->errors['badUPC']) . ' upc';
                }
            }
            $message .= '</div></div>';
            echo $message;
        }
    }

    /*
    ****************************************************************************
    */
}