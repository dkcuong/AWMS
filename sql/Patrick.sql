-- 12/29/2014 add a column in neworder table named "EcoOrReg" 

ALTER TABLE `neworder` ADD `EcoOrReg` VARCHAR(9) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER `canceldate`;

-- 12/29/2014 
-- add two columns in neworder table named "label" and "labelinfo" 
ALTER TABLE `neworder` ADD `label` VARCHAR(13) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER `ediasn`;
ALTER TABLE `neworder` ADD `labelinfo` VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ;


-- 12/29/2014
-- drop three columns in neworder table named "shiptolabel","eri" and "UFlabels" 
ALTER TABLE `neworder`
  DROP `shiptolabel`,
  DROP `eri`,
  DROP `UFlabels`;


-- 12/31/2014 
-- add a column in locations table named "locationNumber" for "pallet locations by numeric value"
ALTER TABLE `locations` ADD `locationNumber` INT(8) NOT NULL AFTER `displayName`;


-- 01/02/2015
ALTER TABLE `neworder` ADD `carrierName` VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER `numberofcarton`;
ALTER TABLE `neworder` ADD `physicalhours` INT(4) NOT NULL AFTER `NOpallets`, ADD `overtimehours` INT(4) NOT NULL AFTER `physicalhours`;
ALTER TABLE `neworder` ADD `payBy` VARCHAR(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER `carrierName`, ADD `payByInfo` FLOAT(10) NOT NULL AFTER `payBy`;
ALTER TABLE `neworder` CHANGE `payByInfo` `payByInfo` DECIMAL(10,2) NOT NULL;

ALTER TABLE `neworder` CHANGE `numberofcarton` `numberofcarton` VARCHAR(5) NULL DEFAULT NULL;
ALTER TABLE `neworder` CHANGE `numberofpiece` `numberofpiece` VARCHAR(10) NULL DEFAULT NULL;
ALTER TABLE `neworder` CHANGE `pickid` `pickid` VARCHAR(20) NULL DEFAULT NULL;
ALTER TABLE `neworder` CHANGE `samples` `samples` VARCHAR(10) NULL DEFAULT NULL;
ALTER TABLE `neworder` CHANGE `pickpack` `pickpack` VARCHAR(10) NULL DEFAULT NULL;
ALTER TABLE `neworder` CHANGE `payByInfo` `payByInfo` VARCHAR(10) NOT NULL;
ALTER TABLE `neworder` CHANGE `NOpallets` `NOpallets` VARCHAR(10) NULL DEFAULT NULL;
ALTER TABLE `neworder` CHANGE `cartonofcontent` `cartonofcontent` VARCHAR(10) NULL DEFAULT NULL, CHANGE `physicalhours` `physicalhours` VARCHAR(4) NOT NULL, CHANGE `overtimehours` `overtimehours` VARCHAR(4) NOT NULL, CHANGE `NOrushhours` `NOrushhours` VARCHAR(10) NULL DEFAULT NULL;



-- 01/07/2015
ALTER TABLE `workorder`  ADD `qtyHRmPTS` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRmPT` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRmApPTS` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRmApPT` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHPTSMt` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHApPTS` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHApPT` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyPPack` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHSort` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRep` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHSzMt` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRmSz` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRmApSz` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHApSz` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHCutSew` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHStMt` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRmSt` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRmApSt` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHApSt` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRmHng` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRmApH` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHHngMt` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHApHng` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHFold` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHSType15` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHSType14` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHSType13` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHSType12` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHSType1` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRmCLbl` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHPrApLbl` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHCrtVol` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHCrtUsed` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHCMark` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHCLabel` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHBLblMt` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRmBag` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRmApBag` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHBagMt` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHApBag` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHSpec` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHMSets` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHUProc` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHOTime` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHQC` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHLabour` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder`  ADD `qtyHRush` int(5) NOT NULL  AFTER `workordernumber`;
ALTER TABLE `workorder` CHANGE `qtyHSType12` `qtyHSType2` INT(5) NOT NULL, 
                        CHANGE `qtyHSType13` `qtyHSType3` INT(5) NOT NULL, 
                        CHANGE `qtyHSType14` `qtyHSType4` INT(5) NOT NULL, 
                        CHANGE `qtyHSType15` `qtyHSType5` INT(5) NOT NULL;
ALTER TABLE `workorder` CHANGE `qtyPPack` `qtyHPPack` INT(5) NOT NULL;
ALTER TABLE `workorder` ADD `qtyHApLbl` INT(5) NOT NULL AFTER `qtyHPrApLbl`;
ALTER TABLE `workorder` DROP `qtyHCrtVol`;
ALTER TABLE `workorder` CHANGE `qtyHCrtUsed` `qtyHCrtMt` INT(5) NOT NULL;



-- 1/12/2015 

ALTER TABLE `neworder` CHANGE `orderShipDate` `orderShipDate` DATE NULL DEFAULT NULL;

-- Drop fields that will not be used from workorder table
ALTER TABLE `workorder` DROP `hanggarment`;
ALTER TABLE `workorder`
    DROP `qtyhanggarment`,
    DROP `removegarment`,
    DROP `qtyremovegarment`;
ALTER TABLE `workorder` 
    DROP `applysize`, 
    DROP `qtyapplysize`, 
    DROP `removesize`, 
    DROP `qtyremovesize`, 
    DROP `polybagging`, 
    DROP `qtypolybagging`, 
    DROP `removepoly`, 
    DROP `qtyremovepoly`, 
    DROP `applytickets`, 
    DROP `qtyapplytickets`;
ALTER TABLE `workorder`
    DROP `removetickets`,
    DROP `qtyremovetickets`,
    DROP `seldathangers`,
    DROP `qtyseldathangers`,
    DROP `clienthangers`,
    DROP `qtyclienthangers`,
    DROP `seldatcollars`,
    DROP `qtyseldatcollars`,
    DROP `clientcollars`,
    DROP `qtyclientcollars`;
ALTER TABLE `workorder`
    DROP `numberofgarments`,
    DROP `seldattickets`,
    DROP `qtyseldattickets`,
    DROP `clienttickets`,
    DROP `qtyclienttickets`,
    DROP `stickersprice`,
    DROP `qtystickersprice`,
    DROP `stickerscolor`,
    DROP `qtystickerscolor`;
ALTER TABLE `workorder`
    DROP `whoprovidestickers`,
    DROP `seldatgeneratebarcode`,
    DROP `applycarton`,
    DROP `qtyapplycarton`,
    DROP `markcarton`,
    DROP `qtymarkcarton`,
    DROP `removeandapply`,
    DROP `qtyremoveandapply`,
    DROP `removesticker`,
    DROP `qtyremovesticker`;
ALTER TABLE `workorder`
    DROP `applysticker`,
    DROP `qtyapplysticker`,
    DROP `sku`,
    DROP `qtysku`,
    DROP `other`,
    DROP `qtyother`,
    DROP `sortitems`,
    DROP `qtysortitems`,
    DROP `depackitems`,
    DROP `qtydepackitems`;
ALTER TABLE `workorder`
    DROP `banditems`,
    DROP `qtybanditems`,
    DROP `repackitems`,
    DROP `qtyrepackitems`,
    DROP `assemble`,
    DROP `qtyassemble`,
    DROP `checkmarkings`,
    DROP `qtycheckmarkings`,
    DROP `checkcontents`,
    DROP `qtycheckcontents`,
    DROP `username`;

-- change varchar fields' collation to utf8_general_ci in workorder table
ALTER TABLE `workorder` 
    CHANGE `location` `location` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `requestdate` `requestdate` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `completedate` `completedate` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `ClientWorkOrderNumber` `ClientWorkOrderNumber` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `workordernumber` `workordernumber` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `workorder` 
    CHANGE `relatedtocustomer` `relatedtocustomer` VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `ordernumber` `ordernumber` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `shipdate` `shipdate` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `requestby` `requestby` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `workdetails` `workdetails` VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `status` `status` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;                        

-- change varchar fields' collation to utf8_general_ci in neworder table
ALTER TABLE `neworder` 
    CHANGE `customername` `customername` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `clientordernumber` `clientordernumber` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `customerordernumber` `customerordernumber` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `scanordernumber` `scanordernumber` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `scanworkorder` `scanworkorder` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `scanpicking` `scanpicking` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `location` `location` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `shipto` `shipto` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; 
ALTER TABLE `neworder` 
    CHANGE `shiptoaddress` `shiptoaddress` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `shiptocity` `shiptocity` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;   
ALTER TABLE `neworder` 
    CHANGE `numberofcarton` `numberofcarton` VARCHAR(5) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `carrierName` `carrierName` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
    CHANGE `payBy` `payBy` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
    CHANGE `payByInfo` `payByInfo` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
    CHANGE `numberofpiece` `numberofpiece` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `startshipdate` `startshipdate` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `neworder` 
    CHANGE `canceldate` `canceldate` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `EcoOrReg` `EcoOrReg` VARCHAR(9) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
    CHANGE `service` `service` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `picklist` `picklist` VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `packinglist` `packinglist` VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `prebol` `prebol` VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `neworder` 
    CHANGE `commercialinvoice` `commercialinvoice` VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `otherdocumentinform` `otherdocumentinform` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `shiptolabels` `shiptolabels` VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `ediasn` `ediasn` VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `label` `label` VARCHAR(13) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
    CHANGE `cartoncontent` `cartoncontent` VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `neworder` 
    CHANGE `otherlabelinform` `otherlabelinform` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `carrier` `carrier` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `carriernote` `carriernote` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
    CHANGE `ordernotes` `ordernotes` VARCHAR(400) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `dateentered` `dateentered` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `username` `username` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `neworder` 
    CHANGE `saleorderid` `saleorderid` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `pickid` `pickid` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `samples` `samples` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `pickpack` `pickpack` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `cartonofcontent` `cartonofcontent` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
    CHANGE `NOpallets` `NOpallets` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `physicalhours` `physicalhours` VARCHAR(4) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
    CHANGE `overtimehours` `overtimehours` VARCHAR(4) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
    CHANGE `NOrushhours` `NOrushhours` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `partyname` `partyname` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `partyaddress` `partyaddress` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `partycity` `partycity` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `specialinstruction` `specialinstruction` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `commodity` `commodity` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `Status` `Status` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'WMCI', 
    CHANGE `RoutedStatus` `RoutedStatus` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
    CHANGE `labelinfo` `labelinfo` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    CHANGE `holdStatus` `holdStatus` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- 01/13/2015 change varchar field default to NULL
    ALTER TABLE `neworder` 
        CHANGE `carrierName` `carrierName` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
        CHANGE `payBy` `payBy` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
        CHANGE `payByInfo` `payByInfo` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
        CHANGE `EcoOrReg` `EcoOrReg` VARCHAR(9) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
        CHANGE `label` `label` VARCHAR(13) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
        CHANGE `physicalhours` `physicalhours` VARCHAR(4) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
        CHANGE `overtimehours` `overtimehours` VARCHAR(4) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
        CHANGE `labelinfo` `labelinfo` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, 
        CHANGE `orderShipDate` `orderShipDate` DATE NULL DEFAULT NULL;

-- 01/30/2015 add a field in neworder for Bill of Lading
    ALTER TABLE `neworder` ADD `bol` VARCHAR(20) NULL DEFAULT NULL AFTER `scanworkorder`;

-- 02/03/2015 change displayName length to 20
    ALTER TABLE `locations` CHANGE `displayName` `displayName` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
-- 02/03/2015 drop field 'Status' in neworder    
    ALTER TABLE `neworder` DROP `Status`;

-- 02/06/2015 change bol field in neworder to bolNumber and collation to utf8_general_ci
    ALTER TABLE `neworder` CHANGE `bol` `bolNumber` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;


-- 02/18/2015  create order_type table and insert data,

    CREATE TABLE IF NOT EXISTS `order_types` (
      `id` int(3) NOT NULL AUTO_INCREMENT,
      `typeName` varchar(50) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

    INSERT INTO `order_types` (`id`, `typeName`) VALUES 
      (NULL, 'Casepack DC'), 
      (NULL, 'Casepack DTS');
    INSERT INTO `order_types` (`id`, `typeName`) VALUES 
      (NULL, 'Casepack Cross Dock'), 
      (NULL, 'Pick & Pack DC'),
      (NULL, 'Pick & Pack DTS'),
      (NULL, 'Pick & Pack Cross Dock'),
      (NULL, 'Consumer Pick & Pack'), 
      (NULL, 'Consumer Fast Pack'),
      (NULL, 'Destuff Container To Orders');

-- 02/18/2015 add a field 'type' to neworder table
    ALTER TABLE `neworder` ADD `type` INT(3) NULL DEFAULT NULL AFTER `canceldate`;

-- 02/18/2015 add a field 'isErr' to online_orders table
    ALTER TABLE `online_orders` ADD `is_err` INT(2) NOT NULL DEFAULT '30' ;    

-- 02/24/2015 
    INSERT INTO `statuses` (`id`, `category`, `displayName`, `shortName`) VALUES 
        (NULL, 'online_orders', 'Enough Inventory', ''), 
        (NULL, 'online_orders', 'Lack of Inventory', '');
 
    UPDATE `statuses` SET `shortName` = 'ENIN' 
        WHERE `displayName` = 'Enough Inventory'; 
    UPDATE `statuses` SET `shortName` = 'LOIN' 
        WHERE `displayName` = 'Lack of Inventory';

-- 02/27/2015 
    ALTER TABLE `online_orders` DROP `is_err`;
    ALTER TABLE `neworder` ADD `is_err` INT(2) NULL DEFAULT NULL ;

-- 03/08/2015
    UPDATE `statuses` SET `displayName` = 'No Error' WHERE `displayName` = 'Enough Inventory';
    UPDATE `statuses` SET `displayName` = 'Error' WHERE `displayName` = 'Lack of Inventory';

-- 03/11/2015
    ALTER TABLE `neworder` CHANGE `is_err` `isError` INT(2) NULL DEFAULT NULL;

-- 03/27/2015
    INSERT INTO `paid_by` (`id`, `name`) VALUES (4, 'collect');

-- 04/24/2015
    UPDATE `warehouses` w JOIN `company_address` ca SET `locationID` = ca.id WHERE `companyName`= 'NJ Docks Corner Warehouse' AND w.shortName = 'NJ';
    UPDATE `warehouses` w JOIN `company_address` ca SET `locationID` = ca.id WHERE `companyName`= 'Los Angeles Warehouse' AND w.shortName = 'LA';
    UPDATE `warehouses` w JOIN `company_address` ca SET `locationID` = ca.id WHERE `companyName`= 'Toronto Warehouse' AND w.shortName = 'TO';
     
-- 04/27/2015
    CREATE TABLE IF NOT EXISTS `status_boolean` ( 
      `id` TINYINT(1) NOT NULL AUTO_INCREMENT, 
      `boolean` VARCHAR(3) NULL DEFAULT NULL,
      PRIMARY KEY (`id`)  
    ) ENGINE = InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

    INSERT INTO `status_boolean` (`id`, `boolean`) VALUES (NULL, 'YES'), (NULL, 'NO');

-- 04/28/2015
    ALTER TABLE `workorder` CHANGE `relatedtocustomer` `relatedtocustomer` TINYINT(1) NULL DEFAULT NULL;




