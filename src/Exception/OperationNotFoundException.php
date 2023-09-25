<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Exception;

final class OperationNotFoundException extends \Exception
{
    protected $message = 'Operation does not exist!'; // @phpstan-ignore-line
}
