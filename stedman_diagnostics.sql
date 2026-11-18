-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2026 at 11:27 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stedman_diagnostics`
--

-- --------------------------------------------------------

--
-- Table structure for table `instrument_master`
--

CREATE TABLE `instrument_master` (
  `id` int(11) NOT NULL,
  `firestore_doc_id` varchar(100) DEFAULT NULL,
  `lab_id` int(11) NOT NULL,
  `instrument_name` varchar(200) NOT NULL,
  `model_number` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) NOT NULL,
  `installation_date` date DEFAULT NULL,
  `warranty_status` varchar(50) DEFAULT NULL,
  `warranty_expiry_date` date DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `manufacturer` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instrument_master`
--

INSERT INTO `instrument_master` (`id`, `firestore_doc_id`, `lab_id`, `instrument_name`, `model_number`, `serial_number`, `installation_date`, `warranty_status`, `warranty_expiry_date`, `status`, `manufacturer`) VALUES
(1, 'DOC001', 2, 'Fully Automated Biochemistry Analyzer', 'BA-400', 'STD-BA-1001', '2025-01-15', 'Active', '2027-01-14', 'Active', NULL),
(2, 'DOC002', 2, 'Hematology Analyzer', 'HA-300', 'STD-HA-1002', '2025-03-10', 'Active', '2027-03-09', 'Active', NULL),
(3, 'DOC003', 3, 'Electrolyte Analyzer', 'EA-200', 'STD-EA-1003', '2024-06-20', 'Expired', '2026-06-19', 'Active', ''),
(4, 'DOC004', 1, 'Semi-Automatic Biochemistry Analyzer', 'SBA-100', 'STD-SBA-1004', '2026-02-01', 'Active', '2028-01-31', 'Active', NULL),
(5, 'DOC005', 2, 'Urine Analyzer', 'UA-150', 'STD-UA-1005', '2024-04-12', 'Expired', '2026-04-11', 'Active', ''),
(6, NULL, 1, 'test by subhanu', '0000000000', '000000', '2003-01-01', 'Expired', '2002-01-01', 'Active', ''),
(7, NULL, 8, 'test1', '0000000000', '00000000', '2002-02-02', 'Expired', '2002-01-01', 'Active', '');

-- --------------------------------------------------------

--
-- Table structure for table `lab_master`
--

