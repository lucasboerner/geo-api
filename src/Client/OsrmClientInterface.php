<?php

declare(strict_types=1);

namespace App\Client;

use App\ApiResource\Route;
use App\Dto\Coordinate;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

interface OsrmClientInterface
{
    /**
     * @throws ExceptionInterface
     * @throws \RuntimeException
     */
    public function route(Coordinate $from, Coordinate $to): Route;
}
