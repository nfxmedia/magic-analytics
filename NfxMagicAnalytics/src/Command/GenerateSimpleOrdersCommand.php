<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\Command;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Cart\Order\OrderConversionContext;
use Shopware\Core\Checkout\Cart\Processor;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'nfx:generate-simple-orders',
    description: 'Generate simple demo orders using cart service',
)]
class GenerateSimpleOrdersCommand extends Command
{
    public function __construct(
        private EntityRepository $orderRepository,
        private EntityRepository $customerRepository,
        private EntityRepository $productRepository,
        private EntityRepository $salesChannelRepository,
        private CartService $cartService,
        private OrderConverter $orderConverter,
        private $salesChannelContextFactory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('start-date', null, InputOption::VALUE_REQUIRED, 'Start date (Y-m-d)', '2024-01-01')
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED, 'End date (Y-m-d)', '2025-07-15')
            ->addOption('orders-per-day', null, InputOption::VALUE_REQUIRED, 'Orders per day', '5');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $startDate = new \DateTime($input->getOption('start-date'));
        $endDate = new \DateTime($input->getOption('end-date'));
        $ordersPerDay = (int) $input->getOption('orders-per-day');
        
        $context = Context::createDefaultContext();
        
        // Get necessary data
        $customers = $this->getCustomers($context);
        if (empty($customers)) {
            $io->error('No customers found. Please create some customers first.');
            return Command::FAILURE;
        }
        
        $products = $this->getProducts($context);
        if (empty($products)) {
            $io->error('No products found. Please create some products first.');
            return Command::FAILURE;
        }
        
        $salesChannel = $this->getDefaultSalesChannel($context);
        if (!$salesChannel) {
            $io->error('No sales channel found.');
            return Command::FAILURE;
        }
        
        $totalOrders = 0;
        $currentDate = clone $startDate;
        $totalDays = $startDate->diff($endDate)->days + 1;
        
        $io->progressStart($totalDays);
        
        while ($currentDate <= $endDate) {
            for ($i = 0; $i < $ordersPerDay; $i++) {
                try {
                    $customer = $customers[array_rand($customers)];
                    
                    // Create sales channel context for customer
                    $salesChannelContext = $this->salesChannelContextFactory->create(
                        Uuid::randomHex(),
                        $salesChannel->getId(),
                        [
                            SalesChannelContextService::CUSTOMER_ID => $customer->getId(),
                        ]
                    );
                    
                    // Create cart
                    $cart = new Cart(Uuid::randomHex());
                    
                    // Add random products
                    $numProducts = random_int(1, 3);
                    for ($j = 0; $j < $numProducts; $j++) {
                        $product = $products[array_rand($products)];
                        $quantity = random_int(1, 3);
                        
                        $lineItem = new LineItem(
                            $product->getId(),
                            LineItem::PRODUCT_LINE_ITEM_TYPE,
                            $product->getId(),
                            $quantity
                        );
                        
                        $cart->add($lineItem);
                    }
                    
                    // Calculate cart
                    $cart = $this->cartService->recalculate($cart, $salesChannelContext);
                    
                    // Convert to order
                    $orderId = Uuid::randomHex();
                    $conversionContext = new OrderConversionContext(
                        $salesChannelContext->getContext(),
                        $salesChannelContext,
                        []
                    );
                    $order = $this->orderConverter->convertToOrder($cart, $salesChannelContext, $conversionContext);
                    
                    // Override order date
                    $order['orderDateTime'] = $currentDate->format('Y-m-d H:i:s');
                    
                    // Create order
                    $this->orderRepository->create([$order], $salesChannelContext->getContext());
                    $totalOrders++;
                    
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
    
    private function getCustomers(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit(100);
        $criteria->addFilter(new EqualsFilter('active', true));
        
        $result = $this->customerRepository->search($criteria, $context);
        return array_values($result->getElements());
    }
    
    private function getProducts(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('available', true));
        $criteria->setLimit(50);
        
        $result = $this->productRepository->search($criteria, $context);
        return array_values($result->getElements());
    }
    
    private function getDefaultSalesChannel(Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->setLimit(1);
        
        return $this->salesChannelRepository->search($criteria, $context)->first();
    }
}