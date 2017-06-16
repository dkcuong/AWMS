-- Tri Le
-- 05/05/2016

-- Create 4 table related to cycle count feature
-- ----------------------------
-- Table structure for cycle_count
-- ----------------------------
CREATE TABLE `cycle_count` (
  `cycle_count_id` int(10) NOT NULL AUTO_INCREMENT,
  `name_report` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `cycle_count_by_uom` char(10) COLLATE utf8_unicode_ci NOT NULL,
  `whs_id` int(4) NOT NULL,
  `type` char(4) COLLATE utf8_unicode_ci NOT NULL,
  `descr` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `due_dt` date DEFAULT NULL,
  `created_by` int(4) NOT NULL,
  `updated_by` int(4) DEFAULT NULL,
  `asgd_id` int(4) DEFAULT NULL,
  `sts` char(4) COLLATE utf8_unicode_ci NOT NULL,
  `has_color_size` tinyint(1) DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`cycle_count_id`),
  KEY `FK_whs_id_warehouses` (`whs_id`),
  CONSTRAINT `FK_whs_id_warehouses` FOREIGN KEY (`whs_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for count_items
-- ----------------------------
CREATE TABLE `count_items` (
  `count_item_id` int(10) NOT NULL AUTO_INCREMENT,
  `cycle_count_id` int(10) NOT NULL,
  `vnd_id` int(10) NOT NULL,
  `sku` varchar(65) COLLATE utf8_unicode_ci NOT NULL,
  `size` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `color` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pcs` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `sys_qty` int(3) NOT NULL,
  `sys_loc` int(7) NOT NULL,
  `act_qty` int(3) DEFAULT NULL,
  `act_loc` int(7) DEFAULT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` datetime DEFAULT NULL,
  `accepted` datetime DEFAULT NULL,
  `sts` char(10) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`count_item_id`),
  KEY `FK_cycle_count_id_cycle_count` (`cycle_count_id`),
  KEY `FK_vnd_id_vendors` (`vnd_id`),
  KEY `FK_sys_loc_locations` (`sys_loc`),
  CONSTRAINT `FK_cycle_count_id_cycle_count` FOREIGN KEY (`cycle_count_id`) REFERENCES `cycle_count` (`cycle_count_id`),
  CONSTRAINT `FK_sys_loc_locations` FOREIGN KEY (`sys_loc`) REFERENCES `locations` (`id`),
  CONSTRAINT `FK_vnd_id_vendors` FOREIGN KEY (`vnd_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for discrepancy_cartons
-- ----------------------------
CREATE TABLE `discrepancy_cartons` (
  `dicpy_ctn_id` int(10) NOT NULL AUTO_INCREMENT,
  `count_item_id` int(10) NOT NULL,
  `invt_ctn_id` int(10) NOT NULL,
  `dicpy_qty` int(10) DEFAULT NULL,
  `sts` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`dicpy_ctn_id`),
  KEY `FK_count_itm_id_count_items` (`count_item_id`),
  KEY `FK_invt_ctn_id_inventory_cartons` (`invt_ctn_id`),
  CONSTRAINT `FK_count_itm_id_count_items` FOREIGN KEY (`count_item_id`) REFERENCES `count_items` (`count_item_id`),
  CONSTRAINT `FK_invt_ctn_id_inventory_cartons` FOREIGN KEY (`invt_ctn_id`) REFERENCES `inventory_cartons` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for locked_cartons
-- ----------------------------
CREATE TABLE `locked_cartons` (
  `lock_ctn_id` int(10) NOT NULL AUTO_INCREMENT,
  `vnd_id` int(11) NOT NULL,
  `whs_id` int(4) NOT NULL,
  `count_item_id` int(10) NOT NULL,
  `invt_ctn_id` int(10) NOT NULL,
  `sts` int(2) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`lock_ctn_id`),
  KEY `FK_vnd_id_vendor` (`vnd_id`),
  KEY `FK_whs_id_warehouse` (`whs_id`),
  KEY `FK_count_item_id_count_item` (`count_item_id`),
  KEY `FK_invt_ctn_id_inventory_carton` (`invt_ctn_id`),
  CONSTRAINT `FK_count_item_id_count_item` FOREIGN KEY (`count_item_id`) REFERENCES `count_items` (`count_item_id`),
  CONSTRAINT `FK_invt_ctn_id_inventory_carton` FOREIGN KEY (`invt_ctn_id`) REFERENCES `inventory_cartons` (`id`),
  CONSTRAINT `FK_vnd_id_vendor` FOREIGN KEY (`vnd_id`) REFERENCES `vendors` (`id`),
  CONSTRAINT `FK_whs_id_warehouse` FOREIGN KEY (`whs_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add new 2 status in inventory
INSERT INTO statuses (`category`, `displayName`, `shortName`)
VALUES ("inventory", "Locked", "LK"),
("inventory", "Adjusted", "AJ");

-- Add menu
INSERT INTO `submenus` (`hiddenName`, `displayName`, `displayOrder`, `active`)
VALUES ("cycleCount", "Cycle Count", "11.5", 1);

-- Add "Cycle Count Report" in menu Cycle Count
INSERT INTO `pages` (`displayName`, `displayOrder`, `hiddenName`, `class`, `method`, `red`, `active`)
SELECT    "Cycle Count Report" AS displayName,
          1 AS displayOrder,
          "cycleCount" AS hiddenName,
          "cycleCount" AS `class`,
          "create" AS `method`,
          0 AS red,
          1 AS active
FROM      subMenus sm
WHERE     sm.hiddenName = "cycleCount"
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
  WHERE     sm.hiddenName = "cycleCount"
) m;

-- Create Trigger for locked_carton to update inventory_carton status
DELIMITER //
CREATE TRIGGER trg_unlock_inventory_cartons AFTER DELETE ON locked_cartons FOR EACH ROW
BEGIN
UPDATE inventory_cartons
SET inventory_cartons.statusID = old.sts
WHERE inventory_cartons.id = old.invt_ctn_id;
  END //
 DELIMITER ;

DELIMITER //
-- Create Trigger for update carton status when insert lock_cartons
CREATE TRIGGER trg_lock_carton AFTER INSERT ON locked_cartons FOR EACH ROW
  BEGIN

    SET @statusID = (
      SELECT 	id
      FROM 		statuses s
      WHERE 	s.shortName = "LK"
    );

    UPDATE  inventory_cartons
    SET 	  statusID = @statusID
    WHERE   id = new.invt_ctn_id;

    END //
 DELIMITER ; 

-- Create Trigger update count items status when cycle count status changed
DELIMITER //
CREATE TRIGGER trg_update_count_item AFTER UPDATE ON cycle_count FOR EACH ROW
BEGIN
        UPDATE  count_items
        SET act_loc =
        IF (
            act_loc IS NULL,
            sys_loc,
            act_loc
        ),
         act_qty =
        IF (
            act_qty IS NULL,
            sys_qty,
            act_qty
        )
        WHERE cycle_count_id = NEW.cycle_count_id 
        AND NEW.sts IN ("CC", "CP");

        UPDATE  count_items
        SET     sts = "NA"
        WHERE cycle_count_id = NEW.cycle_count_id 
        AND act_loc = sys_loc AND act_qty = sys_qty
        AND NEW.sts IN ("CC", "CP");

        UPDATE  count_items
        SET sts = "OP"
        WHERE cycle_count_id = NEW.cycle_count_id 
        AND (act_loc <> sys_loc OR act_qty <> sys_qty)
        AND NEW.sts IN ("CC");

  END //
 DELIMITER ; 

-- Change trigger trg_unlock_inventory_cartons 

DROP TRIGGER trg_unlock_inventory_cartons;

DELIMITER //
CREATE TRIGGER trg_unlock_inventory_cartons AFTER DELETE ON locked_cartons FOR EACH ROW
BEGIN

 SET @adjustID= (
      SELECT 	id
      FROM 		statuses s
      WHERE 	s.shortName = "AJ"
    );

UPDATE inventory_cartons
SET inventory_cartons.statusID = old.sts
WHERE inventory_cartons.id = old.invt_ctn_id 
AND inventory_cartons.statusID <> @adjustID;

 END //
 DELIMITER ; 

-- Add 2 status: Clone, Delete Clone in to statuses with categories inventory
INSERT INTO statuses (`category`, `displayName`, `shortName`)
VALUES ("inventory", "Clone", "CL"),
  ("inventory", "Delete Clone", "DC");

-- add fields upc_id, pack_size into count_items table.
ALTER TABLE `count_items` ADD `pack_size` INT(10) NOT NULL AFTER `color`;
ALTER TABLE `count_items` ADD `upc_id` INT(10) NOT NULL AFTER `vnd_id`;

-- add field upc_id into locked_cartons table.
ALTER TABLE `locked_cartons` ADD `upc_id` INT(10) NOT NULL AFTER `invt_ctn_id`;

-- add field pack_size into locked_cartons table.
ALTER TABLE `locked_cartons` ADD `pack_size` INT(10) NOT NULL AFTER `upc_id`;

-- Drop trigger trg_lock_carton
DROP TRIGGER trg_lock_carton;

-- Update inventory carton
DELIMITER //
CREATE TRIGGER trg_update_inventory_carton
AFTER INSERT ON count_items
FOR EACH ROW
  BEGIN

    SET @statusID = (
      SELECT 	id
      FROM 		statuses s
      WHERE 	s.shortName = "LK"
    );

    UPDATE inventory_cartons ca
      JOIN inventory_batches ib ON ib.id = ca.batchID
    SET ca.statusID = @statusID
    WHERE ib.upcID = new.upc_id
          AND 	ca.uom = new.pack_size;

  
   END //
 DELIMITER ; 

-- insert locked_cartons
DELIMITER //
CREATE TRIGGER trg_insert_locked_carton
AFTER UPDATE ON inventory_cartons
FOR EACH ROW
  BEGIN

    SET @rackID = (
      SELECT 	id
      FROM 		statuses s
      WHERE 	s.shortName = "RK"
      AND			category = "inventory"
    );

    SET @lockID = (
      SELECT 	id
      FROM 		statuses s
      WHERE 	s.shortName = "LK"
      AND			category = "inventory"
    );

    IF(old.statusID = @rackID && new.statusID = @lockID) THEN
      SET @upcID = (
        SELECT 	upcID
        FROM 		inventory_batches
        WHERE 	old.batchID = id
      );

      INSERT INTO locked_cartons (upc_id, pack_size, invt_ctn_id, sts, created_at)
      VALUES (@upcID, old.uom, old.id, old.statusID, NOW());

    END IF;
 
   END //
 DELIMITER ; 

-- Drop trigger trg_update_inventory_carton
DROP TRIGGER trg_update_inventory_carton;

-- Update inventory carton
DELIMITER //
CREATE TRIGGER trg_update_inventory_carton
AFTER INSERT ON count_items
FOR EACH ROW
  BEGIN

    SET @statusID = (
      SELECT 	id
      FROM 		statuses s
      WHERE 	s.shortName = "LK"
    );

    UPDATE  inventory_cartons ca
    JOIN    inventory_batches ib ON ib.id = ca.batchID
    JOIN    inventory_containers ic ON ic.recNum = ib.recNum
    SET     ca.statusID = @statusID
    WHERE   ib.upcID = new.upc_id
    AND 	  ca.uom = new.pack_size
    AND     ic.vendorID = NEW.vnd_id;

     END //
 DELIMITER ; 

DROP TRIGGER trg_lock_carton;
ALTER TABLE locked_cartons DROP FOREIGN KEY FK_count_item_id_count_item;
ALTER TABLE locked_cartons DROP COLUMN count_item_id;

-- 
DROP TRIGGER trg_insert_locked_carton;
-- insert locked_cartons
DELIMITER //
CREATE TRIGGER trg_insert_locked_carton
AFTER UPDATE ON inventory_cartons 
FOR EACH ROW
BEGIN

SET @rackID = (
    SELECT      id
    FROM        statuses s
    WHERE       s.shortName = "RK"
    AND         category = "inventory"
);

SET @lockID = (
    SELECT      id
    FROM        statuses s
    WHERE 	    s.shortName = "LK"
    AND         category = "inventory"
);

IF(old.statusID = @rackID && new.statusID = @lockID) THEN

SET @upcID = (
      SELECT 	upcID
      FROM 	  inventory_batches
      WHERE 	old.batchID = id
    );

SET @vendorID = (
        SELECT 	ic.vendorID
        FROM 	  inventory_containers ic
        JOIN	  inventory_batches ib ON ib.recNum = ic.recNum
        WHERE 	old.batchID = ib.id
    );

SET @warehouseID = (
        SELECT 	warehouseID
        FROM 	  vendors v
        JOIN 	  inventory_containers ic ON ic.vendorID = v.id
        JOIN	  inventory_batches ib ON ib.recNum = ic.recNum
        WHERE 	old.batchID = ib.id
    );

INSERT INTO locked_cartons (whs_id, vnd_id, upc_id, pack_size, invt_ctn_id, sts, created_at)
VALUES (@warehouseID, @vendorID, @upcID, old.uom, old.id, old.statusID, NOW());

END IF;
 END //
 DELIMITER ; 

-- Drop trigger trg_update_inventory_carton
DROP TRIGGER trg_update_inventory_carton;

-- Update inventory carton
DELIMITER //
CREATE TRIGGER trg_update_inventory_carton
AFTER INSERT ON count_items
FOR EACH ROW
  BEGIN

    SET @statusID = (
      SELECT 	id
      FROM 		statuses s
      WHERE 	s.shortName = "LK"
    );
    SET @rackID = (
      SELECT 	id
      FROM 		statuses s
      WHERE 	s.shortName = "RK"
      AND			category = "inventory"
    );

    UPDATE  inventory_cartons ca
    JOIN    inventory_batches ib ON ib.id = ca.batchID
    JOIN    inventory_containers ic ON ic.recNum = ib.recNum
    SET     ca.statusID = @statusID
    WHERE   ib.upcID = new.upc_id
    AND 	  ca.uom = new.pack_size
    AND     ic.vendorID = NEW.vnd_id
    AND     ca.statusID = @rackID;

   END //
 DELIMITER ; 

DROP TRIGGER trg_update_count_item;
------
DELIMITER //
CREATE TRIGGER trg_update_count_item AFTER UPDATE ON cycle_count FOR EACH ROW
BEGIN
    UPDATE  count_items
    SET act_loc =
    IF (
        act_loc IS NULL,
        sys_loc,
        act_loc
    ),
    act_qty =
    IF (
        act_qty IS NULL,
        sys_qty,
        act_qty
    )
    WHERE cycle_count_id = NEW.cycle_count_id 
    AND NEW.sts IN ("CC", "CP");

    IF  NEW.sts <> OLD.sts THEN

    UPDATE  count_items
    SET     sts = "NA"
    WHERE cycle_count_id = NEW.cycle_count_id 
    AND act_loc = sys_loc AND act_qty = sys_qty
    AND NEW.sts IN ("CC", "CP");

    UPDATE  count_items
    SET sts = "OP"
    WHERE cycle_count_id = NEW.cycle_count_id 
    AND (act_loc <> sys_loc OR act_qty <> sys_qty)
    AND NEW.sts IN ("CC");

    END IF;

   END //
 DELIMITER ; 

-- Modified trigger trg_update_inventory_carton, not get carton isSplit and unSplit
DROP TRIGGER IF EXISTS trg_update_inventory_carton;
DELIMITER //
CREATE TRIGGER trg_update_inventory_carton
AFTER INSERT ON count_items
FOR EACH ROW
  BEGIN

    SET @statusID = (
      SELECT id
      FROM   statuses s
      WHERE  s.shortName = "LK"
    );
    SET @rackID = (
      SELECT id
      FROM   statuses s
      WHERE  s.shortName = "RK"
      AND    category = "inventory"
    );

    UPDATE  inventory_cartons ca
    JOIN    inventory_batches ib ON ib.id = ca.batchID
    JOIN    inventory_containers ic ON ic.recNum = ib.recNum
    SET     ca.statusID = @statusID
    WHERE   ib.upcID = new.upc_id
    AND     ca.uom = new.pack_size
    AND     ic.vendorID = NEW.vnd_id
    AND     ca.statusID = @rackID
    AND     NOT isSplit
    AND     NOT unSplit;

     END //
 DELIMITER ; 


-- Tri Le
-- 31/05/2016

-- Add column created_at with default value is NULL into inventory_cartons table
ALTER TABLE `inventory_cartons` ADD `created_at` DATE NULL AFTER `rackDate`;

-- Change data type created_at field to datetime
ALTER TABLE `inventory_cartons` CHANGE `created_at` `created_at` DATETIME NULL DEFAULT NULL;

-- Tri Le
-- 03/06/2016

-- 
DROP TRIGGER IF EXISTS trg_update_inventory_carton;
DELIMITER //
CREATE TRIGGER trg_update_inventory_carton
AFTER INSERT ON count_items
FOR EACH ROW
  BEGIN
    SET @statusID = (
      SELECT id
      FROM   statuses s
      WHERE  s.shortName = "LK"
    );
    SET @rackID = (
      SELECT id
      FROM   statuses s
      WHERE  s.shortName = "RK"
      AND    category = "inventory"
    );
    IF NEW.sys_qty <> 0 THEN
      -- Case when create cycle
      UPDATE  inventory_cartons ca
      JOIN    inventory_batches ib ON ib.id = ca.batchID
      JOIN    inventory_containers ic ON ic.recNum = ib.recNum
      SET     ca.statusID = @statusID
      WHERE   ib.upcID = NEW.upc_id
      AND     ca.uom = NEW.pack_size
      AND     ic.vendorID = NEW.vnd_id
      AND     ca.statusID = @rackID
      AND     NOT isSplit
      AND     NOT unSplit;
    ELSE
      -- Case when add new SKU
      UPDATE  inventory_cartons ca
      JOIN    inventory_batches ib ON ib.id = ca.batchID
      JOIN    inventory_containers ic ON ic.recNum = ib.recNum
      SET     ca.statusID = @statusID
      WHERE   ib.upcID = NEW.upc_id
      AND     ic.vendorID = NEW.vnd_id
      AND     ca.statusID = @rackID
      AND     NOT isSplit
      AND     NOT unSplit;
    END IF;
    END //
 DELIMITER ; 

ALTER TABLE  `cycle_count` CHANGE  `created`  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ;
ALTER TABLE  `cycle_count` CHANGE  `updated`  `updated` DATETIME ON UPDATE CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ;

DROP TRIGGER IF EXISTS trg_update_count_item;
DELIMITER //
CREATE TRIGGER trg_update_count_item AFTER UPDATE ON cycle_count FOR EACH ROW
BEGIN
    UPDATE  count_items
    SET act_loc =
    IF (
        act_loc IS NULL,
        sys_loc,
        act_loc
    ),
    act_qty =
    IF (
        act_qty IS NULL,
        sys_qty,
        act_qty
    )
    WHERE cycle_count_id = NEW.cycle_count_id 
    AND NEW.sts IN ("CC", "CP");

    IF  NEW.sts <> OLD.sts THEN

    UPDATE  count_items
    SET     sts = "NA"
    WHERE cycle_count_id = NEW.cycle_count_id 
    AND act_loc = sys_loc AND act_qty = sys_qty
    AND NEW.sts IN ("CC", "CP")
    AND sts IN("NW","RC");

    UPDATE  count_items
    SET sts = "OP"
    WHERE cycle_count_id = NEW.cycle_count_id 
    AND (act_loc <> sys_loc OR act_qty <> sys_qty)
    AND NEW.sts IN ("CC")
    AND sts IN("NW","RC");

    END IF;

  END //
 DELIMITER ;

-- add field data
ALTER TABLE `cycle_count` ADD `data` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `updated`;

-- Set default value 2 field created_dt and updated_dt is CURRENT_TIMESTAMP
ALTER TABLE `count_items` CHANGE `created_dt` `created_dt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE  `count_items` CHANGE  `updated_dt`  `updated_dt` DATETIME ON UPDATE CURRENT_TIMESTAMP NULL DEFAULT NULL ;
ALTER TABLE  `locked_cartons` CHANGE  `created_at`  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ;

-- remove trigger for Cycle detail and Audit page
DROP TRIGGER IF EXISTS trg_update_count_item;
DROP TRIGGER IF EXISTS trg_unlock_inventory_cartons;
-- Tri Le
-- 13/06/2016

-- Drop 2 triggers not use for create report.
DROP TRIGGER IF EXISTS trg_update_inventory_carton;
DROP TRIGGER IF EXISTS trg_insert_locked_carton;

--  Drop 2 column upc_id and pack_size
ALTER TABLE `locked_cartons` DROP `upc_id`, DROP `pack_size`;

-- Add column count_item_id
ALTER TABLE `locked_cartons` ADD `count_item_id` INT(10) NOT NULL AFTER `whs_id`;

-- Vadzim
-- 06/13/2016
-- adding DS cartons to the list of statuses that can be processed in Cycle Count

DROP TRIGGER IF EXISTS trg_lock_carton;
DROP TRIGGER IF EXISTS trg_update_count_item;
DROP TRIGGER IF EXISTS trg_update_inventory_carton;
DROP TRIGGER IF EXISTS trg_insert_locked_carton;
DROP TRIGGER IF EXISTS trg_unlock_inventory_cartons;

DELIMITER //
CREATE TRIGGER trg_update_count_item AFTER UPDATE ON cycle_count FOR EACH ROW
  BEGIN

    UPDATE  count_items
    SET act_loc =
    IF (
        act_loc IS NULL,
        sys_loc,
        act_loc
    ),
      act_qty =
      IF (
          act_qty IS NULL,
          sys_qty,
          act_qty
      )
    WHERE cycle_count_id = NEW.cycle_count_id
          AND NEW.sts IN ("CC", "CP");

    IF  NEW.sts <> OLD.sts THEN

      UPDATE  count_items
      SET     sts = "NA"
      WHERE cycle_count_id = NEW.cycle_count_id
            AND act_loc = sys_loc AND act_qty = sys_qty
            AND NEW.sts IN ("CC", "CP")
            AND sts IN("NW","RC");

      UPDATE  count_items
      SET sts = "OP"
      WHERE cycle_count_id = NEW.cycle_count_id
            AND (act_loc <> sys_loc OR act_qty <> sys_qty)
            AND NEW.sts IN ("CC")
            AND sts IN("NW","RC");

    END IF;

  END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER trg_update_inventory_carton
AFTER INSERT ON count_items
FOR EACH ROW
  BEGIN

    SET @statusID = (
      SELECT id
      FROM   statuses s
      WHERE  s.shortName = "LK"
    );

    SET @discrepantID = (
      SELECT id
      FROM   statuses s
      WHERE  s.shortName = "DS"
             AND    category = "inventory"
    );

    SET @rackID = (
      SELECT id
      FROM   statuses s
      WHERE  s.shortName = "RK"
             AND    category = "inventory"
    );
    IF NEW.sys_qty <> 0 THEN
      -- Case when create cycle
      UPDATE  inventory_cartons ca
        JOIN    inventory_batches ib ON ib.id = ca.batchID
        JOIN    inventory_containers ic ON ic.recNum = ib.recNum
      SET     ca.statusID = @statusID
      WHERE   ib.upcID = NEW.upc_id
              AND     ca.uom = NEW.pack_size
              AND     ic.vendorID = NEW.vnd_id
              AND     (ca.statusID = @rackID
                       AND ca.mStatusID = @rackID
                       OR  ca.statusID = @discrepantID
              )
              AND     NOT isSplit
              AND     NOT unSplit;
    ELSE
      -- Case when add new SKU
      UPDATE  inventory_cartons ca
        JOIN    inventory_batches ib ON ib.id = ca.batchID
        JOIN    inventory_containers ic ON ic.recNum = ib.recNum
      SET     ca.statusID = @statusID
      WHERE   ib.upcID = NEW.upc_id
              AND     ic.vendorID = NEW.vnd_id
              AND     (ca.statusID = @rackID
                       AND ca.mStatusID = @rackID
                       OR  ca.statusID = @discrepantID
              )
              AND     NOT isSplit
              AND     NOT unSplit;
    END IF;

  END //
DELIMITER ;


DELIMITER //
CREATE TRIGGER trg_insert_locked_carton
AFTER UPDATE ON inventory_cartons
FOR EACH ROW
  BEGIN

    SET @rackID = (
      SELECT      id
      FROM        statuses s
      WHERE       s.shortName = "RK"
                  AND         category = "inventory"
    );


    SET @discrepantID = (
      SELECT id
      FROM   statuses s
      WHERE  s.shortName = "DS"
             AND    category = "inventory"
    );


    SET @lockID = (
      SELECT      id
      FROM        statuses s
      WHERE 	    s.shortName = "LK"
                 AND         category = "inventory"
    );

    IF((old.statusID = @rackID && old.mStatusID = @rackID || old.statusID = @discrepantID) && new.statusID = @lockID) THEN

      SET @upcID = (
        SELECT 	upcID
        FROM 	  inventory_batches
        WHERE 	old.batchID = id
      );

      SET @vendorID = (
        SELECT 	ic.vendorID
        FROM 	  inventory_containers ic
          JOIN	  inventory_batches ib ON ib.recNum = ic.recNum
        WHERE 	old.batchID = ib.id
      );

      SET @warehouseID = (
        SELECT 	warehouseID
        FROM 	  vendors v
          JOIN 	  inventory_containers ic ON ic.vendorID = v.id
          JOIN	  inventory_batches ib ON ib.recNum = ic.recNum
        WHERE 	old.batchID = ib.id
      );

      INSERT INTO locked_cartons (whs_id, vnd_id, upc_id, pack_size, invt_ctn_id, sts, created_at)
      VALUES (@warehouseID, @vendorID, @upcID, old.uom, old.id, old.statusID, NOW());

    END IF;
  END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER trg_unlock_inventory_cartons AFTER DELETE ON locked_cartons FOR EACH ROW
  BEGIN


    SET @adjustID= (
      SELECT 	id
      FROM 		statuses s
      WHERE 	s.shortName = "AJ"
    );

    UPDATE inventory_cartons
    SET inventory_cartons.statusID = old.sts
    WHERE inventory_cartons.id = old.invt_ctn_id
          AND inventory_cartons.statusID <> @adjustID;

  END //
DELIMITER ;

-- Vadzim
-- 15/6/2016
-- consider location name when updating cartons in inventory_carton table

DROP TRIGGER trg_update_inventory_carton;

DELIMITER //

CREATE TRIGGER trg_update_inventory_carton
AFTER INSERT ON count_items
FOR EACH ROW
  BEGIN

    SET @statusID = (
      SELECT id
      FROM   statuses s
      WHERE  s.shortName = "LK"
    );

    SET @discrepantID = (
      SELECT id
      FROM   statuses s
      WHERE  s.shortName = "DS"
             AND    category = "inventory"
    );

    SET @rackID = (
      SELECT id
      FROM   statuses s
      WHERE  s.shortName = "RK"
             AND    category = "inventory"
    );
    IF NEW.sys_qty <> 0 THEN
      -- Case when create cycle
      UPDATE  inventory_cartons ca
        JOIN    inventory_batches ib ON ib.id = ca.batchID
        JOIN    inventory_containers ic ON ic.recNum = ib.recNum
      SET     ca.statusID = @statusID
      WHERE   ib.upcID = NEW.upc_id
              AND     ca.uom = NEW.pack_size
              AND     ic.vendorID = NEW.vnd_id
              AND     ca.locID = NEW.sys_loc
              AND     (ca.statusID = @rackID
                       AND ca.mStatusID = @rackID
                       OR  ca.statusID = @discrepantID
              )
              AND     NOT isSplit
              AND     NOT unSplit;
    ELSE
      -- Case when add new SKU
      UPDATE  inventory_cartons ca
        JOIN    inventory_batches ib ON ib.id = ca.batchID
        JOIN    inventory_containers ic ON ic.recNum = ib.recNum
      SET     ca.statusID = @statusID
      WHERE   ib.upcID = NEW.upc_id
              AND     ic.vendorID = NEW.vnd_id
              AND     ca.locID = NEW.sys_loc
              AND     (ca.statusID = @rackID
                       AND ca.mStatusID = @rackID
                       OR  ca.statusID = @discrepantID
              )
              AND     NOT isSplit
              AND     NOT unSplit;
    END IF;

  END //
DELIMITER ;

-- Duy Nguyen
-- Create cycle count staff group
INSERT INTO groups (
    groupName, hiddenName, description, active
) VALUES (
    "Cycle Count Staff", "cycleCountStaff", "Cycle Count Staff", "1"
) ON DUPLICATE KEY UPDATE
    description = "Cycle Count Staff",
    active = "1";

-- Add cycle count page into cycle count staff group

INSERT INTO `group_pages` (`groupID`, `pageID`, `active`) 
SELECT    gr.id AS groupID,
          m.pageID AS pageID,
          1 AS active
FROM      groups gr
JOIN      (
    SELECT    p.id AS pageID
    FROM      pages p
    WHERE      p.displayName = "Cycle Count Report"
	AND       p.active
) m
WHERE     gr.hiddenName = "cycleCountStaff"
AND       gr.active
LIMIT 1;

ALTER TABLE  `cycle_count` CHANGE  `data`  `data` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ;

-- add sign out page for cycle count staff group

INSERT INTO `group_pages` (`groupID`, `pageID`, `active`) 
SELECT    gr.id AS groupID,
          m.pageID AS pageID,
          1 AS active
FROM      groups gr
JOIN      (
    SELECT    p.id AS pageID
    FROM      pages p
    WHERE      p.hiddenName = "confirmSignOut"
	AND       p.active
) m
WHERE     gr.hiddenName = "cycleCountStaff"
AND       gr.active
LIMIT 1;

-- Tri Le
-- 06/30/2016

-- Add two field mn_sts_id, mn_loc_id to locked_cartons table
ALTER TABLE `locked_cartons`
ADD `mn_sts_id` INT(2) NULL AFTER `sts`,
ADD `mn_loc_id` INT(7) NULL AFTER `mn_sts_id`;