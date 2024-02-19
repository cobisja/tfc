<?php

namespace App\Payment;

use App\Exception\PaymentNotProcessedException;
use Exception;
use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor as SystemeioPaypalPaymentProcessor;

class PaypalPaymentProcessor implements PaymentProcessorInterface
{
    /**
     * @throws PaymentNotProcessedException
     */
    public function pay($price): bool
    {
        try {
            (new SystemeioPaypalPaymentProcessor())->pay((float)$price);

            return true;
        } catch (Exception $exception) {
            throw new PaymentNotProcessedException($exception->getMessage());
        }
    }
}