<?php

namespace App\Payment;

use App\Exception\PaymentNotProcessedException;
use Exception;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor as SystemeioStripePaymentProcessor;

class StripePaymentProcessor implements PaymentProcessorInterface
{
    /**
     * @throws PaymentNotProcessedException
     */
    public function pay($price): bool
    {
        try {
            return (new SystemeioStripePaymentProcessor())->processPayment((int)$price);
        } catch (Exception $exception) {
            throw new PaymentNotProcessedException($exception->getMessage());
        }
    }
}