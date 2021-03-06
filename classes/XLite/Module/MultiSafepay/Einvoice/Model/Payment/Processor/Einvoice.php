<?php

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <techsupport@multisafepay.com>
 * @copyright   Copyright (c) 2017 MultiSafepay, Inc. (http://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace XLite\Module\MultiSafepay\Einvoice\Model\Payment\Processor;

use XLite\Module\MultiSafepay\Connect\Model\Cart;
use XLite\Module\MultiSafepay\Connect\Model\Payment\Processor\Connect;
use XLite\Module\MultiSafepay\Connect\Model\Payment\Refund;
use XLite\Module\MultiSafepay\Connect\Model\Tax;

class Einvoice extends \XLite\Model\Payment\Base\WebBased
{

    public $settings = 'MultiSafepay Connect';
    public $icon = 'msp_einvoice.png';
    public $gateway = 'Einvoice';

    /**
     * Get operation types
     *
     * @return array
     */
    public function getOperationTypes()
    {
        return array(
            self::OPERATION_SALE,
        );
    }

    /**
     * Get allowed backend transactions
     *
     * @return string Status code
     */
    public function getAllowedTransactions()
    {
        return [];
    }

    /**
     * Get settings widget or template
     *
     * @return string Widget class name or template path
     */
    public function getSettingsWidget()
    {
        return 'modules/MultiSafepay/Einvoice/config.twig';
    }

    /**
     * Process return
     *
     * @param \XLite\Model\Payment\Transaction $transaction Return-owner transaction
     *
     * @return void
     */
    public function processReturn(\XLite\Model\Payment\Transaction $transaction)
    {
        parent::processReturn($transaction);
        $processor = new \XLite\Module\MultiSafepay\Connect\Model\Payment\Processor\Connect();
        $processor->processReturn($transaction);
    }

    /**
     * Get initial transaction type (used when customer places order)
     *
     * @param \XLite\Model\Payment\Method $method Payment method object OPTIONAL
     *
     * @return string
     */
    public function getInitialTransactionType($method = null)
    {
        return \XLite\Model\Payment\BackendTransaction::TRAN_TYPE_SALE;
    }

    /**
     * Check - payment method is configured or not
     *
     * @param \XLite\Model\Payment\Method $method Payment method
     *
     * @return boolean
     */
    public function isConfigured(\XLite\Model\Payment\Method $method)
    {
        return parent::isConfigured($method) && $this->isAllSettingsProvided($method);
    }

    /**
     * Check - payment method is configured or not
     *
     * @param \XLite\Model\Payment\Method $method Payment method
     *
     * @return boolean
     */
    public function isAllSettingsProvided(\XLite\Model\Payment\Method $method)
    {
        return $method->getSetting('prefix');
    }

    /**
     * Get return type
     *
     * @return string
     */
    public function getReturnType()
    {
        return self::RETURN_TYPE_HTTP_REDIRECT;
    }

    /**
     * Returns the list of settings available for this payment processor
     *
     * @return array
     */
    public function getAvailableSettings()
    {
        return array(
            'prefix'
        );
    }

    /**
     * Get payment method admin zone icon URL
     *
     * @param \XLite\Model\Payment\Method $method Payment method
     *
     * @return string
     */
    public function getAdminIconURL(\XLite\Model\Payment\Method $method)
    {
        return true;
    }

    /**
     * Check - payment method has enabled test mode or not
     *
     * @param \XLite\Model\Payment\Method $method Payment method
     *
     * @return boolean
     */
    public function isTestMode(\XLite\Model\Payment\Method $method)
    {
        return '0' == $this->getSetting('Test');
    }

    /**
     *
     * @param type $transid
     * @param type $settings
     * @param type $gateway
     */
    public function startEinvoice($transid, $settings = 'MultiSafepay Connect', $gateway = "EINVOICE")
    {
        require_once LC_DIR_MODULES . 'MultiSafepay' . LC_DS . 'API' . LC_DS . 'Autoloader.php';

        if (!$this->transaction && $transid) {
            $this->transaction = \XLite\Core\Database::getRepo('XLite\Model\Payment\Transaction')->findOneByPublicTxnId($transid);
        }

        if ($this->transaction) {
            $orderId = $this->transaction->getPublicTxnId();
            $settings = $this->getPaymentSettings($this->settings);

            $items_list = '<ul>';
            foreach ($this->getOrder()->getItems() as $item) {
                $product = $item->getProduct();
                $items_list.= '<li>' . $item->getAmount() . ' x ' . $product->getName() . '</li>';
            }
            $items_list .= '</ul>';

            $trans_type = "redirect";

            if ($this->getSetting('transaction_type') == '1') {
                $trans_type = "direct";
                $birthday_input = $this->formatDob($_POST['dob']);
                $bankaccount_input = $_POST['bankaccount'];
            } else {
                $birthday_input = null;
                $bankaccount_input = null;
            }

            try {
                $msp = new \MultiSafepayAPI\Client();
                $msp->setApiKey($this->getSetting('api_key'));
                $msp->setApiUrl($this->getEnvironment());
                list($billing_street, $billing_housenumber) = $this->parseAddress($this->getProfile()->getBillingAddress()->getStreet());
                list($shipping_street, $shipping_housenumber) = $this->parseAddress($this->getProfile()->getShippingAddress()->getStreet());

                $msp->orders->post(array(
                    "type" => $trans_type,
                    "order_id" => $orderId,
                    "currency" => strtoupper($this->getOrder()->getCurrency()->getCode()),
                    "amount" => $this->getOrder()->getCurrency()->roundValue($this->transaction->getValue()) * 100,
                    "gateway" => $gateway,
                    "description" => $this->getInvoiceDescription(),
                    "var1" => null,
                    "var2" => null,
                    "var3" => null,
                    "items" => $items_list,
                    "manual" => null,
                    "days_active" => $this->getSetting('days_active'),
                    "payment_options" => array(
                        "notification_url" => $this->getReturnURL(null, true) . "&type=initial",
                        "redirect_url" => $this->getReturnURL(null, true) . '&redirect=true',
                        "cancel_url" => \XLite::getInstance()->getShopURL(\XLite\Core\Converter::buildURL('checkout'), \XLite\Core\Config::getInstance()->Security->customer_security
                        ),
                        "close_window" => false
                    ),
                    "customer" => array(
                        "locale"        =>  $this->getLocaleFromLanguageCode(strtolower(\XLite\Core\Session::getInstance()->getLanguage()->getCode())),
                        "ip_address" => $this->getClientIP(),
                        "forwarded_ip" => $_SERVER['HTTP_X_FORWARDED_FOR'],
                        "first_name" => $this->getProfile()->getBillingAddress()->getFirstname(),
                        "last_name" => $this->getProfile()->getBillingAddress()->getLastname(),
                        "address1" => $billing_street,
                        "address2" => null,
                        "house_number" => $billing_housenumber,
                        "zip_code" => $this->getProfile()->getBillingAddress()->getZipcode(),
                        "city" => $this->getProfile()->getBillingAddress()->getCity(),
                        "state" => null,
                        "country" => strtoupper($this->getProfile()->getBillingAddress()->getCountry()->getCode()),
                        "phone" => $this->getProfile()->getBillingAddress()->getPhone(),
                        "email" => $this->getProfile()->getLogin(),
                        "disable_send_email" => false,
                        "user_agent" => $_SERVER['HTTP_USER_AGENT'],
                        "referrer" => $_SERVER['HTTP_REFERER']
                    ),
                    "delivery" => array(
                        "first_name" => $this->getProfile()->getShippingAddress()->getFirstname(),
                        "last_name" => $this->getProfile()->getShippingAddress()->getLastname(),
                        "address1" => $shipping_street,
                        "address2" => null,
                        "house_number" => $shipping_housenumber,
                        "zip_code" => $this->getProfile()->getShippingAddress()->getZipcode(),
                        "city" => $this->getProfile()->getShippingAddress()->getCity(),
                        "state" => null,
                        "country" => strtoupper($this->getProfile()->getShippingAddress()->getCountry()->getCode()),
                        "phone" => $this->getProfile()->getShippingAddress()->getPhone(),
                        "email" => $this->getProfile()->getLogin()
                    ),
                    "shopping_cart" => Cart::getShoppingCart($this->getorder()),
                    "checkout_options" => Tax::getCheckoutOptions(),
                    "gateway_info" => array(
                        "birthday" => $birthday_input,
                        "bank_account" => $bankaccount_input,
                        "phone" => $this->getProfile()->getShippingAddress()->getPhone(),
                        "email" => $this->getProfile()->getLogin(),
                        "gender" => null,
                        "referrer" => $_SERVER['HTTP_REFERER'],
                        "user_agent" => $_SERVER['HTTP_USER_AGENT']
                    ),
                    "google_analytics" => array(
                        "account" => $this->getSetting('ga_accountid')
                    ),
                    "plugin" => array(
                        "shop" => "X-Cart",
                        "plugin_version" => Connect::getPluginVersion(),
                        "shop_version" => \XLite\Core\Config::getInstance()->Version->version,
                        "partner" => null,
                        "shop_root_url" => null
                    )
                ));

                header('Location: ' . $msp->orders->getPaymentLink());
                exit;
            } catch (Exception $e) {
                \XLite\Core\TopMessage::addError("Error " .$e->getMessage());
                return  false;
            }
        }
    }

    /**
     * Convert language_code to locale
     *
     * @param type $language_code
     * @return type
     */

    public function getLocaleFromLanguageCode($language_code)
    {
        $locale_array = array
        (
            'nl' => 'nl_NL',
            'en' => 'en_GB',
            'fr' => 'fr_FR',
            'es' => 'es_ES',
            'de' => 'de_DE',
            'it' => 'it_IT',
            'sv' => 'sv_SE',
            'tr' => 'tr_TR',
            'cs' => 'cs_CZ',
            'pl' => 'pl_PL',
            'pt' => 'pt_PT',
            'he' => 'he_IL',
            'ru' => 'ru_RU',
            'ar' => 'ar_AR',
            'cn' => 'zh_CN',
            'ro' => 'ro_RO',
            'da' => 'da_DK',
            'fi' => 'fi_FI',
            'no' => 'no_NO'
        );

        if (array_key_exists($language_code, $locale_array)) {
            return $locale_array[$language_code];
        } else {
            return null;
        }
    }

    /**
     *
     * @return string
     */
    protected function getEnvironment()
    {
        if ($this->getSetting('account_type') == '1') {
            return "https://api.multisafepay.com/v1/json/";
        } else {
            return "https://testapi.multisafepay.com/v1/json/";
        }
    }

    /**
     * Get array of payment settings
     *
     * @return array
     */
    public function getPaymentSettings()
    {
        $result = array();

        $fields = $this->getAvailableSettings();
        foreach ($fields as $field) {
            $result[$field] = $this->getSetting($field);
        }

        return $result;
    }

    /**
     * Get payment method setting by name
     *
     * @param string $name Setting name
     *
     * @result string
     */
    protected function getSetting($name)
    {
        $result = parent::getSetting($name);

        if (is_null($result)) {
            $method = \XLite\Core\Database::getRepo('XLite\Model\Payment\Method')->findOneBy(array('service_name' => $this->settings));
            $result = $method ? $method->getSetting($name) : null;
        }

        return $result;
    }

    /**
     * Get redirect form URL
     *
     * @return string
     */
    protected function getFormURL()
    {
        return \XLite\Core\Converter::buildURL('Einvoice', 'transaction');
    }

    /**
     * Get redirect form fields list
     *
     * @return array
     */
    protected function getFormFields()
    {
        $data = \XLite\Core\Request::getInstance()->getData();

        return array(
            'bankaccount' => $data['payment']['bankaccount'],
            'dob' => $data['payment']['dob'],
            'transid' => $this->transaction->getPublicTxnId(),
            'returnURL' => $this->getReturnURL(null, true)
        );
    }

    /**
     *
     * @return string
     */
    public function getInputTemplate()
    {
        if ($this->getSetting('transaction_type') == '1') {
            return 'modules/MultiSafepay/Einvoice/checkout/einvoice.twig';
        }
    }

    /**
     * Process input errors
     *
     * @param array $data Input data
     *
     * @return array
     */
    public function getInputErrors(array $data)
    {
        if ($this->getSetting('transaction_type') == '1') {
            $errors = parent::getInputErrors($data);

            foreach ($this->getInputDataLabels() as $k => $t) {
                if (!isset($data[$k]) || !$data[$k]) {
                    $errors[] = \XLite\Core\Translation::lbl('X field is required', array('field' => $t));
                }
            }

            return $errors;
        }
    }

    /**
     * Get input data access levels list
     *
     * @return array
     */
    protected function getInputDataAccessLevels()
    {
        return array(
            'bankaccount' => \XLite\Model\Payment\TransactionData::ACCESS_CUSTOMER,
            'dob' => \XLite\Model\Payment\TransactionData::ACCESS_CUSTOMER,
        );
    }

    /**
     *
     * @param \XLite\Model\Order $order
     * @param \XLite\Model\Payment\Method $method
     * @return type
     */
    public function getIconPath(\XLite\Model\Order $order = null, \XLite\Model\Payment\Method $method = null)
    {
        $processor = new \XLite\Module\MultiSafepay\Connect\Model\Payment\Processor\Connect();
        $processor->gateway = $this->gateway;
        $processor->icon = $this->icon;

        return $processor->getIconPath($order, $method);
    }

    /**
     *
     * @param \XLite\Model\Payment\Method $method
     * @return string
     */
    public function getCheckoutTemplate(\XLite\Model\Payment\Method $method)
    {
        return 'modules/MultiSafepay/Connect/checkout/gateway.twig';
    }

    /**
     *
     * @param type $street_address
     * @return type
     */
    public function parseAddress($street_address)
    {
        $address = $street_address;
        $apartment = "";

        $offset = strlen($street_address);

        while (($offset = $this->rstrpos($street_address, ' ', $offset)) !== false) {
            if ($offset < strlen($street_address) - 1 && is_numeric($street_address[$offset + 1])) {
                $address = trim(substr($street_address, 0, $offset));
                $apartment = trim(substr($street_address, $offset + 1));
                break;
            }
        }

        if (empty($apartment) && strlen($street_address) > 0 && is_numeric($street_address[0])) {
            $pos = strpos($street_address, ' ');

            if ($pos !== false) {
                $apartment = trim(substr($street_address, 0, $pos), ", \t\n\r\0\x0B");
                $address = trim(substr($street_address, $pos + 1));
            }
        }

        return array($address, $apartment);
    }

    /**
     *
     * @param type $haystack
     * @param type $needle
     * @param type $offset
     * @return boolean
     */
    public function rstrpos($haystack, $needle, $offset = null)
    {
        $size = strlen($haystack);

        if (is_null($offset)) {
            $offset = $size;
        }

        $pos = strpos(strrev($haystack), strrev($needle), $size - $offset);

        if ($pos === false) {
            return false;
        }

        return $size - $pos - strlen($needle);
    }

    /**
     *
     * @param type $dob
     * @return type
     */
    public function formatDob($dob)
    {
        try {
            $dt = new \DateTime();
            $format = $dt->createFromFormat('d-m-Y', $dob);
            if ($format) {
                return $format->format('Y-m-d');
            } else {
                return null;
            }
        } catch (Exception $e) {
            \XLite\Core\TopMessage::addError("Error " .$e->getMessage());
            return  false;
        }
    }

    /**
     * @param \XLite\Model\Payment\BackendTransaction $transaction
     * @return bool
     */
    protected function doRefund(\XLite\Model\Payment\BackendTransaction $transaction)
    {
        return Refund::complexRefund($transaction);
    }

}
