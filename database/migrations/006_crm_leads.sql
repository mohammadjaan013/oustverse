-- ============================================
-- Migration: CRM Leads Module
-- ============================================

USE biziverse_erp;

-- Leads/Prospects table
CREATE TABLE IF NOT EXISTS `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_name` varchar(255) NOT NULL,
  `title` varchar(10) DEFAULT 'Mr',
  `first_name` varchar(100) DEFAULT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `whatsapp` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `stage` enum('raw','new','discussion','demo','proposal','decided','inactive') DEFAULT 'raw',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `assigned_to` int(11) UNSIGNED DEFAULT NULL,
  `requirements` text,
  `notes` text,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `pincode` varchar(20) DEFAULT NULL,
  `gstin` varchar(50) DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `product` varchar(255) DEFAULT NULL,
  `potential` decimal(15,2) DEFAULT '0.00',
  `since_date` date DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `is_starred` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive','converted') DEFAULT 'active',
  `last_activity_date` datetime DEFAULT NULL,
  `next_followup_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_stage` (`stage`),
  KEY `idx_status` (`status`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lead activities/interactions table
CREATE TABLE IF NOT EXISTS lead_activities (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    lead_id INT(11) NOT NULL,
    activity_type ENUM('call', 'email', 'meeting', 'note', 'whatsapp', 'status_change') NOT NULL,
    subject VARCHAR(200),
    description TEXT,
    activity_date DATETIME,
    duration INT,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_lead (lead_id),
    INDEX idx_activity_date (activity_date),
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lead appointments table
CREATE TABLE IF NOT EXISTS lead_appointments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    lead_id INT(11) NOT NULL,
    title VARCHAR(200) NOT NULL,
    appointment_date DATETIME NOT NULL,
    duration INT DEFAULT 60,
    location VARCHAR(200),
    notes TEXT,
    status ENUM('scheduled', 'completed', 'cancelled', 'rescheduled') DEFAULT 'scheduled',
    reminder_sent TINYINT(1) DEFAULT 0,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_lead (lead_id),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_status (status),
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data
INSERT INTO leads (business_name, contact_name, email, phone, source, stage, requirements, assigned_to, created_by, created_at) VALUES
('Team Rustic', 'Mr. Shankar Raja', 'shankar@teamrustic.com', '9876543210', 'Mail', 'raw', 'New ABC 5KG', 1, 1, '2025-04-24'),
('URBANWRK PVT LTD', 'Mr. TUSHAR WANJALE', 'tushar@urbanwrk.com', '9123456789', 'Mail', 'new', 'New Fire Extinguishers', 2, 1, '2025-04-18');
