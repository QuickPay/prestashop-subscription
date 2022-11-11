<?php

namespace QuickpaySubscripton;

use Cart;
use Context;
use DbQuery;
use mysqli_result;
use ObjectModel;
use PDOStatement;
use PrestaShopDatabaseException;
use PrestaShopException;
use QuickpaySubscription;

class QuickpaySubscriptionCart extends ObjectModel
{
    public $id;
    public $id_lang;
    public $id_shop;

    public $id_cart;
    public $id_customer;
    public $id_product;
    public $id_product_attribute;
    public $id_customization;
    public $quantity;
    public $id_subscription_product;
    public $id_plan;
    public $frequency;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => QuickpaySubscription::TABLE_BASE . '_cart',
        'primary' => 'id',
        'fields' => [
            'id_shop' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'id_cart' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'id_customer' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'id_product' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'id_product_attribute' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'id_customization' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'quantity' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'id_subscription_product' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'id_plan' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'frequency' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
            'date_add' => ['type' => self::TYPE_DATE, 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'required' => true],
        ]
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $translator);
    }

    /**
     * Get subscription cart by Cart ID, Customer ID and Product ID.
     *
     * @param $idCart
     * @param $idCustomer
     * @param $idProduct
     * @param $idProductAttribute
     * @param $idCustomization
     * @return false|int
     */
    public static function getByProductId($idCart, $idCustomer, $idProduct, $idProductAttribute = 0, $idCustomization = 0) {
        if (!$idCart) {
            return false;
        }

        $sql = new DbQuery();
        $sql->select(self::$definition['primary']);
        $sql->from(self::$definition['table']);
        $sql->where('id_cart = ' . $idCart);
        $sql->where('id_customer = ' . $idCustomer);
        $sql->where('id_product = ' . $idProduct);
        if ($idProductAttribute) {
            $sql->where('id_product_attribute = ' . $idProductAttribute);
        }
        if ($idCustomization) {
            $sql->where('id_customization = ' . $idCustomization);
        }

        $result = (int) \Db::getInstance()->getValue($sql);

        if (!$result) {
            return false;
        }

        return $result;
    }

    /**
     * Check if the cart has a subscription product
     *
     * @param $idCart
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function cartHasSubscriptionProduct($idCart = 0)
    {
        if (!$idCart) {
            $idCart = Context::getContext()->cart->id;
        }

        $plans = $frequencies = [];

        $cart = new \Cart($idCart);

        $cartProducts = $cart->getProducts();

        $statusSubscribe = false;
        $statusNotSubscribe = false;
        $status = false;

        foreach ($cartProducts as $cartProduct) {
            $ifSubscribeProduct = QuickpaySubscriptionProduct::checkIfExists($cartProduct['id_product']);
            $ifIsInCart = self::getByProductId($cart->id, $cart->id_customer, $cartProduct['id_product'], $cartProduct['id_product_attribute']);

            if ($ifSubscribeProduct && $ifIsInCart) {
                $statusSubscribe = true;
                $cartProduct = new self($ifIsInCart);
                $plans[] = $cartProduct->id_plan;
                $frequencies[] = $cartProduct->frequency;

                unset($cartProduct);
            }

            if (!$ifSubscribeProduct || !$ifIsInCart) {
                $statusNotSubscribe = true;
            }
        }

        if ($statusSubscribe && $statusNotSubscribe) {
            $status = true;
        }

        $plans = array_unique($plans);
        $frequencies = array_unique($frequencies);

        if (count($plans) >= 1 || count($frequencies) >= 1) {
            $status = true;
        }

        return $status;
    }

    /**
     * Get subscription cart ID by Prestashop cart ID
     *
     * @param $cartId
     * @return int
     */
    public static function getIdByCartId($cartId)
    {
        $sql = new DbQuery();
        $sql->select(self::$definition['primary']);
        $sql->from(self::$definition['table']);
        $sql->where('`id_cart` = ' . $cartId);

        return (int) \Db::getInstance()->getValue($sql);
    }

    /**
     * Get the selected plan and frequency for the cart
     *
     * @param $idCart
     * @return array|bool|object|null
     */
    public static function getPlanAndFrequencyByCartId($idCart)
    {
        $sql = new DbQuery();
        $sql->select('`id_plan`, `frequency`');
        $sql->from(self::$definition['table']);
        $sql->where('`id_cart` = ' . $idCart);

        return \Db::getInstance()->getRow($sql);
    }

    /**
     * Get cart information by Prestashop cart ID
     *
     * @param $cartId
     * @return array|bool|mysqli_result|PDOStatement|resource|null
     * @throws PrestaShopDatabaseException
     */
    public static function getByCartId($cartId)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(self::$definition['table']);
        $sql->where('`id_cart` = ' . $cartId);

        return \Db::getInstance()->executeS($sql);
    }

    /**
     * Get the cart ID by the subscription product ID
     *
     * @param $idCart
     * @param $idSubscriptionProduct
     * @return array|bool|mysqli_result|PDOStatement|resource|null
     * @throws PrestaShopDatabaseException
     */
    public static function getByCartIdAndSubscriptionProductId($idCart, $idSubscriptionProduct)
    {
        $sql = new DbQuery();
        $sql->select('`id_product`, `id_product_attribute`, `id_customization`');
        $sql->from(self::$definition['table']);
        $sql->where('`' . self::$definition['primary'] . '` = ' . $idCart);
        $sql->where('`id_subscription_product` = ' . $idSubscriptionProduct);

        return \Db::getInstance()->executeS($sql);
    }
}
