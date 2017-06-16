<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{
    function menuMainView()
    {
        $urlImage = makeLink('custom', 'images', 'header.png');
        $urlImageRepeat = makeLink('custom', 'images', 'header-x.png');
        $logoutLink = makeLink('logout');?>

        <table>
            <tr id="headerImage" style="background: url(<?php echo $urlImage; ?>) no-repeat,
                url(<?php echo $urlImageRepeat; ?>) repeat-x">
                <td colspan="2">
                    <div id="userInfo">
                        <span id="username"><?php echo $this->username; ?></span> |
                        <span>
                            <a href="<?php echo $logoutLink; ?>">Sign Out</a>
                        </span>
                    </div>
                    <div id="reportIssue">
                        <span>
                            <a href="#" id="callReportIssue">Report an Issue</a>
                        </span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div id="accordion">
                    <?php foreach ($this->menu as $title => $links) { ?>
                        <h3><?php echo $title; ?></h3>
                        <div>
                        <?php foreach ($links as $url => $link) { ?>
                            <a href="#<?php echo $url; ?>"
                               class="links <?php echo $link[1]?>" data-link="<?php echo $url; ?>">
                                   <?php echo $link[0]; ?></a><br>
                        <?php } ?>
                        </div>
                    <?php } ?>
                    </div>

                    <button type="button" id="btnToggleAccordion"
                         class="ui-icon ui-icon-circle-arrow-w"
                         title='Full screen'></button>

                </td>
                <td id="frameCell">
                    <div id="titleCell"></div>
                    <iframe id="mainDisplay">
                    </iframe>
                </td>
            </tr>
        </table>
        <?php if ($this->isShowFeature) {?>
        <div id="new-feature" title="New Features">
            <table>
                <tr style="font-weight: bold">
                    <td>Feature</td>
                    <td>Description</td>
                </tr>
            <?php foreach ($this->listNewFeatures as $key => $feature) {
                $featureName = $this->listNewFeatures[$key]['featureName'];
                $featureDescription = $this->listNewFeatures[$key]['featureDescription'];
            ?>
                <tr>
                    <td>
                        <?php echo $featureName; ?>
                    </td>
                    <td>
                        <?php echo $featureDescription; ?>
                    </td>
                </tr>
            <?php } ?>
            </table>
            <div style="text-align: right; padding: 10px;">
                <input type="checkbox" class="not-show-feature">Don't show again
                <input type="submit" class="submitNewFeature" value="OK">
            </div>
        </div>
        <?php } ?>
        <div id="dashNotice">Full Screen: Press the Esc Key to return to normal view.</div>
        <?php
        echo $this->dialogHTML;
        echo $this->reportIssueDialogHTML;
        echo $this->testRecorderSetterHTML;
    }

    /*
    ****************************************************************************
    */

    function mobileMenuMainView()
    {
        if (! getDefault($this->get['old'])) {
            echo $this->commonAutomatedMenu;
            return;
        }

        ?>
        <div class="centered"><span id="onlyOnScanner">.
            </span><span id="pageTitle"></span>
        </div>
        <table id="scanner"><?php

            foreach (common\scanner::$scannerTitle as $process => $caption) {
                switch ($process) {
                    case 'shippingCheckIn':
                        $process = 'plateLocation';
                        $param = ['process' => 'checkIn'];
                        break;
                    case 'shipped':
                        $param = ['process' => 'checkOut'];
                        break;
                    case 'routedCheckIn':
                        $process = 'orderEntry';
                        $param = ['process' => 'routedCheckIn'];
                        break;
                    case 'routedCheckOut':
                        $process = 'orderEntry';
                        $param = ['process' => 'routedCheckOut'];
                        break;
                    case 'pickingCheckIn':
                        $process = 'orderEntry';
                        $param = ['process' => 'pickingCheckIn'];
                        break;
                    case 'pickingCheckOut':
                        $process = 'orderEntry';
                        $param = ['process' => 'pickingCheckOut'];
                        break;
                    case 'orderProcessingCheckIn':
                        $process = 'orderEntry';
                        $param = ['process' => 'orderProcessingCheckIn'];
                        break;
                    case 'orderProcessCheckOut':
                        $process = 'orderEntry';
                        $param = ['process' => 'orderProcessCheckOut'];
                        break;
                    case 'errOrderRelease':
                        $process = 'orderEntry';
                        $param = ['process' => 'errOrderRelease'];
                        break;
                    case 'cancel':
                        $process = 'orderEntry';
                        $param = ['process' => 'cancel'];
                        break;
                    default:
                        $param = NULL;
                        break;
                } ?>
            <tr>
                <td class="mobileMenuCell">
                    <a href="<?php echo makeLink('scanners', $process, $param); ?>"
                       class="mobileMenuLink"><?php echo $caption; ?></a>
                </td>
            </tr><?php
            } ?>
        </table><?php
    }

    /*
    ****************************************************************************
    */

    function createMainView()
    {
        ?>
        <?php
    }

}