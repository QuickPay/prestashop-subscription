<?php

use QuickpaySubscripton\QuickpaySubscriptionCart;
use QuickpaySubscripton\QuickpaySubscriptionPlan;
use QuickpaySubscripton\QuickpaySubscriptionProduct;
use QuickpaySubscripton\QuickpaySubscriptionSubscriptions;

class AdminQuickpaySubscriptionsController extends \ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->module = 'quickpaysubscription';
        $this->page_header_toolbar_title = Module::getInstanceByName('quickpaysubscription')->l('Subscriptions');
        $this->toolbar_title = Module::getInstanceByName('quickpaysubscription')->l('Subscriptions');
        Module::getInstanceByName('quickpaysubscription')->lang = false;
        $this->table = QuickpaySubscriptionSubscriptions::$definition['table'];
        $this->className = QuickpaySubscriptionSubscriptions::class;
        $this->identifier = QuickpaySubscriptionSubscriptions::$definition['primary'];
        $this->deleted = false;
        $this->_defaultOrderBy = QuickpaySubscriptionSubscriptions::$definition['primary'];
        $this->_defaultOrderWay = 'ASC';
        $this->allow_export = true;
        $this->explicitSelect = true;
        $this->_pagination = [25, 50, 100, 250, 500];
        $this->_default_pagination = 25;
        $this->show_toolbar = true;
        $this->toolbar_scroll = true;
        $this->addRowAction('edit');

        $this->context = Context::getContext();

        $this->_orderBy = 'date_add';
        $this->_orderWay = 'DESC';

        $this->fields_list = [
            QuickpaySubscriptionSubscriptions::$definition['primary'] => [
                'title' =>  Module::getInstanceByName('quickpaysubscription')->l('Subscription ID'),
                'type' => 'int',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'havingFilter' => true,
                'filter_key' => 'a!' . QuickpaySubscriptionSubscriptions::$definition['primary'],
                'hint' => Module::getInstanceByName('quickpaysubscription')->l('The ID of the subscription from Quickpay.'),
            ],
            'id_customer' => [
                'title' =>  Module::getInstanceByName('quickpaysubscription')->l('Customer'),
                'type' => 'int',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'havingFilter' => true,
                'callback' => 'displayCustomer',
                'hint' => Module::getInstanceByName('quickpaysubscription')->l('Customer used to create the subscription'),
            ],
            'id_cart' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Original Cart ID'),
                'align' => 'center',
                'callback' => 'displayCart',
                'hint' => Module::getInstanceByName('quickpaysubscription')->l('Original Cart ID that was used to create the subscription.'),
            ],
            'products' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Products'),
                'align' => 'left',
                'havingFilter' => true,
                'callback' => 'displayProducts',
                'filter_key' => 'a!id_cart',
                'hint' => Module::getInstanceByName('quickpaysubscription')->l('Products bought in this subscription'),
            ],
            'id_plan' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Plan'),
                'type' => 'int',
                'align' => 'center',
                'havingFilter' => true,
                'callback' => 'displayPlan',
                'hint' => Module::getInstanceByName('quickpaysubscription')->l('The plan that was used to create the subscription'),
            ],
            'frequency' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Frequency'),
                'align' => 'center',
                'type' => 'int',
                'havingFilter' => true,
                'hint' => Module::getInstanceByName('quickpaysubscription')->l('How often the subscription will be renewed based on the plan')
            ],
            'last_recurring' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Last recurring order'),
                'align' => 'center',
                'havingFilter' => true,
                'type' => 'datetime',
                'hint' => Module::getInstanceByName('quickpaysubscription')->l('Last date when a recurring order was created for the subscription'),
            ],
            'status' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Status'),
                'align' => 'center',
                'type'  => 'bool',
                'class' => 'fixed-width-xs',
                'orderBy' => false,
                'hint' => Module::getInstanceByName('quickpaysubscription')->l('Status of the subscription'),
                'active' => 'status'
            ],
            'date_add' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Created at'),
                'align' => 'center',
                'havingFilter' => true,
                'type' => 'datetime',
                'hint' => Module::getInstanceByName('quickpaysubscription')->l('Date when the subscription was created'),
            ],
            'date_upd' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Updated at'),
                'align' => 'center',
                'havingFilter' => true,
                'type' => 'datetime',
                'hint' => Module::getInstanceByName('quickpaysubscription')->l('Last date when the subscription was updated'),
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

    public function displayCustomer($idCustomer, $align = true) {
        $customer = new \Customer($idCustomer);

        $classes = [
            'padding: 10px 15px;',
            'display: flex;',
            'align-content: center;',
            'align-items: center;',
            'align-self: center;',
            'columns: 2;',
            'flex-direction: row;',
            'width: fit-content;',
        ];
        if ($align) {
            $classes[] = 'margin: 0 auto;';
        }

        return '<a class="btn btn-default" style="padding: 10px 15px;display: flex;flex-direction: row;width: fit-content;align-items: center;align-self: center;align-content: center;" href="' . Context::getContext()->link->getAdminLink('AdminCustomers', true, ['route' => 'admin_customers_view', 'customerId' => $idCustomer, 'action' => 'view']) . '" target="_blank"><i class="material-icons">face</i><span style="margin-left: 5px">' . $customer->firstname . ' ' . $customer->lastname . '</span></a>';
    }

    public function displayCart($idCart, $align = true) {
        $classes = [
            'padding: 10px 15px;',
            'display: flex;',
            'align-content: center;',
            'align-items: center;',
            'align-self: center;',
            'columns: 2;',
            'flex-direction: row;',
            'width: fit-content;',
        ];
        if ($align) {
            $classes[] = 'margin: 0 auto;';
        }
        return '<a class="btn btn-default" style="' . implode('', $classes) . '" href="' . Context::getContext()->link->getAdminLink('AdminCarts', true, ['route' => 'admin_carts_view', 'cartId' => $idCart, 'action' => 'view']) . '" target="_blank"><span style="margin-right: 5px">' . $idCart . '</span><i class="material-icons">navigate_next</i></a>';
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function displayPlan($idPlan)
    {
        $plan = new QuickpaySubscriptionPlan($idPlan, $this->context->language->id);
        return $plan->name;
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function displayProducts($idCart) {
        $html = '';
        $cartData = QuickpaySubscriptionCart::getByCartId($idCart);


        $cartProducts = [];

        foreach ($cartData as $data) {
            $cartProducts[] = [
                'id_product' => $data['id_product'],
                'id_product_attribute' => $data['id_product_attribute'],
                'id_customization' => $data['id_customization'],
            ];
        }

        foreach ($cartProducts as $prod) {
            $subProductId = QuickpaySubscriptionProduct::getIdByProductId($prod['id_product']);
            $subProduct = new QuickpaySubscriptionProduct($subProductId);
            $product = new \Product($subProduct->id_product, Context::getContext()->language->id, Context::getContext()->shop->id);
            $html .= '<a href="' . \Context::getContext()->link->getAdminLink('AdminProducts', true, ['id_product' => $product->id]) . '">';
            $html .= '<table style="width: 100%"><tbody><tr>';
            $html .= '<td style="width: 65px;"><img class="imgm img-thumbnail" width="50" height="50" src="' . Context::getContext()->link->getImageLink($product->link_rewrite, $product->getCoverWs(), 'small_default') . '"  alt=""/></td>';
            $html .= '<td>'. Product::getProductName($prod['id_product'], $prod['id_product_attribute']);
            $html .= '<span style="display: block;">' . Customization::getLabel($prod['id_customization'], \Context::getContext()->language->id) . '</span>';
            $html .= '</td>';
            $html .= '</tr></tbody></table>';
            $html .= '</a>';
        }

        return $html;
    }

    public function renderForm()
    {
        $this->initToolbar();

        /** @var QuickpaySubscriptionSubscriptions $obj */
        $obj = $this->loadObject(true);
        $context = Context::getContext();

        $this->toolbar_title = sprintf(Module::getInstanceByName('quickpaysubscription')->l('View subscription: %s'), (is_array($obj->id) ? $obj->id : $obj->id));


        $productHtml = $this->displayProducts($obj->id_cart);

        $plan = new QuickpaySubscriptionPlan($obj->id_plan, $this->context->language->id);

        switch ($plan->frequency) {
            case 'weekly':
                $planHtml = sprintf('Every %d weeks(s)', $obj->frequency);
                break;
            case 'monthly':
                $planHtml = sprintf('Every %d month(s)', $obj->frequency);
                break;
            case 'yearly':
                $planHtml = sprintf('Every %d year(s)', $obj->frequency);
                break;
            default:
                $planHtml = sprintf('Every %d day(s)', $obj->frequency);
                break;
        }

        $this->fields_value['id_plan'] = $planHtml;



        $this->fields_value['orders'] = '';

        $this->fields_value['status'] = $obj->status ? Module::getInstanceByName('quickpaysubscription')->l('Active') : Module::getInstanceByName('quickpaysubscription')->l('Cancelled');

        $this->fields_form = [
            'tinymce' => true,
            'legend' => [
                'title' => Module::getInstanceByName('quickpaysubscription')->l('Quickpay subscription plan'),
                'icon' => 'icon-tags',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => Module::getInstanceByName('quickpaysubscription')->l('Subscription ID'),
                    'name' => 'id',
                    'required' => true,
                    'readonly' => true
                ],
                [
                    'type' => 'html',
                    'label' => Module::getInstanceByName('quickpaysubscription')->l('Customer'),
                    'name' => 'orders',
                    'required' => true,
                    'readonly' => true,
                    'html_content' => $this->displayCustomer($obj->id_customer)
                ],
                [
                    'type' => 'html',
                    'label' => Module::getInstanceByName('quickpaysubscription')->l('Cart ID'),
                    'name' => 'orders',
                    'required' => true,
                    'readonly' => true,
                    'html_content' => $this->displayCart($obj->id_cart, false)
                ],
                [
                    'type' => 'html',
                    'label' => Module::getInstanceByName('quickpaysubscription')->l('Products'),
                    'name' => 'products',
                    'required' => true,
                    'readonly' => true,
                    'html_content' => $productHtml
                ],
                [
                    'type' => 'text',
                    'label' => Module::getInstanceByName('quickpaysubscription')->l('Plan'),
                    'name' => 'id_plan',
                    'required' => true,
                    'readonly' => true,
                ],
                [
                    'type' => 'text',
                    'label' => Module::getInstanceByName('quickpaysubscription')->l('Last recurring order'),
                    'name' => 'last_recurring',
                    'required' => true,
                    'readonly' => true
                ],
                [
                    'type' => 'html',
                    'label' => Module::getInstanceByName('quickpaysubscription')->l('Order list'),
                    'name' => 'orders',
                    'required' => true,
                    'readonly' => true,
                    'html_content' => $this->getSubscriptionOrders($obj->id)
                ],
                [
                    'type' => 'switch',
                    'label' => Module::getInstanceByName('quickpaysubscription')->l('Status'),
                    'name' => 'status',
                    'required' => false,
                    'shop' => true,
                    'readonly' => true,
                    'hint' => Module::getInstanceByName('quickpaysubscription')->l('You can cancel the subscriptions from the subscription list by clicking on the check icon'),
                    'values' => array(
                        array(
                            'id' => 'status_off',
                            'value' => 0,
                            'label' => Module::getInstanceByName('quickpaysubscription')->l('Disabled')
                        ),
                        array(
                            'id' => 'status_on',
                            'value' => 1,
                            'label' => Module::getInstanceByName('quickpaysubscription')->l('Enabled')
                        ),
                    )
                ],
            ],
            'buttons' => [

            ],
        ];

        if (!($this->loadObject(true))) {
            return false;
        }

        return parent::renderForm();
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    private function getSubscriptionOrders($subscriptionId)
    {
        $result = QuickpaySubscriptionSubscriptions::getOrdersById($subscriptionId);

        $html = '<table class="table">';
        $html .= '<thead><th>Order ID</th><th>Created at</th><th>Link</th></thead><tbody>';

        foreach ($result as &$res) {
            $html .= '<tr><td>' . $res['id_order'] . '</td><td>' . $res['date_add'] . '</td><td><a class="btn btn-default" href="' . Context::getContext()->link->getAdminLink('AdminOrders', true, ['id_order' => $res['id_order'], 'vieworder' => true]) . '">Go to order</a></td></tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }
}
