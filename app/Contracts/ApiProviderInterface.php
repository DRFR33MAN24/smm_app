<?php

namespace App\Contracts;

use App\Models\ApiProvider;


interface ApiProviderInterface
{
    public function getAllProviderServices(ApiProvider $apiProvider);

    public function importMulti(ApiProvider $apiProvider,array $request);

    public function updateProviderServicesPrices(ApiProvider $apiProvider);

    public function updateProviderBalance(ApiProvider $apiProvider);

    public function getOrderStatus(ApiProvider $apiProvider, string $orderId);

    public function placeOrder(ApiProvider $apiProvider, array $detials);
}
