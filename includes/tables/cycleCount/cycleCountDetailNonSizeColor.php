<?php

namespace tables\cycleCount;

class cycleCountDetailNonSizeColor extends cycleCountDetail
{

    public $ajaxModel = 'cycleCount\cycleCountDetailNonSizeColor';

    public $customAddRows = 'cycleCount\cycleCountDetailNonSizeColor';

    public $customInsert = 'cycleCount\cycleCountDetailNonSizeColor';

    /*
    ****************************************************************************
    */

    function __construct($app = FALSE)
    {
        $this->fields = parent::fields();
        parent::__construct($app);
    }

    /*
    ****************************************************************************
    */

    function fields()
    {
        return $this->fields;
    }

    /*
    ****************************************************************************
    */




}