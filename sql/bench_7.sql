-- ---------------------------------------------------------
--      wms:      ONLY commands from after 2480 here
--      _default: ONLY commands from after 501 here
-- ---------------------------------------------------------

-- Tri Le
-- 02/23/2016

-- Add "Generate Receiving" in menu Development
INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Generate Receiving" AS displayName,
          maxOrder + 1 AS displayOrder,
          "generateReceivingID" AS hiddenName,
          "receiving" AS `class`,
          "generate" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = "database"
    AND       sm.active
        LIMIT 1
) m
WHERE     sm.hiddenName = "database"
AND       active
LIMIT 1;

-- Add relationship for submenu Generate Receiving
INSERT INTO submenu_pages (pageID, subMenuID)
SELECT
    MAX(p.id) AS pageID,
    m.subMenuID
FROM pages p
JOIN (
  SELECT    sm.id AS subMenuID
  FROM      subMenus sm
  WHERE     sm.hiddenName = "database"
) m

-- Jon Sapp Bug Fix: Shipping Dashboard only shows first client by default.
-- This is an issue for client-users and the redundant becuase of the
-- multi-select dropdown
UPDATE  page_params
SET     active = 0
WHERE   pageID = (
    SELECT id
    FROM `pages`
    WHERE hiddenName = 'dashboardShipping'
    AND name = 'firstDropdown'
);

-- Jon Sapp Bug Fix: BOL ID in shipping_info table must be a big int for
-- bolIDs that have a value over 9000000000
ALTER TABLE `shipping_info` CHANGE `bolID` `bolID` BIGINT(11) NULL DEFAULT NULL;


-- Raji - Add Client Notes to Search Received Container Page/Search Orders page
-- 04/07/2016

-- container_notes table
CREATE TABLE container_notes  (
    id INT(11) NOT NULL AUTO_INCREMENT ,
    recNum INT(8) NOT NULL ,
    vendorID INT(5) NOT NULL ,
    clientNotes VARCHAR(255) NULL DEFAULT NULL ,
    PRIMARY KEY (id)
) ENGINE = InnoDB  CHARACTER SET utf8 COLLATE utf8_general_ci;

-- Client Access to Search Received Container page
UPDATE    pages
SET       clientAccess = 1
WHERE     hiddenName = 'searchReceivedContainers';

