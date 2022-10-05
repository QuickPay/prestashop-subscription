<?php

use QuickpaySubscripton\QuickpaySubscriptionOrder;
use QuickpaySubscripton\QuickpaySubscriptionSubscriptions;

class QuickpaySubscriptionSuccessModuleFrontController extends ModuleFrontController {
    /**
     * @throws PrestaShopException
     * @throws Exception
     */
    public function initContent()
    {
        parent::initContent();

        $subscriptionId = Tools::getValue('id', 0);

        if (!$subscriptionId) {
            Tools::redirect(Context::getContext()->link->getModuleLink($this->module, 'fail', ['id' => $subscriptionId, 'error' => 'id']));
        }

        $quickpayApi = new \QuickPay\QuickPay(":" . Configuration::get('_QUICKPAY_USER_KEY'));
        /** @var Quickpay $quickpay */
        $quickpay = Module::getInstanceByName('quickpay');

        $testMode = \Configuration::get('_QUICKPAY_TESTMODE', 0);

        $result = $quickpayApi->request->get('/subscriptions/' . $subscriptionId);

        if ($result->httpStatus() != 200) {
            Tools::redirect(Context::getContext()->link->getModuleLink($this->module, 'fail', ['id' => $subscriptionId, 'error' => 'subscriptionResult']));
        }

        $subscription = new QuickpaySubscriptionSubscriptions($subscriptionId);

        if (!$subscription) {
            Tools::redirect(Context::getContext()->link->getModuleLink($this->module, 'fail', ['id' => $subscriptionId, 'error' => 'subscription']));
        }

        $cart = new \Cart($subscription->id_cart);

        $result = $quickpayApi->request->post('/subscriptions/' . $subscriptionId . '/recurring', [
            'id' => $subscriptionId,
            'order_id' => Configuration::get('_QUICKPAY_ORDER_PREFIX') . ($subscription->id_cart) . '_1',
            'amount' => $quickpay->toQpAmount($cart->getOrderTotal(), new Currency($cart->id_currency)),
            'description' => 'Recurring order for ' . $subscriptionId,
            'auto_capture' => true,
        ]);

        if (!in_array($result->httpStatus(), [200,201,202])) {
            Tools::redirect(Context::getContext()->link->getModuleLink($this->module->name, 'fail', ['id' => $subscriptionId, 'error' => 'recurring']));
        }

        $data = $result->asObject();

        try {
            $this->module->validateOrder(
                $subscription->id_cart,
                _PS_OS_PAYMENT_,
                $cart->getOrderTotal(),
                $this->module->displayName,
                null,
                ['transaction_id' => $data->id],
                null,
                false,
                $cart->secure_key,
                new \Shop($cart->id_shop)
            );
        } catch (Exception $exception) {
            Tools::redirect(Context::getContext()->link->getModuleLink($this->module, 'fail', ['id' => $subscriptionId, 'error' => 'order']));
        }

        $idOrder = Order::getIdByCartId($subscription->id_cart);

        if ($idOrder) {
            $order = new \Order($idOrder);
            $quickpayApi->request->patch('/payments/' . $data->id, [
                'variables[id_order]' => $order->id,
                'variables[reference]' => $order->reference,
            ]);

            /** @var OrderPayment $orderPayment */
            $orderPaymentId = QuickpaySubscriptionOrder::getOrderPaymentByOrderReference($order->reference);
            if ($orderPaymentId) {
                $orderPayment = new \OrderPayment($orderPaymentId);
                $orderPayment->card_number = $data->metadata->last4;
                $orderPayment->card_brand = $data->metadata->brand;
                $orderPayment->card_expiration = $data->metadata->exp_month . '/' . $data->metadata->exp_year;
                $orderPayment->card_holder = $data->metadata->issued_to;
                $orderPayment->save();
            }

            Db::getInstance()->insert('quickpaysubscription_order', [
                'id_order' => $order->id,
                'id_customer' => $order->id_customer,
                'id_cart' => $order->id_cart,
                'currency' => Currency::getIsoCodeById($order->id_currency),
                'amount' => $order->getTotalPaid(new \Currency($order->id_currency)),
                'subscription_id' => $subscription->id,
                'quickpay_id' => $data->id,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
            ]);

            $subscription->last_recurring = date('Y-m-d H:i:s');
            $subscription->save();

            $customer = new \Customer($order->id_customer);
            Tools::redirect(Context::getContext()->link->getPageLink('order-confirmation', null,null, ['id_order' => $idOrder, 'id_module' => $this->module->id, 'id_cart' => $order->id_cart, 'key' => $customer->secure_key]));

        }


        $this->setTemplate('module:quickpaysubscription/views/templates/front/success.tpl');
    }
}
