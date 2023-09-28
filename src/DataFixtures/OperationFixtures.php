<?php

namespace FrankProjects\UltimateWarfare\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use FrankProjects\UltimateWarfare\Entity\Operation;

class OperationFixtures extends Fixture
{
    /**
     * @var array<int, array<string, array<string, string>>>
     */
    protected array $operations = [
        [
            '__' => ['id' => 1, 'subclass' => 'MissileAttack', 'research_id' => 100, 'unit_id' => 405, 'cost' => 50, 'enabled' => 1, 'difficulty' => 0.5, 'max_distance' => 3] ,
            'en' => ['name' => 'Missile Attack', 'image' => 'op_rocket.gif', 'description' => 'Launch an Missile attack against an enemy country. Every rocket has 50% chance in destroying an building.\n\n\"Your enemy will recieve an report about this attack if you succeed and fail.\"'],
            'nl' => ['name' => 'Raketaanval', 'image' => 'op_rocket.gif', 'description' => 'Lanceer een raketaanval op een vijandig land. Elke raket heeft 50% kans om te vernietigen en te bouwen.\n\n\"Je vijand ontvangt een rapport over deze aanval als je slaagt of faalt.\"'],
        ],
        [
            '__' => ['id' => 2, 'subclass' => 'StealthBomberAttack', 'research_id' => 100, 'unit_id' => 404, 'cost' => 15000, 'enabled' => 1, 'difficulty' => 0.5, 'max_distance' => 10],
            'en' => ['name' => 'Stealth Bombing', 'image' => 'op_stealthbombing.gif', 'description' => 'Launch an Bombing run against an enemy country with your Stealth planes. Every Stealth Bomber is able to hit 5 buildings (train stations, aiports or harbors).\n(Airforce Only)\n\n\"Your enemy will recieve an report about this attack if you succeed and fail. But it will hidde your name if you succeed.\"'],
            'nl' => ['name' => 'Stealth-bombardementen', 'image' => 'op_stealthbombing.gif', 'description' => 'Lanceer een bombardementsvlucht tegen een vijandig land met je Stealth-vliegtuigen. Elke Stealth-bommenwerper kan 5 gebouwen raken (treinstations, luchthavens of havens).\n(Alleen luchtmacht)\n\n\"Je vijand ontvangt een rapport over deze aanval als je slaagt of faalt. Maar hij zal jouw aanval verbergen. naam als het je lukt.\"'],
        ],
        [
            '__' => ['id' => 3, 'subclass' => 'Spy', 'research_id' => 104, 'unit_id' => 407, 'cost' => 150, 'enabled' => 1, 'difficulty' => 0.1, 'max_distance' => 3],
            'en' => ['name' => 'Spy Technology', 'image' => 'spy.gif', 'description' => 'Spy on an enemy country and retrieve important data like buildings and units.\n\n\"If you fail, your enemy recieves an report about your spy attack\"'],
            'nl' => ['name' => 'Spionagetechnologie', 'image' => 'spy.gif', 'description' => 'Bespioneer een vijandelijk land en haal belangrijke gegevens op, zoals gebouwen en eenheden.\n\n\"Als je faalt, ontvangt je vijand een rapport over je spionageaanval\"'],
        ],
        [
            '__' => ['id' => 4, 'subclass' => 'SniperAttack', 'research_id' => 100, 'unit_id' => 402, 'cost' => 250, 'enabled' => 1, 'difficulty' => 0.5, 'max_distance' => 2],
            'en' => ['name' => 'Sniper Team', 'image' => 'sniper.gif', 'description' => 'Deploy a Sniper Team behind the enemy lines and take out an ammount of enemy soldiers. Every sniper can kill 5 soldiers, but when you fail, you lose 20% of your snipers!\n(Army Only)\n\n\"Your enemy will recieve an report about this attack if you succeed or fail.\"'],
            'nl' => ['name' => 'Het sluipschutterteam', 'image' => 'sniper.gif', 'description' => 'Zet een sluipschutterteam achter de vijandelijke linies in en schakel een aantal vijandelijke soldaten uit. Elke sluipschutter kan 5 soldaten doden, maar als je faalt, verlies je 20% van je sluipschutters!\n(Alleen leger)\n\n\"Je vijand ontvangt een rapport over deze aanval als je slaagt of faalt.\"'],
        ],
        [
            '__' => ['id' => 5, 'subclass' => 'NuclearMissileAttack', 'research_id' => 110, 'unit_id' => 408, 'cost' => 2500000, 'enabled' => 1, 'difficulty' => 0.9, 'max_distance' => 3],
            'en' => ['name' => 'Nuclear Missile Attack', 'image' => 'op_nuclear.gif', 'description' => 'Launch an Nuclear Missile Attack against an enemy region.'],
            'nl' => ['name' => 'Kernrakettenaanval', 'image' => 'op_nuclear.gif', 'description' => 'Lanceer een nucleaire raketaanval op een vijandelijk gebied.'],
        ],
        [
            '__' => ['id' => 6, 'subclass' => 'SubmarineAttack', 'research_id' => 100, 'unit_id' => 403, 'cost' => 25000, 'enabled' => 1, 'difficulty' => 0.5, 'max_distance' => 4],
            'en' => ['name' => 'Submarine Attack', 'image' => 'submarine.gif', 'description' => 'Send an Submarine behind enemy lines and sink enemy ships! Every submarine is able to sink at least 1 ship!\n(Navy Only)\n\n\"Your enemy will recieve an report about this attack if you succeed and fail. The report includes your empire name if you fail, else not.\"'],
            'nl' => ['name' => 'Onderzeese aanval', 'image' => 'submarine.gif', 'description' => 'Stuur een onderzeeër achter de vijandelijke linies en laat vijandelijke schepen zinken! Elke onderzeeër kan minstens 1 schip tot zinken brengen!\n(Alleen marine)\n\n\"Je vijand ontvangt een rapport over deze aanval als je slaagt of faalt. Het rapport bevat de naam van je imperium als je faalt, anders niet .\"'],
        ],
        [
            '__' => ['id' => 7, 'subclass' => 'DestroyCash', 'research_id' => 600, 'unit_id' => 401, 'cost' => 100, 'enabled' => 1, 'difficulty' => 0.9, 'max_distance' => 1],
            'en' => ['name' => 'Destroy Cash', 'image' => 'destroy_cash.gif', 'description' => 'Destroy an amount of your enemies cash reserves, based on your \"destroy cash\" research level.'],
            'nl' => ['name' => 'Vernietig contant geld', 'image' => 'destroy_cash.gif', 'description' => 'Vernietig een deel van de geldreserves van je vijanden, gebaseerd op je onderzoeksniveau \"geld vernietigen\".'],
        ],
        [
            '__' => ['id' => 8, 'subclass' => 'DestroyFood', 'research_id' => 605, 'unit_id' => 401, 'cost' => 100, 'enabled' => 1, 'difficulty' => 0.7, 'max_distance' => 1],
            'en' => ['name' => 'Destroy Food', 'image' => 'destroy_food.gif', 'description' => 'Destroy an amount of your enemies food reserves, based on your \"destroy food\" research level.'],
            'nl' => ['name' => 'Vernietig voedsel', 'image' => 'destroy_food.gif', 'description' => 'Vernietig een deel van de voedselreserves van je vijanden, gebaseerd op je onderzoeksniveau \"voedsel vernietigen\".'],
        ],
        [
            '__' => ['id' => 9, 'subclass' => 'ChemicalMissileAttack', 'research_id' => 650, 'unit_id' => 406, 'cost' => 75, 'enabled' => 1, 'difficulty' => 0.7, 'max_distance' => 3],
            'en' => ['name' => 'Chemical Warfare', 'image' => 'chemical_warfare.gif', 'description' => 'Destroy an amount of your enemies population with chemical warheads, based on your \"chemical warfare\" research level.'],
            'nl' => ['name' => 'Chemische oorlogsvoering', 'image' => 'chemical_warfare.gif', 'description' => 'Vernietig een deel van je vijandenpopulatie met chemische kernkoppen, gebaseerd op je onderzoeksniveau \"chemische oorlogsvoering\".'],
        ],
        [
            '__' => ['id' => 10, 'subclass' => 'AdvancedSpy', 'research_id' => 105, 'unit_id' => 407, 'cost' => 250, 'enabled' => 1, 'difficulty' => 0.3, 'max_distance' => 6],
            'en' => ['name' => 'Advanced Spy Operation', 'image' => 'spy2.gif', 'description' => 'Spy on an enemy country and retrieve important data like cash and other empire data.\n\n\"If you fail, your enemy recieves an report about your spy attack\"'],
            'nl' => ['name' => 'Geavanceerde spionageoperatie', 'image' => 'spy2.gif', 'description' => 'Bespioneer een vijandelijk land en haal belangrijke gegevens op, zoals contant geld en andere rijksgegevens.\n\n\"Als je faalt, ontvangt je vijand een rapport over je spionageaanval\"'],
        ],
        [
            '__' => ['id' => 11, 'subclass' => 'AdvancedSpy2', 'research_id' => 105, 'unit_id' => 407, 'cost' => 500, 'enabled' => 1, 'difficulty' => 0.5, 'max_distance' => 9],
            'en' => ['name' => 'Advanced Spy Operation II', 'image' => 'spy2.gif', 'description' => 'Spy on an enemy country and retrieve important data like empire reports.\n\n\"If you fail, your enemy recieves an report about your spy attack\"'],
            'nl' => ['name' => 'Geavanceerde spionageoperatie II', 'image' => 'spy2.gif', 'description' => 'Bespioneer een vijandelijk land en haal belangrijke gegevens op, zoals imperiumrapporten.\n\n\"Als je faalt, ontvangt je vijand een rapport over je spionageaanval\"'],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach ($this->operations as $operation) {
            $operationEntry = new Operation();
            foreach ($operation as $locale => $item) {
                if ($locale === '__') {
                    continue;
                }
                $operationEntry->setId($operation['__']['id']);
                $operationEntry->setSubclass($operation['__']['subclass']);
                //$operation->setResearch($this->getReference('research_'.$operation['__']['research_id']));
                //$operation->setGameUnit($this->getReference('gameunit_'.$operation['__']['unit_id']));
                $operationEntry->setCost($operation['__']['cost']);
                $operationEntry->setEnabled($operation['__']['enabled']);
                $operationEntry->setDifficulty($operation['__']['difficulty']);
                $operationEntry->setMaxDistance($operation['__']['max_distance']);
                $operationEntry->translate($locale)->setName($item['name']);
                $operationEntry->translate($locale)->setImage($item['image']);
                $operationEntry->translate($locale)->setDescription($item['description']);
            }
            $manager->persist($operationEntry);
            $operationEntry->mergeNewTranslations();
        }

        $manager->flush();
    }
}
