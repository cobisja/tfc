<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    final public const PRODUCTS = [
        ['name' => 'iPhone', 'price' => '100.00'],
        ['name' => 'Headphones', 'price' => '20.00'],
        ['name' => 'case', 'price' => '10.00'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PRODUCTS as $product) {
            $p = new Product();

            $p->setName($product['name']);
            $p->setPrice($product['price']);

            $manager->persist($p);
        }

        $manager->flush();
    }
}
