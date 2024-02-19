<?php

namespace App\Controller\Request;

use App\Validator\TaxCodeFormat;
use Symfony\Component\Validator\Constraints as Assert;

class PurchaseRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('integer')]
        public $productId,

        #[Assert\NotBlank]
        #[TaxCodeFormat]
        public string $taxCode,

        public ?string $couponCode,

        #[Assert\NotBlank]
        public string $paymentProcessor
    ) {
    }
}