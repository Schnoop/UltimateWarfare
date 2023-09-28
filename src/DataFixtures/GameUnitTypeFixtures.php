<?php

namespace FrankProjects\UltimateWarfare\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use FrankProjects\UltimateWarfare\Entity\GameUnitType;

class GameUnitTypeFixtures extends Fixture
{
    /**
     * @var array<int, array<string, array<string, string>>>
     */
    protected array $gameUnitTypes = [
        [
            'en' => ['id' => 1, 'name' => 'Buildings', 'imageDir' => 'units/buildings/'],
            'nl' => ['id' => 1, 'name' => 'Gebouwen', 'imageDir' => 'units/buildings/'],
        ],
        [
            'en' => ['id' => 2, 'name' => 'Defence buildings', 'imageDir' => 'units/defense_buildings/'],
            'nl' => ['id' => 2, 'name' => 'Defensie gebouwen', 'imageDir' => 'units/defense_buildings/'],
        ],
        [
            'en' => ['id' => 3, 'name' => 'Special buildings', 'imageDir' => 'units/special_buildings/'],
            'nl' => ['id' => 3, 'name' => 'Bijzondere gebouwen', 'imageDir' => 'units/special_buildings/'],
        ],
        [
            'en' => ['id' => 4, 'name' => 'Units', 'imageDir' => 'units/units/'],
            'nl' => ['id' => 4, 'name' => 'Eenheden', 'imageDir' => 'units/units/'],
        ],
        [
            'en' => ['id' => 5, 'name' => 'Special units', 'imageDir' => 'units/special_units/'],
            'nl' => ['id' => 5, 'name' => 'Speciale eenheden', 'imageDir' => 'units/special_units/'],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach ($this->gameUnitTypes as $gameUnitType) {
            $unitType = new GameUnitType();
            foreach ($gameUnitType as $locale => $item) {
                $unitType->setId($item['id']);
                $unitType->translate($locale)->setName($item['name']);
                $unitType->translate($locale)->setImageDir($item['imageDir']);
            }
            $manager->persist($unitType);
            $unitType->mergeNewTranslations();
        }

        $manager->flush();
    }
}
