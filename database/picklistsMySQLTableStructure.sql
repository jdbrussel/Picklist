-- phpMyAdmin SQL Dump
-- version 4.2.12deb2+deb8u3
-- http://www.phpmyadmin.net
--
-- Machine: localhost
-- Gegenereerd op: 28 jul 2022 om 12:20
-- Serverversie: 5.5.58-0+deb8u1
-- PHP-versie: 7.0.33-1~dotdeb+8.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Databank: `picklists`
--
CREATE DATABASE IF NOT EXISTS `picklists` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `picklists`;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `campagnes`
--

CREATE TABLE IF NOT EXISTS `campagnes` (
`id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `container_id` int(11) DEFAULT NULL,
  `erp_id` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `name` varchar(100) CHARACTER SET latin1 NOT NULL,
  `pick_datetime` timestamp NULL DEFAULT NULL,
  `status` enum('pending','handling','fulfilment') CHARACTER SET utf8 DEFAULT 'pending',
  `type` enum('dagdoos','weekdoos','campagne') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'campagne',
  `excluded_locations` text COLLATE utf8_unicode_ci,
  `palletlist_address` text COLLATE utf8_unicode_ci,
  `palletlist_num_items` int(11) DEFAULT NULL,
  `archive` int(1) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=448 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `campagne_container_boxes`
--

CREATE TABLE IF NOT EXISTS `campagne_container_boxes` (
`id` int(11) NOT NULL,
  `campagne_id` int(11) DEFAULT NULL,
  `dc_container_box_id` int(11) DEFAULT NULL,
  `name` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `campagne_products` text CHARACTER SET latin1,
  `initial` int(1) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1047 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `campagne_dc`
--

CREATE TABLE IF NOT EXISTS `campagne_dc` (
`id` int(11) NOT NULL,
  `campagne_id` int(11) DEFAULT NULL,
  `dc_id` int(11) DEFAULT NULL,
  `filename` varchar(150) CHARACTER SET latin1 DEFAULT NULL,
  `src` varchar(150) CHARACTER SET latin1 DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=530 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `campagne_dc_trucks`
--

CREATE TABLE IF NOT EXISTS `campagne_dc_trucks` (
`id` int(11) NOT NULL,
  `campagne_id` int(11) NOT NULL,
  `dc_id` int(11) NOT NULL,
  `loading_order` int(1) NOT NULL DEFAULT '0',
  `due_date` varchar(100) CHARACTER SET latin1 NOT NULL,
  `due_time` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `due_datetime` datetime DEFAULT NULL,
  `printed_datetime` datetime DEFAULT NULL,
  `printed_user_id` int(11) DEFAULT NULL,
  `picking_datetime` datetime DEFAULT NULL,
  `picking_user_id` int(11) DEFAULT NULL,
  `picked_datetime` datetime DEFAULT NULL,
  `picked_user_id` int(11) DEFAULT NULL,
  `send_datetime` datetime DEFAULT NULL,
  `send_user_id` int(11) DEFAULT NULL,
  `loading_datetime` datetime DEFAULT NULL,
  `loading_user_id` int(11) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `delivery_note` text COLLATE utf8_unicode_ci,
  `status` enum('pending','ready-for-picking','picking','picked','waiting-for-delivery','in-transit','delivered') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'pending',
  `created` timestamp NULL DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4515 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `campagne_dc_trucks_containers`
--

CREATE TABLE IF NOT EXISTS `campagne_dc_trucks_containers` (
`id` int(11) NOT NULL,
  `campagne_id` int(11) DEFAULT NULL,
  `dc_id` int(11) DEFAULT NULL,
  `dc_truck_id` int(11) DEFAULT NULL,
  `location_group_id` int(11) DEFAULT NULL,
  `external_id` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=85271 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `campagne_logistic_centers`
--

CREATE TABLE IF NOT EXISTS `campagne_logistic_centers` (
`id` int(11) NOT NULL,
  `campagne_id` int(11) DEFAULT NULL,
  `dcs_logistic_center_id` int(11) DEFAULT NULL,
  `location_data` longtext,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=455 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `campagne_logistic_centers_containers`
--

CREATE TABLE IF NOT EXISTS `campagne_logistic_centers_containers` (
`id` int(11) NOT NULL,
  `campagne_id` int(11) DEFAULT NULL,
  `external_id` varchar(100) DEFAULT NULL,
  `delivery_datetime` timestamp NULL DEFAULT NULL,
  `delivery_note` text,
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=767 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `campagne_picklists`
--

CREATE TABLE IF NOT EXISTS `campagne_picklists` (
`id` int(11) NOT NULL,
  `campagne_id` int(11) DEFAULT NULL,
  `campagne_container_box_id` int(11) DEFAULT NULL,
  `campagne_logistic_center_id` int(11) DEFAULT NULL,
  `campagne_dc_id` int(11) DEFAULT NULL,
  `campagne_dc_truck_id` int(11) DEFAULT NULL,
  `picklist_data` longtext,
  `container_data` longtext,
  `created_user_id` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=4914 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `campagne_products`
--

CREATE TABLE IF NOT EXISTS `campagne_products` (
`id` int(11) NOT NULL,
  `campagne_id` int(11) NOT NULL,
  `campagne_product_file_id` int(11) DEFAULT NULL,
  `campagne_product_file_column_index` varchar(2) DEFAULT NULL,
  `product_type` enum('product','set','bundle') DEFAULT 'product',
  `set_delivery` enum('set','seperate','bundled') DEFAULT NULL,
  `stations` enum('separate','combined') NOT NULL DEFAULT 'separate',
  `set_picking_status` enum('pending','done') DEFAULT NULL,
  `external_id` varchar(100) DEFAULT NULL,
  `included_external_ids` text,
  `included_external_products` text,
  `variations_data` longtext,
  `name` varchar(120) DEFAULT NULL,
  `locations` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_quantity` int(11) NOT NULL DEFAULT '1',
  `picklist_data` longtext,
  `picklist_note` longtext,
  `value_product` int(1) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=30292 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `campagne_product_files`
--

CREATE TABLE IF NOT EXISTS `campagne_product_files` (
`id` int(11) NOT NULL,
  `campagne_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `type` enum('products','variation_products') NOT NULL DEFAULT 'products',
  `location_data_sheet_index` int(11) NOT NULL DEFAULT '0',
  `variation_data_sheet_index` int(11) NOT NULL DEFAULT '1',
  `variations_name_column_index` varchar(10) NOT NULL DEFAULT 'A',
  `variantions_location_match_column` varchar(11) DEFAULT NULL,
  `variations_start_row_index` int(11) NOT NULL DEFAULT '2',
  `locations_name_row_index` int(11) DEFAULT NULL,
  `locations_id_column_index` varchar(15) DEFAULT NULL,
  `locations_name_column_index` varchar(15) DEFAULT NULL,
  `locations_address_column_index` varchar(3) DEFAULT NULL,
  `locations_address_number_column_index` varchar(3) DEFAULT NULL,
  `locations_postal_code_column_index` varchar(3) DEFAULT NULL,
  `locations_city_column_index` varchar(3) DEFAULT NULL,
  `locations_rayon_column_index` varchar(3) DEFAULT NULL,
  `locations_formule_column_index` varchar(3) DEFAULT NULL,
  `locations_start_row_index` varchar(15) NOT NULL DEFAULT '2',
  `import_location_data` int(1) NOT NULL DEFAULT '0',
  `products_id_row_index` int(11) NOT NULL DEFAULT '1',
  `products_name_row_index` varchar(15) NOT NULL DEFAULT '1',
  `products_version_details_row_index` int(11) DEFAULT NULL,
  `products_value_column_indexes` text,
  `products_articlenumber_column_indexes` varchar(100) DEFAULT NULL,
  `products_version_row_index` int(11) DEFAULT NULL,
  `products_version_multiplier_row_index` int(11) DEFAULT NULL,
  `products_unit_quantity_row_index` varchar(11) DEFAULT NULL,
  `products_packaging_type_row_index` int(1) DEFAULT NULL,
  `excel_data` longtext,
  `excel_data_raw` longblob,
  `variation_data` longtext,
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1556 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `campagne_station_cards`
--

CREATE TABLE IF NOT EXISTS `campagne_station_cards` (
`id` int(11) NOT NULL,
  `campagne_id` int(11) DEFAULT NULL,
  `campagne_container_box_id` int(11) DEFAULT NULL,
  `content` longtext,
  `created_user_id` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=1414 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `dcs`
--

CREATE TABLE IF NOT EXISTS `dcs` (
`id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `erp_id` varchar(25) DEFAULT NULL,
  `external_id` varchar(25) DEFAULT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#FFFFFF',
  `color_cmyk` varchar(100) NOT NULL DEFAULT '0 0 0 0',
  `name` varchar(100) DEFAULT NULL,
  `max_containers_per_truck` int(11) NOT NULL DEFAULT '54',
  `xls_start_row` int(11) NOT NULL DEFAULT '2',
  `xls_column_location_id` varchar(11) DEFAULT NULL,
  `xls_column_location_name` varchar(11) DEFAULT NULL,
  `xls_column_due_date` varchar(11) DEFAULT NULL,
  `xls_column_due_time` varchar(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `dcs_containers`
--

CREATE TABLE IF NOT EXISTS `dcs_containers` (
`id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `dcs_containers_boxes`
--

CREATE TABLE IF NOT EXISTS `dcs_containers_boxes` (
`id` int(11) NOT NULL,
  `container_id` int(11) NOT NULL,
  `max_boxes` int(11) NOT NULL DEFAULT '1',
  `name` varchar(100) DEFAULT NULL,
  `erp_id` varchar(15) DEFAULT NULL,
  `col` int(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `dcs_logistic_centers`
--

CREATE TABLE IF NOT EXISTS `dcs_logistic_centers` (
`id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `erp_id` varchar(25) DEFAULT NULL,
  `external_id` varchar(25) DEFAULT NULL,
  `color_cmyk` varchar(12) NOT NULL DEFAULT '#FFFFFF',
  `name` varchar(100) DEFAULT NULL,
  `address_1` varchar(255) DEFAULT NULL,
  `address_2` varchar(100) DEFAULT NULL,
  `postal_code` varchar(100) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `country_code` int(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `default` int(1) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `deletes`
--

CREATE TABLE IF NOT EXISTS `deletes` (
`id` int(11) NOT NULL,
  `table` varchar(100) DEFAULT NULL,
  `table_id` int(11) DEFAULT NULL,
  `data` longtext,
  `blame_user_id` int(11) DEFAULT NULL,
  `deleted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
`id` int(11) NOT NULL,
  `type` enum('client','account','location') NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `erp_id` varchar(100) DEFAULT NULL,
  `external_id` varchar(12) CHARACTER SET utf8 DEFAULT NULL,
  `account_type` enum('retail','wholesale') DEFAULT NULL,
  `location_type_id` int(11) DEFAULT NULL,
  `rayon` varchar(15) DEFAULT NULL,
  `formule` varchar(15) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `email` varchar(111) DEFAULT NULL,
  `phone` varchar(111) DEFAULT NULL,
  `base_backgroundcolor` varchar(15) NOT NULL DEFAULT '0 1 0 0',
  `base_fontcolor` varchar(15) NOT NULL DEFAULT '0 0 0 0',
  `API_BASE_URL` varchar(256) DEFAULT NULL,
  `API_KEY` varchar(256) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7541 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `group_addresses`
--

CREATE TABLE IF NOT EXISTS `group_addresses` (
`id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `type` enum('billing','delivery','visit') DEFAULT NULL,
  `external_id` varchar(15) DEFAULT NULL,
  `address_1` varchar(100) DEFAULT NULL,
  `address_2` varchar(100) DEFAULT NULL,
  `postal_code` varchar(100) DEFAULT NULL,
  `housenumber` varchar(20) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `formule` varchar(150) DEFAULT NULL,
  `rayon` int(11) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `country_code` varchar(3) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1217 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `group_location_closings`
--

CREATE TABLE IF NOT EXISTS `group_location_closings` (
`id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `external_id` varchar(100) DEFAULT NULL,
  `weeknr_closed` int(11) DEFAULT NULL,
  `year_closed` varchar(4) DEFAULT NULL,
  `weeknr_reopen` int(11) DEFAULT NULL,
  `year_reopen` varchar(4) DEFAULT NULL,
  `comment` text
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `group_location_types`
--

CREATE TABLE IF NOT EXISTS `group_location_types` (
`id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `group_modules`
--

CREATE TABLE IF NOT EXISTS `group_modules` (
`id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `campagnes` int(1) DEFAULT NULL,
  `weekbox` int(1) DEFAULT NULL,
  `mailings` int(1) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `group_regexpatterns`
--

CREATE TABLE IF NOT EXISTS `group_regexpatterns` (
`id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `pattern_location_id` varchar(255) DEFAULT '([0-9]{4,10})',
  `preview_external_id_single` varchar(255) DEFAULT NULL,
  `pattern_external_id_single` varchar(255) DEFAULT NULL,
  `pattern_external_ids_multi` varchar(255) DEFAULT NULL,
  `pattern_weekbox_fixed_item` varchar(255) DEFAULT '((WK)[0-9]{3,4})'
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `group_users`
--

CREATE TABLE IF NOT EXISTS `group_users` (
`id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `department` enum('om','handling','logistics') NOT NULL DEFAULT 'om',
  `accounts` text,
  `function` varchar(100) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `initials` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `user` varchar(100) DEFAULT NULL,
  `pass` varchar(100) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `mailorders`
--

CREATE TABLE IF NOT EXISTS `mailorders` (
`id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `type` enum('b2c','b2c') NOT NULL DEFAULT 'b2c',
  `package_column` varchar(2) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `mailorder_orders`
--

CREATE TABLE IF NOT EXISTS `mailorder_orders` (
`id` int(11) NOT NULL,
  `mailorder_id` int(11) DEFAULT NULL,
  `consumer_id` int(11) DEFAULT NULL,
  `mailorder_package_id` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_user_id` int(11) DEFAULT NULL,
  `processed` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `mailorder_packages`
--

CREATE TABLE IF NOT EXISTS `mailorder_packages` (
`id` int(11) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '1',
  `external_id` varchar(100) DEFAULT NULL,
  `product_type` enum('product','set') NOT NULL DEFAULT 'set',
  `mailorder_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `vardata_document_id` int(11) DEFAULT NULL,
  `weight` varchar(100) DEFAULT NULL,
  `packaging` varchar(100) DEFAULT NULL,
  `size` varchar(100) DEFAULT NULL,
  `products` text
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `mailorder_vardata`
--

CREATE TABLE IF NOT EXISTS `mailorder_vardata` (
`id` int(11) NOT NULL,
  `mailorder_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `display_name` varchar(100) NOT NULL,
  `column` varchar(5) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `mailorder_vardata_documents`
--

CREATE TABLE IF NOT EXISTS `mailorder_vardata_documents` (
`id` int(11) NOT NULL,
  `mailorder_id` int(11) DEFAULT NULL,
  `pdf` varchar(150) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `sscc_containers`
--

CREATE TABLE IF NOT EXISTS `sscc_containers` (
`id` int(11) NOT NULL,
  `campagne_id` int(11) DEFAULT NULL,
  `picklist_id` int(11) DEFAULT NULL,
  `external_id` int(11) DEFAULT NULL,
  `ai` varchar(3) NOT NULL DEFAULT '00',
  `range` varchar(12) DEFAULT '387193340085',
  `deleted` int(1) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=73022 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `stock`
--

CREATE TABLE IF NOT EXISTS `stock` (
`id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `erp_id` varchar(100) DEFAULT NULL,
  `external_id` varchar(100) DEFAULT NULL,
  `description` text,
  `product_type` enum('set','product') NOT NULL DEFAULT 'product',
  `in_order` int(11) NOT NULL DEFAULT '0',
  `in_campagne` int(11) NOT NULL DEFAULT '0',
  `in_stock` int(11) NOT NULL DEFAULT '0',
  `stock_location_id` int(11) DEFAULT NULL,
  `min_stock` int(11) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1002 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `stock_backorders`
--

CREATE TABLE IF NOT EXISTS `stock_backorders` (
`id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `erp_id` varchar(100) DEFAULT NULL,
  `external_id` varchar(100) DEFAULT NULL,
  `campagne_product_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `note` text,
  `quantity` int(11) DEFAULT NULL,
  `expected_date` timestamp NULL DEFAULT NULL,
  `status` enum('pending','delivered') NOT NULL DEFAULT 'pending',
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `stock_locations`
--

CREATE TABLE IF NOT EXISTS `stock_locations` (
`id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `code` varchar(10) DEFAULT NULL,
  `department` varchar(10) DEFAULT NULL,
  `stelling` int(11) DEFAULT NULL,
  `vak` varchar(10) DEFAULT NULL,
  `order` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `suppliers`
--

CREATE TABLE IF NOT EXISTS `suppliers` (
`id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `external` int(1) NOT NULL DEFAULT '1',
  `erp_id` varchar(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `weekbox`
--

CREATE TABLE IF NOT EXISTS `weekbox` (
`id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `week` int(11) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `weekbox_files`
--

CREATE TABLE IF NOT EXISTS `weekbox_files` (
`id` int(11) NOT NULL,
  `weekbox_id` int(11) DEFAULT NULL,
  `filename` varchar(100) DEFAULT NULL,
  `excel_data` longblob,
  `selected_product_columns` text,
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `weekbox_file_products`
--

CREATE TABLE IF NOT EXISTS `weekbox_file_products` (
`id` int(11) NOT NULL,
  `weekbox_file_id` int(11) DEFAULT NULL,
  `weekbox_file_product_column` varchar(10) DEFAULT NULL,
  `weekbox_seal_id` int(11) DEFAULT NULL,
  `weekbox_seal_distribution` text,
  `external_id` varchar(100) DEFAULT NULL,
  `fixed_item` int(11) DEFAULT NULL,
  `product_type` enum('product','set') NOT NULL DEFAULT 'product',
  `name` varchar(100) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `picklist_data` longtext,
  `versions` int(11) NOT NULL DEFAULT '1',
  `included_products_string` longtext,
  `included_products` longtext,
  `unit_quantity` int(11) NOT NULL DEFAULT '1',
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1026 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `weekbox_fixed_items`
--

CREATE TABLE IF NOT EXISTS `weekbox_fixed_items` (
`id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `external_id` varchar(100) DEFAULT NULL,
  `weekbox_seal_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `default_num_products` int(11) NOT NULL DEFAULT '0',
  `default_quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `weekbox_group_type_fixed_items`
--

CREATE TABLE IF NOT EXISTS `weekbox_group_type_fixed_items` (
`id` int(11) NOT NULL,
  `group_type_id` int(11) DEFAULT NULL,
  `weekbox_fixed_items` text
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `weekbox_picklists`
--

CREATE TABLE IF NOT EXISTS `weekbox_picklists` (
`id` int(11) NOT NULL,
  `weekbox_id` int(11) DEFAULT NULL,
  `weekbox_seal_id` int(11) DEFAULT NULL,
  `items` varchar(255) NOT NULL DEFAULT '[]',
  `picklist_data` text,
  `created` timestamp NULL DEFAULT NULL,
  `created_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blame_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `weekbox_seal`
--

CREATE TABLE IF NOT EXISTS `weekbox_seal` (
`id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `quantity_switch` enum('group_location_type') NOT NULL DEFAULT 'group_location_type'
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `campagnes`
--
ALTER TABLE `campagnes`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`,`owner_id`);

--
-- Indexen voor tabel `campagne_container_boxes`
--
ALTER TABLE `campagne_container_boxes`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `campagne_dc`
--
ALTER TABLE `campagne_dc`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `campagne_dc_trucks`
--
ALTER TABLE `campagne_dc_trucks`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `campagne_dc_trucks_containers`
--
ALTER TABLE `campagne_dc_trucks_containers`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`);

--
-- Indexen voor tabel `campagne_logistic_centers`
--
ALTER TABLE `campagne_logistic_centers`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `campagne_logistic_centers_containers`
--
ALTER TABLE `campagne_logistic_centers_containers`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `campagne_picklists`
--
ALTER TABLE `campagne_picklists`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `campagne_products`
--
ALTER TABLE `campagne_products`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `campagne_product_files`
--
ALTER TABLE `campagne_product_files`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `campagne_station_cards`
--
ALTER TABLE `campagne_station_cards`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `dcs`
--
ALTER TABLE `dcs`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `dcs_containers`
--
ALTER TABLE `dcs_containers`
 ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `dcs_containers_boxes`
--
ALTER TABLE `dcs_containers_boxes`
 ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `dcs_logistic_centers`
--
ALTER TABLE `dcs_logistic_centers`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `deletes`
--
ALTER TABLE `deletes`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `groups`
--
ALTER TABLE `groups`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `group_addresses`
--
ALTER TABLE `group_addresses`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `group_location_closings`
--
ALTER TABLE `group_location_closings`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `group_location_types`
--
ALTER TABLE `group_location_types`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `group_modules`
--
ALTER TABLE `group_modules`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `group_regexpatterns`
--
ALTER TABLE `group_regexpatterns`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `group_users`
--
ALTER TABLE `group_users`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `mailorders`
--
ALTER TABLE `mailorders`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `mailorder_orders`
--
ALTER TABLE `mailorder_orders`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `mailorder_packages`
--
ALTER TABLE `mailorder_packages`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `mailorder_vardata`
--
ALTER TABLE `mailorder_vardata`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `mailorder_vardata_documents`
--
ALTER TABLE `mailorder_vardata_documents`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `sscc_containers`
--
ALTER TABLE `sscc_containers`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `stock`
--
ALTER TABLE `stock`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`), ADD KEY `id_2` (`id`);

--
-- Indexen voor tabel `stock_backorders`
--
ALTER TABLE `stock_backorders`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`);

--
-- Indexen voor tabel `stock_locations`
--
ALTER TABLE `stock_locations`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `suppliers`
--
ALTER TABLE `suppliers`
 ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `weekbox`
--
ALTER TABLE `weekbox`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `weekbox_files`
--
ALTER TABLE `weekbox_files`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `weekbox_file_products`
--
ALTER TABLE `weekbox_file_products`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `weekbox_fixed_items`
--
ALTER TABLE `weekbox_fixed_items`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `weekbox_group_type_fixed_items`
--
ALTER TABLE `weekbox_group_type_fixed_items`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `weekbox_picklists`
--
ALTER TABLE `weekbox_picklists`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- Indexen voor tabel `weekbox_seal`
--
ALTER TABLE `weekbox_seal`
 ADD PRIMARY KEY (`id`), ADD KEY `id` (`id`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `campagnes`
--
ALTER TABLE `campagnes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=448;
--
-- AUTO_INCREMENT voor een tabel `campagne_container_boxes`
--
ALTER TABLE `campagne_container_boxes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1047;
--
-- AUTO_INCREMENT voor een tabel `campagne_dc`
--
ALTER TABLE `campagne_dc`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=530;
--
-- AUTO_INCREMENT voor een tabel `campagne_dc_trucks`
--
ALTER TABLE `campagne_dc_trucks`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4515;
--
-- AUTO_INCREMENT voor een tabel `campagne_dc_trucks_containers`
--
ALTER TABLE `campagne_dc_trucks_containers`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=85271;
--
-- AUTO_INCREMENT voor een tabel `campagne_logistic_centers`
--
ALTER TABLE `campagne_logistic_centers`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=455;
--
-- AUTO_INCREMENT voor een tabel `campagne_logistic_centers_containers`
--
ALTER TABLE `campagne_logistic_centers_containers`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=767;
--
-- AUTO_INCREMENT voor een tabel `campagne_picklists`
--
ALTER TABLE `campagne_picklists`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4914;
--
-- AUTO_INCREMENT voor een tabel `campagne_products`
--
ALTER TABLE `campagne_products`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=30292;
--
-- AUTO_INCREMENT voor een tabel `campagne_product_files`
--
ALTER TABLE `campagne_product_files`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1556;
--
-- AUTO_INCREMENT voor een tabel `campagne_station_cards`
--
ALTER TABLE `campagne_station_cards`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1414;
--
-- AUTO_INCREMENT voor een tabel `dcs`
--
ALTER TABLE `dcs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT voor een tabel `dcs_containers`
--
ALTER TABLE `dcs_containers`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT voor een tabel `dcs_containers_boxes`
--
ALTER TABLE `dcs_containers_boxes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT voor een tabel `dcs_logistic_centers`
--
ALTER TABLE `dcs_logistic_centers`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT voor een tabel `deletes`
--
ALTER TABLE `deletes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=36;
--
-- AUTO_INCREMENT voor een tabel `groups`
--
ALTER TABLE `groups`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7541;
--
-- AUTO_INCREMENT voor een tabel `group_addresses`
--
ALTER TABLE `group_addresses`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1217;
--
-- AUTO_INCREMENT voor een tabel `group_location_closings`
--
ALTER TABLE `group_location_closings`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT voor een tabel `group_location_types`
--
ALTER TABLE `group_location_types`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=19;
--
-- AUTO_INCREMENT voor een tabel `group_modules`
--
ALTER TABLE `group_modules`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT voor een tabel `group_regexpatterns`
--
ALTER TABLE `group_regexpatterns`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT voor een tabel `group_users`
--
ALTER TABLE `group_users`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT voor een tabel `mailorders`
--
ALTER TABLE `mailorders`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT voor een tabel `mailorder_orders`
--
ALTER TABLE `mailorder_orders`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT voor een tabel `mailorder_packages`
--
ALTER TABLE `mailorder_packages`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=33;
--
-- AUTO_INCREMENT voor een tabel `mailorder_vardata`
--
ALTER TABLE `mailorder_vardata`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT voor een tabel `mailorder_vardata_documents`
--
ALTER TABLE `mailorder_vardata_documents`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT voor een tabel `sscc_containers`
--
ALTER TABLE `sscc_containers`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=73022;
--
-- AUTO_INCREMENT voor een tabel `stock`
--
ALTER TABLE `stock`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1002;
--
-- AUTO_INCREMENT voor een tabel `stock_backorders`
--
ALTER TABLE `stock_backorders`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT voor een tabel `stock_locations`
--
ALTER TABLE `stock_locations`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT voor een tabel `suppliers`
--
ALTER TABLE `suppliers`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT voor een tabel `weekbox`
--
ALTER TABLE `weekbox`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT voor een tabel `weekbox_files`
--
ALTER TABLE `weekbox_files`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=24;
--
-- AUTO_INCREMENT voor een tabel `weekbox_file_products`
--
ALTER TABLE `weekbox_file_products`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1026;
--
-- AUTO_INCREMENT voor een tabel `weekbox_fixed_items`
--
ALTER TABLE `weekbox_fixed_items`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT voor een tabel `weekbox_group_type_fixed_items`
--
ALTER TABLE `weekbox_group_type_fixed_items`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT voor een tabel `weekbox_picklists`
--
ALTER TABLE `weekbox_picklists`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT voor een tabel `weekbox_seal`
--
ALTER TABLE `weekbox_seal`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
