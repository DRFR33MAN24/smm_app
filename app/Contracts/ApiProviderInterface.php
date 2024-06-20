<?php

namespace App\Contracts;

use App\Models\ApiProvider;
use App\Models\Order;

interface ApiProviderInterface
{
    public function getAllProviderServices(ApiProvider $apiProvider);

    public function importMulti(ApiProvider $apiProvider,array $request);

    public function updateProviderServicesPrices(ApiProvider $apiProvider);

    public function updateProviderBalance(ApiProvider $apiProvider);

    public function getOrderStatus(ApiProvider $apiProvider, Order $orderId);

    public function updateServicePrice(ApiProvider $apiProvider,string $serviceId);

    public function placeOrder(ApiProvider $apiProvider, array $details);
    public function reMapServiceArrayKeys(array $apiResponse) : array;
}
