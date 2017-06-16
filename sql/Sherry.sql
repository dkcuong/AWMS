

CREATE TABLE IF NOT EXISTS `invoices_processing` (
`id` int(11) NOT NULL,
  `vendorID` int(7) DEFAULT NULL,
  `orderID` int(10) DEFAULT NULL,
  `oprcUnCost` decimal(4,2) DEFAULT NULL,
  `oprcTSCost` decimal(4,2) DEFAULT NULL,
  `oprcShFrei` decimal(4,2) DEFAULT NULL,
  `oprcSLabel` decimal(4,2) DEFAULT NULL,
  `oprcSUPSFd` decimal(4,2) DEFAULT NULL,
  `oprcEdiCos` decimal(4,2) DEFAULT NULL,
  `oprcTBOLCo` decimal(4,2) DEFAULT NULL,
  `oprcSPPack` decimal(4,2) DEFAULT NULL,
  `oprcSCrtP` decimal(4,2) DEFAULT NULL,
  `oprcSOOnli` decimal(5,2) NOT NULL,
  `oprcTPCost` decimal(4,2) DEFAULT NULL,
  `oprcSOStnd` decimal(5,2) DEFAULT NULL,
  `oprcSORush` decimal(4,2) DEFAULT NULL,
  `oprcSOSRus` decimal(5,2) DEFAULT NULL,
  `oprcSLabou` decimal(4,2) DEFAULT NULL,
  `oprcSOTime` decimal(4,2) DEFAULT NULL,
  `statusID` int(2) DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT= 1;


ALTER TABLE `invoices_processing`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `invoices_processing`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT= 1;

ALTER TABLE `invoices_processing` ADD INDEX(`orderID`);


INSERT INTO `refs` (`id`, `ref`, `work`, `inputName`, `prefix`, `Active`) VALUES (NULL, 'EDI-COST', 'EDI COST', 'oprcEdiCos', 'oprc', '1');



INSERT INTO `refs` (`id`, `ref`, `work`, `inputName`, `prefix`, `Active`) VALUES (NULL, 'REGULAR-ORDER', 'REGULAR ORDER COST', 'oprcROCost', 'oprc', '1');

ALTER TABLE `invoices_processing` ADD `oprcROCost` DECIMAL(4, 2) NULL DEFAULT NULL AFTER `oprcSCrtP`;


-- 1/18/2015


ALTER TABLE `invoices_processing` DROP `vendorID`;

CREATE TABLE IF NOT EXISTS `paidby` (
`id` int(1) NOT NULL,
  `name` varchar(9) DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;


INSERT INTO `paidby` (`id`, `name`) VALUES
(1, 'client'),
(2, 'seldat'),
(3, '3rdparty');

ALTER TABLE `paidby`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `paidby`
MODIFY `id` int(1) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;

--
ALTER TABLE `paidby` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `invoices_processing` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 1/13/2015
ALTER TABLE `inventory_cartons` ADD `rackDate` DATE NOT NULL ;
ALTER TABLE `inventory_cartons` CHANGE `rackDate` `rackDate` DATE NULL DEFAULT NULL;

ALTER TABLE `inventory_cartons` CHANGE `rackDate` `rackDate` DATE NOT NULL DEFAULT '0000-00-00';

-- 1/14/2015
ALTER TABLE `invoices_processing` CHANGE `oprcUnCost` `oprcUnCost` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcTSCost` `oprcTSCost` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcShFrei` `oprcShFrei` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcSLabel` `oprcSLabel` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcSUPSFd` `oprcSUPSFd` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcEdiCos` `oprcEdiCos` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcTBOLCo` `oprcTBOLCo` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcSPPack` `oprcSPPack` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcSCrtP` `oprcSCrtP` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcROCost` `oprcROCost` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcSOOnli` `oprcSOOnli` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcTPCost` `oprcTPCost` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcSOStnd` `oprcSOStnd` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcSORush` `oprcSORush` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcSOSRus` `oprcSOSRus` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcSLabou` `oprcSLabou` DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE `invoices_processing` CHANGE `oprcSOTime` `oprcSOTime` DECIMAL(8,2) NULL DEFAULT NULL;


-- combine tally_sheet's vendor and wareshouse
ALTER TABLE `pallet_sheets` DROP `warehouseID`;



--
RENAME TABLE `paidby` TO `paid_by`;

-- 2/5/2015
ALTER TABLE `inventory_splits` ADD `active` TINYINT(1) NULL DEFAULT '1' ;
ALTER TABLE `inventory_cartons` ADD `unSplit` TINYINT(1) NULL DEFAULT '0' AFTER `isSplit`;


-- 2/13/2015


ALTER TABLE `online_orders` CHANGE `order_date` `order_date` TIMESTAMP NULL DEFAULT NULL;

-- 2/24/2015

ALTER TABLE `neworder` ADD `order_batch` INT(8) NULL DEFAULT NULL AFTER `customerordernumber`;

INSERT INTO `statuses` (`id`, `category`, `displayName`, `shortName`) VALUES (NULL, 'inventory', 'discrepancy', 'DS');


CREATE TABLE IF NOT EXISTS `adjustment_logs` (
`id` int(10) NOT NULL,
  `cartonID` int(11) DEFAULT NULL,
  `oldPlate` int(8) DEFAULT NULL,
  `newPlate` int(8) DEFAULT NULL,
  `oldLocID` varchar(18) DEFAULT NULL,
  `newLocID` varchar(18) DEFAULT NULL,
  `oldStatusID` tinyint(2) DEFAULT NULL,
  `newStatusID` tinyint(2) DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `adjustment_logs` ADD `dateAdjusted` TIMESTAMP NULL DEFAULT NULL ;

-- 3/12
ALTER TABLE `order_batches` CHANGE `dealSiteID` `dealSiteID` INT(11) NOT NULL;

-- 3/31

ALTER TABLE upcs ADD UNIQUE KEY (upc, sku, size, color)

-- 4/27
ALTER TABLE `neworder` DROP `customername`;
ALTER TABLE `neworder` ADD `first_name` VARCHAR(25) NULL DEFAULT NULL AFTER `userid`, ADD `last_name` VARCHAR(100) NULL DEFAULT NULL AFTER `first_name`;


UPDATE `order_description` SET `fieldName` = 'First Name' WHERE `order_description`.`fieldName` = 'Shipping First Name';
UPDATE `order_description` SET `fieldName` = 'Last Name' WHERE `order_description`.`fieldName` = 'Shipping Last Name';

ALTER TABLE `online_orders_fails` CHANGE `shipping_first_name` `first_name` VARCHAR(100) CHARACTER 
SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
 CHANGE `shipping_last_name` `last_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;



ALTER TABLE `neworder` CHANGE `picklist` `picklist` BOOLEAN NULL DEFAULT FALSE;
ALTER TABLE `neworder` CHANGE `packinglist` `packinglist` BOOLEAN NULL DEFAULT FALSE;
ALTER TABLE `neworder` CHANGE `prebol` `prebol` BOOLEAN NULL DEFAULT FALSE, 
    CHANGE `commercialinvoice` `commercialinvoice` BOOLEAN NULL DEFAULT FALSE, 
    CHANGE `shiptolabels` `shiptolabels` BOOLEAN NULL DEFAULT FALSE, 
    CHANGE `ediasn` `ediasn` BOOLEAN NULL DEFAULT FALSE, 
    CHANGE `cartoncontent` `cartoncontent` BOOLEAN NULL DEFAULT FALSE;

ALTER TABLE `online_orders` DROP `carrier`;
ALTER TABLE `online_orders` DROP `order_id`;
