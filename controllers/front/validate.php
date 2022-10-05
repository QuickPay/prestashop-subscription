<?php

class QuickpaySubscriptionValidateModuleFrontController extends ModuleFrontController
{
    /**
     * Handle the callbacks from Quickpay
     *
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $requestBody = file_get_contents("php://input");
        $checksum = $_SERVER['HTTP_QUICKPAY_CHECKSUM_SHA256'];

        $data = json_decode($requestBody);

        if ($data->type == 'Subscription') {
            if ($data->state == 'cancelled') {
                $this->module->cancelSubscription($requestBody, $checksum, _PS_OS_PAYMENT_, true);
            }
        }

        exit(0);
    }
}
