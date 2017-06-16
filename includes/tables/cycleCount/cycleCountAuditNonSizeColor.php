<?php

namespace tables\cycleCount;

class cycleCountAuditNonSizeColor extends cycleCountAudit
{
    public $ajaxModel = 'cycleCount\cycleCountAuditNonSizeColor';

    /*
    ****************************************************************************
    */

    function __construct($app = FALSE)
    {
        unset ($this->fields['size']);
        unset ($this->fields['color']);

        parent::__construct($app);
    }
    
    /*
    ****************************************************************************
    */
}