-- ---------------------------------------------------------
-- ONLY commands from after 1972 here
-- ---------------------------------------------------------

-- Tri Le
-- 9/16/2015

-- Remove redundant Fields inventoryBatch and invoiceBatch in invoices_receiving table
ALTER TABLE `invoices_receiving` DROP `inventoryBatch`;
ALTER TABLE `invoices_receiving` DROP `invoiceBatch`;

-- Phong Tran
-- 9/16/2015

UPDATE `statuses` SET `shortName`='METR' WHERE (`shortName`='TROM');

-- Phong Tran
-- 9/16/2015

-- Database: Only bench3
-- Add inventoryBatch field for invoices_storage table if inventoryBatch field not exist.
ALTER TABLE `invoices_storage` ADD COLUMN `inventoryBatch`  int(8) NOT NULL AFTER `recNum`;

-- Phong Tran
-- 9/16/2015

-- Database: Only bench3
-- Add sort field and update sort value for some records in refs table.
ALTER TABLE `refs`
	ADD COLUMN `sort` TINYINT(4) NULL DEFAULT NULL AFTER `Active`;
UPDATE `refs` SET `sort`=1 WHERE  `inputName`='recUnits';
UPDATE `refs` SET `sort`=2 WHERE  `inputName`='recCC';
UPDATE `refs` SET `sort`=3 WHERE  `inputName`='recCV';
UPDATE `refs` SET `sort`=4 WHERE  `inputName`='recFreight';
UPDATE `refs` SET `sort`=5 WHERE  `inputName`='recFP';
UPDATE `refs` SET `sort`=6 WHERE  `inputName`='recVT';
UPDATE `refs` SET `sort`=7 WHERE  `inputName`='recDT';
UPDATE `refs` SET `sort`=8 WHERE  `inputName`='recBF';
UPDATE `refs` SET `sort`=9 WHERE  `inputName`='recRush';
UPDATE `refs` SET `sort`=9 WHERE  `inputName`='recSpec';
UPDATE `refs` SET `sort`=9 WHERE  `inputName`='recLabour';
UPDATE `refs` SET `sort`=9 WHERE  `inputName`='recOT';
UPDATE `refs` SET `sort`=9 WHERE  `inputName`='recQC';

-- Phong Tran
-- 9/18/2015

-- All RecNum Fields In the Database should be Length Eight
ALTER TABLE `inventory_batches` CHANGE `recNum` `recNum` INT(8) NOT NULL;
ALTER TABLE `inventory_containers` CHANGE `recNum` `recNum` INT(8) NOT NULL AUTO_INCREMENT;
ALTER TABLE `invoices_receiving` CHANGE `recNum` `recNum` INT(8) UNSIGNED ZEROFILL NULL DEFAULT NULL;
ALTER TABLE `invoices_storage` CHANGE `recNum` `recNum` INT(8) UNSIGNED ZEROFILL NULL DEFAULT NULL;

-- Vadzim
-- 9/18/2015

-- moving group_users table from seldat_users to wms database

CREATE TABLE group_users SELECT * FROM seldat_users.group_users;

DROP TABLE seldat_users.group_users;

-- replacing active column data with values that are congruent to tables\statuses\boolean class

ALTER TABLE `group_users` CHANGE `active` `oldActive` INT(2) NULL DEFAULT NULL;

ALTER TABLE `group_users` ADD `active` TINYINT(1) NOT NULL DEFAULT 1;

UPDATE `group_users` g
JOIN statuses s ON s.id = g.oldActive
SET active = s.displayName = "Active";

ALTER TABLE `group_users` DROP oldActive;

-- replacing active column data with values that are congruent to tables\statuses\boolean class

ALTER TABLE `vendors` CHANGE `active` `oldActive` INT(2) NULL DEFAULT NULL;

ALTER TABLE `vendors` ADD `active` TINYINT(1) NOT NULL DEFAULT 1;

UPDATE `vendors` v
JOIN statuses s ON s.id = v.oldActive
SET active = s.displayName = "Active";

ALTER TABLE `vendors` DROP oldActive;

-- add indices and autoincrement to group_users that was moved from seldat_users database

ALTER TABLE `group_users` 
    ADD PRIMARY KEY (`id`), 
    ADD UNIQUE KEY `userID` (`userID`,`vendorID`), 
    ADD KEY `active` (`active`);

ALTER TABLE `group_users` MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

-- removing groups table from seldat_users database. This table was moved to wms database long ago

DROP TABLE IF EXISTS seldat_users.groups;

-- Vadzim
-- 9/21/2015

-- adding fields to track Mezzanine transfer arrival

ALTER TABLE `transfers` ADD `barcode` VARCHAR(20) NOT NULL;
ALTER TABLE `transfers` ADD `discrepancy` INT(8) DEFAULT NULL;

UPDATE `transfers` 
SET `barcode` = LEFT(MD5(id), 20);

