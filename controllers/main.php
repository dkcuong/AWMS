<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{
    function menuMainController()
    {
        if (getDefault($_SESSION['onScanner'])) {
            $link = makeLink('main', 'mobileMenu');
            redirect($link);
        }

        new jQuery\loginDialog($this);
        new jQuery\reportIssues($this);
        new test\recorder($this);

        $updatesList = new \tables\updatesList($this);

        $class = getDefault($this->get['class']);
        $method = getDefault($this->get['method']);

        $this->isClient = access::checkClientPage($this);

        $this->jsVars['menu'] = $this->menu = $this->getMenu();

        if ($this->isClient) {
            $defaultURL = makeLink('inventory', 'available');
        } else {

            $defaultClass = $this->defaultLink ? $this->defaultLink['class'] :
                'receiving';
            $defaultMethod = $this->defaultLink ? $this->defaultLink['method'] :
                'display';

            $defaultURL = makeLink($defaultClass, $defaultMethod);
        }

        // Default here if a page is not selected
        $requestClass = appConfig::get('site', 'requestClass');
        $this->jsVars['defaultURL'] = $class && $class != $requestClass ?
            makeLink($class, $method) : $defaultURL;

        // Need server name bc some pages are still on /seldat
        $this->jsVars['serverName'] = \models\config::getServerName();

        $this->jsVars['urls']['logout'] = makeLink('logout');

        new common\heightSetter($this);

        $this->jsVars['urls']['updateFeatureShowStatus'] =
            makeLink('appJSON', 'updateFeatureShowStatus');

        $this->isShowFeature = $this->jsVars['isShowFeatures'] =
            $updatesList->checkIsShowFeature();
        if ($this->isShowFeature) {
            $this->listNewFeatures = $updatesList->listNewFeatures();
        }

        $this->username = \access::getUserInfoValue('username');

    }

    /*
    ****************************************************************************
    */

    function mobileMenuMainController()
    {
        $_SESSION['onScanner'] = TRUE;

        $this->commonAutomatedMenu = common\automated::getMenuHTML($this);
    }

    /*
    ****************************************************************************
    */

}