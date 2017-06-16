-- ---------------------------------------------------------
--      wms:      ONLY commands after 10/24/2016 here
--      _default: ONLY commands after 10/24/2016 here
-- ---------------------------------------------------------

-- Vadzim Mechnik
-- 10/28/2016
-- removing Search Inventory to Adjust menu link as unused

UPDATE    submenu_pages sp
JOIN      pages p ON p.id = sp.pageID
SET       sp.active = 0,
          p.active = 0
WHERE     hiddenName IN ('searchInventorytoAdjust');


-- Tri Le
-- 11/30/2016
-- adding field statusID to awms_new_features
ALTER TABLE `awms_new_features` ADD `statusID` INT(2) NULL DEFAULT NULL AFTER `featureDescription`;

-- set default value fields date are current_timestamp
ALTER TABLE `awms_new_features` CHANGE `date` `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `release_versions` CHANGE `date` `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;


-- adding a link for Edit Warehouse Transfers page

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Receiving Report" AS displayName,
          maxOrder + 1 AS displayOrder,
          "receivingReport" AS hiddenName,
          "inventory" AS `class`,
          "receivingReport" AS `method`,
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
    SELECT    MAX(p.id) AS pageID,
              m.subMenuID
    FROM pages p
    JOIN (
        SELECT    sm.id AS subMenuID
        FROM      subMenus sm
        WHERE     sm.hiddenName = "searchData"
    ) m;

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Search Locations Utilization" AS displayName,
          maxOrder + 1 AS displayOrder,
          "searchLocationsUtilization" AS hiddenName,
          "locations" AS `class`,
          "searchLocationsUtilization" AS `method`,
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
    SELECT    MAX(p.id) AS pageID,
              m.subMenuID
    FROM pages p
    JOIN (
        SELECT    sm.id AS subMenuID
        FROM      subMenus sm
        WHERE     sm.hiddenName = "searchData"
    ) m;


-- Tri Le
-- 03/31/2017
-- adding "completed_date" field to "cycle_count" table
ALTER TABLE `cycle_count` ADD `completed_dt` DATETIME NULL DEFAULT NULL AFTER `updated`;

-- End of Day Report - Shipped Orders
INSERT INTO crons.tasks (displayName, `server`, site, app, `class`, `method`, frequency)
VALUES ('End of Day Report - Shipped Orders', 'seldatawms.com', '', 'wms', 'appCrons', 'shippedOrderDailyReport', 60);


-- Tri Le
-- 04/11/2017
-- adding tow column to neworder to check print ucc label
ALTER TABLE `neworder`
ADD COLUMN `edi`  tinyint(1) NOT NULL DEFAULT 0 AFTER `reprintPickTicket`,
ADD COLUMN `isPrintUccEdi`  tinyint(1) NOT NULL DEFAULT 0 AFTER `edi`;

ALTER TABLE `online_orders`
ADD COLUMN `isPrintUccEdi`  tinyint(1) NOT NULL DEFAULT 0 AFTER `SELDAT_THIRD_PARTY`;

-- Duy Nguyen
-- 04/12/2017
-- Add print UCC Label on the Administrator menu group
INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Print UCC labels" AS displayName,
          maxOrder + 1 AS displayOrder,
          "printUCClabels" AS hiddenName,
          "orderLabels" AS `class`,
          "printUCC" AS `method`,
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
    SELECT    MAX(p.id) AS pageID,
              m.subMenuID
    FROM pages p
    JOIN (
        SELECT    sm.id AS subMenuID
        FROM      subMenus sm
        WHERE     sm.hiddenName = "administration"
    ) m;

-- Tri Le
-- 04/13/2017
-- add field "trackingNumber" to shipping_info
ALTER TABLE `shipping_info` ADD `trackingNumber` VARCHAR(35) NULL DEFAULT NULL AFTER `proNumber`;

