<?php

namespace App\Controller;

use App\Controller\Request\CalculatePriceRequest;
use App\Exception\CouponCodeNotFoundException;
use App\Exception\ProductNotFoundException;
use App\Exception\TaxCodeNotFoundException;
use App\Service\Product\CalculatePriceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductsPricesShowController extends AbstractController
{
    public function __construct(
        private readonly CalculatePriceService $calculatePriceService,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/calculate-price', name: 'api_products_calculate_price')]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $requestData = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $calculatePriceRequest = new CalculatePriceRequest(
                productId: $requestData['product'],
                taxCode: $requestData['taxNumber'],
                couponCode: $requestData['couponCode'] ?? null
            );

            $violations = $this->validator->validate($calculatePriceRequest);

            $errors = array_map(
                static fn($error) => ["propertyPath" => $error->getPropertyPath(), "message" => $error->getMessage()],
                iterator_to_array($violations)
            );

            if (0 < count($violations)) {
                return $this->json($errors, Response::HTTP_BAD_REQUEST);
            }

            $productPrice = $this->calculatePriceService->execute(
                productId: $requestData['product'],
                taxCode: $requestData['taxNumber'],
                couponCode: $requestData['couponCode'] ?? null
            );

            return $this->json(['data'=>['price' => $productPrice]], Response::HTTP_OK);
        } catch (\JsonException) {
            return $this->json(['error' => 'Request payload malformed'], Response::HTTP_BAD_REQUEST);
        } catch (ProductNotFoundException|TaxCodeNotFoundException|CouponCodeNotFoundException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}