CREATE TABLE IF NOT EXISTS `branch_requests` (
  `requestID` int(11) NOT NULL AUTO_INCREMENT,
  `sourceBranch` varchar(255) NOT NULL,
  `destinationBranch` varchar(255) NOT NULL,
  `products` text NOT NULL, -- JSON array of product IDs and info
  `notes` text DEFAULT NULL,
  `requiredDate` date NOT NULL,
  `dateRequested` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('PENDING','APPROVED','DECLINED','COMPLETED','CANCELED') NOT NULL DEFAULT 'PENDING',
  `requestedBy` int(11) NOT NULL, -- Employee ID
  `respondedBy` int(11) DEFAULT NULL, -- Employee ID who approved/declined
  `completedBy` int(11) DEFAULT NULL, -- Employee ID who marked as completed
  `completedDate` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`requestID`),
  KEY `idx_source_branch` (`sourceBranch`),
  KEY `idx_destination_branch` (`destinationBranch`),
  KEY `idx_requested_by` (`requestedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
