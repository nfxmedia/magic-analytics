<?php

// Create test orders script for Shopware 6
// Run this inside the docker container

$baseUrl = 'http://localhost';
$username = 'admin';
$password = 'shopware';

// Get auth token
$authData = [
    'grant_type' => 'password',
    'client_id' => 'administration',
    'username' => $username,
    'password' => $password
];

$ch = curl_init($baseUrl . '/api/oauth/token');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($authData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$token = json_decode($response, true)['access_token'];

if (!$token) {
    die("Failed to get auth token\n");
}

echo "Got auth token\n";

// Headers for API requests
$headers = [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
];

// Function to make API requests
function apiRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        echo "API Error ($httpCode): " . $response . "\n";
        return null;
    }
    
    return json_decode($response, true);
}

// Get necessary data
echo "Fetching products...\n";
$products = apiRequest($baseUrl . '/api/product?limit=50&associations[cover][]', 'GET', null, $headers);

echo "Fetching customers...\n";
$customers = apiRequest($baseUrl . '/api/customer?limit=10', 'GET', null, $headers);

echo "Fetching sales channels...\n";
$salesChannels = apiRequest($baseUrl . '/api/sales-channel?limit=1', 'GET', null, $headers);

echo "Fetching payment methods...\n";
$paymentMethods = apiRequest($baseUrl . '/api/payment-method?filter[active]=1', 'GET', null, $headers);

echo "Fetching shipping methods...\n";
$shippingMethods = apiRequest($baseUrl . '/api/shipping-method?filter[active]=1', 'GET', null, $headers);

echo "Fetching currencies...\n";
$currencies = apiRequest($baseUrl . '/api/currency?limit=1', 'GET', null, $headers);

echo "Fetching order states...\n";
$orderStates = apiRequest($baseUrl . '/api/state-machine-state?limit=100', 'GET', null, $headers);

echo "Fetching delivery states...\n";
$deliveryStates = apiRequest($baseUrl . '/api/state-machine-state?limit=100', 'GET', null, $headers);

if (!$products || !$customers || !$salesChannels || !$paymentMethods || !$shippingMethods || !$currencies || !$orderStates) {
    die("Failed to fetch required data\n");
}

$productList = $products['data'];
$customerList = $customers['data'];
$salesChannel = $salesChannels['data'][0];
$paymentMethodList = $paymentMethods['data'];
$shippingMethodList = $shippingMethods['data'];
$currency = $currencies['data'][0];

// Find open order state - look for the first available state
$openState = null;
foreach ($orderStates['data'] as $state) {
    if (isset($state['attributes']['technicalName']) && strpos($state['attributes']['technicalName'], 'open') !== false) {
        $openState = $state;
        break;
    }
}

// If not found, just use the first state
if (!$openState && !empty($orderStates['data'])) {
    $openState = $orderStates['data'][0];
}

// Find delivery state
$deliveryState = null;
foreach ($deliveryStates['data'] as $state) {
    if (isset($state['attributes']['technicalName']) && strpos($state['attributes']['technicalName'], 'open') !== false) {
        $deliveryState = $state;
        break;
    }
}

// If not found, just use the first state
if (!$deliveryState && !empty($deliveryStates['data'])) {
    $deliveryState = $deliveryStates['data'][0];
}

if (!$openState) {
    die("Could not find order state\n");
}

// Random data for orders
$firstNames = ['John', 'Jane', 'Bob', 'Alice', 'Charlie', 'Emma', 'Oliver', 'Sophia', 'William', 'Ava'];
$lastNames = ['Smith', 'Johnson', 'Williams', 'Jones', 'Brown', 'Davis', 'Miller', 'Wilson', 'Moore', 'Taylor'];
$streets = ['Main St', 'First Ave', 'Second Blvd', 'Oak Street', 'Elm Avenue', 'Maple Drive', 'Cedar Lane'];
$cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio'];
$countries = ['US', 'GB', 'DE', 'FR', 'NL', 'BE', 'AT', 'CH'];

// Create orders
$orderCount = 25;
$createdOrders = 0;

echo "\nCreating $orderCount test orders...\n";

