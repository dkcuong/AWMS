ALTER TABLE bill_of_lading ADD UNIQUE (newOrderId);

-- 12/18/2014 ---

ALTER TABLE `online_orders_exports` CHANGE `orderID` `orderID` VARCHAR(50) NOT NULL;

-- 12/18/2014 ---

-- setting default value to isSplit field

ALTER TABLE `inventory_cartons` CHANGE `isSplit` `isSplit` TINYINT(1) NOT NULL DEFAULT '0';

-- adding email field to vendors table
ALTER TABLE `vendors` ADD `email` VARCHAR(50) NULL DEFAULT NULL;

-- 12/19/2014 ---
-- resizing `shortName` field up to 4

ALTER TABLE `statuses` CHANGE `shortName` `shortName` VARCHAR(4) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- 12/22/2014 ---
-- removing redundant fields for neworder table

ALTER TABLE `neworder`
  DROP `otherdocument`,
  DROP `otherlabel`;

-- 01/02/2015 ---
-- removing redundant fields for workorder table

ALTER TABLE `refs` ADD `Active` TINYINT NULL AFTER `prefix`;

-- 01/05/2015 ---
-- changing inputName field values

UPDATE `refs` SET `inputName` = 'woHRmApPT' WHERE `work` = 'HANDELING REMOVE AND APPLY PRICE TICKET';
UPDATE `refs` SET `inputName` = 'woHRmPT' WHERE `work` = 'HANDELING REMOVE PRICE TICKET';
UPDATE `refs` SET `inputName` = 'woHRmApH' WHERE `work` = 'HANDELING REMOVE AND APPLY HANGERS';
UPDATE `refs` SET `inputName` = 'woHPPack' WHERE `work` = 'HANDELING PICK PACK';

-- 01/06/2015 ---
-- adding unique index to invoices_work_orders table

ALTER TABLE `invoices_work_orders` ADD UNIQUE `workordernumber` (`workordernumber`);

-- 01/09/2015 ---
-- adding/changing invoices_work_orders table fields 

