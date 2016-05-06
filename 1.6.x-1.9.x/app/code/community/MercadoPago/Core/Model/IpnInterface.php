<?php

/**
 * Interface MercadoPago_Core_Model_IpnInterface
 */
interface MercadoPago_Core_Model_IpnInterface
{
    const LOG_FILE = 'mercadopago-notification.log';
    
    public function validateData($data);

    public function processResponse($data);
}