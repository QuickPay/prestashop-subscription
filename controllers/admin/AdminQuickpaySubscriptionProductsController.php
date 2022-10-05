<?php

use QuickpaySubscripton\QuickpaySubscriptionPlan;
use QuickpaySubscripton\QuickpaySubscriptionProduct;

class AdminQuickpaySubscriptionProductsController extends \ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->module = 'quickpaysubscription';
        $this->page_header_toolbar_title = Module::getInstanceByName('quickpaysubscription')->l('Subscription products');
        $this->toolbar_title = Module::getInstanceByName('quickpaysubscription')->l('Subscription products');
        Module::getInstanceByName('quickpaysubscription')->lang = false;
        $this->table = QuickpaySubscriptionProduct::$definition['table'];
        $this->className = QuickpaySubscriptionProduct::class;
        $this->identifier = QuickpaySubscriptionProduct::$definition['primary'];
        $this->deleted = false;
        $this->_defaultOrderBy = QuickpaySubscriptionProduct::$definition['primary'];
        $this->_defaultOrderWay = 'ASC';
        $this->allow_export = true;
        $this->explicitSelect = true;
        $this->_pagination = [25, 50, 100, 250, 500];
        $this->_default_pagination = 25;
        $this->show_toolbar = true;
        $this->toolbar_scroll = true;

        $this->context = Context::getContext();

        $this->_select = 'p.id_product,pl.name as product_name, p.price, p.quantity, p.state, p.id_product as image, p.id_product as subscriptions, p.id_product as subscribers';
        $this->_join = 'INNER JOIN ' . _DB_PREFIX_ . 'product p
        ON (p.id_product = a.id_product)
        INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (pl.id_product = a.id_product AND pl.id_shop = a.id_shop)
        INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON (ps.id_product = a.id_product AND ps.id_shop = a.id_shop)';
        $this->_where = ' AND pl.id_lang = ' . (int) Context::getContext()->language->id .
            ' AND ps.id_shop IN ('.implode(',', Shop::getContextListShopID()).')';

        $this->_orderBy = 'a.id_product';
        $this->_orderWay = 'DESC';

        $this->fields_list = [
            QuickpaySubscriptionProduct::$definition['primary'] => [
                'title' =>  Module::getInstanceByName('quickpaysubscription')->l('ID'),
                'type' => 'int',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'havingFilter' => true,
                'filter_key' => 'a!' . QuickpaySubscriptionProduct::$definition['primary']
            ],
            'id_product' => [
                'title' =>  Module::getInstanceByName('quickpaysubscription')->l('ID product'),
                'type' => 'int',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'havingFilter' => true,
            ],
            'image' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Image'),
                'search' => false,
                'orderBy' => false,
                'align' => 'center',
                'callback' => 'displayProductImage'
            ],
            'product_name' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Name'),
                'align' => 'left',
                'havingFilter' => true,
            ],
            'quantity' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Quantity'),
                'type' => 'int',
                'align' => 'center',
                'havingFilter' => true,
                'callback' => 'displayStockQuantity'
            ],
            'price' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Price'),
                'align' => 'center',
                'type' => 'price',
                'havingFilter' => true,
                'hint' => Module::getInstanceByName('quickpaysubscription')->l('Tax exlusive'),
             //   'callback' => 'displayFormattedPrice'
            ],
            'id_plan' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Plan'),
                'align' => 'center',
                'havingFilter' => true,
                'callback' => 'displayPlan'
            ],
            'cycle' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Cycles'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'orderBy' => false,
                'callback' => 'displayCycle'
            ],
            'status' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Status'),
                'align' => 'center',
                'type'  => 'bool',
                'class' => 'fixed-width-xs',
                'orderBy' => false,
                'hint' => Module::getInstanceByName('quickpaysubscription')->l('Product subscription status'),
                'active' => 'status'
            ],
            'id_product_edit' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Edit'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'callback' => 'displayEditButton',
                'filter_key' => 'a!id_product'
            ],
        ];

        parent::__construct();
    }

    public function renderList()
    {

        return parent::renderList(); // TODO: Change the autogenerated stub
    }

    public function processEdit($id_product)
    {
        $link = $this->context->link->getAdminLink(
                'AdminProducts',
                true,
                array('id_product' => (int)$id_product)
            ).'#tab-hooks';
    }

    /**
     * @throws \PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     */
    public function displayFormattedPrice($price)
    {
        return Context::getContext()->getCurrentLocale()->formatPrice($price);
    }

    public function displayProductImage($id_product)
    {
        $product = new \Product($id_product, $this->context->language->id, $this->context->shop->id);

        $image = Product::getCover($id_product);

        $image_url = $this->context->link->getImageLink($product->link_rewrite, $image['id_image']);

        if ($image_url) {
            return '<img class="imgm img-thumbnail" width="45" height="45" src="' . $image_url . '">';
        } else {
            return '--';
        }
    }

    public function displayStockQuantity($quantity, $rowData)
    {
        $quantity = (int) StockAvailable::getQuantityAvailableByProduct($rowData['id_product'], 0);
        return $quantity;
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function displayPlan($id)
    {
        $plan = new QuickpaySubscriptionPlan($id, $this->context->language->id, $this->context->shop->id);

        return $plan->name;
    }

    public function displayCycle($cycle)
    {
         if ($cycle) {
             $cycle = json_decode($cycle);

             return implode('<br />', $cycle);
         }

         return '';
    }

    public function displayEditButton($id)
    {
        return '<a class="btn btn-default" href="' . Context::getContext()->link->getAdminLink('AdminProducts', true, ['id_product' => $id]) . '#tab-hooks"><i class="icon-edit"></i>' . Module::getInstanceByName('quickpaysubscription')->l('Edit') . '</a>';
    }

    public function renderForm()
    {
        $this->initToolbar();

        /** @var QuickpaySubscriptionProduct $obj */
        $obj = $this->loadObject(true);
        $context = Context::getContext();

        $this->toolbar_title = sprintf(Module::getInstanceByName('quickpaysubscription')->l('Edit product: %s'), (is_array($obj->id) ? $obj->id : $obj->id));


        $plans = QuickpaySubscriptionPlan::getAll();
        $formPlans = [];
        foreach ($plans as $plan) {
            $formPlans[] = [
                'id' => $plan['id'],
                'name' => $plan['name'],
                'cycles' => json_decode($plan['frequency'])
            ];
        }

        $subProducts = [];
        $subProductsTmp = QuickpaySubscriptionProduct::getAll();

        foreach ($subProductsTmp as $item) {
            $subProducts[] = $item['id_product'];
        }

        $products = \Product::getProducts($this->context->language->id, 0, 9999999, 'id_product', 'ASC');

        $formProducts = [];

        foreach ($products as &$product) {
            if(!in_array($product['id_product'], $subProducts)) {
                $formProducts[] = [
                    'id' => $product['id_product'],
                    'name' => $product['name'] . '(ID: ' . $product['id_product'] . ')'
                ];
            }

        }


        $this->fields_form = [
            'tinymce' => true,
            'legend' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Quickpay subscription plan'),
                'icon' => 'icon-tags',
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => Module::getInstanceByName('quickpaysubscription')->l('Product'),
                    'name' => 'id_product',
                    'required' => true,
                    'options' => array(
                        'query' => $formProducts,
                        'id' => 'id',
                        'name' => 'name',
                    ),
                ],
                [
                    'type' => 'select',
                    'label' => Module::getInstanceByName('quickpaysubscription')->l('Plan'),
                    'name' => 'id_plan',
                    'required' => true,
                    'options' => array(
                        'query' => $formPlans,
                        'id' => 'id',
                        'name' => 'name',
                    ),
                ],
                [
                    'type' => 'switch',
                    'label' => Module::getInstanceByName('quickpaysubscription')->l('Status'),
                    'name' => 'status',
                    'required' => false,
                    'shop' => true,
                    'values' => array(
                        array(
                            'id' => 'status_on',
                            'value' => 1,
                            'label' => Module::getInstanceByName('quickpaysubscription')->l('Enabled')
                        ),
                        array(
                            'id' => 'status_off',
                            'value' => 0,
                            'label' => Module::getInstanceByName('quickpaysubscription')->l('Disabled')
                        )
                    )
                ],
            ],
            'submit' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Save'),
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type' => 'shop',
                'label' => Module::getInstanceByName('quickpaysubscription')->l('Shop association'),
                'name' => 'checkBoxShopAsso',
            ];
        }
        if (!($this->loadObject(true))) {
            return false;
        }

        return parent::renderForm();
    }
}

