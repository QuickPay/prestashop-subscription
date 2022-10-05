<?php

use QuickpaySubscripton\QuickpaySubscriptionOrder;
use QuickpaySubscripton\QuickpaySubscriptionPlan;
use QuickpaySubscripton\QuickpaySubscriptionSubscriptions;

class QuickpaySubscriptionCronModuleFrontController extends ModuleFrontController
{
    public $display_header = false;
    public $display_footer = false;
    public $display_header_javascript = false;
    public $display_column_left = false;
    public $display_column_right = false;
    public $auth = false;
    public $guestAllowed = true;

    /** @var \Quickpay\QuickPay $quickpayApi */
    public $quickpayApi;

    /** @var QuickpaySubscription */
    public $module;

    public function init()
    {
        parent::init();
    }

    public function display()
    {
        $this->ajax = 1;

        $this->quickpayApi = new \QuickPay\QuickPay(":" . Configuration::get('_QUICKPAY_USER_KEY'));

        $token = Tools::getValue('token');
        $cronToken = Configuration::get(QuickpaySubscription::CRON_TOKEN);

        if (!$token || $token !== $cronToken) {
            echo 'Error: mismatching token' . PHP_EOL;

            return false;
        }

        if (!Configuration::get('QUICKPAY_SUBSCRIPTION_STATUS')) {
            echo 'Subscriptions are not enabled' . PHP_EOL;

            return false;
        }

        $this->getScheduledOrders();

    }

    /**
     * Get all orders that are scheduled to be created for the subscriptions
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function getScheduledOrders()
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('quickpaysubscription_subscriptions');
        $sql->where('status = 1');
        $sql->orderBy('date_add ASC');

        $subscriptions = Db::getInstance()->executeS($sql);

        foreach ($subscriptions as $subscription) {
            $plan = new QuickpaySubscriptionPlan($subscription['id_plan']);
            if (!$plan->status) {
                continue;
            }
            switch ($plan->frequency) {
                case 'weekly':
                    $time = 'week';
                    break;
                case 'monthly':
                    $time = 'month';
                    break;
                case 'yearly':
                    $time = 'year';
                    break;
                default:
                    $time = 'day';
                    break;
            }
            if ($subscription['frequency'] > 1) {
                $time .= 's';
            }

            $timeString = '+' . $subscription['frequency'] . ' ' . $time;

            $nextOrderDate = strtotime($timeString, strtotime($subscription['last_recurring']));

            if ($nextOrderDate <= time()) {
                if ($result = $this->createOrder($subscription['id_cart'], $subscription['id'])) {
                    echo 'Order was created successfully with ID ' . $result . ' for subscription ' . $subscription['id'] . '<br />';
                } else {
                    echo 'There was an error creating the recurring order for subscription ' . $subscription['id'] . '<br />';
                }
            } else {
                echo 'Next recurring order for ' . $subscription['id'] . ': ' . date('Y-m-d H:i:s', $nextOrderDate) . '<br />';
            }
        }
    }

    /**
     * Create an order in Prestashop for the subscription
     *
     * @throws PrestaShopException
     * @throws Exception
     */
    private function createOrder($idCart, $idSubscription)
    {
        /** @var QuickPay $quickpay */
        $quickpay = Module::getInstanceByName('quickpay');

        $totalOrders = $this->getTotalSubscriptionOrders($idSubscription);
        $order_id = Configuration::get('_QUICKPAY_ORDER_PREFIX') . ($idCart) . '_' . (++$totalOrders);
        $cart = new Cart($idCart);
        /** @var \Cart $newCart */
        $newCart = $cart->duplicateObject();
        $newCart->setWsCartRows($cart->getWsCartRows());
        $newCart->save();
        $currency = new Currency((int)$newCart->id_currency);
        Shop::setContext(Shop::CONTEXT_SHOP, $newCart->id_shop);
        $customer = new Customer((int)$newCart->id_customer);
        Context::getContext()->cart = $newCart;
        Context::getContext()->customer = $customer;
        Context::getContext()->currency = $currency;

        $cart_total = $quickpay->toQpAmount($newCart->getOrderTotal(), $currency);

        $result = $this->quickpayApi->request->post('/subscriptions/'. $idSubscription . '/recurring', [
            'order_id' => $order_id,
            'amount' => $cart_total,
            'description' => 'Recurring order for ' . $order_id,
            'auto_capture' => true
        ]);

        $recData = $result->asObject();

        if($this->module->validateOrder(
            $newCart->id,
            _PS_OS_PAYMENT_,
            $newCart->getOrderTotal(),
            $payment_method = $this->module->displayName,
            'Recurring payment for ' . $order_id,
            ['transaction_id' => $recData->id],
            null,
            false,
            $newCart->secure_key,
            new \Shop($newCart->id_shop)
        )) {
            $idOrder = Order::getIdByCartId($newCart->id);
            if ($idOrder) {
                $order = new Order($idOrder);
                $fields = array(
                    'variables[id_order]='.$order->id,
                    'variables[reference]='.$order->reference,
                );

                $this->quickpayApi->request->patch('/payments/' . $recData->id, [
                    'variables[id_order]' => $order->id,
                    'variables[reference]' => $order->reference,
                ]);

                $orderPaymentId = QuickpaySubscriptionOrder::getOrderPaymentByOrderReference($order->reference);
                if ($orderPaymentId) {
                    $orderPayment = new OrderPayment($orderPaymentId);
                    $orderPayment->card_number = $recData->metadata->last4;
                    $orderPayment->card_brand = $recData->metadata->brand;
                    $orderPayment->card_expiration = $recData->metadata->exp_month . '/' . $recData->metadata->exp_year;
                    $orderPayment->card_holder = $recData->metadata->issued_to;
                    $orderPayment->save();
                }

                $subscriptionOrder = new QuickpaySubscriptionOrder();
                $subscriptionOrder->id_order = $order->id;
                $subscriptionOrder->id_customer = $order->id_customer;
                $subscriptionOrder->id_cart = $order->id_cart;
                $subscriptionOrder->currency = Currency::getIsoCodeById($order->id_currency);
                $subscriptionOrder->amount = $cart->getOrderTotal();
                $subscriptionOrder->subscription_id = $recData->subscription_id;
                $subscriptionOrder->quickpay_id = $recData->id;
                $subscriptionOrder->date_add = date('Y-m-d H:i:s');
                $subscriptionOrder->date_upd = date('Y-m-d H:i:s');
                $subscriptionOrder->save();

                $subscription = new QuickpaySubscriptionSubscriptions($idSubscription);
                $subscription->last_recurring = date('Y-m-d H:i:s');
                $subscription->save();

                return $idOrder;
            }
        };

        $data = $result->asObject();
        $data->testing = $quickpay->setup->testmode;
        $data->status_codes = $result->httpStatus();
    }

    /**
     * Get the total amount of orders for a subscription
     *
     * @param $idSubscription
     * @return int
     */
    private function getTotalSubscriptionOrders($idSubscription)
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*)');
        $sql->from('quickpaysubscription_order');
        $sql->where('subscription_id = ' . $idSubscription);

        return (int) \Db::getInstance()->getValue($sql);
    }

}
