<?php

namespace App\Payment;

class RedsysPaymentProcessor implements PaymentProcessorInterface
{
    public function pay($price): bool
    {
        return false; // Let's suppose the payment platform is currently denying any purchase :S
    }
}