CREATE TABLE `lab_master` (
  `id` int(11) NOT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `firestore_doc_id` varchar(100) DEFAULT NULL,
  `diagnostic_lab_name` varchar(200) NOT NULL,
  `lab_incharge_contact` varchar(200) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `email_address` varchar(150) DEFAULT NULL,
  `city_location` varchar(100) DEFAULT NULL,
  `full_address` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_master`
--

INSERT INTO `lab_master` (`id`, `customer_code`, `firestore_doc_id`, `diagnostic_lab_name`, `lab_incharge_contact`, `mobile_number`, `email_address`, `city_location`, `full_address`, `status`) VALUES
(1, NULL, 'DOC001', 'ABC Diagnostic Lab', 'Ramesh Kumar', '9876543210', 'abc@example.com', 'Chennai', 'test1', 'Active'),
(2, NULL, 'DOC002', 'XYZ Diagnostics', 'Priya Devi', '9876543211', 'xyz@example.com', 'Kanchipuram', NULL, 'Active'),
(3, NULL, 'DOC003', 'City Scan Centre', 'Suresh Kumar', '9876543212', 'city@example.com', 'Vellore', NULL, 'Active'),
(8, NULL, NULL, 'test1', 'test1', '0000000000', 'test1@gmail.com', 'test1', 'test1', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `service_calls`
--

CREATE TABLE `service_calls` (
  `id` int(11) NOT NULL,
  `call_id` varchar(50) NOT NULL,
  `status` varchar(50) DEFAULT 'Call Logged',
  `lab_id` int(11) NOT NULL,
  `instrument_id` int(11) DEFAULT NULL,
  `call_type` enum('Application Call','Service Call') NOT NULL,
  `problem_reported` text DEFAULT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `assigned_engineer` varchar(200) DEFAULT NULL,
  `closed_by` varchar(200) DEFAULT NULL,
  `closed_by_role` varchar(100) DEFAULT NULL,
  `logged_datetime` datetime DEFAULT NULL,
  `logged_by` varchar(200) DEFAULT NULL,
  `visit_in_time` datetime DEFAULT NULL,
  `visit_out_time` datetime DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `actions_taken` text DEFAULT NULL,
  `spares_required` enum('Yes','No') DEFAULT NULL,
  `spares_replaced` enum('Yes','No') DEFAULT NULL,
  `spares_status` varchar(100) DEFAULT NULL,
  `spares_details` text DEFAULT NULL,
  `signed_report_url` varchar(500) DEFAULT NULL,
  `lab_representative` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_calls`
--

INSERT INTO `service_calls` (`id`, `call_id`, `status`, `lab_id`, `instrument_id`, `call_type`, `problem_reported`, `contact_person`, `mobile`, `assigned_engineer`, `closed_by`, `closed_by_role`, `logged_datetime`, `logged_by`, `visit_in_time`, `visit_out_time`, `observations`, `actions_taken`, `spares_required`, `spares_replaced`, `spares_status`, `spares_details`, `signed_report_url`, `lab_representative`, `created_at`) VALUES
(1, 'SD-20261019-065823-946', 'Completed', 1, 1, 'Service Call', 'test1', 'Ramesh Kumar', '9876543210', '', 'Stedman Administrator', 'Admin', '2026-10-19 06:58:23', NULL, '2026-10-19 09:12:00', '2026-10-19 09:30:00', 'test1', 'test1', NULL, NULL, 'Not Required', 'test1', 'uploads/signed_report_SD-20261019-065823-946_6de75fea963b0779.pdf', 'test1', '2026-10-19 04:58:23'),
(2, 'SD-20261019-164356-9BC1B4', 'Call Logged', 2, NULL, 'Service Call', 'test2', 'Priya Devi', '9876543211', '', NULL, NULL, '2026-10-19 16:43:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-19 14:43:56'),
(3, 'SD-20261020-070626-1C0A4E', 'Spares to be Replaced', 1, 1, 'Service Call', 'test 3', 'Ramesh Kumar', '9876543210', '', NULL, NULL, '2026-10-20 07:06:26', NULL, '2026-10-20 12:58:00', '2026-10-20 12:58:00', 'test 3', 'test 3', NULL, NULL, 'Required - To Be Replaced', 'test1', NULL, NULL, '2026-10-20 05:06:26'),
(4, 'SD-20261020-074043-4B5DF2', 'Completed', 1, 1, 'Service Call', 'test4', 'Suresh Kumar', '9876543212', '', 'kaviraj', 'Service Person', '2026-10-20 07:40:43', NULL, '2026-10-20 11:25:00', '2026-10-20 11:25:00', 'test4', 'test4', NULL, NULL, 'Not Required', 'test4', 'uploads/signed_report_SD-20261020-074043-4B5DF2_45321c9bcdcb708e.pdf', 'test4', '2026-10-20 05:40:43'),
(5, 'SD-20261020-090641-C67F68', 'Completed', 1, 4, 'Application Call', 'test5', 'Ramesh Kumar', '9876543210', NULL, 'Kaviraj', NULL, '2026-10-20 09:06:41', 'Kaviraj', '2026-10-20 12:37:00', '2026-10-20 12:37:00', 'test5', 'test5', NULL, NULL, 'Required', 'test5', 'uploads/signed_report_SD-20261020-090641-C67F68_52ff70aeb59c71e7.pdf', NULL, '2026-10-20 07:06:41'),
(6, 'SD-20261020-091641-8BE1FC', 'Call Logged', 1, NULL, 'Service Call', 'test1', 'Ramesh Kumar', '9876543210', NULL, NULL, NULL, '2026-10-20 09:16:41', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-20 07:16:41'),
(7, 'SD-20261020-092938-9A2D89', 'Pending', 1, 4, 'Service Call', 'test1', 'Ramesh Kumar', '9876543210', NULL, NULL, NULL, '2026-10-20 09:29:38', 'Kaviraj', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-20 07:29:38'),
(8, 'SD-20261021-072648-9960A7', 'Pending', 3, NULL, 'Service Call', 'TEST', 'Ramesh Kumar', '9876543210', NULL, NULL, NULL, '2026-10-21 07:26:48', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-21 05:26:48'),
(9, 'SD-20261021-075011-C8FDA4', 'Spares to be Replaced', 2, 1, 'Application Call', 'test by subhanu', 'Priya Devi', '9876543211', NULL, NULL, NULL, '2026-10-21 07:50:11', 'admin', '2026-10-21 11:20:00', '2026-10-21 11:20:00', 'test by subhanu', 'test by subhanu', NULL, NULL, 'Required - To Be Replaced', 'test by subhanu', NULL, NULL, '2026-10-21 05:50:11'),
(10, 'SD-20261021-093810-8661E4', 'Completed', 1, 4, 'Service Call', 'test', 'Ramesh Kumar', '9876543210', NULL, 'Kaviraj', NULL, '2026-10-21 09:38:10', 'Kaviraj', '2026-10-21 13:16:00', '2026-10-21 13:16:00', 'test', 'test', NULL, NULL, 'Required - Replaced', 'test', 'uploads/signed_report_SD-20261021-093810-8661E4_b9dc7270137e1466.pdf', NULL, '2026-10-21 07:38:10'),
(11, 'SD-20261022-100948-696159', 'Pending', 3, 3, 'Service Call', 'test', 'Suresh Kumar', '9876543212', NULL, NULL, NULL, '2026-10-22 10:09:48', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-22 04:39:48'),
(12, 'SD-20261022-101227-7DE058', 'Pending', 8, NULL, 'Service Call', 'test1', 'test1', '0000000000', NULL, NULL, NULL, '2026-10-22 10:12:27', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-22 04:42:27'),
(13, 'SD-20261022-101325-C6F903', 'Completed', 8, 7, 'Service Call', 'test1', 'test1', '0000000000', NULL, 'admin', NULL, '2026-10-22 10:13:25', 'admin', '2026-10-22 10:13:00', '2026-10-22 10:13:00', 'test1', 'test1', 'Yes', 'Yes', 'Required - Replaced', 'test1', 'uploads/signed_report_SD-20261022-101325-C6F903_f5b02325a1ea6cff.pdf', NULL, '2026-10-22 04:43:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` enum('Admin','Service Person') NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(150) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `status`, `created_at`, `email`, `mobile_number`) VALUES
(1, 'admin', 'Admin@12345', 'Stedman Administrator', 'Admin', 'Active', '2026-10-18 08:12:19', NULL, NULL),
(2, 'Kaviraj', 'chennai1', 'kaviraj', 'Service Person', 'Active', '2026-10-18 08:15:04', NULL, NULL),
(3, 'serviceperson2', 'serviceperson2', 'Service Person Two', 'Service Person', 'Active', '2026-10-18 08:15:04', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `instrument_master`
--
ALTER TABLE `instrument_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_serial_number` (`serial_number`),
  ADD UNIQUE KEY `firestore_doc_id` (`firestore_doc_id`),
  ADD KEY `fk_instrument_lab` (`lab_id`);

--
-- Indexes for table `lab_master`
--
ALTER TABLE `lab_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `firestore_doc_id` (`firestore_doc_id`),
  ADD UNIQUE KEY `uq_lab_master_customer_code` (`customer_code`);

--
-- Indexes for table `service_calls`
--
ALTER TABLE `service_calls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `call_id` (`call_id`),
  ADD KEY `fk_service_lab` (`lab_id`),
  ADD KEY `fk_service_instrument` (`instrument_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `instrument_master`
--
ALTER TABLE `instrument_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lab_master`
--
ALTER TABLE `lab_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `service_calls`
--
ALTER TABLE `service_calls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `instrument_master`
--
ALTER TABLE `instrument_master`
  ADD CONSTRAINT `fk_instrument_lab` FOREIGN KEY (`lab_id`) REFERENCES `lab_master` (`id`);

--
-- Constraints for table `service_calls`
--
ALTER TABLE `service_calls`
  ADD CONSTRAINT `fk_service_instrument` FOREIGN KEY (`instrument_id`) REFERENCES `instrument_master` (`id`),
  ADD CONSTRAINT `fk_service_lab` FOREIGN KEY (`lab_id`) REFERENCES `lab_master` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
