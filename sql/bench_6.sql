-- ---------------------------------------------------------
--      wms:      ONLY commands from after 2173 here
--      _default: ONLY commands from after 375 here
-- ---------------------------------------------------------

-- Vadzim
-- 12/18/2015

-- Remove unique constraint based on child cartons and active fields
ALTER TABLE `inventory_splits` DROP INDEX `activeChild`;

-- Make child cartons unique
ALTER TABLE `inventory_splits` ADD UNIQUE KEY `uniqueChild` (`childID`);

-- Vadzim
-- 12/21/2015

-- removing onload searcher

UPDATE page_params pp
JOIN pages p ON p.ID = pp.pageID
SET pp.active = 0
WHERE hiddenName = 'dashboardShipping'
AND `name` = 'firstDropdown';

-- Tri Le
-- 12/22/2015

-- Set allows field 'newPassword' in table info is NULL
ALTER TABLE `info` CHANGE `newPassword` `newPassword` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

-- Duy Nguyen
-- 12/29/2015
-- Add "Search Warehouse Locations" page to "Administration" 

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`) 
SELECT  sm.id AS subMenuID,
        "Search Warehouse Locations" AS displayName,
        m.maxOrder + 1 AS displayOrder,
        "adminSearchWarehouseLocations" AS hiddenName,
        "locations" AS `class`,
        "adminSearch" AS `method`,
        0 AS red,
        1 AS active
FROM      subMenus sm
JOIN      (
    SELECT maxOrder
    FROM (
        SELECT    MAX(p.displayOrder)  AS maxOrder
        FROM      pages p
        JOIN      submenus sm ON sm.id = p.subMenuID
        WHERE     sm.displayName = "Administration"
        AND       sm.active
    )  AS displayMaxOrder
) m
WHERE     sm.displayName = "Administration"
AND       active
LIMIT 1;

-- Vadzim
-- 1/4/2016

-- add missing indices

ALTER TABLE warehouses ADD KEY locationID (locationID);
ALTER TABLE logs_scan_input ADD KEY userID (userID);
ALTER TABLE inventory_unsplits ADD KEY userID (userID);

-- remove a extra index

ALTER TABLE group_users DROP INDEX active;

-- creating tests database

CREATE DATABASE tests CHARSET=utf8 COLLATE=utf8_general_ci;

-- grant privileges to the DB
-- GRANT ALL ON tests.* TO 'seldatWMS'@'localhost';

-- do not use "USE `tests`;" command since there is no way to explicitly close 
-- a database. Use a database prefix instead as I assume that 'tests' DB will
-- have its present name in all projects. Otherwise remove DB name prefix.

CREATE TABLE tests.tests (
    id INT(4) NOT NULL AUTO_INCREMENT,
    description VARCHAR(100) NOT NULL,
    outputName TEXT NOT NULL,
    outputValue TEXT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;

CREATE TABLE tests.test_inputs (
    id INT(4) NOT NULL AUTO_INCREMENT,
    testID INT(4) NOT NULL,
    `type` VARCHAR(7) NOT NULL,
    json TEXT,
    PRIMARY KEY (id),
    UNIQUE KEY uniqueTypeTestID (testID, `type`),
    KEY testID (testID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;

CREATE TABLE tests.cases (
    id INT(4) NOT NULL AUTO_INCREMENT,
    displayName VARCHAR(100) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;

CREATE TABLE tests.case_tests (
    id INT(5) NOT NULL AUTO_INCREMENT,
    caseID INT(4) NOT NULL,
    testID INT(4) NOT NULL,
    testOrder INT(2) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY caseID (caseID),
    KEY testID (testID),
    UNIQUE KEY uniqueCaseTest (caseID, testID),
    UNIQUE KEY uniqueTestOrder (caseID, testOrder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;

-- Create test_runs database

CREATE DATABASE wms_test_runs CHARSET=utf8 COLLATE=utf8_general_ci;

-- grant privileges to the DB
-- GRANT ALL ON test_runs.* TO 'seldatWMS'@'localhost';

-- do not use "USE `test_runs`;" command since there is no way to explicitly close 
-- a database. Use a database prefix instead as I assume that 'tests' DB will
-- have its present name in all projects. Otherwise remove DB name prefix.

-- add tables that will be emptied prior to a test run

CREATE TABLE wms_test_runs.adjustment_logs LIKE adjustment_logs;
CREATE TABLE wms_test_runs.client_emails LIKE client_emails;
CREATE TABLE wms_test_runs.consolidation_waves LIKE consolidation_waves;
CREATE TABLE wms_test_runs.consolidations LIKE consolidations;
CREATE TABLE wms_test_runs.costs LIKE costs;
CREATE TABLE wms_test_runs.group_pages LIKE group_pages;
CREATE TABLE wms_test_runs.group_users LIKE group_users;
CREATE TABLE wms_test_runs.groups LIKE groups;
CREATE TABLE wms_test_runs.history LIKE history;
CREATE TABLE wms_test_runs.history_values LIKE history_values;
CREATE TABLE wms_test_runs.inventory_batches LIKE inventory_batches;
CREATE TABLE wms_test_runs.inventory_cartons LIKE inventory_cartons;
CREATE TABLE wms_test_runs.inventory_containers LIKE inventory_containers;
CREATE TABLE wms_test_runs.inventory_control LIKE inventory_control;
CREATE TABLE wms_test_runs.inventory_merge_converse LIKE inventory_merge_converse;
CREATE TABLE wms_test_runs.inventory_splits LIKE inventory_splits;
CREATE TABLE wms_test_runs.inventory_unsplits LIKE inventory_unsplits;
CREATE TABLE wms_test_runs.invoices LIKE invoices;
CREATE TABLE wms_test_runs.invoices_processing LIKE invoices_processing;
CREATE TABLE wms_test_runs.invoices_receiving LIKE invoices_receiving;
CREATE TABLE wms_test_runs.invoices_receiving_batches LIKE invoices_receiving_batches;
CREATE TABLE wms_test_runs.invoices_storage LIKE invoices_storage;
CREATE TABLE wms_test_runs.invoices_work_orders LIKE invoices_work_orders;
CREATE TABLE wms_test_runs.label_batches LIKE label_batches;
CREATE TABLE wms_test_runs.licenseplate LIKE licenseplate;
CREATE TABLE wms_test_runs.logs_adds LIKE logs_adds;
CREATE TABLE wms_test_runs.logs_cartons LIKE logs_cartons;
CREATE TABLE wms_test_runs.logs_orders LIKE logs_orders;
CREATE TABLE wms_test_runs.logs_values LIKE logs_values;
CREATE TABLE wms_test_runs.logs_workorders LIKE logs_workorders;
CREATE TABLE wms_test_runs.logs_scan_input LIKE logs_scan_input;
CREATE TABLE wms_test_runs.masterlabel LIKE masterlabel;
CREATE TABLE wms_test_runs.min_max LIKE min_max;
CREATE TABLE wms_test_runs.min_max_ranges LIKE min_max_ranges;
CREATE TABLE wms_test_runs.neworder LIKE neworder;
CREATE TABLE wms_test_runs.neworderlabel LIKE neworderlabel;
CREATE TABLE wms_test_runs.nsi LIKE nsi;
CREATE TABLE wms_test_runs.nsi_po_batches LIKE nsi_po_batches;
CREATE TABLE wms_test_runs.nsi_pos LIKE nsi_pos;
CREATE TABLE wms_test_runs.nsi_receiving LIKE nsi_receiving;
CREATE TABLE wms_test_runs.nsi_receiving_pallets LIKE nsi_receiving_pallets;
CREATE TABLE wms_test_runs.nsi_shipping LIKE nsi_shipping;
CREATE TABLE wms_test_runs.nsi_shipping_batches LIKE nsi_shipping_batches;
CREATE TABLE wms_test_runs.online_orders LIKE online_orders;
CREATE TABLE wms_test_runs.online_orders_exports LIKE online_orders_exports;
CREATE TABLE wms_test_runs.online_orders_exports_bill_to LIKE online_orders_exports_bill_to;
CREATE TABLE wms_test_runs.online_orders_exports_orders LIKE online_orders_exports_orders;
CREATE TABLE wms_test_runs.online_orders_exports_packages LIKE online_orders_exports_packages;
CREATE TABLE wms_test_runs.online_orders_exports_providers LIKE online_orders_exports_providers;
CREATE TABLE wms_test_runs.online_orders_exports_services LIKE online_orders_exports_services;
CREATE TABLE wms_test_runs.online_orders_exports_signatures LIKE online_orders_exports_signatures;
CREATE TABLE wms_test_runs.online_orders_fails LIKE online_orders_fails;
CREATE TABLE wms_test_runs.online_orders_fails_update LIKE online_orders_fails_update;
CREATE TABLE wms_test_runs.order_batches LIKE order_batches;
CREATE TABLE wms_test_runs.order_picks_fails LIKE order_picks_fails;
CREATE TABLE wms_test_runs.orders_shipping_info LIKE orders_shipping_info;
CREATE TABLE wms_test_runs.pallet_sheet_batches LIKE pallet_sheet_batches;
CREATE TABLE wms_test_runs.pallet_sheets LIKE pallet_sheets;
CREATE TABLE wms_test_runs.pick_cartons LIKE pick_cartons;
CREATE TABLE wms_test_runs.pick_errors LIKE pick_errors;
CREATE TABLE wms_test_runs.pick_orders LIKE pick_orders;
CREATE TABLE wms_test_runs.pick_waves LIKE pick_waves;
CREATE TABLE wms_test_runs.plate_batches LIKE plate_batches;
CREATE TABLE wms_test_runs.receiving_numbers LIKE receiving_numbers;
CREATE TABLE wms_test_runs.reports LIKE reports;
CREATE TABLE wms_test_runs.reports_data LIKE reports_data;
CREATE TABLE wms_test_runs.stores LIKE stores;
CREATE TABLE wms_test_runs.tallies LIKE tallies;
CREATE TABLE wms_test_runs.tally_cartons LIKE tally_cartons;
CREATE TABLE wms_test_runs.tally_rows LIKE tally_rows;
CREATE TABLE wms_test_runs.transfer_cartons LIKE transfer_cartons;
CREATE TABLE wms_test_runs.transfer_items LIKE transfer_items;
CREATE TABLE wms_test_runs.transfers LIKE transfers;
CREATE TABLE wms_test_runs.upcs LIKE upcs;
CREATE TABLE wms_test_runs.upcs_assigned LIKE upcs_assigned;
CREATE TABLE wms_test_runs.upcs_checkout LIKE upcs_checkout;
CREATE TABLE wms_test_runs.user_groups LIKE user_groups;
CREATE TABLE wms_test_runs.workorder LIKE workorder;
CREATE TABLE wms_test_runs.workorderlabel LIKE workorderlabel;

-- add tables that will retain its data (will not be emptied priot to a test run)

CREATE TABLE wms_test_runs.awms_new_features LIKE awms_new_features;
INSERT INTO wms_test_runs.awms_new_features SELECT * FROM awms_new_features;
CREATE TABLE wms_test_runs.commodity LIKE commodity;
INSERT INTO wms_test_runs.commodity SELECT * FROM commodity;
CREATE TABLE wms_test_runs.company_address LIKE company_address;
INSERT INTO wms_test_runs.company_address SELECT * FROM company_address;
CREATE TABLE wms_test_runs.deal_sites LIKE deal_sites;
INSERT INTO wms_test_runs.deal_sites SELECT * FROM deal_sites;
CREATE TABLE wms_test_runs.history_actions LIKE history_actions;
INSERT INTO wms_test_runs.history_actions SELECT * FROM history_actions;
CREATE TABLE wms_test_runs.history_fields LIKE history_fields;
INSERT INTO wms_test_runs.history_fields SELECT * FROM history_fields;
CREATE TABLE wms_test_runs.history_models LIKE history_models;
INSERT INTO wms_test_runs.history_models SELECT * FROM history_models;
CREATE TABLE wms_test_runs.locations LIKE locations;
INSERT INTO wms_test_runs.locations SELECT * FROM locations;
CREATE TABLE wms_test_runs.locations_info LIKE locations_info;
INSERT INTO wms_test_runs.locations_info SELECT * FROM locations_info;
CREATE TABLE wms_test_runs.logs_fields LIKE logs_fields;
INSERT INTO wms_test_runs.logs_fields SELECT * FROM logs_fields;
CREATE TABLE wms_test_runs.measurement_systems LIKE measurement_systems;
INSERT INTO wms_test_runs.measurement_systems SELECT * FROM measurement_systems;
CREATE TABLE wms_test_runs.order_description LIKE order_description;
INSERT INTO wms_test_runs.order_description SELECT * FROM order_description;
CREATE TABLE wms_test_runs.order_types LIKE order_types;
INSERT INTO wms_test_runs.order_types SELECT * FROM order_types;
CREATE TABLE wms_test_runs.page_params LIKE page_params;
INSERT INTO wms_test_runs.page_params SELECT * FROM page_params;
CREATE TABLE wms_test_runs.pages LIKE pages;
INSERT INTO wms_test_runs.pages SELECT * FROM pages;
CREATE TABLE wms_test_runs.paid_by LIKE paid_by;
INSERT INTO wms_test_runs.paid_by SELECT * FROM paid_by;
CREATE TABLE wms_test_runs.refs LIKE refs;
INSERT INTO wms_test_runs.refs SELECT * FROM refs;
CREATE TABLE wms_test_runs.release_versions LIKE release_versions;
INSERT INTO wms_test_runs.release_versions SELECT * FROM release_versions;
CREATE TABLE wms_test_runs.status_boolean LIKE status_boolean;
INSERT INTO wms_test_runs.status_boolean SELECT * FROM status_boolean;
CREATE TABLE wms_test_runs.statuses LIKE statuses;
INSERT INTO wms_test_runs.statuses SELECT * FROM statuses;
CREATE TABLE wms_test_runs.submenus LIKE submenus;
INSERT INTO wms_test_runs.submenus SELECT * FROM submenus;
CREATE TABLE wms_test_runs.upcs_categories LIKE upcs_categories;
INSERT INTO wms_test_runs.upcs_categories SELECT * FROM upcs_categories;
CREATE TABLE wms_test_runs.upcs_originals LIKE upcs_originals;
INSERT INTO wms_test_runs.upcs_originals SELECT * FROM upcs_originals;
CREATE TABLE wms_test_runs.user_levels LIKE user_levels;
INSERT INTO wms_test_runs.user_levels SELECT * FROM user_levels;
CREATE TABLE wms_test_runs.users_access LIKE users_access;
INSERT INTO wms_test_runs.users_access SELECT * FROM users_access;
CREATE TABLE wms_test_runs.vendors LIKE vendors;
INSERT INTO wms_test_runs.vendors SELECT * FROM vendors;
CREATE TABLE wms_test_runs.version_info LIKE version_info;
INSERT INTO wms_test_runs.version_info SELECT * FROM version_info;
CREATE TABLE wms_test_runs.warehouses LIKE warehouses;
INSERT INTO wms_test_runs.warehouses SELECT * FROM warehouses;

-- Add Tester user

INSERT INTO seldat_users.info (firstName, lastName, userName, email, `password`, employer)
	SELECT    'Tester', 
    		  'Tester', 
              'tester', 
              'no.reply@seldatinc.com', 
              MD5('tester'),
              id
    FROM      statuses
    WHERE     displayName = 'Seldat'
    AND       category = 'employers';

INSERT INTO users_access (userID, levelID) 
	SELECT    MAX(i.id), 
              l.id 
    FROM      user_levels l
    JOIN      seldat_users.info i
    WHERE     l.displayName = 'Developer';

--  Add "hiddenName" field to "submenus" table

ALTER TABLE submenus ADD COLUMN hiddenName VARCHAR(50) NOT NULL AFTER id;

UPDATE  submenus
SET     hiddenName = 'wms'
WHERE   displayName = 'WMS';

UPDATE  submenus
SET     hiddenName = 'searchData'
WHERE   displayName = 'Search Data';

UPDATE  submenus
SET     hiddenName = 'searchLogs'
WHERE   displayName = 'Search Logs';

UPDATE  submenus
SET     hiddenName = 'onlineOrder'
WHERE   displayName = 'Online Order';

UPDATE  submenus
SET     hiddenName = 'invoice'
WHERE   displayName = 'Invoice';

UPDATE  submenus
SET     hiddenName = 'nsi'
WHERE   displayName = 'NSI';

UPDATE  submenus
SET     hiddenName = 'editData'
WHERE   displayName = 'Edit Data';

UPDATE  submenus
SET     hiddenName = 'administration'
WHERE   displayName = 'Administration';

UPDATE  submenus
SET     hiddenName = 'inventoryControl'
WHERE   displayName = 'Inventory Control';

UPDATE  submenus
SET     hiddenName = 'database'
WHERE   displayName = 'DataBase';

UPDATE  submenus
SET     hiddenName = 'pageTester'
WHERE   displayName = 'Page Tester';

UPDATE  submenus
SET     hiddenName = 'signOut'
WHERE   displayName = 'Sign Out';

-- Remove "Page Tester" submenu from the Main Menu

UPDATE  pages p
JOIN    submenus sm ON sm.id = p.subMenuID
SET     p.active = 0,
        sm.active = 0
WHERE   sm.hiddenName = 'pageTester';

--  Add "hiddenName" field to "submenus" table in wms_test_runs DB

ALTER TABLE wms_test_runs.submenus ADD COLUMN hiddenName VARCHAR(50) NOT NULL AFTER id;

UPDATE  wms_test_runs.submenus
SET     hiddenName = 'wms'
WHERE   displayName = 'WMS';

UPDATE  wms_test_runs.submenus
SET     hiddenName = 'searchData'
WHERE   displayName = 'Search Data';

UPDATE  wms_test_runs.submenus
SET     hiddenName = 'searchLogs'
WHERE   displayName = 'Search Logs';

UPDATE  wms_test_runs.submenus
SET     hiddenName = 'onlineOrder'
WHERE   displayName = 'Online Order';

UPDATE  wms_test_runs.submenus
SET     hiddenName = 'invoice'
WHERE   displayName = 'Invoice';

UPDATE  wms_test_runs.submenus
SET     hiddenName = 'nsi'
WHERE   displayName = 'NSI';

UPDATE  wms_test_runs.submenus
SET     hiddenName = 'editData'
WHERE   displayName = 'Edit Data';

UPDATE  wms_test_runs.submenus
SET     hiddenName = 'administration'
WHERE   displayName = 'Administration';

UPDATE  wms_test_runs.submenus
SET     hiddenName = 'inventoryControl'
WHERE   displayName = 'Inventory Control';

UPDATE  wms_test_runs.submenus
SET     hiddenName = 'database'
WHERE   displayName = 'DataBase';

UPDATE  wms_test_runs.submenus
SET     hiddenName = 'pageTester'
WHERE   displayName = 'Page Tester';

UPDATE  wms_test_runs.submenus
SET     hiddenName = 'signOut'
WHERE   displayName = 'Sign Out';

-- Remove "Page Tester" submenu from the Main Menu

UPDATE  wms_test_runs.pages p
JOIN    wms_test_runs.submenus sm ON sm.id = p.subMenuID
SET     p.active = 0,
        sm.active = 0
WHERE   sm.hiddenName = 'pageTester';

-- Vadzim
-- 7/4/2016

-- adding AUTO_INCREMENT to wms_test_runs DB tables 

ALTER TABLE wms_test_runs.inventory_batches AUTO_INCREMENT = 10000001;
ALTER TABLE wms_test_runs.inventory_containers AUTO_INCREMENT = 10000001;
ALTER TABLE wms_test_runs.licenseplate AUTO_INCREMENT = 10000001;
ALTER TABLE wms_test_runs.neworderlabel AUTO_INCREMENT = 100001;
ALTER TABLE wms_test_runs.order_batches AUTO_INCREMENT = 1000001;
ALTER TABLE wms_test_runs.plate_batches AUTO_INCREMENT = 100001;
ALTER TABLE wms_test_runs.workorderlabel AUTO_INCREMENT = 100001;

-- Raji
-- 01/06/2016

-- DefaultInputs Dropdown Receiving Invoice

UPDATE  page_params pm
JOIN    pages p ON p.id = pm.pageID
SET     pm.active = 1,
        pm.value = 'statusID'
WHERE   pm.name = 'firstDropdown' 
AND     p.hiddenName = 'receivingInvoices';

UPDATE  page_params pm
JOIN    pages p ON p.id = pm.pageID
SET     pm.active = 0,
        pm.value = '' 
WHERE   pm.name = 'secondDropdown' 
AND     p.hiddenName = 'receivingInvoices';


UPDATE  page_params pm
JOIN    pages p ON p.id = pm.pageID
SET     pm.active = 0,
        pm.value = ''  
WHERE   pm.name = 'thirdDropdown' 
AND     p.hiddenName = 'receivingInvoices';

UPDATE  page_params pm
JOIN    pages p ON p.id = pm.pageID
SET     pm.active = 0,
        pm.value = '' 
WHERE   pm.name = 'fourthDropdown' 
AND     p.hiddenName = 'receivingInvoices';

-- DefaultInputs Receiving Invoice to wms_test_runs DB tables 

UPDATE  wms_test_runs.page_params pm
JOIN    wms_test_runs.pages p ON p.id = pm.pageID
SET     pm.active = 1,
        pm.value = 'statusID' 
WHERE   pm.name = 'firstDropdown' 
AND     p.hiddenName = 'receivingInvoices';

UPDATE  wms_test_runs.page_params pm
JOIN    wms_test_runs.pages p ON p.id = pm.pageID
SET     pm.active = 0,
        pm.value = '' 
WHERE   pm.name = 'secondDropdown' 
AND     p.hiddenName = 'receivingInvoices';


UPDATE  wms_test_runs.page_params pm
JOIN    wms_test_runs.pages p ON p.id = pm.pageID
SET     pm.active = 0,
        pm.value = '' 
WHERE   pm.name = 'thirdDropdown' 
AND     p.hiddenName = 'receivingInvoices';


UPDATE  wms_test_runs.page_params pm
JOIN    wms_test_runs.pages p ON p.id = pm.pageID
SET     pm.active = 0,
        pm.value = '' 
WHERE   pm.name = 'fourthDropdown' 
AND     p.hiddenName = 'receivingInvoices';


-- Changed recOT,recLabour,recQC fields to NOT NULL

ALTER TABLE invoices_receiving CHANGE recOT recOT DECIMAL(8,2) UNSIGNED ZEROFILL NOT NULL;
ALTER TABLE invoices_receiving CHANGE recLabour recLabour DECIMAL(8,2) UNSIGNED ZEROFILL NOT NULL;
ALTER TABLE invoices_receiving CHANGE recQC recQC DECIMAL(8,2) UNSIGNED ZEROFILL NOT NULL;

ALTER TABLE wms_test_runs.invoices_receiving CHANGE recOT recOT DECIMAL(8,2) UNSIGNED ZEROFILL NOT NULL;
ALTER TABLE wms_test_runs.invoices_receiving CHANGE recLabour recLabour DECIMAL(8,2) UNSIGNED ZEROFILL NOT NULL;
ALTER TABLE wms_test_runs.invoices_receiving CHANGE recQC recQC DECIMAL(8,2) UNSIGNED ZEROFILL NOT NULL;

-- Tri Le
-- 1/12/2016

-- Add unique 'vendor' in table vendors
ALTER TABLE vendors ADD UNIQUE vendorName (vendorName, warehouseID);

-- Tri Le
-- 1/12/2016

-- Add sub menu "Receiving" in the first WMS
INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
   	      'Receiving' AS displayName,
          m.displayOrder - 0.5 AS displayOrder,
          'receivingContainer' AS hiddenName,
          'receiving' AS `class`,
          'display' AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    displayOrder
    FROM      pages
    WHERE     hiddenName = 'scanContainer'
    AND       active
) m
WHERE     sm.hiddenName = 'wms'
AND       active
LIMIT 1;

-- Phong Tran
-- 01/12/2016

-- Create table for receivngs feature.

CREATE TABLE IF NOT EXISTS `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_general_ci NOT NULL,
  `mime` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  `created_at` datetime NOT NULL,
  `created_by` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `receiving_attachment` (
  `receiving_attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) DEFAULT NULL,
  `receiving_id` int(11) DEFAULT NULL,
  `ordered` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`receiving_attachment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `receiving_containers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `receiving_id` int(11) DEFAULT NULL,
  `container_num` varchar(8) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `receivings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `ref` varchar(50) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `created_by` int(4) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Add unique 'file' in table receiving_attachment
ALTER TABLE receiving_attachment ADD UNIQUE file (file_id, receiving_id);

-- add tables that will be emptied prior to a test run

CREATE TABLE wms_test_runs.files LIKE files;
INSERT INTO wms_test_runs.files SELECT * FROM files;
CREATE TABLE wms_test_runs.receiving_attachment LIKE receiving_attachment;
INSERT INTO wms_test_runs.receiving_attachment SELECT * FROM receiving_attachment;
CREATE TABLE wms_test_runs.receivings LIKE receivings;
INSERT INTO wms_test_runs.receivings SELECT * FROM receivings;
CREATE TABLE wms_test_runs.receiving_containers LIKE receiving_containers;
INSERT INTO wms_test_runs.receiving_containers SELECT * FROM receiving_containers;

-- Vadzim
-- 1/12/2016

-- add ignore_fields table to tests DB

CREATE TABLE tests.ignore_fields (
    id INT(6) NOT NULL AUTO_INCREMENT,
    testID INT(4) NOT NULL,
    ignoreField VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY testID (testID),
    UNIQUE KEY uniqueTestField (testID, ignoreField)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;

-- Tri Le
-- 1/19/2016

-- Add sub menu "UCCs Mezzanine Transfer Tool" in Administration

INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "UCCs Mezzanine Transfer Tool" AS displayName,
          maxOrder + 1 AS displayOrder,
          "transferMezzanine" AS hiddenName,
          "scanners" AS `class`,
          "transferMezzanine" AS `method`,
          0 AS red,
          1 AS active
FROM      submenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.hiddenName = "inventoryControl"
    AND       sm.active
) m
WHERE     sm.hiddenName = 'inventoryControl'
AND       active
LIMIT 1;


UPDATE pages
SET    displayName = 'Test Commands', 
       `class` = 'developers', 
       `method` = 'dbChecker' 
WHERE  hiddenName = 'runDBCheck';


-- Tri Le
-- 1/22/2016

-- Add status for Receiving
INSERT INTO statuses (`category`, `displayName`, `shortName`)
VALUES ('receivings', 'New', 'NEW'),
('receivings', 'Receipted', 'RCT'),
('receivings', 'Finished', 'FNS'),
('receivings', 'Canceled', 'CCL'),
('receivings', 'Deleted', 'DEL');

-- Change status to statusID in receivings table
ALTER TABLE `receivings` CHANGE `status` `statusID` INT(3) NOT NULL;
    
-- Tri Le
-- 1/22/2016

-- Modify table receivings structure, set fields (Foreign Key) not null.
ALTER TABLE `receivings` CHANGE `warehouse_id` `warehouse_id` INT(11) NOT NULL;
ALTER TABLE `receivings` CHANGE `client_id` `client_id` INT(11) NOT NULL;
ALTER TABLE `receivings` CHANGE `created_by` `created_by` INT(4) NOT NULL;

UPDATE `statuses` SET `shortName` = 'ACT' 
WHERE category = 'vendors' AND displayName = 'Active';
UPDATE `statuses` SET `shortName` = 'IN' 
WHERE category = 'vendors' AND displayName = 'Inactive';

-- Jon
-- These constraints should have existed all along
ALTER TABLE `statuses` ADD UNIQUE  (`category`, `displayName`);
ALTER TABLE `statuses` ADD UNIQUE  (`category`, `shortName`);

-- Raji
-- Add 'total' field to invoices_receiving table
ALTER TABLE invoices_receiving ADD COLUMN total DECIMAL(8,2) NULL DEFAULT 0.00 AFTER recSpec;

-- Change 'recNum' field to NOT NULL
ALTER TABLE invoices_receiving CHANGE recNum  recNum INT(8) NOT NULL;

-- Phong Tran
-- 05/02/2016

ALTER TABLE neworder ADD COLUMN deptId VARCHAR(20) NOT NULL AFTER bolNumber;
ALTER TABLE neworder ADD COLUMN clientPickTicket VARCHAR(20) NOT NULL AFTER deptid;
ALTER TABLE neworder ADD COLUMN additionalShipperInformation VARCHAR(20) NOT NULL AFTER clientpickticket;

-- ----------------------------
-- Table structure for bill_batches
-- ----------------------------
CREATE TABLE `bill_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for billofladings
-- ----------------------------
CREATE TABLE `billofladings` (
  `batch` int(6) DEFAULT NULL,
  `assignNumber` int(10) NOT NULL AUTO_INCREMENT,
  `dateEntered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userID` int(4) DEFAULT NULL,
  PRIMARY KEY (`assignNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for shipping_info
-- ----------------------------
CREATE TABLE `shipping_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bolID` int(11) DEFAULT NULL,
  `bolLabel` varchar(11) DEFAULT NULL,
  `shipFromId` int(5) DEFAULT NULL,
  `shipFromName` varchar(100) DEFAULT NULL,
  `shipFromAddress` varchar(100) DEFAULT NULL,
  `shipFromCity` varchar(100) DEFAULT NULL,
  `shipToName` varchar(100) DEFAULT NULL,
  `shipToAddress` varchar(100) DEFAULT NULL,
  `shipToCity` varchar(100) DEFAULT NULL,
  `shipToTel` varchar(15) DEFAULT NULL,
  `parTyName` varchar(100) DEFAULT NULL,
  `partyAddress` varchar(100) DEFAULT NULL,
  `partyCity` varchar(100) DEFAULT NULL,
  `statusID` int(11) DEFAULT NULL,
  `specialInstruction` varchar(255) DEFAULT NULL,
  `otherDocument` varchar(5) COLLATE DEFAULT NULL,
  `otherDocumentInForm` varchar(200) DEFAULT NULL,
  `freightChargeTermBy` varchar(50) DEFAULT NULL,
  `freightChargeTermInfo` decimal(10,2) DEFAULT NULL,
  `carrierName` varchar(50) DEFAULT NULL,
  `carrier` varchar(50) DEFAULT NULL,
  `carrierNote` varchar(50) utf8_unicode_ci DEFAULT NULL,
  `commodity` int(11) DEFAULT NULL,
  `trailerNumber` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sealNumber` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `scac` varchar(4) COLLATE utf8_unicode_ci DEFAULT NULL,
  `proNumber` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipType` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `attachBillOfLading` tinyint(1) DEFAULT NULL,
  `accepTableCustomer` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `feeTermBy` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `trailerLoadBy` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `trailerCountedBy` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for shipping_orders
-- ----------------------------
CREATE TABLE `shipping_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bolID` varchar(11) COLLATE utf8_unicode_ci NOT NULL,
  `orderID` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Phong Tran 02/22/2016
--  Add "Bill Of Ladings" in menu WMS
INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "Bill Of Ladings" AS displayName,
          7.5 AS displayOrder,
          "addShipmentBillOfLadings" AS hiddenName,
          "billOfLadings" AS `class`,
          "addShipment" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.hiddenName = "wms"
    AND       sm.active
        LIMIT 1
) m
WHERE     sm.hiddenName = "wms"
AND       active
LIMIT 1;

