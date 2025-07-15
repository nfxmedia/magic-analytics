<?php

// Script to generate orders for every day from 01.01.2024 to 15.07.2025
// This will create 5-10 orders per day with random values

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Defaults;

// Bootstrap Shopware
require '/var/www/html/vendor/autoload.php';

use Shopware\Core\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->bootEnv('/var/www/html/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();

// Get repositories
$orderRepository = $container->get('order.repository');
$productRepository = $container->get('product.repository');
$customerRepository = $container->get('customer.repository');
$countryRepository = $container->get('country.repository');
$salesChannelRepository = $container->get('sales_channel.repository');
$paymentMethodRepository = $container->get('payment_method.repository');
$shippingMethodRepository = $container->get('shipping_method.repository');
$currencyRepository = $container->get('currency.repository');
$orderStateRepository = $container->get('state_machine_state.repository');
$connection = $container->get('Doctrine\DBAL\Connection');

$context = Context::createDefaultContext();

// Get necessary data
echo "Loading products...\n";
$products = $productRepository->search(
    (new Criteria())->setLimit(500)->addFilter(new EqualsFilter('active', true)),
    $context
)->getElements();

if (empty($products)) {
    die("No products found!\n");
}

echo "Loading customers...\n";
$customers = $customerRepository->search(
    (new Criteria())->setLimit(100),
    $context
)->getElements();

echo "Loading sales channels...\n";
$salesChannel = $salesChannelRepository->search(
    (new Criteria())->setLimit(1),
    $context
)->first();

echo "Loading payment methods...\n";
$paymentMethods = $paymentMethodRepository->search(
    (new Criteria())->addFilter(new EqualsFilter('active', true)),
    $context
)->getElements();

echo "Loading shipping methods...\n";
$shippingMethods = $shippingMethodRepository->search(
    (new Criteria())->addFilter(new EqualsFilter('active', true)),
    $context
)->getElements();

echo "Loading currency...\n";
$currency = $currencyRepository->search(
    (new Criteria())->setLimit(1),
    $context
)->first();

echo "Loading countries...\n";
$countries = $countryRepository->search(
    (new Criteria())->setLimit(10),
    $context
)->getElements();

// Get order states
$orderStates = $orderStateRepository->search(
    (new Criteria()),
    $context
)->getElements();

$openState = null;
$paidState = null;
$completedState = null;
$cancelledState = null;

foreach ($orderStates as $state) {
    $technicalName = $state->getTechnicalName();
    if (strpos($technicalName, 'open') !== false && !$openState) {
        $openState = $state;
    }
    if (strpos($technicalName, 'paid') !== false && !$paidState) {
        $paidState = $state;
    }
    if (strpos($technicalName, 'completed') !== false && !$completedState) {
        $completedState = $state;
    }
    if (strpos($technicalName, 'cancelled') !== false && !$cancelledState) {
        $cancelledState = $state;
    }
}

// Default to first state if specific ones not found
if (!$openState) $openState = array_values($orderStates)[0];

// Convert to arrays
$productArray = array_values($products);
$customerArray = array_values($customers);
$paymentArray = array_values($paymentMethods);
$shippingArray = array_values($shippingMethods);
$countryArray = array_values($countries);

// Date range
$startDate = new DateTime('2024-01-01');
$endDate = new DateTime('2025-07-15');

// Customer data for guest orders
$firstNames = ['John', 'Jane', 'Bob', 'Alice', 'Charlie', 'Emma', 'Oliver', 'Sophia', 'William', 'Ava', 
               'Michael', 'Isabella', 'James', 'Mia', 'David', 'Charlotte', 'Richard', 'Amelia', 'Joseph', 'Harper'];
$lastNames = ['Smith', 'Johnson', 'Williams', 'Jones', 'Brown', 'Davis', 'Miller', 'Wilson', 'Moore', 'Taylor',
              'Anderson', 'Thomas', 'Jackson', 'White', 'Harris', 'Martin', 'Thompson', 'Garcia', 'Martinez', 'Robinson'];
$streets = ['Main St', 'First Ave', 'Second Blvd', 'Oak Street', 'Elm Avenue', 'Maple Drive', 'Cedar Lane',
            'Park Road', 'Lake Street', 'Hill Avenue', 'Forest Drive', 'River Road', 'Mountain View', 'Valley Lane'];
$cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio',
           'San Diego', 'Dallas', 'San Jose', 'Austin', 'Jacksonville', 'Fort Worth', 'Columbus'];

