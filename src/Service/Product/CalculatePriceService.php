<?php

namespace App\Service\Product;

use App\Exception\CouponCodeNotFoundException;
use App\Exception\ProductNotFoundException;
use App\Exception\TaxCodeNotFoundException;
use App\Repository\CouponRepository;
use App\Repository\ProductRepository;
use App\Repository\TaxRepository;

readonly class CalculatePriceService
{
    public function __construct(
        private ProductRepository $productRepository,
        private TaxRepository $taxRepository,
        private CouponRepository $couponRepository
    ) {
    }

    /**
     * @throws ProductNotFoundException
     * @throws TaxCodeNotFoundException
     * @throws CouponCodeNotFoundException
     */
    public function execute(int $productId, string $taxCode, ?string $couponCode = null)
    {
        if (!$product = $this->productRepository->find($productId)) {
            throw new ProductNotFoundException('Product not found');
        }

        if (!$tax = $this->taxRepository->findOneBy(['code' => $taxCode])) {
            throw new TaxCodeNotFoundException('Tax code not found');
        }

        if ($couponCode && !$coupon = $this->couponRepository->findOneBy(['code' => $couponCode])) {
            throw new CouponCodeNotFoundException('Coupon code not found');
        }

        $productPrice = $product->getPrice();
        $discount = $productPrice * ($couponCode && $coupon ? $coupon->getValue() / 100 : 0);
        $grossPrice = $productPrice - $discount;
        $taxes = $grossPrice * $tax->getAmount() / 100;

        return $grossPrice + $taxes;
    }
}