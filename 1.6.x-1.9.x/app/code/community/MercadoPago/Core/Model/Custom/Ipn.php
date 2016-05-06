<?php

/**
 * Class MercadoPago_Core_Model_Custom_Ipn
 */
class MercadoPago_Core_Model_Custom_Ipn
    implements MercadoPago_Core_Model_IpnInterface
{
    public function validateData($data)
    {
        if(empty($data['data_id'])){
            throw new MercadoPago_Core_Model_Exception_InvalidRequest('Data Id is missing');
        }
        
        if($data['type'] != 'payment'){
            throw new MercadoPago_Core_Model_Exception_InvalidRequest(sprintf('Invalid type, expected "type", actual %s', $data['type']));
        }

        return true;
    }

    public function processResponse($data)
    {
        $this->getHelper()->log("Custom Received notification", self::LOG_FILE, $data);

        $this->validateData($data);

        $dataId = $data['data_id'];
        /** @var MercadoPago_Core_Model_Core $mpCore */
        $mpCore = Mage::getModel('mercadopago/core');
        $paymentData = $mpCore->getPaymentV1($dataId);

        $response = $this->processPaymentData($paymentData);

        return $response;
    }

    public function processPaymentData($data)
    {
        $this->getHelper()->log("Return payment", self::LOG_FILE, $data);

        if (
            $data['status'] != MercadoPago_Core_Helper_Response::HTTP_OK
            && $data['status'] != MercadoPago_Core_Helper_Response::HTTP_CREATED
        ) {
            throw new MercadoPago_Core_Model_Exception_InvalidRequest('Can not retrieve payment information');
        }

        $payment = $data['response'];
        $payment = $this->getHelper()->setPayerInfo($payment);

        $this->getHelper()->log("Update Order", self::LOG_FILE);
        $this->getHelper()->setStatusUpdated($payment);

        /** @var MercadoPago_Core_Model_Core $mpCore */
        $mpCore = Mage::getModel('mercadopago/core');
        $mpCore->updateOrder($payment);
        $response = $mpCore->setStatusOrder($payment);

        return $response;
    }

    /**
     * @return MercadoPago_Core_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('mercadopago');
    }
}