<?php

use QuickpaySubscripton\QuickpaySubscriptionCart;
use QuickpaySubscripton\QuickpaySubscriptionPlan;
use QuickpaySubscripton\QuickpaySubscriptionProduct;
use QuickpaySubscripton\QuickpaySubscriptionSubscriptions;

class QuickpaySubscriptionPaymentModuleFrontController extends ModuleFrontController
{
    public $display_header = false;
    public $display_footer = false;
    public $display_header_javascript = false;
    public $display_column_left = false;
    public $display_column_right = false;
    public $auth = true;
    public $guestAllowed = false;

    public $quickpay;
    public $currency;
    /** @var QuickpaySubscription $module */
    public $module;


    public function init()
    {
        parent::init();
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function display()
    {
        $this->ajax = 1;

        if (!\Context::getContext()->customer->isLogged()) {
            return false;
        }

        $this->quickpay = new \QuickPay\QuickPay(":" . Configuration::get('_QUICKPAY_USER_KEY'));
        $orderId = Tools::getValue('order_id');
        $total = Tools::getValue('total');
        $cartId = substr($orderId,3);
        $cart = new Cart($cartId);

        $this->currency = new Currency(Context::getContext()->cart->id_currency);

        $data = $this->module->apiPayment($orderId);
        $cartSubData = QuickpaySubscriptionCart::getPlanAndFrequencyByCartId($cart->id);

        $subscription = $this->checkSubscription($orderId);


        if (!$subscription) {
            $subscription = $this->createSubscription($orderId, $total, $data);
        }

        if (!$subscription) {
            return false;
        }

        $products = [];

        foreach ($cart->getProducts() as $product) {
            $data = QuickpaySubscriptionProduct::getByProductIdAndFrequency($product['id_product'], $cartSubData['id_plan']);
            $products[] = $data['id'];
        }

        Db::getInstance()->insert(QuickpaySubscriptionSubscriptions::$definition['table'], [
            'id' => $subscription->id,
            'id_customer' => $cart->id_customer,
            'id_cart' => $cart->id,
            'id_shop' => $cart->id_shop,
            'products' => json_encode($products),
            'id_plan' => $cartSubData['id_plan'],
            'frequency' => $cartSubData['frequency'],
            'status' => 0,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ],  false, false, \Db::INSERT_IGNORE);

        $auth = $this->getAuthorizeLink($subscription->id, $total, $orderId);

        if (!$auth) {
            return false;
        }

        Db::getInstance()->update(QuickpaySubscriptionSubscriptions::$definition['table'], [
            'status' => 1
        ], 'id = ' . $subscription->id);

        Tools::redirect($auth->url);
    }


    /**
     * Create a subscription in QuickPay
     *
     * @param $orderId
     * @param $amount
     * @param $data
     * @param $description
     * @return false
     */
    private function createSubscription($orderId, $amount, $data, $description = '')
    {
        $data = array_merge($data, [
            'order_id' => $orderId,
            'currency' => $this->currency->iso_code,
            'description' => $description,
            'amount' => $amount,
        ]);

        $result = $this->quickpay->request->post('/subscriptions', $data);

        if (in_array($result->httpStatus(), [200, 201])) {
            return $result->asObject();
        } else {
            $checkoutUrl = $this->context->link->getPageLink('order', true, null, ['step' => 3]);

            Tools::redirect($checkoutUrl);
            return false;
        }
    }

    /**
     * Get authorization link from QuickPay
     *
     * @param $id
     * @param $amount
     * @param $orderId
     * @return false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function getAuthorizeLink($id, $amount, $orderId)
    {
        /** @var QuickPay $qp */
        $qp = Module::getInstanceByName('quickpay');

        $data = $this->module->apiPayment($orderId);

        $customer = new Customer(\Context::getContext()->cart->id_customer);

        $data = array_merge($data, [
            'id' => $id,
            'amount' => $amount,
            'continue_url' => \Context::getContext()->link->getModuleLink('quickpaysubscription', 'success', ['id' => $id]),
            'cancel_url' => \Context::getContext()->link->getModuleLink('quickpaysubscription', 'fail', ['id' => $id]),
            'language' => \Context::getContext()->language->iso_code,
            'google_analytics_tracking_id' => \Configuration::get('_QUICKPAY_GA_TRACKING_ID'),
            'google_analytics_client_id' => \Configuration::get('_QUICKPAY_GA_CLIENT_ID'),
            'customer_email' => $customer->email,
        ]);

        $result = $this->quickpay->request->put('/subscriptions/' . $id . '/link', $data);

        if (in_array($result->httpStatus(), [200, 201])) {
            return $result->asObject();
        } else {
            var_dump($result->asObject());
            return false;
        }
    }

    /**
     * Check if a subscription exists in Quickpay for the cart
     *
     * @param $orderId
     * @return stdClass|int
     */
    private function checkSubscription($orderId)
    {
        $result = $this->quickpay->request->get('/subscriptions?order_id=' . $orderId);

        if (in_array($result->httpStatus(), [200, 201])) {
            $result = $result->asArray();


            if (!count($result)) {
                return 0;
            }

            return json_decode(json_encode($result[0]));

        } else {
            return 0;
        }
    }
}
