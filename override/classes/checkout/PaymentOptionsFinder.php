<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShop\PrestaShop\Core\Payment\PaymentOptionFormDecorator;
use PrestaShopBundle\Service\Hook\HookFinder;
use QuickpaySubscripton\QuickpaySubscriptionCart;

class PaymentOptionsFinder extends PaymentOptionsFinderCore
{
    /**
     * Collects available payment options from three different hooks.
     *
     * @return array An array of available payment options
     *
     * @see HookFinder::find()
     */
    public function find() //getPaymentOptions()
    {
        $paymentOptions = parent::find();

        $quickpaySubscription = false;

        if (\Context::getContext()->customer->isLogged() &&
            \Module::isEnabled('quickpay') &&
            \Module::isEnabled('quickpaysubscription') &&
            \Configuration::get('QUICKPAY_SUBSCRIPTION_STATUS') &&
            \Configuration::get('QUICKPAY_SUBSCRIPTION_DISABLE_PAYMENTS') &&
            QuickpaySubscriptionCart::cartHasSubscriptionProduct(\Context::getContext()->cart->id)) {
            $quickpaySubscription = true;
        }

        foreach ($paymentOptions as $moduleName => $paymentOption) {
            if (!is_array($paymentOption)) {
                unset($paymentOptions[$moduleName]);
            }
            if ($quickpaySubscription && $moduleName != 'quickpaysubscription') {
                unset($paymentOptions[$moduleName]);
            }

        }

        return $paymentOptions;
    }
}