-- order_notes table
CREATE TABLE order_notes (
    id INT(8) NOT NULL AUTO_INCREMENT ,
    scanordernumber VARCHAR(20) NOT NULL ,
    vendorID INT(5) NOT NULL ,
    clientNotes VARCHAR(255) NULL DEFAULT NULL ,
    PRIMARY KEY (id)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

-- Client Access to Search Orders page
UPDATE    pages
SET       clientAccess = 1
WHERE     hiddenName = 'searchOrders';


-- Raji
-- 04/08/2016

-- Modify the order_notes table field
ALTER TABLE order_notes CHANGE scanordernumber orderID INT(10) NOT NULL;

-- Drop the vendorID field container_notes table
ALTER TABLE container_notes DROP vendorID;


-- Drop the vendorID field in order_notes table
ALTER TABLE order_notes DROP vendorID;

-- Add Index to container_notes table
ALTER TABLE container_notes ADD INDEX(recNum);


-- Add Index to order_notes table
ALTER TABLE order_notes ADD INDEX(orderID);

-- Raji
-- 04/17/2016

-- New tables for Invoice Tool - Development Phase

-- Table structure for table charge_cd_mstr
CREATE TABLE IF NOT EXISTS charge_cd_mstr (
    chg_cd_id int(4) NOT NULL AUTO_INCREMENT,
    chg_cd varchar(50) NOT NULL,
    chg_cd_des varchar(250) NOT NULL,
    chg_cd_type varchar(10) NOT NULL,
    chg_cd_uom varchar(10) NOT NULL,
    chg_cd_sts varchar(10) NOT NULL,
    disp_ord int(4) NOT NULL,
    create_by varchar(50) NOT NULL,
    create_dt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    update_by varchar(50) DEFAULT NULL,
    update_dt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    status varchar(1) NOT NULL,
    PRIMARY KEY (chg_cd_id),
    UNIQUE KEY chg_cd (chg_cd)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;


-- Table structure for table customer_ctc
CREATE TABLE IF NOT EXISTS customer_ctc (
    cust_ctc_id int(8) NOT NULL AUTO_INCREMENT,
    cust_id int(5) NOT NULL,
    ctc_dept varchar(50) NOT NULL,
    ctc_nm varchar(60) NOT NULL,
    ctc_dft tinyint(1) NOT NULL,
    ctc_ph varchar(20) NOT NULL,
    ctc_eml varchar(250) NOT NULL,
    create_by varchar(50) NOT NULL,
    create_dt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    update_by varchar(50) DEFAULT NULL,
    update_dt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    status varchar(1) NOT NULL,
    PRIMARY KEY (cust_ctc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;

-- Table structure for table customer_mstr
CREATE TABLE IF NOT EXISTS customer_mstr (
    cust_id int(5) NOT NULL AUTO_INCREMENT,
    cust_cd varchar(50) NOT NULL,
    cust_type varchar(25) NOT NULL,
    cust_nm varchar(25) NOT NULL,
    bill_to_add1 varchar(250) NOT NULL,
    bill_to_add2 varchar(50) DEFAULT NULL,
    bill_to_state varchar(50) NOT NULL,
    bill_to_cnty varchar(50) NOT NULL,
    bill_to_zip varchar(5) NOT NULL,
    bill_to_contact varchar(250) NOT NULL,
    ship_to_add1 varchar(250) NOT NULL,
    ship_to_add2 varchar(250) DEFAULT NULL,
    ship_to_state varchar(50) NOT NULL,
    ship_to_cnty varchar(50) NOT NULL,
    ship_to_zip varchar(5) NOT NULL,
    net_terms varchar(10) NOT NULL,
    create_by varchar(50) NOT NULL,
    create_dt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    update_by varchar(50) DEFAULT NULL,
    update_dt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    status varchar(1) NOT NULL,
   PRIMARY KEY (cust_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=10001;


-- Table structure for table invoice_cost
CREATE TABLE IF NOT EXISTS invoice_cost (
    inv_cost_id int(6) NOT NULL AUTO_INCREMENT,
    cust_id int(5) NOT NULL,
    chg_cd_id int(4) NOT NULL,
    chg_cd_cur varchar(10) NOT NULL,
    chg_cd_price double(6,2) NOT NULL,
    create_by varchar(50) NOT NULL,
    create_dt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    update_by varchar(50) DEFAULT NULL,
    update_dt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    status varchar(1) NOT NULL,
    PRIMARY KEY (inv_cost_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;


-- Table structure for table invoice_dtls
CREATE TABLE IF NOT EXISTS invoice_dtls (
    inv_id int(8) NOT NULL AUTO_INCREMENT,
    wh_id int(4) NOT NULL,
    cust_id int(5) NOT NULL,
    inv_num int(10) DEFAULT NULL,
    chg_cd_id int(4) NOT NULL,
    chg_cd_desc varchar(50) NOT NULL,
    chg_cd_qty int(4) DEFAULT NULL,
    chg_cd_uom varchar(10) DEFAULT NULL,
    chg_cd_price double(6,2) NOT NULL,
    chg_cd_cur varchar(10) NOT NULL,
    chg_cd_amt double(6,2) NOT NULL,
    create_by varchar(50) NOT NULL,
    create_dt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    update_by varchar(50) DEFAULT NULL,
    update_dt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    status varchar(1) NOT NULL,
    PRIMARY KEY (inv_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;


-- Table structure for table invoice_hdr
CREATE TABLE IF NOT EXISTS invoice_hdr (
    inv_id int(8) NOT NULL AUTO_INCREMENT,
    wh_id int(4) NOT NULL,
    cust_id int(5) NOT NULL,
    inv_num int(10) DEFAULT NULL,
    inv_dt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    inv_type varchar(10) NOT NULL,
    inv_cur varchar(10) NOT NULL,
    inv_amt int(6) NOT NULL,
    inv_tax double(6,2) NOT NULL,
    inv_sts varchar(1) NOT NULL,
    inv_org_id int(8) DEFAULT NULL,
    inv_org int(10) DEFAULT NULL,
    inv_paid_sts varchar(1) DEFAULT NULL,
    inv_paid_dt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    inv_paid_typ varchar(10) DEFAULT NULL,
    inv_paid_ref varchar(50) NOT NULL,
    cust_ref varchar(10) NOT NULL,
    net_terms varchar(10) NOT NULL,
    bill_to_add1 varchar(250) NOT NULL,
    bill_to_add2 varchar(250) NOT NULL,
    bill_to_state varchar(50) NOT NULL,
    bill_to_cnty varchar(50) NOT NULL,
    bill_to_zip varchar(5) NOT NULL,
    bill_to_contact varchar(250) NOT NULL,
    create_by varchar(50) NOT NULL,
    create_dt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    update_by varchar(50) DEFAULT NULL,
    update_dt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    status varchar(1) NOT NULL,
    PRIMARY KEY (inv_id),
    UNIQUE KEY inv_num (inv_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;


-- Table structure for table invoice_sum
CREATE TABLE IF NOT EXISTS invoice_sum (
    inv_id int(8) NOT NULL AUTO_INCREMENT,
    cust_id int(5) NOT NULL,
    inv_num int(10) DEFAULT NULL,
    check_num int(10) DEFAULT NULL,
    inv_net_ord varchar(10) DEFAULT NULL,
    inv_dis double(2,2) DEFAULT NULL,
    inv_freight double(6,2) DEFAULT NULL,
    inv_tax double(6,2) DEFAULT NULL,
    inv_terms varchar(250) DEFAULT NULL,
    create_by varchar(50) NOT NULL,
    create_dt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    update_by varchar(50) DEFAULT NULL,
    update_dt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    status varchar(1) NOT NULL,
    PRIMARY KEY (inv_id),
    UNIQUE KEY inv_num (inv_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;


-- Table structure for table invoice_log
CREATE TABLE IF NOT EXISTS invoice_log (
    inv_id int(8) NOT NULL AUTO_INCREMENT,
    inv_num int(10) DEFAULT NULL,
    inv_type varchar(10) NOT NULL,
    inv_dt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    inv_acct_flg tinyint(1) NOT NULL,
    inv_acct_dt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    inv_edi_flg tinyint(1) NOT NULL,
    inv_edi_dt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    create_by varchar(50) NOT NULL,
    create_dt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    update_by varchar(50) DEFAULT NULL,
    update_dt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    status varchar(1) NOT NULL,
    PRIMARY KEY (inv_id),
    UNIQUE KEY inv_num (inv_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;


-- Raji
-- 04/18/2016

-- Add bill_to_city / ship to city field in customer_mstr and invoice_hdr table

ALTER TABLE customer_mstr ADD bill_to_city VARCHAR(50) NOT NULL AFTER bill_to_state;

ALTER TABLE customer_mstr ADD ship_to_city VARCHAR(50) NOT NULL AFTER ship_to_state;

ALTER TABLE invoice_hdr ADD bill_to_city VARCHAR(50) NOT NULL AFTER bill_to_state;


-- Tri Le
-- 04/19/3016

-- Make ref unique
ALTER TABLE `receivings` ADD UNIQUE KEY `ref` (`ref`);

-- ref field is NOT NULL
ALTER TABLE `receivings` CHANGE `ref` `ref` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- Vadzim
-- 04/25/3016-- adding invoice history tables

CREATE TABLE inv_his_rec (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    inv_id INT(8) NOT NULL,
    rec_num INT(8) NOT NULL,
    inv_sts TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY inv_id (inv_id),
    KEY rec_num (rec_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE inv_his_ord_prc (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    inv_id INT(8) NOT NULL,
    ord_id BIGINT(10) NOT NULL,
    inv_sts TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY inv_id (inv_id),
    KEY ord_id (ord_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE inv_his_wo (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    inv_id INT(8) NOT NULL,
    wo_id BIGINT(10) NOT NULL,
    inv_sts TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY inv_id (inv_id),
    KEY wo_id (wo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- adding working hours tables

CREATE TABLE wrk_hrs_rcv (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    dt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    rcv_num INT(8) NOT NULL,
    amount DECIMAL(6, 2) NOT NULL,
    ctg ENUM('a', 'e') NOT NULL,
    create_by VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    KEY rcv_num (rcv_num)
) ENGINE=InnoDB;

CREATE TABLE wrk_hrs_ord_prc (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    dt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ord_id BIGINT(10) NOT NULL,
    amount DECIMAL(6, 2) NOT NULL,
    ctg ENUM('a', 'e') NOT NULL,
    create_by VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    KEY ord_id (ord_id)
) ENGINE=InnoDB;

CREATE TABLE wrks_hr_wo (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    dt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    wo_id BIGINT(10) NOT NULL,
    amount DECIMAL(6, 2) NOT NULL,
    ctg ENUM('a', 'e') NOT NULL,
    create_by VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    KEY wo_id (wo_id)
) ENGINE=InnoDB;

-- Jon
INSERT INTO customer_mstr (
    cust_id,
    cust_nm
)
SELECT    v.id, CONCAT(w.shortName, '_', vendorName)
FROM      vendors v
JOIN      warehouses w ON v.warehouseID = w.id
LEFT JOIN customer_mstr c ON cust_id = v.id
WHERE     cust_id IS NULL
AND (
    v.active
    OR
    v.oldActive = (
        SELECT id
        FROM statuses
        WHERE displayName = "active"
        AND  category = "vendor"
    )
);


-- Vadzim
-- 04/25/3016
-- modifying wrk_hrs_rcv, wrk_hrs_ord_prc and wrks_hr_wo tables

ALTER TABLE wrk_hrs_ord_prc DROP INDEX ord_id;
ALTER TABLE wrk_hrs_ord_prc CHANGE ord_id scan_ord_nbr INT(10) UNSIGNED ZEROFILL NOT NULL;
ALTER TABLE wrk_hrs_ord_prc ADD INDEX scan_ord_nbr (scan_ord_nbr);

ALTER TABLE `wrk_hrs_ord_prc` CHANGE `create_by` `create_by` INT(4) NOT NULL;
ALTER TABLE wrk_hrs_ord_prc ADD INDEX create_by (create_by);

ALTER TABLE wrk_hrs_rcv CHANGE create_by create_by INT(4) NOT NULL;
ALTER TABLE wrk_hrs_rcv ADD INDEX create_by (create_by);

ALTER TABLE wrks_hr_wo CHANGE create_by create_by INT(4) NOT NULL;
ALTER TABLE wrks_hr_wo ADD INDEX create_by (create_by);

-- Tri Le
-- 04/19/3016

-- Change fields created_at and shipped_at, default value is CURRENT_TIMESTAMP.
ALTER TABLE `receivings` CHANGE `created_at` `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `receivings` CHANGE `shipped_at` `shipped_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;



-- Raji
-- 04/27/2016

-- Drop the index chg_cd from charge_cd_mstr table
ALTER TABLE charge_cd_mstr DROP INDEX chg_cd

-- populate charge codes

-- Truncate the table
TRUNCATE TABLE `charge_cd_mstr`;


-- Populate the charge_cd_mstr table

INSERT INTO `charge_cd_mstr` (`chg_cd_id`, `chg_cd`, `chg_cd_des`, `chg_cd_type`, `chg_cd_uom`, `chg_cd_sts`, `disp_ord`, `create_by`, `create_dt`, `update_by`, `update_dt`, `status`)
VALUES
(NULL, 'REC-CARTON', 'CARTON RECEIVED', 'RECEIVING', 'CARTON', 'active', '1', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'REC-UNIT', 'PIECES RECEIVED', 'RECEIVING', 'UNIT', 'active', '2', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'REC-PALLET', 'PALLETS RECEIVED', 'RECEIVING', 'PALLET', 'active', '3', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'REC-VOLUME', 'VOLUMES RECEIVED', 'RECEIVING', 'VOLUME', 'active', '4', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'STOR-CART', 'STORAGE CARTON', 'STORAGE', 'CARTON', 'active', '5', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'STOR-PALLET', 'STORAGE PALLET', 'STORAGE', 'PALLET', 'active', '6', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'ORD-PROC', 'ORDER PROCESSING', 'ORD_PROC', 'CARTON', 'active', '7', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'ORD-PROC', 'ORDER PROCESSING', 'ORD_PROC', 'UNIT', 'active', '8', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'ORD-PROC', 'ORDER PROCESSING', 'ORD_PROC', 'ORDER', 'active', '9', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'BOL-FEE', 'BOL COST', 'ORD_PROC', 'BOL', 'active', '10', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'BOL-FEE', 'BOL COST', 'ORD_PROC', 'ORDER', 'active', '11', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'SHIP-LABEL', 'CARTON LABEL / SHIP TO LABEL', 'ORD_PROC', 'CARTON', 'active', '12', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'SHIP-LABEL', 'CARTON LABEL / SHIP TO LABEL', 'ORD_PROC', 'UNIT', 'active', '13', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'SHIP-LABEL', 'CARTON LABEL / SHIP TO LABEL', 'ORD_PROC', 'PALLET', 'active', '14', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'SHIP-LABEL', 'CARTON LABEL / SHIP TO LABEL', 'ORD_PROC', 'ORDER', 'active', '15', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'UCC128', 'SHIPPING UCC128 LABEL', 'ORD_PROC', 'LABEL', 'active', '16', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'UCC128', 'SHIPPING UCC128 LABEL', 'ORD_PROC', 'UNIT', 'active', '17', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'UCC128', 'SHIPPING UCC128 LABEL', 'ORD_PROC', 'CARTON', 'active', '18', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'UCC128', 'SHIPPING UCC128 LABEL', 'ORD_PROC', 'PALLET', 'active', '19', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'UCC128', 'SHIPPING UCC128 LABEL', 'ORD_PROC', 'ORDER', 'active', '20', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'UPS / FEDEX', 'SHIPPING UPS/FEDEX LABEL', 'ORD_PROC', 'UNIT', 'active', '21', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'UPS / FEDEX', 'SHIPPING UPS/FEDEX LABEL', 'ORD_PROC', 'CARTON', 'active', '22', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'UPS / FEDEX', 'SHIPPING UPS/FEDEX LABEL', 'ORD_PROC', 'PALLET', 'active', '23', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'UPS / FEDEX', 'SHIPPING UPS/FEDEX LABEL', 'ORD_PROC', 'ORDER', 'active', '24', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'PAL-STRETCH-WRAP', 'PALLET w/STRETCH-WRAP', 'ORD_PROC', 'PALLET', 'active', '25', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'MOVE INVENTORY', 'MOVE INVENTORY TO E-COMM AREA', 'ORD_PROC', 'UNIT', 'active', '26', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'E-COMM-STOR', 'E-COMMERCE STORAGE', 'ORD_PROC', 'UNIT', 'active', '27', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'ONLINE-ORD-PROC', 'ONLINE ORDER PROCESSING', 'ORD_PROC', 'ORDER', 'active', '28', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'ADDITIONAL-PICKS', 'ADDITIONAL-PICKS', 'ORD_PROC', 'PICK', 'active', '29', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'SHIP-MATERIALS', 'SHIPPING MATERIALS', 'ORD_PROC', 'UNIT', 'active', '30', '69', CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RUSH', 'HANDELING RUSH', 'WO', 'UNIT', 'active', 31, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RUSH', 'HANDELING RUSH', 'WO', 'CARTON', 'active', 32, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-SPEC', 'HANDELING SPECIAL', 'WO', 'UNIT', 'active', 33, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-SPEC', 'HANDELING SPECIAL', 'WO', 'CARTON', 'active', 34, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-LABOUR', 'HANDELING LABOR / PHYS COUNT', 'WO', 'UNIT', 'active', 35, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-LABOUR', 'HANDELING LABOR / PHYS COUNT', 'WO', 'CARTON', 'active', 36, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-QC', 'HANDELING QC', 'WO', 'UNIT', 'active', 37, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-QC', 'HANDELING QC', 'WO', 'CARTON', 'active', 38, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-OT', 'HANDELING OVER TIME', 'WO', 'UNIT', 'active', 39, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-OT', 'HANDELING OVER TIME', 'WO', 'CARTON', 'active', 40, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-UNIT-PRCSS', 'HANDELING UNITS PROCESSED', 'WO', 'UNIT', 'active', 41, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-UNIT-PRCSS', 'HANDELING UNITS PROCESSED', 'WO', 'CARTON', 'active', 42, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-MAKE SETS', 'HANDELING MAKE SETS', 'WO', 'UNIT', 'active', 43, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-MAKE SETS', 'HANDELING MAKE SETS', 'WO', 'CARTON', 'active', 44, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-SORTED', 'HANDELING SORTED', 'WO', 'UNIT', 'active', 45, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-SORTED', 'HANDELING SORTED', 'WO', 'CARTON', 'active', 46, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-REPACK', 'HANDELING REPACK', 'WO', 'UNIT', 'active', 47, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-REPACK', 'HANDELING REPACK', 'WO', 'CARTON', 'active', 48, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-PICK PACK', 'HANDELING PICK PACK', 'WO', 'UNIT', 'active', 49, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-PICK PACK', 'HANDELING PICK PACK', 'WO', 'CARTON', 'active', 50, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-APLY-HNGRS', 'HANDELING REMOVE AND APPLY HANGERS', 'WO', 'UNIT', 'active', 51, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-APLY-HNGRS', 'HANDELING REMOVE AND APPLY HANGERS', 'WO', 'CARTON', 'active', 52, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-HNGRS', 'HANDELING APPLY HANGERS', 'WO', 'UNIT', 'active', 53, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-HNGRS', 'HANDELING APPLY HANGERS', 'WO', 'CARTON', 'active', 54, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-HNGRS', 'HANDELING REMOVE HANGERS', 'WO', 'UNIT', 'active', 55, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-HNGRS', 'HANDELING REMOVE HANGERS', 'WO', 'CARTON', 'active', 56, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-HNGRS-MATER', 'HANDELING HANGERS MATERIAL', 'WO', 'UNIT', 'active', 57, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-HNGRS-MATER', 'HANDELING HANGERS MATERIAL', 'WO', 'CARTON', 'active', 58, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-APLY-SIZERS', 'HANDELING REMOVE AND APPLY SIZER', 'WO', 'UNIT', 'active', 59, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-APLY-SIZERS', 'HANDELING REMOVE AND APPLY SIZER', 'WO', 'CARTON', 'active', 60, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-SIZERS', 'HANDELING APPLY SIZER', 'WO', 'UNIT', 'active', 61, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-SIZERS', 'HANDELING APPLY SIZER', 'WO', 'CARTON', 'active', 62, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-SIZERS', 'HANDELING REMOVE SIZER', 'WO', 'UNIT', 'active', 63, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-SIZERS', 'HANDELING REMOVE SIZER', 'WO', 'CARTON', 'active', 64, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-SIZERS-MATER', 'HANDELING SIZER MATERIAL', 'WO', 'UNIT', 'active', 65, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-SIZERS-MATER', 'HANDELING SIZER MATERIAL', 'WO', 'CARTON', 'active', 66, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-APLY-STKR', 'HANDELING REMOVE AND APPLY STICKER', 'WO', 'UNIT', 'active', 67, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-APLY-STKR', 'HANDELING REMOVE AND APPLY STICKER', 'WO', 'CARTON', 'active', 68, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-STKR', 'HANDELING APPLY STICKER', 'WO', 'UNIT', 'active', 69, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-STKR', 'HANDELING APPLY STICKER', 'WO', 'CARTON', 'active', 70, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-STKR', 'HANDELING REMOVE STICKER', 'WO', 'UNIT', 'active', 71, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-STKR', 'HANDELING REMOVE STICKER', 'WO', 'CARTON', 'active', 72, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-STKR-MATER', 'HANDELING STICKER MATERIAL', 'WO', 'UNIT', 'active', 73, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-STKR-MATER', 'HANDELING STICKER MATERIAL', 'WO', 'CARTON', 'active', 74, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-APLY-PRC-TKT', 'HANDELING REMOVE AND APPLY PRICE TICKET', 'WO', 'UNIT', 'active', 75, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-APLY-PRC-TKT', 'HANDELING REMOVE AND APPLY PRICE TICKET', 'WO', 'CARTON', 'active', 76, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-PRC-TKT', 'HANDELING APPLY PRICE TICKET', 'WO', 'UNIT', 'active', 77, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-PRC-TKT', 'HANDELING APPLY PRICE TICKET', 'WO', 'CARTON', 'active', 78, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-PRC-TKT', 'HANDELING REMOVE PRICE TICKET', 'WO', 'UNIT', 'active', 79, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-PRC-TKT', 'HANDELING REMOVE PRICE TICKET', 'WO', 'CARTON', 'active', 80, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-APLY-PRC-TKT-SIZ', 'HANDELING REMOVE AND APPLY PRICE TICKET SIZE/COLOR', 'WO', 'UNIT', 'active', 81, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-APLY-PRC-TKT-SIZ', 'HANDELING REMOVE AND APPLY PRICE TICKET SIZE/COLOR', 'WO', 'CARTON', 'active', 82, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-PRC-TKT-SIZ', 'HANDELING APPLY PRICE TICKET SIZE/COLOR/STYLE', 'WO', 'UNIT', 'active', 83, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-PRC-TKT-SIZ', 'HANDELING APPLY PRICE TICKET SIZE/COLOR/STYLE', 'WO', 'CARTON', 'active', 84, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-PRC-TKT-SIZ', 'HANDELING REMOVE PRICE TICKET SIZE/COLOR/STYLE', 'WO', 'UNIT', 'active', 85, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-PRC-TKT-SIZ', 'HANDELING REMOVE PRICE TICKET SIZE/COLOR/STYLE', 'WO', 'CARTON', 'active', 86, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-PRC-TKT-SIZ-MATER', 'HANDELING PRICE TICKET MATERIAL', 'WO', 'UNIT', 'active', 87, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-PRC-TKT-SIZ-MATER', 'HANDELING PRICE TICKET MATERIAL', 'WO', 'CARTON', 'active', 88, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-FOLD', 'HANDELING FOLD GARNMENT', 'WO', 'UNIT', 'active', 89, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-FOLD', 'HANDELING FOLD GARNMENT', 'WO', 'CARTON', 'active', 90, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-CUT-SEW', 'HANDELING CUT AND SEW', 'WO', 'UNIT', 'active', 91, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-CUT-SEW', 'HANDELING CUT AND SEW', 'WO', 'CARTON', 'active', 92, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-APLY-BAG', 'HANDELING REMOVE AND APPLY BAG', 'WO', 'UNIT', 'active', 93, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-APLY-BAG', 'HANDELING REMOVE AND APPLY BAG', 'WO', 'CARTON', 'active', 94, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-BAG', 'HANDELING APPLY BAG', 'WO', 'UNIT', 'active', 95, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-BAG', 'HANDELING APPLY BAG', 'WO', 'CARTON', 'active', 96, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-BAG', 'HANDELING REMOVE BAG', 'WO', 'UNIT', 'active', 97, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-BAG', 'HANDELING REMOVE BAG', 'WO', 'CARTON', 'active', 98, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-BAG-MATER', 'HANDELING BAG MATERIAL', 'WO', 'UNIT', 'active', 99, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-BAG-MATER', 'HANDELING BAG MATERIAL', 'WO', 'CARTON', 'active', 100, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-CARTON-MATER', 'HANDELING CARTON MATERIAL', 'WO', 'UNIT', 'active', 101, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-CARTON-MATER', 'HANDELING CARTON MATERIAL', 'WO', 'CARTON', 'active', 102, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-CNT-LABEL', 'HANDELING CARTON CONTENT LABEL', 'WO', 'UNIT', 'active', 103, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-CNT-LABEL', 'HANDELING CARTON CONTENT LABEL', 'WO', 'CARTON', 'active', 104, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-CNT-MARKD', 'HANDELING CARTON MARKED BY HAND', 'WO', 'UNIT', 'active', 105, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-CNT-MARKD', 'HANDELING CARTON MARKED BY HAND', 'WO', 'CARTON', 'active', 106, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-STFG-TYP1', 'HANDELING STUFFING TYPE 1', 'WO', 'UNIT', 'active', 107, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-STFG-TYP1', 'HANDELING STUFFING TYPE 1', 'WO', 'CARTON', 'active', 108, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-STFG-TYP2', 'HANDELING STUFFING TYPE 2', 'WO', 'UNIT', 'active', 109, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-STFG-TYP2', 'HANDELING STUFFING TYPE 2', 'WO', 'CARTON', 'active', 110, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-STFG-TYP3', 'HANDELING STUFFING TYPE 3', 'WO', 'UNIT', 'active', 111, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-STFG-TYP3', 'HANDELING STUFFING TYPE 3', 'WO', 'CARTON', 'active', 112, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-STFG-TYP4', 'HANDELING STUFFING TYPE 4', 'WO', 'UNIT', 'active', 113, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-STFG-TYP4', 'HANDELING STUFFING TYPE 4', 'WO', 'CARTON', 'active', 114, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-STFG-TYP5', 'HANDELING STUFFING TYPE 5', 'WO', 'UNIT', 'active', 115, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-STFG-TYP5', 'HANDELING STUFFING TYPE 5', 'WO', 'CARTON', 'active', 116, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-CARTON-PRNT-APLY', 'HANDELING PRINT AND APPLY LABEL', 'WO', 'UNIT', 'active', 117, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-CARTON-PRNT-APLY', 'HANDELING PRINT AND APPLY LABEL', 'WO', 'CARTON', 'active', 118, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-LABL', 'HANDELING APPLY CLIENT LABEL', 'WO', 'UNIT', 'active', 119, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-LABL', 'HANDELING APPLY CLIENT LABEL', 'WO', 'CARTON', 'active', 120, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-CART-LABL', 'HANDELING REMOVE CARTON LABEL', 'WO', 'UNIT', 'active', 121, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-RMV-CART-LABL', 'HANDELING REMOVE CARTON LABEL', 'WO', 'CARTON', 'active', 122, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-BLNK-LBL-MATER', 'HANDELING BLANK LABEL MATERIAL', 'WO', 'UNIT', 'active', 123, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-BLNK-LBL-MATER', 'HANDELING BLANK LABEL MATERIAL', 'WO', 'CARTON', 'active', 124, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-NEW-CAROTNS', 'NEW CAROTNS', 'WO', 'UNIT', 'active', 125, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-NEW-CAROTNS', 'NEW CAROTNS', 'WO', 'CARTON', 'active', 126, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-BUBBLE-WRAP', 'BUBBLE WRAP', 'WO', 'UNIT', 'active', 127, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-BUBBLE-WRAP', 'BUBBLE WRAP', 'WO', 'CARTON', 'active', 128, 69, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i')


-- Add a index to charge_cd_mstr
ALTER TABLE charge_cd_mstr ADD UNIQUE charge_code(chg_cd, chg_cd_uom);


-- Add default to chg_cd_cur field in invoice_cost table
ALTER TABLE invoice_cost CHANGE chg_cd_cur chg_cd_cur VARCHAR(10)  NOT NULL DEFAULT 'USD';

-- Vadzim
-- 4/27/2016
-- adding new work orders tables

CREATE TABLE wo_hdr (
    wo_id BIGINT(10) NOT NULL AUTO_INCREMENT,
    scn_ord_num BIGINT(10) UNSIGNED ZEROFILL NOT NULL,
    rqst_dt DATE NOT NULL,
    comp_dt DATE NOT NULL,
    client_wo_num VARCHAR(30) NOT NULL,
    wo_num INT(10) UNSIGNED ZEROFILL NOT NULL,
    rlt_to_cust TINYINT(1) NOT NULL DEFAULT '0',
    ship_dt DATE NOT NULL,
    rqst_by DATE NOT NULL,
    wo_dtl VARCHAR(255) NULL DEFAULT NULL,
    create_by INT(4) NOT NULL,
    create_dt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_by INT(4) NULL DEFAULT NULL,
    update_dt DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    sts ENUM('i', 'u', 'd') NOT NULL DEFAULT 'i',
    PRIMARY KEY (wo_id),
    UNIQUE KEY unique_scn_ord_num (scn_ord_num),
    UNIQUE KEY unique_wo_num (wo_num)
) ENGINE=InnoDB;

CREATE TABLE wo_dtls (
    wo_dtl_id BIGINT(11) NOT NULL AUTO_INCREMENT,
    wo_id BIGINT(10) NOT NULL,
    scn_ord_num BIGINT(10) UNSIGNED ZEROFILL NOT NULL,
    chg_cd_id INT(4) NOT NULL,
    qty INT(6) NOT NULL,
    create_by INT(4) NOT NULL,
    create_dt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_by INT(4) NULL DEFAULT NULL,
    update_dt DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    sts ENUM('i', 'u', 'd') NOT NULL DEFAULT 'i',
    PRIMARY KEY (wo_dtl_id),
    KEY wo_id (wo_id),
    KEY scn_ord_num (scn_ord_num),
    KEY chg_cd_id (chg_cd_id),
    UNIQUE KEY unique_ord_id (wo_id, chg_cd_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wo_labor (
    labor_cd_id INT(5) NOT NULL,
    labor_cd_des VARCHAR(50) NOT NULL,
    chg_cd_id INT(4) NOT NULL,
    sts_id tinyint(1) NOT NULL DEFAULT '1',
    create_by INT(4) NOT NULL,
    create_dt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_by INT(4) DEFAULT NULL,
    update_dt datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    sts enum('i','u','d') NOT NULL DEFAULT 'i',
    PRIMARY KEY (labor_cd_id),
    KEY chg_cd_id (chg_cd_id),
    UNIQUE KEY uniqueLabour (chg_cd_id,labor_cd_des),
    KEY create_by (create_by),
    KEY update_by (update_by)
) ENGINE=InnoDB;


-- Vadzim
-- 4/28/2016
-- renaming wrks_hr_wo table to wrk_hrs_wo

RENAME TABLE wrks_hr_wo TO wrk_hrs_wo;


-- Raji
-- Update to 'USD' to chg_cd_cur field in invoice_cost table

UPDATE invoice_cost SET chg_cd_cur = 'USD';


-- Remove charge codes from charge_cd_mstr table
TRUNCATE TABLE charge_cd_mstr;

-- Update charge codes to 6 total

INSERT INTO `charge_cd_mstr` (`chg_cd_id`, `chg_cd`, `chg_cd_des`, `chg_cd_type`, `chg_cd_uom`, `chg_cd_sts`, `disp_ord`, `create_by`, `create_dt`, `update_by`, `update_dt`, `status`)
VALUES
(NULL, 'REC-CARTON', 'CARTON RECEIVED', 'RECEIVING', 'CARTON', 'active', '1', 0, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'STOR-CART', 'STORAGE CARTON', 'STORAGE', 'CARTON', 'active', '2', 0, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'ORD-PROC', 'ORDER PROCESSING', 'ORD_PROC', 'CARTON', 'active', '3', 0, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-PRC-TKT', 'HANDELING APPLY PRICE TICKET', 'WO', 'CARTON', 'active', 4, 0, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-APLY-PRC-STKR', 'HANDELING APPLY PRICE STICKER', 'WO', 'CARTON', 'active', 5, 0, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i'),
(NULL, 'HDL-PICK PACK', 'HANDELING PICK PACK', 'WO', 'CARTON', 'active', 6, 0, CURRENT_TIMESTAMP, NULL, '0000-00-00 00:00:00.000000', 'i');


-- Vadzim
-- 4/29/2016

-- replacing regular index with unique

ALTER TABLE order_notes DROP INDEX orderID;
ALTER TABLE order_notes ADD UNIQUE KEY orderID (orderID);

-- adding missing indexes

ALTER TABLE shipping_orders ADD KEY orderID (orderID);
ALTER TABLE shipping_orders ADD KEY bolID (bolID);


-- Vadzim
-- 4/29/2016

-- refactoring invoice_hdr and invoice_dtls tables

ALTER TABLE invoice_hdr CHANGE inv_tax inv_tax DECIMAL(9,2) NULL DEFAULT NULL;
ALTER TABLE invoice_hdr CHANGE inv_amt inv_amt DECIMAL(10,2) NULL DEFAULT NULL;
ALTER TABLE invoice_hdr CHANGE inv_type inv_type ENUM('o', 'c') NOT NULL DEFAULT 'o';
ALTER TABLE invoice_hdr CHANGE inv_cur inv_cur VARCHAR(3) NOT NULL DEFAULT 'USD';
ALTER TABLE invoice_hdr CHANGE inv_sts inv_sts ENUM('o', 'c') NOT NULL DEFAULT 'o';
ALTER TABLE invoice_hdr CHANGE inv_paid_sts inv_paid_sts TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE invoice_hdr CHANGE inv_paid_ref inv_paid_ref VARCHAR(50) NULL DEFAULT NULL;
ALTER TABLE invoice_hdr CHANGE bill_to_add2 bill_to_add2 VARCHAR(250) NULL DEFAULT NULL;
ALTER TABLE invoice_hdr CHANGE bill_to_zip bill_to_zip VARCHAR(10) NULL DEFAULT NULL;

ALTER TABLE invoice_hdr CHANGE create_by create_by INT(4) NOT NULL;
ALTER TABLE invoice_hdr ADD INDEX create_by (create_by);
ALTER TABLE invoice_hdr CHANGE create_dt create_dt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE invoice_hdr CHANGE update_by update_by INT(4) NULL DEFAULT NULL;
ALTER TABLE invoice_hdr ADD INDEX update_by (update_by);
ALTER TABLE invoice_hdr CHANGE update_dt update_dt DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE invoice_hdr CHANGE inv_dt inv_dt DATE NULL DEFAULT NULL;
ALTER TABLE invoice_hdr CHANGE inv_paid_dt inv_paid_dt DATE NULL DEFAULT NULL;


ALTER TABLE invoice_dtls CHANGE chg_cd_price chg_cd_price DECIMAL(8,2) NULL DEFAULT NULL;
ALTER TABLE invoice_dtls CHANGE chg_cd_amt chg_cd_amt DECIMAL(10,2) NULL DEFAULT NULL;
ALTER TABLE invoice_dtls CHANGE chg_cd_cur chg_cd_cur VARCHAR(3) NOT NULL DEFAULT 'USD';

ALTER TABLE invoice_dtls CHANGE create_by create_by INT(4) NOT NULL;
ALTER TABLE invoice_dtls ADD INDEX create_by (create_by);
ALTER TABLE invoice_dtls CHANGE create_dt create_dt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE invoice_dtls ADD UNIQUE invoiceCharge (inv_num, chg_cd_id);

ALTER TABLE invoice_dtls CHANGE update_by update_by INT(4) NULL DEFAULT NULL;
ALTER TABLE invoice_dtls ADD INDEX update_by (update_by);
ALTER TABLE invoice_dtls CHANGE update_dt update_dt DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;


ALTER TABLE inv_his_ord_prc DROP KEY ord_id;
ALTER TABLE inv_his_ord_prc ADD UNIQUE ord_id (ord_id);

ALTER TABLE inv_his_wo DROP KEY wo_id;
ALTER TABLE inv_his_wo ADD UNIQUE wo_id (wo_id);

DROP TABLE IF EXISTS inv_his_rec;

CREATE TABLE IF NOT EXISTS inv_his_rcv (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    inv_id INT(8) NOT NULL,
    rcv_nbr INT(8) NOT NULL,
    inv_sts TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY inv_id (inv_id),
    UNIQUE rcv_nbr (rcv_nbr)
) ENGINE=InnoDB;

CREATE TABLE inv_his_str_crt (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    inv_id INT(8) NOT NULL,
    crt_id BIGINT(10) NOT NULL,
    inv_sts TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY inv_id (inv_id),
    UNIQUE crt_id (crt_id)
) ENGINE=InnoDB;

CREATE TABLE inv_his_str_plt (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    inv_id INT(8) NOT NULL,
    crt_id BIGINT(10) NOT NULL,
    inv_sts TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY inv_id (inv_id),
    UNIQUE crt_id (crt_id)
) ENGINE=InnoDB;

CREATE TABLE inv_his_str_vlm (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    inv_id INT(8) NOT NULL,
    crt_id BIGINT(10) NOT NULL,
    inv_sts TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY inv_id (inv_id),
    UNIQUE crt_id (crt_id)
) ENGINE=InnoDB;

-- Vadzim
-- 5/2/2016

-- replacing invoice history tables related to storing carton and pallet data

DROP TABLE IF EXISTS inv_his_str_crt;
DROP TABLE IF EXISTS inv_his_str_plt;
DROP TABLE IF EXISTS inv_his_str_vlm;

CREATE TABLE inv_his_crt (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    inv_id INT(8) NOT NULL,
    crt_id BIGINT(10) NOT NULL,
    ctg ENUM ('RECEIVING', 'STORAGE', 'ORD_PROC') NOT NULL,
    inv_sts TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY inv_id (inv_id),
    UNIQUE crt_id (crt_id)
) ENGINE=InnoDB;

CREATE TABLE inv_his_plt (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    inv_id INT(8) NOT NULL,
    plt BIGINT(9) NOT NULL,
    ctg ENUM ('RECEIVING', 'STORAGE', 'ORD_PROC') NOT NULL,
    inv_sts TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY inv_id (inv_id),
    UNIQUE plate (plt)
) ENGINE=InnoDB;

-- renaming status field to sts in invoice_dtls and invoice_hdr tables

ALTER TABLE invoice_dtls CHANGE status sts ENUM('i', 'u', 'd') NOT NULL DEFAULT 'i';
ALTER TABLE invoice_hdr CHANGE status sts ENUM('i', 'u', 'd') NOT NULL DEFAULT 'i';

-- 05/02/2016
-- Raji
-- inv_his_crt

ALTER TABLE inv_his_crt ADD cust_id INT(5) NOT NULL AFTER crt_id;

-- 5/2/2016
-- Vadzim
-- add cust_id field to inv_his_ord_prc, inv_his_plt, inv_his_rcv, inv_his_wo tables

ALTER TABLE inv_his_ord_prc ADD cust_id INT(5) NOT NULL AFTER inv_id;
ALTER TABLE inv_his_plt ADD cust_id INT(5) NOT NULL AFTER inv_id;
ALTER TABLE inv_his_rcv ADD cust_id INT(5) NOT NULL AFTER inv_id;
ALTER TABLE inv_his_wo ADD cust_id INT(5) NOT NULL AFTER inv_id;

-- add indexes on cust_id field to inv_his_crt, inv_his_ord_prc, inv_his_plt, inv_his_rcv, inv_his_wo tables

ALTER TABLE inv_his_crt ADD INDEX cust_id (cust_id);
ALTER TABLE inv_his_ord_prc ADD INDEX cust_id (cust_id);
ALTER TABLE inv_his_plt ADD INDEX cust_id (cust_id);
ALTER TABLE inv_his_rcv ADD INDEX cust_id (cust_id);
ALTER TABLE inv_his_wo ADD INDEX cust_id (cust_id);

-- 5/3/2016
-- Vadzim
-- adding cust_id field to inv_his_ord_prc, inv_his_plt, inv_his_rcv, inv_his_wo tables

CREATE TABLE inv_his_ctn_dt (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    inv_id INT(8) NOT NULL,
    ctn_id BIGINT(10) NOT NULL,
    dt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    inv_sts TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY inv_id (inv_id),
    UNIQUE ctn_id (ctn_id)
) ENGINE=InnoDB;

CREATE TABLE inv_his_plt_dt (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    inv_id INT(8) NOT NULL,
    plt BIGINT(9) NOT NULL,
    dt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    inv_sts TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY inv_id (inv_id),
    UNIQUE plate (plt)
) ENGINE=InnoDB;

-- renaming inv_his_crt table to inv_his_ctn

RENAME TABLE inv_his_crt TO inv_his_ctn;

-- renaming "ctg" field to "cat" in inv_his_plt, wrk_hrs_rcv, wrk_hrs_ord_prc, wrks_hr_wo tables

ALTER TABLE inv_his_ctn CHANGE crt_id ctn_id BIGINT(10) NOT NULL;
ALTER TABLE inv_his_ctn CHANGE ctg cat ENUM ('RECEIVING', 'STORAGE', 'ORD_PROC') NOT NULL;
ALTER TABLE inv_his_plt CHANGE ctg cat ENUM ('RECEIVING', 'STORAGE', 'ORD_PROC') NOT NULL;
ALTER TABLE wrk_hrs_rcv CHANGE ctg cat ENUM('a', 'e') NOT NULL;
ALTER TABLE wrk_hrs_ord_prc CHANGE ctg cat ENUM('a', 'e') NOT NULL;
ALTER TABLE wrk_hrs_wo CHANGE ctg cat ENUM('a', 'e') NOT NULL;

-- renaming "inv_type" field to "inv_typ" in invoice_hdr table and its allowed values (invoiced, paid, cancelled)

ALTER TABLE invoice_hdr CHANGE inv_type inv_typ ENUM('i', 'p', 'c') NOT NULL DEFAULT 'i';

-- add cust_id field to inv_his_ctn_dt and inv_his_plt_dt tables

ALTER TABLE inv_his_ctn_dt ADD cust_id INT(5) NOT NULL AFTER inv_id;
ALTER TABLE inv_his_plt_dt ADD cust_id INT(5) NOT NULL AFTER inv_id;

-- add indexes on cust_id field to inv_his_ctn_dt and inv_his_plt_dt tables

ALTER TABLE inv_his_ctn_dt ADD INDEX cust_id (cust_id);
ALTER TABLE inv_his_plt_dt ADD INDEX cust_id (cust_id);

-- swap inv_typ and inv_sts potential and default values
-- add "o" - open to the list of inv_sts potential values

ALTER TABLE invoice_hdr CHANGE inv_typ inv_typ ENUM('o', 'c') NOT NULL DEFAULT 'o';
ALTER TABLE invoice_hdr CHANGE inv_sts inv_sts ENUM('o', 'i', 'p', 'c') NOT NULL DEFAULT 'o';


-- Raji
-- 05/03/2016

-- Add unique key to inv_his_ctn

ALTER TABLE inv_his_ctn DROP INDEX crt_id;

ALTER TABLE inv_his_ctn ADD UNIQUE ctn_cat (ctn_id, cat);

-- RAJI
-- 05/04/2016

-- charge_cd_mstr
-- Modify create_by to Not Null
-- Modify update_dt to DateTime and Default to Null
-- Modify chg_cd_status default to 'active'

ALTER TABLE charge_cd_mstr CHANGE create_by create_by INT(4) NOT NULL;

ALTER TABLE charge_cd_mstr CHANGE update_dt update_dt DATETIME on update CURRENT_TIMESTAMP NULL DEFAULT NULL;

ALTER TABLE charge_cd_mstr CHANGE chg_cd_sts chg_cd_sts VARCHAR(10) NOT NULL DEFAULT 'active';


-- Truncate the charge_cd_mstr table
TRUNCATE TABLE charge_cd_mstr;

-- Populate the charge_cd_mstr table

INSERT INTO charge_cd_mstr (chg_cd, chg_cd_des, chg_cd_type, chg_cd_uom, disp_ord)
VALUES
('REC-CART', 'CARTON RECEIVED', 'RECEIVING', 'CARTON', '1'),
('REC-VOL', 'VOLUME RECEIVED', 'RECEIVING', 'VOLUME', '2'),
('STOR-CART', 'STORAGE CARTON', 'STORAGE', 'CARTON', '3'),
('STOR-VOL', 'STORAGE VOLUME', 'STORAGE', 'VOLUME', '4'),
('SHIP-CART', 'CARTON SHIPPED', 'ORD_PROC', 'CARTON', '5'),
('SHIP-VOL', 'VOLUME SHIPPED', 'ORD_PROC', 'VOLUME', '6'),
('ORD-PROC', 'ORDER PROCESSING', 'ORD_PROC', 'ORDER', '7'),
('BOL-FEE', 'BOL COST', 'ORD_PROC', 'ORDER', '8'),
('SHIP-LABEL', 'CARTON LABEL / SHIP TO LABEL', 'ORD_PROC', 'LABEL', '9'),
('UCC128', 'SHIPPING UCC128 LABEL', 'ORD_PROC', 'LABEL', '10'),
('UPS/FEDEX', 'SHIPPING UPS/FEDEX LABEL', 'ORD_PROC', 'CARTON', '11'),
('PAL-STRETCH-WRAP', 'PALLET w/STRETCH-WRAP inc PALLET', 'ORD_PROC', 'PALLET', '12'),
('HDL-APLY-PRC-TKT', 'APPLY PRICE TICKET', 'WO', 'UNIT', '13'),
('HDL-RMV-PRC-TKT', 'REMOVE PRICE TICKET', 'WO', 'UNIT', '14'),
('HDL-RMV-APLY-STKR', 'REMOVE AND APPLY STICKER', 'WO', 'UNIT', '15'),
('HDL-REPACK', 'REPACK', 'WO', 'UNIT', '16'),
('HDL-APLY-HNGRS', 'HANG INDIVIDUAL GARMENT', 'WO', 'UNIT', '17'),
('HDL-APLY-BAG', 'INDIVIDUAL GARMENT INTO POLY BAG', 'WO', 'UNIT', '18'),
('HDL-RMV-BAG', 'REMOVE POLY BAG', 'WO', 'UNIT', '19'),
('HDL-MAKE SETS', 'SETS INTO INDIVIDUAL POLY BAG', 'WO', 'UNIT', '20'),
('HDL-SORTED', 'SORT PIECES', 'WO', 'UNIT', '21'),
('HDL-RUB-BAND', 'RUBBER BAND', 'WO', 'UNIT', '22'),
('HDL-RMV-STRP', 'REMOVE STRAPS', 'WO', 'UNIT', '23');


-- 5/3/2016
-- Vadzim

ALTER TABLE invoice_hdr CHANGE inv_org inv_org INT(10) UNSIGNED ZEROFILL NOT NULL;


-- 05/06/2016
-- Raji

-- Add new page to charge code master
INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `clientAccess`)
SELECT    "Edit Charge Codes" AS displayName,
          maxOrder + 1 AS displayOrder,
          "editChargeCodeMaster" AS hiddenName,
          "invoices" AS `class`,
          "chargeCodeMaster" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = "invoice"
    AND       sm.active
        LIMIT 1
) m
WHERE     sm.hiddenName = "invoice"
AND       active
LIMIT 1;

-- Add chargecodemaster to Invoice submenu
INSERT INTO submenu_pages (pageID, subMenuID)
SELECT
    MAX(p.id) AS pageID,
    m.subMenuID
FROM pages p
JOIN (
  SELECT    sm.id AS subMenuID
  FROM      subMenus sm
  WHERE     sm.hiddenName = "invoice"
) m;

-- Add page params for "Charge Code Master"
INSERT INTO page_params (pageID, name, value, active)
SELECT    id AS pageID,
          "editable" AS name,
          "display" AS value,
          1 AS active
FROM      pages
WHERE     hiddenName = "editChargeCodeMaster";

-- Update disp_ord = 0
UPDATE charge_cd_mstr SET disp_ord = 0;

-- Set disp_ord Default to 0
ALTER TABLE charge_cd_mstr CHANGE disp_ord disp_ord INT(4) NOT NULL DEFAULT '0';

-- Modify the chg_cd_type
ALTER TABLE charge_cd_mstr CHANGE chg_cd_type chg_cd_type VARCHAR(50) NOT NULL;

-- Insert Labor Fee as Other Services category
INSERT INTO charge_cd_mstr (chg_cd, chg_cd_des, chg_cd_type, chg_cd_uom)
VALUES
('LABOR-FEE', 'LABOR FEE', 'OTHER SERVICES', 'UNIT');

-- 5/7/2016
-- Vadzim
-- merging Order Processing and Work Orders charges

UPDATE    charge_cd_mstr
SET       chg_cd_type = 'ORD_PROC'
WHERE     chg_cd_type = 'WO';

-- Jon
-- Tables for invoice inventory summary

CREATE TABLE `ctn_sum` (
  `carton_id` int(10) NOT NULL,
  `batch_id` int(8) NOT NULL,
  `cust_id` int(5) DEFAULT NULL,
  `rcv_dt` date DEFAULT NULL,
  `last_active` date DEFAULT NULL,
  `vol` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ctn_sum_batch` (
  `id` int(10) NOT NULL,
  `recNum` int(8) NOT NULL,
  `upcID` int(10) NOT NULL,
  `prefix` varchar(80) DEFAULT NULL,
  `suffix` varchar(25) DEFAULT NULL,
  `height` decimal(5,1) NOT NULL,
  `width` decimal(5,1) NOT NULL,
  `length` decimal(5,1) NOT NULL,
  `weight` decimal(5,1) DEFAULT NULL,
  `eachHeight` decimal(6,2) NOT NULL,
  `eachWidth` decimal(6,2) NOT NULL,
  `eachLength` decimal(6,2) NOT NULL,
  `eachWeight` decimal(6,2) NOT NULL,
  `initialCount` int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ctn_sum_cntr_custs` (
  `rcv_nbr` int(8) NOT NULL,
  `cust_id` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ctn_sum_mk` (
  `carton_id` int(10) NOT NULL,
  `batch_id` int(8) NOT NULL,
  `cust_id` int(5) DEFAULT NULL,
  `rcv_dt` date DEFAULT NULL,
  `last_active` date DEFAULT NULL,
  `vol` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ctn_sum_rec_dt` (
  `carton_id` int(10) NOT NULL,
  `rcv_dt` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ctn_sum_shp` (
  `carton_id` int(10) NOT NULL,
  `batch_id` int(8) NOT NULL,
  `cust_id` int(5) DEFAULT NULL,
  `rcv_dt` date DEFAULT NULL,
  `last_active` date DEFAULT NULL,
  `vol` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ctn_sum`
  ADD PRIMARY KEY (`carton_id`),
  ADD KEY `cust_id` (`cust_id`),
  ADD KEY `rcv_dt` (`rcv_dt`),
  ADD KEY `batch_id` (`batch_id`);

ALTER TABLE `ctn_sum_batch`
  ADD PRIMARY KEY (`id`),
  ADD KEY `upcID` (`upcID`),
  ADD KEY `receivingNumber` (`recNum`);

ALTER TABLE `ctn_sum_cntr_custs`
  ADD PRIMARY KEY (`rcv_nbr`),
  ADD KEY `vendorID` (`cust_id`);

ALTER TABLE `ctn_sum_mk`
  ADD PRIMARY KEY (`carton_id`),
  ADD KEY `cust_id` (`cust_id`),
  ADD KEY `rcv_dt` (`rcv_dt`),
  ADD KEY `batch_id` (`batch_id`);

ALTER TABLE `ctn_sum_rec_dt`
  ADD PRIMARY KEY (`carton_id`),
  ADD KEY `rcv_dt` (`rcv_dt`);

ALTER TABLE `ctn_sum_shp`
  ADD PRIMARY KEY (`carton_id`),
  ADD KEY `cust_id` (`cust_id`),
  ADD KEY `rcv_dt` (`rcv_dt`),
  ADD KEY `batch_id` (`batch_id`);

-- Jon
-- more tables for invoicing history

CREATE TABLE `inv_stor_his` (
  `cust_id` int(5) NOT NULL,
  `dt` date NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `inv_id` int(10) NOT NULL,
  `inv_num` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `inv_vol_rates` (
  `cust_id` int(5) NOT NULL,
  `category` varchar(1) NOT NULL,
  `chg_cd_id` int(4) NOT NULL,
  `min_vol` decimal(4,2) NOT NULL,
  `max_vol` decimal(4,2) NOT NULL,
  `rate` decimal(3,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `inv_stor_his`
  ADD PRIMARY KEY (`cust_id`,`dt`) USING BTREE,
  ADD KEY `dt` (`dt`),
  ADD KEY `inv_id` (`inv_id`),
  ADD KEY `inv_num` (`inv_num`);

ALTER TABLE `inv_vol_rates`
  ADD PRIMARY KEY (`cust_id`,`chg_cd_id`) USING BTREE,
  ADD KEY `chg_cd_id` (`chg_cd_id`);

-- 05/08/2016
-- Raji
-- insert Rec Month charge to charge code master

INSERT INTO charge_cd_mstr (chg_cd, chg_cd_des, chg_cd_type, chg_cd_uom)
VALUES
('REC-MONTH', 'RECEIVE CARTON CHARGE BY MONTH', 'RECEIVING', 'MONTH');

-- modify the charge code type name
UPDATE   charge_cd_mstr
SET      chg_cd_type = 'OTHER_SERV'
WHERE    chg_cd_type = 'OTHER SERVICES';

-- Jon
-- set labor charge code uom to labor so it is distinguishable
-- add charge codes for the three customers demo

UPDATE charge_cd_mstr
SET    chg_cd_uom = 'LABOR'
WHERE  chg_cd = 'LABOR-FEE'
AND    chg_cd_type = 'OTHER_SERV';

ALTER TABLE `charge_cd_mstr` CHANGE `chg_cd_uom` `chg_cd_uom` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

INSERT INTO `charge_cd_mstr` (`chg_cd`, `chg_cd_des`, `chg_cd_type`, `chg_cd_uom`) VALUES
('TRNS-SERVICE-DRAYAGE', 'INCLUDES CHASSIS FEES RATES AVAILABLE UPON REQUEST', 'RECEIVING', 'CONTAINER'),
('REC-VOL-RANGE', 'CHARGE PER CARTON RECEIEVED', 'RECEIVING', 'VOLUME_RANGES'),
('STOR-VOL-RANGE', 'CHARGE PER CARTON PER MONTH CALCULATED WEEKELY', 'STORAGE', 'VOLUME_RANGES'),
('CROSS-DOCK-SERV', 'AVAILABLE RATE TO BE DETERMINED', 'RECEIVING', 'TBD'),
('LIVE-UNLOAD', 'AVAILABLE RATE TO BE DETERMINED', 'RECEIVING', 'TBD'),
('ORDERS-PROCESSED', 'PER ORDERS PROECESSED', 'ORD_PROC', 'ORDER'),
('ORDERS-CANCELLED', 'PER ORDERS CANCELLED', 'ORD_PROC', 'ORDER_CANCEL'),
('SEP-SEG-AFTER-5-SKU', 'SEPARATE SEGREGATE AFTER 5 SKUS', 'ORD_PROC', 'UNIT'),
('STRAP-SMAL-APPL', 'STRAPPING SMALL APPLIANCE', 'ORD_PROC', 'UNIT'),
('STRAP-LARGE-APPL', 'STRAPPING LARGE APPLIANCE', 'ORD_PROC', 'UNIT'),
('CART-LABEL-SHIP-TO-LABEL', 'CARTON LABEL / SHIP TO LABEL', 'ORD_PROC', 'UNIT'),
('UPS-FED-EX', 'UPS / FED EX', 'ORD_PROC', 'UNIT'),
('UCC128', 'UCC128', 'ORD_PROC', 'UNIT'),
('STRETCH-WRAP', 'PALLET W/STRRETCH-WRAP INC PALLET', 'ORD_PROC', 'PALLET'),
('REC-CNTR', 'PER CONTAINERS RECEIVED', 'RECEIVING', 'CONTAINER'),
('REC-FLAT-MONTH', 'RECEIVING FLAT MONTHLY RATE', 'RECEIVING', 'MONTH'),
('STOR-VOL-CUR', 'CHARGE FOR ONLY CURRENT CARTON VOLUME', 'STORAGE', 'VOLUME_CURRENT'),
('STOR-PAL-CUR', 'CHARGE FOR ONLY CURRENT PALLET COUNT', 'STORAGE', 'PALLET_CURRENT'),
('STOR-CART-CUR', 'CHARGE FOR ONLY CURRENT CARTON COUNT', 'STORAGE', 'CARTON_CURRENT'),
('SPEC-PROJ', 'CHARGES FOR SPECIAL PROJECTS', 'ORD_PROC', 'UNIT'),
('PRICE-TICKET', 'APPLY PRICE TICKET', 'ORD_PROC', 'UNIT'),
('RM-PRICE-TICK', 'REMOVE PRICE TICKET', 'ORD_PROC', 'UNIT'),
('RM-AP-PRICE-TICK', 'REMOVE AND APPLY PRICE TICKET', 'ORD_PROC', 'UNIT'),
('RPK-PNP', 'REPACK / PICK AND PACK', 'ORD_PROC', 'UNIT'),
('HANG-GARM', 'HANG INDIVIDUAL GARMENT', 'ORD_PROC', 'UNIT'),
('GARM-BAG', 'INDIVIDUAL GARMENT INTO BAG', 'ORD_PROC', 'UNIT'),
('RM-POLY-BAG', 'REMOVE POLY BAG', 'ORD_PROC', 'UNIT'),
('SORT-PCS', 'SORT PIECES', 'ORD_PROC', 'UNIT'),
('RM-STRAPS', 'REMOVE STRAPS', 'ORD_PROC', 'UNIT'),
('RUBBER-BAND', 'RUBBER BAND', 'ORD_PROC', 'UNIT');

-- Jon
-- Adding UOMs to ctn_sum

ALTER TABLE `ctn_sum_mk`  ADD `uom` INT(3) NULL DEFAULT NULL AFTER `vol`;
ALTER TABLE `ctn_sum_shp`  ADD `uom` INT(3) NULL DEFAULT NULL AFTER `vol`;
ALTER TABLE `ctn_sum`  ADD `uom` INT(3) NULL DEFAULT NULL AFTER `vol`;

ALTER TABLE `inv_vol_rates` CHANGE `chg_cd_id` `uom` VARCHAR(25) NOT NULL;

INSERT INTO `charge_cd_mstr` (`chg_cd`, `chg_cd_des`, `chg_cd_type`, `chg_cd_uom`) VALUES
('STOR-SMALL-CART', 'STORAGE SMALL CARTON', 'STORAGE', 'MONTHLY_SMALL_CARTON'),
('STOR-MEDIUM-CART', 'STORAGE MEDIUM CARTON', 'STORAGE', 'MONTHLY_MEDIUM_CARTON'),
('STOR-LARGE-CART', 'STORAGE LARGE CARTON', 'STORAGE', 'MONTHLY_LARGE_CARTON'),
('REC-SMALL-CART', 'RECEIVE SMALL CARTON', 'RECEIVING', 'MONTHLY_SMALL_CARTON'),
('REC-MEDIUM-CART', 'RECEIVE MEDIUM CARTON', 'RECEIVING', 'MONTHLY_MEDIUM_CARTON'),
('REC-LARGE-CART', 'RECEIVE LARGE CARTON', 'RECEIVING', 'MONTHLY_LARGE_CARTON');

ALTER TABLE `inv_vol_rates` CHANGE `category` `category` VARCHAR(10)
CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- delete where charge uom eaquals volume range
DELETE FROM `charge_cd_mstr` WHERE `chg_cd_uom` = 'VOLUME_RANGES';

ALTER TABLE `inv_vol_rates` DROP `rate`;

ALTER TABLE inv_vol_rates DROP PRIMARY KEY;

ALTER TABLE `inv_vol_rates`
ADD UNIQUE  (`cust_id`, `category`, `uom`);

INSERT INTO `inv_vol_rates` (`cust_id`, `category`, `uom`, `min_vol`, `maxinv_his_month_vol`)
VALUES ('10003', 'RECEIVING', 'MONTHLY_SMALL_CARTON', NULL, '3.5'),
('10003', 'RECEIVING', 'MONTHLY_MEDIUM_CARTON', '3.5', '6.5'),
('10003', 'RECEIVING', 'MONTHLY_LARGE_CARTON', '6.5', NULL),
('10003', 'STORAGE', 'MONTHLY_SMALL_CARTON', NULL, '3.5'),
('10003', 'STORAGE', 'MONTHLY_MEDIUM_CARTON', '3.5', '6.5'),
('10003', 'STORAGE', 'MONTHLY_LARGE_CARTON', '6.5', NULL);


-- Raji
-- 05/09/2016

CREATE TABLE inv_his_month
(
id BIGINT(11) NOT NULL AUTO_INCREMENT ,
 inv_id INT(8) NOT NULL ,
 cust_id INT(5) NOT NULL ,
 inv_date DATE NOT NULL ,
 inv_sts TINYINT(1) NOT NULL DEFAULT '1' ,
 PRIMARY KEY (id),
 INDEX (inv_id),
 INDEX(cust_id)
 )ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

INSERT INTO charge_cd_mstr (chg_cd, chg_cd_des, chg_cd_type, chg_cd_uom)
VALUES
('STOR-MONTH-VOL', 'CHARGED PER CARTONS MONTHLY VOLUME', 'STORAGE', 'MONTHLY_VOLUME');


-- Raji
-- 05/09/2016


-- Update the existing LABOR-FEE to RUSH-LABOR in 'OTHER-SERV' category
UPDATE  charge_cd_mstr
SET     chg_cd = 'RUSH-LABOR',
        chg_cd_des = 'RUSH LABOR'
WHERE   chg_cd_type = 'OTHER_SERV'
AND     chg_cd = 'LABOR-FEE';

-- Add new OVERTIME-LABOR charge code to 'OTHER-SERV' category
INSERT INTO charge_cd_mstr (chg_cd, chg_cd_des, chg_cd_type, chg_cd_uom)
VALUES
('OVERTIME-LABOR', 'OVERTIME LABOR', 'OTHER_SERV', 'LABOR');


CREATE TABLE `ord_sum_ctn` (
  `cust_id` int(5) NOT NULL,
  `dt` date NOT NULL,
  `val` float NOT NULL
 )ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE `ord_sum_lbl` (
  `cust_id` int(5) NOT NULL,
  `dt` date NOT NULL,
  `val` float NOT NULL
 )ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE `ord_sum_ord` (
  `cust_id` int(5) NOT NULL,
  `dt` date NOT NULL,
  `val` float NOT NULL
 )ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE `ord_sum_plt` (
  `cust_id` int(5) NOT NULL,
  `dt` date NOT NULL,
  `val` float NOT NULL
 )ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE `ord_sum_vol` (
  `cust_id` int(5) NOT NULL,
  `dt` date NOT NULL,
  `val` float NOT NULL
 )ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `ord_sum_ctn`
  ADD PRIMARY KEY (`cust_id`,`dt`),
  ADD KEY `dt` (`dt`);

ALTER TABLE `ord_sum_lbl`
  ADD PRIMARY KEY (`cust_id`,`dt`),
  ADD KEY `dt` (`dt`);

ALTER TABLE `ord_sum_ord`
  ADD PRIMARY KEY (`cust_id`,`dt`),
  ADD KEY `dt` (`dt`);

ALTER TABLE `ord_sum_plt`
  ADD PRIMARY KEY (`cust_id`,`dt`),
  ADD KEY `dt` (`dt`);

ALTER TABLE `ord_sum_vol`
  ADD PRIMARY KEY (`cust_id`,`dt`),
  ADD KEY `dt` (`dt`);

ALTER TABLE `ord_sum_ctn`  ADD `order_nbr` INT(10) NOT NULL  AFTER `cust_id`;
ALTER TABLE ord_sum_ctn DROP PRIMARY KEY;
ALTER TABLE `ord_sum_ctn` ADD INDEX(`order_nbr`);
ALTER TABLE `ord_sum_ctn` ADD PRIMARY KEY (`cust_id`, `order_nbr`, `dt`);

ALTER TABLE `ord_sum_lbl`  ADD `order_nbr` INT(10) NOT NULL  AFTER `cust_id`;
ALTER TABLE ord_sum_lbl DROP PRIMARY KEY;
ALTER TABLE `ord_sum_lbl` ADD INDEX(`order_nbr`);
ALTER TABLE `ord_sum_lbl` ADD PRIMARY KEY (`cust_id`, `order_nbr`, `dt`);

ALTER TABLE `ord_sum_ord`  ADD `order_nbr` INT(10) NOT NULL  AFTER `cust_id`;
ALTER TABLE ord_sum_ord DROP PRIMARY KEY;
ALTER TABLE `ord_sum_ord` ADD INDEX(`order_nbr`);
ALTER TABLE `ord_sum_ord` ADD PRIMARY KEY (`cust_id`, `order_nbr`, `dt`);

ALTER TABLE `ord_sum_plt`  ADD `order_nbr` INT(10) NOT NULL  AFTER `cust_id`;
ALTER TABLE ord_sum_plt DROP PRIMARY KEY;
ALTER TABLE `ord_sum_plt` ADD INDEX(`order_nbr`);
ALTER TABLE `ord_sum_plt` ADD PRIMARY KEY (`cust_id`, `order_nbr`, `dt`);

ALTER TABLE `ord_sum_vol`  ADD `order_nbr` INT(10) NOT NULL  AFTER `cust_id`;
ALTER TABLE ord_sum_vol DROP PRIMARY KEY;
ALTER TABLE `ord_sum_vol` ADD INDEX(`order_nbr`);
ALTER TABLE `ord_sum_vol` ADD PRIMARY KEY (`cust_id`, `order_nbr`, `dt`);

CREATE TABLE `ord_sum_wo` (
  `order_nbr` int(10) NOT NULL,
  `cust_id` int(5) NOT NULL,
  `dt` date NOT NULL,
  `chg_cd` varchar(50) NOT NULL,
  `labor` int(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `ord_sum_wo`
  ADD PRIMARY KEY (`order_nbr`,`cust_id`,`dt`,`chg_cd`),
  ADD KEY `dt` (`dt`),
  ADD KEY `chg_cd` (`chg_cd`),
  ADD KEY `cust_id` (`cust_id`);

-- Jon
-- Receiving summary tables

CREATE TABLE `rcv_sum_cntr` (
  `cust_id` int(5) NOT NULL,
  `rcv_nbr` int(8) NOT NULL,
  `dt` date NOT NULL,
  `val` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `rcv_sum_ctn` (
  `rcv_nbr` int(8) NOT NULL,
  `cust_id` int(5) NOT NULL,
  `dt` date NOT NULL,
  `val` int(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `rcv_sum_vol` (
  `rcv_nbr` int(8) NOT NULL,
  `cust_id` int(5) NOT NULL,
  `dt` date NOT NULL,
  `vol` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `rcv_sum_cntr`
  ADD UNIQUE KEY `cust_id` (`cust_id`,`rcv_nbr`,`dt`),
  ADD KEY `rcv_nbr` (`rcv_nbr`),
  ADD KEY `dt` (`dt`);

ALTER TABLE `rcv_sum_ctn`
  ADD PRIMARY KEY (`rcv_nbr`),
  ADD KEY `dt` (`dt`),
  ADD KEY `cust_id` (`cust_id`);

ALTER TABLE `rcv_sum_vol`
  ADD PRIMARY KEY (`rcv_nbr`),
  ADD KEY `dt` (`dt`),
  ADD KEY `cust_id` (`cust_id`);


-- Raji
-- 05/11/2016

-- Add page params for "List Invoices" page
INSERT INTO page_params (pageID, name, value, active)
SELECT    id AS pageID,
          "editable" AS name,
          "display" AS value,
          1 AS active
FROM      pages
WHERE     hiddenName = "listInvoices";


-- Raji
-- 05/13/2016

-- Add type field to wrk_hrs_rcv , wrk_hrs_ord_prc , wrks_hr_wo

ALTER TABLE wrk_hrs_rcv ADD type ENUM('r','o') NOT NULL AFTER rcv_num;

ALTER TABLE wrk_hrs_ord_prc ADD `type` ENUM('r','o') NOT NULL AFTER scan_ord_nbr;

ALTER TABLE wrk_hrs_wo ADD type ENUM('r','o') NOT NULL AFTER wo_id;

ALTER TABLE wrk_hrs_ord_prc CHANGE id id BIGINT(11) NOT NULL AUTO_INCREMENT;


-- Add inv_id, inv_num and inv_sts to wrk_hrs_wo

ALTER TABLE wrk_hrs_wo
ADD inv_id INT(8) NOT NULL AFTER dt,
ADD inv_num INT(10) NULL DEFAULT NULL AFTER inv_id,
ADD INDEX (inv_id), ADD INDEX (inv_num);

-- Raji
-- 05/15/2016

-- Add inv_num field to inv_his_month table
ALTER TABLE `inv_his_month` ADD `inv_num` INT(10) NULL DEFAULT NULL AFTER `inv_id`;


-- Jon

-- Invoice Month History table status wasn't defaulted to active
ALTER TABLE `inv_his_month` CHANGE `inv_sts`
`inv_sts` VARCHAR(1) NOT NULL DEFAULT '1';

-- Adding a type field to monthly history so it can be used for storage too
ALTER TABLE `inv_his_month`  ADD `type` VARCHAR(1) NOT NULL  AFTER `inv_num`;

-- Updating primary key
ALTER TABLE `inv_his_month`
DROP PRIMARY KEY, ADD PRIMARY KEY (`cust_id`, `inv_date`, `type`) USING BTREE;

ALTER TABLE `inv_his_ord_prc`  ADD `ord_num` BIGINT(10) NOT NULL
AFTER `ord_id`,  ADD   INDEX  (`ord_num`);


-- Modifying Order Processing Check-Out menu link so that it refer to NO UCC page

UPDATE pages
SET    method = "orderEntry"
WHERE  hiddenName = "orderProcessingCheckOut";

-- Add page params for "Charge Code Master"
INSERT INTO page_params (pageID, name, value, active)
SELECT    id AS pageID,
          "process" AS name,
          "orderProcessCheckOut" AS value,
          1 AS active
FROM      pages
WHERE     hiddenName = "orderProcessingCheckOut";


-- Raji
-- 05/15/2016

-- Add inv_id, inv_num and inv_sts to wrk_hrs_rcv
ALTER TABLE wrk_hrs_rcv
ADD inv_id INT(8) NOT NULL AFTER dt,
ADD inv_num INT(10) NULL DEFAULT NULL AFTER inv_id,
ADD INDEX (inv_id), ADD INDEX (inv_num);


-- Add inv_id, inv_num and inv_sts to wrk_hrs_ord_prc
ALTER TABLE wrk_hrs_ord_prc
ADD inv_id INT(8) NOT NULL AFTER dt,
ADD inv_num INT(10) NULL DEFAULT NULL AFTER inv_id,
ADD INDEX (inv_id), ADD INDEX (inv_num);

-- Add receiving date to inv_his_rcv table
ALTER TABLE `inv_his_rcv` ADD `recv_dt` DATE NOT NULL AFTER `rcv_nbr`;


-- Tri Le
-- 05/16/2016

-- Change field Length = 50 character in table history_models
ALTER TABLE `history_models` CHANGE `displayName` `displayName` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- Raji
-- 05/16/2016

-- Add inv_sts field to wrk_hrs_rcv, wrk_hrs_ord_proc, wrk_hrs_wo
ALTER TABLE `wrk_hrs_rcv` ADD `inv_sts` TINYINT NOT NULL DEFAULT '1' AFTER `create_by`;

ALTER TABLE `wrk_hrs_ord_prc` ADD `inv_sts` TINYINT NOT NULL DEFAULT '1' AFTER `create_by`;

ALTER TABLE `wrk_hrs_wo` ADD `inv_sts` TINYINT NOT NULL DEFAULT '1' AFTER `create_by`;


-- Add index to create_by
ALTER TABLE `wrk_hrs_ord_prc` ADD INDEX(`create_by`);

ALTER TABLE `wrk_hrs_rcv` ADD INDEX(`create_by`);

-- Phong Tran
-- 05/18/2016

ALTER TABLE attribute_group ADD category VARCHAR(50) NOT NULL AFTER id;

UPDATE  attribute_group
SET     category = 'item';

INSERT INTO `attribute_group` (`category`, `name`, `short_name`, `description`)
    VALUES ('batch', 'description', 'description', 'Batch Description');

CREATE TABLE `batches_meta` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `attribute_group_id` int(11) NOT NULL,
  `value` varchar(2555) COLLATE utf8_unicode_ci NOT NULL,
  `option` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Vadzim
-- 05/20/2016

-- removing constraints on clientordernumber field in neworder table

ALTER TABLE neworder DROP INDEX clientordernumber;

-- Vadzim
-- 05/23/2016

-- making scanordernumber field unique in neworder table

ALTER TABLE neworder DROP INDEX scanordernumber;
ALTER TABLE neworder ADD UNIQUE scanordernumber (scanordernumber);

-- Vadzim
-- 5/30/2016

-- making 1 a default value for active field in groups and group_pages tables

ALTER TABLE groups CHANGE active active TINYINT(1) NULL DEFAULT '1';
ALTER TABLE group_pages CHANGE active active TINYINT(1) NULL DEFAULT '1';

-- adding constraints to group_pages table on groupID and pageID fields

ALTER TABLE group_pages ADD UNIQUE KEY uniqueGroupPage (groupID, pageID);

-- adding User Group Based Access

UPDATE    groups
SET       groupName = 'WMS',
		  hiddenName = 'wms'
WHERE     hiddenName = 'manager';

UPDATE    groups
SET       groupName = 'Search Data',
		  hiddenName = 'searchData'
WHERE     hiddenName = 'CSR';

UPDATE    groups
SET       groupName = 'Search Logs',
		  hiddenName = 'searchLogs'
WHERE     hiddenName = 'warehouseStaff';

UPDATE    groups
SET       groupName = 'Invoice',
		  hiddenName = 'invoice'
WHERE     hiddenName = 'dataEntry';

UPDATE    groups
SET       groupName = 'NSI',
		  hiddenName = 'nsi'
WHERE     hiddenName = 'pickingStaff';

UPDATE    groups
SET       groupName = 'Edit Data',
		  hiddenName = 'editData'
WHERE     hiddenName = 'shippingStaff';

UPDATE    groups
SET       groupName = 'Administration',
		  hiddenName = 'administration'
WHERE     hiddenName = 'invoicingStaff';

UPDATE    groups
SET       groupName = 'Inventory Control',
		  hiddenName = 'inventoryControl',
          description = NULL
WHERE     hiddenName = 'mezzanineAdmin';

INSERT INTO groups (groupName, hiddenName) VALUES
('Cycle Count', 'cycleCount');

UPDATE    group_pages
SET       active = 0;

INSERT INTO group_pages (groupID, pageID)
    SELECT    g.id AS groupID,
              p.id AS pageID
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus s ON s.id = sp.subMenuID
    JOIN      groups g ON g.hiddenName = s.hiddenName
    WHERE     g.active
    AND       sp.active
ON DUPLICATE KEY UPDATE
	active = 1;

INSERT INTO group_pages (groupID, pageID)
    SELECT    g.id AS groupID,
              pageID
    FROM      submenus s
    JOIN      submenu_pages sp ON sp.subMenuID = s.id
    JOIN      groups g
    WHERE     s.hiddenName = 'signOut'
    AND       g.active
    AND       sp.active
ON DUPLICATE KEY UPDATE
	active = 1;

-- Vadzim
-- 05/30/2016

-- creating a table that will store discrepant split cartons created in
-- Picking Check Out page (siblings of reserved cartons)

CREATE TABLE inventory_split_discrepancies (
    id BIGINT(11) NOT NULL AUTO_INCREMENT,
    ord_nbr INT(10) UNSIGNED ZEROFILL NOT NULL,
    ctn_id BIGINT(10) NOT NULL,
    ctn_sts tinyint(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (id),
    KEY ord_nbr (ord_nbr),
    KEY ctn_id (ctn_id),
    KEY unique_ord_ctn (ord_nbr, ctn_id)
) ENGINE=InnoDB;

-- Raji
-- 05/31/2016
-- create ctn_log_sum table

CREATE TABLE ctn_log_sum (
    carton_id INT(10) NOT NULL ,
    batch_id INT(8) NOT NULL ,
    statusID INT(2) NOT NULL ,
    orderID INT(6) NOT NULL ,
    uom INT(3) NOT NULL ,
    last_active DATE NOT NULL ,
    PRIMARY KEY (carton_id),
    INDEX (batch_id),
    INDEX (orderID)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;


-- modify the field Name
ALTER TABLE ctn_log_sum CHANGE statusID status_id INT(2) NOT NULL;


ALTER TABLE ctn_log_sum CHANGE orderID order_id INT(6) NOT NULL;

-- modify the field order_id
ALTER TABLE ctn_log_sum CHANGE order_id order_id INT(6) NULL DEFAULT NULL;


-- Raji
-- 06/03/2016
-- Add log_id field to ctn_log_sum
ALTER TABLE `ctn_log_sum` ADD `log_id` INT(8) NOT NULL AFTER `uom`;

ALTER TABLE `ctn_log_sum` ADD INDEX(`log_id`);

ALTER TABLE `ctn_log_sum` DROP INDEX `orderID`, ADD INDEX `order_id` (`order_id`);


-- Change the field type order_id in ctn_log_sum table
ALTER TABLE `ctn_log_sum` CHANGE `order_id` `order_id` INT(10) NULL DEFAULT NULL;

-- Change the field type order_id in inventory_cartons table
ALTER TABLE `inventory_cartons` CHANGE `orderID` `orderID` INT(10) NULL DEFAULT NULL;


-- Add index status_id, uom, last_active
ALTER TABLE `ctn_log_sum` ADD INDEX(`status_id`);

-- Vadzim
-- 6/7/2016
-- increase width of displayName field in history_models table to 50 characters

ALTER TABLE history_models CHANGE displayName displayName VARCHAR(50) NOT NULL;



-- Raji
-- 06/09/2016
-- update the companyName from Docks Rd to Burlington

UPDATE  company_address
SET     companyName = 'SELDAT Burlington',
        city = 'Burlington',
        address = '1900 River Rd',
        zip = '08016',
        phone = '732-348-0000'
WHERE   companyName = 'SELDAT NJ Docks Corner';

-- Jon
-- Table to populate ctn_log_sum table

CREATE TABLE `log_sum` (
  `carton_id` int(10) NOT NULL,
  `log_id` int(8) NOT NULL,
  `log_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `log_sum`
  ADD PRIMARY KEY (`carton_id`),
  ADD KEY `logID` (`log_id`);

ALTER TABLE `ctn_log_sum` DROP INDEX `orderID`, ADD INDEX `order_id` (`order_id`);


INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    'Order Carton Corrections' AS displayName,
          maxOrder + 1 AS displayOrder,
          'updateShippedOrders' AS hiddenName,
          'orders' AS `class`,
          'updateShipped' AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = 'administration'
    AND       sm.active
        LIMIT 1
) m
WHERE     sm.hiddenName = 'administration'
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
WHERE p.hiddenName = 'updateShippedOrders';

-- Jon: new table for tracking which logs are for cartons that have been
-- modified by shipped order cartons page

CREATE TABLE `ship_ctn_mod_logs` (
  `logID` int(8) NOT NULL,
  `orderNum` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `ship_ctn_mod_logs`
  ADD PRIMARY KEY (`logID`),
  ADD KEY `orderNum` (`orderID`);

-- Vadzim
-- 06/13/2016
-- adding "Back To Stock" locations to locations table

INSERT INTO locations (displayName, locationNumber, isShipping, isMezzanine, warehouseID, cubicFeet, distance)
SELECT    'Back To Stock' AS displayName,
		  0 AS locationNumber,
          0 AS isShipping,
          0 AS isMezzanine,
		  id AS warehouseID,
          0 AS cubicFeet,
          0 AS distance
FROM      warehouses;

-- Jon
ALTER TABLE `ship_ctn_mod_logs`
CHANGE `orderNum` `orderID` BIGINT(10) NOT NULL;

-- Shipped Carton Admin Group
INSERT INTO `groups` (`id`, `groupName`, `hiddenName`, `description`, `active`)
VALUES (NULL, 'Shipped Carton Admin', 'shpCtnAdmin',
'Will have the ability to set cartons to shipped if they are associated with
a shipped order.', '1');

ALTER TABLE `ship_ctn_mod_logs` ADD `updateDT`
TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP FIRST;

ALTER TABLE ship_ctn_mod_logs DROP PRIMARY KEY;

ALTER TABLE `ship_ctn_mod_logs`
  ADD PRIMARY KEY (`updateDT`,`logID`),
  ADD KEY `logID` (`logID`);

-- Vadzim
-- 06/15/2016
-- dropping inventory_split_discrepancies table as unused

DROP TABLE IF EXISTS inventory_split_discrepancies;

-- Vadzim
-- 06/16/2016
-- renaming group_users table to client_users

RENAME TABLE group_users TO client_users;

-- Vadzim
-- 06/20/2016
-- moving "Generate License Plates", "Generate Work Order Labels", "Dashboard
-- Receiving", "Dashboard Shipping", "Summary Report" links to "Search Data" submenu

UPDATE    pages p
JOIN      submenu_pages sp ON sp.pageID = p.id
JOIN      submenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = 'searchData'
    AND       sm.active
) o
SET       submenuID = sm.id,
          p.displayOrder = maxOrder + 1
WHERE     p.hiddenName = 'generateLicensePlates'
AND       sm.hiddenName = 'searchData'
AND       sm.active;

UPDATE    pages p
JOIN      submenu_pages sp ON sp.pageID = p.id
JOIN      submenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = 'searchData'
    AND       sm.active
) o
SET       submenuID = sm.id,
          p.displayOrder = maxOrder + 1
WHERE     p.hiddenName = 'generateWorkOrderLabels'
AND       sm.hiddenName = 'searchData'
AND       sm.active;

UPDATE    pages p
JOIN      submenu_pages sp ON sp.pageID = p.id
JOIN      submenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = 'searchData'
    AND       sm.active
) o
SET       submenuID = sm.id,
          p.displayOrder = maxOrder + 1
WHERE     p.hiddenName = 'dashboardReceiving'
AND       sm.hiddenName = 'searchData'
AND       sm.active;

UPDATE    pages p
JOIN      submenu_pages sp ON sp.pageID = p.id
JOIN      submenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = 'searchData'
    AND       sm.active
) o
SET       submenuID = sm.id,
          p.displayOrder = maxOrder + 1
WHERE     p.hiddenName = 'dashboardShipping'
AND       sm.hiddenName = 'searchData'
AND       sm.active;

UPDATE    pages p
JOIN      submenu_pages sp ON sp.pageID = p.id
JOIN      submenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = 'searchData'
    AND       sm.active
) o
SET       submenuID = sm.id,
          p.displayOrder = maxOrder + 1
WHERE     p.hiddenName = 'summaryReport'
AND       sm.hiddenName = 'searchData'
AND       sm.active;

-- Vadzim
-- 06/22/2016
-- adding Receiving Manager, Receiving Supervisor, Receiving Department user groups

INSERT INTO groups (groupName, hiddenName) VALUES
('Receiving Manager', 'receivingManager'),
('Receiving Supervisor', 'receivingSupervisor'),
('Receiving Department', 'receivingDepartment');

INSERT INTO group_pages (groupID, pageID)
    SELECT    g.id AS groupID,
              pageID
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      groups g
    WHERE     p.hiddenName = 'scanContainer'
    AND       g.hiddenName IN ('receivingManager', 'receivingSupervisor')
    AND       sp.active
ON DUPLICATE KEY UPDATE
	active = 1;

INSERT INTO group_pages (groupID, pageID)
    SELECT    g.id AS groupID,
              pageID
    FROM      submenus s
    JOIN      submenu_pages sp ON sp.subMenuID = s.id
    JOIN      groups g
    WHERE     s.hiddenName = 'signOut'
    AND       g.hiddenName IN ('receivingManager', 'receivingSupervisor')
    AND       sp.active
ON DUPLICATE KEY UPDATE
	active = 1;

INSERT INTO group_pages (groupID, pageID)
    SELECT    g.id AS groupID,
              pageID
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      groups g
    WHERE     p.hiddenName IN ('receivingContainer', 'rCLogs', 'receiving/BackToStock', 'scanToLocation')
    AND       g.hiddenName = 'receivingDepartment'
    AND       sp.active
ON DUPLICATE KEY UPDATE
	active = 1;

INSERT INTO group_pages (groupID, pageID)
    SELECT    g.id AS groupID,
              pageID
    FROM      submenus s
    JOIN      submenu_pages sp ON sp.subMenuID = s.id
    JOIN      groups g
    WHERE     s.hiddenName IN ('searchData', 'signOut')
    AND       g.hiddenName = 'receivingDepartment'
    AND       sp.active
ON DUPLICATE KEY UPDATE
	active = 1;

-- changing table collation to utf8_general_ci

ALTER TABLE batches_meta CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE locked_cartons CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE discrepancy_cartons CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE count_items CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE cycle_count CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;

-- Raji
-- 06/24/2016
-- Add charge codes for charge By Volume for Shipping

INSERT INTO `charge_cd_mstr` (`chg_cd`, `chg_cd_des`, `chg_cd_type`, `chg_cd_uom`) VALUES
('SHIP-SMALL-CART', 'SHIPPING SMALL CARTON', 'ORD_PROC', 'MONTHLY_SMALL_CARTON'),
('SHIP-MEDIUM-CART', 'SHIPPING MEDIUM CARTON', 'ORD_PROC', 'MONTHLY_MEDIUM_CARTON'),
('SHIP-LARGE-CART', 'SHIPPING LARGE CARTON', 'ORD_PROC', 'MONTHLY_LARGE_CARTON');


-- Raji
-- 06/30/2016
-- Charge codes charge By Volume for Storage

INSERT INTO `charge_cd_mstr` (`chg_cd`, `chg_cd_des`, `chg_cd_type`, `chg_cd_uom`) VALUES
('STOR-XL-CART', 'STORAGE XL CARTON', 'STORAGE', 'MONTHLY_XL_CARTON'),
('STOR-XXL-CART', 'STORAGE XXL CARTON', 'STORAGE', 'MONTHLY_XXL_CARTON');


-- Order Processing Charge Codes

INSERT INTO `charge_cd_mstr` (`chg_cd`, `chg_cd_des`, `chg_cd_type`, `chg_cd_uom`)
VALUES
('SHIP-SMALL-CART', 'SHIPPING SMALL CARTON', 'ORD_PROC', 'MONTHLY_SMALL_CARTON'),
('SHIP-MEDIUM-CART', 'SHIPPING MEDIUM CARTON', 'ORD_PROC', 'MONTHLY_MEDIUM_CARTON'),
('SHIP-LARGE-CART', 'SHIPPING LARGE CARTON', 'ORD_PROC', 'MONTHLY_LARGE_CARTON');


-- Add New Charge Codes to Receiving ,Order Processing and Storage

INSERT INTO `charge_cd_mstr` (`chg_cd`, `chg_cd_des`, `chg_cd_type`, `chg_cd_uom`)
VALUES
('RCV-PCS', 'PIECES RECEIVED', 'RECEIVING', 'PIECES'),
('RCV-PLT', 'RECEIVING PALLET', 'RECEIVING', 'PALLET'),
('SHIP-PCS', 'PIECES SHIPPED', 'ORD_PROC', 'PIECES'),
('STOR-PCS', 'STORAGE PIECES', 'STORAGE', 'PIECES');



-- Receiving Summary Tables

CREATE TABLE `rcv_sum_pcs` (
  `rcv_nbr` int(8) NOT NULL,
  `cust_id` int(5) NOT NULL,
  `dt` date NOT NULL,
  `val` int(7) NOT NULL
) ENGINE=InnoDB;

ALTER TABLE `rcv_sum_pcs`
  ADD PRIMARY KEY (`rcv_nbr`),
  ADD KEY `dt` (`dt`),
  ADD KEY `cust_id` (`cust_id`);


CREATE TABLE `rcv_sum_plt` (
  `rcv_nbr` int(8) NOT NULL,
  `cust_id` int(5) NOT NULL,
  `dt` date NOT NULL,
  `val` int(7) NOT NULL
) ENGINE=InnoDB;

ALTER TABLE `rcv_sum_plt`
  ADD PRIMARY KEY (`rcv_nbr`),
  ADD KEY `dt` (`dt`),
  ADD KEY `cust_id` (`cust_id`);

-- Order Processing Summary Tables

CREATE TABLE `ord_sum_pcs` (
  `cust_id` int(5) NOT NULL,
  `order_nbr` INT(10) NOT NULL,
  `dt` date NOT NULL,
  `val` float NOT NULL
)ENGINE = InnoDB;


ALTER TABLE `ord_sum_pcs`
  ADD KEY `dt` (`dt`),
  ADD PRIMARY KEY (`cust_id`, `order_nbr`, `dt`),
  ADD INDEX(`order_nbr`);

-- Vadzim
-- 7/7/2016
-- adding CSR user group

INSERT INTO groups (groupName, hiddenName) VALUES
('CSR', 'csr');

INSERT INTO group_pages (groupID, pageID)
    SELECT    g.id AS groupID,
              pageID
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      groups g
    WHERE     p.hiddenName IN ('orderCheckIn', 'orderCheckOut', 'routedCheckIn', 'routedCheckOut', 'addShipmentBillOfLadings')
    AND       g.hiddenName = 'csr'
    AND       sp.active
ON DUPLICATE KEY UPDATE
	active = 1;

INSERT INTO group_pages (groupID, pageID)
    SELECT    g.id AS groupID,
              pageID
    FROM      submenus s
    JOIN      submenu_pages sp ON sp.subMenuID = s.id
    JOIN      groups g
    WHERE     s.hiddenName IN ('searchData', 'signOut')
    AND       g.hiddenName = 'csr'
    AND       sp.active
ON DUPLICATE KEY UPDATE
	active = 1;

-- Vadzim
-- 7/7/2016
-- adding create_dt field to neworder table

ALTER TABLE neworder ADD create_dt TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

UPDATE    neworder
SET       create_dt = NULL;

-- Raji
-- 07/01/2016
-- inv_vol_rates table - For live site clients

-- Cayre  FA
INSERT INTO `inv_vol_rates`
(`cust_id`, `category`, `uom`, `min_vol`, `max_vol`)
VALUES
('11243', 'RECEIVING', 'MONTHLY_SMALL_CARTON', NULL, '1.5'),
('11243', 'RECEIVING', 'MONTHLY_MEDIUM_CARTON', '1.5', NULL),
('11243', 'ORD_PROC', 'MONTHLY_SMALL_CARTON', NULL, '1.5'),
('11243', 'ORD_PROC', 'MONTHLY_MEDIUM_CARTON', '1.5', NULL);


-- Cyberkids NJ
INSERT INTO `inv_vol_rates`
(`cust_id`, `category`, `uom`, `min_vol`, `max_vol`)
VALUES
('10007', 'STORAGE', 'MONTHLY_SMALL_CARTON', '0', '1'),
('10007', 'STORAGE', 'MONTHLY_MEDIUM_CARTON', '1', '3'),
('10007', 'STORAGE', 'MONTHLY_LARGE_CARTON', '3', '5'),
('10007', 'STORAGE', 'MONTHLY_XL_CARTON', '5', '7'),
('10007', 'STORAGE', 'MONTHLY_XXL_CARTON', '7', '8');

-- Concept NYC  NJ
INSERT INTO `inv_vol_rates`
(`cust_id`, `category`, `uom`, `min_vol`, `max_vol`)
VALUES
('10005', 'RECEIVING', 'MONTHLY_SMALL_CARTON', NULL, '4'),
('10005', 'RECEIVING', 'MONTHLY_MEDIUM_CARTON', '4', NULL),
('10005', 'ORD_PROC', 'MONTHLY_SMALL_CARTON', NULL, '4'),
('10005', 'ORD_PROC', 'MONTHLY_MEDIUM_CARTON', '4', NULL);


-- WAPPLIANCE NJ
INSERT INTO `inv_vol_rates`
(`cust_id`, `category`, `uom`, `min_vol`, `maxinv_his_month_vol`)
VALUES
('10003', 'ORD_PROC', 'MONTHLY_SMALL_CARTON', NULL, '3.5'),
('10003', 'ORD_PROC', 'MONTHLY_MEDIUM_CARTON', '3.5', '6.5'),
('10003', 'ORD_PROC', 'MONTHLY_LARGE_CARTON', '6.5', NULL);


-- Raji
-- 07/08/2016
-- storage monthly pallet

INSERT INTO `charge_cd_mstr` (`chg_cd`, `chg_cd_des`, `chg_cd_type`, `chg_cd_uom`)
VALUES
('STOR-MONTH-PALLET', 'CHARGED PER PALLETS MONTHLY', 'STORAGE', 'MONTHLY_PALLET');

-- Storage Summary Tables

CREATE TABLE `stor_sum_plt` (
  `plate` int(9) NOT NULL,
  `cust_id` int(5) DEFAULT NULL,
  `start_log_id` int(8) DEFAULT NULL,
  `rcv_dt` date DEFAULT NULL,
  `last_log_id` int(8) DEFAULT NULL,
  `last_active` date DEFAULT NULL
) ENGINE=InnoDB;

ALTER TABLE `stor_sum_plt`
  ADD PRIMARY KEY (`plate`),
  ADD KEY `cust_id` (`cust_id`),
  ADD KEY `start_log_id` (`start_log_id`),
  ADD KEY `rcv_dt` (`rcv_dt`),
  ADD KEY `last_log_id` (`last_log_id`),
  ADD KEY last_active (`last_active`);