$totalOrders = 0;
$currentDate = clone $startDate;

echo "\nGenerating orders from " . $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d') . "\n\n";

while ($currentDate <= $endDate) {
    $dayOfWeek = $currentDate->format('w');
    $month = $currentDate->format('n');
    
    // Generate 5-10 orders per day, with more on weekends and holidays
    $baseOrders = rand(5, 8);
    
    // More orders on weekends
    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
        $baseOrders += rand(2, 4);
    }
    
    // More orders during holiday seasons (November-December, May-July)
    if (in_array($month, [11, 12, 5, 6, 7])) {
        $baseOrders += rand(1, 3);
    }
    
    $ordersToday = $baseOrders;
    
    echo "Creating " . $ordersToday . " orders for " . $currentDate->format('Y-m-d') . "...\n";
    
    for ($i = 0; $i < $ordersToday; $i++) {
        // Random time during the day (8 AM to 10 PM)
        $hour = rand(8, 22);
        $minute = rand(0, 59);
        $second = rand(0, 59);
        $orderDateTime = clone $currentDate;
        $orderDateTime->setTime($hour, $minute, $second);
        
        // 70% existing customers, 30% guests
        $useExistingCustomer = !empty($customerArray) && rand(1, 100) <= 70;
        
        if ($useExistingCustomer) {
            $customer = $customerArray[array_rand($customerArray)];
            $customerId = $customer->getId();
            $customerNumber = $customer->getCustomerNumber();
            $email = $customer->getEmail();
            $firstName = $customer->getFirstName();
            $lastName = $customer->getLastName();
        } else {
            $customerId = null;
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $email = strtolower($firstName . '.' . $lastName . rand(100, 999) . '@example.com');
            $customerNumber = 'GUEST-' . rand(100000, 999999);
        }
        
        // Random number of products (1-8 items)
        $itemCount = rand(1, 8);
        $orderLineItems = [];
        $totalNetPrice = 0;
        $totalGrossPrice = 0;
        
        for ($j = 0; $j < $itemCount; $j++) {
            $product = $productArray[array_rand($productArray)];
            $quantity = rand(1, 5);
            
            // Get product price
            $productPrices = $product->getPrice();
            if ($productPrices && $productPrices->count() > 0) {
                $productPrice = $productPrices->first();
                $unitPrice = $productPrice->getGross();
            } else {
                $unitPrice = rand(10, 500) + (rand(0, 99) / 100);
            }
            
            $lineItemTotal = $unitPrice * $quantity;
            $lineItemNetTotal = $lineItemTotal / 1.19; // Assuming 19% tax
            
            $totalGrossPrice += $lineItemTotal;
            $totalNetPrice += $lineItemNetTotal;
            
            $lineItemId = Uuid::randomHex();
            
            $orderLineItems[] = [
                'id' => $lineItemId,
                'identifier' => $lineItemId,
                'productId' => $product->getId(),
                'referencedId' => $product->getId(),
                'quantity' => $quantity,
                'label' => $product->getTranslation('name'),
                'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                'position' => $j + 1,
                'price' => [
                    'unitPrice' => $unitPrice,
                    'totalPrice' => $lineItemTotal,
                    'quantity' => $quantity,
                    'calculatedTaxes' => [
                        [
                            'tax' => $lineItemTotal - $lineItemNetTotal,
                            'taxRate' => 19,
                            'price' => $lineItemTotal
                        ]
                    ],
                    'taxRules' => [
                        [
                            'taxRate' => 19,
                            'percentage' => 100
                        ]
                    ]
                ]
            ];
        }
        
        // Add shipping costs
        $shippingCosts = rand(0, 100) <= 80 ? 4.99 : 0; // 80% orders have shipping, 20% free shipping
        $shippingNetCosts = $shippingCosts / 1.19;
        
        $totalGrossPrice += $shippingCosts;
        $totalNetPrice += $shippingNetCosts;
        
        // Random payment and shipping methods
        $paymentMethod = $paymentArray[array_rand($paymentArray)];
        $shippingMethod = $shippingArray[array_rand($shippingArray)];
        $country = $countryArray[array_rand($countryArray)];
        
        // Create order
        $orderId = Uuid::randomHex();
        $addressId = Uuid::randomHex();
        
        // Determine order state (80% completed, 10% open, 5% paid, 5% cancelled)
        $stateRandom = rand(1, 100);
        if ($stateRandom <= 80 && $completedState) {
            $orderState = $completedState;
        } elseif ($stateRandom <= 90) {
            $orderState = $openState;
        } elseif ($stateRandom <= 95 && $paidState) {
            $orderState = $paidState;
        } elseif ($cancelledState) {
            $orderState = $cancelledState;
        } else {
            $orderState = $openState;
        }
        
        $orderData = [
            'id' => $orderId,
            'orderNumber' => date('Ymd', $orderDateTime->getTimestamp()) . '-' . str_pad($totalOrders + 1, 6, '0', STR_PAD_LEFT),
            'salesChannelId' => $salesChannel->getId(),
            'currencyId' => $currency->getId(),
            'currencyFactor' => 1.0,
            'price' => [
                'netPrice' => $totalNetPrice,
                'totalPrice' => $totalGrossPrice,
                'calculatedTaxes' => [
                    [
                        'tax' => $totalGrossPrice - $totalNetPrice,
                        'taxRate' => 19,
                        'price' => $totalGrossPrice
                    ]
                ],
                'taxRules' => [
                    [
                        'taxRate' => 19,
                        'percentage' => 100
                    ]
                ],
                'positionPrice' => $totalGrossPrice,
                'taxStatus' => CartPrice::TAX_STATE_GROSS,
                'rawTotal' => $totalGrossPrice
            ],
            'orderDateTime' => $orderDateTime->format('Y-m-d H:i:s'),
            'shippingCosts' => [
                'unitPrice' => $shippingCosts,
                'totalPrice' => $shippingCosts,
                'quantity' => 1,
                'calculatedTaxes' => [
                    [
                        'tax' => $shippingCosts - $shippingNetCosts,
                        'taxRate' => 19,
                        'price' => $shippingCosts
                    ]
                ],
                'taxRules' => [
                    [
                        'taxRate' => 19,
                        'percentage' => 100
                    ]
                ]
            ],
            'stateId' => $orderState->getId(),
            'paymentMethodId' => $paymentMethod->getId(),
            'lineItems' => $orderLineItems,
            'deliveries' => [
                [
                    'stateId' => $orderState->getId(),
                    'shippingMethodId' => $shippingMethod->getId(),
                    'shippingCosts' => [
                        'unitPrice' => $shippingCosts,
                        'totalPrice' => $shippingCosts,
                        'quantity' => 1,
                        'calculatedTaxes' => [
                            [
                                'tax' => $shippingCosts - $shippingNetCosts,
                                'taxRate' => 19,
                                'price' => $shippingCosts
                            ]
                        ],
                        'taxRules' => [
                            [
                                'taxRate' => 19,
                                'percentage' => 100
                            ]
                        ]
                    ],
                    'shippingDateEarliest' => $orderDateTime->format('Y-m-d H:i:s'),
                    'shippingDateLatest' => $orderDateTime->format('Y-m-d H:i:s'),
                    'shippingOrderAddressId' => $addressId
                ]
            ],
            'orderCustomer' => [
                'email' => $email,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'customerNumber' => $customerNumber,
                'customerId' => $customerId
            ],
            'billingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'street' => rand(1, 999) . ' ' . $streets[array_rand($streets)],
                    'city' => $cities[array_rand($cities)],
                    'zipcode' => (string)rand(10000, 99999),
                    'countryId' => $country->getId()
                ]
            ]
        ];
        
        try {
            $orderRepository->create([$orderData], $context);
            $totalOrders++;
            
            if ($totalOrders % 100 == 0) {
                echo "Created $totalOrders orders so far...\n";
            }
        } catch (\Exception $e) {
            echo "Error creating order: " . $e->getMessage() . "\n";
        }
    }
    
    // Move to next day
    $currentDate->modify('+1 day');
}

echo "\n\nSuccessfully created $totalOrders orders!\n";
echo "Orders span from " . $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d') . "\n";