-- Make a page for Mezzanine transfer confirmation scanner after "Mezzanine Inventory Transfer" page

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`) 
SELECT    sm.id AS subMenuID,
   	      "Confirm Mezzanine Transfers" AS displayName,
          m.displayOrder + 0.5 AS displayOrder,
          "confirmMezzanineTransfers" AS hiddenName,
          "scanners" AS `class`,
          "confirmMezzanineTransfers" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    displayOrder
    FROM      pages
    WHERE     displayName = "Mezzanine Inventory Transfer"
	AND       active
) m
WHERE     sm.displayName = "Administration"
AND       active
LIMIT 1;

-- Duy Nguyen

INSERT INTO `crons`.`tasks` (`displayName`, `server`, `site`, `app`, `class`, `method`, `frequency`, `active`) 
VALUES ('Email Invoice Notifications', 'localhost', 'mvc', 'wms', 'appCrons', 'emailInvoiceNotifications', '60', '1');

-- Phong Tran
-- 9/22/2015

-- Add active feild in upcs_assigned table.
ALTER TABLE `upcs_assigned` ADD `active` TINYINT(1) NOT NULL DEFAULT '1' ;

-- Tri Le
-- 9/25/2015

-- Create 3 tables (awms_new_features, release_versions, version_info) for new feature: Show New Feature.

CREATE TABLE `awms_new_features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `versionID` int(11) NOT NULL,
  `featureName` varchar(255) NOT NULL,
  `featureDescription` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `versionID` (`versionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `release_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `versionName` varchar(50) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `version_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `versionID` int(11) NOT NULL,
  `isShow` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Phong Tran
-- 9/28/2015

-- Drop active field in upcs_assigned table
ALTER TABLE upcs_assigned DROP active;

-- Change unique index in upcs_assigned table
ALTER TABLE upcs_assigned DROP INDEX userID, ADD UNIQUE userID (userID, upcID);

-- Phong Tran
-- 9/29/2015

-- Add hiddenName in groups table.
ALTER TABLE `groups` ADD `hiddenName` VARCHAR(50) NOT NULL AFTER `groupName`;

-- Update hiddenName value
UPDATE `groups` SET `hiddenName`='manager' WHERE (`groupName`='Manager');
UPDATE `groups` SET `hiddenName`='CSR' WHERE (`groupName`='CSR');
UPDATE `groups` SET `hiddenName`='warehouseStaff' WHERE (`groupName`='Warehouse Staff');
UPDATE `groups` SET `hiddenName`='dataEntry' WHERE (`groupName`='Data Entry');
UPDATE `groups` SET `hiddenName`='pickingStaff' WHERE (`groupName`='Picking Staff');
UPDATE `groups` SET `hiddenName`='shippingStaff' WHERE (`groupName`='Shipping Staff');
UPDATE `groups` SET `hiddenName`='invoicingStaff' WHERE (`groupName`='Invoicing Staff');

-- Add hiddenName Unique index
ALTER TABLE `groups`
ADD UNIQUE INDEX `hiddenName` (`hiddenName`) USING BTREE;

-- Add Mezzanine Admin Group in groups table
INSERT INTO groups (
    groupName, hiddenName, description, active
) VALUES (
    'Mezzanine Admin', 'mezzanineAdmin', 'Mezzanine Admin', '1'
) ON DUPLICATE KEY UPDATE
    description = 'Mezzanine Admin',
    active = '1';

-- Phong Tran
-- 9/29/2015

-- Add catID index in upcs table
ALTER TABLE upcs ADD INDEX `catID` (`catID`);

-- Tri Le
-- 10/1/2015

-- Add submenu "List of Changes" in Administration Menu
INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "List of Changes" AS displayName,
          maxOrder + 1 AS displayOrder,
          "updatesList" AS hiddenName,
          "updatesList" AS `class`,
          "modify" AS `method`,
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

-- Add page params for "List of Changes"

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
          "type" AS `name`,
          "listFeature" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "updatesList";

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
          "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "updatesList";

--  Add "Edit Release Version" in menu Administration
INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "Edit Release Version" AS displayName,
          maxOrder + 1 AS displayOrder,
          "editReleaseVersion" AS hiddenName,
          "updatesList" AS `class`,
          "modify" AS `method`,
          0 AS red,
          0 AS active
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

-- Add page params for "Edit Release Version"

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
          "type" AS `name`,
          "releaseVersion" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "editReleaseVersion";

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
          "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "editReleaseVersion";

-- Phong Tran
-- 10/01/2015

-- Add Mezzanine User Status in statuses table.
INSERT INTO statuses (`category`, `displayName`, `shortName`) VALUES ('reports', 'Transfer Mezzanine User', 'METU');

-- Change default isSent value to 1
ALTER TABLE `reports_data` CHANGE `isSent` `isSent` TINYINT(1) NOT NULL DEFAULT '1';

-- VAdzim
-- 10/10/2015

ALTER TABLE `statuses` CHANGE `displayName` `displayName` VARCHAR(30);

-- renaming existing Mezzanine Transfer Statuses

UPDATE `statuses` 
SET   displayName = 'Transfer Mezzanine User' 
WHERE shortName = 'METU';

-- moving isSent field from reports_data to reports table

ALTER TABLE `reports_data` DROP `isSent`;

ALTER TABLE `reports` ADD COLUMN `isSent` TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE reports_data ADD UNIQUE uniqueReport (reportID, primeKey, statusID);

ALTER TABLE `locations_info` ADD COLUMN `vendorID` INT(5) NOT NULL AFTER `id`;

-- Tri Le
-- 10/19/2015

-- Remove fields: sku, size1, color1, size2, color2, size3, color3 in
-- inventory_batches table

ALTER TABLE `inventory_batches`
  DROP `sku`,
  DROP `size1`,
  DROP `color1`;
