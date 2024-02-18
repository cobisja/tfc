<?php

namespace App\DataFixtures;

use App\Entity\Coupon;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CouponFixtures extends Fixture
{
    final public const COUPONS = [
        ['code' => 'P10', 'value' => '10.00'],
        ['code' => 'P30', 'value' => '30.00'],
        ['code' => 'P50', 'value' => '50.00']
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::COUPONS as $coupon) {
            $c = new Coupon();

            $c->setCode($coupon['code']);
            $c->setValue($coupon['value']);

            $manager->persist($c);
        }

        $manager->flush();
    }
}
