<?php return array(
    'root' => array(
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'type' => 'prestashop-module',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => 'quickpay/quickpaysubscription',
        'dev' => true,
    ),
    'versions' => array(
        'quickpay/quickpay-php-client' => array(
            'pretty_version' => '1.1.0',
            'version' => '1.1.0.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../quickpay/quickpay-php-client',
            'aliases' => array(),
            'reference' => '31ec24f449b6bc27607773098e150a3f005441ad',
            'dev_requirement' => false,
        ),
        'quickpay/quickpaysubscription' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'type' => 'prestashop-module',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
    ),
);
