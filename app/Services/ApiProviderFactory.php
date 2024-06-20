<?php

namespace App\Services;

use App\Concrete\DHRUApiProvider;
use App\Concrete\SMMApiProvider;
use App\Concrete\ZDDKApiProvider;
use App\Contracts\ApiProviderInterface;


class ApiProviderFactory
{
    public function createProvider(string $providerType): ApiProviderInterface
    {
        return match ($providerType) {
            'SMM' => new SMMApiProvider(),
            'DHRU' => new DHRUApiProvider(),
            'ZDDK' => new ZDDKApiProvider(),


            default => throw new \InvalidArgumentException("Invalid provider type: $providerType"),
        };
    }
}