USE test;
USE wms_empty_dbs;
USE wms_first_test;
USE wms_live;
USE edi;
USE test;


ALTER TABLE `upcs` ADD `sku` VARCHAR(25) NOT NULL , ADD `size` VARCHAR(25) NOT NULL , ADD `color` VARCHAR(25) NOT NULL ;

ALTER TABLE online_orders ADD INDEX (order_id);

DROP TABLE `backtostock_temp`, `licenseplatelabel_temp`, `licenseplate_temp`, `location_temp`, `neworderlabel_temp`, `nsilabel_temp`, `nsi_temp`, `opcheckout_temp`, `optable_temp`, `orderprocessing_temp`, `pc_temp`, `picking_temp`, `pickticket_temp`, `products_temp`, `routed_temp`, `shipped_temp`, `shippingcheckout_temp`, `shippinglabel_temp`, `shipping_temp`, `temp_neworder`, `temp_receivingdashboard`, `temp_shippingdashboard`, `wms_temp`, `workorderlabel_temp`, `workorder_temp`;

ALTER TABLE `upcs` CHANGE `sku` `sku` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `size` `size` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `color` `color` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `tallies` ADD `rcLabelPrinted` BOOLEAN NOT NULL DEFAULT FALSE , ADD `rcLogPrinted` BOOLEAN NOT NULL DEFAULT FALSE, ADD `locked` BOOLEAN NOT NULL DEFAULT FALSE; 

ALTER TABLE inventory_cartons ADD INDEX (plate);
ALTER TABLE inventory_cartons ADD INDEX (locID);
ALTER TABLE inventory_cartons ADD INDEX (batchID);

ALTER TABLE `inventory_batches` CHANGE `weight` `weight` DECIMAL(5,1) NULL DEFAULT NULL;

-- New NSI tables

