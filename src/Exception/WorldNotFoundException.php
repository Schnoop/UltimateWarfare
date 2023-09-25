<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Exception;

final class WorldNotFoundException extends \Exception
{
    protected $message = 'World does not exist!'; // @phpstan-ignore-line
}
