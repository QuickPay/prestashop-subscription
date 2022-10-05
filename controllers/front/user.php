<?php

use QuickpaySubscripton\QuickpaySubscriptionSubscriptions;

class QuickpaySubscriptionUserModuleFrontController extends \ModuleFrontController
{
    public $display_column_left = false;
    public $display_column_right = false;
    public $auth = true;
    public $guestAllowed = false;


    /**
     * @throws PrestaShopException
     */
    public function initContent()
    {
        parent::initContent();

        $subscriptions = QuickpaySubscriptionSubscriptions::getSubscriptionsByCustomer($this->context->customer->id);

        $this->context->smarty->assign([
            'subscriptions' => $subscriptions,
            'userCanCancel' => \Configuration::get('QUICKPAY_SUBSCRIPTION_USER_CANCEL', 0)
        ]);

        $this->setTemplate('module:quickpaysubscription/views/templates/front/user.tpl');
    }

    public function setMedia()
    {
        Media::addJsDef([
            'quickpaysubscription_token' => $this->module::TOKEN,
            'quickpaysubscription_ajax_url' => $this->context->link->getModuleLink($this->module->name, 'ajax')
        ]);

        $this->context->controller->registerJavascript(
            'module-quickpaysubsciption-js',
            'modules/'.$this->module->name.'/views/js/script.js',
            [
                'priority' => 999,
                'attribute' => 'defer',
            ]
        );
        return parent::setMedia();
    }
}
