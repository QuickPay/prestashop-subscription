<?php

namespace QuickpaySubscripton;

use Context;
use Db;
use DbQuery;
use Module;
use PrestaShop\PrestaShop\Adapter\Entity\ObjectModel;
use PrestaShopDatabaseException;
use PrestaShopException;
use QuickpaySubscription;
use Shop;

class QuickpaySubscriptionPlan extends \ObjectModel
{
    public $id;
    public $id_lang;
    public $id_shop;

    public $status;
    public $frequency;
    public $name;

    public $date_add;
    public $date_upd;



    public static $definition = [
        'table' => QuickpaySubscription::TABLE_BASE . '_plan',
        'primary' => 'id',
        'multilang' => true,
        'multishop' => true,
        'fields' => [
            'status' => ['type' => self::TYPE_INT, 'shop' => true, 'required' => false],
            'frequency' => ['type' => self::TYPE_STRING, 'required' => true],
            'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => '', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'required' => true],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        Shop::addTableAssociation(self::$definition['table'], array('type' => 'shop'));
        parent::__construct($id, $id_lang, $id_shop);
    }

    /**
     * Returns all created plans
     *
     * @param int $idLang Language ID
     * @poram int $idShop Shop ID
     *
     * @throws PrestaShopDatabaseException
     */
    public static function getAll($idLang = 0, $idShop = 0)
    {
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }

        if (!$idShop) {
            $idShop = Context::getContext()->shop->id;
        }

        $sql = new DbQuery();
        $sql->select('p.`' . self::$definition['primary'] . '`, p.`frequency`, pl.`name`, ps.`status`');
        $sql->from(self::$definition['table'], 'p');
        $sql->innerJoin(self::$definition['table'] . '_lang', 'pl', 'pl.' . self::$definition['primary'] . ' = p.' . self::$definition['primary']);
        $sql->innerJoin(self::$definition['table'] . '_shop', 'ps', 'ps.' . self::$definition['primary'] . ' = p.' . self::$definition['primary']);
        $sql->where('ps.`id_shop` = ' . $idShop);
        $sql->where('pl.`id_lang` = ' . $idLang);
        $sql->where('ps.`status` = 1');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Ajax functionality for backoffice list to enable/disable plans
     *
     * @return bool|int|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function toggleStatus()
    {
        // Object must have a variable called 'status'
        if (!property_exists($this, 'status')) {
            throw new PrestaShopException('property "status" is missing in object ' . get_class($this));
        }

        // Update only active field
        $this->setFieldsToUpdate(['status' => true]);

        // Update active status on object
        $this->status = !(int) $this->status;

        // Change status to active/inactive
        return $this->update(false);
    }

    /**
     * Get the amount of plans by shop
     *
     * @throws PrestaShopDatabaseException
     */
    public static function getCount($idShop = 0)
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*)');
        $sql->from(self::$definition['table'], 'qp');
        $sql->innerJoin(self::$definition['table'] . '_shop', 'qps', 'qps.' . self::$definition['primary'] . ' = qp.' . self::$definition['primary']);
        $sql->where('qps.id_shop = ' . $idShop);

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get plans by frequency
     *
     * @param $frequency
     * @param $idLang
     * @param $idShop
     * @return int
     */
    public static function getByFrequency($frequency, $idLang = 0, $idShop = 0)
    {
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }

        if (!$idShop) {
            $idShop = Context::getContext()->shop->id;
        }

        $sql = new DbQuery();
        $sql->select('p.' . self::$definition['primary']);
        $sql->from(self::$definition['table'], 'p');
        $sql->innerJoin(self::$definition['table'] . '_lang', 'pl', 'pl.' . self::$definition['primary'] . ' = p.' . self::$definition['primary']);
        $sql->innerJoin(self::$definition['table'] . '_shop', 'ps', 'ps.' . self::$definition['primary'] . ' = p.' . self::$definition['primary']);
        $sql->where('p.frequency = \'' . $frequency . '\'');
        $sql->where('ps.`id_shop` = ' . $idShop);
        $sql->where('pl.`id_lang` = ' . $idLang);
        $sql->where('ps.`status` = 1');

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get plans for the admin users
     *
     * @throws PrestaShopDatabaseException
     */
    public static function getPlansForAdmin($idLang = 0, $idShop = 0)
    {
        $plans = self::getAll($idLang, $idShop);

        foreach ($plans as &$plan) {
            $plan['label'] = $plan['name'];
            $plan['name'] = $plan['frequency'];
            $plan['multiple'] = true;
            switch ($plan['frequency']) {
                case 'daily':
                    for ($i = 1; $i <= QuickpaySubscription::DAILY_MAX; $i++) {
                        $plan['choices'][$i] = $i;
                    }
                    break;
                case 'weekly':
                    for ($i = 1; $i <= QuickpaySubscription::WEEKLY_MAX; $i++) {
                        $plan['choices'][$i] = $i;
                    }
                    break;
                case 'monthly':
                    for ($i = 1; $i <= QuickpaySubscription::MONTHLY_MAX; $i++) {
                        $plan['choices'][$i] = $i;
                    }
                    break;
                case 'yearly':
                    for ($i = 1; $i <= QuickpaySubscription::YEARLY_MAX; $i++) {
                        $plan['choices'][$i] = $i;
                    }
                    break;
            }
        }

        return $plans;
    }

    /**
     * Get the maximum frequencies for plans
     *
     * @return array
     */
    public static function getFrequencyChoicesForAdmin()
    {
        $choices = [];
        for ($i = 1; $i <= 11; $i++) {
            $choices[$i] = $i;
        }

        return $choices;
    }

    /**
     * Get translations for frequencies
     *
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public static function getTranslationByPlanAndFrequency($idPlan, $frequency)
    {
        $module = Module::getInstanceByName('quickpaysubscription');
        $plan = new self($idPlan, Context::getContext()->language->id);

        $text = $frequency . ' ';

        if ($frequency > 1) {
            switch ($plan->frequency) {
                case 'daily':
                    $text .= $module->l('days');
                    break;
                case 'weekly':
                    $text .= $module->l('weeks');
                    break;
                case 'monthly':
                    $text .= $module->l('months');
                    break;
                case 'yearly':
                    $text .= $module->l('years');
                    break;
            }
        } else {
            switch ($plan->frequency) {
                case 'daily':
                    $text .= $module->l('day');
                    break;
                case 'weekly':
                    $text .= $module->l('week');
                    break;
                case 'monthly':
                    $text .= $module->l('month');
                    break;
                case 'yearly':
                    $text .= $module->l('year');
                    break;
            }
        }

        return $text;
    }
}
