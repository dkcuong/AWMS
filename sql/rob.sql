ALTER TABLE `neworder` ADD `holdStatus` VARCHAR(10) NULL ;


--changes to statuses and neworder tables

UPDATE `statuses` SET `displayName` = 'Off Hold', `shortName` = 'NOHO' WHERE `statuses`.`id` = 25;

ALTER TABLE `neworder` DROP `holdStatus`;

ALTER TABLE `neworder` ADD `holdStatusID` INT(2) NULL DEFAULT NULL ;

--changes to set status as statusID in neworder table

ALTER TABLE `neworder` ADD `statusID` INT(2) NULL DEFAULT NULL AFTER `Status`;

UPDATE neworder INNER JOIN statuses ON neworder.status = statuses.shortName SET neworder.statusID = statuses.id

--note I left status in there for now will remove after further testing

--changed routstatus toroutstatus ID 

ALTER TABLE `neworder` CHANGE `RoutedStatus` `RoutedStatusID` INT(2) NULL DEFAULT NULL;

UPDATE neworder INNER JOIN statuses ON neworder.routedStatus = statuses.shortName SET neworder.routedStatus = statuses.id;

--added routing category and inserted in statuses

INSERT INTO `statuses` (`id`, `category`, `displayName`, `shortName`) VALUES (NULL, 'Routing', 'Routing Check In', 'RTCI'), (NULL, 'Routing', 'Routing Check Out', 'RTCO');

--03-13-2015 status table update for hold orders
UPDATE `statuses` SET `category` = 'hold' WHERE category='orders' AND shortname = 'ONHO';
UPDATE `statuses` SET `category` = 'hold' WHERE category='orders' AND shortname = 'NOHO';