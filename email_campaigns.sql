-- Email Campaigns and Templates Tables

-- ============================================
-- EMAIL CAMPAIGNS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS `email_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `recipient_type` enum('all_customers','newsletter','custom') NOT NULL DEFAULT 'all_customers',
  `recipient_emails` text DEFAULT NULL,
  `recipient_count` int(11) DEFAULT 0,
  `sent_count` int(11) DEFAULT 0,
  `failed_count` int(11) DEFAULT 0,
  `status` enum('draft','sending','completed') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EMAIL TEMPLATES TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text,
  `content` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default email templates
INSERT INTO `email_templates` (`name`, `subject`, `description`, `content`) VALUES
('Hoşgeldin Emaili', 'Hoş Geldiniz - {site_name}', 'Yeni müşteriler için hoşgeldin mesajı',
'<h2>Merhaba {name}!</h2>
<p>Aramıza hoş geldiniz! {site_name} ailesine katıldığınız için çok mutluyuz.</p>
<p>Size özel fırsatlar ve yeni ürünlerden haberdar olmak için bizi takip edin.</p>
<p><a href="{site_url}" style="background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Alışverişe Başla</a></p>
<p>Teşekkürler,<br>{site_name} Ekibi</p>');
