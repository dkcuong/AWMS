<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{
    
    function clientsCostsController()
    {
        $this->jsVars['custID'] = $this->vendorID = 
            getDefault($this->post['custID'], NULL);
        
        $this->jsVars['urls']['getClientCosts'] 
            = customJSONLink('appJSON', 'getClientCosts');
        $this->jsVars['urls']['updateClientCosts'] 
            = customJSONLink('appJSON', 'updateClientCosts');
        
        $this->jsVars['urls']['deleteClientCosts'] 
            = customJSONLink('appJSON', 'deleteClientCosts');
        
        $this->jsVars['urls']['getChargeCodes'] 
            = customJSONLink('appJSON', 'getChargeCodes');
        
        $this->jsVars['urls']['checkVolumeRates'] 
            = customJSONLink('appJSON', 'checkVolumeRates');

        $chargeMaster = new tables\customer\chargeCodeMaster($this);
        $vendors = new tables\vendors($this);
        
        $this->vendorNames = [
            0 => [
                'vendorID' => 0,
                'fullVendorName' => 'Select Client'
            ]
        ];

        $this->vendorNames = array_merge($this->vendorNames, 
                $vendors->getAlphabetizedNames());
       
        $this->chargeTypes = $chargeMaster->getChargeCodeType(TRUE);
       
        $this->vendorCosts = $chargeMaster->getClientCharges($this->vendorID);
    }
    
    /*
    ****************************************************************************
    */
}