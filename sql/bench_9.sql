-- ---------------------------------------------------------
--      wms:      ONLY commands after 9/15/2016 here
--      _default: ONLY commands after 9/15/2016 here
-- ---------------------------------------------------------

-- Duy Nguyen
-- 9/15/2016
-- add proc_out_dt field into outbound_sum table

ALTER TABLE `outbound_sum` ADD `proc_out_dt` TIMESTAMP NOT NULL AFTER `chk_out_dt`;

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
ship_out_dt = VALUES(proc_out_dt);


-- Duy Nguyen
-- 9/15/2016
-- add Inventory Transfer Scanner link to WMS submenu

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Inventory Transfer Scanner" AS displayName,
          maxOrder + 1 AS displayOrder,
          "inventoryTransferScanner" AS hiddenName,
          "scanners" AS `class`,
          "inventoryTransfer" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
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
WHERE p.hiddenName = 'inventoryTransferScanner';

-- Tri Le
-- 10/5/2016
-- Add “Reset Password” page to the “Administration” sub menu.

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
  SELECT    "Reset Password" AS displayName,
            maxOrder + 1 AS displayOrder,
            "resetPassword" AS hiddenName,
            "users" AS `class`,
            "resetPassword" AS `method`,
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
  WHERE p.hiddenName = 'resetPassword';


-- Tri Le
-- 10/5/2016

-- Create Password Administrator group
INSERT INTO groups (
  groupName, hiddenName, description, active
) VALUES (
  "Password Administrator", "passwordAdministrator", "Password Administrator", "1"
) ON DUPLICATE KEY UPDATE
  description = "Password Administrator",
  active = "1";

-- Add reset password page into Password Administrator group

INSERT INTO `group_pages` (`groupID`, `pageID`, `active`)
SELECT    gr.id AS groupID,
          m.pageID AS pageID,
          1 AS active
FROM      groups gr
JOIN      (
            SELECT    p.id AS pageID
            FROM      pages p
            WHERE      p.hiddenName = "resetPassword"
            AND       p.active
          ) m
WHERE     gr.hiddenName = "passwordAdministrator"
AND       gr.active
LIMIT 1;

-- SCC Page

INSERT INTO pages
SET         displayName = 'SCC Stock',
            displayOrder = (
                SELECT p.displayOrder
                FROM pages p
                JOIN submenu_pages sp ON p.id = sp.pageID
                JOIN submenus s ON s.id = sp.submenuID
                WHERE  s.hiddenName = 'inventoryControl'
                ORDER BY p.displayOrder DESC
                LIMIT 1
            ) + 1,
            hiddenName = 'sccStock',
            class = 'inventory',
            method = 'scc',
            red = 0,
            clientAccess = 0,
            app = NULL,
            active = 1;

INSERT INTO submenu_pages
SET         submenuID = (
                SELECT id
                FROM   submenus
                WHERE  hiddenName = 'inventoryControl'
            ),
            pageID = (
                SELECT id
                FROM   pages
                WHERE  hiddenName = 'sccStock'
            );

-- SCC Tables

