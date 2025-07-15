<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\Command;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'nfx:generate-demo-orders',
    description: 'Generate demo orders for testing Magic Analytics',
)]
class GenerateDemoOrdersCommand extends Command
{
    private array $stateCache = [];
    
    public function __construct(
        private EntityRepository $orderRepository,
        private EntityRepository $customerRepository,
        private EntityRepository $productRepository,
        private EntityRepository $salesChannelRepository,
        private EntityRepository $currencyRepository,
        private EntityRepository $countryRepository,
        private EntityRepository $paymentMethodRepository,
        private EntityRepository $shippingMethodRepository,
        private StateMachineRegistry $stateMachineRegistry,
        private EntityRepository $stateMachineStateRepository,
        private EntityRepository $salutationRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('start-date', null, InputOption::VALUE_REQUIRED, 'Start date (Y-m-d)', '2024-01-01')
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED, 'End date (Y-m-d)', '2025-07-15')
            ->addOption('min-per-day', null, InputOption::VALUE_REQUIRED, 'Minimum orders per day', '5')
            ->addOption('max-per-day', null, InputOption::VALUE_REQUIRED, 'Maximum orders per day', '15');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $startDate = new \DateTime($input->getOption('start-date'));
        $endDate = new \DateTime($input->getOption('end-date'));
        $minPerDay = (int) $input->getOption('min-per-day');
        $maxPerDay = (int) $input->getOption('max-per-day');
        
        $context = Context::createDefaultContext();
        
        // Load required data
        $customers = $this->getCustomers($context);
        $products = $this->getProducts($context);
        $salesChannels = $this->getSalesChannels($context);
        $currencies = $this->getCurrencies($context);
        $countries = $this->getCountries($context);
        $paymentMethods = $this->getPaymentMethods($context);
        $shippingMethods = $this->getShippingMethods($context);
        
        if (empty($customers)) {
            $io->error('No customers found. Please create some customers first.');
            return Command::FAILURE;
        }
        
        if (empty($products)) {
            $io->error('No products found. Please create some products first.');
            return Command::FAILURE;
        }
        
        $totalOrders = 0;
        $currentDate = clone $startDate;
        
        $io->progressStart($startDate->diff($endDate)->days + 1);
        
        while ($currentDate <= $endDate) {
            $ordersToday = random_int($minPerDay, $maxPerDay);
            
            for ($i = 0; $i < $ordersToday; $i++) {
                try {
                    $customer = $customers[array_rand($customers)];
                    $salesChannel = $salesChannels[array_rand($salesChannels)];
                    $currency = $currencies[array_rand($currencies)];
                    $country = $countries[array_rand($countries)];
                    $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
                    $shippingMethod = $shippingMethods[array_rand($shippingMethods)];
                    
                    $orderData = $this->generateOrderData(
                        $currentDate,
                        $customer,
                        $products,
                        $salesChannel,
                        $currency,
                        $country,
                        $paymentMethod,
                        $shippingMethod,
                        $totalOrders + 1
                    );
                    
                    $this->orderRepository->create([$orderData], $context);
                    $totalOrders++;
                    
                    // Randomly transition some orders to different states
                    if (random_int(1, 100) <= 80) {
                        $this->transitionOrderState($orderData['id'], $context);
                    }
                } catch (\Exception $e) {
                    $io->warning("Failed to create order: " . $e->getMessage());
                }
            }
            
            $io->progressAdvance();
            $currentDate->modify('+1 day');
        }
        
        $io->progressFinish();
        $io->success("Generated {$totalOrders} orders from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
        
        return Command::SUCCESS;
    }
    
