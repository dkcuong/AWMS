<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{
    static $incomplete = [
        'wmco' => 'wmci',
        'rtco' => 'rtci',
        'pkco' => 'pkci',
        'opco' => 'opci',
        'woco' => 'woci',
        'shco' => 'lsci',
    ];    
    
    function displayDashboardsView()
    {
        ?>
        <div id="displayDashboard">
        <?php echo $this->searcherHTML; ?>
        <div id="filterContainer">
            <div class="orderFilter">
                <table id="statusTable">
                    <tr>
                        <td id="statusTableTitle">Select Order Statuses:<br>
                            <span id="statusDisplay">
                                <select id="category"></select>
                            </span><br>
                        </td>
                    </tr>
                </table>        
            </div>
            <div class="orderFilter"> <?php
                echo $this->multiSelectTableStarts['completion']; 
                echo $this->multiSelectTableEnd; ?>
            </div> 
            <div class="orderFilter"> <?php
                echo $this->multiSelectTableStarts['vendorID']; 
                echo $this->multiSelectTableEnd; ?>
            </div> 
        </div> <?php 
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton; ?>
        <button id="dashView">Full Screen View</button>
        </div><?php
    }
    
    /*
    ****************************************************************************
    */

    function  smartTVDashboardsView()
    {
        $filterTitle = $_SESSION['search']['filterTitle'];

        $_SESSION['blinkDashboard'] = ! getDefault($_SESSION['blinkDashboard']);
        
        reset($this->filters[$this->title]);

        $this->table->smartTVFields();

        $groupClause = $havingClause = NULL;
        $param = $fields = [];

        foreach ($this->table->smartTVFields as $key => $value) {
            
            if (! isset($value['display'])) {
                continue;
            }

            $fields[] = isset($value['select']) 
                    ? $value['select'].' AS '.$key : $key;

            $titles[] = $value['display'];

            if ($value['display'] === $filterTitle) {

                $compareOperator = $_SESSION['search']['status'] == 'complete'
                        ? '!=' : '=';

                $havingClause = 'HAVING '.$key.' '.$compareOperator.' ?';
                
                $param[] = 'Incomplete';
            }
        } ?>

        <table id="smartTVDashboard">
            <tr class="caption"><?php

        $allOrders = $filterTitle === TRUE;
                    
        foreach ($fields as $key => $value) { 
            
            $caption = $titles[$key];
            
            if (! $allOrders && isset($this->filters[$this->title][$caption]) 
                    && $filterTitle != $caption) {
                
                continue;
            } ?>
                <td style="font-size: <?php echo $this->fontSize; ?>pt;"><?php 
                    echo $caption; ?></td><?php
        } ?>       
            </tr> <?php

        if (! $this->results || ! $this->loop && ! $_SESSION['elapsedTime']) {
            
            // ! $this->loop: 1-st screen from a set of screens (if amount of 
            // rows to display greater than amount of rows on a screen)
            
            // ! $_SESSION['elapsedTime']: a screen is displayed for the 1-st
            // time (multiple blinks per one screen, each red blink increases
            // $_SESSION['elapsedTime'] counter by amount of elapsed seconds)
            
            $this->results = $this->getData($fields, $havingClause, $param);
        }

        $now = date('Y-m-d', time());
        $alarmTime = strtotime('+2 day', strtotime($now));

        $rowCount = 0;

        foreach ($this->results as $values) { 
        
            $rowCount++;
            
            if ($rowCount <= $this->tableRowAmount*$this->loop) {
                continue;
            } elseif ($rowCount > $this->tableRowAmount*($this->loop + 1)) {
                break;
            }
            
            $class = $rowCount % 2 == 0 ? 'even' : 'odd'; ?>
            <tr class="<?php echo $class; ?>"><?php
            
            $key = 0;        
            $alarmRow = FALSE;

            if ($this->title == 'Shipping' && $_SESSION['blinkDashboard'] 
                    && $values['shco'] == 'Incomplete') {
                
                $cancelDate = strtotime($values['canceldate']);
                $alarmRow = $cancelDate < $alarmTime;
            }

            $orderType = $logDate = NULL;
            
            foreach ($values as $field => $value) {
                
                $cellClass = NULL;
                $class = $alarmRow ? 'blinkRow statusCell' : 'statusCell';
                $caption = $titles[$key];
                $key++;

                if ($this->title == 'Shipping') {
                    if ($field == 'clientordernumber') {
                        
                        $orderType = substr($value, 0, 4);
                        $orderType = strtolower($orderType);
                        
                        // clientordernumber is preceded with order status:
                        // 4 characters plus space (5 characters total)
                        
                        $value = substr($value, 5);
                    }

                    if ($field == 'scanordernumber') {
                        $logDate = substr($value, 0, 10);
                        
                        // scanordernumber is preceded with order date:
                        // 10 characters plus space (11 characters total)
                        
                        $value = substr($value, 11);
                    }
                }
                
                if (isset($this->filters[$this->title][$caption])) {

                    $cellClass = 'statusCell';
                    
                    if (! $allOrders && $filterTitle != $caption) {
                        continue;
                    }

                    if ($this->title == 'Shipping') {

                        if (! in_array($orderType, self::$incomplete) && $value != 'Incomplete'
                                || $value == 'Complete') {
                            
                            $class = $alarmRow ? 'blinkRow_complete' : 'complete';
                        }
                        
                        $checkIn = self::$incomplete[$field];
                        
                        if (($orderType == $field || $orderType == $checkIn)
                                && $logDate != 'XXXX-XX-XX') {
                            
                            // XXXX-XX-XX: no date was found for the order status
                            
                            $value = $logDate;
                        }                            

                    } else {
                        if ($value != 'Incomplete') {
                            $class = $alarmRow ? 'blinkRow_complete' : 'complete';
                        }                        
                    }
                } elseif ($field == 'clientordernumber' || $field == 'scanordernumber'
                        || $field == 'batchID') {
                    
                    $cellClass = 'orderNumberCell';
                } elseif ($field == 'startshipdate' || $field == 'canceldate' 
                        || $field == 'setDate') {
                    
                    $cellClass = 'dateCell';
                } elseif ($field == 'numberofcarton' || $field == 'numberofpiece'
                        || $field == 'initialCount' || $field == 'totalPieces'
                        || $field == 'volume' || $field == 'cartonCount') {
                    
                    $cellClass = 'quantityCell';
                } elseif ($field == 'NOrushhours') {
                    
                    $cellClass = 'rushCell';
                    
                    if ($value == 'Complete') {
                        $class = $alarmRow ? 'blinkRow_complete' : 'complete';
                    }
                } ?>
                
                <td style="font-size: <?php echo $this->fontSize; ?>pt;"
                    class="<?php echo $cellClass.' '.$class; ?>"><?php 
                    echo $value; ?></td><?php
            } ?>
            </tr><?php
        } ?>

        </table><?php
    }
    
    /*
    ****************************************************************************
    */

    function selectDashboardsView()
    {
        ?>
        <div id="dashboardSelections">
            <form target="_blank" method="post" 
                  action="<?php echo $this->displayLink; ?>"><?php

        foreach ($this->filters as $title => $filters) { ?>
            <table><tr><td class="titles">
                <?php echo $title; ?> Dashboards</td></tr><?php
            foreach ($filters as $filterTitle => $boolean) { 

                $submitName = $boolean === TRUE ? $title : $boolean; ?>
                <tr><td>
                        <input type="submit" name="<?php echo $submitName; ?>" 
                               value="Display" >
                    <?php echo $filterTitle; ?></td><td><?php
                    if ($boolean !== TRUE) { ?>
                        <input type="radio" 
                               name="<?php echo $boolean; ?>status" 
                               value="complete" checked> Complete
                        <input type="radio" 
                               name="<?php echo $boolean; ?>status" 
                               value="incomplete"> Incomplete
                        <?php
                    }
                ?></td></tr><?php
            } ?>
            </table><?php
        } ?>
            <table>
                <tr>
                    <td class="titles" colspan="3">Settings</td>
                </tr>
                <tr><td style="padding-right: 20px;">Show
                        <input type="number" class="settings" name="tableRowAmount"
                               size="3" min="2" max="100" value="20">rows</td>
                    <td >Update
                        <input type="number" class="settings" name="updateTime"
                               min="2" max="100" value="10">sec</td>
                    <td style="padding-left: 20px;">Blink
                        <input type="number" class="settings" name="blinkTime"
                               min="2" max="100" value="2">sec</td>
                </tr>
                <tr><td style="padding-right: 20px;">Font Size
                        <input type="number" class="settings" name="fontSize"
                               min="4" max="40" value="12">pt</td>
                </tr>
            </table>
        </form>
        </div><?php
    }    
}