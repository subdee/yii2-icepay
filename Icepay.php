<?php

namespace subdee\icepay;


use yii\base\Component;

/**
 * Icepay provides a payment component for the Yii2 framework in order to initialise and complete payments using your
 * Icepay account.
 *
 * To use Icepay, you should configure it in the application configuration like the following,
 *
 * ~~~
 * 'components' => [
 * ...
 * 'icepay' => [
 * 'class' => 'subdee\icepay\Icepay',
 * 'merchantID' => 'merchant id',
 * 'secretCode' => 'secretCode',
 * ],
 * ...
 * ],
 * ~~~
 *
 * By default, Icepay will take the application configuration for the language, country and currency properties.
 * It uses the \Yii::$app->lanaguage and \Yii::$app->formatter->currencyCode properties to determine default values.
 * To override the default values, you can specify those properties separately either in the config file:
 *
 * ~~~
 * 'components' => [
 * ...
 * 'icepay' => [
 * 'class' => 'subdee\icepay\Icepay',
 * 'merchantID' => 'merchant id',
 * 'secretCode' => 'secretCode',
 * 'language' => 'EN',
 * 'country' => 'GB',
 * 'currency' => 'GBP'
 * ],
 * ...
 * ],
 * ~~~
 *
 * or by setting the property in your code:
 *
 * ~~~
 * \Yii::$app->icepay->language = 'EN'
 * ~~~
 *
 * @see http://www.icepay.com
 *
 * @author Kostas Thermos <info@subdee.org>
 */
class Icepay extends Component
{
    public $merchantID;
    public $secretCode;
    public $language;
    public $country;
    public $currency;

    /**
     * Initialize application default values if not set
     */
    public function init()
    {
        if (!$this->language || !$this->country || !$this->currency) {
            list($language, $country) = explode('-', \Yii::$app->language);
            if (!$this->language) {
                $this->language = strtoupper($language);
            }
            if (!$this->country) {
                $this->country = strtoupper($country);
            }
            if (!$this->currency) {
                $this->currency = strtoupper(\Yii::$app->formatter->currencyCode);
            }
        }
    }

    /**
     * Get an array of payment methods
     * @return array The payment methods available for this merchant
     * @throws \Exception
     */
    public function getPaymentMethods()
    {
        try {
            $ideal = new \Icepay_Webservice_Paymentmethods();
            return $ideal
                ->setMerchantID($this->merchantID)
                ->setSecretCode($this->secretCode)
                ->retrieveAllPaymentmethods()
                ->asArray();
        } catch (\Exception $e) {
            \Yii::error($e->getMessage());
        }
        return [];
    }

    /**
     * Create a new payment with Icepay
     * @param string $method The method used for the payment as returned from the list of payments
     * @param string $amount The amount of the payment
     * @param string $orderID The order ID
     * @param string|null $description A description of the payment
     * @param string|null $issuer The issuer of the payment method, if applicable
     * @param string|null $reference A reference for the payment
     * @return bool|string The URL to redirect for the payment if successful, false if unsuccessful
     * @throws \Exception
     */
    public function createPayment($method, $amount, $orderID, $description = null, $issuer = null, $reference = null)
    {
        $methodName = '\Icepay_Paymentmethod_' . ucfirst(strtolower($method));
        $paymentMethod = new $methodName();
        $payment = new \Icepay_PaymentObject();
        $payment->setPaymentMethod($paymentMethod->getCode())
            ->setAmount($amount)
            ->setOrderID($orderID)
            ->setDescription($description)
            ->setReference($reference)
            ->setLanguage($this->language)
            ->setCountry($this->country)
            ->setCurrency($this->currency)
            ->setIssuer($issuer);

        $basicMode = \Icepay_Basicmode::getInstance();
        $basicMode->setMerchantId($this->merchantID)
            ->setSecretCode($this->secretCode)
            ->setProtocol('https')
            ->validatePayment($payment);

        try {
            return $basicMode->getURL();
        } catch (\Exception $e) {
            \Yii::error($e->getMessage());
        }
        return false;
    }

    /**
     * Do the postback validation and finish payment
     * @return bool|\Icepay_Postback
     */
    public function doPostback()
    {
        try {
            $postback = new \Icepay_Postback();
            $postback->setMerchantID($this->merchantID)
                ->setSecretCode($this->secretCode)
                ->doIPCheck();

            if ($postback->validate() && $postback->getStatus() == \Icepay_StatusCode::SUCCESS) {
                return $postback;
            }
        } catch (\Exception $e) {
            \Yii::error($e->getMessage());
        }

        return false;
    }

}
