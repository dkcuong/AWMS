-- ---------------------------------------------------------
--      wms:      ONLY commands after 7/10/2016 here
--      _default: ONLY commands after 7/10/2016 here
-- ---------------------------------------------------------

-- Vadzim
-- 7/10/2016
-- updating crons tasks table server field to "seldatawms.com"

UPDATE    crons.tasks
SET       server = 'seldatawms.com';

-- adding palletLogSummary task to crons

INSERT INTO crons.tasks (displayName, `server`, site, app, `class`, `method`, frequency) VALUES
('Update Pallet Logs Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'palletLogSummary', 60);

-- adding RFA deal site

INSERT INTO deal_sites (displayName, imageName) VALUES
('RFA', 'RFA');

-- Vadzim
-- 7/13/2016
-- add Outbound Orders link to Administration submenu

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Outbound Orders" AS displayName,
          maxOrder + 1 AS displayOrder,
          "outboundOrders" AS hiddenName,
          "outbound" AS `class`,
          "orders" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = "administration"
    AND       sm.active
        LIMIT 1
) m
WHERE     sm.hiddenName = "administration"
AND       active
LIMIT 1;

INSERT INTO submenu_pages (pageID, subMenuID)
SELECT
    p.id AS pageID,
    m.subMenuID
FROM pages p
JOIN (
  SELECT    sm.id AS subMenuID
  FROM      subMenus sm
  WHERE     sm.hiddenName = 'administration'
) m
WHERE p.hiddenName = 'outboundOrders';

-- add leadig zeros to ord_num field in inv_his_ord_prc table

ALTER TABLE inv_his_ord_prc CHANGE ord_num ord_num BIGINT(10) UNSIGNED ZEROFILL NOT NULL;

-- add summary table for outbound orderes report

CREATE TABLE outbound_sum (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    log_id INT(8) NOT NULL,
    ord_id BIGINT(10) NOT NULL,
    chk_in_dt DATETIME NULL DEFAULT NULL,
    chk_out_dt DATETIME NULL DEFAULT NULL,
    ship_dt DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY log_id (log_id),
    UNIQUE ord_id (ord_id)
) ENGINE=InnoDB;

INSERT INTO crons.tasks (displayName, `server`, site, app, `class`, `method`, frequency) VALUES
('Update Outbound Orders Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'outboundOrdersSummary', 5);

-- Raji
-- 07/13/2016

-- Increase the field width
ALTER TABLE `invoice_dtls` CHANGE `chg_cd_uom` `chg_cd_uom` VARCHAR(25) NULL DEFAULT NULL;

-- update the value in invoice_dtls
UPDATE `invoice_dtls`
SET chg_cd_uom = "MONTHLY_SMALL_CARTON"
WHERE chg_cd_uom = "MONTHLY_SM"

UPDATE `invoice_dtls`
SET chg_cd_uom = "MONTHLY_MEDIUM_CARTON"
WHERE chg_cd_uom = "MONTHLY_ME"

UPDATE `invoice_dtls`
SET chg_cd_uom = "MONTHLY_LARGE_CARTON"
WHERE chg_cd_uom = "MONTHLY_LA"

-- Vadzim
-- 7/15/2016

-- adding dcUserID field to neworder table

ALTER TABLE neworder ADD dcUserID INT(4) NOT NULL AFTER saleorderid;

ALTER TABLE neworder ADD INDEX(dcUserID);

-- Vadzim
-- 7/20/2016
-- add Actual Receivings link to Administration submenu

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Actual Receiving" AS displayName,
          maxOrder + 1 AS displayOrder,
          "actualReceiving" AS hiddenName,
          "receiving" AS `class`,
          "actual" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = "administration"
    AND       sm.active
        LIMIT 1
) m
WHERE     sm.hiddenName = "administration"
AND       active
LIMIT 1;

INSERT INTO submenu_pages (pageID, subMenuID)
SELECT
    p.id AS pageID,
    m.subMenuID
FROM pages p
JOIN (
  SELECT    sm.id AS subMenuID
  FROM      subMenus sm
  WHERE     sm.hiddenName = 'administration'
) m
WHERE p.hiddenName = 'actualReceiving';

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Receiving Inspection Report" AS displayName,
          maxOrder + 1 AS displayOrder,
          "receivingInspectionReport" AS hiddenName,
          "receiving" AS `class`,
          "inspectionReport" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = "administration"
    AND       sm.active
        LIMIT 1
) m
WHERE     sm.hiddenName = "administration"
AND       active
LIMIT 1;

INSERT INTO submenu_pages (pageID, subMenuID)
SELECT
    p.id AS pageID,
    m.subMenuID
