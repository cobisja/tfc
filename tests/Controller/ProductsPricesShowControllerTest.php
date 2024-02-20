<?php

namespace App\Tests\Controller;

use App\Entity\Coupon;
use App\Entity\Product;
use App\Entity\Tax;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductsPricesShowControllerTest extends WebTestCase
{
    final public const CALCULATE_PRICE_URI = '/calculate-price';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    /**
     * @test
     * @dataProvider requestsContentFixtures
     */
    public function it_should_returns_a_400_code_with_malformed_or_unexpected_or_missing_request_params(
        string $content,
        int $expectedCode
    ): void {
        $this->client->request(
            method: 'POST',
            uri: self::CALCULATE_PRICE_URI,
            content: $content
        );

        self::assertResponseStatusCodeSame($expectedCode);
    }

    /**
     * @test
     * @dataProvider malformedRequestParamsFixtures
     */
    public function it_should_returns_validation_errors_with_malformed_request_params(string $content): void
    {
        $this->client->request(
            method: 'POST',
            uri: self::CALCULATE_PRICE_URI,
            content: $content
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertArrayHasKey('errors', $response);
    }

    /**
     * @test
     */
    public function it_should_returns_a_response_with_error_when_any_params_does_not_exist(): void
    {
        $entityManager = self::getContainer()->get('doctrine')?->getManager();

        /**
         * Fixtures must be loaded first!
         */
        $products = $entityManager->getRepository(Product::class)->findAll();
        $taxes = $entityManager->getRepository(Tax::class)->findAll();
        $coupons = $entityManager->getRepository(Coupon::class)->findAll();

        $requestParams = [
            [
                'content' => json_encode([
                    'product' => -1,
                    'taxNumber' => $taxes[0]->getCode(),
                    'couponCode' => $coupons[0]->getCode()
                ]),
                'error_message' => 'Product not found'
            ],
            [
                'content' => json_encode([
                    'product' => $products[0]->getId(),
                    'taxNumber' => 'IT00000000000',
                    'couponCode' => $coupons[0]->getCode()
                ]),
                'error_message' => 'Tax code not found'
            ],
            [
                'content' => json_encode([
                    'product' => $products[0]->getId(),
                    'taxNumber' => $taxes[0]->getCode(),
                    'couponCode' => 'XX'
                ]),
                'error_message' => 'Coupon code not found'
            ]
        ];

        /**
         * This is a hack due the restrictions about accessing the entity manager
         * via "dataProviders" because any class field is not initialized when the
         * provider is called.
         */
        foreach ($requestParams as $requestParam) {
            $this->client->request(
                method: 'POST',
                uri: self::CALCULATE_PRICE_URI,
                content: $requestParam['content']
            );

            $response = json_decode($this->client->getResponse()->getContent(), true);

            self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
            self::assertArrayHasKey('error', $response);
            self::assertEquals($requestParam['error_message'], $response['error']);
        }
    }

    /**
     * @test
     */
    public function it_should_returns_the_product_price(): void
    {
        $entityManager = self::getContainer()->get('doctrine')?->getManager();

        /**
         * Fixtures must be loaded first!
         */
        $products = $entityManager->getRepository(Product::class)->findAll();
        $taxes = $entityManager->getRepository(Tax::class)->findAll();
        $coupons = $entityManager->getRepository(Coupon::class)->findAll();

        $content = json_encode([
            'product' => $products[0]->getId(),
            'taxNumber' => $taxes[0]->getCode(),
            'couponCode' => $coupons[0]->getCode()
        ]);

        $this->client->request(
            method: 'POST',
            uri: self::CALCULATE_PRICE_URI,
            content: $content
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertArrayHasKey('data', $response);
        self::assertIsNumeric($response['data']['price']);
    }

    private function requestsContentFixtures(): array
    {
        return [
            /**
             * Malformed request
             */
            ['{', Response::HTTP_BAD_REQUEST],

            /**
             * Empty body (missing all request params)
             */
            ['{}', Response::HTTP_BAD_REQUEST],
        ];
    }

    private function malformedRequestParamsFixtures(): array
    {
        return [
            /**
             * Missing productId
             */
            [json_encode(['taxNumber' => 'FRXY0123456789'])],
            [json_encode(['taxNumber' => 'FRXY0123456789', 'couponCode' => 'P50'])],

            /**
             * Missing taxCode
             */
            [json_encode(['product' => 10])],
            [json_encode(['product' => 10, 'couponCode' => 'P50'])],

            /**
             * Wrong taxCode Format
             */
            [json_encode(['product' => 10, 'taxNumber' => 'FRX0123456789'])],

            /*
             * Empty couponCode
             */
            [json_encode(['product' => 10, 'taxNumber' => 'FRX0123456789', ''])]
        ];
    }
}