-- 04/18/2017
-- Add Mezzanine Transferred Inventory Report on the Administrator menu group
INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
  SELECT    "Mezzanine Transferred Report" AS displayName,
            maxOrder + 1 AS displayOrder,
            "mezzanineTransferred" AS hiddenName,
            "inventory" AS `class`,
            "mezzanineTransferred" AS `method`,
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
  SELECT    MAX(p.id) AS pageID,
    m.subMenuID
  FROM pages p
    JOIN (
           SELECT    sm.id AS subMenuID
           FROM      subMenus sm
           WHERE     sm.hiddenName = "administration"
         ) m;

-- Duy Nguyen
-- 04/27/2017
-- Change title name of menu
UPDATE    `pages`
SET       `displayName` = 'Print UCC Labels'
WHERE     `hiddenName` = 'printUCClabels'
AND       `class` = 'orderLabels';

-- Tri Le
-- 04/24/2017
-- Add Print Multiple License Plates on the Administrator menu group
INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
  SELECT    "Print Multiple License Plates" AS displayName,
            maxOrder + 1 AS displayOrder,
            "printMultiLicense" AS hiddenName,
            "scanners" AS `class`,
            "printMultiLicense" AS `method`,
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
  SELECT    MAX(p.id) AS pageID,
    m.subMenuID
  FROM pages p
    JOIN (
           SELECT    sm.id AS subMenuID
           FROM      subMenus sm
           WHERE     sm.hiddenName = "administration"
         ) m;

-- 04/27/2017
-- add field "description" to upcs maintain product description
ALTER TABLE `upcs` ADD `description` VARCHAR(255) NULL DEFAULT NULL AFTER `color`;

-- Duy Nguyen
-- 04/25/2017
-- add field for Carton UOM change
INSERT INTO `logs_fields` (`displayName`, `category`) VALUES ('UOM', 'cartons');

INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Edit License Plate UOM" AS displayName,
          maxOrder + 1 AS displayOrder,
          "scanLicensePlateScanners" AS hiddenName,
          "scanners" AS `class`,
          "scanLicensePlate" AS `method`,
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
    SELECT    MAX(p.id) AS pageID,
              m.subMenuID
    FROM pages p
    JOIN (
        SELECT    sm.id AS subMenuID
        FROM      subMenus sm
        WHERE     sm.hiddenName = "administration"
    ) m;


-- Tri Le
-- 04/27/2017
-- Add Update Carton Statuses on the Administrator menu group
INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
  SELECT    "Update Carton Statuses" AS displayName,
            maxOrder + 1 AS displayOrder,
            "changeCartonStatus" AS hiddenName,
            "scanners" AS `class`,
            "changeCartonStatus" AS `method`,
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
  SELECT    MAX(p.id) AS pageID,
    m.subMenuID
  FROM pages p
    JOIN (
           SELECT    sm.id AS subMenuID
           FROM      subMenus sm
           WHERE     sm.hiddenName = "inventoryControl"
         ) m;

-- Create new ctn_sts_req table
CREATE TABLE `ctn_sts_req` (
  `req_id` int(11) NOT NULL AUTO_INCREMENT,
  `to_sts_req` int(3) NOT NULL,
  `to_msts_req` int(3) NOT NULL,
  `req_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `req_by` int(4) NOT NULL,
  PRIMARY KEY (`req_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Create new ctn_sts_req_dtl table
CREATE TABLE `ctn_sts_req_dtl` (
  `req_dtl_id` int(11) NOT NULL AUTO_INCREMENT,
  `req_id` int(11) NOT NULL,
  `vnd_id` int(11) NOT NULL,
  `whs_id` int(4) NOT NULL,
  `ctn_id` int(10) NOT NULL,
  `from_sts_req` int(3) NOT NULL,
  `from_msts_req` int(3) NOT NULL,
  `update_dt` datetime DEFAULT NULL,
  `update_by` int(4) DEFAULT NULL,
  `sts` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`req_dtl_id`),
  KEY `req_id` (`req_id`),
  CONSTRAINT `fk_to_ctn_sts_req` FOREIGN KEY (`req_id`) REFERENCES `ctn_sts_req` (`req_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;