FROM pages p
JOIN (
  SELECT    sm.id AS subMenuID
  FROM      subMenus sm
  WHERE     sm.hiddenName = 'administration'
) m
WHERE p.hiddenName = 'receivingInspectionReport';

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Receiving Container Report" AS displayName,
          maxOrder + 1 AS displayOrder,
          "receivingContainerReport" AS hiddenName,
          "receiving" AS `class`,
          "containerReport" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = "administration"
    AND       sm.active
        LIMIT 1
) m
WHERE     sm.hiddenName = "administration"
AND       active
LIMIT 1;

INSERT INTO submenu_pages (pageID, subMenuID)
SELECT
    p.id AS pageID,
    m.subMenuID
FROM pages p
JOIN (
  SELECT    sm.id AS subMenuID
  FROM      subMenus sm
  WHERE     sm.hiddenName = 'administration'
) m
WHERE p.hiddenName = 'receivingContainerReport';

-- Vadzim
-- 7/20/2016
-- adding setDate field to tallies table

ALTER TABLE tallies ADD COLUMN setDate DATETIME NULL DEFAULT CURRENT_TIMESTAMP;

UPDATE tallies SET setDate = NULL;

-- Vadzim
-- 7/23/2016
-- addind Picking Check Out and Shipping Check In data to Outbound Orders report

ALTER TABLE outbound_sum ADD COLUMN pick_out_dt DATE NULL DEFAULT NULL AFTER chk_out_dt;
ALTER TABLE outbound_sum ADD COLUMN ship_in_dt DATE NULL DEFAULT NULL AFTER pick_out_dt;

-- changing existing DATATIME fields type to DATE

ALTER TABLE outbound_sum CHANGE chk_in_dt chk_in_dt DATE NULL DEFAULT NULL;
ALTER TABLE outbound_sum CHANGE chk_out_dt chk_out_dt DATE NULL DEFAULT NULL;
ALTER TABLE outbound_sum CHANGE ship_dt ship_out_dt DATE NULL DEFAULT NULL;

-- Vadzim
-- 7/27/2016
-- add Style History link to Administration submenu

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Style History" AS displayName,
          maxOrder + 1 AS displayOrder,
          "styleHistory" AS hiddenName,
          "inventory" AS `class`,
          "styleHistory" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = "administration"
    AND       sm.active
        LIMIT 1
) m
WHERE     sm.hiddenName = "administration"
AND       active
LIMIT 1;

INSERT INTO submenu_pages (pageID, subMenuID)
SELECT
    p.id AS pageID,
    m.subMenuID
FROM pages p
JOIN (
  SELECT    sm.id AS subMenuID
  FROM      subMenus sm
  WHERE     sm.hiddenName = 'administration'
) m
WHERE p.hiddenName = 'styleHistory';

-- add summary table for Style History report

CREATE TABLE style_his_sum (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    log_id INT(8) NOT NULL,
    carton_id BIGINT(10) NOT NULL,
    cust_id INT(5) NOT NULL,
    upc_id BIGINT(10) NOT NULL,
    ucc128 VARCHAR(20) NOT NULL,
    rcv_dt DATE NULL DEFAULT NULL,
    rack_dt DATE NULL DEFAULT NULL,
    alloc_dt DATE NULL DEFAULT NULL,
    alloc_ord BIGINT(10) UNSIGNED ZEROFILL NULL DEFAULT NULL,
    ship_dt DATE NULL DEFAULT NULL,
    ship_ord BIGINT(10) UNSIGNED ZEROFILL NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY log_id (log_id),
    KEY cust_id (cust_id),
    KEY upc_id (upc_id),
    KEY alloc_ord (alloc_ord),
    KEY ship_ord (ship_ord),
    UNIQUE carton_id (carton_id)
) ENGINE=InnoDB;

