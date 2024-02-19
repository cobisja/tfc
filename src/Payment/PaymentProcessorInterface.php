<?php

namespace App\Payment;

interface PaymentProcessorInterface
{
    public function pay($price): bool;
}