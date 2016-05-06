<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL).
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category       Payment Gateway
 * @package        MercadoPago
 * @author         Gabriel Matsuoka (gabriel.matsuoka@gmail.com)
 * @copyright      Copyright (c) MercadoPago [http://www.mercadopago.com]
 * @license        http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MercadoPago_Core_ApiController
    extends Mage_Core_Controller_Front_Action
{
    // action: /mercadopago/api/amount

    public function amountAction()
    {
        $core = Mage::getModel('mercadopago/core');

        $response = array(
            "amount" => $core->getAmount()
        );

        $jsonData = Mage::helper('core')->jsonEncode($response);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }

    // action: /mercadopago/api/cupom?id=:cupom_id

    public function couponAction()
    {
        /** @var MercadoPago_Core_Model_Core $mpCore */
        $mpCore = Mage::getModel('mercadopago/core');

        $couponId = $this->getRequest()->getParam('id');
        if (!empty($couponId)) {
            $response = $mpCore->validCoupon($couponId);
        } else {
            $response = [
                "status"   => 400,
                "response" => [
                    "error"   => "invalid_id",
                    "message" => "invalid id"
                ]
            ];
        }

        $jsonData = Mage::helper('core')->jsonEncode($response);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }


    /*
     *
     * Test Request
     *
     */

    public function testAction()
    {
        $mpCore = Mage::getModel('mercadopago/core');

        $paymentMethods = $mpCore->getPaymentMethods();

        $response = [
            "getPaymentMethods" => $paymentMethods['status'],
            "public_key"        => Mage::getStoreConfig('payment/mercadopago_custom/public_key')
        ];

        $jsonData = Mage::helper('core')->jsonEncode($response);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }
}
