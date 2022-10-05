<?php


use QuickpaySubscripton\QuickpaySubscriptionPlan;

class AdminQuickpaySubscriptionPlansController extends \ModuleAdminControllerCore
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->module = 'quickpaysubscription';
        $this->page_header_toolbar_title = Module::getInstanceByName('quickpaysubscription')->l('Subscription plans');
        $this->toolbar_title = Module::getInstanceByName('quickpaysubscription')->l('Subscription plans');
        $this->table = QuickpaySubscriptionPlan::$definition['table'];
        $this->className = QuickpaySubscriptionPlan::class;
        $this->identifier = QuickpaySubscriptionPlan::$definition['primary'];
        $this->deleted = false;
        $this->_defaultOrderBy = QuickpaySubscriptionPlan::$definition['primary'];
        $this->_defaultOrderWay = 'ASC';
        $this->allow_export = true;
        $this->explicitSelect = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->_pagination = [25, 50, 100, 250, 500];
        $this->_default_pagination = 25;
        $this->show_toolbar = true;
        $this->toolbar_scroll = true;
        $this->lang = true;
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . $this->table . '_shop` c ON (c.`' . QuickpaySubscriptionPlan::$definition['primary'] . '` = a.`' . QuickpaySubscriptionPlan::$definition['primary'] . '`)';


        $this->context = Context::getContext();

        $this->fields_list = [
            QuickpaySubscriptionPlan::$definition['primary'] => [
                'title' =>  Module::getInstanceByName('quickpaysubscription')->l('ID'),
                'type' => 'int',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'havingFilter' => true,
                'filter_key' => 'a!' . QuickpaySubscriptionPlan::$definition['primary']
            ],
            'name' => [
                'title' =>  Module::getInstanceByName('quickpaysubscription')->l('Plan'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'havingFilter' => true,
                'filter_key' => 'b!name'
            ],
            'frequency' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Cycle'),
                'search' => false,
                'orderBy' => false,
                'align' => 'center',
            ],
            'status' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Status'),
                'align' => 'center',
                'type'  => 'bool',
                'class' => 'fixed-width-xs',
                'orderBy' => false,
                'hint' => Module::getInstanceByName('quickpaysubscription')->l('Product subscription status'),
                'active' => 'c!status',
                'filter_key' => 'c!status'
            ]
        ];

        parent::__construct();
    }

    public function renderList()
    {
        return parent::renderList(); // TODO: Change the autogenerated stub
    }

    public function renderForm()
    {
        $this->initToolbar();

        /** @var QuickpaySubscriptionPlan $obj */
        $obj = $this->loadObject(true);
        $context = Context::getContext();

        $this->toolbar_title = sprintf(Module::getInstanceByName('quickpaysubscription')->l('Edit plan: %s'), (is_array($obj->id) ? $obj->id : $obj->id));

        $cycles = ['daily', 'weekly', 'monthly', 'yearly'];

        $cyclesObj = [];
        foreach ($cycles as $key => $cycle) {
            $cyclesObj[] = [
                'id' => $key,
                'name' => $cycle,
            ];
        }

        $this->fields_form = [
            'tinymce' => true,
            'legend' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Quickpay subscription plan'),
                'icon' => 'icon-tags',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => Module::getInstanceByName('quickpaysubscription')->l('Name'),
                    'name' => 'name',
                    'lang' => true,
                    'required' => true,
                    'maxchar' => 255,
                    'rows' => 5,
                    'cols' => 100,
                    'hint' => Module::getInstanceByName('quickpaysubscription')->l('Invalid characters:') . ' <>;=#{}',
                ],
                [
                    'type' => 'select',
                    'label' => Module::getInstanceByName('quickpaysubscription')->l('Cycles'),
                    'name' => 'frequency',
                    'required' => true,
                    'options' => array(
                        'query' => $cyclesObj,
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