CREATE TABLE `scc_ctgrs` (
  `id` int(8) NOT NULL,
  `type` varchar(40) NOT NULL,
  `name` varchar(40) NOT NULL,
  `ac` varchar(1) NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `scc_items` (
  `upc_id` int(8) NOT NULL,
  `category_id` int(8) NOT NULL,
  `description` varchar(250) NOT NULL,
  `qty` int(7) NOT NULL,
  `test_qty` int(7) NOT NULL,
  `active` varchar(1) NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `scc_logs` (
  `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `targetID` int(8) NOT NULL,
  `field` varchar(30) NOT NULL,
  `title` varchar(15) DEFAULT NULL,
  `hasTitle` varchar(1) NOT NULL,
  `type` varchar(3) NOT NULL,
  `userID` int(4) NOT NULL,
  `fromVal` varchar(2000) DEFAULT NULL,
  `toVal` varchar(2000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `scc_ctgrs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type` (`type`,`name`);

ALTER TABLE `scc_items`
  ADD PRIMARY KEY (`upc_id`),
  ADD KEY `category_id` (`category_id`);

ALTER TABLE `scc_logs`
  ADD PRIMARY KEY (`dt`,`targetID`,`field`,`type`) USING BTREE,
  ADD KEY `targetID` (`targetID`),
  ADD KEY `field` (`field`),
  ADD KEY `type` (`type`),
  ADD KEY `user_id` (`userID`);

ALTER TABLE `scc_ctgrs`
  MODIFY `id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

-- Tri Le
-- 10/11/2016
-- Add “Download Cartons History” page to the “Administration” sub menu.

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
  SELECT    "Download Cartons History" AS displayName,
            maxOrder + 1 AS displayOrder,
            "downloadCartonHistory" AS hiddenName,
            "scanners" AS `class`,
            "downloadCartonHistory" AS `method`,
            0 AS red,
            1 AS active
  FROM      subMenus sm
    JOIN      (
                SELECT    MAX(p.displayOrder) AS maxOrder
                FROM      pages p
                  JOIN      submenu_pages sp ON sp.pageID = p.id
                  JOIN      submenus sm ON sm.id = sp.subMenuID
                WHERE     sm.hiddenName = "inventoryControl"
                          AND       sm.active
                LIMIT 1
              ) m
  WHERE     sm.hiddenName = "inventoryControl"
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
           WHERE     sm.hiddenName = 'inventoryControl'
         ) m
  WHERE p.hiddenName = 'downloadCartonHistory';

-- Tri Le
-- 10/12/2016

-- Change groupName field to varchar(50).
ALTER TABLE `groups` CHANGE `groupName` `groupName` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- adding Cycle Count Operators user group
INSERT INTO groups (groupName, hiddenName, description, active)
VALUES ("Cycle Count Operators", "cycleCountOperators", "Cycle Count Operators", "1")
ON DUPLICATE KEY UPDATE
  description = "Cycle Count Operators",
  active = "1";

INSERT INTO group_pages (groupID, pageID)
  SELECT    g.id AS groupID,
    pageID
  FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      groups g
  WHERE     p.hiddenName = 'cycleCount'
            AND       g.hiddenName = 'cycleCountOperators'
            AND       sp.active
ON DUPLICATE KEY UPDATE
  active = 1;

-- Addding SCC log fields
ALTER TABLE `scc_logs` ADD `tranID` VARCHAR(255) NOT NULL AFTER `toVal`;
ALTER TABLE `scc_logs` ADD `supplier` VARCHAR(25) NULL DEFAULT NULL AFTER `tranID`;
ALTER TABLE `scc_logs` ADD `isTest` VARCHAR(1) NOT NULL AFTER `supplier`;
ALTER TABLE `scc_logs` ADD `style` VARCHAR(25) NULL DEFAULT NULL AFTER `supplier`;
ALTER TABLE `scc_logs` ADD `requestedBy` VARCHAR(25) NULL DEFAULT NULL AFTER `style`;

-- Three different SCC item statuses
ALTER TABLE `scc_items` CHANGE `active` `active` VARCHAR(12)
CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
ALTER TABLE `scc_items` CHANGE `active` `active` VARCHAR(12) CHARACTER SET latin1
COLLATE latin1_swedish_ci NOT NULL DEFAULT 'Active';

-- Duy Nguyen
-- 12/19/2016
-- add submenu Open Online Orders Report

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
  SELECT    "Open Online Orders Report" AS displayName,
            maxOrder + 1 AS displayOrder,
            "openOnlineOrdersReport" AS hiddenName,
            "onlineOrders" AS `class`,
            "openOnlineOrdersReport" AS `method`,
            0 AS red,
            1 AS active
  FROM      subMenus sm
    JOIN      (
                SELECT    MAX(p.displayOrder) AS maxOrder
                FROM      pages p
                  JOIN      submenu_pages sp ON sp.pageID = p.id
                  JOIN      submenus sm ON sm.id = sp.subMenuID
                WHERE     sm.hiddenName = "onlineOrder"
                          AND       sm.active
                LIMIT 1
              ) m
  WHERE     sm.hiddenName = "onlineOrder"
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
           WHERE     sm.hiddenName = 'onlineOrder'
         ) m
  WHERE p.hiddenName = 'openOnlineOrdersReport';

-- Vadzim
-- 12/27/2016
-- adding Warehouse Transfer tables

CREATE TABLE warehouse_transfers (
    id INT(5) NOT NULL AUTO_INCREMENT,
    transferDate DATE NOT NULL,
    description VARCHAR(250) NOT NULL,
    userID INT(4) NULL DEFAULT NULL,
    outWarehouseID INT(4) NULL DEFAULT NULL,
    inWarehouseID INT(4) NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE description (description),
    KEY userID (userID)
) ENGINE=InnoDB;

CREATE TABLE warehouse_transfer_pallets (
    id INT(10) NOT NULL AUTO_INCREMENT,
    warehouseTransferID INT(5) NOT NULL,
    manifest INT(9) NOT NULL,
    plate INT(9) NOT NULL,
    sts enum('OUT', 'IN') NOT NULL DEFAULT 'OUT',
    outTime TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    inTime  TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    outUserID INT(4) NOT NULL,
    inUserID INT(4) NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY warehouseTransferID (warehouseTransferID),
    UNIQUE plate (plate),
    KEY outUserID (outUserID),
    KEY inUserID (inUserID)
) ENGINE=InnoDB;

-- adding a link for Edit Warehouse Transfers page

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Edit Warehouse Transfers" AS displayName,
          maxOrder + 1 AS displayOrder,
          "editWarehouseTransfers" AS hiddenName,
          "warehouseTransfers" AS `class`,
          "search" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenu_pages sp ON sp.pageID = p.id
    JOIN      submenus sm ON sm.id = sp.subMenuID
    WHERE     sm.hiddenName = "editData"
    AND       sm.active
    LIMIT 1
) m
WHERE     sm.hiddenName = "editData"
AND       active
LIMIT 1;

