<?php
/**
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author Payrexx <support@payrexx.com>
 * @copyright  2017 Payrexx
 * @license MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Payrexx extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'payrexx';
        $this->tab = 'payments_gateways';
        $this->module_key = '0c4dbfccbd85dd948fd9a13d5a4add90';
        $this->version = '1.1.0';
        $this->author = 'Payrexx';
        $this->is_eu_compatible = 1;
        $this->ps_versions_compliancy = array('min' => '1.6');
        $this->controllers = array('payment', 'validation', 'gateway');

        parent::__construct();

        $this->displayName = $this->l('Payrexx');
        $this->description = $this->l('Accept payments using Payrexx Payment gateway');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->validateDb();
    }

    private function validateDb()
    {
        Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payrexx_gateway` (
                id_cart INT(11) NOT NULL UNIQUE,
                id_gateway INT(11) UNSIGNED DEFAULT "0" NOT NULL,
                PRIMARY KEY (`id_cart`)
            ) DEFAULT CHARSET=utf8');
    }

    public function install()
    {
        // Install default
        if (!parent::install() || !$this->installDb() || !$this->registrationHook()) {
            return false;
        }

        if (!Configuration::updateValue('PAYREXX_PLATFORM', '')
            || !Configuration::updateValue('PAYREXX_API_SECRET', '')
            || !Configuration::updateValue('PAYREXX_INSTANCE_NAME', '')
            || !Configuration::updateValue('PAYREXX_PAY_ICONS', '')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Install DataBase table
     * @return boolean if install was successfull
     */
    private function installDb()
    {
        return Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payrexx_gateway` (
                id_cart INT(11) NOT NULL UNIQUE,
                id_gateway INT(11) UNSIGNED DEFAULT "0" NOT NULL,
                PRIMARY KEY (`id_cart`)
            ) DEFAULT CHARSET=utf8');
    }

    /**
     * [registrationHook description]
     * @return [type] [description]
     */
    private function registrationHook()
    {
        if (_PS_VERSION_ >= '1.7' && !$this->registerHook('paymentOptions')) {
            return false;
        } elseif (_PS_VERSION_ < '1.7' &&
            (!$this->registerHook('payment') || !$this->registerHook('paymentReturn'))) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        $config = array(
            'PAYREXX_PLATFORM',
            'PAYREXX_API_SECRET',
            'PAYREXX_INSTANCE_NAME',
            'PAYREXX_PAY_ICONS',
        );
        foreach ($config as $var) {
            Configuration::deleteByName($var);
        }

        //Uninstall DataBase
        if (!$this->uninstallDb()) {
            return false;
        }

        // Uninstall default
        if (!parent::uninstall()) {
            return false;
        }
        return true;
    }

    /**
     * Uninstall DataBase table
     * @return boolean if install was successfull
     */
    private function uninstallDb()
    {
        return Db::getInstance()->execute('DROP TABLE `' . _DB_PREFIX_ . 'payrexx_gateway`');
    }

    public function getContent()
    {
        $this->postProcess();

        $paymentMethods = array(
            array('id_option' => 'masterpass', 'name' => 'Masterpass',),
            array('id_option' => 'mastercard', 'name' => 'Mastercard',),
            array('id_option' => 'visa', 'name' => 'Visa',),
            array('id_option' => 'apple_pay', 'name' => 'Apple Pay',),
            array('id_option' => 'maestro', 'name' => 'Maestro',),
            array('id_option' => 'jcb', 'name' => 'JCB',),
            array('id_option' => 'american_express', 'name' => 'American Express',),
            array('id_option' => 'wirpay', 'name' => 'WIRpay',),
            array('id_option' => 'paypal', 'name' => 'PayPal',),
            array('id_option' => 'bitcoin', 'name' => 'Bitcoin',),
            array('id_option' => 'sofortueberweisung_de', 'name' => 'Sofort Ueberweisung',),
            array('id_option' => 'airplus', 'name' => 'Airplus',),
            array('id_option' => 'billpay', 'name' => 'Billpay',),
            array('id_option' => 'bonuscard', 'name' => 'Bonus card',),
            array('id_option' => 'cashu', 'name' => 'CashU',),
            array('id_option' => 'cb', 'name' => 'Carte Bleue',),
            array('id_option' => 'diners_club', 'name' => 'Diners Club',),
            array('id_option' => 'direct_debit', 'name' => 'Direct Debit',),
            array('id_option' => 'discover', 'name' => 'Discover',),
            array('id_option' => 'elv', 'name' => 'ELV',),
            array('id_option' => 'ideal', 'name' => 'iDEAL',),
            array('id_option' => 'invoice', 'name' => 'Invoice',),
            array('id_option' => 'myone', 'name' => 'My One',),
            array('id_option' => 'paysafecard', 'name' => 'Paysafe Card',),
            array('id_option' => 'postfinance_card', 'name' => 'PostFinance Card',),
            array('id_option' => 'postfinance_efinance', 'name' => 'PostFinance E-Finance',),
            array('id_option' => 'swissbilling', 'name' => 'SwissBilling',),
            array('id_option' => 'twint', 'name' => 'TWINT'),
            array('id_option' => 'barzahlen', 'name' => 'Barzahlen/Viacash'),
            array('id_option' => 'bancontact', 'name' => 'Bancontact'),
            array('id_option' => 'giropay', 'name' => 'GiroPay'),
            array('id_option' => 'eps', 'name' => 'EPS'),
            array('id_option' => 'google_pay', 'name' => 'Google Pay'),
            array('id_option' => 'wechat_pay', 'name' => 'WeChat Pay'),
            array('id_option' => 'alipay', 'name' => 'Alipay'),
        );

        $platforms = [
            [
                'url' => 'payrexx.com',
                'name' => 'Payrexx',
            ],
            [
                'url' => 'shop-and-pay.com',
                'name' => 'Shop and Pay',
            ],
            [
                'url' => 'ideal-pay.ch',
                'name' => 'Ideal Pay',
            ],
            [
                'url' => 'zahls.ch',
                'name' => 'zahls.ch',
            ],
        ];

        $fields_form = [];
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->l('Payment Icons'),
                    'name' => 'payrexx_platform',
                    'multiple' => false,
                    'options' => [
                        'query' => $platforms,
                        'id' => 'url',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('API Secret'),
                    'name' => 'payrexx_api_secret',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('INSTANCE NAME') .
                        "<br /><small style='color:#00f; font-weight:normal'>
                            (INSTANCE NAME is a part of the url where you access your payrexx installation. 
                            https://INSTANCE.payrexx.com)</small>",
                    'name' => 'payrexx_instance_name',
                    'required' => true
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Payment Icons'),
                    'name' => 'payrexx_pay_icons',
                    'multiple' => true,
                    'options' => [
                        'query' => $paymentMethods,
                        'id' => 'id_option',
                        'name' => 'name',
                    ]
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ],
        ];

        $fields_value = array(
            'payrexx_label' => $this->l('Payrexx payment method title'),
            'payrexx_description' => $this->l('Payrexx payment method description'),
            'payrexx_platform' => Configuration::get('PAYREXX_PLATFORM'),
            'payrexx_api_secret' => Configuration::get('PAYREXX_API_SECRET'),
            'payrexx_instance_name' => Configuration::get('PAYREXX_INSTANCE_NAME'),
            'payrexx_pay_icons[]' => unserialize(Configuration::get('PAYREXX_PAY_ICONS')),
        );
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->submit_action = 'payrexx_config';
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->tpl_vars = array(
            'fields_value' => $fields_value,
            'id_language' => $this->context->language->id,
            'back_url' => $this->context->link->getAdminLink('AdminModules')
                . '&configure=' . $this->name
                . '&tab_module=' . $this->tab
                . '&module_name=' . $this->name
                . '#paypal_params'
        );
        $form = $helper->generateForm($fields_form);

        return $form;
    }

    private function postProcess()
    {
        if (Tools::isSubmit('payrexx_config')) {
            Configuration::updateValue('PAYREXX_PLATFORM', Tools::getValue('payrexx_platform'));
            Configuration::updateValue('PAYREXX_API_SECRET', Tools::getValue('payrexx_api_secret'));
            Configuration::updateValue('PAYREXX_INSTANCE_NAME', Tools::getValue('payrexx_instance_name'));
            Configuration::updateValue('PAYREXX_PAY_ICONS', serialize(Tools::getValue('payrexx_pay_icons')));
        }
    }

    // Payment hook for version < 1.7
    public function hookPaymentReturn($params)
    {
        // By default Prestashop v1.6 will display the order confirmation message for the guest users
        if ($this->context->customer->is_guest) {
            return;
        }

        $invoice_url = null;
        if ($params['objOrder'] && !empty($params['objOrder']->id)) {
            $invoice_url = $this->context->link->getPageLink(
                'pdf-invoice',
                true,
                $this->context->language->id,
                "id_order={$params['objOrder']->id}"
            );
        }
        $customer_email = null;
        if ($params['cart'] && !empty($params['cart']->id_customer)) {
            $customer = new Customer($params['cart']->id_customer);
            $customer_email = $customer->email;
        }
        $this->smarty->assign(array(
            'invoice_url' => $invoice_url,
            'customer_email' => $customer_email,
        ));
        return $this->display(__FILE__, 'confirmation.tpl');
    }

    // Payment hook for version < 1.7
    public function hookPayment($params)
    {
        $action_text = $this->l('Payrexx payment method title');
        $this->smarty->assign(array(
            'payrexx_url' => $this->context->link->getModuleLink($this->name, 'payrexx'),
            'image_path' => $this->_path,
            'title' => $action_text,
        ));
        return $this->display(__FILE__, 'payrexx_payment.tpl');
    }

    // Payment hook for version >= 1.7
    public function hookPaymentOptions($params)
    {
        $payIconSource = unserialize(Configuration::get('PAYREXX_PAY_ICONS'));

        $payment_option = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $action_text = $this->l('Payrexx payment method title');
        $this->context->smarty->assign(array(
            'path' => $this->_path,
        ));
        $payment_option->setCallToActionText($action_text);
        $payment_option->setAction($this->context->link->getModuleLink($this->name, 'payrexx'));

        $payIcons = '';
        if ($payIconSource) {
            foreach ((array)$payIconSource as $iconSource) {
                $payIcons .=
                    '<img style="width: 50px" src="' . $this->_path . 'views/img/cardicons/card_' . $iconSource . '.svg" />';
            }

            $payIcons = '<div class="payrexxPayIcons">' . $payIcons . '</div>';
        }

        $payment_option->setAdditionalInformation($this->l('Payrexx payment method description') . $payIcons);

        $payment_options = array(
            $payment_option,
        );

        return $payment_options;
    }
}