    private function generateOrderData(
        \DateTime $orderDate,
        $customer,
        array $products,
        $salesChannel,
        $currency,
        $country,
        $paymentMethod,
        $shippingMethod,
        int $orderNumber
    ): array {
        $orderId = Uuid::randomHex();
        $lineItems = $this->generateLineItems($products, $currency);
        $price = $this->calculateOrderPrice($lineItems, $currency);
        
        return [
            'id' => $orderId,
            'orderNumber' => str_pad((string)$orderNumber, 6, '0', STR_PAD_LEFT),
            'orderDateTime' => $orderDate->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'price' => $price,
            'shippingCosts' => $this->generateShippingCosts($currency),
            'stateId' => $this->getOrderStateId(OrderStates::STATE_OPEN),
            'paymentMethodId' => $paymentMethod->getId(),
            'currencyId' => $currency->getId(),
            'currencyFactor' => $currency->getFactor(),
            'salesChannelId' => $salesChannel->getId(),
            'lineItems' => $lineItems,
            'deliveries' => [[
                'stateId' => $this->getDeliveryStateId(OrderDeliveryStates::STATE_OPEN),
                'shippingMethodId' => $shippingMethod->getId(),
                'shippingCosts' => $this->generateShippingCosts($currency),
                'shippingOrderAddress' => $this->generateAddress($country),
                'shippingDateEarliest' => $orderDate->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                'shippingDateLatest' => (clone $orderDate)->modify('+3 days')->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                'positions' => array_map(function ($lineItem) {
                    return [
                        'price' => $lineItem['price'],
                        'orderLineItemId' => $lineItem['id'],
                    ];
                }, $lineItems),
            ]],
            'orderCustomer' => [
                'email' => $customer->getEmail(),
                'salutationId' => $customer->getSalutationId(),
                'firstName' => $customer->getFirstName(),
                'lastName' => $customer->getLastName(),
                'customerId' => $customer->getId(),
            ],
            'billingAddressId' => Uuid::randomHex(),
            'addresses' => [
                $this->generateAddress($country),
            ],
            'itemRounding' => json_decode('{"decimals":2,"interval":0.01,"roundForNet":true}', true),
            'totalRounding' => json_decode('{"decimals":2,"interval":0.01,"roundForNet":true}', true),
        ];
    }
    
    private function generateLineItems(array $products, $currency): array
    {
        $lineItems = [];
        $itemCount = random_int(1, 5);
        $selectedProducts = array_rand($products, min($itemCount, count($products)));
        
        if (!is_array($selectedProducts)) {
            $selectedProducts = [$selectedProducts];
        }
        
        foreach ($selectedProducts as $productIndex) {
            $product = $products[$productIndex];
            $quantity = random_int(1, 5);
            $unitPrice = (float) random_int(1000, 50000) / 100;
            $totalPrice = $unitPrice * $quantity;
            
            $lineItems[] = [
                'id' => Uuid::randomHex(),
                'identifier' => Uuid::randomHex(),
                'productId' => $product->getId(),
                'referencedId' => $product->getId(),
                'quantity' => $quantity,
                'label' => $product->getTranslation('name') ?? 'Product',
                'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                'price' => [
                    'unitPrice' => $unitPrice,
                    'totalPrice' => $totalPrice,
                    'quantity' => $quantity,
                    'calculatedTaxes' => [],
                    'taxRules' => [],
                ],
                'priceDefinition' => [
                    'price' => $unitPrice,
                    'taxRules' => [],
                    'quantity' => $quantity,
                    'isCalculated' => false,
                    'type' => 'quantity',
                ],
                'good' => true,
            ];
        }
        
