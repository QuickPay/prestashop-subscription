<?php
/**
* NOTICE OF LICENSE
*
*  @author    Kjeld Borch Egevang
*  @copyright 2020 QuickPay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*  $Date: 2021/01/05 08:05:42 $
*  E-mail: support@quickpay.net
*/

$sql = array();

$tables = [
    'quickpaysubscription_cart',
    'quickpaysubscription_order',
    'quickpaysubscription_plan',
    'quickpaysubscription_plan_lang',
    'quickpaysubscription_plan_shop',
    'quickpaysubscription_product',
    'quickpaysubscription_schedule',
    'quickpaysubscription_subscriptions'
];

foreach ($tables as $table) {
    $sql[] = 'DROP TABLE IF EXISTS ' . _DB_PREFIX_ . $table;
}



foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) === false) {
        return false;
    }
}
