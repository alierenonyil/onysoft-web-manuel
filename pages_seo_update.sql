-- Add SEO fields to pages table

ALTER TABLE `pages`
ADD COLUMN `meta_description` text DEFAULT NULL AFTER `content`,
ADD COLUMN `meta_keywords` varchar(255) DEFAULT NULL AFTER `meta_description`,
ADD COLUMN `show_in_menu` tinyint(1) DEFAULT 1 AFTER `meta_keywords`,
ADD COLUMN `menu_order` int(11) DEFAULT 0 AFTER `show_in_menu`;
