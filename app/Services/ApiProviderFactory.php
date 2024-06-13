<?php

namespace App\Services;

use App\Concrete\SMMApiProvider;
use App\Contracts\ApiProviderInterface;


class ApiProviderFactory
{
    public function createProvider(string $providerType): ApiProviderInterface
    {
        return match ($providerType) {
            'SMM' => new SMMApiProvider(),


            default => throw new \InvalidArgumentException("Invalid provider type: $providerType"),
        };
    }
}