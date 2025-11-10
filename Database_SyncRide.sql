SET NAMES utf8mb4;


CREATE TABLE `Logs` (
  `logID` int(11) NOT NULL AUTO_INCREMENT,
  `Action` varchar(255) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`logID`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `Logs` (logID, Action, date) VALUES ('61', 'Histórico de ações limpo', '2025-05-12 15:00:18');
INSERT INTO `Logs` (logID, Action, date) VALUES ('62', 'Eliminou todas as viagens', '2025-08-22 19:21:46');
INSERT INTO `Logs` (logID, Action, date) VALUES ('63', 'Eliminou todas as viagens', '2025-08-22 19:23:18');
INSERT INTO `Logs` (logID, Action, date) VALUES ('64', 'Backup da base de dados realizado', '2025-10-15 14:22:21');




CREATE TABLE `Services` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `serviceDate` date NOT NULL,
  `serviceStartTime` time NOT NULL,
  `paxADT` int(11) NOT NULL,
  `paxCHD` int(11) NOT NULL,
  `serviceStartPoint` varchar(255) NOT NULL,
  `serviceTargetPoint` varchar(255) NOT NULL,
  `FlightNumber` varchar(256) DEFAULT NULL,
  `NomeCliente` varchar(256) DEFAULT NULL,
  `ClientNumber` varchar(256) DEFAULT NULL,
  `serviceType` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=536 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `Services` (ID, serviceDate, serviceStartTime, paxADT, paxCHD, serviceStartPoint, serviceTargetPoint, FlightNumber, NomeCliente, ClientNumber, serviceType) VALUES ('532', '2025-10-16', '16:21:00', '3', '2', '2', '1', '2', 'Ola', '934478588', '1');
INSERT INTO `Services` (ID, serviceDate, serviceStartTime, paxADT, paxCHD, serviceStartPoint, serviceTargetPoint, FlightNumber, NomeCliente, ClientNumber, serviceType) VALUES ('533', '2025-09-01', '16:42:00', '1', '1', '1', '1', '1', '1', '1', '1');
INSERT INTO `Services` (ID, serviceDate, serviceStartTime, paxADT, paxCHD, serviceStartPoint, serviceTargetPoint, FlightNumber, NomeCliente, ClientNumber, serviceType) VALUES ('534', '2025-11-08', '18:38:00', '1', '1', 'GaiaShopping', 'Badajoz', '1', '1', '1', '1');
INSERT INTO `Services` (ID, serviceDate, serviceStartTime, paxADT, paxCHD, serviceStartPoint, serviceTargetPoint, FlightNumber, NomeCliente, ClientNumber, serviceType) VALUES ('535', '2025-11-09', '18:38:00', '1', '1', 'lever', 'crestuma', '1', '1', '1', '1');




CREATE TABLE `Services_Rides` (
  `associationID` int(11) NOT NULL AUTO_INCREMENT,
  `RideID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  PRIMARY KEY (`associationID`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `Services_Rides` (associationID, RideID, UserID) VALUES ('97', '532', '22');
INSERT INTO `Services_Rides` (associationID, RideID, UserID) VALUES ('98', '533', '25');
INSERT INTO `Services_Rides` (associationID, RideID, UserID) VALUES ('99', '534', '25');
INSERT INTO `Services_Rides` (associationID, RideID, UserID) VALUES ('100', '535', '25');




CREATE TABLE `Users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` int(11) NOT NULL,
  `name` varchar(256) NOT NULL,
  `phone` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `Users` (id, email, password, role, name, phone) VALUES ('19', 'tiagofsilva04@gmail.com', '$2y$10$CYWGvnX8/VoQc8bsb36pL.YfxLltdNPZsI1OZGrzAcToCCDAL9zYO', '1', 'tiago silva', '934478588');
INSERT INTO `Users` (id, email, password, role, name, phone) VALUES ('21', 'joel@gmail.com', '$2y$10$nNgJQe1GcvvQr8vojtC5fuuo9DDPCTgz2Dcswua1l4Iti4fnJLjui', '2', 'Joel Amaral', '934478588');
INSERT INTO `Users` (id, email, password, role, name, phone) VALUES ('22', 'carlos@gmail.com', '$2y$10$/rboVo1tyGrVaUCrTgYgvOHrvrYIXcU55crpRDysy14GCcQNN2xFq', '2', 'Amílcar', '934478588');
INSERT INTO `Users` (id, email, password, role, name, phone) VALUES ('23', 'julio@gmail.com', '$2y$10$PwqvWOPRT4gzWtNkEW353eo.sm5R6tL9gA23D0R0E.SeU21PhQ8xi', '1', 'julio reis', '934478588');
INSERT INTO `Users` (id, email, password, role, name, phone) VALUES ('25', 'tiago@gmail.com', '$2y$10$iVqWUE40jd5MXgr3chk5OO0rdyAxR8/bwgrUi7X8re7uTPZyFM5ZW', '2', 'tiago silva', '934478588');


