<?php

use QuickpaySubscripton\QuickpaySubscriptionSubscriptions;

class QuickpaySubscriptionFailModuleFrontController extends ModuleFrontController {

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function initContent()
    {
        parent::initContent();

        $subscriptionId = Tools::getValue('id', 0);

        if (!$subscriptionId) {

        }

        $plan = new QuickpaySubscriptionSubscriptions($subscriptionId);

        $plan->last_recurring = null;
        $plan->status = 0;

        $plan->save();

        $checkoutUrl = $this->context->link->getPageLink('order', true, null, ['step' => 3]);

        Tools::redirect($checkoutUrl);

        $this->setTemplate('module:quickpaysubscription/views/templates/front/fail.tpl');
    }
}
