<?php

namespace locations;

class minMax extends \tables\_default
{

    /*
    ****************************************************************************
    */

    static function importMinMaxHTML($modelObject, $importer)
    { ?>

        <form id="importMinMax" method="post" enctype="multipart/form-data">
            <div id="minMaxImport" class="inputBlock">
                <div class="message">
                    Import list of location min/max values
                    <input type="file" name="file" id="minMaxFile">
                    <input type="submit" value="Submit" class="inputBlockSubmit">
                </div>
            </div>
        </form>

        <?php
        
        if ($modelObject->fileSubmitted) {
            $importer->importError();
        }
    }

    /*
    ****************************************************************************
    */
  
    static function importSuccessHTML()
    { ?>

        <br>
        <div style="border: 1px #9d9 solid; background: #e9ffe9;" 
             class="blockDisplay">Your file has been imported successfully!
        </div>

        <?php
    }

    /*
    ****************************************************************************
    */
  
    static function importTemplateHTML()
    {
        ob_start();
        
        ?>

        <br><br>
        <form method="post">
            <input type="submit" name="template" value="Download Import Template">
        </form>
        
        <?php
        
        return ob_get_clean();
    }

    /*
    ****************************************************************************
    */
  
    static function importTemplate($fields)
    {
        $fieldKeys = [];

        foreach ($fields as $values) {
            if (isset($values['required'])) {
                $fieldKeys[] = [
                  'title' => $values['display']
                ];
            }
        }        

        \excel\exporter::ArrayToExcel([
            'data' => [], 
            'fileName' => 'min_max_import_template',
            'fieldKeys' => $fieldKeys,
        ]);
    }

    /*
    ****************************************************************************
    */
    
}
