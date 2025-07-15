<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateDailyOrdersCommand extends Command
{
    protected static $defaultName = 'nfx:generate-daily-orders';
    protected static $defaultDescription = 'Generates orders for every day from 01.01.2024 to 15.07.2025';

    private EntityRepository $orderRepository;
    private EntityRepository $productRepository;
    private EntityRepository $customerRepository;
    private EntityRepository $countryRepository;
    private EntityRepository $salesChannelRepository;
    private EntityRepository $paymentMethodRepository;
    private EntityRepository $shippingMethodRepository;
    private EntityRepository $currencyRepository;
    private EntityRepository $orderStateRepository;

    public function __construct(
        EntityRepository $orderRepository,
        EntityRepository $productRepository,
        EntityRepository $customerRepository,
        EntityRepository $countryRepository,
        EntityRepository $salesChannelRepository,
        EntityRepository $paymentMethodRepository,
        EntityRepository $shippingMethodRepository,
        EntityRepository $currencyRepository,
        EntityRepository $orderStateRepository
    ) {
        parent::__construct();
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->countryRepository = $countryRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->currencyRepository = $currencyRepository;
        $this->orderStateRepository = $orderStateRepository;
    }

    protected function configure(): void
    {
        $this
            ->setName('nfx:generate-daily-orders')
            ->setDescription('Generates orders for every day from 01.01.2024 to 15.07.2025');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        
        $io->title('Generating Daily Orders for nfx MAGIC Analytics');
        
        // Get necessary data
        $io->text('Loading products...');
        $products = $this->productRepository->search(
            (new Criteria())->setLimit(500)->addFilter(new EqualsFilter('active', true)),
            $context
        )->getElements();

        if (empty($products)) {
            $io->error('No products found!');
            return Command::FAILURE;
        }

        $io->text('Loading customers...');
        $customers = $this->customerRepository->search(
            (new Criteria())->setLimit(100),
            $context
        )->getElements();

        $io->text('Loading other data...');
        $salesChannel = $this->salesChannelRepository->search(
            (new Criteria())->setLimit(1),
            $context
        )->first();

        $paymentMethods = $this->paymentMethodRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('active', true)),
            $context
        )->getElements();

        $shippingMethods = $this->shippingMethodRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('active', true)),
            $context
        )->getElements();

        $currency = $this->currencyRepository->search(
            (new Criteria())->setLimit(1),
            $context
        )->first();

        $countries = $this->countryRepository->search(
            (new Criteria())->setLimit(10),
            $context
        )->getElements();

        // Get order states
        $orderStates = $this->orderStateRepository->search(
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
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-03'); // Just test 3 days first

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
        $totalDays = $startDate->diff($endDate)->days + 1;

        $io->text("Generating orders from " . $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d'));
        $io->text("Total days: " . $totalDays);
        
        $io->progressStart($totalDays);

        while ($currentDate <= $endDate) {
            $dayOfWeek = $currentDate->format('w');
            $month = (int)$currentDate->format('n');
            
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
                        'label' => $product->getTranslation('name') ?: 'Product',
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
                    'orderNumber' => date('Ymd', $orderDateTime->getTimestamp()) . '-' . str_pad((string)($totalOrders + 1), 6, '0', STR_PAD_LEFT),
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
                    'itemRounding' => [
                        'decimals' => 2,
                        'interval' => 0.01,
                        'roundForNet' => true
                    ],
                    'totalRounding' => [
                        'decimals' => 2,
                        'interval' => 0.01,
                        'roundForNet' => true
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
                    $this->orderRepository->create([$orderData], $context);
                    $totalOrders++;
                } catch (\Exception $e) {
                    $io->error('Failed to create order: ' . $e->getMessage());
                }
            }
            
            $io->progressAdvance();
            
            // Move to next day
            $currentDate->modify('+1 day');
        }

        $io->progressFinish();
        
        $io->success("Successfully created $totalOrders orders!");
        $io->text("Orders span from " . $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d'));
        
        return Command::SUCCESS;
    }
}