for ($i = 0; $i < $orderCount; $i++) {
    // Random customer or guest
    $useExistingCustomer = !empty($customerList) && rand(0, 1);
    
    if ($useExistingCustomer) {
        $customer = $customerList[array_rand($customerList)];
        $customerId = $customer['id'];
        $email = $customer['email'];
        $firstName = $customer['firstName'];
        $lastName = $customer['lastName'];
    } else {
        $customerId = null;
        $firstName = $firstNames[array_rand($firstNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        $email = strtolower($firstName . '.' . $lastName . rand(100, 999) . '@example.com');
    }
    
    // Random products (1-5 items)
    $lineItems = [];
    $itemCount = rand(1, 5);
    $totalPrice = 0;
    
    for ($j = 0; $j < $itemCount; $j++) {
        $product = $productList[array_rand($productList)];
        $quantity = rand(1, 3);
        $price = $product['price'][0]['gross'] ?? rand(10, 200);
        $lineTotal = $price * $quantity;
        $totalPrice += $lineTotal;
        
        $lineItems[] = [
            'identifier' => $product['id'],
            'type' => 'product',
            'referencedId' => $product['id'],
            'quantity' => $quantity,
            'label' => $product['name'],
            'price' => [
                'unitPrice' => $price,
                'totalPrice' => $lineTotal,
                'quantity' => $quantity,
                'calculatedTaxes' => [],
                'taxRules' => []
            ],
            'priceDefinition' => [
                'price' => $price,
                'taxRules' => [],
                'quantity' => $quantity,
                'isCalculated' => true,
                'referencedId' => $product['id'],
                'type' => 'quantity'
            ],
            'good' => true
        ];
    }
    
    // Random dates (last 30 days)
    $daysAgo = rand(0, 30);
    $orderDate = date('c', strtotime("-$daysAgo days"));
    
    // Random payment and shipping
    $paymentMethod = $paymentMethodList[array_rand($paymentMethodList)];
    $shippingMethod = $shippingMethodList[array_rand($shippingMethodList)];
    
    // Create order
    $orderData = [
        'orderNumber' => 'TEST-' . date('Ymd') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
        'salesChannelId' => $salesChannel['id'],
        'orderDateTime' => $orderDate,
        'orderDate' => $orderDate,
        'price' => [
            'netPrice' => $totalPrice * 0.84,
            'totalPrice' => $totalPrice,
            'calculatedTaxes' => [],
            'taxRules' => [],
            'positionPrice' => $totalPrice,
            'taxStatus' => 'gross'
        ],
        'shippingCosts' => [
            'unitPrice' => 5.00,
            'totalPrice' => 5.00,
            'quantity' => 1,
            'calculatedTaxes' => [],
            'taxRules' => []
        ],
        'stateId' => $openState['id'],
        'paymentMethodId' => $paymentMethod['id'],
        'currencyId' => $currency['id'],
        'currencyFactor' => 1.0,
        'lineItems' => $lineItems,
        'deliveries' => [
            [
                'stateId' => $deliveryState ? $deliveryState['id'] : $openState['id'],
                'shippingMethodId' => $shippingMethod['id'],
                'shippingCosts' => [
                    'unitPrice' => 5.00,
                    'totalPrice' => 5.00,
                    'quantity' => 1,
                    'calculatedTaxes' => [],
                    'taxRules' => []
                ],
                'shippingDateEarliest' => date('c', strtotime($orderDate . ' +1 day')),
                'shippingDateLatest' => date('c', strtotime($orderDate . ' +3 days')),
                'shippingOrderAddress' => [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'street' => rand(1, 999) . ' ' . $streets[array_rand($streets)],
                    'city' => $cities[array_rand($cities)],
                    'zipcode' => rand(10000, 99999),
                    'countryId' => $salesChannel['countryId'],
                    'phoneNumber' => '+1' . rand(1000000000, 9999999999)
                ]
            ]
        ],
        'orderCustomer' => [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'customerNumber' => 'GUEST-' . rand(10000, 99999)
        ],
        'billingAddressId' => null,
        'addresses' => [
            [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'street' => rand(1, 999) . ' ' . $streets[array_rand($streets)],
                'city' => $cities[array_rand($cities)],
                'zipcode' => rand(10000, 99999),
                'countryId' => $salesChannel['countryId'],
                'phoneNumber' => '+1' . rand(1000000000, 9999999999)
            ]
        ]
    ];
    
    if ($customerId) {
        $orderData['orderCustomer']['customerId'] = $customerId;
    }
    
    $result = apiRequest($baseUrl . '/api/order', 'POST', $orderData, $headers);
    
    if ($result) {
        $createdOrders++;
        echo "Created order " . ($i + 1) . " of $orderCount\n";
    } else {
        echo "Failed to create order " . ($i + 1) . "\n";
    }
}

echo "\nSuccessfully created $createdOrders test orders!\n";
echo "You can now check the nfx MAGIC Analytics plugin to see the test data.\n";