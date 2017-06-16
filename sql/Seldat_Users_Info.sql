CREATE TABLE IF NOT EXISTS `info` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(32) NOT NULL,
  `email` varchar(50) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Full Name` (`firstName`,`lastName`),
  UNIQUE KEY `Username` (`username`),
  UNIQUE KEY `Email` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=30 ;

--
-- Dumping data for table `info`
--

INSERT INTO `info` (`id`, `firstName`, `lastName`, `username`, `password`, `email`, `active`) VALUES
(1, 'Daniel', 'Dadoun', 'ddadoun', '0ec7634467ba0942ffbe3d33bd955f07', 'daniel@seldatinc.com', 1),
(2, 'Jon', 'Sapp', 'jsapp', '488d34e32e6d29e550b9e461cc631786', 'jonathan.sapp@seldatinc.com', 1),
(3, 'Patrick', 'Rong', 'prong', '8e09c89613f9811b6c189286b1e56c44', 'patrick.rong@seldatinc.com', 1),
(4, 'Vadzim', 'Mechnik', 'vmechnik', '4c9aaabbaadfcb595660879496387ca3', 'vadzim.mechnik@seldatinc.com', 1),
(5, 'Sherry', 'DiShao', 'sdishao', 'f85b738e75255e482d6f03fe2ff12ce3', 'di.shao@seldatinc.com', 1),
(6, 'Prathab', 'Kandasamy', 'pkand', '2b8bf13cc3ebf69804619d14f73ff2c3', 'prathab@seldatinc.com', 1),
(7, 'Alfredo', 'Layne', 'alfredo', '5c2bf15004e661d7b7c9394617143d07', 'alfredo@golifeworks.com', 1),
(8, 'Michael', 'Zammit', 'mzammit', '9d45a7bbe9cec4b9e0961279a33befb7', 'mzammit@appliance.com', 1),
(9, 'Andres', 'Peralta', 'aperalta', 'b90a88d8a810af1e776ba8234299988d', 'andres.peralta@seldatinc.com', 1),
(10, 'Andres', 'Rojas', 'arojas', '67c07188c7aac8f7c96f89ac572385cc', 'andres.rojas@seldatinc.com', 1),
(11, 'Robert', 'De Falco', 'rdefalco', '2d58dcf92b666f833e6615a976d7f401', 'robert.defalco@seldatinc.com', 1),
(12, 'Anthony', 'Basantes', 'abasantes', 'd1535f3acc5313b7a89bf69ccffd8ac0', 'anthony.basantes@seldatinc.com', 1),
(13, 'Wesley', 'Cooper', 'wcooper', 'f0e333f3c34d74aab167bdf4fb56aaa3', 'wesley.cooper@seldatinc.com', 1),
(14, 'Greg', 'Martin', 'gmartin', '48d44cdd65fc5e9339fca30ee4ecb45d', 'greg@seldatinc.com', 1),
(15, 'Dana', 'Borisov', 'dborisov', '1d204af058fa3b531a4edc6e2ab6419b', 'dana.borisov@seldatinc.com', 1),
(16, 'Victoria', 'Letrinh', 'vletrinh', '13f70d2cf648dfe2c86293ea1f3eec0c', 'victoria.letrinh@seldatinc.com', 1),
(17, 'Iohan', 'Moreno', 'imoreno', '338a2b82b06772c785999cee37b3301e', 'iohan.moreno@seldatinc.com', 1),
(18, 'Estefani', 'Lopez', 'elopez', '0166e74e19142d4a9e5b9d3c274ef092', 'estefani.lopez@seldatinc.com', 1),
(19, 'Bryan', 'Muniz', 'bmuniz', '2b6b16d29dc3885bbdb10ee941d1e2eb', 'bryan.muniz@seldatinc.com', 1),
(20, 'Alex', 'Flores', 'aflores', 'cffd155a2a575f4b803c3e232ec656db', 'alex.flores@seldatinc.com', 1),
(21, 'Kiet', 'Nguyen', 'knguyen', '06e983f797b225749184cc7fb2a9312b', 'kiet.nguyen@seldatinc.com', 1),
(22, 'Yigal', 'Dadoun', 'ydadoun', '9d129b2f4b5e521e016b586a09784cab', 'yigal.dadoun@seldatinc.com', 1),
(23, 'Michael', 'Glova', 'mglova', 'b708479597428248afc9e753322c447e', 'michael.glova@seldatinc.com', 1),
(24, 'Manuel', 'Tuba', 'mtuba', '28815393a3bb02312374d9678b416bd4', 'manuel.tuba@seldatinc.com', 1),
(25, 'Mariela', 'gomez', 'mgomez', 'f5ddb541cd46d6f5606e830a3e4d3c60', 'mariela.gomez@seldatinc.com', 1),
(26, 'Hanh', 'Nguyen', 'hnguyen', 'aa25901256cd9d84e5f5732c27926872', 'hanh.nguyen@seldatinc.com', 1),
(27, 'Tam', 'Nguyen', 'tnguyen', '6eefcb5d7e895e7cbaa517042bc32bc4', 'tam.nguyen@seldatinc.com', 1),
(28, 'Lizbeth', 'Iturriaga', 'liturriaga', '06ef9ebfe9ba3d6273d8b70f87fab2cf', 'lizbeth.iturriaga@seldatinc.com', 1),
(29, 'Justin', 'Basantes', 'jbasantes', '03826175522ae3495f852208fa7e4c3f', 'justin.basantes@seldatinc.com', 1);

