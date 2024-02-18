<?php

namespace App\DataFixtures;

use App\Entity\Tax;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TaxFixtures extends Fixture
{
    final public const TAXES = [
        ['code' => 'DE012345678', 'amount' => '19.00'],
        ['code' => 'IT01234567890', 'amount' => '22.00'],
        ['code' => 'GR012345678', 'amount' => '24.00'],
        ['code' => 'FRXY0123456789', 'amount' => '20.00'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::TAXES as $tax) {
            $t = new Tax();

            $t->setCode($tax['code']);
            $t->setAmount($tax['amount']);

            $manager->persist($t);
        }

        $manager->flush();
    }
}
