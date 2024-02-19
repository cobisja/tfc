<?php

namespace App\Tests\Service\Product;

use App\Payment\PaypalPaymentProcessor;
use App\Payment\StripePaymentProcessor;
use App\Service\Product\CalculatePriceService;
use App\Service\Product\PurchaseCreateService;
use PHPUnit\Framework\TestCase;

class PurchaseCreateServiceTest extends TestCase
{
    /**
     * @test
     * @dataProvider fixturesForCreatingPurchases
     */
    public function is_should_create_a_purchase($paymentProcessor, $productId, $taxCode, $couponCode): void
    {
        $calculatePriceService = $this->createMock(CalculatePriceService::class);
        $purchaseCreateService = new PurchaseCreateService($calculatePriceService);
        $result = $purchaseCreateService->execute($paymentProcessor, $productId, $taxCode);

        $this->assertIsBool($result);
    }

    /**
     * @return array
     */
    public function fixturesForCreatingPurchases(): array
    {
        /**
         * Fixture structure:
         * [ PaymentProcessorInterface, $productId, taxCode, couponCode ]
         */
        return [
            [new PaypalPaymentProcessor(), 10, 'GR012345678', 'D6'],
            [new StripePaymentProcessor(), 11, 'IT01234567890', null],
        ];
    }
}