-- ---------------------------------------------------------
--      wms:      ONLY commands from after 1955 here
--      _default: ONLY commands from after 334 here
-- ---------------------------------------------------------
    
-- Phong Tran
-- 10/23/2015

--  Add "Search UPC Client" in menu Inventory Control
INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "Search UPC Client" AS displayName,
          maxOrder + 1 AS displayOrder,
          "upcclient" AS hiddenName,
          "upcs" AS `class`,
          "search" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.displayName = "Inventory Control"
    AND       sm.active
) m
WHERE     sm.displayName = "Inventory Control"
AND       active
LIMIT 1;

-- Add page params for "Search UPC Client"

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
          "show" AS `name`,
          "client" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "upcclient";

-- Vuong Nguyen
-- 10/234/2015
-- data fields for carton IDs and transfer info by json data
ALTER TABLE `reports_data` ADD `data` LONGTEXT NULL DEFAULT NULL AFTER `primeKey`;
-- Status for Transfer Email
INSERT INTO `statuses` (`id`, `category`, `displayName`, `shortName`) VALUES (NULL, 'reports', 'Transfer Email', 'TE');

-- Duy Nguyen
-- 10/26/2015
-- create cron task email transfer mezzanine
INSERT INTO `crons`.`tasks` (`displayName`, `server`, `site`, `app`, `class`, `method`, `frequency`, `active`) 
VALUES ('Email transfer mezzanine ', 'localhost', 'mvc', 'wms', 'appCrons', 'emailTransferMezzanine', '5', '1');

-- Vadzim Mechnik
-- 10/26/2015

-- increase decimal part for each measurements

ALTER TABLE `inventory_batches` CHANGE `eachHeight` `eachHeight` DECIMAL(6,2) NOT NULL;
ALTER TABLE `inventory_batches` CHANGE `eachWidth` `eachWidth` DECIMAL(6,2) NOT NULL;
ALTER TABLE `inventory_batches` CHANGE `eachLength` `eachLength` DECIMAL(6,2) NOT NULL;
ALTER TABLE `inventory_batches` CHANGE `eachWeight` `eachWeight` DECIMAL(6,2) NOT NULL;

-- increase clientOrderNumber field width

ALTER TABLE `neworder` CHANGE `clientordernumber` `clientordernumber` VARCHAR(30) NULL DEFAULT NULL;

-- Vadzim
-- 10/29/2015

-- adding indices and AUTO_INCREMENT to commodity table

ALTER TABLE `commodity`
 ADD PRIMARY KEY (`id`), 
 ADD UNIQUE KEY `description` (`description`);

ALTER TABLE `commodity` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;

-- Phong Tran 
-- 11/04/2015

-- Add newPassword field in userDB.info - seldat_users database
ALTER TABLE `info` ADD `newPassword` VARCHAR(40) NOT NULL AFTER `password`;

-- Tri Le
-- 11/06/2015

-- Add vendorID Unique index
ALTER TABLE locations_info DROP INDEX `upcLocID`,
ADD UNIQUE `upcLocIDVendorID` (`upcID`, `locID`, `vendorID`);

-- Vadzim
-- 11/11/2015

--  Add "Summary report" to menu Administration menu

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "Summary report" AS displayName,
          maxOrder + 1 AS displayOrder,
          "summaryReport" AS hiddenName,
          "inventory" AS `class`,
          "summaryReport" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.displayName = "Administration"
    AND       sm.active
) m
WHERE     sm.displayName = "Administration"
AND       active
LIMIT 1;

-- Vadzim
-- 11/11/2015

-- Make main menu captions in title case

UPDATE pages
SET displayName = 'Summary Report'
WHERE hiddenName = 'summaryReport';

-- Vadzim
-- 11/18/2015

--  Create min_max table

