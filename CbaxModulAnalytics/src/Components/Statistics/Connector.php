<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;

use Cbax\ModulAnalytics\Bootstrap\Database;
use Cbax\ModulAnalytics\Components\Base;

class Connector
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $languageRepository,
        private readonly EntityRepository $productRepository,
        private readonly EntityRepository $customerRepository,
        private readonly EntityRepository $propertyGroupOptionRepository,
        private readonly EntityRepository $searchRepository,
        private readonly EntityRepository $productStreamRepository,
        private readonly EntityRepository $productManufacturerRepository,
        private readonly EntityRepository $productImpressionRepository,
        private readonly EntityRepository $visitorsRepository,
        private readonly EntityRepository $refererRepository,
        private readonly EntityRepository $categoryImpressionsRepository,
        private readonly EntityRepository $categoryRepository,
        private readonly EntityRepository $manufacturerImpressionsRepository,
        private readonly EntityRepository $crossSellingRepository,
        private readonly EntityRepository $salesChannelRepository,
        private readonly ProductStreamBuilder $productStreamBuilder,
        private readonly Connection $connection
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context, string $statistics): array
    {
        //Lexikon, CrossSelling extra, da Plugins Voraussetzung ////
        if ($statistics === 'LexiconImpressions') {
            return $this->getLexiconImpressions($parameters, $context);
        }

        if ($statistics === 'CrossSelling') {
            return $this->getCrossSelling($parameters, $context);
        }
        /////////////////////////////////////////////////////////////

        $getStatisticsData = match($statistics) {
            'SalesByAccountTypes'   => new SalesByAccountTypes($this->base, $this->orderRepository),
            'OrdersCountAll'        => new OrdersCountAll($this->base, $this->orderRepository, $this->connection),
            'QuickOverview'         => new QuickOverview($this->base, $this->orderRepository, $this->customerRepository, $this->connection),
            'SalesAll'              => new SalesAll($this->base, $this->orderRepository),
            'SalesAllInvoice'       => new SalesAllInvoice($this->base, $this->orderRepository),
            'SalesAllPwreturn'      => new SalesAllPwreturn($this->base, $this->orderRepository, $this->connection),
            'SalesByMonth'          => new SalesByMonth($this->base, $this->orderRepository),
            'SalesByMonthPwreturn'  => new SalesByMonthPwreturn($this->base, $this->orderRepository, $this->connection),
            'SalesByMonthInvoice'   => new SalesByMonthInvoice($this->base, $this->orderRepository),
            'SalesByQuarter'        => new SalesByQuarter($this->base, $this->orderRepository),
            'SalesByQuarterInvoice' => new SalesByQuarterInvoice($this->base, $this->orderRepository),
            'SalesByQuarterPwreturn' => new SalesByQuarterPwreturn($this->base, $this->orderRepository, $this->connection),
            'SalesByPayment'        => new SalesByPayment($this->base, $this->orderRepository),
            'SalesByShipping'       => new SalesByShipping($this->base, $this->orderRepository),
            'SalesByManufacturer'   => new SalesByManufacturer($this->base, $this->productManufacturerRepository, $this->connection),
            'SalesByProducts'       => new SalesByProducts($this->base, $this->connection, $this->orderRepository, $this->productManufacturerRepository),
            'CountByProducts'       => new CountByProducts($this->base, $this->orderRepository, $this->productRepository),
            'SalesByCountry'        => new SalesByCountry($this->base, $this->orderRepository),
            'SalesByBillingCountry' => new SalesByBillingCountry($this->base, $this->orderRepository),
            'SalesByBillingCountryInvoice' => new SalesByBillingCountryInvoice($this->base, $this->orderRepository),
            'SalesByLanguage'       => new SalesByLanguage($this->base, $this->orderRepository, $this->languageRepository),
            'SalesBySaleschannels'  => new SalesBySaleschannels($this->base, $this->orderRepository),
            'SalesByAffiliates'     => new SalesByAffiliates($this->base, $this->orderRepository),
            'SalesByCampaign'       => new SalesByCampaign($this->base, $this->orderRepository),
            'SalesByCustomergroups' => new SalesByCustomergroups($this->base, $this->orderRepository),
            'SalesByWeekdays'       => new SalesByWeekdays($this->base, $this->orderRepository),
            'SalesByTime'           => new SalesByTime($this->base, $this->orderRepository),
            'OrdersByStatus'        => new OrdersByStatus($this->base, $this->orderRepository),
            'ProductLowInstock'     => new ProductLowInstock($this->base),
            'ProductHighInstock'    => new ProductHighInstock($this->base),
            'SalesByPromotion'      => new SalesByPromotion($this->base, $this->connection),
            'SalesByPromotionOthers'=> new SalesByPromotionOthers($this->base, $this->connection),
            'ProductInactiveWithInstock' => new ProductInactiveWithInstock($this->base),
            'ProductByOrders'            => new ProductByOrders($this->base, $this->orderRepository),
            'SalesByCustomer'            => new SalesByCustomer($this->base, $this->orderRepository),
            'NewCustomersByTime'         => new NewCustomersByTime($this->base, $this->customerRepository),
            'CustomerAge'               => new CustomerAge($this->base, $this->customerRepository),
            'CustomerOnline'            => new CustomerOnline($this->base, $this->customerRepository, $this->connection),
            'ProductSoonOutstock'       => new ProductSoonOutstock($this->base, $this->orderRepository),
            'OrdersByTransactionStatus' => new OrdersByTransactionStatus($this->base, $this->orderRepository),
            'CustomerBySalutation'      => new CustomerBySalutation($this->base, $this->customerRepository),
            'OrdersByDeliveryStatus'    => new OrdersByDeliveryStatus($this->base, $this->orderRepository),
            'UnfinishedOrders'          => new UnfinishedOrders($this->base, $this->connection, $this->salesChannelRepository),
            'UnfinishedOrdersByPayment' => new UnfinishedOrdersByPayment($this->base, $this->connection, $this->salesChannelRepository),
            'UnfinishedOrdersByCart'    => new UnfinishedOrdersByCart($this->base, $this->connection, $this->salesChannelRepository),
            'CanceledOrdersByMonth'     => new CanceledOrdersByMonth($this->base, $this->orderRepository),
            'SearchTerms'               => new SearchTerms($this->base, $this->searchRepository),
            'SearchActivity'            => new SearchActivity($this->base, $this->searchRepository),
            'SearchTrends'              => new SearchTrends($this->base, $this->searchRepository),
            'SalesByDevice'             => new SalesByDevice($this->base, $this->connection),
            'SalesByOs'                 => new SalesByOs($this->base, $this->connection),
            'SalesByBrowser'            => new SalesByBrowser($this->base, $this->connection),
            'ProductsProfit'            => new ProductsProfit($this->base, $this->orderRepository),
            'ProductsInventory'         => new ProductsInventory($this->base),
            'VariantsCompare'           => new VariantsCompare($this->base, $this->propertyGroupOptionRepository, $this->productRepository, $this->connection),
            'SalesByProductsFilter'     => new SalesByProductsFilter($this->base, $this->productRepository, $this->connection, $this->productStreamRepository, $this->productStreamBuilder),
            'SalesByCategory'           => new SalesByCategory($this->base, $this->productRepository, $this->connection, $this->productStreamRepository, $this->productStreamBuilder),
            'CategoryCompare'           => new CategoryCompare($this->base, $this->productRepository, $this->connection, $this->productStreamRepository, $this->productStreamBuilder),
            'ProductImpressions'        => new ProductImpressions($this->base, $this->productImpressionRepository, $this->connection),
            'Visitors'                  => new Visitors($this->base, $this->visitorsRepository),
            'VisitorImpressions'        => new VisitorImpressions($this->base, $this->visitorsRepository),
            'Referer'                   => new Referer($this->base, $this->refererRepository),
            'CategoryImpressions'       => new CategoryImpressions($this->base, $this->categoryImpressionsRepository, $this->categoryRepository),
            'ManufacturerImpressions'   => new ManufacturerImpressions($this->base, $this->manufacturerImpressionsRepository, $this->productManufacturerRepository),
            'SingleProduct'             => new SingleProduct($this->base, $this->productImpressionRepository, $this->orderRepository, $this->productRepository),
            'SalesByTaxrate'            => new SalesByTaxrate($this->base, $this->connection),
            'SalesBySalutation'         => new SalesBySalutation($this->base, $this->orderRepository),
            'SalesByCurrency'           => new SalesByCurrency($this->base, $this->orderRepository),
            'ConversionAll'             => new ConversionAll($this->base, $this->orderRepository, $this->visitorsRepository),
            'ConversionByMonth'         => new ConversionByMonth($this->base, $this->orderRepository, $this->visitorsRepository),
            'SalesByProductsPwreturn'   => new SalesByProductsPwreturn($this->base, $this->connection, $this->orderRepository, $this->productManufacturerRepository),
            'PickwareReturns'           => new PickwareReturns($this->base, $this->connection, $this->productManufacturerRepository),

            'default'                   => new QuickOverview($this->base, $this->orderRepository, $this->customerRepository, $this->connection)
        };

        return $getStatisticsData->getStatisticsData($parameters, $context);

    }

    // geklickte Lexikon Links
    public function getLexiconImpressions(array $parameters, Context $context): array
    {
        if (Database::tableExist('cbax_lexicon_entry', $this->connection)) {
            try {
                $getStatisticsData = new LexiconImpressions($this->base, $this->connection);

                return $getStatisticsData->getStatisticsData($parameters, $context);

            } catch (\Exception) {
                return ["success" => false];
            }

        } else {

            return ["success" => false];
        }
    }

    // Cross-Selling Statistics for a single product
    public function getCrossSelling(array $parameters, Context $context): array
    {
        if (
            Database::tableExist('cbax_cross_selling_also_bought', $this->connection) &&
            Database::tableExist('cbax_cross_selling_also_viewed', $this->connection)
        ) {
            try {
                $getStatisticsData = new CrossSelling($this->base, $this->productRepository, $this->crossSellingRepository, $this->connection, $this->productStreamBuilder);

                return $getStatisticsData->getStatisticsData($parameters, $context);

            } catch (\Exception) {
                return ["success" => false];
            }

        } else {

            return ["success" => false];
        }
    }

    public function getProductsGridData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocaleLanguage'], $context);

        return ProductsGridData::getProductsGridData($parameters, $context, $languageId, $this->connection, $this->base);
    }

}

