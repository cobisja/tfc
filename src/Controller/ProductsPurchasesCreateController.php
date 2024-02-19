<?php

namespace App\Controller;

use App\Controller\Request\PurchaseRequest;
use App\Exception\CouponCodeNotFoundException;
use App\Exception\PaymentNotProcessedException;
use App\Exception\ProductNotFoundException;
use App\Exception\TaxCodeNotFoundException;
use App\Payment\PaymentProcessorInterface;
use App\Service\Product\PurchaseCreateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductsPurchasesCreateController extends AbstractController
{
    public function __construct(
        private readonly PurchaseCreateService $purchaseCreateService,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/purchase', name: 'api_products_purchases_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $requestData = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $purchaseRequest = new PurchaseRequest(
                productId: $requestData['product'] ?? '',
                taxCode: $requestData['taxNumber'] ?? '',
                couponCode: $requestData['couponCode'] ?? null,
                paymentProcessor: $requestData['paymentProcessor'] ?? ''
            );

            $violations = $this->validator->validate($purchaseRequest);

            $errors = array_map(
                static fn($error) => ["propertyPath" => $error->getPropertyPath(), "message" => $error->getMessage()],
                iterator_to_array($violations)
            );

            if (0 < count($violations)) {
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $paymentProcessorClass = sprintf(
                'App\\Payment\\%sPaymentProcessor',
                ucfirst(strtolower($requestData['paymentProcessor']))
            );

            if (!class_exists($paymentProcessorClass)) {
                return $this->json(['error' => 'Payment processor not supported'], Response::HTTP_BAD_REQUEST);
            }

            /** @var PaymentProcessorInterface $paymentProcessor */
            $paymentProcessor = new $paymentProcessorClass;

            $paymentProcessed = $this->purchaseCreateService->execute(
                paymentProcessor: $paymentProcessor,
                productId: $requestData['product'],
                taxCode: $requestData['taxNumber'],
                couponCode: $requestData['couponCode'] ?? null
            );

            return $this->json(['data' => ['payment_processed' => $paymentProcessed]], Response::HTTP_OK);
        } catch (\JsonException) {
            return $this->json(['error' => 'Request payload malformed'], Response::HTTP_BAD_REQUEST);
        } catch (
        ProductNotFoundException
        |TaxCodeNotFoundException
        |CouponCodeNotFoundException
        |PaymentNotProcessedException $exception
        ) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}