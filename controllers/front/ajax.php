<?php


use QuickpaySubscripton\QuickpaySubscriptionCart;
use QuickpaySubscripton\QuickpaySubscriptionProduct;

class QuickpaySubscriptionAjaxModuleFrontController extends ModuleFrontController
{
    /**
     * @throws PrestaShopException
     */
    public function displayAjaxCheck()
    {
        $plans = $frequencies = [];

        if (!Tools::getValue('token') || Tools::getValue('token') !== QuickpaySubscription::TOKEN) {
            $this->ajaxRender(json_encode([
                'status' => false
            ]));
        }

        $cartProducts = $this->context->cart->getProducts();

        $statusSubscribe = false;
        $statusNotSubscribe = false;
        $status = false;

        foreach ($cartProducts as $cartProduct) {
            $ifSubscribeProduct = QuickpaySubscriptionProduct::checkIfExists($cartProduct['id_product']);
            $ifIsInCart = QuickpaySubscriptionCart::getByProductId($this->context->cart->id, $this->context->cart->id_customer, $cartProduct['id_product'], $cartProduct['id_product_attribute']);

            if ($ifSubscribeProduct && $ifIsInCart) {
                $statusSubscribe = true;
                $cartProduct = new QuickpaySubscriptionCart($ifIsInCart);
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

        if (count($plans) > 1 || count($frequencies) > 1) {
            $status = true;
        }

        $this->ajaxRender(json_encode(compact('status')));
    }

    /**
     * @throws PrestaShopException
     */
    public function displayAjaxAdd()
    {
        if (!Tools::getValue('token') || Tools::getValue('token') !== QuickpaySubscription::TOKEN) {
            $this->ajaxRender(json_encode([
                'status' => false
            ]));
        }

        $cart = Context::getContext()->cart;

        $idProduct = Tools::getValue('id_product');
        $idProductAttribute = Tools::getValue('id_product_attribute');
        $idCustomization = Tools::getValue('id_customization');
        $quantity = Tools::getValue('quantity');
        $idPlan = Tools::getValue('id_plan');
        $cycle = Tools::getValue('frequency');

        $subscribeCartId = QuickpaySubscriptionCart::getByProductId($cart->id, $this->context->cart->id_customer, $idProduct, $idProductAttribute);
        $subscribeProduct = QuickpaySubscriptionProduct::getProductByIdAndPlan($idProduct, $idPlan, $cycle);

        if (!$subscribeCartId) {
            $subscribeCartProduct = new QuickpaySubscriptionCart();
            $subscribeCartProduct->id_cart = $cart->id;
            $subscribeCartProduct->id_shop = $this->context->shop->id;
            $subscribeCartProduct->id_customer = $this->context->cart->id_customer;
            $subscribeCartProduct->id_product = $idProduct;
            $subscribeCartProduct->id_product_attribute = $idProductAttribute;
            $subscribeCartProduct->id_customization = $idCustomization ?? null;
            $subscribeCartProduct->id_subscription_product = $subscribeProduct;
            $subscribeCartProduct->quantity = $quantity;

        } else {
            $subscribeCartProduct = new QuickpaySubscriptionCart($subscribeCartId);
            $subscribeCartProduct->quantity = $quantity;
            $subscribeCartProduct->id_shop = $this->context->shop->id;
        }
        $subscribeCartProduct->id_plan = $idPlan;
        $subscribeCartProduct->frequency = $cycle;
        $subscribeCartProduct->save();

        $this->ajaxRender(json_encode([
            'status' => true,
            'cycle' => $subscribeCartProduct->frequency,
            'plan' => $subscribeCartProduct->id_plan
        ]));
    }

    /**
     * @throws PrestaShopException
     */
    public function displayAjaxUpdate()
    {
        if (!Tools::getValue('token') || Tools::getValue('token') !== QuickpaySubscription::TOKEN) {
            $this->ajaxRender(json_encode([
                'status' => false
            ]));
        }

        $cart = Context::getContext()->cart;

        $subscribeCartProduct = Tools::getValue('id_subscription_product');
        $subscribeCartId = Tools::getValue('id_subscription_cart');
        $idPlan = Tools::getValue('id_plan');
        $cycle = Tools::getValue('frequency');


        $subscribeCartProduct = new QuickpaySubscriptionCart($subscribeCartId);
        $subscribeCartProduct->id_plan = $idPlan;
        $subscribeCartProduct->frequency = $cycle;
        $subscribeCartProduct->update();

        $this->ajaxRender(json_encode([
            'status' => true,
            'cycle' => $subscribeCartProduct->frequency,
            'plan' => $subscribeCartProduct->id_plan
        ]));
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function displayAjaxDelete()
    {
        if (!Tools::getValue('token') || Tools::getValue('token') !== QuickpaySubscription::TOKEN) {
            $this->ajaxRender(json_encode([
                'status' => false
            ]));
        }

        $idSubscriptionCart = Tools::getValue('id_subscription_cart');

        $subscriptionCart = new QuickpaySubscriptionCart($idSubscriptionCart);

        $subscriptionCart->delete();

        $this->ajaxRender(json_encode([
            'status' => true
        ]));

    }

    /**
     * @throws PrestaShopException
     */
    public function displayAjaxCancel()
    {
        if (!Tools::getValue('token') || Tools::getValue('token') !== QuickpaySubscription::TOKEN) {
            $this->ajaxRender(json_encode([
                'status' => false,
                'message' => $this->module->l('Mismatched token')
            ]));
        }

        $id = Tools::getValue('id_subscription', 0);

        if (!$id) {
            $this->ajaxRender(json_encode([
                'status' => false,
                'message' => $this->module->l('Invalid subscription ID')
            ]));
        }

        $quickpayApi = new \QuickPay\QuickPay(":" . Configuration::get('_QUICKPAY_USER_KEY'));
        $result = $quickpayApi->request->post('/subscriptions/' . $id. '/cancel');

        if ($result->httpStatus() == '202') {
            $this->ajaxRender(json_encode([
                'status' => true,
                'message' => $this->module->l('Successfully cancelled your subscription')
            ]));
        } else {
            $this->ajaxRender(json_encode([
                'status' => true,
                'message' => $this->module->l('There was an error cancelling your subscription')
            ]));
        }
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function displayAjaxCheckInCart()
    {
        $idProduct = Tools::getValue('id_product', 0);
        $idProductAttribute = Tools::getValue('id_product_attribute', 0);
        $idCustomization = Tools::getValue('id_customization', 0);

        $plans = QuickpaySubscriptionProduct::getByProductId($idProduct);
        $subscribeProduct = null;
        $subscribeCartId = QuickpaySubscriptionCart::getByProductId($this->context->cart->id, $this->context->cart->id_customer, $idProduct, $idProductAttribute);

        if ($subscribeCartId) {
            $subscribeProduct = new QuickpaySubscriptionCart($subscribeCartId);
        }

        $this->ajaxRender(json_encode(compact('subscribeProduct', 'plans')));
    }
}