        return $lineItems;
    }
    
    private function calculateOrderPrice(array $lineItems, $currency): array
    {
        $netPrice = 0;
        $totalPrice = 0;
        
        foreach ($lineItems as $lineItem) {
            if (isset($lineItem['price']['totalPrice'])) {
                $totalPrice += $lineItem['price']['totalPrice'];
                $netPrice += $lineItem['price']['totalPrice'];
            }
        }
        
        return [
            'netPrice' => $netPrice,
            'totalPrice' => $totalPrice,
            'calculatedTaxes' => [],
            'taxRules' => [],
            'positionPrice' => $totalPrice,
            'taxStatus' => CartPrice::TAX_STATE_GROSS,
            'rawTotal' => $totalPrice,
        ];
    }
    
    private function generateShippingCosts($currency): array
    {
        $shippingCost = (float) random_int(0, 1500) / 100;
        
        return [
            'unitPrice' => $shippingCost,
            'totalPrice' => $shippingCost,
            'calculatedTaxes' => [],
            'taxRules' => [],
            'quantity' => 1,
        ];
    }
    
    private function generateAddress($country): array
    {
        $streets = ['Main Street', 'Oak Avenue', 'Elm Street', 'Park Road', 'Market Square'];
        $cities = ['Berlin', 'Munich', 'Hamburg', 'Frankfurt', 'Cologne'];
        
        return [
            'id' => Uuid::randomHex(),
            'salutationId' => $this->getRandomSalutationId(),
            'firstName' => 'Test',
            'lastName' => 'Customer',
            'street' => $streets[array_rand($streets)] . ' ' . random_int(1, 100),
            'zipcode' => (string) random_int(10000, 99999),
            'city' => $cities[array_rand($cities)],
            'countryId' => $country->getId(),
        ];
    }
    
    private function transitionOrderState(string $orderId, Context $context): void
    {
        $states = [
            OrderStates::STATE_IN_PROGRESS,
            OrderStates::STATE_COMPLETED,
            OrderStates::STATE_CANCELLED,
        ];
        
        $targetState = $states[array_rand($states)];
        
        try {
            $this->stateMachineRegistry->transition(
                new Transition(
                    'order',
                    $orderId,
                    $this->getTransitionAction($targetState),
                    'stateId'
                ),
                $context
            );
        } catch (\Exception $e) {
            // Ignore transition errors
        }
    }
    
    private function getTransitionAction(string $targetState): string
    {
        return match ($targetState) {
            OrderStates::STATE_IN_PROGRESS => StateMachineTransitionActions::ACTION_PROCESS,
            OrderStates::STATE_COMPLETED => StateMachineTransitionActions::ACTION_COMPLETE,
            OrderStates::STATE_CANCELLED => StateMachineTransitionActions::ACTION_CANCEL,
            default => StateMachineTransitionActions::ACTION_PROCESS,
        };
    }
    
    private function getCustomers(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit(100);
        
        $result = $this->customerRepository->search($criteria, $context);
        return array_values($result->getElements());
    }
    
    private function getProducts(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('stock', 0)]
        ));
        $criteria->setLimit(100);
        
        $result = $this->productRepository->search($criteria, $context);
        return array_values($result->getElements());
    }
    
    private function getSalesChannels(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        
        $result = $this->salesChannelRepository->search($criteria, $context);
        return array_values($result->getElements());
    }
    
    private function getCurrencies(Context $context): array
    {
        $result = $this->currencyRepository->search(new Criteria(), $context);
        return array_values($result->getElements());
    }
    
    private function getCountries(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        
        $result = $this->countryRepository->search($criteria, $context);
        return array_values($result->getElements());
    }
    
    private function getPaymentMethods(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        
        $result = $this->paymentMethodRepository->search($criteria, $context);
        return array_values($result->getElements());
    }
    
    private function getShippingMethods(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        
        $result = $this->shippingMethodRepository->search($criteria, $context);
        return array_values($result->getElements());
    }
    
    private function getOrderStateId(string $technicalName): string
    {
        if (isset($this->stateCache['order_' . $technicalName])) {
            return $this->stateCache['order_' . $technicalName];
        }
        
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));
        $criteria->addFilter(new EqualsFilter('stateMachine.technicalName', 'order.state'));
        $criteria->addAssociation('stateMachine');
        
        $state = $this->stateMachineStateRepository->search($criteria, Context::createDefaultContext())->first();
        
        if ($state) {
            $this->stateCache['order_' . $technicalName] = $state->getId();
            return $state->getId();
        }
        
        throw new \RuntimeException('Order state not found: ' . $technicalName);
    }
    
    private function getDeliveryStateId(string $technicalName): string
    {
        if (isset($this->stateCache['delivery_' . $technicalName])) {
            return $this->stateCache['delivery_' . $technicalName];
        }
        
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));
        $criteria->addFilter(new EqualsFilter('stateMachine.technicalName', 'order_delivery.state'));
        $criteria->addAssociation('stateMachine');
        
        $state = $this->stateMachineStateRepository->search($criteria, Context::createDefaultContext())->first();
        
        if ($state) {
            $this->stateCache['delivery_' . $technicalName] = $state->getId();
            return $state->getId();
        }
        
        throw new \RuntimeException('Delivery state not found: ' . $technicalName);
    }
    
    private function getRandomSalutationId(): string
    {
        if (isset($this->stateCache['salutation'])) {
            $salutations = $this->stateCache['salutation'];
        } else {
            $criteria = new Criteria();
            $criteria->setLimit(10);
            $salutations = $this->salutationRepository->search($criteria, Context::createDefaultContext())->getIds();
            $this->stateCache['salutation'] = $salutations;
        }
        
        return $salutations[array_rand($salutations)];
    }
}