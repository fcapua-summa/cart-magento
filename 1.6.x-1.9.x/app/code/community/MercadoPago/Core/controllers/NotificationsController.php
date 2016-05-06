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
class MercadoPago_Core_NotificationsController
    extends Mage_Core_Controller_Front_Action
{
    /**
     * @param MercadoPago_Core_Model_IpnInterface $ipnModel
     * @throws Zend_Controller_Response_Exception
     */
    protected function _processRequest($ipnModel)
    {
        $data = $this->getRequest()->getParams();

        try {
            $result = $ipnModel->processResponse($data);

            $this->getResponse()->setBody($result['text']);
            $this->getResponse()->setHttpResponseCode($result['code']);
        } catch (MercadoPago_Core_Model_Exception_InvalidRequest $e) {
            Mage::helper('mercadopago')->log("Merchant Order not found", MercadoPago_Core_Model_IpnInterface::LOG_FILE, $data);
            $this->getResponse()->getBody("Merchant Order not found");
            $this->getResponse()->setHttpResponseCode(MercadoPago_Core_Helper_Response::HTTP_NOT_FOUND);
            Mage::helper('mercadopago')->log("Http code", MercadoPago_Core_Model_IpnInterface::LOG_FILE, $this->getResponse()->getHttpResponseCode());
        } catch (Exception $e) {
            Mage::helper('mercadopago')->log("Unexpected error", MercadoPago_Core_Model_IpnInterface::LOG_FILE, $data);
            $this->getResponse()->getBody("Unexpected error");
            $this->getResponse()->setHttpResponseCode(MercadoPago_Core_Helper_Response::HTTP_BAD_REQUEST);
        }

        Mage::helper('mercadopago')->log("Http code", MercadoPago_Core_Model_IpnInterface::LOG_FILE, $this->getResponse()->getHttpResponseCode());
    }

    public function standardAction()
    {
        /** @var MercadoPago_Core_Model_Standard_Ipn $ipnModel */
        $ipnModel = Mage::getModel('mercadopago/standard_ipn');

        $this->_processRequest($ipnModel);
    }


    public function customAction()
    {
        /** @var MercadoPago_Core_Model_Custom_Ipn $ipnModel */
        $ipnModel = Mage::getModel('mercadopago/custom_ipn');

        $this->_processRequest($ipnModel);
    }

}
