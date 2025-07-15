<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateTestOrdersCommand extends Command
{
    protected static $defaultName = 'nfx:create-test-orders';

    private EntityRepository $productRepository;
    private EntityRepository $customerRepository;
    private EntityRepository $salesChannelRepository;
    private EntityRepository $paymentMethodRepository;
    private EntityRepository $shippingMethodRepository;
    private SalesChannelContextFactory $salesChannelContextFactory;
    private CartService $cartService;
    private OrderService $orderService;

    public function __construct(
        EntityRepository $productRepository,
        EntityRepository $customerRepository,
        EntityRepository $salesChannelRepository,
        EntityRepository $paymentMethodRepository,
        EntityRepository $shippingMethodRepository,
        SalesChannelContextFactory $salesChannelContextFactory,
        CartService $cartService,
        OrderService $orderService
    ) {
        parent::__construct();
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->cartService = $cartService;
        $this->orderService = $orderService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates random test orders for analytics testing')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of orders to create', 10)
            ->addOption('date-range', 'd', InputOption::VALUE_REQUIRED, 'Days back to spread orders', 30);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = (int) $input->getOption('count');
        $dateRange = (int) $input->getOption('date-range');
        
        $io->title('Creating Test Orders for nfx MAGIC Analytics');
        
        $context = Context::createDefaultContext();
        
        // Get available products
        $products = $this->productRepository->search(
            (new Criteria())->setLimit(50)->addFilter(new EqualsFilter('active', true)),
            $context
        )->getElements();
        
        if (empty($products)) {
            $io->error('No active products found. Please ensure you have products in your shop.');
            return Command::FAILURE;
        }
        
        // Get available customers
        $customers = $this->customerRepository->search(
            (new Criteria())->setLimit(10),
            $context
        )->getElements();
        
        if (empty($customers)) {
            $io->error('No customers found. Please ensure you have customers in your shop.');
            return Command::FAILURE;
        }
        
        // Get sales channel
        $salesChannel = $this->salesChannelRepository->search(
            (new Criteria())->setLimit(1),
            $context
        )->first();
        
        if (!$salesChannel) {
            $io->error('No sales channel found.');
            return Command::FAILURE;
        }
        
        // Get payment methods
        $paymentMethods = $this->paymentMethodRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('active', true)),
            $context
        )->getElements();
        
        // Get shipping methods
        $shippingMethods = $this->shippingMethodRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('active', true)),
            $context
        )->getElements();
        
        $productArray = array_values($products);
        $customerArray = array_values($customers);
        $paymentArray = array_values($paymentMethods);
        $shippingArray = array_values($shippingMethods);
        
        $io->progressStart($count);
        
        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            try {
                // Random customer
                $customer = $customerArray[array_rand($customerArray)];
                
                // Create sales channel context
                $salesChannelContext = $this->salesChannelContextFactory->create(
                    Uuid::randomHex(),
                    $salesChannel->getId(),
                    [
                        'customerId' => $customer->getId(),
                        'paymentMethodId' => $paymentArray[array_rand($paymentArray)]->getId(),
                        'shippingMethodId' => $shippingArray[array_rand($shippingArray)]->getId(),
                    ]
                );
                
                // Create cart
                $cart = $this->cartService->createNew($salesChannelContext->getToken());
                
                // Add random products (1-5 items)
                $itemCount = rand(1, 5);
                for ($j = 0; $j < $itemCount; $j++) {
                    $product = $productArray[array_rand($productArray)];
                    $quantity = rand(1, 3);
                    
                    $lineItem = new LineItem(
                        Uuid::randomHex(),
                        LineItem::PRODUCT_LINE_ITEM_TYPE,
                        $product->getId(),
                        $quantity
                    );
                    
                    $this->cartService->add($cart, $lineItem, $salesChannelContext);
                }
                
                // Recalculate cart
                $cart = $this->cartService->recalculate($cart, $salesChannelContext);
                
                // Create order
                $orderId = $this->orderService->createOrder($cart, $salesChannelContext);
                
                // Set random order date (within date range)
                $daysBack = rand(0, $dateRange);
                $orderDate = (new \DateTime())->modify("-{$daysBack} days");
                
                // Update order date
                $this->updateOrderDate($orderId, $orderDate, $context);
                
                $created++;
                
            } catch (\Exception $e) {
                // Continue on error
            }
            
            $io->progressAdvance();
        }
        
        $io->progressFinish();
        
        $io->success(sprintf('Successfully created %d test orders!', $created));
        
        return Command::SUCCESS;
    }
    
    private function updateOrderDate(string $orderId, \DateTime $date, Context $context): void
    {
        $connection = $this->productRepository->getDefinition()->getEntityName();
        
        // Direct SQL update for order date
        $sql = "UPDATE `order` SET created_at = :date, updated_at = :date WHERE id = UNHEX(:orderId)";
        
        $connection = $this->getContainer()->get('Doctrine\DBAL\Connection');
        $connection->executeStatement($sql, [
            'date' => $date->format('Y-m-d H:i:s'),
            'orderId' => $orderId
        ]);
    }
}