INSERT INTO crons.tasks (displayName, `server`, site, app, `class`, `method`, frequency) VALUES
('Update Style History Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'styleHistorySummary', 5);

-- adding a paramter that allows add notes

INSERT INTO page_params (pageID, name, value, active)
SELECT    id AS pageID,
          "type" AS name,
          "received" AS value,
          1 AS active
FROM      pages
WHERE     hiddenName = "searchContainerSKUs";


-- Raji
-- 08/03/2016

-- Add UPS and FED-EX charge codes with UOM "CARTON" under ORD_PROC
INSERT INTO charge_cd_mstr (chg_cd, chg_cd_des, chg_cd_type, chg_cd_uom)
VALUES
('UPS', 'UPS', 'ORD_PROC', 'CARTON'),
('FED-EX', 'FED-EX', 'ORD_PROC', 'CARTON');


-- Add "Sort Cartons"  charge code
INSERT INTO charge_cd_mstr (chg_cd, chg_cd_des, chg_cd_type, chg_cd_uom)
VALUES
('SORT-CART', 'SORT CARTONS', 'ORD_PROC', 'UNIT');

-- Raji
-- 08/07/2016
-- table sum_last_ctn_sts

CREATE TABLE sum_last_ctn_sts (
    carton_id INT(10) NOT NULL ,
    batch_id INT(8) NOT NULL ,
    status_id INT(2) NOT NULL ,
    mStatus_id INT(2) NOT NULL ,
    last_log_id INT(8) NULL DEFAULT NULL ,
    last_his_id INT(8) NULL DEFAULT NULL ,
    last_update_time DATETIME NOT NULL ,
    PRIMARY KEY (carton_id),
    INDEX (batch_id),
    INDEX (status_id),
    INDEX (mstatus_id),
    INDEX (last_log_id),
    INDEX (last_his_id),
    INDEX (last_update_time)
) ENGINE=InnoDB;


-- Add cust_id to "sum_last_ctn_sts" table

ALTER TABLE sum_last_ctn_sts
ADD cust_id INT(5) NOT NULL AFTER batch_id,
ADD INDEX (cust_id);


-- Add the groups for the report

 INSERT INTO `groups`
(`groupName`, `hiddenName`, `description`, `active`)
 VALUES
 ('LA Group', 'LAGroupAdmin', 'Aging LA report for Carton and Order', '1'),
 ('NJ Group', 'NJGroupAdmin', 'Aging NJ report for Carton and Order', '1'),
 ('TO Group', 'TOGroupAdmin', 'Aging TO report for Carton and Order', '1'),
 ('FA Group', 'FAGroupAdmin', 'Aging FA report for Carton and Order', '1');

-- Vadzim
-- 8/8/2016
-- add app firld to pages table

ALTER TABLE `pages` ADD `app` VARCHAR(15) NULL DEFAULT NULL AFTER clientAccess;

-- add TMS Dispatch List to wms submenu

INSERT INTO `pages` (
    `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `app`
)
SELECT    "TMS Dispatch List" AS displayName,
          minOrder*0.9 AS displayOrder,
          "tmsList" AS hiddenName,
          "containers" AS `class`,
          "remote" AS `method`,
          0 AS red,
          "tms" AS `app`
FROM      subMenus sm
JOIN      (
    SELECT    MIN(p.displayOrder) AS minOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = "wms"
    AND       sm.active
        LIMIT 1
) m
WHERE     sm.hiddenName = "wms"
AND       active
LIMIT 1;

INSERT INTO submenu_pages (pageID, subMenuID)
SELECT
    p.id AS pageID,
    m.subMenuID
FROM pages p
JOIN (
  SELECT    sm.id AS subMenuID
  FROM      subMenus sm
  WHERE     sm.hiddenName = 'wms'
) m
WHERE p.hiddenName = 'tmsList';

-- Raji
-- 08/08/2016

-- Add shortname for vendors
ALTER TABLE `vendors` ADD `clientCode` VARCHAR(3) NOT NULL AFTER `vendorName`;

-- Drop Index 'venodrName' and recreate it
ALTER TABLE vendors DROP INDEX vendorName;

ALTER TABLE `vendors` ADD UNIQUE `vendorName` (`vendorName`, `clientCode`, `warehouseID`);

-- populate vendor clientCode
UPDATE vendors SET clientCode = 'AW'  WHERE vendorName = 'ACCUTIME WATCH' ;
UPDATE vendors SET clientCode = 'AF'  WHERE vendorName = 'ACTIVE FOOTWEAR';
UPDATE vendors SET clientCode = 'AS'  WHERE vendorName = 'Andrew Steven';
UPDATE vendors SET clientCode = 'AH'  WHERE vendorName = 'Azzure Home';
UPDATE vendors SET clientCode = 'BH'  WHERE vendorName = 'B&H Apparel ';
UPDATE vendors SET clientCode = 'AM'  WHERE vendorName = 'Basic Resources Americo';
UPDATE vendors SET clientCode = 'BBC'  WHERE vendorName = 'BBC';
UPDATE vendors SET clientCode = 'BA'  WHERE vendorName = 'BBC Apparel';
UPDATE vendors SET clientCode = 'BL'  WHERE vendorName = 'Bliss';
UPDATE vendors SET clientCode = 'BU'  WHERE vendorName = 'BU Seldat INV';
UPDATE vendors SET clientCode = 'CL'  WHERE vendorName = 'C-Life';
UPDATE vendors SET clientCode = 'CS'  WHERE vendorName = 'CARLSON';
UPDATE vendors SET clientCode = 'CI'  WHERE vendorName = 'Carlson_INACTIVE_DUP';
UPDATE vendors SET clientCode = 'CA'  WHERE vendorName = 'Cayre';
UPDATE vendors SET clientCode = 'CD'  WHERE vendorName = 'Celebrity Design Group';
UPDATE vendors SET clientCode = 'CN'  WHERE vendorName = 'Cloud Nine';
UPDATE vendors SET clientCode = 'CT'  WHERE vendorName = 'Concept In Time';
UPDATE vendors SET clientCode = 'NY'  WHERE vendorName = 'Concept NYC';
UPDATE vendors SET clientCode = 'CT'  WHERE vendorName = 'Concepts in Time';
UPDATE vendors SET clientCode = 'CO'  WHERE vendorName = 'CONCEPT_ONE';
UPDATE vendors SET clientCode = 'CG'  WHERE vendorName = 'cougar';
UPDATE vendors SET clientCode = 'CF'  WHERE vendorName = 'CRAFTWELL';
UPDATE vendors SET clientCode = 'CK'  WHERE vendorName = 'Cyber Kids';
UPDATE vendors SET clientCode = 'DNU'  WHERE vendorName = 'DNU';
UPDATE vendors SET clientCode = 'DM'  WHERE vendorName = 'DONNAMAX';
UPDATE vendors SET clientCode = 'DU'  WHERE vendorName = 'DONOTUSE';
UPDATE vendors SET clientCode = 'EL'  WHERE vendorName = 'Eccolo Ltd';
UPDATE vendors SET clientCode = 'EL'  WHERE vendorName = 'Eccolo_Ltd';
UPDATE vendors SET clientCode = 'EF'  WHERE vendorName = 'Eco Loofah';
UPDATE vendors SET clientCode = 'EG'  WHERE vendorName = 'EDGEU';
UPDATE vendors SET clientCode = 'EB'  WHERE vendorName = 'Elite Brands';
UPDATE vendors SET clientCode = 'EC'  WHERE vendorName = 'Extreme Concepts';
UPDATE vendors SET clientCode = 'FS'  WHERE vendorName = 'FA Seldat INV';
UPDATE vendors SET clientCode = 'GP'  WHERE vendorName = 'genius pack';
UPDATE vendors SET clientCode = 'GA'  WHERE vendorName = 'Global Accessories';
UPDATE vendors SET clientCode = 'GL'  WHERE vendorName = 'Go Life Works';
UPDATE vendors SET clientCode = 'GC'  WHERE vendorName = 'gottex canada';
UPDATE vendors SET clientCode = 'GM'  WHERE vendorName = 'GOT_MILK';
UPDATE vendors SET clientCode = 'GS'  WHERE vendorName = 'GOT_SNAKS';
UPDATE vendors SET clientCode = 'HG'  WHERE vendorName = 'H Group';
UPDATE vendors SET clientCode = 'IA'  WHERE vendorName = 'I Apparel';
UPDATE vendors SET clientCode = 'IC'  WHERE vendorName = 'iCANDY';
UPDATE vendors SET clientCode = 'JF'  WHERE vendorName = 'Jesco_Footwear';
UPDATE vendors SET clientCode = 'JS'  WHERE vendorName = 'Jesse';
UPDATE vendors SET clientCode = 'JO'  WHERE vendorName = 'Just One';
UPDATE vendors SET clientCode = 'KI'  WHERE vendorName = 'Kenedy Intl';
UPDATE vendors SET clientCode = 'KA'  WHERE vendorName = 'Kids Apparel Club';
UPDATE vendors SET clientCode = 'LT'  WHERE vendorName = 'La Collina Toscana';
UPDATE vendors SET clientCode = 'LS'  WHERE vendorName = 'LA Seldat INV';
UPDATE vendors SET clientCode = 'LF'  WHERE vendorName = 'Lane Crawford';
UPDATE vendors SET clientCode = 'LC'  WHERE vendorName = 'LC Apparel';
UPDATE vendors SET clientCode = 'LC'  WHERE vendorName = 'lcapparel';
UPDATE vendors SET clientCode = 'LC'  WHERE vendorName = 'LC_Apparel';
UPDATE vendors SET clientCode = 'LL'  WHERE vendorName = 'Lot Less';
UPDATE vendors SET clientCode = 'MI'  WHERE vendorName = 'Mamiye Imports';
UPDATE vendors SET clientCode = 'MR'  WHERE vendorName = 'Maverick ';
UPDATE vendors SET clientCode = 'ML'  WHERE vendorName = 'Milen';
UPDATE vendors SET clientCode = 'MH'  WHERE vendorName = 'Morgan Home';
UPDATE vendors SET clientCode = 'OR'  WHERE vendorName = 'Orva';
UPDATE vendors SET clientCode = 'PL'  WHERE vendorName = 'PREMIUM LOUNGE';
UPDATE vendors SET clientCode = 'RD'  WHERE vendorName = 'Red Daisy';
UPDATE vendors SET clientCode = 'RG'  WHERE vendorName = 'RFA GROUP';
UPDATE vendors SET clientCode = 'RF'  WHERE vendorName = 'Royal Footwear';
UPDATE vendors SET clientCode = 'RE'  WHERE vendorName = 'Rugged Equipment';
UPDATE vendors SET clientCode = 'SA'  WHERE vendorName = 'SARAMAX APPAREL';
UPDATE vendors SET clientCode = 'SA'  WHERE vendorName = 'Saramaxx Apparel';
UPDATE vendors SET clientCode = 'SV'  WHERE vendorName = 'SAVANTE APPAREL';
UPDATE vendors SET clientCode = 'SH'  WHERE vendorName = 'Shalam';
UPDATE vendors SET clientCode = 'SI'  WHERE vendorName = 'Shalam Imports';
UPDATE vendors SET clientCode = 'SN'  WHERE vendorName = 'SHALOM INTERNATIONAL';
UPDATE vendors SET clientCode = 'SL'  WHERE vendorName = 'Sindrella';
UPDATE vendors SET clientCode = 'ST'  WHERE vendorName = 'Southern Telecom';
UPDATE vendors SET clientCode = 'SR'  WHERE vendorName = 'Star Ride';
UPDATE vendors SET clientCode = 'SK'  WHERE vendorName = 'STARKID';
UPDATE vendors SET clientCode = 'SB'  WHERE vendorName = 'Sweater Brands';
UPDATE vendors SET clientCode = 'TC'  WHERE vendorName = 'Terracycle';
UPDATE vendors SET clientCode = 'TO'  WHERE vendorName = 'TestOnly';
UPDATE vendors SET clientCode = 'KK'  WHERE vendorName = 'The Kind Kitchen';
UPDATE vendors SET clientCode = 'TS'  WHERE vendorName = 'TOP_SHELF';
UPDATE vendors SET clientCode = 'TL'  WHERE vendorName = 'Trade Lines';
UPDATE vendors SET clientCode = 'TZ'  WHERE vendorName = 'TRENDALIZE';
UPDATE vendors SET clientCode = 'UL'  WHERE vendorName = 'USA Legwear Americo';
UPDATE vendors SET clientCode = 'UU'  WHERE vendorName = 'USA Underwear Americo';
UPDATE vendors SET clientCode = 'VA'  WHERE vendorName = 'VAULT';
UPDATE vendors SET clientCode = 'VG'  WHERE vendorName = 'VG GLOBAL';
UPDATE vendors SET clientCode = 'WS'  WHERE vendorName = 'Wanted Shoes';
UPDATE vendors SET clientCode = 'WA'  WHERE vendorName = 'WAPPLIANCE';
UPDATE vendors SET clientCode = 'WP'  WHERE vendorName = 'Weather_Proof';
UPDATE vendors SET clientCode = 'WE'  WHERE vendorName = 'Web E Shops';
UPDATE vendors SET clientCode = 'WH'  WHERE vendorName = 'WhiSpering Smith';
UPDATE vendors SET clientCode = 'YL'  WHERE vendorName = 'Youngland';


-- Raji
-- 08/09/2016

-- Update the following charge codes to "delete" status

UPDATE    charge_cd_mstr c
JOIN      seldat_users.info u
SET       update_by = u.id,
          update_dt = NOW(),
          status = 'd'
WHERE     chg_cd_uom = 'UNIT'
AND       chg_cd IN
(
    'SEP-SEG-AFTER-5-SKU',
    'STRAP-SMAL-APPL',
    'STRAP-LARGE-APPL',
    'CART-LABEL-SHIP-TO-LABEL',
    'UPS-FED-EX',
    'UCC128'
)
AND      u.userName = 'vmechnik';


-- make neworder table dcUserID field accept NULL values

ALTER TABLE neworder CHANGE dcUserID dcUserID INT(4) NULL DEFAULT NULL;

-- adding crons for Last Carton Status Report and Aging Reports

INSERT INTO crons.tasks
(displayName, `server`, site, app, `class`, `method`, frequency)
VALUES
('Last Carton Status Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'lastCartonLogs', 60),
('Aging FA Carton Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'agingFACartonSummary', 604800),
('Aging FA Order Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'agingFAOrderSummary', 604800),
('Aging LA Carton Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'agingLACartonSummary', 604800),
('Aging LA Order Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'agingLAOrderSummary', 604800),
('Aging NJ Carton Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'agingNJCartonSummary', 604800),
('Aging NJ Order Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'agingNJOrderSummary', 604800),
('Aging TO Carton Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'agingTOCartonSummary', 604800),
('Aging TO Order Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'agingTOOrderSummary', 604800);

-- Vadzim
-- 8/8/2016
-- adding CURRENT_TIMESTAMPs to Cycle Count tables

ALTER TABLE cycle_count CHANGE created created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE cycle_count CHANGE updated updated DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE count_items CHANGE created_dt created_dt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE count_items CHANGE updated_dt updated_dt DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE locked_cartons CHANGE created_at created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE discrepancy_cartons CHANGE created_at created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE discrepancy_cartons CHANGE updated_at updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Duy Nguyen
-- 8/11/2016
-- Add a field (logTime) that will be updated when an RC Log is submitted.

ALTER TABLE  `tallies` ADD  `logTime` DATETIME NULL AFTER  `setDate` ;

-- Tri Le
-- 8/11/2016

-- Add dropDown field to info table
ALTER TABLE seldat_users.info ADD `dropDown` TINYINT(1) NOT NULL DEFAULT '1' AFTER `active`;

-- Update dropDown equal zero if user inactive.
UPDATE seldat_users.info SET dropDown = 0 WHERE active = 0;

-- Vadzim
-- 8/11/2016
-- adding Conatiner Name field to style_his_sum table, replace upc_id field with
-- sku field

ALTER TABLE style_his_sum ADD `name` VARCHAR(50) NOT NULL AFTER carton_id;
ALTER TABLE style_his_sum ADD sku VARCHAR(65) NOT NULL AFTER cust_id;

UPDATE    style_his_sum shs
JOIN      inventory_cartons ca ON ca.id = shs.carton_id
JOIN      inventory_batches b ON b.id = ca.batchID
JOIN      inventory_containers co ON co.recNum = b.recNum
JOIN      upcs u ON u.id = b.upcID
SET       shs.`name` = co.`name`,
          shs.sku = u.sku;

ALTER TABLE style_his_sum DROP upc_id;

-- Raji
-- 08/16/2016

-- Add logId field to Order summary tables

ALTER TABLE `ord_sum_ctn`
ADD `log_id` INT(8) NOT NULL AFTER `order_nbr`,
ADD INDEX (`log_id`);

ALTER TABLE `ord_sum_lbl`
ADD `log_id` INT(8) NOT NULL AFTER `order_nbr`,
ADD INDEX (`log_id`);

ALTER TABLE `ord_sum_ord`
ADD `log_id` INT(8) NOT NULL AFTER `order_nbr`,
ADD INDEX (`log_id`);

ALTER TABLE `ord_sum_pcs`
ADD `log_id` INT(8) NOT NULL AFTER `order_nbr`,
ADD INDEX (`log_id`);

ALTER TABLE `ord_sum_plt`
ADD `log_id` INT(8) NOT NULL AFTER `order_nbr`,
ADD INDEX (`log_id`);

ALTER TABLE `ord_sum_vol`
ADD `log_id` INT(8) NOT NULL AFTER `order_nbr`,
ADD INDEX (`log_id`);

ALTER TABLE `ord_sum_wo`
ADD `log_id` INT(8) NOT NULL AFTER `cust_id`,
ADD INDEX (`log_id`);

-- Modify the order_nbr field for Order summary tables
ALTER TABLE ord_sum_ctn CHANGE order_nbr order_nbr BIGINT(10) UNSIGNED ZEROFILL NOT NULL;
ALTER TABLE ord_sum_lbl CHANGE order_nbr order_nbr BIGINT(10) UNSIGNED ZEROFILL NOT NULL;
ALTER TABLE ord_sum_ord CHANGE order_nbr order_nbr BIGINT(10) UNSIGNED ZEROFILL NOT NULL;
ALTER TABLE ord_sum_pcs CHANGE order_nbr order_nbr BIGINT(10) UNSIGNED ZEROFILL NOT NULL;
ALTER TABLE ord_sum_plt CHANGE order_nbr order_nbr BIGINT(10) UNSIGNED ZEROFILL NOT NULL;
ALTER TABLE ord_sum_vol CHANGE order_nbr order_nbr BIGINT(10) UNSIGNED ZEROFILL NOT NULL;
ALTER TABLE ord_sum_wo  CHANGE order_nbr order_nbr BIGINT(10) UNSIGNED ZEROFILL NOT NULL;

-- Modify the scan_ord_nbr field
ALTER TABLE wrk_hrs_ord_prc CHANGE scan_ord_nbr scan_ord_nbr BIGINT(10) UNSIGNED ZEROFILL NOT NULL;

-- Drop primary key and add for Order summary tables
ALTER TABLE ord_sum_ctn DROP PRIMARY KEY;
ALTER TABLE ord_sum_ctn ADD PRIMARY KEY (order_nbr);

ALTER TABLE ord_sum_lbl DROP PRIMARY KEY;
ALTER TABLE ord_sum_lbl ADD PRIMARY KEY (order_nbr);

ALTER TABLE ord_sum_ord DROP PRIMARY KEY;
ALTER TABLE ord_sum_ord ADD PRIMARY KEY (order_nbr);


ALTER TABLE ord_sum_pcs DROP PRIMARY KEY;
ALTER TABLE ord_sum_pcs ADD PRIMARY KEY (order_nbr);

ALTER TABLE ord_sum_plt DROP PRIMARY KEY;
ALTER TABLE ord_sum_plt ADD PRIMARY KEY (order_nbr);

ALTER TABLE ord_sum_vol DROP PRIMARY KEY;
ALTER TABLE ord_sum_vol ADD PRIMARY KEY (order_nbr);

ALTER TABLE ord_sum_wo DROP PRIMARY KEY;
ALTER TABLE ord_sum_wo ADD PRIMARY KEY (order_nbr, chg_cd);


-- Add last_ctn_log_id field to ctn_sum table - Storage

ALTER TABLE `ctn_sum`
ADD `last_ctn_log_id` INT(8) NOT NULL AFTER `rcv_dt`,
ADD INDEX (`last_ctn_log_id`);

ALTER TABLE `ctn_sum_mk`
ADD `last_ctn_log_id` INT(8) NOT NULL AFTER `rcv_dt`,
ADD INDEX (`last_ctn_log_id`);

-- Add log_id to Receiving summary tables

ALTER TABLE `rcv_sum_ctn`
ADD `log_id` INT(8) NOT NULL AFTER `cust_id`,
ADD INDEX (`log_id`);

ALTER TABLE `rcv_sum_cntr`
ADD `log_id` INT(8) NOT NULL AFTER `cust_id`,
ADD INDEX (`log_id`);

ALTER TABLE `rcv_sum_pcs`
ADD `log_id` INT(8) NOT NULL AFTER `cust_id`,
ADD INDEX (`log_id`);

ALTER TABLE `rcv_sum_plt`
ADD `log_id` INT(8) NOT NULL AFTER `cust_id`,
ADD INDEX (`log_id`);

ALTER TABLE `rcv_sum_vol`
ADD `log_id` INT(8) NOT NULL AFTER `cust_id`,
ADD INDEX (`log_id`);

-- Raji
-- 8/17/2016
-- deactivating UPS/FEDEX carge code

UPDATE    charge_cd_mstr c
JOIN      seldat_users.info u
SET       update_by = u.id,
          update_dt = NOW(),
          `status` = 'd'
WHERE     chg_cd_uom = 'CARTON'
AND       chg_cd = 'UPS/FEDEX'
AND       u.userName = 'vmechnik';


-- Raji
-- 08/17/2016

-- Create new order summary table ord_ship_sum

CREATE TABLE `ord_ship_sum` (
  `cust_id` INT(5) NOT NULL,
  `order_nbr` BIGINT(10) UNSIGNED ZEROFILL NOT NULL,
  `carrierType` varchar(50) NOT NULL,
  `log_id` INT(8) NOT NULL,
  `dt` DATE NOT NULL,
   PRIMARY KEY (`order_nbr`,`carrierType`),
   KEY `dt` (`dt`),
   KEY `cust_id` (`cust_id`),
   KEY `log_id` (`log_id`),
   KEY `carrierType` (`carrierType`)
) ENGINE=InnoDB;

-- Update Fedex and UPS charge code UOM and Description

UPDATE  charge_cd_mstr c
JOIN    seldat_users.info u
SET     update_by = u.id,
        update_dt = NOW(),
        chg_cd_uom = 'UPS_CARTON',
	chg_cd_des = 'UPS CARTON FEE'
WHERE   chg_cd_uom = 'CARTON'
AND     chg_cd = 'UPS'
AND     u.userName = 'vmechnik';


UPDATE  charge_cd_mstr c
JOIN    seldat_users.info u
SET     update_by = u.id,
        update_dt = NOW(),
        chg_cd_uom = 'FEDEX_CARTON',
        chg_cd_des = 'FEDEX CARTON FEE'
WHERE   chg_cd_uom = 'CARTON'
AND     chg_cd = 'FED-EX'
AND     u.userName = 'vmechnik';

-- Raji
-- 08-22-2016

-- update cron task for invoicing summary daily cron
INSERT INTO crons.tasks (displayName, `server`, site, app, `class`, `method`, frequency) VALUES
('Invoice Receiving Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'invoiceReceivingSummary', 30),
('Invoice Order Processing Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'invoiceOrderProcessingSummary', 30),
('Invoice Storage Summary', 'seldatawms.com', '', 'wms', 'appCrons', 'invoiceStorageSummary', 30),
('Invoice Storage Update Carton', 'seldatawms.com', '', 'wms', 'appCrons', 'invoiceStorageUpdateCarton', 10080);


-- Raji
-- 08/23/2016

-- Add val field to ord_ship_sum
ALTER TABLE `ord_ship_sum` ADD `val` FLOAT NOT NULL AFTER `dt`;


-- Create new order summary table ord_sum_cncl

CREATE TABLE `ord_sum_cncl` (
  `cust_id` INT(5) NOT NULL,
  `order_nbr` BIGINT(10) UNSIGNED ZEROFILL NOT NULL,
  `log_id` INT(8) NOT NULL,
  `dt` DATE NOT NULL,
  `val` FLOAT NOT NULL,
   PRIMARY KEY (`order_nbr`),
   KEY `dt` (`dt`),
   KEY `cust_id` (`cust_id`),
   KEY `log_id` (`log_id`)
) ENGINE=InnoDB;

-- Tri Le
-- 08-26-2016

-- Add field 'source_loc_id' to transfer_items table that will signify a location a carton was moved from
ALTER TABLE `transfer_items` ADD `source_loc_id` INT(7) NULL AFTER `locationID`;

-- Vadzim
-- 8/29/2016
-- add Open Orders report link to Search Data submenu

INSERT INTO pages (displayName, displayOrder, hiddenName, `class`, `method`, red, active)
SELECT    "Open Orders Report" AS displayName,
          maxOrder + 1 AS displayOrder,
          "openOrdersReport" AS hiddenName,
          "openOrders" AS `class`,
          "report" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = "searchData"
    AND       sm.active
    LIMIT 1
) m
WHERE     sm.hiddenName = "searchData"
AND       active
LIMIT 1;

INSERT INTO submenu_pages (pageID, subMenuID)
SELECT
    p.id AS pageID,
    m.subMenuID
FROM pages p
JOIN (
  SELECT    sm.id AS subMenuID
  FROM      subMenus sm
  WHERE     sm.hiddenName = "searchData"
) m
WHERE p.hiddenName = "openOrdersReport";

INSERT INTO page_params (pageID, `name`, `value`, active)
SELECT    id AS pageID,
          "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "openOrdersReport";

CREATE TABLE open_orders_statuses (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    ord_id BIGINT(10) NOT NULL,
    status_id INT(2) NOT NULL,
    user_id INT(4) NOT NULL,
    updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE ord_id (ord_id),
    KEY status_id (status_id),
    KEY user_id (user_id)
) ENGINE=InnoDB;

INSERT INTO statuses (category, displayName, shortName) VALUES
("openOrders", "Open", "OP"),
("openOrders", "Closed", "CL");

-- Vadzim
-- 8/29/2016
-- remove old Invoices pages

UPDATE    submenu_pages sp
JOIN      pages p ON p.id = sp.pageID
SET       sp.active = 0
WHERE     hiddenName IN ('searchReceivingInvoices', 'searchStorageInvoices', 'editReceivingInvoices', 'editProcessingInvoices', 'editStorageInvoices', 'editWorkOrderInvoices');

-- Tri Le
-- 09/05/2016

-- Add page_params for Search Scan Input Data page

INSERT INTO page_params (pageID, name, value, active)
SELECT    id AS pageID,
          "show" AS name,
          "scanInput" AS value,
          1 AS active
FROM      pages
WHERE     hiddenName = "searchScanInputData";

-- Duy Nguyen
-- 09/14/2016

-- Add source location of carton whhich transfered to mezzanine location
ALTER TABLE `transfer_cartons` ADD `fromLocID` INT(7) NULL AFTER `cartonID`;

-- VAdzim
-- 09/15/2016

-- adding proc_out_dt field to outbound_sum table

ALTER TABLE outbound_sum ADD proc_out_dt DATE NULL DEFAULT NULL AFTER pick_out_dt;

INSERT INTO outbound_sum (log_id, ord_id, proc_out_dt)
SELECT  logID,
        orderID,
        logTime
FROM (
        SELECT    logID,
                  primeKey AS orderID,
                  logTime
        FROM      logs_orders lo
        JOIN      logs_values lv ON lv.logID = lo.id
        JOIN      logs_fields lf ON lf.id = lv.fieldID
        JOIN      statuses st ON st.id = lv.toValue
        WHERE     st.category = 'orders'
        AND       st.shortName = 'OPCO'
        AND       lf.displayName = 'statusID'
        AND       lf.tableName = 'statuses'
        AND       lf.category = 'orders'
        AND       fromValue != toValue
        GROUP BY  primeKey,
                  toValue
        ORDER BY  logID DESC
) a
GROUP BY orderID
ON DUPLICATE KEY UPDATE
log_id = VALUES(log_id),
ord_id = VALUES(ord_id),
proc_out_dt = VALUES(proc_out_dt);