CREATE TABLE `min_max` ( 
  `id` INT(7) NOT NULL AUTO_INCREMENT, 
  `locID` INT(7) NOT NULL, 
  `upcID` INT(10) NOT NULL, 
  `minCount` INT(8) NOT NULL DEFAULT 0, 
  `maxCount` INT(8) NOT NULL DEFAULT 0, 
  `active` tinyint(1) NOT NULL DEFAULT 1, 
  PRIMARY KEY (`id`), 
  KEY `locID` (`locID`), 
  KEY `upcID` (`upcID`), 
  UNIQUE KEY `uniqueUpc` (`upcID`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 
 
--  Fill in min_max table with data obtained from locations_info table
 
INSERT INTO min_max (upcID, locID, minCount, maxCount) 
SELECT upcID, locID, minCount, maxCount 
FROM locations_info 
WHERE  active 
AND    minCount IS NOT NULL 
AND    maxCount IS NOT NULL 
GROUP BY upcID;

--  Create a table for min_max location ranges

CREATE TABLE `min_max_ranges` ( 
  `id` INT(11) NOT NULL AUTO_INCREMENT, 
  `vendorID` INT(11) NOT NULL, 
  `minCount` INT(8) NOT NULL DEFAULT 0, 
  `maxCount` INT(8) NOT NULL DEFAULT 0, 
  `startLocID` INT(7) NULL DEFAULT NULL, 
  `endLocID` INT(7) NULL DEFAULT NULL,
  PRIMARY KEY (`id`), 
  KEY `vendorID` (`vendorID`), 
  UNIQUE KEY `uniqueVendor` (`vendorID`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--  Add "Min Max" to menu Inventory Control menu (that will substitule "Location Info" page)

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "Locations MinMax" AS displayName,
          maxOrder + 1 AS displayOrder,
          "minMax" AS hiddenName,
          "minMax" AS `class`,
          "search" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.displayName = "Inventory Control"
    AND       sm.active
) m
WHERE     sm.displayName = "Inventory Control"
AND       active
LIMIT 1;

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
          "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "minMax";

ALTER TABLE min_max DROP INDEX `uniqueUpc`;
ALTER TABLE min_max ADD UNIQUE KEY `uniqueLocationUpc` (`locID`, `upcID`);

-- Vadzim
-- 11/19/2015

--  Add "Min Max Ranges" to menu Inventory Control menu

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "Min Max Ranges" AS displayName,
          maxOrder + 1 AS displayOrder,
          "minMaxRanges" AS hiddenName,
          "minMaxRanges" AS `class`,
          "search" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.displayName = "Inventory Control"
    AND       sm.active
) m
WHERE     sm.displayName = "Inventory Control"
AND       active
LIMIT 1;

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
          "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "minMaxRanges";

ALTER TABLE `min_max_ranges` ADD `active` TINYINT(1) NOT NULL DEFAULT 1;

-- Adding elite brands to dealsites
INSERT INTO `live_wms`.`deal_sites` (`id`, `displayName`, `imageName`, `active`) 
VALUES (NULL, 'EliteBrands.com', 'eliteBrands', '1');

-- Phong Tran
-- 12/02/2015
-- Change camleCase for WorkOrder Menu (workorders => workOrders)

UPDATE page_params pp
JOIN pages p ON p.id = pp.pageID
SET value = 'workOrders'
WHERE     p.hiddenName = "searchWorkOrdersLogs";

UPDATE page_params pp
JOIN pages p ON p.id = pp.pageID
SET value = 'workOrders'
WHERE     p.hiddenName = "workOrdersInvoices";

UPDATE page_params pp
JOIN pages p ON p.id = pp.pageID
SET value = 'workOrders'
WHERE     p.hiddenName = "editWorkOrderInvoices";

-- Vadzim
-- 12/3/2015

-- Add a field that specifies a label number that is assigned to a export row so
-- that multiple orders can be shipped under a single UPS labels

ALTER TABLE `online_orders_exports` ADD `labelNo` INT(3) NULL DEFAULT NULL;

-- Vadzim
-- 12/4/2015

-- Add a field that specifies a label number that is assigned to a export row so
-- that multiple orders can be shipped under a single UPS labels

-- remove possible duplicate entried 
-- (use GROUP BY upcID DESC to remove newest ids and leave first entered ones)

-- DELETE a 
-- FROM upcs_assigned a
-- JOIN      (
--     SELECT    id, 
--               COUNT(upcID) AS rowCount
--     FROM      upcs_assigned
--     GROUP BY  upcID DESC
--     HAVING    rowCount > 1
-- ) n ON a.id = n.id

-- Add a unique key on upcID field to upcs_assigned table
ALTER TABLE `upcs_assigned` ADD UNIQUE `uniqueUpcID` (`upcID`);

-- Vadzim
-- 12/4/2015

-- removing extra index from upcs_assigned table

ALTER TABLE upcs_assigned DROP INDEX upcID;
ALTER TABLE upcs_assigned DROP INDEX uniqueUpcID;

ALTER TABLE `upcs_assigned` ADD UNIQUE `upcID` (`upcID`);

-- Phong Tran
-- 08/12/2015
-- Create inventory_unsplits for inventory_unsplits logs

CREATE TABLE `inventory_unsplits` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `parentID` int(10) NOT NULL,
  `childID` int(10) NOT NULL,
  `userID` int(4) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `parentID` (`parentID`,`childID`),
  KEY `childID` (`childID`)
) ENGINE=InnoDB AUTO_INCREMENT=7208 DEFAULT CHARSET=utf8;

-- Duy Nguyen
-- 09/12/2015
-- Create logs_scan_input table

CREATE TABLE IF NOT EXISTS `logs_scan_input` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `pageRequest` varchar(255) NOT NULL,
  `scanInput` text NOT NULL,
  `inputOption` varchar(255) DEFAULT NULL,
  `logTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- add Search Scan Input Data page 

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "Search Scan Input Data" AS displayName,
          maxOrder + 1 AS displayOrder,
          "searchScanInputData" AS hiddenName,
          "logs" AS `class`,
          "components" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.displayName = "Search Logs"
    AND       sm.active
) m
WHERE     sm.displayName = "Search Logs"
AND       active
LIMIT 1;

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
          "show" AS `name`,
          "scanInput" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "searchScanInputData";

-- Phong Tran 
-- 09/12/2015
-- Add "Reprint Unsplit Carton Labels" to menu Search Data menu

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "Reprint Unsplit Carton Labels" AS displayName,
          maxOrder + 1 AS displayOrder,
          "reprintunSplitCartonLabels" AS hiddenName,
          "inventory" AS `class`,
          "listSplitCartons" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.displayName = "Search Data"
    AND       sm.active
) m
WHERE     sm.displayName = "Search Data"
AND       active
LIMIT 1;

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
          "type" AS `name`,
          "reprintUnsplit" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "reprintunSplitCartonLabels";


-- Tri Le
-- 12/10/2015

--  Add "Seldat Original" to menu Administration menu

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "Seldat Original" AS displayName,
          maxOrder + 1 AS displayOrder,
          "seldatOriginal" AS hiddenName,
          "seldatOriginal" AS `class`,
          "addNewUPCs" AS `method`,
          0 AS red,
          1 AS active
FROM      submenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.displayName = "Administration"
    AND       sm.active
) m
WHERE     sm.displayName = "Administration"
AND       active
LIMIT 1;

-- Add a unique key on upc field to upcs_originals table
ALTER TABLE `upcs_originals` DROP INDEX `upc`, ADD UNIQUE `upc` (`upc`) COMMENT '';

-- Add field data into upcs_original table
ALTER TABLE `upcs_originals` ADD `date` DATETIME NULL ;

-- Phong Tran
-- 12/11/2015

UPDATE page_params pp
JOIN pages p ON p.id = pp.pageID
SET displayName = 'Merge Cartons'
WHERE     p.hiddenName = "unsplitCartons";

-- Vadzim
-- 12/17/2015

--  Add "Edit Order Shipping Info" to menu Edit Data menu

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "Edit Order Shipping Info" AS displayName,
          maxOrder + 1 AS displayOrder,
          "orderShippingInfo" AS hiddenName,
          "orders" AS `class`,
          "shippingInfo" AS `method`,
          0 AS red,
          1 AS active
FROM      submenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.displayName = "Edit Data"
    AND       sm.active
) m
WHERE     sm.displayName = "Edit Data"
AND       active
LIMIT 1;

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
          "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "orderShippingInfo";

-- Tri Le
-- 12/18/2015

-- Add submenu "Reprint License Plates" in Inventory Control
INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
   	      "Reprint License Plates" AS displayName,
          maxOrder + 1 AS displayOrder,
          "reprintLicensePlates" AS hiddenName,
          "scanners" AS `class`,
          "reprintLicensePlate" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.displayName = "Inventory Control"
    AND       sm.active
) m
WHERE     sm.displayName = "Inventory Control"
AND       active
LIMIT 1;

-- Jon
-- 12/15/2015

-- Change default isSent value to 1
ALTER TABLE `reports_data` CHANGE `isSent` `isSent` TINYINT(1) NOT NULL DEFAULT '1';

-- Adding missing split table constraint, unique child cartons
ALTER TABLE `inventory_splits` ADD UNIQUE KEY `activeChild` (`childID`,`active`);

-- test comment