-- Add Edit Warehouse Transfers to Edit Data submenu

INSERT INTO submenu_pages (pageID, subMenuID)
    SELECT    MAX(p.id) AS pageID,
              m.subMenuID
    FROM pages p
    JOIN (
        SELECT    sm.id AS subMenuID
        FROM      subMenus sm
        WHERE     sm.hiddenName = "editData"
    ) m;

-- Add page params for "Edit Warehouse Transfers"

INSERT INTO page_params (pageID, name, value, active)
    SELECT    id AS pageID,
              "editable" AS name,
              "display" AS value,
              1 AS active
    FROM      pages
    WHERE     hiddenName = "editWarehouseTransfers";

-- adding a link for Search Warehouse Transfer Pallets page

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Search Warehouse Transfer Pallets" AS displayName,
          maxOrder + 1 AS displayOrder,
          "searchWarehouseTransferPallets" AS hiddenName,
          "warehouseTransferPallets" AS `class`,
          "search" AS `method`,
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

-- Add Edit Warehouse Transfers to Edit Data submenu
INSERT INTO submenu_pages (pageID, subMenuID)
    SELECT    MAX(p.id) AS pageID,
              m.subMenuID
    FROM pages p
    JOIN (
        SELECT    sm.id AS subMenuID
        FROM      subMenus sm
        WHERE     sm.hiddenName = "searchData"
    ) m;

-- adding a link for Warehouse Transfer scanner

