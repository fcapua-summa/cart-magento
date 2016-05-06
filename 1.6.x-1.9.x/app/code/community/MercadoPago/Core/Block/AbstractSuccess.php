<?php

class MercadoPago_Core_Block_AbstractSuccess
    extends Mage_Core_Block_Template
{

    public function getPayment()
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();

        return $payment;
    }

    public function getOrder()
    {
        $orderIncrementId = $this->getOrderIncrementId();
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);

        return $order;
    }

    public function getTotal()
    {
        $order = $this->getOrder();
        $total = $order->getBaseGrandTotal();

        if (!$total) {
            $total = $order->getBasePrice() + $order->getBaseShippingAmount();
        }

        $total = number_format($total, 2, '.', '');

        return $total;
    }

    public function getEntityId()
    {
        $order = $this->getOrder();

        return $order->getEntityId();
    }

    public function getOrderIncrementId()
    {
        return Mage::getSingleton('checkout/session')->getLastRealOrderId();
    }


    public function getPaymentMethod()
    {
        $payment_method = $this->getPayment()->getMethodInstance()->getCode();

        return $payment_method;
    }

    public function getInfoPayment()
    {
        $orderIncrementId = $this->getOrderIncrementId();
        /** @var MercadoPago_Core_Model_Core $mpModel */
        $mpModel = Mage::getModel('mercadopago/core');
        $infoPayments = $mpModel->getInfoPaymentByOrder($orderIncrementId);

        return $infoPayments;
    }

    public function getMessageByStatus($status, $status_detail, $payment_method, $amount, $installment)
    {
        /** @var MercadoPago_Core_Model_Core $mpCore */
        $mpCore = Mage::getModel('mercadopago/core');
        return $mpCore->getMessageByStatus($status, $status_detail, $payment_method, $amount, $installment);
    }
}
