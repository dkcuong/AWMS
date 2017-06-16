<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{
    
    function addNewUPCsSeldatOriginalController()
    {
        $this->errors =[];
        $this->upcsAddSuccess = [];

        $seldatOriginal = new \tables\seldatOriginal($this);

        $this->modelName = getClass($seldatOriginal);

        $dtOptions = [
            'bFilter' => FALSE,
            'order' => [1 => 'desc'],
        ];

        $ajax = new datatables\ajax($this);

        $ajax->output($seldatOriginal, $dtOptions);

        new datatables\searcher($seldatOriginal);

        $template = getDefault($this->post['template']);

        if ($template) {
            $this->downloadTemplate();
        }

        if (getDefault($_FILES) && $_FILES['file']['error']) {
            $this->errors['missFile'] = 'Please input file to upload';
        }

        $this->fileSubmitted = getDefault($_FILES) && ! $_FILES['file']['error'];

        // Upload file

        if ($this->fileSubmitted) {
            $result = $seldatOriginal->processUploadUpcOriginal($this);
            $result['errors']
                ? $this->errors = $result['errors'] : $result['success']
                ? $this->upcsAddSuccess = $result['success'] : FALSE;
        }

        // Get data from UI

        $upcs = getDefault($this->post['scan-upcs'], []);

        if ($upcs) {

            $upcs = $seldatOriginal->getInputScan($upcs);

            $duplicate = $seldatOriginal->checkDuplicateUPC($upcs);

            $duplicate ? $this->errors['duplicate'] = $duplicate : NULL;

            $existUPC = $seldatOriginal->getExistUpcs($upcs);

            $existUPC ? $this->errors['badUPC'] = $existUPC : NULL;

            $upcInvalid = $seldatOriginal->getUPCsInvalid($upcs);

            $upcInvalid ? $this->errors['upcInvalid'] = $upcInvalid : NULL;

            $seldatOriginal->removeExistUPCs($upcs);

            $this->upcsAddSuccess = count($upcs);

            $seldatOriginal->addNewUPCsOriginal($upcs);
        }
    }

    /*
    ****************************************************************************
    */

    private function downloadTemplate()
    {
        $exporter = new \excel\exporter($this);
        $exporter->ArrayToExcel([
            'data' => [
                ['8988999889888']
            ],
            'fileName' => 'upc_original_template',
            'fieldKeys' => [
                ['title' => 'UPC']
            ]
        ]);
    }

    /*
    ****************************************************************************
    */
}