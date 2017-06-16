-- Add short DB commands in here
-- Or list the name of a file that needs to be applied to the DB

-- Example 1
-- ALTER TABLE vendor_users ADD `active` INT(2) NOT NULL, ADD INDEX (`active`);
-- UPDATE seldat_users.vendor_users v JOIN statuses s SET v.active = s.id
-- WHERE s.displayName = 'Active' AND s.category = 'vendors';

-- Example 2
-- Table added: See file newInventoryTable.sql


-- Vadzim
-- 05/22/2015
-- adding new handelings to workorders table

ALTER TABLE `workorder` ADD `qtyHNewCart` INT(5) NULL DEFAULT NULL AFTER `qtyHRmPTS`;
ALTER TABLE `workorder` ADD `qtyHBubWrap` INT(5) NULL DEFAULT NULL AFTER `qtyHNewCart`;

INSERT INTO `refs`(`ref`, `work`, `inputName`, `prefix`, `Active`) VALUES 
('HDL-NEW-CAROTNS', 'NEW CAROTNS', 'woHNewCart', 'wo', '1'),
('HDL-BUBBLE-WRAP', 'BUBBLE WRAP', 'woHBubWrap', 'wo', '1');

ALTER TABLE `invoices_work_orders` ADD `woHNewCart` DECIMAL(8,2) NULL DEFAULT NULL AFTER `woHRmPTS`;
ALTER TABLE `invoices_work_orders` ADD `woHBubWrap` DECIMAL(8,2) NULL DEFAULT NULL AFTER `woHNewCart`;

-- creating common logger tables for tracking changes to tables fields

RENAME TABLE cartons_logs TO logs_cartons;

CREATE TABLE `logs_orders` LIKE `logs_cartons`;

RENAME TABLE cartons_logs_adds TO logs_adds;

ALTER TABLE `logs_adds` DROP INDEX invID;
ALTER TABLE `logs_adds` CHANGE `invID` `primeKey` INT(8) NOT NULL;
ALTER TABLE `logs_adds` ADD INDEX (primeKey);

RENAME TABLE cartons_logs_fields TO logs_fields;

RENAME TABLE cartons_logs_values TO logs_values;

ALTER TABLE `logs_values` DROP INDEX invID;
ALTER TABLE `logs_values` CHANGE `invID` `primeKey` INT(8) NOT NULL;
ALTER TABLE `logs_values` ADD INDEX (primeKey);

ALTER TABLE `logs_fields` CHANGE `displayName` `displayName` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
INSERT INTO `logs_fields`(`displayName`, `tableName`) VALUES ('RoutedStatusID', 'statuses');

-- manually drop order_control table

-- Vadzim
-- 05/26/2015
-- adding new columns to invoices_receiving table

ALTER TABLE `invoices_receiving` 
    ADD `totalFreight` DECIMAL(8,2) NULL DEFAULT NULL, 
    ADD `totalFP` DECIMAL(8,2) NULL DEFAULT NULL, 
    ADD `totalVT` DECIMAL(8,2) NULL DEFAULT NULL, 
    ADD `totalDT` DECIMAL(8,2) NULL DEFAULT NULL, 
    ADD `totalBF` DECIMAL(8,2) NULL DEFAULT NULL, 
    ADD `totalRush` DECIMAL(8,2) NULL DEFAULT NULL, 
    ADD `totalSpec` DECIMAL(8,2) NULL DEFAULT NULL;

-- 05/26/2015
-- adding category field to logs_fields table

ALTER TABLE `logs_fields` ADD `category` VARCHAR(10) NOT NULL ;

UPDATE `logs_fields` SET `category` = 'cartons';
UPDATE `logs_fields` SET `category` = 'orders' WHERE `displayName` = 'RoutedStatusID';
INSERT INTO `logs_fields`(`displayName`, `tableName`, `category`) VALUES ('statusID', 'statuses', 'orders');

-- 05/26/2015
-- adding unique for reserved cartons

ALTER TABLE pick_cartons ADD UNIQUE uniqueCarton (orderID, cartonID, active);

-- 06/01/2015
-- adding missing indices

ALTER TABLE inventory_splits ADD INDEX childID (childID);
ALTER TABLE neworder ADD INDEX order_batch (order_batch);
ALTER TABLE pick_waves ADD INDEX statusID (statusID);
ALTER TABLE inventory_cartons ADD INDEX statusID (statusID);
ALTER TABLE order_batches ADD INDEX vendorID (vendorID);
ALTER TABLE neworder ADD INDEX userID (userID);
ALTER TABLE neworder ADD INDEX location (location);
ALTER TABLE neworder ADD INDEX holdStatusID (holdStatusID);
ALTER TABLE neworder ADD INDEX RoutedStatusID (RoutedStatusID);
ALTER TABLE neworder ADD INDEX isError (isError);
ALTER TABLE neworder ADD INDEX `type` (`type`);
ALTER TABLE neworder ADD INDEX picklist (picklist);
ALTER TABLE pick_errors ADD INDEX upcID (upcID);
ALTER TABLE workorder ADD INDEX userID (userID);
ALTER TABLE workorder ADD INDEX status (status);
ALTER TABLE workorder ADD INDEX relatedtocustomer (relatedtocustomer);
ALTER TABLE online_orders_exports ADD INDEX orderBatch (orderBatch);
ALTER TABLE costs ADD INDEX refID (refID);
ALTER TABLE inventory_cartons ADD INDEX mLocID (mLocID);
ALTER TABLE inventory_cartons ADD INDEX mStatusID (mStatusID);
ALTER TABLE inventory_control ADD INDEX inventoryID (inventoryID);
ALTER TABLE invoices_processing ADD INDEX statusID (statusID);
ALTER TABLE invoices_work_orders ADD INDEX statusID (statusID);
ALTER TABLE neworder ADD INDEX payBy (payBy);

-- renaming poorly named index

ALTER TABLE pick_waves DROP INDEX pickID;
ALTER TABLE pick_waves ADD INDEX locID (locID);

-- Missing inventory carton indexes

ALTER TABLE inventory_cartons ADD INDEX (uom);
ALTER TABLE inventory_cartons ADD INDEX (cartonID);

-- Hau Nguyen
-- 06/03/2015
-- adding new tables for "Mezzanine Inventory Transfer" tool

CREATE TABLE `transfers` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `userID` int(4) NOT NULL,
  `createDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `transfer_items` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `transferID` int(10) NOT NULL,
  `vendorID` int(10) NOT NULL,
  `upcID` int(10) NOT NULL,
  `pieces` int(10) NOT NULL,
  `locationID` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `trasferID` (`transferID`),
  KEY `vendorID` (`vendorID`),
  KEY `upcID` (`upcID`),
  KEY `locationID` (`locationID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `transfer_cartons` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `transferItemID` int(10) NOT NULL,
  `cartonID` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transferItemID` (`transferItemID`),
  KEY `cartonID` (`cartonID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE inventory_cartons ADD INDEX (cartonID);

-- 6/4 add unit vol cost in to refs and invoices_processing table
INSERT INTO `refs` (`id`, `ref`, `work`, `inputName`, `prefix`, `Active`) VALUES (NULL, 'UNIT-VOL-COST', 'UNIT-VOL-COST', 'oprcVolCos', 'oprc', '1');
ALTER TABLE `invoices_processing` ADD `oprcVolCos` DECIMAL(8, 2) NULL DEFAULT NULL AFTER `oprcSOTime`;