--  Add "Generate Bill of Lading Labels" in menu Administration
INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "Generate Bill of Lading Labels" AS displayName,
          maxOrder + 1 AS displayOrder,
          "generateBillOfLadingLabels" AS hiddenName,
          "billOfLadings" AS `class`,
          "listAdd" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.hiddenName = "administration"
    AND       sm.active
        LIMIT 1
) m
WHERE     sm.hiddenName = "administration"
AND       active
LIMIT 1;

--  Add "Search Bill Of Ladings" in menu Search Data
INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "Search Bill Of Ladings" AS displayName,
          maxOrder + 1 AS displayOrder,
          "searchBillOfLadings" AS hiddenName,
          "billOfLadings" AS `class`,
          "search" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.hiddenName = "searchData"
    AND       sm.active
        LIMIT 1
) m
WHERE     sm.hiddenName = "searchData"
AND       active
LIMIT 1;

--  Add "Edit Bill Of Ladings" in menu Edit Data
INSERT INTO `pages` (`subMenuID`, `displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    sm.id AS subMenuID,
          "Edit Bill Of Ladings" AS displayName,
          maxOrder + 1 AS displayOrder,
          "editBillOfLadings" AS hiddenName,
          "billOfLadings" AS `class`,
          "search" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
JOIN      (
    SELECT    MAX(p.displayOrder) AS maxOrder
    FROM      pages p
    JOIN      submenus sm ON sm.id = p.subMenuID
    WHERE     sm.hiddenName = "editData"
    AND       sm.active
        LIMIT 1
) m
WHERE     sm.hiddenName = "editData"
AND       active
LIMIT 1;

-- Add page params for "Edit Bill Of Ladings"
INSERT INTO `page_params` (`pageID`, `name`, `value`, `active`)
SELECT    id AS `pageID`,
          "editable" AS `name`,
          "display" AS `value`,
          1 AS active
FROM      pages
WHERE     hiddenName = "editBillOfLadings";

-- Vadzim
-- 21/2/2016

-- Add truck_order_waves table

CREATE TABLE IF NOT EXISTS truck_order_waves (
    id INT(11) NOT NULL AUTO_INCREMENT,
    userID INT(4) NOT NULL,
    assignNumber INT(6) NOT NULL,
    upcID INT(10) NOT NULL,
    quantity INT(8) NOT NULL,
    submitted TINYINT(1) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY upcID (upcID),
    KEY userID (userID),
    KEY assignNumber (assignNumber),
    UNIQUE KEY orderUPC (upcID, userID, assignNumber)
) ENGINE=InnoDB;

-- Vadzim
-- 3/3/2016

-- Add truck_orders table

CREATE TABLE IF NOT EXISTS truck_orders (
    id INT(8) NOT NULL AUTO_INCREMENT,
    userID INT(4) NOT NULL,
    assignNumber INT(6) NOT NULL,
    importTime TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY userID (userID),
    KEY assignNumber (assignNumber),
    UNIQUE KEY uniqueOrder (userID, assignNumber)
) ENGINE=InnoDB;

-- Prepopulate truck_orders using truck_order_waves table data

INSERT INTO truck_orders (userID, assignNumber, submitted)
    SELECT    userID, 
              assignNumber, 
              submitted
    FROM      truck_order_waves
    GROUP BY  userID, 
              assignNumber;

ALTER TABLE truck_order_waves ADD COLUMN truckOrderID INT(8) NOT NULL AFTER id;

UPDATE    truck_order_waves tow
JOIN      truck_orders t ON t.assignNumber = tow.assignNumber AND t.userID = tow.userID
SET       truckOrderID = t.id;

ALTER TABLE truck_order_waves 
    DROP INDEX orderUPC, 
    DROP INDEX upcID,
    DROP INDEX userID,
    DROP INDEX assignNumber;

ALTER TABLE truck_order_waves 
    DROP userID, 
    DROP assignNumber,
    DROP submitted;

ALTER TABLE truck_order_waves ADD KEY truckOrderID (truckOrderID);
ALTER TABLE truck_order_waves ADD KEY upcID (upcID);
ALTER TABLE truck_order_waves ADD UNIQUE truckUpcID (truckOrderID, upcID);

-- Tri Le
-- 3/4/2016

-- Set sub menu "Bill Of Ladings" is red color
UPDATE pages SET red = 1 WHERE `hiddenName` = 'searchBillOfLadings';

-- Vadzim
-- 3/4/2016

-- Add clientAccess field to pages table

ALTER TABLE pages ADD COLUMN clientAccess TINYINT(1) NULL DEFAULT 0 AFTER red;

UPDATE    pages
SET       clientAccess = 1
WHERE     hiddenName IN (
    'searchAvailableInventory', 
    'searchInventoryControl', 
    'dashboardReceiving', 
    'dashboardShipping', 
    'summaryReport', 
    'confirmSignOut', 
    'changePassword'
);

-- Vadzim
-- 3/6/2016

-- Add submenu_pages table to signify what pages belong to what submenu. Some
-- pages may not belong to any submenu at all.

CREATE TABLE IF NOT EXISTS submenu_pages (
    id INT(4) NOT NULL AUTO_INCREMENT,
    pageID INT(3) NOT NULL,
    subMenuID INT(2) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY pageID (pageID),
    KEY subMenuID (subMenuID),
    UNIQUE KEY uniqueSubmenuPage (pageID, subMenuID)
) ENGINE=InnoDB;

INSERT INTO submenu_pages (pageID, subMenuID)
    SELECT    p.id,
              subMenuID
    FROM      pages p
    JOIN      submenus s ON s.id = p.subMenuID
    WHERE     p.active
    AND       s.active;
 
ALTER TABLE pages DROP subMenuID;


-- Tri Le
-- 3/8/2016

-- Unset sub menu "Search Bill Of Ladings" is red color
UPDATE pages SET red = 0 WHERE `hiddenName` = 'searchBillOfLadings';

-- Set sub menu "Bill Of Ladings" is red color
UPDATE pages SET red = 1 WHERE `hiddenName` = 'addShipmentBillOfLadings';

-- Move submenu "Bill Of Lading" to near "Order Processing Check-Out"
UPDATE pages
SET displayOrder = (
    SELECT p.displayOrder + 0.5
    FROM (SELECT * FROM pages) AS p
    WHERE p.hiddenName = 'orderProcessingCheckOut'
)
WHERE hiddenName = 'addShipmentBillOfLadings';

-- Add status for Bill Of Ladings
INSERT INTO statuses (`category`, `displayName`, `shortName`)
VALUES ('orders', 'Bill Of Ladings', 'BOL');


-- Raji
-- 03/08/2016

-- Change the search url method in Search Receiving / Storage Invoices

UPDATE  pages p
JOIN    page_params pm ON pm.pageID = p.id
SET     p.method = 'search'
WHERE   pm.name = 'type'
AND	pm.value = 'receiving'
AND 	p.hiddenName = 'searchReceivingInvoices';

UPDATE  pages p
JOIN    page_params pm ON pm.pageID = p.id
SET     p.method = 'search'
WHERE   pm.name = 'type'
AND	pm.value = 'storage'
AND 	p.hiddenName = 'searchStorageInvoices';

-- Jon
-- 03/09/2016
ALTER TABLE `neworder` CHANGE `deptID` `deptID` VARCHAR(20) NULL DEFAULT NULL;
ALTER TABLE `neworder` CHANGE `clientPickTicket` `clientPickTicket` 
VARCHAR(20) NULL DEFAULT NULL;
ALTER TABLE `neworder` CHANGE `additionalShipperInformation` 
`additionalShipperInformation` VARCHAR(20) NULL DEFAULT NULL;

--  Vadzim
--  3/10/2016
--
-- make CURRENT_TIMESTAMP a default value for `date` field
--
ALTER TABLE awms_new_features CHANGE `date` `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
--
-- Make Feature Name unique within a given Version
--
ALTER TABLE awms_new_features ADD UNIQUE KEY uniqueVersionFeature (versionID, featureName);
--
--  Make Version Name unique
--
ALTER TABLE release_versions ADD UNIQUE KEY uniqueVersion (versionName);
--
-- make CURRENT_TIMESTAMP a default value for `date` field
--
ALTER TABLE release_versions CHANGE `date` `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Phong Tran
-- 03/16/2016

-- Make length to 100 for color column in upcs table
ALTER TABLE upcs MODIFY color VARCHAR(100);