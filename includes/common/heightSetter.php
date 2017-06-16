<?php

namespace common;

class heightSetter
{
    function __construct($app)
    {
        $this->app = $app;
        $app->includeJS['custom/js/common/heightSetter.js'] = TRUE;
    }
}