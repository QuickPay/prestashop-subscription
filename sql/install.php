<?php
/**
* NOTICE OF LICENSE
*
*  @author    QuickPay
*  @copyright 2022 QuickPay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*  Date: 2022/05/02 18:45:00
*  E-mail: support@quickpay.net
*/

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ .'quickpaysubscription_cart` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `id_shop` int(11) DEFAULT NULL,
         `id_cart` int(11) NOT NULL,
         `id_customer` int(11) DEFAULT NULL,
         `id_product` int(11) DEFAULT NULL,
         `id_product_attribute` int(11) DEFAULT NULL,
         `id_customization` int(11) DEFAULT NULL,
         `quantity` int(11) DEFAULT NULL,
         `id_subscription_product` int(11) DEFAULT NULL,
         `id_plan` int(11) DEFAULT NULL,
         `frequency` int(11) DEFAULT NULL,
         `date_add` datetime DEFAULT NULL,
         `date_upd` datetime DEFAULT NULL,
         PRIMARY KEY (`id`)
         ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ .'quickpaysubscription_order` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `id_order` int(11) DEFAULT NULL,
         `id_customer` int(11) DEFAULT NULL,
         `id_cart` int(11) DEFAULT NULL,
         `currency` varchar(6) DEFAULT NULL,
         `amount` float DEFAULT NULL,
         `subscription_id` int(11) DEFAULT NULL,
         `quickpay_id` varchar(255) DEFAULT NULL,
         `date_add` datetime DEFAULT NULL,
         `date_upd` datetime DEFAULT NULL,
         PRIMARY KEY (`id`)
         ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ .'quickpaysubscription_plan` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `status` int(11) DEFAULT NULL,
         `frequency` varchar(32) DEFAULT NULL,
         `date_add` datetime DEFAULT NULL,
         `date_upd` datetime DEFAULT NULL,
         PRIMARY KEY (`id`)
         ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ .'quickpaysubscription_plan_lang` (
         `id` int(11) NOT NULL,
         `id_lang` int(11) NOT NULL,
         `name` varchar(255) DEFAULT NULL,
         PRIMARY KEY (`id`,`id_lang`)
         ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ .'quickpaysubscription_plan_shop` (
         `id` int(11) NOT NULL,
         `id_shop` int(11) NOT NULL,
         `status` int(11) DEFAULT NULL,
         PRIMARY KEY (`id`,`id_shop`)
         ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ .'quickpaysubscription_product` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `id_shop` int(11) NOT NULL,
         `id_product` int(11) NOT NULL,
         `id_plan` int(11) NOT NULL,
         `cycle` longtext,
         `status` int(11) DEFAULT NULL,
         `date_add` datetime DEFAULT NULL,
         `date_upd` datetime DEFAULT NULL,
         PRIMARY KEY (`id`,`id_product`,`id_plan`,`id_shop`) USING BTREE
         ) ENGINE=' . _MYSQL_ENGINE_ . ' AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ .'quickpaysubscription_schedule` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `id_cart` int(11) DEFAULT NULL,
         `id_subscription` int(11) DEFAULT NULL,
         `id_customer` int(11) DEFAULT NULL,
         `next_order` datetime DEFAULT NULL,
         `date_add` datetime DEFAULT NULL,
         `date_upd` datetime DEFAULT NULL,
         PRIMARY KEY (`id`)
         ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE `' . _DB_PREFIX_ .'quickpaysubscription_subscriptions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `id_customer` int(11) DEFAULT NULL,
        `id_cart` int(11) DEFAULT NULL,
        `id_shop` int(11) DEFAULT NULL,
        `products` longtext,
        `id_plan` int(11) DEFAULT NULL,
        `frequency` varchar(64) DEFAULT NULL,
        `last_recurring` datetime DEFAULT NULL,
        `status` int(11) DEFAULT NULL,
        `date_add` datetime DEFAULT NULL,
        `date_upd` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';


foreach ($sql as $query) {
    if (\Db::getInstance()->execute($query) === false) {
        return false;
    }
}

$insertQueries = [
    'INSERT INTO `' . _DB_PREFIX_ . 'quickpaysubscription_plan` (`id`, `status`, `frequency`, `date_add`, `date_upd`) VALUES (1, 1, \'daily\', \''. date('Y-m-d H:i:s') .'\', \''. date('Y-m-d H:i:s') .'\'), (2, 1, \'weekly\', \''. date('Y-m-d H:i:s') .'\', \''. date('Y-m-d H:i:s') .'\'), (3, 1, \'monthly\', \''. date('Y-m-d H:i:s') .'\', \''. date('Y-m-d H:i:s') .'\'), (4, 1, \'yearly\', \''. date('Y-m-d H:i:s') .'\', \''. date('Y-m-d H:i:s') .'\');',
];


    $langData = '';
foreach (Language::getLanguages(false, false, true) as $language) {
    $insertQueries[] = 'INSERT INTO `' . _DB_PREFIX_ . 'quickpaysubscription_plan_lang` (`id`, `id_lang`, `name`) VALUES (1, ' . $language . ', \'Daily\'), (2, ' . $language . ', \'Weekly\'), (3, ' . $language . ', \'Monthly\'), (4, ' . $language . ', \'Yearly\')';
}


foreach (Shop::getShops(false, null, true) as $shop) {
    $insertQueries[] = 'INSERT INTO `' . _DB_PREFIX_ . 'quickpaysubscription_plan_shop` (`id`, `id_shop`, `status`) VALUES (1, ' . $shop . ', 1), (2, ' . $shop . ', 1), (3, ' . $shop . ', 1), (4, ' . $shop . ', 1);';
}

foreach ($insertQueries as $query) {
    if (\Db::getInstance()->execute($query) === false) {
        return false;
    }
}
