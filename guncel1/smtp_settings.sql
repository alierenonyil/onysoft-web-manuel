-- SMTP Mail Settings
-- Bu ayarlar admin panelinden yönetilebilir

INSERT INTO `site_settings` (`setting_group`, `setting_key`, `setting_value`, `setting_type`) VALUES
('smtp', 'smtp_enabled', '0', 'boolean'),
('smtp', 'smtp_host', 'smtp.gmail.com', 'text'),
('smtp', 'smtp_port', '587', 'number'),
('smtp', 'smtp_username', '', 'email'),
('smtp', 'smtp_password', '', 'password'),
('smtp', 'smtp_encryption', 'tls', 'text'),
('smtp', 'smtp_from_email', 'noreply@staravcisi.com', 'email'),
('smtp', 'smtp_from_name', 'StarAvcısı E-Ticaret', 'text'),
('smtp', 'smtp_debug', '0', 'boolean')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    setting_type = VALUES(setting_type);
