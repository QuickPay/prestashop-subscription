<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use PrestaShopBundle\Form\Admin\Type\Material\MaterialMultipleChoiceTableType;
use QuickpaySubscripton\QuickpaySubscriptionCart;
use QuickpaySubscripton\QuickpaySubscriptionPlan;
use QuickpaySubscripton\QuickpaySubscriptionProduct;
use QuickpaySubscripton\QuickpaySubscriptionSubscriptions;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class QuickpaySubscription extends PaymentModule
{
    const TABLE_BASE = 'quickpaysubscription';
    const DAILY_MAX = 6;
    const WEEKLY_MAX = 3;
    const MONTHLY_MAX = 11;
    const YEARLY_MAX = 2;
    const TOKEN = '23142j3k45jhg2kjhkj32h4kj23h4kj23jh';
    const CRON_TOKEN = 'QUICKPAY_SUBSCRIPTION_CRON_TOKEN';

    const DEBUG_MODE = true;

    public $alreadyRan = false;

    public $quickpayApi = null;

    public function __construct()
    {
        $this->name = 'quickpaysubscription';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->author = 'Quickpay';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.6',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;
        $this->module_key = 'WN3C69JCxfRuURMxx2kciBu7Hs9E8W3c';
        $this->secure_key = Tools::hash($this->name);

        parent::__construct();

        $this->displayName = $this->trans('Quickpay subscription', [], 'Modules.Quickpaysubscription.Admin');
        $this->description = $this->trans('Adds option to subscribe to products', [], 'Modules.Quickpaysubscription.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Quickpaysubscription.Admin');
    }

    /**
     * @throws PrestaShopException
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        include_once dirname(__FILE__) . '/sql/install.php';

        return parent::install() &&
            $this->hooks() &&
            $this->createTabs();
    }

    public function uninstall()
    {
        include_once dirname(__FILE__) . '/sql/uninstall.php';

        return parent::uninstall();
    }

    public function createTabs()
    {
        $urls = [
            [
                'controller' => 'QUICKPAYSUBSCRIPTION',
                'url' => '#',
                'name' => $this->l('Quickpay subscriptions'),
                'parent' => 'DEFAULT'
            ],
            [
                'controller' => 'AdminQuickpaySubscriptions',
                'url' => $this->context->link->getAdminLink('AdminQuickpaySubscriptions'),
                'name' => $this->l('Quickpay subscriptions'),
                'parent' => 'QUICKPAYSUBSCRIPTION'
            ],
            [
                'controller' => 'AdminQuickpaySubscriptionProducts',
                'url' => $this->context->link->getAdminLink('AdminQuickpaySubscriptionProducts'),
                'name' => $this->l('Quickpay subscription products'),
                'parent' => 'QUICKPAYSUBSCRIPTION'
            ],
            [
                'controller' => 'AdminQuickpaySubscriptionPlans',
                'url' => $this->context->link->getAdminLink('AdminQuickpaySubscriptionPlans'),
                'name' => $this->l('Quickpay subscription plans'),
                'parent' => 'QUICKPAYSUBSCRIPTION'
            ]
        ];

        $result = true;

        foreach ($urls as $url) {
            $result &= $this->installTab($url['controller'], $url['name'], $url['parent']);
        }

        return $result;
    }

    /**
     * Register the necessary hooks for the module
     *
     * @return bool
     */
    public function hooks()
    {
        $hookList = [
            'actionAuthentication',
            'actionProductAdd',
            'actionProductUpdate',
            'actionProductDelete',
            'actionDeleteProductInCartAfter',
            'actionObjectProductInCartDeleteAfter',
            'actionValidateOrder',
            'actionAdminControllerSetMedia',
            'actionFrontControllerSetMedia',
            'displayCartExtraProductActions',
            'displayCustomerAccount',
            'displayOrderConfirmation',
            'displayProductPriceBlock',
            'displayShoppingCartFooter',
            'displayAdminProductsExtra',
            'displayAdminOrderLeft',
            'displayAdminOrderMain',
            'payment',
            'paymentOptions',
            'paymentReturn'
        ];

        return $this->registerHook($hookList);
    }

    /**
     * @throws SmartyException
     */
    public function getContent()
    {
        $html = '';
        if (Tools::isSubmit('submitGeneral')) {
            $this->postProcess();
        }
        if (Tools::isSubmit('submitCron')) {
            $this->postProcess('cron');
        }
        if (!Module::isEnabled('quickpay')) {
            $html .= $this->displayError($this->l('You MUST have the Quickpay module enabled to use this module.') . '<br />' . $this->l('Please download it from ') . '<a style="font-weight: bold;" target="_blank" href="https://quickpay.net/integrations/prestashop/">' . $this->l('here') . '</a>');
            return $html;
        }

        return $html . $this->getAdminUrls() . $this->renderForm() . $this->renderFormCron();
    }

    /**
     * @throws SmartyException
     * @throws Exception
     */
    public function getAdminUrls()
    {
        $urls = [
            [
                'controller' => 'AdminQuickpaySubscriptions',
                'url' => $this->context->link->getAdminLink('AdminQuickpaySubscriptions'),
                'name' => $this->l('Quickpay subscriptions')
            ],
            [
                'controller' => 'AdminQuickpaySubscriptionProducts',
                'url' => $this->context->link->getAdminLink('AdminQuickpaySubscriptionProducts'),
                'name' => $this->l('Quickpay subscription products')
            ],
            [
                'controller' => 'adminQuickpaySubscriptionPlans',
                'url' => $this->context->link->getAdminLink('AdminQuickpaySubscriptionPlans'),
                'name' => 'Quickpay subscription plans'
            ]
        ];

        $this->context->smarty->assign(compact('urls'));

        return $this->context->smarty->fetch('module:'.$this->name.'/views/templates/admin/admin_urls.tpl');
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->getTranslator()->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('Enable subscriptions', [], 'Modules.Quickpaysubscription.Admin'),
                        'name' => 'QUICKPAY_SUBSCRIPTION_STATUS',
                        'values' => array(
                            array(
                                'id' => 'status_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'status_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('User cancel', [], 'Modules.Quickpaysubscription.Admin'),
                        'name' => 'QUICKPAY_SUBSCRIPTION_USER_CANCEL',
                        'values' => array(
                            array(
                                'id' => 'user_cancel_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'user_cancel_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('Disable other payment methods', [], 'Modules.Quickpaysubscription.Admin'),
                        'hint' => $this->getTranslator()->trans('Disable other payment methods to not show when the cart only contains subscription products', [], 'Modules.Quickpaysubscription.Admin'),
                        'name' => 'QUICKPAY_SUBSCRIPTION_DISABLE_PAYMENTS',
                        'values' => array(
                            array(
                                'id' => 'disable_payments_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'disable_payments_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('Show subscription information when not logged in', [], 'Modules.Quickpaysubscription.Admin'),
                        'name' => 'QUICKPAY_SUBSCRIPTION_SHOW_WHEN_NOT_LOGGED_IN',
                        'values' => array(
                            array(
                                'id' => 'show_not_logged_in_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'show_not_logged_in_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ],
                ],
                'submit' => [
                    'title' => $this->getTranslator()->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->submit_action = 'submitGeneral';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function renderFormCron()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->getTranslator()->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->getTranslator()->trans('Cron url', [], 'Modules.Quickpaysubscription.Admin'),
                        'name' => 'QUICKPAY_SUBSCRIPTION_CRON_URL',
                        'readonly' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->getTranslator()->trans('Cron token', [], 'Modules.Quickpaysubscription.Admin'),
                        'name' => 'QUICKPAY_SUBSCRIPTION_CRON_TOKEN',
                    ],
                ],
                'submit' => [
                    'title' => $this->getTranslator()->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->submit_action = 'submitCron';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues('cron'),
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Get configuration field values
     *
     * @param $type
     * @return array
     */
    public function getConfigFieldsValues($type = 'general')
    {
        switch ($type) {
            case 'cron':
                return [
                    'QUICKPAY_SUBSCRIPTION_CRON_URL' => $this->context->link->getModuleLink($this->name, 'cron', ['token' => Tools::getValue('QUICKPAY_SUBSCRIPTION_CRON_TOKEN', Configuration::get('QUICKPAY_SUBSCRIPTION_CRON_TOKEN'))]),
                    'QUICKPAY_SUBSCRIPTION_CRON_TOKEN' => Tools::getValue('QUICKPAY_SUBSCRIPTION_CRON_TOKEN', Configuration::get('QUICKPAY_SUBSCRIPTION_CRON_TOKEN')),
                ];
            default:
                return [
                    'QUICKPAY_SUBSCRIPTION_STATUS' => Tools::getValue('QUICKPAY_SUBSCRIPTION_STATUS', Configuration::get('QUICKPAY_SUBSCRIPTION_STATUS')),
                    'QUICKPAY_SUBSCRIPTION_USER_CANCEL' => Tools::getValue('QUICKPAY_SUBSCRIPTION_USER_CANCEL', Configuration::get('QUICKPAY_SUBSCRIPTION_USER_CANCEL')),
                    'QUICKPAY_SUBSCRIPTION_DISABLE_PAYMENTS' => Tools::getValue('QUICKPAY_SUBSCRIPTION_DISABLE_PAYMENTS', Configuration::get('QUICKPAY_SUBSCRIPTION_DISABLE_PAYMENTS')),
                    'QUICKPAY_SUBSCRIPTION_SHOW_WHEN_NOT_LOGGED_IN' => Tools::getValue('QUICKPAY_SUBSCRIPTION_SHOW_WHEN_NOT_LOGGED_IN', Configuration::get('QUICKPAY_SUBSCRIPTION_SHOW_WHEN_NOT_LOGGED_IN')),
                ];
        }
    }

    /**
     * Process admin configuration save
     *
     * @param $type
     * @return void
     */
    public function postProcess($type = 'general')
    {
        switch ($type) {
            case 'cron':
                $keys = [
                    'QUICKPAY_SUBSCRIPTION_CRON_TOKEN',
                ];
                break;
            default:
                $keys = [
                    'QUICKPAY_SUBSCRIPTION_STATUS',
                    'QUICKPAY_SUBSCRIPTION_USER_CANCEL',
                    'QUICKPAY_SUBSCRIPTION_DISABLE_PAYMENTS',
                    'QUICKPAY_SUBSCRIPTION_SHOW_WHEN_NOT_LOGGED_IN',
                ];
                break;
        }
        foreach ($keys as $key) {
            Configuration::updateValue($key, Tools::getValue($key, Configuration::get($key)));
        }
    }


    /**
     * Install backoffice tabs
     *
     * @param $controller
     * @param $name
     * @param $parent
     * @return bool
     * @throws Exception
     */
    public function installTab($controller, $name, $parent = 'DEFAULT')
    {
        $tabRepository = $this->get('prestashop.core.admin.tab.repository');

        $tab = new Tab();
        $tab->class_name = $controller;
        $tab->module = $this->name;
        $tab->id_parent = (int)$tabRepository->findOneIdByClassName($parent);
        $tab->multishop_context = Shop::CONTEXT_SHOP;
        $tab->lang = true;

        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = $name;
        }

        try {
            $tab->save();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Display necessary settings in the product's admin module tab
     *
     * @param array $params
     *
     * @throws Exception
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        global $kernel;
        $productAdapter = $kernel->getContainer()->get('prestashop.adapter.data_provider.product');
        $product = $productAdapter->getProduct($params['id_product']);

        /** @var Form\FormFactory $formFactory */
        $formFactory = $kernel->getContainer()->get('form.factory');

        $plans = QuickpaySubscriptionPlan::getPlansForAdmin(\Context::getContext()->language->id, \Context::getContext()->shop->id);
        $choices = QuickpaySubscriptionPlan::getFrequencyChoicesForAdmin();

        $quickpaySubscriptionFrequency = [
            'daily' => QuickpaySubscriptionProduct::getCycleByProductIdAndFrequency($params['id_product'],QuickpaySubscriptionPlan::getByFrequency('daily')),
            'weekly' => QuickpaySubscriptionProduct::getCycleByProductIdAndFrequency($params['id_product'],QuickpaySubscriptionPlan::getByFrequency('weekly')),
            'monthly' => QuickpaySubscriptionProduct::getCycleByProductIdAndFrequency($params['id_product'],QuickpaySubscriptionPlan::getByFrequency('monthly')),
            'yearly' => QuickpaySubscriptionProduct::getCycleByProductIdAndFrequency($params['id_product'],QuickpaySubscriptionPlan::getByFrequency('yearly')),
        ];

        $form = $formFactory->createBuilder(FormType::class, [
            'quickpaysubscription_frequency' => $quickpaySubscriptionFrequency
        ])
            ->add('quickpaysubscription_frequency', MaterialMultipleChoiceTableType::class, [
                'label' => $this->trans('Subscription frequency', [], 'Modules.Quickpaysubscription.Admin'),
                'choices' => $choices,
                'multiple_choices' => $plans,
            ])
            ->getForm();

        return $kernel->getContainer()->get('twig')->render(_PS_MODULE_DIR_ . $this->name . '/views/templates/admin/display-admin-products-extra.html.twig', [
            'quickpay_subscription' => $form->createView()
        ]);
    }

    /**
     * Run the saving process when a new product is added to Prestashop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionProductAdd()
    {
        $this->hookActionProductUpdate();
    }

    /**
     * Run the saving process when a new product is updated in Prestashop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionProductUpdate()
    {
        if ($this->alreadyRan) {
            return true;
        }

        $frequencies = $_REQUEST['form']['quickpaysubscription_frequency'];

        $subscriptionProductDataDaily = QuickpaySubscriptionProduct::getByProductIdAndFrequency($_REQUEST['form']['id_product'], QuickpaySubscriptionPlan::getByFrequency('daily'));
        $subscriptionProductDataWeekly = QuickpaySubscriptionProduct::getByProductIdAndFrequency($_REQUEST['form']['id_product'], QuickpaySubscriptionPlan::getByFrequency('weekly'));
        $subscriptionProductDataMonthly = QuickpaySubscriptionProduct::getByProductIdAndFrequency($_REQUEST['form']['id_product'], QuickpaySubscriptionPlan::getByFrequency('monthly'));
        $subscriptionProductDataYearly = QuickpaySubscriptionProduct::getByProductIdAndFrequency($_REQUEST['form']['id_product'], QuickpaySubscriptionPlan::getByFrequency('yearly'));

        $plans = [];

        foreach ($frequencies as $frequency => $cycle) {
            foreach ($cycle as &$item) {
                $item = (int) $item;
            }
            switch ($frequency) {
                case 'daily':
                    if (isset($subscriptionProductDataDaily['id'])) {
                        $subscriptionProductDataDaily['cycle'] = array_values(array_unique(array_merge($subscriptionProductDataDaily['cycle'], $cycle)));
                    } else {
                        $subscriptionProductDataDaily['cycle'] = $cycle;
                    }
                    break;
                case 'weekly':
                    if (isset($subscriptionProductDataWeekly['id'])) {
                        $subscriptionProductDataWeekly['cycle'] = array_unique(array_merge($subscriptionProductDataWeekly['cycle'], $cycle));
                    } else {
                        $subscriptionProductDataWeekly['cycle'] = $cycle;
                    }
                    break;
                case 'monthly':
                    if (isset($subscriptionProductDataMonthly['id'])) {
                        $subscriptionProductDataMonthly['cycle'] = array_values(array_unique(array_merge($subscriptionProductDataMonthly['cycle'], $cycle)));
                    } else {
                        $subscriptionProductDataMonthly['cycle'] = $cycle;
                    }
                    break;
                case 'yearly':
                    if (isset($subscriptionProductDataYearly['id'])) {
                        $subscriptionProductDataYearly['cycle'] = array_values(array_unique(array_merge($subscriptionProductDataYearly['cycle'], $cycle)));
                    } else {
                        $subscriptionProductDataYearly['cycle'] = $cycle;
                    }
                    break;
            }
        }

        if (isset($subscriptionProductDataDaily['cycle'])) {
            QuickpaySubscriptionProduct::adminSave((int) $_REQUEST['form']['id_product'],QuickpaySubscriptionPlan::getByFrequency('daily'), $subscriptionProductDataDaily['cycle'], ($subscriptionProductDataDaily['id'] ?? 0));
        }
        if (isset($subscriptionProductDataWeekly['cycle'])) {
            QuickpaySubscriptionProduct::adminSave((int) $_REQUEST['form']['id_product'],QuickpaySubscriptionPlan::getByFrequency('weekly'), $subscriptionProductDataWeekly['cycle'], ($subscriptionProductDataWeekly['id'] ?? 0));
        }
        if (isset($subscriptionProductDataMonthly['cycle'])) {
            QuickpaySubscriptionProduct::adminSave((int) $_REQUEST['form']['id_product'],QuickpaySubscriptionPlan::getByFrequency('monthly'), $subscriptionProductDataMonthly['cycle'], ($subscriptionProductDataMonthly['id'] ?? 0));
        }
        if (isset($subscriptionProductDataYearly['cycle'])) {
            QuickpaySubscriptionProduct::adminSave((int) $_REQUEST['form']['id_product'],QuickpaySubscriptionPlan::getByFrequency('yearly'), $subscriptionProductDataYearly['cycle'], ($subscriptionProductDataYearly['id'] ?? 0));

        }

        $this->alreadyRan = true;

        return true;
    }

    /**
     * Delete the subscription product data when a product is deleted from Prestashop
     *
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function hookActionProductDelete()
    {
        $idShop = Context::getContext()->shop->id;

        $subscriptionProductData = QuickpaySubscriptionProduct::getByProductId($_REQUEST['form']['id_product']);

        if (!count($subscriptionProductData)) {
            return false;
        }

        $result = false;
        /** @var QuickpaySubscriptionProduct $subscriptionProduct */
        foreach ($subscriptionProductData as $subscriptionProduct) {
            $result &= $subscriptionProduct->delete();
        }

        return $result;
    }

    /**
     * Remove the subscription product data when a product is deleted from the cart
     *
     * @param $params
     * @return false|void
     */
    public function hookActionObjectProductInCartDeleteAfter($params)
    {
        if (!Configuration::get('QUICKPAY_SUBSCRIPTION_STATUS')) {
            return false;
        }

        $idProduct = $params['id_product'];
        $idProductAttribute = $params['id_product_attribute'];
        $idCart = $params['id_cart'];

        \Db::getInstance()->delete(QuickpaySubscriptionCart::$definition['table'], 'id_cart = ' . $idCart . ' AND id_product = ' . $idProduct . ' AND id_product_attribute = ' . $idProductAttribute);
    }

    /**
     * Update the subscription product data when a product is added or quantity modified
     *
     * @param $params
     * @return false|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionCartUpdateQuantityBefore($params)
    {
        if (!Configuration::get('QUICKPAY_SUBSCRIPTION_STATUS')) {
            return false;
        }

        $cart = new Cart($params['cart']->id);

        $cartQty = $cart->getProductQuantity($params['product']->id, $params['id_product_attribute'], $params['id_customization']);

        $subscribeCartId = QuickpaySubscriptionCart::getByProductId($params['cart']->id, $this->context->cart->id_customer, $params['product']->id, $params['id_product_attribute']);

        if ($subscribeCartId) {
            $subscribeCartProduct = new QuickpaySubscriptionCart($subscribeCartId);
            if ($params['operator'] == 'up') {
                $subscribeCartProduct->quantity = ($cartQty['quantity'] + $params['quantity']);
            } else {
                $subscribeCartProduct->quantity = ((int)$cartQty['quantity'] - (int)$params['quantity']);
            }
            $subscribeCartProduct->save();
        }
    }

    /**
     * Display the necessary information on the product page
     *
     * @param $params
     * @return false|string|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookDisplayProductPriceBlock($params)
    {
        if (!Configuration::get('QUICKPAY_SUBSCRIPTION_STATUS')) {
            return false;
        }

        if (!Context::getContext()->customer->isLogged()) {
            if (!Configuration::get('QUICKPAY_SUBSCRIPTION_SHOW_WHEN_NOT_LOGGED_IN')) {
                return false;
            }
        }

        $idProduct = (int) Tools::getValue('id_product');
        $idProductAttribute = (int) Tools::getValue('id_product_attribute', 0);

        if (!$idProduct) {
            return false;
        }

        $plans = QuickpaySubscriptionProduct::getByProductId($idProduct);
        $subscribeProduct = null;
        $subscribeCartId = QuickpaySubscriptionCart::getByProductId($this->context->cart->id, $this->context->cart->id_customer, $idProduct, $idProductAttribute);

        if ($subscribeCartId) {
            $subscribeProduct = new QuickpaySubscriptionCart($subscribeCartId);
        }

        if ($params['type'] == 'after_price' && count($plans)) {
            $this->context->smarty->assign([
                'subscribeProduct' => $subscribeProduct,
                'plans' => $plans,
                'quickpaysubscription_token' => self::TOKEN,
                'quickpaysubscription_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax')
            ]);
            return $this->display(__FILE__, 'product.tpl');
        }

    }

    /**
     * Add CSS and JS files to load
     * It will load on the following pages: product, cart, order
     *
     * @param $params
     * @return false|void
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        if (!Configuration::get('QUICKPAY_SUBSCRIPTION_STATUS')) {
            return false;
        }

        Media::addJsDef([
            'quickpaysubscription_token' => self::TOKEN,
            'quickpaysubscription_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax')
        ]);

        if (in_array($this->context->controller->php_self, ['product', 'cart', 'order'])) {
            $this->context->controller->registerJavascript(
                'module-quickpaysubsciption-js',
                'modules/'.$this->name.'/views/js/script.js',
                [
                    'priority' => 300,
                    'attribute' => 'defer',
                ]
            );

            $this->context->controller->registerStylesheet(
                'module-quickpaysubsciption-css',
                'modules/'.$this->name.'/views/css/style.css'
            );
        }
    }

    /**
     * Add subscription button to the customer's 'My account' page
     *
     * @return false|string
     * @throws SmartyException
     */
    public function hookDisplayCustomerAccount()
    {
        if (!Module::isEnabled('quickpay') || !Configuration::get('QUICKPAY_SUBSCRIPTION_STATUS') || !$this->context->customer->isLogged()) {
            return false;
        }

        $this->context->smarty->assign(
            'quickpaysubscription_user_url',
            $this->context->link->getModuleLink($this->name, 'user')
        );
        return $this->context->smarty->fetch('module:'.$this->name.'/views/templates/hook/customer.tpl');
    }

    /**
     * Adds information to the cart's page
     *
     * @return false|string
     * @throws SmartyException
     */
    public function hookDisplayShoppingCartFooter()
    {
        if (!Module::isEnabled('quickpay') ||
            !Configuration::get('QUICKPAY_SUBSCRIPTION_STATUS') ||
            !in_array($this->context->controller->php_self, ['cart', 'order'])) {
            return false;
        }

        $this->context->smarty->assign([
            'quickpaysubscription_token' => self::TOKEN,
            'quickpaysubscription_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax')
        ]);

        return $this->context->smarty->fetch('module:'.$this->name.'/views/templates/hook/cart.tpl');
    }

    /**
     * Hook to display the subscription as a payment method
     *
     * @param $params
     * @return false|string
     */
    public function hookPaymentReturn($params)
    {
        if (!Module::isEnabled('quickpay') ||
            !Configuration::get('QUICKPAY_SUBSCRIPTION_STATUS') ||
            !in_array($this->context->controller->php_self, ['cart', 'order']) ||
            !$this->context->customer->isLogged()) {
            return false;
        }

        $order = $params['order'];
        $state = $order->getCurrentState();

        if ($state == _PS_OS_ERROR_) {
            $status = 'callback';
            $msg = 'QuickPay: Confirmation failed';
            $this->addLog($msg, 2, 0, 'Order', $order->id);
        } else {
            $status = 'ok';
        }

        $this->quickpayApi = new \QuickPay\QuickPay(":" . Configuration::get('_QUICKPAY_USER_KEY'));

        return $this->display(__FILE__, 'views/templates/hook/success.tpl');
    }


    /**
     * Hook to display the subscription as a payment method
     *
     * @param $params
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookPayment($params) {
        if (!$this->context->customer->isLogged() ||
            !Module::isEnabled('quickpay') ||
            !Configuration::get('QUICKPAY_SUBSCRIPTION_STATUS') ||
            !QuickpaySubscriptionCart::cartHasSubscriptionProduct($this->context->cart->id)) {
            return;
        }
    }

    /**
     * Hook for setting up the payment method information
     *
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function hookPaymentOptions($params)
    {
        $paymentOptions = array();

        if (!$this->context->customer->isLogged() ||
            !Module::isEnabled('quickpay') ||
            !Configuration::get('QUICKPAY_SUBSCRIPTION_STATUS') ||
            !QuickpaySubscriptionCart::cartHasSubscriptionProduct($this->context->cart->id)) {
            return;
        }

        return $this->setUpPayment($params, $paymentOptions);
    }

    private function setUpPayment($params, &$paymentOptions)
    {
        /** @var QuickPay $quickpay */
        $quickpay = Module::getInstanceByName('quickpay');
        $cart = $params['cart'];
        $id_currency = $cart->id_currency;
        $currency = new Currency($id_currency);
        $cart_total = $quickpay->toQpAmount($cart->getOrderTotal(), $currency);
        if (isset($cart->qpPreview)) {
            $cart_total = 10000;
        }

        $order_id = Configuration::get('_QUICKPAY_ORDER_PREFIX') . ($cart->id);
        $this->context->smarty->assign([
            'payment_logo' => Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/payment_logo.png'),
            'logo_alt' => $this->l('Quickpay subscribe')
        ]);
        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $newOption->setCallToActionText($this->l(('Quickpay subscribe')))
            ->setAction($this->context->link->getModuleLink($this->name,'payment'))
            ->setAdditionalInformation($this->fetch('module:quickpaysubscription/views/templates/hook/payment.tpl'))
            ->setInputs([
                'order_id' => [
                    'name' =>'order_id',
                    'type' =>'hidden',
                    'value' => $order_id,
                ],
                'total' => [
                    'name' =>'total',
                    'type' =>'hidden',
                    'value' => $cart_total,
                ],
            ])
        ;
        $paymentOptions[] = $newOption;

        return $paymentOptions;
    }

    /**
     * Set up all necessary information for the subscription
     *
     * @param $order_id
     * @return array|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function apiPayment($order_id)
    {
        /** @var Quickpay $quickpay */
        $quickpay = Module::getInstanceByName('quickpay');

        $fields = [];
        $mobilepay_checkout = Tools::getValue('mobilepay_checkout');
        $id_cart = (int)Tools::substr($order_id, 3);
        $cart = new Cart($id_cart);

        $delivery_address = new Address($cart->id_address_delivery);
        $carrier = new Carrier($cart->id_carrier);
        $invoice_address = new Address($cart->id_address_invoice);
        $invoice_street = $invoice_address->address1;
        if ($invoice_address->address2) {
            $invoice_street .= ' '.$invoice_address->address2;
        }
        $country = new Country($invoice_address->id_country);
        $invoice_country_code = $quickpay->getIso3($country->iso_code);
        $delivery_address = new Address($cart->id_address_delivery);
        $delivery_street = $delivery_address->address1;
        if ($delivery_address->address2) {
            $delivery_street .= ' '.$delivery_address->address2;
        }
        $country = new Country($delivery_address->id_country);
        $delivery_country = $quickpay->getIso3($country->iso_code);
        $customer = new Customer($cart->id_customer);
        $currency = new Currency($cart->id_currency);
        $info = array(
            'variables[module_version]' => $this->version,
            'shopsystem[name]' => 'PrestaShop',
            'shopsystem[version]' => _PS_VERSION_,
            'customer_email' => $customer->email,
            'google_analytics_client_id' => Configuration::get('_QUICKPAY_GA_CLIENT_ID') ?? '',
            'google_analytics_tracking_id' => Configuration::get('_QUICKPAY_GA_TRACKING_ID') ?? '',
        );
        if ($mobilepay_checkout) {
            $info += array(
                'invoice_address_selection' => true,
                'shipping_address_selection' => true
            );
        }

        if ($delivery_address->id) {
            $info += array(
                'shipping_address[name]' => $delivery_address->firstname .' '. $delivery_address->lastname,
                'shipping_address[street]' => $delivery_street,
                'shipping_address[city]' => $delivery_address->city,
                'shipping_address[zip_code]' => $delivery_address->postcode,
                'shipping_address[country_code]' => $delivery_country,
                'shipping_address[phone_number]' => $delivery_address->phone,
                'shipping_address[mobile_number]' => $delivery_address->phone_mobile,
                'shipping_address[vat_no]' => $delivery_address->vat_number,
                'shipping_address[email]' => $customer->email
            );
        }

        if ($invoice_address->id) {
            $info += array(
                'invoice_address[name]' => $invoice_address->firstname .' '. $invoice_address->lastname,
                'invoice_address[street]' => $invoice_street,
                'invoice_address[city]' => $invoice_address->city,
                'invoice_address[zip_code]' => $invoice_address->postcode,
                'invoice_address[country_code]' => $invoice_country_code,
                'invoice_address[phone_number]' => $invoice_address->phone,
                'invoice_address[mobile_number]' => $invoice_address->phone_mobile,
                'invoice_address[vat_no]' => $invoice_address->vat_number,
                'invoice_address[email]' => $customer->email,
            );

            if (!$delivery_address->id) {
                $info += array(
                    'shipping_address[name]' => $delivery_address->firstname .' '. $delivery_address->lastname,
                    'shipping_address[street]' => $delivery_street,
                    'shipping_address[city]' => $delivery_address->city,
                    'shipping_address[zip_code]' => $delivery_address->postcode,
                    'shipping_address[country_code]' => $delivery_country,
                    'shipping_address[phone_number]' => $delivery_address->phone,
                    'shipping_address[mobile_number]' => $delivery_address->phone_mobile,
                    'shipping_address[vat_no]' => $delivery_address->vat_number,
                    'shipping_address[email]' => $customer->email
                );
            }
        }
        foreach ($info as $k => $v) {
            $fields[$k] = urlencode($v);
        }

        if (!in_array('payment_methods=paypal', $fields)) {
            $info = array(
                'shipping[amount]' => $quickpay->toQpAmount($cart->getTotalShippingCost(), $currency),
                'shipping[vat_rate]' => $carrier->getTaxesRate($delivery_address) / 100,
            );
            foreach ($info as $k => $v) {
                $fields[$k] = urlencode($v);
            }

            foreach ($cart->getProducts() as $product) {
                $info = array(
                    'basket[][qty]' => $product['cart_quantity'],
                    'basket[][item_no]' => $product['id_product'],
                    'basket[][item_name]' => $product['name'],
                    'basket[][item_price]' => $quickpay->toQpAmount($product['price_wt'], $currency),
                    'basket[][vat_rate]' => $product['rate'] / 100
                );
                foreach ($info as $k => $v) {
                    $fields[] = $k.'='.urlencode($v);
                }
            }
            $total_discounts = $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
            if ($total_discounts > 0) {
                $info = array(
                    'basket[][qty]' => 1,
                    'basket[][item_no]' => 0,
                    'basket[][item_name]' => $this->l('Discount', $this->name),
                    'basket[][item_price]' => -$quickpay->toQpAmount($total_discounts, $currency),
                    'basket[][vat_rate]' => '-'
                );
                foreach ($info as $k => $v) {
                    $fields[] = $k.'='.urlencode($v);
                }
            }
        }

        if (!Validate::isLoadedObject($cart)) {
            $msg = 'QuickPay: Payment error. Not a valid cart';
            $this->addLog($msg, 2, 0, 'Cart', $id_cart);
            die('Not a valid cart');
        }

        return $fields;
    }

    /**
     * Add information to each cart item line about the selected subscription
     *
     * @param $params
     * @return false|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayCartExtraProductActions($params)
    {
        $product = $params['product'];

        $subscriptionCartId = QuickpaySubscriptionCart::getByProductId($this->context->cart->id, $this->context->cart->id_customer, $product['id'], $product['id_product_attribute']);

        if (!$subscriptionCartId) {
            return false;
        }

        $subscriptionCartProduct = new QuickpaySubscriptionCart($subscriptionCartId);

        $this->context->smarty->assign([
            'id_product' => $product['id'],
            'id_subscription_cart' => $subscriptionCartId,
            'plan' => $subscriptionCartProduct->id_plan,
            'frequency' => QuickpaySubscriptionPlan::getTranslationByPlanAndFrequency($subscriptionCartProduct->id_plan, $subscriptionCartProduct->frequency),
            'cycle' => $subscriptionCartProduct->frequency
        ]);

        return $this->context->smarty->fetch('module:'.$this->name.'/views/templates/hook/cart_product.tpl');
    }

    public function cancelSubscription($json, $checksum, $idOrderState, $callback = false)
    {
        $this->quickpayApi = new \QuickPay\QuickPay(":" . Configuration::get('_QUICKPAY_USER_KEY'));

        /** @var Quickpay $quickpay */
        $quickpay = Module::getInstanceByName('quickpay');
        $quickpay->getSetup();

        $data = json_decode($json);

        if ($checksum != $this->sign($json, \Configuration::get('_QUICKPAY_PRIVATE_KEY'))) {
            $msg = 'QuickPay: Validate error. Checksum failed. Check private key in configuration';
            $this->addLog($msg, 2);
            http_response_code(500);
            die('Checksum failed');
        }

        Db::getInstance()->update('quickpaysubscription_subscriptions', [
            'status' => 0,
        ], 'id = ' . $data->id);

        http_response_code(200);
    }

    public function sign($data, $key)
    {
        return call_user_func('hash_hmac', 'sha256', $data, $key);
    }

    /**
     * Functionality to make debugging easier
     *
     * @param $data
     * @return void
     */
    public static function prePrint($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

    /**
     * Function to add logs to the backoffice
     *
     * @param $message
     * @param $severity
     * @param $error_code
     * @param $object_type
     * @param $object_id
     * @return void
     */
    public function addLog($message, $severity = 1, $error_code = null, $object_type = null, $object_id = null)
    {
        Logger::addLog($message, $severity, $error_code, $object_type, $object_id);
    }

    /**
     * Update the subscription cart after successful login
     *
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function hookActionAuthentication($params)
    {

        $subscriptionCartId = QuickpaySubscriptionCart::getIdByCartId(Context::getContext()->cart->id);
        if (!$subscriptionCartId) {
            return false;
        }

        $subscriptionCart = new QuickpaySubscriptionCart($subscriptionCartId);
        $subscriptionCart->id_customer = Context::getContext()->customer->id;

        $subscriptionCart->save();
    }
}
