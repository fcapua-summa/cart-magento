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


class MercadoPago_Core_Block_Standard_Info
    extends Mage_Payment_Block_Info_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('mercadopago/standard/info.phtml');
    }


    public function getOrder()
    {
        return $this->getInfo();
    }

    public function getInfoPayment()
    {
        $orderId = $this->getInfo()->getOrder()->getIncrementId();
        /** @var MercadoPago_Core_Model_Core $mpModel */
        $mpModel = Mage::getModel('mercadopago/core');
        $infoPayments = $mpModel->getInfoPaymentByOrder($orderId);

        return $infoPayments;
    }
}