-- Patrick
-- 06/04/2015
CREATE TABLE `invoices` (
    `id` INT(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT PRIMARY KEY ,  
    `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP 
) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `invoices_processing` ADD `invoiceID` INT(10) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `id`;
ALTER TABLE `invoices_storage` ADD `invoiceID` INT(10) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `id`;
ALTER TABLE `invoices_work_orders` ADD `invoiceID` INT(10) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `id`;
ALTER TABLE `invoices_receiving` ADD `invoiceID` INT(10) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `id`;

-- Hau Nguyen
-- 06/08/2015
-- This value will be used to determine whether a location is in the mezzanine or not
ALTER TABLE locations ADD COLUMN isMezzanine TINYINT(1) DEFAULT 0 AFTER isShipping;

-- Vadzim
-- 06/08/2015

-- changing workorder table `status` field to `statusID`

ALTER TABLE workorder DROP INDEX status;
ALTER TABLE workorder ADD `statusID` INT(3) NULL DEFAULT NULL;
ALTER TABLE workorder ADD INDEX statusID (statusID);

-- fill in `statusID` field with new status pattern (foreing key to `id` field in statuses table)
UPDATE workorder w
JOIN statuses s ON s.id = w.`status`
SET statusID = `status`;

-- fill in `statusID` field with old status pattern (WOCI, WOCO)
UPDATE workorder w
JOIN statuses s
SET statusID = s.id
WHERE s.shortName = w.status AND category = 'workorders';

-- drop `status` field

ALTER TABLE workorder DROP status;

-- creating logger for workorder table

CREATE TABLE `logs_workorders` LIKE `logs_orders`;
INSERT INTO `logs_fields`(`displayName`, `tableName`, `category`) VALUES ('statusID', 'statuses', 'workorders');


UPDATE `statuses` SET `category` = 'orders' 
WHERE `statuses`.`category` = 'order' AND shortName = 'CNCL';

-- Vadzim
-- 06/12/2015

-- adding users groups
-- !! preface table names with a DB name (`seldat_users`.) !!

CREATE TABLE IF NOT EXISTS `groups` (
    `id` int(2) NOT NULL AUTO_INCREMENT,
    `groupName` VARCHAR(20) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE groupName (groupName)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

RENAME TABLE `vendor_users` TO `group_users`;

-- !! end of prefacing table names with a DB name !!

CREATE TABLE IF NOT EXISTS `user_groups` (
    `id` int(2) NOT NULL AUTO_INCREMENT,
    `userID` int(2) NULL DEFAULT NULL,
    `groupID` INT(2) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `userID` (`userID`),
    KEY `groupID` (`groupID`),
    UNIQUE `uniqueUser` (`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- Vadzim
-- 06/15/2015

-- adding volume and weight columns to neworder table

ALTER TABLE `neworder` ADD `totalVolume` VARCHAR(10) NULL AFTER `numberofpiece`;
ALTER TABLE `neworder` ADD `totalWeight` VARCHAR(10) NULL AFTER `totalVolume`;

-- Tables to record which containers have sent reports to client

CREATE TABLE IF NOT EXISTS `reports` (
`id` int(7) NOT NULL,
  `setDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
ALTER TABLE `reports`
 ADD PRIMARY KEY (`id`);

CREATE TABLE IF NOT EXISTS `report_containers` (
`id` int(8) NOT NULL,
  `reportID` int(7) NOT NULL,
  `recNum` int(8) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
ALTER TABLE `report_containers`
 ADD PRIMARY KEY (`id`), ADD KEY `reportID` (`reportID`), ADD KEY `recNum` (`recNum`);

-- Phong Tran
-- 06/22/2015

-- adding volume and weight columns to neworder table 
 
ALTER TABLE `invoices_storage` ADD `storPallet` decimal(8,2) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `storVol`;

ALTER TABLE `invoices_receiving` DROP INDEX `rush`;

ALTER TABLE `invoices_receiving` ADD `recOT` decimal(8,2) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `recRush`;

ALTER TABLE `invoices_receiving` ADD `recLabour` decimal(8,2) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `recOT`;

ALTER TABLE `invoices_receiving` ADD `recQC` decimal(8,2) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `recLabour`; 

ALTER TABLE `invoices_receiving` CHANGE `totalFreight` `recFreight` DECIMAL(8,2) NULL DEFAULT NULL;

ALTER TABLE `invoices_receiving` CHANGE `totalFP` `recFP` DECIMAL(8,2) NULL DEFAULT NULL;

ALTER TABLE `invoices_receiving` CHANGE `totalVT` `recVT` DECIMAL(8,2) NULL DEFAULT NULL;

ALTER TABLE `invoices_receiving` CHANGE `totalDT` `recDT` DECIMAL(8,2) NULL DEFAULT NULL;

ALTER TABLE `invoices_receiving` CHANGE `totalBF` `recBF` DECIMAL(8,2) NULL DEFAULT NULL;

ALTER TABLE `invoices_receiving` CHANGE `totalSpec` `recSpec` DECIMAL(8,2) NULL DEFAULT NULL;

ALTER TABLE `invoices_receiving` ADD `recNum` int(10) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `invoiceBatch`; 

ALTER TABLE `invoices_storage` ADD `recNum` int(10) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `invoiceID`; 

ALTER TABLE `invoices_processing` ADD `oprcSOSpec` DECIMAL(8,2) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `oprcVolCos`; 

ALTER TABLE `invoices_processing` ADD `oprcSPltP` DECIMAL(8,2) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER  `oprcSOSpec`; 

ALTER TABLE `invoices_processing` ADD `oprcSQC` DECIMAL(8,2) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `oprcSPltP`; 

ALTER TABLE `invoices_processing` ADD `oprcSSpec` DECIMAL(8,2) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `oprcSQC`; 

ALTER TABLE `invoices_processing` ADD `oprcSUCC12` DECIMAL(8,2) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `oprcSSpec`; 

ALTER TABLE `invoices_processing` ADD `oprcSUnOut` DECIMAL(8,2) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `oprcSUCC12`; 

ALTER TABLE `invoices_processing` ADD `oprcSVAT` DECIMAL(8,2) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `oprcSUnOut`; 

UPDATE `refs` SET `inputName`='recQC' WHERE (`ref`='REC-QC');
UPDATE `refs` SET `inputName`='recOT' WHERE (`ref`='REC-OT');

ALTER TABLE `inventory_cartons` ADD `InvoiceDate` TIMESTAMP NOT NULL AFTER `rackDate`;


CREATE TABLE `invoices_storage` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `invoiceID` int(8) DEFAULT NULL,
  `recNum` int(8) unsigned zerofill DEFAULT NULL,
  `recInvoiceID` int(2) DEFAULT NULL,
  `setDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `startDate` timestamp NULL DEFAULT NULL,
  `endDate` timestamp NULL DEFAULT NULL,
  `actualCarton` decimal(8,2) DEFAULT NULL,
  `actualPieces` decimal(8,2) DEFAULT NULL,
  `recCarton` decimal(8,2) DEFAULT NULL,
  `recPieces` decimal(8,2) DEFAULT NULL,
  `shipCarton` decimal(8,2) DEFAULT NULL,
  `shipPieces` decimal(8,2) DEFAULT NULL,
  `actualVolume` decimal(8,2) DEFAULT NULL,
  `totalPrevious` decimal(8,2) DEFAULT NULL,
  `typeID` int(2) DEFAULT NULL,
  `statusID` int(2) DEFAULT NULL,
  `storCart` decimal(8,2) DEFAULT NULL,
  `storEach` decimal(8,2) DEFAULT NULL,
  `storVol` decimal(8,2) DEFAULT NULL,
  `storPallet` decimal(8,2) unsigned zerofill DEFAULT NULL,
  `totalCart` decimal(8,2) DEFAULT NULL,
  `totalEach` decimal(8,2) DEFAULT NULL,
  `totalPallet` decimal(8,2) DEFAULT NULL,
  `totalVol` decimal(8,2) DEFAULT NULL,
  `total` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `statusID` (`statusID`),
  KEY `typeID` (`typeID`)
) ENGINE=InnoDB AUTO_INCREMENT=863 DEFAULT CHARSET=utf8;


-- Vadzim
-- 06/22/2015

-- adding active/inactive status for user groups

ALTER TABLE `user_groups` ADD `active` BOOLEAN NOT NULL;

-- replacing UNIQUE expression in user_groups

ALTER TABLE user_groups DROP INDEX uniqueUser;
ALTER TABLE user_groups ADD UNIQUE uniqueUserGroup (userID, groupID);

-- Phong Tran
-- 06/23/2015
ALTER TABLE `inventory_cartons` CHANGE `rackDate` `recDate` TIMESTAMP NOT NULL, CHANGE `InvoiceDate` `shipDate` TIMESTAMP NOT NULL;
ALTER TABLE `invoices_storage` DROP `recInvoiceID`;

-- Phong Tran
-- 2015/06/15
ALTER TABLE `refs` ADD `sort` INT(3) NOT NULL AFTER `prefix`;

-- Vadzim
-- 6/26/2015
-- using "camel" case for a field name

UPDATE logs_fields
SET displayName = 'routedStatusID'
WHERE displayName = 'RoutedStatusID';

-- add new statuses to logs_fields table

INSERT INTO `logs_fields`(`displayName`, `tableName`, `category`) VALUES ('holdStatusID', 'statuses', 'orders');
INSERT INTO `logs_fields`(`displayName`, `tableName`, `category`) VALUES ('isError', 'statuses', 'orders');

-- add UNIQUE to workorder table
ALTER TABLE `invoices_receiving` 
CHANGE `invoiceID` `invoiceID` INT(10) UNSIGNED ZEROFILL NULL DEFAULT NULL, 
CHANGE `invoiceBatch` `invoiceBatch` INT(8) NULL DEFAULT NULL, 
CHANGE `recNum` `recNum` INT(8) UNSIGNED ZEROFILL NULL DEFAULT NULL, 
CHANGE `inventoryBatch` `inventoryBatch` INT(8) NULL DEFAULT NULL, 
CHANGE `recUnits` `recUnits` DECIMAL(4,2) NOT NULL, 
CHANGE `recCC` `recCC` DECIMAL(4,2) NOT NULL, 
CHANGE `recCV` `recCV` DECIMAL(4,2) NOT NULL, 
CHANGE `recRush` `recRush` DECIMAL(4,2) NOT NULL,
CHANGE `recOT` `recOT` DECIMAL(8,2) NOT NULL, 
CHANGE `recLabour` `recLabour` DECIMAL(8,2) NOT NULL, 
CHANGE `recQC` `recQC` DECIMAL(8,2) NOT NULL;

-- Phong tran
-- 2015/06/26

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
ALTER TABLE workorder ADD UNIQUE workordernumberUnique (workordernumber);

-- Phong Tran
-- 06/29/2015
ALTER TABLE `inventory_cartons` 
CHANGE `recDate` `recDate` TIMESTAMP NULL DEFAULT NULL, 
CHANGE `shipDate` `shipDate` TIMESTAMP NULL DEFAULT NULL;
-- Phong Tran
-- 07/02/2015
ALTER TABLE `invoices_storage`
DROP COLUMN `inventoryBatch`,
ADD COLUMN `inventoryBatch`  int(8) NULL AFTER `recNum`;


-- Log fields for logging setting orders to error status
INSERT INTO logs_fields (`displayName`, `tableName`, `category`) 
VALUES ('isError', 'newOrder', 'orders');

-- isMezzanine field was added but never set for mezzanine locations
UPDATE locations SET isMezzanine = 1 WHERE displayName like 'z%';

-- Vadzim
-- 6/30/2015

UPDATE workorder w
SET relatedtocustomer = 0
WHERE relatedtocustomer = 2;

-- Vadzim
-- 7/02/2015

-- create directory tables for online_orders_exports signatures, bill to, providers, packages and services type fields

CREATE TABLE IF NOT EXISTS `online_orders_exports_providers` (
    `id` int(2) NOT NULL AUTO_INCREMENT,
    `displayName` VARCHAR(20) NULL DEFAULT NULL,
    `active` BOOLEAN NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `online_orders_exports_providers` (`id`, `displayName`, `active`) VALUES
(1, 'UPS', 1),
(2, 'FedEx', 1);

CREATE TABLE IF NOT EXISTS `online_orders_exports_packages` (
    `id` int(3) NOT NULL AUTO_INCREMENT,
    `providerID` int(2) NOT NULL,
    `shortName` VARCHAR(50) NULL DEFAULT NULL,
    `displayName` VARCHAR(50) NULL DEFAULT NULL,
    `active` BOOLEAN NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `providerID` (`providerID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `online_orders_exports_packages` (
    `providerID`, 
    `shortName`, 
    `displayName`, 
    `active`) 
VALUES
(1, '02', 'Your Packaging', 1),
(1, '01', 'UPS Letter', 1),
(1, '21', 'UPS Express Box', 1),
(1, '04', 'UPS  PAK', 1),
(1, '2a', 'UPS Small Express Box', 1),
(1, '2b', 'UPS Medium Express Box', 1),
(1, '2c', 'UPS Large Express Box', 1),
(1, '24', 'UPS  25KG Box', 1),
(1, '25', 'UPS 10KG Box', 1),
(2, '25', 'UPS 10KG Box', 1),
(2, 'YOUR_PACKAGING', 'YOUR PACKAGING', 1),
(2, 'FEDEX_ENVELOPE', 'FEDEX ENVELOPE', 1),
(2, 'FEDEX_BOX', 'FEDEX BOX', 1),
(2, 'FEDEX_PAK', 'FEDEX PAK', 1),
(2, 'FEDEX_10KG_BOX', 'FEDEX 10KG BOX', 1),
(2, 'FEDEX_25KG_BOX', 'FEDEX 25KG BOX', 1),
(2, 'FEDEX_TUBE', 'FEDEX TUBE', 1);

CREATE TABLE IF NOT EXISTS `online_orders_exports_services` (
    `id` int(3) NOT NULL AUTO_INCREMENT,
    `providerID` int(2) NOT NULL,
    `shortName` VARCHAR(50) NULL DEFAULT NULL,
    `displayName` VARCHAR(50) NULL DEFAULT NULL,
    `active` BOOLEAN NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `providerID` (`providerID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `online_orders_exports_services` (
    `providerID`, 
    `shortName`, 
    `displayName`, 
    `active`) 
VALUES
(1, '01', 'UPS Next Day Air', 1),
(1, '13', 'Next Day Air Saver', 1),
(1, '59', 'UPS Second Day Air AM', 1),
(1, '02', 'UPS Second Day Air', 1),
(1, '12', 'UPS Three-Day Select', 1),
(1, '14', 'UPS Next Day Air Early AM', 1),
(1, '07', 'UPS Worldwide Express', 1),
(1, '08', 'UPS Worldwide Expedited', 1),
(1, '11', 'UPS Standard to Canada', 1),
(1, '54', 'UPS Worldwide Express Plus', 1),
(1, '65', 'UPS Worldwide Saver', 1),
(1, '93', 'UPS SurePost', 1),
(1, '11', 'UPS Standard to Mexico', 1),
(2, 'PRIORITY_OVERNIGHT', 'Priority Overnight', 1),
(2, 'STANDARD_OVERNIGHT', 'Standard Overnight', 1),
(2, 'FEDEX_2_DAY', 'Fedex 2 Day', 1),
(2, 'FEDEX_2_DAY_AM', 'FEDEX 2 DAY AM', 1),
(2, 'FEDEX_EXPRESS_SAVER', 'Fedex Express Saver', 1),
(2, 'FEDEX_GROUND', 'Fedex Ground', 1),
(2, 'GROUND_HOME_DELIVERY', 'Ground Home Delivery', 1),
(2, 'INTERNATIONAL_PRIORITY', 'International Priority', 1),
(2, 'INTERNATIONAL_ECONOMY', 'International Economy', 1),
(2, 'FIRST_OVERNIGHT', 'First Overnight', 1),
(2, 'INTERNATIONAL_FIRST', 'International First', 1),
(2, 'SMART_POST', 'Smart Post', 1);

CREATE TABLE IF NOT EXISTS `online_orders_exports_signatures` (
    `id` int(2) NOT NULL AUTO_INCREMENT,
    `displayName` VARCHAR(50) NULL DEFAULT NULL,
    `active` BOOLEAN NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `online_orders_exports_signatures` (`displayName`, `active`) VALUES
('Adult Signature Required', 1),
('Indirect Signature / Confirmation', 1),
('Direct Signature', 1);

CREATE TABLE IF NOT EXISTS `online_orders_exports_bill_to` (
    `id` int(2) NOT NULL AUTO_INCREMENT,
    `displayName` VARCHAR(50) NULL DEFAULT NULL,
    `active` BOOLEAN NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `online_orders_exports_bill_to` (`displayName`, `active`) VALUES
('Sender', 1),
('3rd Party', 1);

-- change online_orders_exports fields to 

ALTER TABLE `online_orders_exports` CHANGE `provider` `providerID` 
    INT(2) NULL DEFAULT NULL;
ALTER TABLE `online_orders_exports` CHANGE `package_type` `packageID` 
    INT(3) NULL DEFAULT NULL;
ALTER TABLE `online_orders_exports` CHANGE `service` `serviceID` 
    INT(3) NULL DEFAULT NULL;
ALTER TABLE `online_orders_exports` CHANGE `bill_to` `billToID` 
    INT(2) NULL DEFAULT NULL;
ALTER TABLE `online_orders_exports` CHANGE `signature` `signatureID` 
    INT(2) NULL DEFAULT NULL;

ALTER TABLE `online_orders_exports` ADD INDEX providerID (providerID);
ALTER TABLE `online_orders_exports` ADD INDEX packageID (packageID);
ALTER TABLE `online_orders_exports` ADD INDEX serviceID (serviceID);
ALTER TABLE `online_orders_exports` ADD INDEX billToID (billToID);
ALTER TABLE `online_orders_exports` ADD INDEX signatureID (signatureID);

INSERT INTO `online_orders_exports_signatures` (`displayName`, `active`) VALUES
('Not Required', 1);

INSERT INTO `online_orders_exports_bill_to` (`displayName`, `active`) VALUES
('Not Required', 1);

ALTER TABLE online_orders_exports_packages 
    ADD UNIQUE uniquePackage (providerID, shortName);

UPDATE `online_orders_exports_signatures`
SET `displayName` = 'None Required'
WHERE `displayName` = 'Not Required';

UPDATE `online_orders_exports_bill_to`
SET `displayName` = 'None Required'
WHERE `displayName` = 'Not Required';


-- Vadzim
-- 7/02/2015

-- create tables to limit user's access to site pages

-- move groups table from users DB to test/live DB

DROP TABLE IF EXISTS groups;

CREATE TABLE IF NOT EXISTS `groups` (
    `id` int(2) NOT NULL AUTO_INCREMENT,
    `groupName` VARCHAR(20) NULL DEFAULT NULL,
    `description` VARCHAR(200) NULL DEFAULT NULL,
    `active` BOOLEAN NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE groupName (groupName)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `groups` (`id`, `groupName`, `description`, `active`) VALUES
(1, 'Manager', NULL, 1),
(2, 'CSR', NULL, 1),
(3, 'Warehouse Staff', NULL, 1),
(4, 'Data Entry', NULL, 1),
(5, 'Picking Staff', NULL, 1),
(6, 'Shipping Staff', NULL, 1),
(7, 'Invoicing Staff', NULL, 1);

CREATE TABLE `subMenus` (
    `id` INT(2) NOT NULL AUTO_INCREMENT,
    `displayName` VARCHAR(50) NOT NULL,
    `displayOrder` FLOAT NOT NULL,
    `active` BOOLEAN NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE `displayName` (`displayName`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO `submenus` (`id`, `displayName`, `displayOrder`, `active`) VALUES
(1, 'WMS', 1, 1),
(2, 'Search Data', 2, 1),
(3, 'Search Logs', 3, 1),
(4, 'Online Order', 4, 1),
(5, 'Invoice', 5, 1),
(6, 'NSI', 6, 1),
(7, 'Edit Data', 7, 1),
(8, 'Administration', 8, 1),
(9, 'Inventory Control', 9, 1),
(10, 'DataBase', 10, 1),
(11, 'Page Tester', 11, 1),
(12, 'Sign Out', 12, 1);

CREATE TABLE `pages` (
    `id` INT(3) NOT NULL AUTO_INCREMENT,
    `subMenuID` INT(2) NOT NULL,
    `displayName` VARCHAR(50) NOT NULL,
    `displayOrder` FLOAT NOT NULL,
    `hiddenName` VARCHAR(50) NOT NULL,
    `class` VARCHAR(50) NULL DEFAULT NULL,
    `method` VARCHAR(50) NULL DEFAULT NULL,
    `red` BOOLEAN NULL DEFAULT NULL,
    `active` BOOLEAN NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE `hiddenName` (`hiddenName`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO `pages` (
    `id`, 
    `subMenuID`, 
    `displayName`, 
    `displayOrder`, 
    `hiddenName`, 
    `class`, 
    `method`, 
    `red`, 
    `active`
) VALUES
(1, 1, 'Scan Container', 1, 'scanContainer', 'seldatContainers', 'scan', 0, 1),
(2, 1, 'RC Logs', 2, 'rCLogs', 'receiving', 'recordTallySheets', 0, 1),
(3, 1, 'Receiving / Back To Stock', 3, 'receiving/BackToStock', 'scanners', 'receivingToStock', 0, 1),
(4, 1, 'Scan To Location', 4, 'scanToLocation', 'scanners', 'plateLocation', 0, 1),
(5, 1, 'Order Check-In', 5, 'orderCheckIn', 'orders', 'addOrEdit', 0, 1),
(6, 1, 'Order Check-Out', 6, 'orderCheckOut', 'scanners', 'orderEntry', 1, 1),
(7, 1, 'Routed Check-In', 7, 'routedCheckIn', 'scanners', 'orderEntry', 0, 1),
(8, 1, 'Routed Check-Out', 8, 'routedCheckOut', 'scanners', 'orderEntry', 0, 1),
(9, 1, 'Picking Check-In', 9, 'pickingCheckIn', 'scanners', 'orderEntry', 1, 1),
(10, 1, 'Picking Check-Out', 10, 'pickingCheckOut', 'scanners', 'orderEntry', 1, 1),
(11, 1, 'Work Order Check-In', 11, 'workOrderCheckIn', 'scanners', 'workOrderCheckIn', 0, 1),
(12, 1, 'Work Order Check-Out', 12, 'workOrderCheckOut', 'scanners', 'workOrderCheckOut', 0, 1),
(13, 1, 'Order Processing Check-In', 13, 'orderProcessingCheckIn', 'scanners', 'orderEntry', 1, 1),
(14, 1, 'Order Processing Check-Out', 14, 'orderProcessingCheckOut', 'scanners', 'orderProcessCheckOut', 1, 1),
(15, 1, 'Shipping Check-In', 15, 'shippingCheckIn', 'scanners', 'plateLocation', 1, 1),
(16, 1, 'Shipped Check-Out', 16, 'shippedCheckOut', 'scanners', 'shipped', 1, 1),
(17, 1, 'Hold Orders', 17, 'holdOrders', 'scanners', 'orderHold', 0, 1),
(18, 1, 'Error Orders Release', 18, 'errorOrdersRelease', 'scanners', 'orderEntry', 0, 1),
(19, 1, 'Cancel Orders', 19, 'cancelOrders', 'scanners', 'orderEntry', 0, 1),
(20, 1, 'Move Orders to a Batch', 20, 'moveOrderstoaBatch', 'scanners', 'batch', 0, 1),
(21, 2, 'Search Inventory', 1, 'searchInventory', 'inventory', 'search', 0, 1),
(22, 2, 'Search Orders', 2, 'searchOrders', 'orders', 'search', 0, 1),
(23, 2, 'Search Online Orders', 3, 'searchOnlineOrders', 'onlineOrders', 'search', 0, 1),
(24, 2, 'Search Work Orders', 4, 'searchWorkOrders', 'workOrders', 'search', 0, 1),
(25, 2, 'Search Receiving Invoices', 5, 'searchReceivingInvoices', 'invoices', 'list', 0, 1),
(26, 2, 'Search Storage Invoices', 6, 'searchStorageInvoices', 'invoices', 'list', 0, 1),
(27, 2, 'Search Received Containers', 7, 'searchReceivedContainers', 'containers', 'search', 0, 1),
(28, 2, 'Search Clients', 8, 'searchClients', 'vendors', 'search', 0, 1),
(29, 2, 'Search Warehouse Locations', 9, 'searchWarehouseLocations', 'locations', 'search', 0, 1),
(30, 2, 'Search Pallet Tally Sheets', 10, 'searchPalletTallySheets', 'plates', 'search', 0, 1),
(31, 2, 'Search Master Labels', 11, 'searchMasterLabels', 'masterLabels', 'list', 0, 1),
(32, 2, 'Print Master Labels', 12, 'printMasterLabels', 'masterLabels', 'list', 0, 1),
(33, 2, 'Reprint Carton Labels', 13, 'reprintCartonLabels', 'inventory', 'search', 0, 1),
(34, 2, 'Reprint Split Carton Labels', 14, 'reprintSplitCartonLabels', 'inventory', 'listSplitCartons', 0, 1),
(35, 2, 'Search Available Inventory', 15, 'searchAvailableInventory', 'inventory', 'components', 0, 1),
(36, 2, 'Search Pallets', 16, 'searchPallets', 'inventory', 'components', 0, 1),
(37, 2, 'Search Client Pallets', 17, 'searchClientPallets', 'inventory', 'components', 0, 1),
(38, 2, 'Search Plates', 18, 'searchPlates', 'inventory', 'components', 0, 1),
(39, 2, 'Search Style Locations', 19, 'searchStyleLocations', 'inventory', 'components', 0, 1),
(40, 2, 'Style Locations Scanner', 20, 'styleLocationsScanner', 'scanners', 'searchStyleLocations', 0, 1),
(41, 2, 'Search No Mezzanine Scanner', 21, 'searchNoMezzanineScanner', 'scanners', 'searchNoMezzanine', 0, 1),
(42, 2, 'Inactive Inventory Scanner', 22, 'inactiveInventoryScanner', 'scanners', 'searchInactiveInventory', 0, 1),
(43, 2, 'Search Wave Picks', 23, 'searchWavePicks', 'inventory', 'pickCartons', 0, 1),
(44, 2, 'Style UOMs Scanner', 24, 'styleUOMsScanner', 'scanners', 'searchStyleUOMs', 0, 1),
(45, 2, 'Search Shipped Inventory', 25, 'searchShippedInventory', 'inventory', 'components', 0, 1),
(46, 3, 'Search Cartons', 1, 'searchCartons', 'logs', 'components', 0, 1),
(47, 3, 'Search Orders', 2, 'searchOrdersLogs', 'logs', 'components', 0, 1),
(48, 3, 'Search Work Orders', 3, 'searchWorkOrdersLogs', 'logs', 'components', 0, 1),
(49, 4, 'Import Online Orders', 1, 'importOnlineOrders', 'onlineOrders', 'import', 0, 1),
(50, 4, 'List Import Failures', 2, 'listImportFailures', 'onlineOrders', 'listFails', 0, 1),
(51, 4, 'Incorrect Online Orders', 3, 'incorrectOnlineOrders', 'onlineOrders', 'incorrect', 0, 1),
(52, 4, 'Import Carrier Order Updates', 4, 'importCarrierOrderUpdates', 'onlineOrders', 'importCarrier', 0, 1),
(53, 4, 'List Carrier Update Failures', 5, 'listCarrierUpdateFailures', 'onlineOrders', 'listUpdateFails', 0, 1),
(54, 4, 'Print Packing Slip', 6, 'printPackingSlip', 'packingSlip', 'display', 0, 1),
(55, 4, 'Create Wave Pick', 7, 'createWavePick', 'wavePicks', 'create', 0, 1),
(56, 4, 'View Wave Picks', 8, 'viewWavePicks', 'wavePicks', 'list', 0, 1),
(57, 4, 'Scan Shipped Orders', 9, 'scanShippedOrders', 'scanners', 'shippedOrders', 0, 1),
(58, 5, 'Receiving Invoices', 1, 'receivingInvoices', 'invoices', 'list', 0, 1),
(59, 5, 'Order Processing Invoices', 2, 'orderProcessingInvoices', 'invoices', 'list', 0, 1),
(60, 5, 'Storage Invoices', 3, 'storageInvoices', 'invoices', 'list', 0, 1),
(61, 5, 'Work Orders Invoices', 4, 'workOrdersInvoices', 'invoices', 'list', 0, 1),
(62, 5, 'Client Costs', 5, 'clientCosts', 'costs', 'clients', 0, 1),
(63, 6, 'Receiving', 1, 'receiving', 'nsi', 'add', 0, 1),
(64, 6, 'Reciving Reprint Labels', 2, 'recivingReprintLabels', 'nsi', 'list', 0, 1),
(65, 6, 'Back To Stock', 3, 'backToStock', 'nsi', 'add', 0, 1),
(66, 6, 'Back To Stock Reprint Labels', 4, 'backToStockReprintLabels', 'nsi', 'list', 0, 1),
(67, 6, 'Shipping', 5, 'shipping', 'nsi', 'shipping', 0, 1),
(68, 7, 'Edit Inventory Containers', 1, 'editInventoryContainers', 'inventory', 'components', 0, 1),
(69, 7, 'Edit Inventory Batches', 2, 'editInventoryBatches', 'inventory', 'components', 0, 1),
(70, 7, 'Edit Inventory Cartons', 3, 'editInventoryCartons', 'inventory', 'components', 0, 1),
(71, 7, 'Edit Location Batches', 4, 'editLocationBatches', 'inventory', 'components', 0, 1),
(72, 7, 'Edit Orders', 5, 'editOrders', 'orders', 'search', 0, 1),
(73, 7, 'Edit Online Orders', 6, 'editOnlineOrders', 'onlineOrders', 'search', 0, 1),
(74, 7, 'Edit Work Orders', 7, 'editWorkOrders', 'workOrders', 'search', 0, 1),
(75, 7, 'Edit Receiving Invoices', 8, 'editReceivingInvoices', 'invoices', 'search', 0, 1),
(76, 7, 'Edit Processing Invoices', 9, 'editProcessingInvoices', 'invoices', 'search', 0, 1),
(77, 7, 'Edit Storage Invoices', 10, 'editStorageInvoices', 'invoices', 'search', 0, 1),
(78, 7, 'Edit Work Order Invoices', 11, 'editWorkOrderInvoices', 'invoices', 'search', 0, 1),
(114, 7, 'Edit Carrier Export Providers', 12, 'editCarrierExportProviders', 'onlineOrders', 'editDirectories', 0, 1),
(115, 7, 'Edit Carrier Export Packages', 13, 'editCarrierExportPackages', 'onlineOrders', 'editDirectories', 0, 1),
(116, 7, 'Edit Carrier Export Services', 14, 'editCarrierExportServices', 'onlineOrders', 'editDirectories', 0, 1),
(117, 7, 'Edit Carrier Export Bill To', 15, 'editCarrierExportBillTo', 'onlineOrders', 'editDirectories', 0, 1),
(118, 7, 'Edit Carrier Export Signatures', 16, 'editCarrierExportSignatures', 'onlineOrders', 'editDirectories', 0, 1),
(79, 7, 'Edit Clients', 17, 'editClients', 'vendors', 'search', 0, 1),
(80, 7, 'Administrator History', 18, 'administratorHistory', 'history', 'admin', 0, 1),
(81, 7, 'Edit Users', 19, 'editUsers', 'users', 'search', 0, 1),
(82, 7, 'Edit User Access', 20, 'editUserAccess', 'users', 'search', 0, 1),
(83, 7, 'Edit Client Users', 21, 'editClientUsers', 'users', 'search', 0, 1),
(84, 7, 'Edit Groups', 22, 'editGroups', 'users', 'search', 0, 1),
(85, 7, 'Edit Users Groups', 23, 'editUsersGroups', 'users', 'search', 0, 1),
(86, 7, 'Edit Pages', 24, 'editPages', 'users', 'search', 0, 1),
(87, 7, 'Edit Page Params', 25, 'editPageParams', 'users', 'search', 0, 1),
(88, 7, 'Edit Group Pages', 26, 'editGroupPages', 'users', 'search', 0, 1),
(89, 7, 'Edit Submenus', 27, 'editSubmenus', 'users', 'search', 0, 1),
(90, 8, 'Packing List', 1, 'packingList', 'admin', 'packingList', 0, 1),
(91, 8, 'Create Tally', 2, 'createTally', 'inventory', 'createTally', 0, 1),
(92, 8, 'Print Pallet Tally Sheets', 3, 'printPalletTallySheets', 'plates', 'recordSheets', 0, 1),
(93, 8, 'Generate License Plates', 4, 'generateLicensePlates', 'plates', 'listAdd', 0, 1),
(94, 8, 'Generate Order Labels', 5, 'generateOrderLabels', 'orderLabels', 'listAdd', 0, 1),
(95, 8, 'Generate Work Order Labels', 6, 'generateWorkOrderLabels', 'workOrders', 'addLabels', 0, 1),
(96, 8, 'Client Order Status Count', 7, 'clientOrderStatusCount', 'orders', 'getClientStatus', 0, 1),
(97, 8, 'Dashboard Receiving ', 8, 'dashboardReceiving', 'dashboards', 'display', 0, 1),
(98, 8, 'Dashboard Shipping', 9, 'dashboardShipping', 'dashboards', 'display', 0, 1),
(99, 8, 'Mezzanine Inventory Transfer', 10, 'mezzanineInventoryTransfer', 'transfers', 'list', 0, 1),
(100, 9, 'Search Adjustment', 1, 'searchAdjustment', 'adjustments', 'list', 0, 1),
(101, 9, 'Search Inventory to Adjust', 2, 'searchInventorytoAdjust', 'adjustments', 'list', 0, 1),
(102, 9, 'Search Inventory Control', 3, 'searchInventoryControl', 'inventory', 'components', 0, 1),
(103, 9, 'Consolidation Waves', 4, 'consolidationWaves', 'consolidation', 'waveOne', 0, 1),
(104, 9, 'Reset Locations', 5, 'resetLocations', 'scanners', 'adjust', 0, 1),
(105, 9, 'Split Cartons', 6, 'splitCartons', 'inventory', 'splitAll', 0, 1),
(106, 9, 'Unsplit Cartons', 7, 'unsplitCartons', 'inventory', 'listSplitCartons', 0, 1),
(107, 9, 'Pick Error Cartons', 8, 'pickErrorCartons', 'inventory', 'pickErrors', 0, 1),
(108, 9, 'Location Inquery Scanner', 9, 'locationInqueryScanner', 'scanners', 'locations', 0, 1),
(109, 9, 'Import Inventory', 10, 'importInventory', 'imports', 'inventory', 0, 1),
(110, 10, 'Run DB Check', 1, 'runDBCheck', 'databaseCheck', 'run', 0, 1),
(111, 11, 'Run Page Tests', 1, 'runPageTests', 'tester', 'pages', 0, 1),
(112, 12, 'Confirm Sign Out', 1, 'confirmSignOut', 'logout', '', 0, 1),
(113, 12, 'Change Password', 2, 'changePassword', 'login', 'changePassword', 0, 1);

CREATE TABLE `page_params` (
    `id` INT(3) NOT NULL AUTO_INCREMENT,
    `pageID` INT(3) NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `value` VARCHAR(50) NOT NULL,
    `active` BOOLEAN NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `pageID` (`pageID`),
    UNIQUE `uniquePageParam` (`pageID`, `name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`) VALUES
(6, 'process', 'orderCheckOut', 1),
(7, 'process', 'routedCheckIn', 1),
(8, 'process', 'routedCheckOut', 1),
(9, 'process', 'pickingCheckIn', 1),
(10, 'process', 'pickingCheckOut', 1),
(11, 'process', 'workOrderCheckIn', 1),
(12, 'process', 'workOrderCheckOut', 1),
(13, 'process', 'orderProcessingCheckIn', 1),
(15, 'process', 'checkIn', 1),
(16, 'process', 'checkOut', 1),
(18, 'process', 'errOrderRelease', 1),
(19, 'process', 'cancel', 1),
(25, 'type', 'receiving', 1),
(26, 'type', 'storage', 1),
(27, 'type', 'received', 1),
(31, 'type', 'search', 1),
(32, 'type', 'print', 1),
(34, 'type', 'reprint', 1),
(35, 'show', 'available', 1),
(36, 'show', 'pallets', 1),
(37, 'show', 'vendorPallets', 1),
(38, 'show', 'plates', 1),
(39, 'show', 'styleLocations', 1),
(45, 'show', 'shipped', 1),
(46, 'show', 'cartons', 1),
(47, 'show', 'orders', 1),
(48, 'show', 'workOrders', 1),
(58, 'type', 'receiving', 1),
(59, 'type', 'processing', 1),
(60, 'type', 'storage', 1),
(61, 'type', 'workorders', 1),
(63, 'receiving', 'display', 1),
(68, 'show', 'containers', 1),
(69, 'show', 'batches', 1),
(70, 'show', 'cartons', 1),
(71, 'show', 'locBatches', 1),
(75, 'type', 'receiving', 1),
(76, 'type', 'processing', 1),
(77, 'type', 'storage', 1),
(78, 'type', 'workorders', 1),
(114, 'show', 'providers', 1),
(115, 'show', 'packages', 1),
(116, 'show', 'services', 1),
(117, 'show', 'billTo', 1),
(118, 'show', 'signatures', 1),
(114, 'editable', 'display', 1),
(115, 'editable', 'display', 1),
(116, 'editable', 'display', 1),
(117, 'editable', 'display', 1),
(118, 'editable', 'display', 1),
(81, 'show', 'info', 1),
(82, 'show', 'access', 1),
(83, 'show', 'clients', 1),
(84, 'show', 'groups', 1),
(85, 'show', 'userGroups', 1),
(86, 'show', 'pages', 1),
(87, 'show', 'pageParams', 1),
(88, 'show', 'groupPages', 1),
(89, 'show', 'subMenus', 1),
(97, 'type', 'receiving', 1),
(98, 'type', 'shipping', 1),
(100, 'display', 'logs', 1),
(101, 'display', 'inventory', 1),
(102, 'show', 'control', 1),
(106, 'type', 'unsplit', 1),
(58, 'firstDropdown', 'vendor', 1),
(59, 'firstDropdown', 'vendorID', 1),
(60, 'firstDropdown', 'vendor', 1),
(61, 'firstDropdown', 'vendor', 1),
(64, 'receiving', 'display',  1),
(68, 'editable', 'display', 1),
(69, 'editable', 'display', 1),
(70, 'editable', 'display', 1),
(71, 'editable', 'display', 1),
(75, 'editable', 'display', 1),
(76, 'editable', 'display', 1),
(77, 'editable', 'display', 1),
(78, 'editable', 'display', 1),
(81, 'editable', 'display', 1),
(82, 'editable', 'display', 1),
(83, 'editable', 'display', 1),
(84, 'editable', 'display', 1),
(85, 'editable', 'display', 1),
(86, 'editable', 'display', 1),
(87, 'editable', 'display', 1),
(88, 'editable', 'display', 1),
(89, 'editable', 'display', 1),
(98, 'firstDropdown', 'vendor', 1),
(58, 'secondDropdown', 'setDate%5Bstarting%5D', 1),
(59, 'secondDropdown', 'shipDate%5Bstarting%5D', 1),
(60, 'secondDropdown', 'setDate%5Bstarting%5D', 1),
(61, 'secondDropdown', 'shipDate%5Bstarting%5D', 1),
(58, 'thirdDropdown', 'setDate%5Bending%5D', 1),
(59, 'thirdDropdown', 'shipDate%5Bending%5D', 1),
(60, 'thirdDropdown', 'setDate%5Bending%5D', 1),
(61, 'thirdDropdown', 'shipDate%5Bending%5D', 1),
(58, 'fourthDropdown', 'statusID', 1),
(59, 'fourthDropdown', 'invoiceStatusID', 1),
(60, 'fourthDropdown', 'statusID', 1),
(61, 'fourthDropdown', 'statusID', 1),
(33, 'reprint', '', 1),
(64, 'reprint', '1',  1);

CREATE TABLE `group_pages` (
    `id` INT(3) NOT NULL AUTO_INCREMENT,
    `groupID` INT(2) NOT NULL,
    `pageID` INT(3) NOT NULL,
    `active` BOOLEAN NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `groupID` (`groupID`),
    KEY `pageID` (`pageID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- CSR - everything untill Picking, Search Inventory and Online Orders
-- Warehouse Staff - everything starting from RC Log, Reprint Labels

INSERT INTO `group_pages` (`groupID`, `pageID`, `active`) VALUES
(2, 1, 1),
(2, 2, 1),
(2, 3, 1),
(2, 4, 1),
(2, 5, 1),
(2, 6, 1),
(2, 7, 1),
(2, 8, 1),
(2, 21, 1),
(2, 22, 1),
(2, 23, 1),
(2, 24, 1),
(2, 25, 1),
(2, 26, 1),
(2, 27, 1),
(2, 28, 1),
(2, 29, 1),
(2, 30, 1),
(2, 31, 1),
(2, 32, 1),
(2, 33, 1),
(2, 34, 1),
(2, 35, 1),
(2, 36, 1),
(2, 37, 1),
(2, 38, 1),
(2, 39, 1),
(2, 40, 1),
(2, 41, 1),
(2, 42, 1),
(2, 43, 1),
(2, 44, 1),
(2, 45, 1),
(2, 46, 1),
(2, 47, 1),
(2, 48, 1),
(2, 97, 1),
(2, 98, 1),
(3, 2, 1),
(3, 3, 1),
(3, 4, 1),
(3, 5, 1),
(3, 6, 1),
(3, 7, 1),
(3, 8, 1),
(3, 9, 1),
(3, 10, 1),
(3, 11, 1),
(3, 12, 1),
(3, 13, 1),
(3, 14, 1),
(3, 15, 1),
(3, 16, 1),
(3, 17, 1),
(3, 18, 1),
(3, 19, 1),
(3, 20, 1),
(3, 33, 1),
(3, 34, 1),
(3, 97, 1),
(3, 98, 1);


-- restoring rackDate field in inventory_cartons table

ALTER TABLE `inventory_cartons` ADD `rackDate` TIMESTAMP NOT NULL DEFAULT '0000-00-00';

-- Phong Tran- 
-- 07/04/2015
ALTER TABLE `invoices_storage`
MODIFY COLUMN `actualCarton`  decimal(8,2) NOT NULL AFTER `endDate`,
MODIFY COLUMN `actualPieces`  decimal(8,2) NOT NULL AFTER `actualCarton`,
MODIFY COLUMN `recCarton`  decimal(8,2) NOT NULL AFTER `actualPieces`,
MODIFY COLUMN `recPieces`  decimal(8,2) NOT NULL AFTER `recCarton`,
MODIFY COLUMN `shipCarton`  decimal(8,2) NOT NULL AFTER `recPieces`,
MODIFY COLUMN `shipPieces`  decimal(8,2) NOT NULL AFTER `shipCarton`,
MODIFY COLUMN `actualVolume`  decimal(8,2) NOT NULL AFTER `shipPieces`,
MODIFY COLUMN `totalPrevious`  decimal(8,2) NOT NULL AFTER `actualVolume`,
MODIFY COLUMN `storCart`  decimal(4,2) NOT NULL AFTER `statusID`,
MODIFY COLUMN `storEach`  decimal(4,2) NOT NULL AFTER `storCart`,
MODIFY COLUMN `storVol`  decimal(4,2) NOT NULL AFTER `storEach`,
MODIFY COLUMN `storPallet`  decimal(4,2) UNSIGNED ZEROFILL NOT NULL AFTER `storVol`,
MODIFY COLUMN `totalCart`  decimal(8,2) NOT NULL AFTER `storPallet`,
MODIFY COLUMN `totalEach`  decimal(8,2) NOT NULL AFTER `totalCart`,
MODIFY COLUMN `totalPallet`  decimal(8,2) NOT NULL AFTER `totalEach`,
MODIFY COLUMN `totalVol`  decimal(8,2) NOT NULL AFTER `totalPallet`,
MODIFY COLUMN `total`  decimal(8,2) NOT NULL AFTER `totalVol`;

-- Vadzim
-- 7/06/2015

-- create fields to track orders with sent BOL emails

ALTER TABLE `neworder` ADD `processedBolEmailed` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `neworder` ADD `shippedBolEmailed` BOOLEAN NOT NULL DEFAULT FALSE;

-- create a table with client emails

CREATE TABLE `client_emails` (
    `id` INT(10) NOT NULL AUTO_INCREMENT,
    `vendorID` INT(10) NOT NULL,
    `receivingConfirmation` BOOLEAN NOT NULL DEFAULT FALSE,
    `bolConfirmation` BOOLEAN NOT NULL DEFAULT FALSE,
    `email` VARCHAR(50) NULL DEFAULT NULL,
    `bolEmail` BOOLEAN NOT NULL DEFAULT FALSE,
    `active` BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`id`),
    KEY `vendorID` (`vendorID`),
    UNIQUE vendorEmail (vendorID, email)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- add a menu item

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`) 
SELECT    sm.id AS subMenuID,
   	      "Edit Client Emails" AS displayName,
          maxOrder + 1 AS displayOrder,
          "clientEmails" AS hiddenName,
          "clientEmails" AS `class`,
          "search" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
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

-- add page parameters

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
		  "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "clientEmails";

-- create a table for mail reports

CREATE TABLE IF NOT EXISTS `report_bols` (
    `id` INT(8) NOT NULL AUTO_INCREMENT,
    `reportID` INT(7) NOT NULL,
    `orderID` INT(10) NOT NULL,
    `isProcessed` BOOLEAN NOT NULL DEFAULT FALSE,
    `isShipped` BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (`id`),
    KEY `reportID` (`reportID`),
    KEY `orderID` (`orderID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- reports and report_containers were missing id field AUTO_INCREMENT

ALTER TABLE `reports` CHANGE `id` `id` INT(7) NOT NULL AUTO_INCREMENT;
ALTER TABLE `report_containers` CHANGE `id` `id` INT(8) NOT NULL AUTO_INCREMENT;

-- Vadzim
-- 7/08/2015

-- make "Edit Orders", "Edit Online Orders", "Edit Work Orders", "Edit Clients" pages editable

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
		  "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "editOrders";

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
		  "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "editOnlineOrders";

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
		  "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "editWorkOrders";

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
		  "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "editClients";

-- Vadzim
-- 7/08/2015

-- create a fields to track orders with discrepancies in Pick Tickets

ALTER TABLE `neworder` ADD `reprintPickTicket` BOOLEAN NOT NULL DEFAULT FALSE;

-- create a fields to track email notifications about discrepancies in Pick Tickets

CREATE TABLE IF NOT EXISTS `report_orders` (
    `id` INT(8) NOT NULL AUTO_INCREMENT,
    `reportID` INT(7) NOT NULL,
    `orderID` INT(10) NOT NULL,
    `invalidPickTicket` BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (`id`),
    KEY `reportID` (`reportID`),
    KEY `orderID` (`orderID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


-- Phong Tran
-- 07/08/2015
ALTER TABLE `invoices_storage` ADD `recVolume` DECIMAL(8,2) NULL AFTER `recPieces`;
ALTER TABLE `invoices_storage` ADD `shipVolume` DECIMAL(8,2) NULL AFTER `shipPieces`;
ALTER TABLE `invoices_storage` DROP `totalPrevious`;
ALTER TABLE `invoices_storage` 
ADD `preCarton` DECIMAL(8,2) NOT NULL AFTER `shipVolume`, 
ADD `prePieces` DECIMAL(8,2) NOT NULL AFTER `preCarton`, 
ADD `preVolume` DECIMAL(8,2) NOT NULL AFTER `prePieces`;

-- Phong Tran
-- 07/10/2015
ALTER TABLE `invoices_storage` 
CHANGE `actualCarton` `actualCarton` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `actualPieces` `actualPieces` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `actualVolume` `actualVolume` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `recCarton` `recCarton` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `recPieces` `recPieces` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `recVolume` `recVolume` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `shipPieces` `shipPieces` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `shipVolume` `shipVolume` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `preCarton` `preCarton` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `prePieces` `prePieces` DECIMAL(8,2) NULL DEFAULT '0.0',
CHANGE `preVolume` `preVolume` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `storCart` `storCart` DECIMAL(4,2) NULL DEFAULT '0.0', 
CHANGE `storEach` `storEach` DECIMAL(4,2) NULL DEFAULT '0.0', 
CHANGE `storVol` `storVol` DECIMAL(4,2) NULL DEFAULT '0.0', 
CHANGE `storPallet` `storPallet` DECIMAL(4,2) UNSIGNED ZEROFILL NULL DEFAULT '0.0', 
CHANGE `totalCart` `totalCart` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `totalEach` `totalEach` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `totalPallet` `totalPallet` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `totalVol` `totalVol` DECIMAL(8,2) NULL DEFAULT '0.0', 
CHANGE `total` `total` DECIMAL(8,2) NULL DEFAULT '0.0';

-- Vadzim
-- 7/09/2015

-- adding Tracking field to online_orders_exports and online_orders_fails_update tables

ALTER TABLE `online_orders_exports` ADD `tracking` VARCHAR(20) NULL AFTER `packageReference`;
ALTER TABLE `online_orders_fails_update` ADD `tracking` VARCHAR(20) NULL AFTER `order_id`;

-- Vadzim
-- 7/10/2015

-- making a separate controller for availableInventory page

UPDATE `pages`
SET    `method` = 'availabe'
WHERE  `hiddenName` = 'searchAvailableInventory';

UPDATE    `page_params` pp
JOIN      `pages` p ON p.id = pp.pageID
SET       pp.active = 0
WHERE     `hiddenName` = 'searchAvailableInventory';

-- Vadzim
-- 7/09/2015

-- change method name with a mistake

UPDATE `pages`
SET    `method` = 'available'
WHERE  `hiddenName` = 'searchAvailableInventory';


-- Make a page to zero out inventory

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`) 
SELECT    sm.id AS subMenuID,
   	      "Zero Out Inventory" AS displayName,
          maxOrder + 1 AS displayOrder,
          "zeroOutInventory" AS hiddenName,
          "scanners" AS `class`,
          "zeroOutInventory" AS `method`,
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

-- Vadzim
-- 15/09/2015

-- add a column to track cartons that were included into a Pict Ticket regardless 
-- whether they were processed for the order they were reserved for or for any
-- other order

-- make default value FALSE to initially populate the field with zeros

ALTER TABLE `pick_cartons` ADD `isOriginalPickTicket` BOOLEAN NOT NULL DEFAULT TRUE;

-- set initial isOriginalPickTicket values equal to active

UPDATE `pick_cartons`
SET    `isOriginalPickTicket` = `active`;


-- Trang Le
-- 07/16/2015
-- Adding fields: eachHeight, eachWidth, eachLength, eachWeight in inventory_batches
ALTER TABLE `inventory_batches`
ADD COLUMN `eachHeight` decimal(5,1) NOT NULL AFTER `weight`,
ADD COLUMN `eachWidth` decimal(5,1) NOT NULL AFTER `eachHeight`,
ADD COLUMN `eachLength` decimal(5,1) NOT NULL AFTER `eachWidth`,
ADD COLUMN `eachWeight` decimal(5,1) NOT NULL AFTER `eachLength`;

-- Vadzim
-- 7/20/2015

-- create a fields to track several online order rows related to one exports order row

CREATE TABLE IF NOT EXISTS `online_orders_exports_orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `exportOrderID` INT(11) NOT NULL,
    `onlineOrderID` INT(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `exportOrderID` (`exportOrderID`),
    KEY `onlineOrderID` (`onlineOrderID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO online_orders_exports_orders (exportOrderID, onlineOrderID)
SELECT    id,
          orderID
FROM      online_orders_exports;

ALTER TABLE online_orders_exports DROP INDEX orderBatch;
ALTER TABLE online_orders_exports DROP orderBatch;

ALTER TABLE online_orders_exports DROP INDEX orderID;
ALTER TABLE online_orders_exports DROP orderID;

ALTER TABLE online_orders_exports DROP packageReference;

-- Vuong
-- 14/7/2015
-- Add dedault search param for Edit Location Patch
INSERT INTO page_params (`pageID`, `name`, `value`, `active`)
	SELECT p.id,'firstDropdown','vendor',1 
	FROM pages p 
	WHERE p.hiddenName = 'editLocationBatches';
ALTER TABLE `invoices_receiving`
ADD COLUMN `totalVol` DECIMAL(8,2) ZEROFILL NOT NULL AFTER `recSpec`;

-- Phong Tran
-- 07/22/2015
-- Adding fields: totalPieces, totalCarton in invoices_receiving
ALTER TABLE `invoices_receiving` 
ADD `totalPieces` DECIMAL(8,2) NOT NULL AFTER `inventoryBatch`, 
ADD `totalCarton` DECIMAL(8,2) NOT NULL AFTER `totalPieces`;
ALTER TABLE `invoices_receiving` CHANGE `totalVol` `totalVol` DECIMAL(8,2) NOT NULL AFTER `totalCarton`;

-- Vadzim
-- 7/22/2015

-- add missing UPS service

INSERT INTO `online_orders_exports_services` (
    `providerID`, 
    `shortName`, 
    `displayName`, 
    `active`) 
SELECT    id AS `providerID`,
		  "03" AS `shortName`,
          "UPS Ground" AS `displayName`,
          1 AS `active`
FROM      online_orders_exports_providers
WHERE     displayName = "UPS";

-- add country, phone and email fields to company_address table

ALTER TABLE `company_address` 
ADD `country` VARCHAR(2) NOT NULL AFTER `companyName`,
ADD `email` VARCHAR(50) NULL DEFAULT NULL AFTER `zip`,
ADD `phone` VARCHAR(15) NULL DEFAULT NULL AFTER `zip`;

-- change company_address table values

UPDATE `company_address`
SET    `companyName` = 'SELDAT NJ Docks Corner',
       `country` = 'US',
       `state` = 'NJ',
       `phone` = '732-438-0000'
WHERE  `companyName` = 'NJ Docks Corner Warehouse';

UPDATE `company_address`
SET    `companyName` = 'SELDAT NJ Thatcher',
       `country` = 'US',
       `state` = 'NJ',
       `phone` = '732-438-0000'
WHERE  `companyName` = 'NJ Thatcher Warehouse';

UPDATE `company_address`
SET    `companyName` = 'SELDAT California',
       `country` = 'US',
       `state` = 'CA',
       `phone` = '732-438-0000'
WHERE  `companyName` = 'Los Angeles Warehouse';

UPDATE `company_address`
SET    `companyName` = 'SELDAT Toronto',
       `country` = 'CA',
       `state` = 'ON',
       `phone` = '732-438-0000'
WHERE  `companyName` = 'Toronto Warehouse';

-- Vadzim
-- 7/27/2015

ALTER TABLE `neworder` ADD INDEX (statusID);

-- Jon
-- Removing incorrect upcs unique index

ALTER TABLE upcs DROP INDEX upc_2;
ALTER TABLE `upcs` ADD UNIQUE (`sku`, `size`, `color`);

-- Vadzim
-- 8/3/2015

-- refactoring report tables

CREATE TABLE IF NOT EXISTS `reports_data` (
    `id` int(2) NOT NULL AUTO_INCREMENT,
    `reportID` INT(7) NOT NULL,
    `primeKey` INT(11) NOT NULL,
    `statusID` INT(3) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `reportID` (`reportID`),
    KEY `primeKey` (`primeKey`),
    KEY `statusID` (`statusID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

DROP TABLE report_bols;
DROP TABLE report_containers;
DROP TABLE report_orders;

-- adding report categories

INSERT INTO `statuses`(`category`, `displayName`, `shortName`) VALUES 
('reports', 'Received Container', 'RCNT'),
('reports', 'Invalid Pick Ticket', 'INPT'),
('reports', 'Processed Lading', 'PBOL'),
('reports', 'Shipped Lading', 'SBOL');

-- removing unnecessary fields (this data will be stored in reports_data table)

ALTER TABLE `neworder` DROP `processedBolEmailed`;
ALTER TABLE `neworder` DROP `shippedBolEmailed`;

-- Vadzim
-- 8/5/2015

-- dropping "tracking" field from online_orders_exports table
-- "shipment_tracking_id" field from online_orders table will be used instead

ALTER TABLE `online_orders_exports` DROP `tracking`;

-- add empty signaturte (required for correct export to corporatefreightsavers.com)

INSERT INTO `online_orders_exports_signatures` (`displayName`,`active`) VALUES
('', 1);

-- Duy
-- 08/06/2015
-- Alter the types and name of fields relating to quantity from Decimal to Int 
-- in tables of Invoices Section

-- invoice receiving table

ALTER TABLE `invoices_receiving` 
CHANGE `totalPieces` `totalPieces` INT(5) NOT NULL,
CHANGE `totalCarton` `totalCartons` INT(5) NOT NULL;

-- invoices_storage table

ALTER TABLE `invoices_storage` 
CHANGE `actualCarton` `actualCartons` INT(5) NULL DEFAULT '0',
CHANGE `actualPieces` `actualPieces` INT(5) NULL DEFAULT '0',
CHANGE `recCarton` `recCartons` INT(5) NULL DEFAULT '0',
CHANGE `recPieces` `recPieces` INT(5) NULL DEFAULT '0',
CHANGE `shipCarton` `shipCartons` INT(5) NOT NULL DEFAULT '0',
CHANGE `shipPieces` `shipPieces` INT(5) NULL DEFAULT '0',
CHANGE `preCarton` `preCartons` INT(5) NULL DEFAULT '0',
CHANGE `prePieces` `prePieces` INT(5) NULL DEFAULT '0';

-- Adding Fontana Warehouse
INSERT INTO `company_address` (`companyName`, `state`, `city`, `address`, `zip`) 
VALUES ('Fontana Warehouse', 'California', 'Fontana', '3325 C St', '91752');

INSERT INTO warehouses (displayName, shortName, locationID) 
VALUES ('Fontana', 'LA', (
    SELECT id 
    FROM   company_address
    WHERE  companyName = "Fontana Warehouse"
));

-- Vadzim
-- 8/5/2015

-- creating crons database

CREATE DATABASE crons 
CHARSET=utf8 COLLATE=utf8_general_ci;

-- grant privileges to the DB
-- GRANT ALL ON crons.* TO 'seldatWMS'@'localhost';

-- do not use "USE `crons`;" command since there is no way to explicitly close 
-- a database. Use a database prefix instead as I assume that 'crons' DB will
-- have its present name in all projects. Otherwise remove DB name prefix.

CREATE TABLE IF NOT EXISTS `crons`.`tasks` (
    `id` int(2) NOT NULL AUTO_INCREMENT,
    `displayName` VARCHAR(50) NOT NULL,
    `server` VARCHAR(50) NOT NULL,
    `site` VARCHAR(50) NOT NULL,
    `app` VARCHAR(50) NOT NULL,
    `class` VARCHAR(50) NOT NULL,
    `method` VARCHAR(50) NOT NULL,
    `duration` INT(7) NOT NULL,
    `active` BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;

-- need to replace 'localhost' after INSERT query execution

INSERT INTO `crons`.`tasks` (
    `displayName`,
    `server`,
    `site`,
    `app`,
    `class`,
    `method`,
    `duration`) VALUES
('Receiving Containers notice', 'localhost', 'mvc', 'wms', 'appCrons', 'containerReports', 5),
('Rush Orders notice', 'localhost', 'mvc', 'wms', 'appCrons', 'rushOrdersNotices', 5),
('Reprint invalid Pick Tickets notice', 'localhost', 'mvc', 'wms', 'appCrons', 'emailInvalidPickTicket', 5),
('Processed Bill of Ladings notice', 'localhost', 'mvc', 'wms', 'appCrons', 'emailProcessedLading', 5),
('Shipped Bill of Ladings notice', 'localhost', 'mvc', 'wms', 'appCrons', 'emailShippedLading', 5);

-- crons is supposed to run each minute

-- Vadzim
-- 8/10/2015

-- creating crons menu item

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`) 
SELECT    sm.id AS subMenuID,
   	      "Edit Crons Tasks" AS displayName,
          maxOrder + 1 AS displayOrder,
          "cronsTasks" AS hiddenName,
          "cronsTasks" AS `class`,
          "search" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
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

-- add page parameters

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
		  "show" AS `name`,
          "tasks" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "cronsTasks";

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
		  "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "cronsTasks";

-- Unique index for company addresses to prevent duplicates
ALTER TABLE company_address ADD UNIQUE (companyName);

-- Phong Tran
-- 08/11/2015

-- create upcs_categories table

CREATE TABLE `upcs_categories` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;

ALTER TABLE `upcs` ADD `catID` INT(10) NULL DEFAULT NULL AFTER `upc`;

-- Vadzim
-- 8/11/2015

-- Add Splitting Users to Split Table

ALTER TABLE `inventory_splits` ADD `userID` INT(4) NULL DEFAULT NULL AFTER `childID`;

-- Phong Tran
-- 08/12/2015

-- creating upcsCategories menu item 

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`) 
SELECT    sm.id AS subMenuID,
   	      "Edit UPC Categories" AS displayName,
          maxOrder + 1 AS displayOrder,
          "editupcscategory" AS hiddenName,
          "seldatContainers" AS `class`,
          "search" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
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

-- add page parameters

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
		  "show" AS `name`,
          "upcscategories" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "editupcscategory";

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
		  "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "editupcscategory";

ALTER TABLE `upcs_categories` ADD `active` TINYINT(1) DEFAULT '1' AFTER `name`; 
ALTER TABLE upcs_categories ADD UNIQUE KEY name (name);

-- Duy Nguyen
-- 08/13/2015
-- Remove dedault search param for Edit Location Patch

UPDATE `page_params`
SET active = 0
WHERE pageID IN (
	SELECT p.id
	FROM pages p 
	WHERE p.hiddenName = 'editLocationBatches'
) AND NAME = 'firstDropdown';

-- Duy Nguyen
-- 08/17/2015
-- Deactive Print Pallet Tally Sheets page

UPDATE `pages` SET active = 0 WHERE hiddenName = 'printPalletTallySheets';

-- Phong Tran
-- 08/19/2015

-- Update class and method for  Edit UPC Categories submenu
UPDATE `pages`
SET 
	class = 'upcs',
	method = 'editCategories'
WHERE hiddenName = 'editupcscategory';


-- add active fields in upcs table

ALTER TABLE `upcs` ADD `active` TINYINT(1) NOT NULL DEFAULT '1' AFTER `color`;  

-- creating Edit UPCs submenu item in Administration menu
INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`) 
SELECT    sm.id AS subMenuID,
   	      "Edit UPCs" AS displayName,
          maxOrder + 1 AS displayOrder,
          "editupcs" AS hiddenName,
          "upcs" AS `class`,
          "edit" AS `method`,
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

-- add page parameters

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
		  "show" AS `name`,
          "upcs" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "editupcs";

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
		  "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "editupcs";

-- -- creating Search UPCs submenu item in Search Data menu
INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`) 
SELECT    sm.id AS subMenuID,
   	      "Search UPCs" AS displayName,
          maxOrder + 1 AS displayOrder,
          "searchupcs" AS hiddenName,
          "upcs" AS `class`,
          "search" AS `method`,
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

-- add page parameters

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT  id AS `pageID`,
		"show" AS `name`,
        "upcs" AS `value`,
        1 AS active
FROM    pages
WHERE   hiddenName = "searchupcs";

-- Phong Tran
-- 08/21/2015

-- Create Inventory History Report submenu in Administration menu
INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`) 
SELECT    sm.id AS subMenuID,
   	      "Inventory History Report" AS displayName,
          maxOrder + 1 AS displayOrder,
          "inventoryHistoryReport" AS hiddenName,
          "inventory" AS `class`,
          "historyReport" AS `method`,
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

-- Phong Tran
-- 08/22/2015

-- Move Inventory History Report submenus from Administration menu to Search Data menu

UPDATE `pages` 
JOIN (
	SELECT    s.id AS subMenu
    FROM      submenus s    
		WHERE     s.displayName = "Search Data"
	AND       s.active
) su
JOIN (
	SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.displayName = "Search Data"
	AND       sm.active
) m
SET 
	`subMenuID`= subMenu, 
	`displayOrder`=maxOrder + 1, 
	`class`='inventory', 
	`method`='components'
	
WHERE hiddenName = "inventoryHistoryReport";

-- add page parameters

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT  id AS `pageID`,
		"show" AS `name`,
		"history" AS `value`,
        1 AS active
FROM    pages
WHERE   hiddenName = "inventoryHistoryReport";

-- Duy Nguyen
-- 08/25/2015

-- Change value for secondDropdown 
-- from setDate%5Bstarting%5D to date%5Bstarting%5D
UPDATE `page_params`
SET VALUE = 'date%5Bstarting%5D'
WHERE pageID IN (
	SELECT p.id
	FROM pages p 
	WHERE p.hiddenName = 'storageInvoices'
) AND NAME = 'secondDropdown';

-- Change value for thirdDropdown from setDate%5Bending%5D to date%5Bending%5D

UPDATE `page_params`
SET VALUE = 'date%5Bending%5D'
WHERE pageID IN (
	SELECT p.id
	FROM pages p 
	WHERE p.hiddenName = 'storageInvoices'
) AND NAME = 'thirdDropdown';

-- Order Processing Invoices

-- Change value for secondDropdown 
-- from shipDate%5Bstarting%5D to startshipdate%5Bstarting%5D

UPDATE `page_params`
SET VALUE = 'startshipdate%5Bstarting%5D'
WHERE pageID IN (
	SELECT p.id
	FROM pages p 
	WHERE p.hiddenName = 'orderProcessingInvoices'
) AND NAME = 'secondDropdown';

-- Change value for thirdDropdown 
-- from shipDate%5Bending%5D to startshipdate%5Bending%5D

UPDATE `page_params`
SET VALUE = 'startshipdate%5Bending%5D'
WHERE pageID IN (
	SELECT p.id
	FROM pages p 
	WHERE p.hiddenName = 'orderProcessingInvoices'
) AND NAME = 'thirdDropdown';

-- Vadzim
-- 08/25/2015
-- Remove date fields from inventory_cartons table. These fields are going to
-- be replaces by data from logs_cartons table

ALTER TABLE `inventory_cartons` DROP `recDate`;
ALTER TABLE `inventory_cartons` DROP `shipDate`;
ALTER TABLE `inventory_cartons` DROP `invoiceDate`;

-- replace url encoded values with corresponding characters

UPDATE page_params 
SET `value` = REPLACE(`value`, '%5B', '['),
    `value` = REPLACE(`value`, '%5D', ']');

-- making a field name more informative

ALTER TABLE `crons`.`tasks` CHANGE `duration` `frequency` INT(7) NOT NULL;



-- Phong Tran
-- 08/26/2015

-- creating Location Info submenu item in Inventory COntrol menu

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`) 
SELECT    sm.id AS subMenuID,
   	      "Location Info" AS displayName,
          maxOrder + 1 AS displayOrder,
          "locationinfo" AS hiddenName,
          "locations" AS `class`,
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

-- add page parameters

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
		  "display" AS `name`,
          "locationinfo" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "locationinfo";

INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
		  "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "locationinfo";


-- Vadzim
-- 08/27/2015

-- creating location_info table

CREATE TABLE IF NOT EXISTS `locations_info` ( 
    `id` int(7) NOT NULL AUTO_INCREMENT,
    `locID` INT(7) NOT NULL, 
    `upcID` INT(10) NOT NULL, 
    `minCount` INT(8) NOT NULL DEFAULT 0, 
    `maxCount` INT(8) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8; 


-- Insert data to the table location_info 

INSERT INTO locations_info (locID, upcID) 
    SELECT  l.id AS locID, 
            u.id AS upcID 
    FROM    locations l 
    JOIN    inventory_cartons ca ON ca.locID = l.id 
    JOIN    inventory_batches b ON b.id = ca.batchID 
    JOIN    upcs u ON u.id = b.upcID 
    WHERE   isMezzanine 
    AND     NOT EXISTS ( 
                SELECT  li.locID, 
                        li.upcID 
                FROM    locations_info li
                WHERE   li.locID = ca.locID 
                AND     li.upcID = b.upcID 
    ) 
    GROUP BY l.id, 
             u.id;

ALTER TABLE `locations_info` 
    ADD KEY `locID` (`locID`), 
    ADD KEY `upcID` (`upcID`),
    ADD UNIQUE `upcLocID` (`upcID`, `locID`);

-- Vadzim
-- 08/31/2015

-- add unique keys to directory tables related to online_orders_exports table

ALTER TABLE `online_orders_exports_bill_to` 
    ADD UNIQUE `uniqueDisplayName` (`displayName`);

ALTER TABLE `online_orders_exports_packages` 
    ADD UNIQUE `uniqueProviderPackage` (`providerID`, `shortName`);

ALTER TABLE `online_orders_exports_providers` 
    ADD UNIQUE `uniqueDisplayName` (`displayName`);

ALTER TABLE `online_orders_exports_signatures` 
    ADD UNIQUE `uniqueDisplayName` (`displayName`);

-- change company_address table value for Fontana Warehouse to suit requirements 
-- for online orders exports

UPDATE `company_address`
SET    `companyName` = 'SELDAT Fontana',
       `country` = 'US',
       `state` = 'CA',
       `phone` = '732-438-0000'
WHERE  `companyName` = 'Fontana Warehouse';

-- Vadzim
-- 9/01/2015

-- making inactive invalid entry

UPDATE online_orders_exports_packages pk
JOIN online_orders_exports_providers pr ON pr.id = pk.providerID
SET   pk.shortName = '25 - Error',
      pk.active = 0 
WHERE pk.displayName = 'UPS 10KG Box'
AND   shortName = 25
AND   pr.displayName = 'FedEx';

-- removing duplicate UNIQUE index

ALTER TABLE online_orders_exports_packages DROP INDEX uniquePackage;

-- correcting duplicate values

UPDATE online_orders_exports_services
SET shortName = '11 - Error',
    active = 0 
WHERE displayName = 'UPS Standard to Mexico';

UPDATE online_orders_exports_services
SET displayName = 'UPS Standard'
WHERE displayName = 'UPS Standard to Canada';

-- add a unique key

ALTER TABLE `online_orders_exports_services` 
    ADD UNIQUE `uniqueProviderService` (`providerID`, `shortName`);

-- making inactive invalid entry (previously ship.cfswebship.com did not treat
-- "None Required" as a valid value for Signatures property)

UPDATE online_orders_exports_signatures
SET active = 0 
WHERE displayName = '';

-- add Genius Pack to deal sites list

INSERT INTO `deal_sites` (`displayName`, `imageName`) VALUES 
('geniuspack.com', 'GeniusPack');

-- add "active" column to deal_sites table

ALTER TABLE deal_sites ADD COLUMN active TINYINT(1) DEFAULT 1 AFTER imageName;

ALTER TABLE `deal_sites` 
    ADD UNIQUE `uniqueDealSite` (`displayName`);

-- Change position of storage invoice filter params
UPDATE    `page_params` pp
JOIN      `pages` p ON p.id = pp.pageID
SET       pp.`value` = 'statusID'
WHERE     `hiddenName` = 'storageInvoices'
AND  pp.`name` = 'secondDropdown';

UPDATE    `page_params` pp
JOIN      `pages` p ON p.id = pp.pageID
SET       pp.`value` = 'date[starting]'
WHERE     `hiddenName` = 'storageInvoices'
AND  pp.`name` = 'thirdDropdown';

UPDATE    `page_params` pp
JOIN      `pages` p ON p.id = pp.pageID
SET       pp.`value` = 'date[ending]'
WHERE     `hiddenName` = 'storageInvoices'
AND  pp.`name` = 'fourthDropdown';

-- uniform case for deal site display names
UPDATE deal_sites SET displayName = 'GeniusPack.com' 
WHERE displayName = 'geniuspack.com';

-- Custom invoice storage filter params
UPDATE    `page_params` pp
JOIN      `pages` p ON p.id = pp.pageID
SET       pp.`value` = 'date[starting]'
WHERE     `hiddenName` = 'storageInvoices'
AND  pp.`name` = 'firstDropdown';

UPDATE    `page_params` pp
JOIN      `pages` p ON p.id = pp.pageID
SET       pp.`value` = 'date[ending]'
WHERE     `hiddenName` = 'storageInvoices'
AND  pp.`name` = 'secondDropdown';

UPDATE    `page_params` pp
JOIN      `pages` p ON p.id = pp.pageID
SET       pp.`active` = 0
WHERE     `hiddenName` = 'storageInvoices'
AND  pp.`name` = 'thirdDropdown';

UPDATE    `page_params` pp
JOIN      `pages` p ON p.id = pp.pageID
SET       pp.`active` = 0
WHERE     `hiddenName` = 'storageInvoices'
AND  pp.`name` = 'fourthDropdown';

-- Vadzim
-- 9/11/2015

-- adding Shipment columns to Online Orders Import Template

INSERT INTO `order_description`(`fieldName`, `description`, `mandatory`) VALUES 
('shipment_tracking_id', 'Shipment Tracking ID', 0),
('shipment_sent_on', 'Shipment Cost', 0),
('shipment_cost', 'Shipment Sent On', 0);

-- make order_description table fieldName field data title case

UPDATE    `order_description` 
SET       `fieldName` = `description`
WHERE     `description` = 'Shipment Tracking ID'
OR        `description` = 'Shipment Cost'
OR        `description` = 'Shipment Sent On';

-- make order_description table description field blank if it repeats fieldName field

UPDATE    `order_description` 
SET       `description` = NULL
WHERE     `fieldName` = `description`;

-- Phong Tran
-- 9/14/2015

-- Create new Auto Crons for Update MinMax Mezzanine Location Info and Transfer Mezzanine for Online Order

-- Database: crons
INSERT INTO `crons`.`tasks` (`displayName`, `server`, `site`, `app`, `class`, `method`, `frequency`, `active`) 
VALUES ('Update MinMax Mezzanine Location Info', 'localhost', 'mvc', 'wms', 'appCrons', 'emailMinMaxMezzanineInfo', '5', '1');
INSERT INTO `crons`.`tasks` (`displayName`, `server`, `site`, `app`, `class`, `method`, `frequency`, `active`) 
VALUES ('Transfer Mezzanine for Online Order', 'localhost', 'mvc', 'wms', 'appCrons', 'transferMezzanineOnlineOrder', '5', '1');


ALTER TABLE `reports` CHANGE `id` `id` INT(7) NOT NULL AUTO_INCREMENT;

ALTER TABLE `reports_data` ADD `category` VARCHAR(15) NOT NULL AFTER `statusID`;

-- Add new status for transfer Mezzanine
INSERT INTO `statuses` (`category`, `displayName`, `shortName`) VALUES ('reports', 'Transfer Mezzanine', 'TROM');

-- Vuong Nguyen
-- 9/15/2015

-- Add new status for Invoice Send Mail
INSERT INTO `statuses` (`category`, `displayName`, `shortName`) VALUES ('reports', 'Invoice Receiving', 'IR');
INSERT INTO `statuses` (`category`, `displayName`, `shortName`) VALUES ('reports', 'Invoice Storage', 'IS');

-- change category to isSent
ALTER TABLE `reports_data` CHANGE `category` `isSent` TINYINT(1) NOT NULL;

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

-- Duy Nguyen
-- 9/17/2015
INSERT INTO `crons`.`tasks` (`displayName`, `server`, `site`, `app`, `class`, `method`, `frequency`, `active`) 
VALUES ('Email Invoice Notifications', 'localhost', 'mvc', 'wms', 'appCrons', 'emailInvoiceNotifications', '60', '1');

-- Phong Tran
-- 9/18/2015

-- All RecNum Fields In the Database should be Length Eight
ALTER TABLE `inventory_batches` CHANGE `recNum` `recNum` INT(8) NOT NULL;
ALTER TABLE `inventory_containers` CHANGE `recNum` `recNum` INT(8) NOT NULL AUTO_INCREMENT;
ALTER TABLE `invoices_receiving` CHANGE `recNum` `recNum` INT(8) UNSIGNED ZEROFILL NULL DEFAULT NULL;
ALTER TABLE `invoices_storage` CHANGE `recNum` `recNum` INT(8) UNSIGNED ZEROFILL NULL DEFAULT NULL;

-- I had to re-add these invoices_storage field updates with commands to turn 
-- null values to 0 because otherwise they do not work on a populated table

UPDATE invoices_storage SET actualCarton = 0 WHERE actualCarton IS NULL;
UPDATE invoices_storage SET actualPieces = 0 WHERE actualPieces IS NULL;
UPDATE invoices_storage SET recCarton = 0 WHERE recCarton IS NULL;
UPDATE invoices_storage SET recPieces = 0 WHERE recPieces IS NULL;
UPDATE invoices_storage SET shipCarton = 0 WHERE shipCarton IS NULL;
UPDATE invoices_storage SET shipPieces = 0 WHERE shipPieces IS NULL;
UPDATE invoices_storage SET actualVolume = 0 WHERE actualVolume IS NULL;
UPDATE invoices_storage SET totalPrevious = 0 WHERE totalPrevious IS NULL;
UPDATE invoices_storage SET storCart = 0 WHERE storCart IS NULL;
UPDATE invoices_storage SET storEach = 0 WHERE storEach IS NULL;
UPDATE invoices_storage SET storVol = 0 WHERE storVol IS NULL;
UPDATE invoices_storage SET storPallet = 0 WHERE storPallet IS NULL;
UPDATE invoices_storage SET totalCart = 0 WHERE totalCart IS NULL;
UPDATE invoices_storage SET totalEach = 0 WHERE totalEach IS NULL;
UPDATE invoices_storage SET totalPallet = 0 WHERE totalPallet IS NULL;
UPDATE invoices_storage SET totalVol = 0 WHERE totalVol IS NULL;
UPDATE invoices_storage SET total = 0 WHERE total IS NULL;

ALTER TABLE `invoices_storage`
MODIFY COLUMN `actualCarton`  decimal(8,2) NOT NULL AFTER `endDate`,
MODIFY COLUMN `actualPieces`  decimal(8,2) NOT NULL AFTER `actualCarton`,
MODIFY COLUMN `recCarton`  decimal(8,2) NOT NULL AFTER `actualPieces`,
MODIFY COLUMN `recPieces`  decimal(8,2) NOT NULL AFTER `recCarton`,
MODIFY COLUMN `shipCarton`  decimal(8,2) NOT NULL AFTER `recPieces`,
MODIFY COLUMN `shipPieces`  decimal(8,2) NOT NULL AFTER `shipCarton`,
MODIFY COLUMN `actualVolume`  decimal(8,2) NOT NULL AFTER `shipPieces`,
MODIFY COLUMN `totalPrevious`  decimal(8,2) NOT NULL AFTER `actualVolume`,
MODIFY COLUMN `storCart`  decimal(4,2) NOT NULL AFTER `statusID`,
MODIFY COLUMN `storEach`  decimal(4,2) NOT NULL AFTER `storCart`,
MODIFY COLUMN `storVol`  decimal(4,2) NOT NULL AFTER `storEach`,
MODIFY COLUMN `storPallet`  decimal(4,2) UNSIGNED ZEROFILL NOT NULL AFTER `storVol`,
MODIFY COLUMN `totalCart`  decimal(8,2) NOT NULL AFTER `storPallet`,
MODIFY COLUMN `totalEach`  decimal(8,2) NOT NULL AFTER `totalCart`,
MODIFY COLUMN `totalPallet`  decimal(8,2) NOT NULL AFTER `totalEach`,
MODIFY COLUMN `totalVol`  decimal(8,2) NOT NULL AFTER `totalPallet`,
MODIFY COLUMN `total`  decimal(8,2) NOT NULL AFTER `totalVol`;

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

-- Make a page for Mezzainine transfer confirmation scanner after "Mezzanine Inventory Transfer" page

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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `release_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `versionName` varchar(50) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `version_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `versionID` int(11) NOT NULL,
  `isShow` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `versionID` (`userID`,`versionID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- Phong Tran
-- 9/28/2015

-- Drop active feild in upcs_assigned table
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

-- Add versionID index in awms_new_features table
ALTER TABLE awms_new_features ADD INDEX `versionID` (`versionID`);
-- Drop versionID index and  in version_info table
ALTER TABLE version_info DROP INDEX `versionID`, ADD UNIQUE `userID` (`userID`);

-- Add active field after date field
ALTER TABLE `awms_new_features` ADD `active` TINYINT(1) NOT NULL DEFAULT '1' AFTER `date`;

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

