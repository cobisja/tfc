<?php

namespace App\Tests\Service\Product;

use App\Entity\Coupon;
use App\Entity\Product;
use App\Entity\Tax;
use App\Exception\CouponCodeNotFoundException;
use App\Exception\ProductNotFoundException;
use App\Exception\TaxCodeNotFoundException;
use App\Repository\CouponRepository;
use App\Repository\ProductRepository;
use App\Repository\TaxRepository;
use App\Service\Product\CalculatePriceService;
use PHPUnit\Framework\TestCase;

class CalculatePriceServiceTest extends TestCase
{
    private ProductRepository $productRepository;
    private TaxRepository $taxRepository;
    private CouponRepository $couponRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->taxRepository = $this->createMock(TaxRepository::class);
        $this->couponRepository = $this->createMock(CouponRepository::class);
    }

    /**
     * @test
     */
    public function it_should_throws_product_not_found_exception_when_the_product_id_does_not_exists(): void
    {
        $this->expectException(ProductNotFoundException::class);

        $productId = -1; // We can be sure this id does not exist
        $taxCode = 'XYZ';

        $this->productRepository
            ->expects(self::once())
            ->method('find')
            ->willReturn(null);

        $calculatePriceService = new CalculatePriceService(
            $this->productRepository,
            $this->taxRepository,
            $this->couponRepository
        );

        $calculatePriceService->execute($productId, $taxCode);
    }

    /**
     * @test
     */
    public function it_should_throws_tax_code_not_found_exception_when_the_tax_code_does_not_exists(): void
    {
        $this->expectException(TaxCodeNotFoundException::class);

        $product = new Product();

        $productId = 1; // We can be sure this id does not exist
        $taxCode = 'XYZ';

        $this->productRepository
            ->expects(self::once())
            ->method('find')
            ->willReturn($product);

        $this->taxRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $calculatePriceService = new CalculatePriceService(
            $this->productRepository,
            $this->taxRepository,
            $this->couponRepository
        );

        $calculatePriceService->execute($productId, $taxCode);
    }

    /**
     * @test
     */
    public function it_should_throws_coupon_code_not_found_exception_when_the_coupon_is_passed_and_does_not_exists(
    ): void
    {
        $this->expectException(CouponCodeNotFoundException::class);

        $product = new Product();
        $tax = new Tax();

        $productId = 1;
        $taxCode = 'DE123456789';
        $couponCode = 'D15';

        $this->productRepository
            ->expects(self::once())
            ->method('find')
            ->willReturn($product);

        $this->taxRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn($tax);

        $this->couponRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $calculatePriceService = new CalculatePriceService(
            $this->productRepository,
            $this->taxRepository,
            $this->couponRepository
        );

        $calculatePriceService->execute($productId, $taxCode, $couponCode);
    }

    /**
     * @test
     * @dataProvider fixturesForCalculatingPrices
     */
    public function is_should_calculate_the_product_price(
        $productId,
        $productPrice,
        $taxCode,
        $taxAmount,
        $couponCode,
        $couponValue,
        $expectedPrice
    ): void {
        $product = new Product();
        $product->setPrice($productPrice);

        $tax = new Tax();
        $tax->setAmount($taxAmount);

        if ($couponCode) {
            $coupon = new Coupon();
            $coupon->setValue($couponValue);
        } else {
            $coupon = null;
        }

        $this->productRepository
            ->expects(self::any())
            ->method('find')
            ->willReturn($product);

        $this->taxRepository
            ->expects(self::any())
            ->method('findOneBy')
            ->willReturn($tax);

        $this->couponRepository
            ->expects(self::any())
            ->method('findOneBy')
            ->willReturn($coupon);

        $calculatePriceService = new CalculatePriceService(
            $this->productRepository,
            $this->taxRepository,
            $this->couponRepository
        );

        $price = $calculatePriceService->execute($productId, $taxCode, $couponCode);

        $this->assertEquals($expectedPrice, $price);
    }

    /**
     * @return array
     */
    public function fixturesForCalculatingPrices(): array
    {
        /**
         * Fixture structure:
         * [ productId, $productPrice, taxCode, $taxAmount, couponCode, $couponValue, expectedResult ]
         */
        return [
            [10, 100.0, 'GR012345678', 24.0, 'D6', 6.0, 116.56],
            [11, 20.0, 'IT01234567890', 22.0, null, null, 24.4],
            [12, 10.0, 'FRXY0123456789', 20.0, 'D20', 20, 9.6],
        ];
    }
}