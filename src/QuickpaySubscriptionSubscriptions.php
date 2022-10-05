<?php

namespace QuickpaySubscripton;

use Cart;
use Configuration;
use Context;
use Db;
use DbQuery;
use PrestaShop\PrestaShop\Adapter\Entity\ObjectModel;
use PrestaShopDatabaseException;
use PrestaShopException;
use Product;
use QuickPay\QuickPay;
use QuickpaySubscription;

class QuickpaySubscriptionSubscriptions extends \ObjectModel
{
    public $id;
    public $id_customer;
    public $id_cart;
    public $id_shop;
    public $products;
    public $id_plan;
    public $frequency;
    public $status;
    public $last_recurring;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => QuickpaySubscription::TABLE_BASE . '_subscriptions',
        'primary' => 'id',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'required' => true],
            'id_cart' => ['type' => self::TYPE_INT, 'required' => true],
            'id_shop' => ['type' => self::TYPE_INT, 'required' => true],
            'products' => ['type' => self::TYPE_STRING, 'required' => true],
            'id_plan' => ['type' => self::TYPE_INT, 'required' => true],
            'frequency' => ['type' => self::TYPE_INT, 'required' => true],
            'status' => ['type' => self::TYPE_INT, 'shop' => true, 'required' => false],
            'last_recurring' => ['type' => self::TYPE_DATE],
            'date_add' => ['type' => self::TYPE_DATE, 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'required' => true],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
    }

    public function update($null_values = false)
    {
        if (!$this->status) {
            $quickpayApi = new \QuickPay\QuickPay(":" . Configuration::get('_QUICKPAY_USER_KEY'));
            $result = $quickpayApi->request->post('/subscriptions/' . $this->id. '/cancel');
        }

        return parent::update($null_values);
    }

    /**
     * Get subscriptions by customer
     *
     * @param int $idCustomer
     * @param bool $onlyActive
     *
     * @return array|bool|\mysqli_result|\PDOStatement|resource
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getSubscriptionsByCustomer(int $idCustomer, bool $onlyActive = false)
    {
        $context = Context::getContext();

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(self::$definition['table']);
        $sql->where('`id_customer` = ' . $idCustomer);
        if ($onlyActive) {
            $sql->where('`status` = 1');
        }
        $sql->orderBy('`date_add` DESC');

        $subscriptions = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$subscriptions || !count($subscriptions)) {
            return [];
        }

        foreach ($subscriptions as &$subscription) {
            $subscription['plan'] = new QuickpaySubscriptionPlan($subscription['id_plan'], $context->language->id, $context->shop->id);
            $subscription['products'] = json_decode($subscription['products']);

            foreach ($subscription['products'] as &$product) {
                $id = $product;
                $product = [];
                $product['id'] = $id;
                $qpProduct = new QuickpaySubscriptionProduct($id);
                $prod = new Product($qpProduct->id_product, $context->language->id, $context->shop->id);
                $cart = new Cart($subscription['id_cart']);
                foreach ($cart->getProducts() as $cartProduct) {
                    if ($cartProduct['id_product'] == $prod->id) {
                        $product['name'] = $prod->name;
                        if ($cartProduct['attributes']) {
                            $product['name'] .= ' (' . $cartProduct['attributes'] . ')';
                        }
                        if ($cartProduct['id_customization']) {
                            $product['custom'] = 1;
                        }
                        if ($cartProduct['id_product_attribute']) {
                            $product['link'] = $context->link->getProductLink($prod, null, null, null, null, null, $cartProduct['id_product_attribute']);
                        } else {
                            $product['link'] = $context->link->getProductLink($prod);
                        }
                        $product['image'] = $context->link->getImageLink($prod->link_rewrite, $prod->getCoverWs(), 'small_default');

                    }
                }

            }
        }

        return $subscriptions;
    }

    /**
     * Get subscription orders by subscription ID
     *
     * @param int $id
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     * @throws PrestaShopDatabaseException
     */
    public static function getOrdersById(int $id)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('quickpaysubscription_order');
        $sql->where('subscription_id = ' . $id);
        $sql->orderBy('date_add DESC');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Toggle the status with ajax in the list of subscriptions
     *
     * @return bool|int|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function toggleStatus()
    {
        if (!property_exists($this, 'status')) {
            throw new PrestaShopException('property "status" is missing in object ' . get_class($this));
        }

        $this->setFieldsToUpdate(['status' => true]);
        $this->status = !(int) $this->status;

        return $this->update();
    }
}
