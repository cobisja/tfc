<?php

namespace App\Service\Product;

use App\Exception\CouponCodeNotFoundException;
use App\Exception\ProductNotFoundException;
use App\Exception\TaxCodeNotFoundException;
use App\Payment\PaymentProcessorInterface;

readonly class PurchaseCreateService
{
    public function __construct(private CalculatePriceService $calculatePriceService)
    {
    }

    /**
     * @throws ProductNotFoundException
     * @throws TaxCodeNotFoundException
     * @throws CouponCodeNotFoundException
     */
    public function execute(
        PaymentProcessorInterface $paymentProcessor,
        int $productId,
        string $taxCode,
        ?string $couponCode = null
    ): bool {
        return $paymentProcessor->pay(
            $this->calculatePriceService->execute($productId, $taxCode, $couponCode)
        );
    }
}