ALTER TABLE `invoices_work_orders` CHANGE `woHCrtVol` `cartonVol` DECIMAL(8,2) NOT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHCrtUsed` `newCartonUsed` INT(3) NOT NULL;
ALTER TABLE `invoices_work_orders` ADD `woHCrtMt` DECIMAL(8,2) NOT NULL AFTER `woHCMark`;
ALTER TABLE `invoices_work_orders` ADD `woHApLbl` DECIMAL(8,2) NOT NULL AFTER `woHPrApLbl`;
ALTER TABLE `invoices_work_orders` ADD `statusID` INT(3) NOT NULL ;

UPDATE `refs` SET `inputName` = 'woHRmApH' WHERE `work` = 'HANDELING REMOVE AND APPLY HANGERS';
UPDATE `refs` SET `inputName` = 'woHPPack' WHERE `work` = 'HANDELING PICK PACK';


-- 01/10/2015 ---
-- adding date when the order is shipped (needed in Shipping Dashboards)

ALTER TABLE `neworder` ADD `orderShipDate` DATE NOT NULL DEFAULT '0000-00-00';

-- 01/12/2015 ---
-- adding default values to fields that are not populated from Work Order Check-In/Out

ALTER TABLE `invoices_work_orders` CHANGE `newCartonUsed` `newCartonUsed` INT(3) NULL DEFAULT NULL; 
ALTER TABLE `invoices_work_orders` CHANGE `boxSize` `boxSize` DECIMAL(6,2) NULL DEFAULT NULL; 
ALTER TABLE `invoices_work_orders` CHANGE `cartonVol` `cartonVol` DECIMAL(8,2) NULL DEFAULT NULL; 

-- 01/13/2015 ---
-- adding default value to orderShipDate field

ALTER TABLE `neworder` CHANGE `orderShipDate` `orderShipDate` DATE NOT NULL DEFAULT '0000-00-00';

-- 01/13/2015 ---
-- invoices_work_orders: adding default values

ALTER TABLE `invoices_work_orders` CHANGE `woHRush` `woHRush` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHLabour` `woHLabour` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHQC` `woHQC` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHOTime` `woHOTime` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHUProc` `woHUProc` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHMSets` `woHMSets` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHSpec` `woHSpec` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHApBag` `woHApBag` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHBagMt` `woHBagMt` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRmApBag` `woHRmApBag` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRmBag` `woHRmBag` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHBLblMt` `woHBLblMt` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHCLabel` `woHCLabel` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHCMark` `woHCMark` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHCrtMt` `woHCrtMt` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `newCartonUsed` `newCartonUsed` INT(3) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `boxSize` `boxSize` DECIMAL(6,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `cartonVol` `cartonVol` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHPrApLbl` `woHPrApLbl` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHApLbl` `woHApLbl` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRmCLbl` `woHRmCLbl` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHSType1` `woHSType1` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHSType2` `woHSType2` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHSType3` `woHSType3` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHSType4` `woHSType4` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHSType5` `woHSType5` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHFold` `woHFold` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHApHng` `woHApHng` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHHngMt` `woHHngMt` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRmApH` `woHRmApH` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRmHng` `woHRmHng` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHApSt` `woHApSt` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRmApSt` `woHRmApSt` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRmSt` `woHRmSt` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHStMt` `woHStMt` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHCutSew` `woHCutSew` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHApSz` `woHApSz` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRmApSz` `woHRmApSz` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRmSz` `woHRmSz` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHSzMt` `woHSzMt` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRep` `woHRep` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHSort` `woHSort` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHPPack` `woHPPack` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHApPT` `woHApPT` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHApPTS` `woHApPTS` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHPTSMt` `woHPTSMt` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRmApPT` `woHRmApPT` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRmApPTS` `woHRmApPTS` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRmPT` `woHRmPT` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_work_orders` CHANGE `woHRmPTS` `woHRmPTS` DECIMAL(8,2) NULL DEFAULT NULL;

-- 01/23/2015 ---
-- Refactoring online_orders_exports table 

CREATE TABLE IF NOT EXISTS `online_orders_exports` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `orderBatch` INT(7) NULL DEFAULT NULL,
    `orderID` INT(10) NULL DEFAULT NULL,
    `packageReference` VARCHAR(50) NULL DEFAULT NULL,
    `from_company` VARCHAR(50) NULL DEFAULT NULL,
    `from_name` VARCHAR(50) NULL DEFAULT NULL,
    `from_address_1` VARCHAR(50) NULL DEFAULT NULL,
    `from_address_2` VARCHAR(50) NULL DEFAULT NULL,
    `from_city` VARCHAR(50) NULL DEFAULT NULL,
    `from_state` VARCHAR(2) NULL DEFAULT NULL,
    `from_postal` VARCHAR(10) NULL DEFAULT NULL,
    `from_country` VARCHAR(50) NULL DEFAULT NULL,
    `from_phone` VARCHAR(20) NULL DEFAULT NULL,
    `from_email` VARCHAR(50) NULL DEFAULT NULL,
    `from_notify_on_shipment` TINYINT(1) NOT NULL DEFAULT '0',
    `from_notify_on_exception` TINYINT(1) NOT NULL DEFAULT '0',
    `from_notify_on_delivery` TINYINT(1) NOT NULL DEFAULT '0',
    `to_company` VARCHAR(50) NULL DEFAULT NULL,
    `to_name` VARCHAR(50) NULL DEFAULT NULL,
    `to_address_1` VARCHAR(50) NULL DEFAULT NULL,
    `to_address_2` VARCHAR(50) NULL DEFAULT NULL,
    `to_city` VARCHAR(50) NULL DEFAULT NULL,
    `to_state` VARCHAR(2) NULL DEFAULT NULL,
    `to_postal` VARCHAR(10) NULL DEFAULT NULL,
    `to_country` VARCHAR(50) NULL DEFAULT NULL,
    `to_phone` VARCHAR(20) NULL DEFAULT NULL,
    `to_email` VARCHAR(50) NULL DEFAULT NULL,
    `to_notify_on_shipment` TINYINT(1) NOT NULL DEFAULT '0',
    `to_notify_on_exception` TINYINT(1) NOT NULL DEFAULT '0',
    `to_notify_on_delivery` TINYINT(1) NOT NULL DEFAULT '0',
    `signature` VARCHAR(50) NOT NULL DEFAULT 'None Required',
    `saturday_delivery` TINYINT(1) NOT NULL DEFAULT '0',
    `reference_1` VARCHAR(20) NULL DEFAULT NULL,
    `reference_2` VARCHAR(20) NULL DEFAULT NULL,
    `provider` VARCHAR(20) NULL DEFAULT NULL,
    `package_type` VARCHAR(50) NULL DEFAULT NULL,
    `service` VARCHAR(50) NULL DEFAULT NULL,
    `bill_to` VARCHAR(50) NULL DEFAULT NULL,
    `third_party_acc_num` VARCHAR(20) NULL DEFAULT NULL,
    `third_party_postal_code` VARCHAR(10) NULL DEFAULT NULL,
    `third_party_country_code` VARCHAR(50) NULL DEFAULT NULL,
    `package_weight` DECIMAL(5,1) NULL DEFAULT NULL,
    `package_length` DECIMAL(5,2) NULL DEFAULT NULL,
    `package_width` DECIMAL(5,2) NULL DEFAULT NULL,
    `package_height` DECIMAL(5,2) NULL DEFAULT NULL,
    `package_insured_value` DECIMAL(10,2) NULL DEFAULT NULL,
    `can_be_merged` TINYINT(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`),
    KEY `orderID` (`orderID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `online_orders_fails_update` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `submitTime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reference_id` VARCHAR(50) NULL DEFAULT NULL,
    `order_id` INT(10) NULL DEFAULT NULL,
    `from_company` VARCHAR(50) NULL DEFAULT NULL,
    `from_name` VARCHAR(50) NULL DEFAULT NULL,
    `from_address_1` VARCHAR(50) NULL DEFAULT NULL,
    `from_address_2` VARCHAR(50) NULL DEFAULT NULL,
    `from_city` VARCHAR(50) NULL DEFAULT NULL,
    `from_state` VARCHAR(2) NULL DEFAULT NULL,
    `from_postal` VARCHAR(10) NULL DEFAULT NULL,
    `from_country` VARCHAR(50) NULL DEFAULT NULL,
    `from_phone` VARCHAR(20) NULL DEFAULT NULL,
    `from_email` VARCHAR(50) NULL DEFAULT NULL,
    `from_notify_on_shipment` TINYINT(1) NULL DEFAULT NULL,
    `from_notify_on_exception` TINYINT(1) NULL DEFAULT NULL,
    `from_notify_on_delivery` TINYINT(1) NULL DEFAULT NULL,
    `to_company` VARCHAR(50) NULL DEFAULT NULL,
    `to_name` VARCHAR(50) NULL DEFAULT NULL,
    `to_address_1` VARCHAR(50) NULL DEFAULT NULL,
    `to_address_2` VARCHAR(50) NULL DEFAULT NULL,
    `to_city` VARCHAR(50) NULL DEFAULT NULL,
    `to_state` VARCHAR(2) NULL DEFAULT NULL,
    `to_postal` VARCHAR(10) NULL DEFAULT NULL,
    `to_country` VARCHAR(50) NULL DEFAULT NULL,
    `to_phone` VARCHAR(20) NULL DEFAULT NULL,
    `to_email` VARCHAR(50) NULL DEFAULT NULL,
    `to_notify_on_shipment` TINYINT(1) NULL DEFAULT NULL,
    `to_notify_on_exception` TINYINT(1) NULL DEFAULT NULL,
    `to_notify_on_delivery` TINYINT(1) NULL DEFAULT NULL,
    `signature` VARCHAR(50) NULL DEFAULT NULL,
    `saturday_delivery` TINYINT(1) NULL DEFAULT NULL,
    `reference_1` VARCHAR(20) NULL DEFAULT NULL,
    `reference_2` VARCHAR(20) NULL DEFAULT NULL,
    `provider` VARCHAR(20) NULL DEFAULT NULL,
    `package_type` VARCHAR(50) NULL DEFAULT NULL,
    `service` VARCHAR(50) NULL DEFAULT NULL,
    `bill_to` VARCHAR(50) NULL DEFAULT NULL,
    `third_party_acc_num` VARCHAR(20) NULL DEFAULT NULL,
    `third_party_postal_code` VARCHAR(10) NULL DEFAULT NULL,
    `third_party_country_code` VARCHAR(50) NULL DEFAULT NULL,
    `package_weight` DECIMAL(5,1) NULL DEFAULT NULL,
    `package_length` DECIMAL(5,2) NULL DEFAULT NULL,
    `package_width` DECIMAL(5,2) NULL DEFAULT NULL,
    `package_height` DECIMAL(5,2) NULL DEFAULT NULL,
    `package_insured_value` DECIMAL(10,2) NULL DEFAULT NULL,
    `can_be_merged` TINYINT(1) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


-- 01/27/2015 ---
-- adding index on SCAN_SELDAT_ORDER_NUMBER field to online_orders table

ALTER TABLE online_orders ADD INDEX (SCAN_SELDAT_ORDER_NUMBER);

-- 01/27/2015 ---
-- adding index on to_address_3 field

ALTER TABLE `online_orders_exports` ADD `to_address_3` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `to_address_2`;
ALTER TABLE `online_orders_fails_update` ADD `to_address_3` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `to_address_2`;

-- 01/28/2015 ---
-- incresing fields' length

ALTER TABLE `online_orders_exports` CHANGE `package_weight` `package_weight` DECIMAL(10,1) NULL DEFAULT NULL;
ALTER TABLE `online_orders_exports` CHANGE `package_length` `package_length` DECIMAL(10,2) NULL DEFAULT NULL;
ALTER TABLE `online_orders_exports` CHANGE `package_width` `package_width` DECIMAL(10,2) NULL DEFAULT NULL;
ALTER TABLE `online_orders_exports` CHANGE `package_height` `package_height` DECIMAL(10,2) NULL DEFAULT NULL;

ALTER TABLE `online_orders_fails_update` CHANGE `package_weight` `package_weight` DECIMAL(10,1) NULL DEFAULT NULL;
ALTER TABLE `online_orders_fails_update` CHANGE `package_length` `package_length` DECIMAL(10,2) NULL DEFAULT NULL;
ALTER TABLE `online_orders_fails_update` CHANGE `package_width` `package_width` DECIMAL(10,2) NULL DEFAULT NULL;
ALTER TABLE `online_orders_fails_update` CHANGE `package_height` `package_height` DECIMAL(10,2) NULL DEFAULT NULL;

-- 01/28/2015 ---
-- dropping table connected with bill of lading 



-- 02/06/2015 ---
-- increasing category field width to fit "invoiceType" value

ALTER TABLE `statuses` CHANGE `category` `category` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- 02/11/2015 ---
-- modifying order_description table

CREATE TABLE IF NOT EXISTS `order_description` (
`id` int(11) NOT NULL,
  `fieldName` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `mandatory` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=26 ;


INSERT INTO `order_description` (`id`, `fieldName`, `description`, `mandatory`) VALUES
(1, 'Reference ID', 'Client Order Number - Customer PO', 1),
(2, 'Order ID', 'Client Order Number', 1),
(3, 'Shipment ID', 'Customer PO', 1),
(4, 'Shipping First Name', 'Ship To Address: First Name', 1),
(5, 'Shipping Last Name', 'Ship To Address: Last Name', 1),
(6, 'Shipping Address Street', 'Ship To Address: Address Line 1', 1),
(7, 'Shipping Address Street Cont', 'Ship To Address: Address Line 2', 0),
(8, 'Shipping City', 'Ship To Address: City', 1),
(9, 'Shipping State', 'Ship To Address: Province/State', 1),
(10, 'Shipping Postal Code', 'Ship To Address: Zip/Postal Code', 1),
(11, 'Shipping Country', 'Ship To Address: Country', 1),
(12, 'Shipping Country Name', 'Ship To Address: Country Full Name', 0),
(13, 'Product SKU', 'SKU/Style', 1),
(14, 'UPC', 'Universal Product Code', 1),
(15, 'Warehouse ID', 'Warehouse Number', 0),
(16, 'Warehouse Name', 'Warehouse', 0),
(17, 'Product Quantity', 'Qty/UOM', 1),
(18, 'Product Name', 'Product Name', 1),
(19, 'Product Description', 'Description/color/size', 0),
(20, 'Product Cost', 'Price/UOM', 1),
(21, 'Customer Phone Number', 'Ship To Address:Phone', 0),
(22, 'Order Date', 'Order Date', 1),
(23, 'Carrier', 'Carrier', 1),
(24, 'Account Number', 'Account No', 0),
(25, 'Seldat/Third Party', '', 0);

ALTER TABLE `order_description` ADD PRIMARY KEY (`id`);

ALTER TABLE `order_description`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=26;

-- 02/11/2015 ---
-- deleting entry regarding class moved from 'tables\workOrders' to 'tables' folder

DELETE FROM `history_models` WHERE `id` = '9';

-- 02/11/2015 ---
-- creating index on batchnumber field in order to boost sql query

ALTER TABLE masterlabel ADD INDEX (`batchnumber`);

-- 02/13/2015 ---
-- changing Product Quantity description to 'Total Units Per Product'

UPDATE order_description SET description = 'Total Units Per Product' WHERE `id`= 17

-- 02/13/2015 ---
-- order_date field in online_orders to datetime

ALTER TABLE online_orders CHANGE order_date order_date DATETIME NULL DEFAULT NULL;

-- 02/18/2015 ---
-- creating order_products table

CREATE TABLE IF NOT EXISTS `order_products` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `orderID` INT(10) NOT NULL,
    `quantity` INT(10) NULL DEFAULT NULL,
    `sku` VARCHAR(25) NULL DEFAULT NULL,
    `size` VARCHAR(25) NULL DEFAULT NULL,
    `color` VARCHAR(25) NULL DEFAULT NULL,
    `upcID` INT(10) NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 02/18/2015 ---
-- creating inventory keys tables

CREATE TABLE IF NOT EXISTS `inv_keys` (
    `id` INT(10) NOT NULL AUTO_INCREMENT,
    `sku` VARCHAR(25) NULL DEFAULT NULL,
    `uom` INT(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `inv_keys_cartons` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `invKey` INT(10) NOT NULL,
    `locID` INT(7) NULL DEFAULT NULL,
    `plate` INT(9) NULL DEFAULT NULL,
    `statusID` INT(2) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO inv_keys (
    uom, 
    sku
) 
SELECT    uom,
          sku
FROM 	  inventory_cartons ca
LEFT JOIN inventory_batches b ON b.id = ca.batchID
GROUP BY sku, uom;

INSERT INTO inv_keys_cartons (
    invKey, 
    locID, 
    statusID, 
    plate
)
SELECT ik.id, locID, statusID, plate
FROM (SELECT    ca.locID, 
                ca.plate, 
                ca.statusID, 
                ca.uom,
                b.sku
      FROM 	    inventory_cartons ca
      LEFT JOIN inventory_batches b ON b.id = ca.batchID) ca
LEFT JOIN inv_keys ik ON ik.uom = ca.uom AND ik.sku = ca.sku;

-- 02/19/2015 ---
-- creating unique for inv_keys tables

-- ALTER TABLE `inv_keys` ADD UNIQUE `sku_uom` (`sku`, `uom`);

-- 02/20/2015 ---
-- adding "manual" locations and statuses fields to inventory_cartons table

ALTER TABLE `inventory_cartons` ADD `mLocID` INT(7) NULL DEFAULT NULL AFTER `locID`;
ALTER TABLE `inventory_cartons` ADD `mStatusID` INT(2) NULL DEFAULT NULL AFTER `statusID`;

-- populating "manual" locations and statuses fields

UPDATE inventory_cartons
    SET mLocID = locID
    WHERE locID;
    
UPDATE inventory_cartons
    SET mStatusID = statusID
    WHERE statusID;

-- adding Reserved status to statuses table

INSERT INTO `statuses` (`category`, `displayName`, `shortName`) VALUES ('inventory', 'Reserved', 'RS');

-- adding cartonID field (foreign key to id field in inventory_cartons table) to order_products table

ALTER TABLE `order_products` ADD `cartonID` INT(10) NOT NULL AFTER orderID;

-- adding Picked status to statuses table

INSERT INTO `statuses` (`category`, `displayName`, `shortName`) VALUES ('inventory', 'Picked', 'PK');

-- refactoring order_products table

ALTER TABLE `order_products` ADD `active` BOOLEAN NOT NULL DEFAULT TRUE ;
ALTER TABLE `order_products` DROP `quantity`;
ALTER TABLE `order_products` DROP `sku`;
ALTER TABLE `order_products` DROP `size`;
ALTER TABLE `order_products` DROP `color`;
ALTER TABLE `order_products` DROP `upcID`;

ALTER TABLE order_products ADD INDEX (`orderID`);
ALTER TABLE order_products ADD INDEX (`cartonID`);

-- adding default value to mLocID and mStatusID fields

ALTER TABLE `inventory_cartons` CHANGE `mLocID` `mLocID` INT(7) NULL DEFAULT NULL;
ALTER TABLE `inventory_cartons` CHANGE `mStatusID` `mStatusID` INT(2) NULL DEFAULT NULL;

-- adding staging locations

INSERT INTO locations (displayName, locationNumber, isShipping, warehouseID, cubicFeet, distance) VALUES 
('staging', '0', '1', '1', '999.99', '1'),
('staging', '0', '1', '2', '999.99', '1'),
('staging', '0', '1', '3', '999.99', '1');

-- 02/26/2015 ---
-- creating inventory keys tables

CREATE TABLE IF NOT EXISTS `wave_cartons` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `batch` INT(9) NOT NULL,
    `waveID` INT(10) NOT NULL,
    `cartonID` INT(10) NOT NULL,
    `active` BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`id`),
    KEY `batch` (`batch`),
    KEY `waveID` (`waveID`),
    KEY `cartonID` (`cartonID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 02/27/2015 ---

UPDATE `locations` SET isShipping = '0', cubicFeet= '0', distance = '0' WHERE displayName = 'staging';

-- adding field for lanes (shipping locations)

ALTER TABLE `wave_cartons` ADD `locID` INT(7) NULL DEFAULT NULL AFTER `cartonID`;

-- 03/02/2015 ---
-- moving wave picks cartons reservation from wave_cartons to order_products table

ALTER TABLE `order_products` ADD `waveID` INT(10) NULL DEFAULT NULL AFTER `cartonID`;
ALTER TABLE `order_products` ADD `locID` INT(7) NULL DEFAULT NULL AFTER `cartonID`;

-- 03/02/2015 ---
-- moving wave picks cartons reservation from order_products to wave_picks table

ALTER TABLE `order_products` DROP `waveID`;
ALTER TABLE `order_products` DROP `locID`;

CREATE TABLE IF NOT EXISTS `wave_picks` (
    `id` int(10) NOT NULL AUTO_INCREMENT,
    `batch` int(7) NOT NULL,
    `cartonID` int(10) NOT NULL,
    `locID` int(7) NOT NULL,
    `statusID` int(3) NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`),
    KEY `batch` (`batch`),
    KEY `cartonID` (`cartonID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO statuses (category, displayName, shortName) VALUES 
('wave_picks', 'Active', 'AC'),
('wave_picks', 'Inactive', 'IA'),
('wave_picks', 'Cancelled', 'CN');

-- 03/06/2015 ---
-- refactoringing wave picks tables

CREATE TABLE IF NOT EXISTS `order_picks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `orderID` int(10) NOT NULL,
    `cartonID` int(10) NOT NULL,
    `pickID` int(10) NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`),
    KEY `orderID` (`orderID`),
    KEY `pickID` (`pickID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `wave_picks` (
    `id` int(10) NOT NULL AUTO_INCREMENT,
    `locID` int(7) NOT NULL,
    `statusID` int(3) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `pickID` (`locID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 03/09/2015 ---
-- removing batch_order field from online_orders table

-- move order batches from online_orders to neworder table

UPDATE `neworder`n
JOIN `online_orders` o ON n.scanordernumber = o.SCAN_SELDAT_ORDER_NUMBER
SET `order_batch` = `batch_order`
WHERE `order_batch` IS NULL;

-- remove batch_order field 

ALTER TABLE `online_orders` DROP `batch_order`;

-- 03/11/2015 ---
-- using camelCase for statuses.category field value

UPDATE statuses SET category = 'wavePicks' WHERE category = 'wave_picks';
UPDATE statuses SET category = 'onlineOrders' WHERE category = 'online_orders';
UPDATE statuses SET category = 'routing' WHERE category = 'Routing';

-- changing category for online orders errors

UPDATE statuses SET category = 'orderErrors' WHERE category = 'onlineOrders';

-- 03/13/2015 ---
-- creating a table for error order products

CREATE TABLE IF NOT EXISTS `order_picks_fails` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `orderID` int(10) NOT NULL,
    `quantity` INT(10) NULL DEFAULT NULL,
    `sku` VARCHAR(25) NULL DEFAULT NULL,
    `size` VARCHAR(25) NULL DEFAULT NULL,
    `color` VARCHAR(25) NULL DEFAULT NULL,
    `upcID` INT(10) NULL DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`),
    KEY `orderID` (`orderID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `order_picks_fails` DROP `sku`;
ALTER TABLE `order_picks_fails` DROP `size`;
ALTER TABLE `order_picks_fails` DROP `color`;

-- 03/17/2015 ---
-- renaming wave picks tables

RENAME TABLE `order_picks_fails` TO `pick_errors`;
RENAME TABLE `wave_picks` TO `pick_waves`;
RENAME TABLE `order_picks` TO `pick_cartons`;

-- 03/17/2015 ---
-- creating order picks table

CREATE TABLE IF NOT EXISTS `pick_orders` (
    `id` int(8) NOT NULL AUTO_INCREMENT,
    `orderID` int(10) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `orderID` (`orderID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `pick_orders` (`orderID`) 
    SELECT DISTINCT `orderID`
    FROM `pick_cartons`;

-- 04/02/2015 ---
-- dropping fields sku/size/color fields. Upcs table will be used instead

ALTER TABLE `inventory_batches` DROP `sku`;
ALTER TABLE `inventory_batches` DROP `size1`;
ALTER TABLE `inventory_batches` DROP `color1`;

-- 04/02/2015 ---
-- restoring sku/size/color fields

ALTER TABLE `inventory_batches` 
    ADD `sku` VARCHAR(25) NOT NULL AFTER `upcID`, 
    ADD `size1` VARCHAR(25) NOT NULL AFTER `weight`, 
    ADD `color1` VARCHAR(25) NOT NULL AFTER `size1`;


-- 04/03/2015 ---
-- making id field in adjustment_logs table AUTO_INCREMENT

ALTER TABLE `adjustment_logs` ADD PRIMARY KEY(`id`);
ALTER TABLE `adjustment_logs` CHANGE `id` `id` INT(10) NOT NULL AUTO_INCREMENT;

-- 04/13/2015 ---
-- adding unique to pick_cartons to revert active from "0" to "1"  if we are about
-- to reserve cartons that were previously reserved for this order and later cancelled

ALTER TABLE `pick_cartons` ADD UNIQUE `orderCarton` (`orderID`, `cartonID`);

-- pick_errors: remove extra fields and making upcID field mandatory

ALTER TABLE `pick_errors`
  DROP `sku`,
  DROP `size`,
  DROP `color`;
ALTER TABLE `pick_errors` CHANGE `upcID` `upcID` INT(10) NOT NULL;


-- 04/24/2015 ---
-- add locationID field to warehouses table
-- drop vendor field from neworder table
-- drop vendor and location fields from workorder table

ALTER TABLE `warehouses` ADD `locationID` INT(2) NULL DEFAULT NULL AFTER `shortName`;
ALTER TABLE `workorder` DROP `location`;
ALTER TABLE `neworder` DROP `vendor`;
ALTER TABLE `workorder` DROP `vendor`;

-- 04/29/2015 ---
-- info table: make NULL a default value for email field

ALTER TABLE `info` CHANGE `email` `email` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

-- 05/04/2015 ---
-- neworder table: make first_name and last_name columns width to be the same

ALTER TABLE `neworder` CHANGE `first_name` `first_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- 05/08/2015
-- dropping index for a non existing field

ALTER TABLE `users_access` DROP INDEX `Username`;

-- 05/11/2015
-- adding index on orderID field to invoices_processing

ALTER TABLE invoices_processing ADD INDEX (orderID);
ALTER TABLE invoices_processing ADD UNIQUE (orderID);

-- 05/11/2015
-- adding statuses for Work Orders

INSERT INTO `statuses` (`category`, `displayName`, `shortName`) VALUES ('workorders', 'Work Order Check-In', 'WOCI');
INSERT INTO `statuses` (`category`, `displayName`, `shortName`) VALUES ('workorders', 'Work Order Check-Out', 'WOCO');

-- 05/15/2015 ---
-- adding index on cartonID field to pick_cartons table

ALTER TABLE pick_cartons ADD INDEX (cartonID);

-- creating table to track changes to neworder table statuses

CREATE TABLE IF NOT EXISTS `order_control` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `orderID` int(10) NOT NULL,
  `statusID` int(2) NOT NULL,
  `changeDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE order_control ADD INDEX (orderID);
ALTER TABLE order_control ADD INDEX (statusID);

-- adding missing indexes for dashboard shipping

ALTER TABLE neworder ADD INDEX (scanordernumber);
ALTER TABLE workorder ADD INDEX (workordernumber);

-- 05/18/2015 ---
-- adding UNIQUE value to order_control table

ALTER TABLE order_control ADD UNIQUE orderUnique (orderID, statusID);

-- adding userid field to track who did make a change to order status in neworder table

ALTER TABLE `order_control` ADD `userid` INT(4) NULL DEFAULT NULL ;

-- adding UNIQUE value to users_access table

ALTER TABLE users_access ADD UNIQUE userUnique (userID);