INSERT INTO pages (displayName, displayOrder, hiddenName, `class`, `method`, red, active)
    SELECT    "Warehouse Transfers" AS displayName,
              maxOrder + 1 AS displayOrder,
              "warehouseTransfer" AS hiddenName,
              "scanners" AS `class`,
              "warehouseTransfer" AS `method`,
              0 AS red,
              1 AS active
    FROM      subMenus sm
    JOIN      (
                  SELECT    MAX(p.displayOrder) AS maxOrder
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
    SELECT    p.id AS pageID,
              m.subMenuID
    FROM      pages p
    JOIN      (
                  SELECT    sm.id AS subMenuID
                  FROM      subMenus sm
                  WHERE     sm.hiddenName = 'wms'
              ) m
    WHERE p.hiddenName = 'warehouseTransfer';

-- updating link for Inbound Warehouse Transfer scanner

UPDATE    pages p
JOIN      submenu_pages sp ON sp.pageID = p.id
JOIN      submenus sm
SET       p.displayName = "Warehouse Outbound Transfers",
          p.hiddenName = "warehouseOutboundTransfer",
          p.`method` = "warehouseOutboundTransfer",
          sp.subMenuID = sm.id
WHERE     p.hiddenName = "warehouseTransfer"
AND       sm.hiddenName = "inventoryControl";

-- adding a link for Inbound Warehouse Transfer scanner

INSERT INTO pages (displayName, displayOrder, hiddenName, `class`, `method`, red, active)
    SELECT    "Warehouse Inbound Transfers" AS displayName,
              maxOrder + 1 AS displayOrder,
              "warehouseInboundTransfer" AS hiddenName,
              "scanners" AS `class`,
              "warehouseInboundTransfer" AS `method`,
              0 AS red,
              1 AS active
    FROM      subMenus sm
    JOIN      (
                  SELECT    MAX(p.displayOrder) AS maxOrder
                  FROM      pages p
                  JOIN      submenu_pages sp ON sp.pageID = p.id
                  JOIN      submenus sm ON sm.id = sp.subMenuID
                  WHERE     sm.hiddenName = "inventoryControl"
                  AND       sm.active
                  LIMIT 1
              ) m
    WHERE     sm.hiddenName = "inventoryControl"
    AND       active
    LIMIT 1;

INSERT INTO submenu_pages (pageID, subMenuID)
    SELECT    p.id AS pageID,
              m.subMenuID
    FROM      pages p
    JOIN      (
                  SELECT    sm.id AS subMenuID
                  FROM      subMenus sm
                  WHERE     sm.hiddenName = 'inventoryControl'
              ) m
    WHERE p.hiddenName = 'warehouseInboundTransfer';

-- Raji
-- Add new link for warehouse transfers consolidation

INSERT INTO pages (displayName, displayOrder, hiddenName, `class`, `method`, red, active)
    SELECT    "Warehouse Transfers Consolidation" AS displayName,
              maxOrder + 1 AS displayOrder,
              "warehouseTransferConsolidation" AS hiddenName,
              "scanners" AS `class`,
              "warehouseTransferConsolidation" AS `method`,
              0 AS red,
              1 AS active
    FROM      subMenus sm
    JOIN      (
                  SELECT    MAX(p.displayOrder) AS maxOrder
                  FROM      pages p
                  JOIN      submenu_pages sp ON sp.pageID = p.id
                  JOIN      submenus sm ON sm.id = sp.subMenuID
                  WHERE     sm.hiddenName = "inventoryControl"
                  AND       sm.active
                  LIMIT 1
              ) m
    WHERE     sm.hiddenName = "inventoryControl"
    AND       active
    LIMIT 1;


INSERT INTO submenu_pages (pageID, subMenuID)
    SELECT    p.id AS pageID,
              m.subMenuID
    FROM      pages p
    JOIN      (
                  SELECT    sm.id AS subMenuID
                  FROM      subMenus sm
                  WHERE     sm.hiddenName = 'inventoryControl'
              ) m
    WHERE p.hiddenName = 'warehouseTransferConsolidation';

-- Vadzim
-- 12/29/2016
-- removing clientCode fields from vendorName index

ALTER TABLE vendors DROP INDEX vendorName, ADD UNIQUE vendorName (vendorName, warehouseID);

-- Vadzim
-- 12/31/2016
-- adding recNum and vendorID fields to warehouse_transfer_pallets tables,
-- making manifest field of VARCHAR type

ALTER TABLE warehouse_transfer_pallets ADD recNum INT(8) NOT NULL AFTER plate;

ALTER TABLE warehouse_transfer_pallets ADD outVendorID INT(5) NOT NULL;
ALTER TABLE warehouse_transfer_pallets ADD inVendorID INT(5) NULL DEFAULT NULL;

ALTER TABLE warehouse_transfer_pallets CHANGE manifest manifest VARCHAR(255) NOT NULL;

-- Vadzim
-- 1/12/2017
-- adding a field that signifies that an order has VAS (Value-Added Services)

ALTER TABLE neworder ADD isVAS TINYINT(1) NOT NULL DEFAULT '0' AFTER labelinfo;

-- Vadzim
-- 1/17/2017
-- adding Americo deal sites

INSERT INTO `deal_sites` (`id`, `displayName`, `imageName`, `active`) VALUES
(NULL, 'Basic Resources Americo', 'Americo', '1'),
(NULL, 'USA Underwear Americo', 'Americo', '1'),
(NULL, 'USA Legwear Americo', 'Americo', '1');

-- Vadzim
-- 2/04/2017
-- adding Morgan Home Fashions deal sites

INSERT INTO deal_sites (displayName, imageName, active) VALUES
('Morgan Home Fashions', 'MorganHomeFashions', '1');

-- 2/06/2017
-- adding shipping statuses

INSERT INTO statuses (category, displayName, shortName) VALUES
('shipping', 'Canceled Order', 'CNCL'),
('shipping', 'Work Order not Shipping', 'WONS'),
('shipping', 'Shipping', 'SHIP');

-- adding a field to neworder table that will store order shipping status

ALTER TABLE neworder ADD shippingStatusID INT(2) NULL DEFAULT NULL AFTER isVAS,
ADD INDEX shippingStatusID (shippingStatusID);

ALTER TABLE logs_fields CHANGE displayName displayName VARCHAR(20) NOT NULL;

INSERT INTO logs_fields (displayName, tableName, category) VALUES ('shippingStatusID', 'statuses', 'orders');

ALTER TABLE  `count_items` ADD  `allocate_qty` INT( 3 ) NOT NULL DEFAULT  '0' AFTER  `pcs` ;

-- daily receiving container report
INSERT INTO crons.tasks (displayName, `server`, site, app, `class`, `method`, frequency)
VALUES ('Daily Receiving Container Report', 'seldatawms.com', '', 'wms', 'appCrons', 'dailyReceivingContainerReport', 60);

-- weekly receiving container report
INSERT INTO crons.tasks (displayName, `server`, site, app, `class`, `method`, frequency)
VALUES ('Email Receiving Weekly Report', 'seldatawms.com', '', 'wms', 'appCrons', 'emailReceivingWeeklyReport', 60);

-- 3/07/2017
-- fixing locations table fields

ALTER TABLE locations CHANGE displayName displayName VARCHAR(21) NOT NULL;
ALTER TABLE locations CHANGE warehouseID warehouseID INT(4) NOT NULL;

-- locationNumber field as unused

ALTER TABLE locations DROP locationNumber;

-- Vadzim
-- 3/11/2017
-- adding uom field to pick_errors table

ALTER TABLE pick_errors ADD uom INT(3) NULL DEFAULT NULL AFTER orderID;
