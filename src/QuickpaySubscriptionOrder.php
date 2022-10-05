<?php

namespace QuickpaySubscripton;

use Db;
use DbQuery;
use ObjectModel;
use OrderPayment;
use PrestaShopDatabaseException;
use PrestaShopException;

class QuickpaySubscriptionOrder extends ObjectModel
{
    public $id;
    public $id_lang;
    public $id_shop;

    public $id_order;
    public $id_customer;
    public $id_cart;
    public $currency;
    public $amount;
    public $subscription_id;
    public $quickpay_id;

    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => \QuickpaySubscription::TABLE_BASE . '_order',
        'primary' => 'id',
        'fields' => [
            'id_order' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'id_cart' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'id_customer' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'currency' => ['type' => self::TYPE_STRING, 'required' => true],
            'amount' => ['type' => self::TYPE_FLOAT, 'required' => true, 'validate' => 'isUnsignedFloat'],
            'subscription_id' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'quickpay_id' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'date_add' => ['type' => self::TYPE_DATE, 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'required' => true],
        ]
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $translator);
    }

    /**
     * Get the order payment information by order reference.
     *
     * @param $reference
     * @return int
     */
    public static function getOrderPaymentByOrderReference($reference)
    {
        $sql = new DbQuery();
        $sql->select(OrderPayment::$definition['primary']);
        $sql->from(OrderPayment::$definition['table']);
        $sql->where('`order_reference` = \'' . $reference . '\'');

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }
}