CREATE TABLE IF NOT EXISTS `nsi_receiving` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `po` int(6) unsigned zerofill NOT NULL,
  `ra` int(8) unsigned zerofill NOT NULL,
  `palletNumber` int(8) NOT NULL,
  `setDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userID` int(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `nsi_receiving_pallets` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `receivingID` int(8) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `receivingID` (`receivingID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10000001 ;

CREATE TABLE IF NOT EXISTS `nsi_shipping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch` int(8) NOT NULL,
  `storeNumber` int(11) NOT NULL,
  `status` varchar(2) CHARACTER SET utf8 DEFAULT 'IN',
  PRIMARY KEY (`id`),
  KEY `batch` (`batch`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=900001 ;

CREATE TABLE IF NOT EXISTS `nsi_shipping_batches` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `setDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- New invoice tables

CREATE TABLE IF NOT EXISTS `invoices_receiving_batches` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `setDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `setDate` (`setDate`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1000001 ;

INSERT INTO `statuses` (`id`, `category`, `displayName`, `shortName`) VALUES (NULL, 'invoice', 'Received', 'RC'), (NULL, 'invoice', 'Shipped', 'SH');

DROP TABLE IF EXISTS `invoices_receiving`;
CREATE TABLE IF NOT EXISTS `invoices_receiving` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `containerID` int(9) NOT NULL,
  `invoiceBatch` int(8) NOT NULL,
  `inventoryBatch` int(8) NOT NULL,
  `statusID` int(2) NOT NULL,
  `rush` decimal(4,2) NOT NULL,
  `recUnits` decimal(4,2) NOT NULL,
  `recCC` decimal(4,2) NOT NULL,
  `recCV` decimal(4,2) NOT NULL,
  `recRush` decimal(4,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventoryBatch` (`inventoryBatch`),
  KEY `invoiceStatus` (`statusID`),
  KEY `invoiceBatch` (`invoiceBatch`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `invoices_receiving_batches`;
CREATE TABLE IF NOT EXISTS `invoices_receiving_batches` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `setDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `setDate` (`setDate`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1000001 ;


DROP TABLE IF EXISTS `invoices_storage`;
CREATE TABLE IF NOT EXISTS `invoices_storage` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `inventoryBatch` int(9) NOT NULL,
  `setDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `typeID` int(2) NOT NULL,
  `statusID` int(2) NOT NULL,
  `storCart` decimal(4,2) NOT NULL,
  `storEach` decimal(4,2) NOT NULL,
  `storVol` decimal(4,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch` (`inventoryBatch`,`typeID`),
  KEY `statusID` (`statusID`),
  KEY `typeID` (`typeID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- Locations and Pallet Sheet Import Changes

UPDATE vendors SET active = 13;

ALTER TABLE `vendors` auto_increment = 10040;

DROP INDEX batch ON invoices_storage;

-- WMCI is the new NOCI
ALTER TABLE `neworder` CHANGE `Status` `Status` VARCHAR(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'WMCI';
UPDATE `neworder` SET status = 'WMCI' WHERE status IN ('NOCI', 'NOCO');

-- Invoice status modifications
ALTER TABLE `statuses` CHANGE `category` `category` VARCHAR(12) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
UPDATE statuses SET category = 'invoiceType' WHERE category = 'invoice' AND shortName IN ('RC', 'SH');

-- Missing Order Statuses

INSERT INTO `statuses` (`id`, `category`, `displayName`, `shortName`) VALUES (NULL, 'orders', 'Entry Check-Out', 'WMCO'), 
(NULL, 'orders', 'Picking Check-In', 'PKCI'), (NULL, 'orders', 'Picking Check-Out', 'PKCO'), 
(NULL, 'orders', 'Processing Check-In', 'OPCI'), (NULL, 'orders', 'Processing Check-Out', 'OPCO'), 
(NULL, 'orders', 'Shipping Check-In', 'LSCI'), (NULL, 'orders', 'Shipped Check-Out', 'SHCO');



INSERT INTO `stores` (`StoreNumber`) VALUES ('561'), ('577'), ('582');

ALTER TABLE `locations` CHANGE `displayName` `displayName` VARCHAR(18) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

ALTER TABLE `invoices_receiving` DROP `containerID`;

-- Some new order table fields were too long
ALTER TABLE `neworder` CHANGE `customername` `customername` VARCHAR(100) CHARACTER 
SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL, CHANGE `vendor` `vendor` 
INT(5) NULL DEFAULT NULL, CHANGE `scanpicking` `scanpicking` VARCHAR(100) CHARACTER 
SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL, CHANGE `shipto` `shipto` 
VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL, 
CHANGE `shiptoaddress` `shiptoaddress` VARCHAR(100) CHARACTER SET latin1 COLLATE 
latin1_swedish_ci NULL DEFAULT NULL, CHANGE `shiptocity` `shiptocity` VARCHAR(100) 
CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL, CHANGE `partyname` 
`partyname` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 
NULL, CHANGE `partycity` `partycity` VARCHAR(100) CHARACTER SET latin1 COLLATE 
latin1_swedish_ci NULL DEFAULT NULL;


DELETE FROM `history_models` WHERE `history_models`.`displayName` IN ("invoicesStor", "invoicesRec") limit 2;

-- Making inventory cartons mulit index individual indexes
DROP INDEX containerID  ON inventory_cartons;
DROP INDEX containerID_2  ON inventory_cartons;
DROP INDEX batchID_2 ON inventory_cartons;
DROP INDEX locID_2 ON inventory_cartons;
DROP INDEX plate_2  ON inventory_cartons;
DROP INDEX plate_3 ON inventory_cartons;
CREATE INDEX statusID ON inventory_cartons (statusID);
CREATE INDEX cartonID ON inventory_cartons (cartonID);
CREATE UNIQUE INDEX uniqueCarton ON inventory_cartons (batchID, cartonID);

ALTER TABLE `online_orders_fails` CHANGE `reference_id` `reference_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `order_id` `order_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipment_id` `shipment_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_first_name` `shipping_first_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_last_name` `shipping_last_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_address_street` `shipping_address_street` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_address_street_cont` `shipping_address_street_cont` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_city` `shipping_city` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_state` `shipping_state` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_postal_code` `shipping_postal_code` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_country` `shipping_country` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_country_name` `shipping_country_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `product_sku` `product_sku` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `UPC` `UPC` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `warehouse_id` `warehouse_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `warehouse_name` `warehouse_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `product_quantity` `product_quantity` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `product_description` `product_description` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `product_cost` `product_cost` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `customer_phone_number` `customer_phone_number` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `order_date` `order_date` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `carrier` `carrier` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `ACCOUNT_NUMBER` `ACCOUNT_NUMBER` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `SELDAT_THIRD_PARTY` `SELDAT_THIRD_PARTY` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `online_orders_fails_update` CHANGE `reference_id` `reference_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `order_id` `order_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipment_id` `shipment_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipment_tracking_id` `shipment_tracking_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipment_sent_on` `shipment_sent_on` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipment_cost` `shipment_cost` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_first_name` `shipping_first_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_last_name` `shipping_last_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_address_street` `shipping_address_street` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_address_street_cont` `shipping_address_street_cont` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_city` `shipping_city` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_state` `shipping_state` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_postal_code` `shipping_postal_code` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_country` `shipping_country` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_country_name` `shipping_country_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `product_sku` `product_sku` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `UPC` `UPC` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `warehouse_id` `warehouse_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `warehouse_name` `warehouse_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `product_quantity` `product_quantity` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `product_name` `product_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `product_description` `product_description` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `product_cost` `product_cost` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `customer_phone_number` `customer_phone_number` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `order_date` `order_date` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `carrier` `carrier` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `ACCOUNT_NUMBER` `ACCOUNT_NUMBER` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `SELDAT_THIRD_PARTY` `SELDAT_THIRD_PARTY` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `online_orders` CHANGE `SCAN_SELDAT_ORDER_NUMBER` `SCAN_SELDAT_ORDER_NUMBER` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `reference_id` `reference_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `order_id` `order_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipment_id` `shipment_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipment_tracking_id` `shipment_tracking_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipment_sent_on` `shipment_sent_on` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipment_cost` `shipment_cost` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_first_name` `shipping_first_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_last_name` `shipping_last_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_address_street` `shipping_address_street` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_address_street_cont` `shipping_address_street_cont` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_city` `shipping_city` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_state` `shipping_state` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_postal_code` `shipping_postal_code` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_country` `shipping_country` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `shipping_country_name` `shipping_country_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `product_sku` `product_sku` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `UPC` `UPC` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `warehouse_id` `warehouse_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `warehouse_name` `warehouse_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `product_quantity` `product_quantity` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `product_description` `product_description` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `product_cost` `product_cost` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `customer_phone_number` `customer_phone_number` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `order_date` `order_date` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `carrier` `carrier` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `ACCOUNT_NUMBER` `ACCOUNT_NUMBER` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `SELDAT_THIRD_PARTY` `SELDAT_THIRD_PARTY` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `consolidations` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `waveNumber` int(1) NOT NULL,
  `userID` int(4) NOT NULL,
  `setDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `consolidations` CHANGE `waveNumber` `waveNumber` INT(1) NOT NULL DEFAULT '1';

CREATE TABLE IF NOT EXISTS `consolidation_waves` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `consolidationID` int(7) NOT NULL,
  `cartonID` int(7) NOT NULL,
  `prevLocID` int(7) NOT NULL,
  `newLocID` int(7) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `consolidationID` (`consolidationID`,`cartonID`,`prevLocID`,`newLocID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- Switching EDI over to new test site

GRANT ALL PRIVILEGES ON test.* TO 'seldat_edi'@'localhost';

-- New User Access Structure

CREATE TABLE IF NOT EXISTS `seldat_users.log_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) NOT NULL DEFAULT '',
  `quantity` int(1) NOT NULL DEFAULT '1',
  `lastUpdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `username` varchar(50) NOT NULL,
  `passRecovery` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`,`username`,`passRecovery`),
  KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

-- Table for users to recoever their passwords

CREATE TABLE IF NOT EXISTS `seldat_users.reset_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(4) NOT NULL,
  `setDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `code` varchar(12) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `User ID` (`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- Modifying Log Attempts to track password recovery attempts as well

DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `log_attempts`;
CREATE TABLE IF NOT EXISTS `log_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) NOT NULL DEFAULT '',
  `quantity` int(1) NOT NULL DEFAULT '1',
  `lastUpdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `username` varchar(50) NOT NULL,
  `passRecovery` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`,`username`,`passRecovery`),
  KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
-- Make the user table into the new user access table

UPDATE `user` u LEFT JOIN seldat_users.info i ON u.username = i.username SET u.username = IF (i.id, i.id, u.username);
ALTER TABLE `user` CHANGE `username` `userID` INT(4) NOT NULL;
ALTER TABLE `user` CHANGE `ID` `id` INT(4) NOT NULL AUTO_INCREMENT;
ALTER TABLE `user` DROP `firstName`, DROP `lastName`, DROP `active`;
DROP TABLE IF EXISTS `users_access`;
RENAME TABLE `user` TO users_access;

-- Get the new seldat_users.info table in its own SQL file


-- Shipping info to be entered on shipping check out, used for EDI

CREATE TABLE IF NOT EXISTS `orders_shipping_info` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `newOrderID` int(8) NOT NULL,
  `transMC` varchar(1) NOT NULL,
  `scac` varchar(4) NOT NULL,
  `shipType` varchar(2) NOT NULL,
  `trailerNumber` varchar(8) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newOrderID_2` (`newOrderID`),
  KEY `newOrderID` (`newOrderID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `orders_shipping_info` ADD `proNumber` INT NOT NULL AFTER `scac`;
ALTER TABLE `orders_shipping_info` CHANGE `proNumber` `proNumber` VARCHAR(11) NOT NULL;

-- New Tables cartons_logs cartons_logs_adds cartons_logs_fields cartons_logs_values

CREATE TABLE IF NOT EXISTS `cartons_logs` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `logTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userID` int(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `cartons_logs_adds` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `invID` int(8) NOT NULL,
  `addTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `invID` (`invID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `cartons_logs_fields` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `displayName` varchar(10) NOT NULL,
  `tableName` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `cartons_logs_values` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `logID` int(8) NOT NULL,
  `invID` int(8) NOT NULL,
  `fieldID` int(3) NOT NULL,
  `fromValue` int(10) NOT NULL,
  `toValue` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `logID` (`logID`),
  KEY `invID` (`invID`),
  KEY `fromValue` (`fromValue`),
  KEY `toValue` (`toValue`),
  KEY `fieldID` (`fieldID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Carton Log Fields

INSERT INTO `cartons_logs_fields` 
(`id`, `displayName`, `tableName`) 
VALUES (NULL, 'locID', 'locations'), (NULL, 'plate', 'licensePlate');

-- More Carton Log Fields

INSERT INTO `cartons_logs_fields` 
(`id`, `displayName`, `tableName`) 
VALUES (NULL, 'isSplit', ''), (NULL, 'unSplit', '');

INSERT INTO `cartons_logs_fields` 
(`id`, `displayName`, `tableName`) 
VALUES (NULL, 'orderID', 'newOrder'), (NULL, 'mStatusID', 'statuses'),
(NULL, 'statusID', 'statuses');

INSERT INTO `cartons_logs_fields` 
(`id`, `displayName`, `tableName`) 
VALUES (NULL, 'mLocID', 'locations');

ALTER TABLE `users_access` CHANGE `level` `levelID` INT(1) NOT NULL DEFAULT '4';
ALTER TABLE `users_access` ADD INDEX (`levelID`);

INSERT INTO `vendors` (`id`, `vendorName`, `active`, `email`, `warehouseID`) 
VALUES (NULL, 'Eccolo Ltd', '13', NULL, '1');

ALTER TABLE `inventory_batches` CHANGE `prefix` `prefix` 
VARCHAR(80) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `inventory_containers` CHANGE `name` `name` VARCHAR(50) CHARACTER 
SET utf8 COLLATE utf8_general_ci NOT NULL;


ALTER TABLE `inventory_containers` DROP INDEX `vendorID`;
ALTER TABLE `inventory_containers` ADD INDEX (`vendorID`);
ALTER TABLE `inventory_containers` ADD INDEX (`measureID`);
ALTER TABLE `inventory_cartons` ADD INDEX (`uom`);
ALTER TABLE inventory_cartons DROP INDEX containerID_2;
ALTER TABLE `inventory_cartons` ADD INDEX (`cartonID`);

INSERT INTO `statuses` (`id`, `category`, `displayName`, `shortName`) 
VALUES (NULL, 'order', 'Cancelled', 'CNCL');


ALTER TABLE `upcs` CHANGE `sku` `sku` VARCHAR(30) CHARACTER SET utf8 COLLATE 
utf8_general_ci NOT NULL;

ALTER TABLE `inventory_batches` CHANGE `sku` `sku` VARCHAR(30);

ALTER TABLE `neworder` CHANGE `payByInfo` `payByInfo` VARCHAR(250) CHARACTER 
SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `neworder` CHANGE `specialinstruction` `specialinstruction` TEXT 
CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;


ALTER TABLE `upcs` CHANGE `sku` `sku` VARCHAR(50) CHARACTER SET utf8 COLLATE 
utf8_general_ci NOT NULL;

ALTER TABLE `upcs` CHANGE `size` `size` VARCHAR(50) CHARACTER SET utf8 COLLATE 
utf8_general_ci NOT NULL, CHANGE `color` `color` VARCHAR(50) CHARACTER SET utf8 
COLLATE utf8_general_ci NOT NULL;

-- For WMS DBs

INSERT INTO `statuses` (`id`, `category`, `displayName`, `shortName`) 
VALUES (NULL, 'employers', 'Seldat', 'SD'), (NULL, 'employers', 'Client', 'CL');


-- For User DBs

ALTER TABLE `info` ADD `employer` INT NOT NULL , ADD INDEX (`employer`) ;

UPDATE `seldat_users`.info i JOIN statuses s SET i.employer = s.id
WHERE s.displayName = 'Seldat' AND s.category = 'employers';

ALTER TABLE `vendor_users` ADD `active` INT(2) NOT NULL , ADD INDEX (`active`) ;
UPDATE `seldat_users`.vendor_users v JOIN statuses s SET v.active = s.id
WHERE s.displayName = 'Active' AND s.category = 'vendors';

ALTER TABLE vendor_users DROP INDEX userID, ADD UNIQUE INDEX `userID` (userID, vendorID);

