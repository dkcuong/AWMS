-- phpMyAdmin SQL Dump
-- version 4.2.7.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jan 09, 2015 at 05:16 PM
-- Server version: 5.6.20
-- PHP Version: 5.5.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `thanhco_seldat`
--

-- --------------------------------------------------------

--
-- Table structure for table `invoices_work_orders`
--

CREATE TABLE IF NOT EXISTS `invoices_work_orders` (
`id` int(11) NOT NULL,
  `workordernumber` varchar(10) NOT NULL,
  `woHRush` decimal(8,2) NOT NULL,
  `woHLabour` decimal(8,2) NOT NULL,
  `woHQC` decimal(8,2) NOT NULL,
  `woHOTime` decimal(8,2) NOT NULL,
  `woHUProc` decimal(8,2) NOT NULL,
  `woHMSets` decimal(8,2) NOT NULL,
  `woHSpec` decimal(8,2) NOT NULL,
  `woHApBag` decimal(8,2) NOT NULL,
  `woHBagMt` decimal(8,2) NOT NULL,
  `woHRmApBag` decimal(8,2) NOT NULL,
  `woHRmBag` decimal(8,2) NOT NULL,
  `woHBLblMt` decimal(8,2) NOT NULL,
  `woHCLabel` decimal(8,2) NOT NULL,
  `woHCMark` decimal(8,2) NOT NULL,
  `woHCrtMt` decimal(8,2) NOT NULL,
  `newCartonUsed` int(3) NOT NULL,
  `boxSize` decimal(6,2) NOT NULL,
  `cartonVol` decimal(8,2) NOT NULL,
  `woHPrApLbl` decimal(8,2) NOT NULL,
  `woHApLbl` decimal(8,2) NOT NULL,
  `woHRmCLbl` decimal(8,2) NOT NULL,
  `woHSType1` decimal(8,2) NOT NULL,
  `woHSType2` decimal(8,2) NOT NULL,
  `woHSType3` decimal(8,2) NOT NULL,
  `woHSType4` decimal(8,2) NOT NULL,
  `woHSType5` decimal(8,2) NOT NULL,
  `woHFold` decimal(8,2) NOT NULL,
  `woHApHng` decimal(8,2) NOT NULL,
  `woHHngMt` decimal(8,2) NOT NULL,
  `woHRmApH` decimal(8,2) NOT NULL,
  `woHRmHng` decimal(8,2) NOT NULL,
  `woHApSt` decimal(8,2) NOT NULL,
  `woHRmApSt` decimal(8,2) NOT NULL,
  `woHRmSt` decimal(8,2) NOT NULL,
  `woHStMt` decimal(8,2) NOT NULL,
  `woHCutSew` decimal(8,2) NOT NULL,
  `woHApSz` decimal(8,2) NOT NULL,
  `woHRmApSz` decimal(8,2) NOT NULL,
  `woHRmSz` decimal(8,2) NOT NULL,
  `woHSzMt` decimal(8,2) NOT NULL,
  `woHRep` decimal(8,2) NOT NULL,
  `woHSort` decimal(8,2) NOT NULL,
  `woHPPack` decimal(8,2) NOT NULL,
  `woHApPT` decimal(8,2) NOT NULL,
  `woHApPTS` decimal(8,2) NOT NULL,
  `woHPTSMt` decimal(8,2) NOT NULL,
  `woHRmApPT` decimal(8,2) NOT NULL,
  `woHRmApPTS` decimal(8,2) NOT NULL,
  `woHRmPT` decimal(8,2) NOT NULL,
  `woHRmPTS` decimal(8,2) NOT NULL,
  `statusID` int(3) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `invoices_work_orders`
--

--
-- Indexes for dumped tables
--

--
-- Indexes for table `invoices_work_orders`
--
ALTER TABLE `invoices_work_orders`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `workordernumber` (`workordernumber`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `invoices_work_orders`
--
ALTER TABLE `invoices_work_orders`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
