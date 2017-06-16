<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

class model extends base
{
    public $menuString;

    public $isClient = TRUE;

    public $isShowFeature = FALSE;

    public $listNewFeatures = [];

    public $defaultLink = [];

    /*
    ****************************************************************************
    */

    function getMenu()
    {
        $currentGroups = FALSE;
        $menu = [];

        $isClient = access::isClient($this);

        $userGroups = new \tables\users\userGroups($this);
        $pages = new \tables\users\pages($this);
        $pageParams = new \tables\users\pageParams($this);

        if (! $this->isClient) {

            $userLevel = appConfig::getSetting('accessLevels', 'user');
            $accessLevel = access::getUserInfoValue('level');

            if ($accessLevel == $userLevel) {
                // limit displayed pages to Users level only

                $userID = access::getUserID();

                $currentGroups = $userGroups->getUserGroups([$userID]);

                if (! $currentGroups) {
                    return [];
                }
            }
        }

        // getUserPages will return all pages if $currentGroups is set to FALSE
        $currentPages = $pages->getUserPages($currentGroups, $pageParams, $isClient);

        foreach ($currentPages as $currentPage) {

            $subMenu = $currentPage['subMenu'];
            $method = $currentPage['method'];
            $class = $currentPage['class'];
            $page = $currentPage['page'];
            $red = $currentPage['red'] ? 'redLink' : NULL;
            $app = $currentPage['app'];
            $pageParams = getDefault($currentPage['pageParams'], NULL);

            $link = $app ? makeAppLink($app, $class, [
                'method' => $method,
                'var' => $pageParams,
            ]) : makeLink($class, $method, $pageParams);

            $menu[$subMenu][$link] = [$page, $red];
        }

        return $menu;
    }

    /*
    ****************************************************************************
    */

}
