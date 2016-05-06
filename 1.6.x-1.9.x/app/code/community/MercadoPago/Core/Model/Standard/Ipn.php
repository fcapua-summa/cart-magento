<?php

/**
 * Class MercadoPago_Core_Model_Standard_Ipn
 */
class MercadoPago_Core_Model_Standard_Ipn
    implements MercadoPago_Core_Model_IpnInterface
{
    const LOG_FILE = 'mercadopago-notification.log';

    protected $_finalStatus = ['rejected', 'cancelled', 'refunded', 'charge_back'];
    protected $_notFinalStatus = ['authorized', 'process', 'in_mediation'];

    protected function _getDataPayments($merchantOrder)
    {
        $data = [];
        $core = Mage::getModel('mercadopago/core');
        foreach ($merchantOrder['payments'] as $payment) {
            $response = $core->getPayment($payment['id']);
            $payment = $response['response']['collection'];
            $data = $this->_formatArrayPayment($data, $payment);
        }
        return $data;
    }

    protected function _dateCompare($a, $b)
    {
        $t1 = strtotime($a['value']);
        $t2 = strtotime($b['value']);
        return $t2 - $t1;
    }

    /**
     * @param $payments
     * @param $status
     *
     * @return int
     */
    protected function _getLastPaymentIndex($payments, $status)
    {
        $dates = [];
        foreach ($payments as $key => $payment) {
            if (in_array($payment['status'], $status)) {
                $dates[] = ['key' => $key, 'value' => $payment['last_modified']];
            }
        }
        usort($dates, [get_class($this), "_dateCompare"]);
        if ($dates) {
            $lastModified = array_pop($dates);
            return $lastModified['key'];
        }
        return 0;
    }

    protected function _formatArrayPayment($data, $payment)
    {
        $this->getHelper()->log("Format Array", self::LOG_FILE);

        $fields = [
            "status",
            "status_detail",
            "id",
            "payment_method_id",
            "transaction_amount",
            "total_paid_amount",
            "coupon_amount",
            "installments",
            "shipping_cost",
            "amount_refunded",
        ];

        foreach ($fields as $field) {
            if (isset($payment[$field])) {
                if (isset($data[$field])) {
                    $data[$field] .= " | " . $payment[$field];
                } else {
                    $data[$field] = $payment[$field];
                }
            }
        }

        if (isset($payment["last_four_digits"])) {
            if (isset($data["trunc_card"])) {
                $data["trunc_card"] .= " | " . "xxxx xxxx xxxx " . $payment["last_four_digits"];
            } else {
                $data["trunc_card"] = "xxxx xxxx xxxx " . $payment["last_four_digits"];
            }
        }

        if (isset($payment['cardholder']['name'])) {
            if (isset($data["cardholder_name"])) {
                $data["cardholder_name"] .= " | " . $payment["cardholder"]["name"];
            } else {
                $data["cardholder_name"] = $payment["cardholder"]["name"];
            }
        }

        if (isset($payment['statement_descriptor'])) {
            $data['statement_descriptor'] = $payment['statement_descriptor'];
        }

        $data['external_reference'] = $payment['external_reference'];
        $data['payer_first_name'] = $payment['payer']['first_name'];
        $data['payer_last_name'] = $payment['payer']['last_name'];
        $data['payer_email'] = $payment['payer']['email'];

        return $data;
    }

    protected function _getStatusFinal($dataStatus)
    {
        if ($dataStatus['total_amount'] == $dataStatus['paid_amount']) {
            return 'approved';
        }
        $payments = $dataStatus['payments'];
        $statuses = explode('|', $dataStatus);
        foreach ($statuses as $status) {
            $status = str_replace(' ', '', $status);
            if (in_array($status, $this->_notFinalStatus)) {
                $lastPaymentIndex = $this->_getLastPaymentIndex($payments, $this->_notFinalStatus);
                return $payments[$lastPaymentIndex]['status'];
            }
        }
        $lastPaymentIndex = $this->_getLastPaymentIndex($payments, $this->_finalStatus);
        return $payments[$lastPaymentIndex]['status'];
    }

    public function validateData($data)
    {
        if (empty($data['id'])) {
            throw new MercadoPago_Core_Model_Exception_InvalidRequest('Id is missing');
        }

        if ($data['topic'] != 'merchant_order') {
            throw new MercadoPago_Core_Model_Exception_InvalidRequest(sprintf('Invalid topic, expected "merchant_order", actual %s',
                $data['type']));
        }

        return true;
    }

    public function processResponse($data)
    {
        $this->getHelper()->log("Standard Received notification", self::LOG_FILE, $data);

        $this->validateData($data);

        $id = $data['id'];
        /** @var MercadoPago_Core_Model_Core $mpCore */
        $mpCore = Mage::getModel('mercadopago/core');
        $paymentData = $mpCore->getMerchantOrder($id);

        $response = $this->processPaymentData($paymentData);

        return $response;
    }

    public function processPaymentData($data)
    {
        $this->getHelper()->log("Return merchant_order", self::LOG_FILE, $data);

        if (
            $data['status'] != MercadoPago_Core_Helper_Response::HTTP_OK
            && $data['status'] != MercadoPago_Core_Helper_Response::HTTP_CREATED
        ) {
            throw new MercadoPago_Core_Model_Exception_InvalidRequest('Can not retrieve payment information');
        }


        if (count($data['payments']) > 0) {
            $data = $this->_getDataPayments($data);
            $status_final = $this->getStatusFinal($data['status'], $data);
            $shipmentData = (isset($data['shipments'][0])) ? $data['shipments'][0] : [];
            $this->getHelper()->log("Update Order", self::LOG_FILE);
            $this->getHelper()->setStatusUpdated($data);

            /** @var MercadoPago_Core_Model_Core $mpCore */
            $mpCore = Mage::getModel('mercadopago/core');
            $mpCore->updateOrder($data);

            if (!empty($shipmentData)) {
                Mage::dispatchEvent('mercadopago_standard_notification_before_set_status',
                    [
                        'shipmentData' => $shipmentData,
                        'orderId' => $data['external_reference']
                    ]
                );
            }
            if ($status_final != false) {
                $data['status_final'] = $status_final;
                $this->getHelper()->log("Received Payment data", self::LOG_FILE, $data);
                $response = $mpCore->setStatusOrder($data);
            } else {
                $response = [
                    'text' => 'Status not final',
                    'code' => MercadoPago_Core_Helper_Response::HTTP_OK
                    ];
            }
            Mage::dispatchEvent('mercadopago_standard_notification_received',
                [
                    'payment' => $data,
                    'merchant_order' => $data
                ]
            );

            return $response;
        }
    }

    /**
     * @return MercadoPago_Core_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('mercadopago');
    }
}