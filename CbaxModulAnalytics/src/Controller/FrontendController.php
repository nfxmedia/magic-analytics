<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Attribute\Route;

use Shopware\Storefront\Controller\StorefrontController;
//use Shopware\Core\Framework\Routing\Annotation\RouteScope;
//use Shopware\Storefront\Framework\Cache\Annotation\HttpCache;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

use Cbax\ModulAnalytics\Components\DetectionHelper;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class FrontendController extends StorefrontController
{
    const CONFIG_PATH = 'CbaxModulAnalytics.config';
    private $config = null;

    public function __construct(private readonly Connection $connection)
    {

    }

    #[Route(path: "/widgets/cbax/analytics/visitors/{controller};{parameter1};{parameter2};{isNew}", name: "frontend.cbax.analytics.visitors", defaults: ['XmlHttpRequest' => true, '_httpCache' => false], methods: ['GET'])]
    public function updateVisitors(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        if (empty($_SERVER)) {
            return new Response();
        }
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return new Response();
        }
        if (!$request->hasSession()) {
            return new Response();
        }

        $httpUserAgent = (string)$_SERVER['HTTP_USER_AGENT'];
        if (DetectionHelper::botDetected($httpUserAgent)) {
            return new Response();
        }

        $salesChannelId = $salesChannelContext->getSalesChannelID();
        $this->config = $this->config ?? $this->getSystemConfigService()->get(self::CONFIG_PATH, $salesChannelId);
        if (empty($this->config['recordSearch']) && empty($this->config['recordVisitors'])) {
            return new Response();
        }
        if (DetectionHelper::ipIsBlacklisted($request->getClientIp(), $this->config)) {
            return new Response();
        }

        $controller = $request->attributes->get('controller') ?? null;
        $parameter2 = $request->attributes->get('parameter2') ?? null;
        $parameter1 = $request->attributes->get('parameter1') ?? '';
        //isNew: Neukunde 1, wiederkehrend 0
        $isNew = (int)($request->attributes->get('isNew') ?? 0);
        $parameter1 = rawurldecode($parameter1);

        if (empty($parameter1) || empty($controller)) {
            return new Response();
        }

        //visitor = 1 setzen für CustomerOnline Statistik
        $request->getSession()->set('visitor', 1);

        $salesChannelIdBytes = Uuid::fromHexToBytes($salesChannelId);
        $date = (new \DateTime())->format(Defaults::STORAGE_DATE_FORMAT);
        $deviceType = DetectionHelper::getDeviceType($httpUserAgent);
        $createdAt = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $customerGroupIdBytes = $this->getCustomerGroupId($salesChannelContext);

        if (!empty($this->config['recordVisitors'])) {
            if ($controller !== 'Search') {
                $this->updateVisitorCount($request, $salesChannelIdBytes, $deviceType, $date, $createdAt, $isNew);
            }
            if ($controller === 'Navigation') {
                $this->updateCategoryImpressions($parameter1, $salesChannelIdBytes, $customerGroupIdBytes, $deviceType, $date, $createdAt);
            } elseif ($controller === 'Product') {
                $this->updateProductImpressions($parameter1, $parameter2, $salesChannelIdBytes, $customerGroupIdBytes, $deviceType, $date, $createdAt);
            }
        }

        if (!empty($this->config['recordSearch']) && $controller === 'Search') {
            $searchResults = (int)$parameter2;
            $this->updateSearchTerms($parameter1, $searchResults, $salesChannelIdBytes, $createdAt);
        }

        return new Response();
    }

    private function updateSearchTerms(string $searchTerm, int $searchResults, string $salesChannelIdBytes, string $createdAt): void
    {
        $randomId = Uuid::randomBytes();
        try {
            $this->connection->executeStatement('
            INSERT INTO `cbax_analytics_search`
                (`id`, `search_term`, `results`, `sales_channel_id`, `created_at`)
            VALUES
                (:id, :search_term, :results, :sales_channel_id, :created_at);',
                [
                    'id' => $randomId,
                    'search_term' => $searchTerm,
                    'results' => $searchResults,
                    'sales_channel_id' => $salesChannelIdBytes,
                    'created_at' => $createdAt
                ]
            );

        } catch(\Exception) {

        }
    }

    private function updateCategoryImpressions(string $categoryId, string $salesChannelIdBytes, ?string $customerGroupIdBytes, string $deviceType, string $date, string $createdAt): void
    {
        $randomId = Uuid::randomBytes();
        try {
            $this->connection->executeStatement('
                INSERT INTO `cbax_analytics_category_impressions`
                    (`id`, `category_id`, `sales_channel_id`, `customer_group_id`, `date`, `impressions`, `device_type`, `created_at`)
                VALUES
                    (:id, :category_id, :sales_channel_id, :customer_group_id, :date, :impressions, :device_type, :created_at)
                    ON DUPLICATE KEY UPDATE impressions=impressions+1;',
                [
                    'id' => $randomId,
                    'category_id' => Uuid::fromHexToBytes($categoryId),
                    'sales_channel_id' => $salesChannelIdBytes,
                    'customer_group_id' => $customerGroupIdBytes,
                    'date' => $date,
                    'impressions' => 1,
                    'device_type' => $deviceType,
                    'created_at' => $createdAt
                ]
            );

        } catch(\Exception) {

        }
    }

    private function updateProductImpressions(string $productId, string $manufacturerId, string $salesChannelIdBytes, ?string $customerGroupIdBytes, string $deviceType, string $date, string $createdAt): void
    {
        $randomId = Uuid::randomBytes();
        try {
            $this->connection->executeStatement('
                INSERT INTO `cbax_analytics_product_impressions`
                    (`id`, `product_id`, `sales_channel_id`, `customer_group_id`, `date`, `impressions`, `device_type`, `created_at`)
                VALUES
                    (:id, :product_id, :sales_channel_id, :customer_group_id, :date, :impressions, :device_type, :created_at)
                    ON DUPLICATE KEY UPDATE impressions=impressions+1;',
                [
                    'id' => $randomId,
                    'product_id' => Uuid::fromHexToBytes($productId),
                    'sales_channel_id' => $salesChannelIdBytes,
                    'customer_group_id' => $customerGroupIdBytes,
                    'date' => $date,
                    'impressions' => 1,
                    'device_type' => $deviceType,
                    'created_at' => $createdAt
                ]
            );
        } catch(\Exception) {

        }

        if (empty($manufacturerId)) return;

        $randomId = Uuid::randomBytes();
        try {
            $this->connection->executeStatement('
                INSERT INTO `cbax_analytics_manufacturer_impressions`
                    (`id`, `manufacturer_id`, `sales_channel_id`, `customer_group_id`, `date`, `impressions`, `device_type`, `created_at`)
                VALUES
                    (:id, :manufacturer_id, :sales_channel_id, :customer_group_id, :date, :impressions, :device_type, :created_at)
                    ON DUPLICATE KEY UPDATE impressions=impressions+1;',
                [
                    'id' => $randomId,
                    'manufacturer_id' => Uuid::fromHexToBytes($manufacturerId),
                    'sales_channel_id' => $salesChannelIdBytes,
                    'customer_group_id' => $customerGroupIdBytes,
                    'date' => $date,
                    'impressions' => 1,
                    'device_type' => $deviceType,
                    'created_at' => $createdAt
                ]
            );
        } catch(\Exception) {

        }
    }

    private function updateVisitorCount(Request $request, $salesChannelIdBytes, string $deviceType, string $date, string $createdAt, int $isNewVisitor): void
    {
        $referer = $this->getDomainString($request->headers->get('cbax-referer'));
        $host = $this->getDomainString($request->getHttpHost());

        if ($isNewVisitor == 1) {
            $randomId = Uuid::randomBytes();
            try {
                $this->connection->executeStatement('
                INSERT INTO `cbax_analytics_visitors`
                    (`id`, `sales_channel_id`, `date`,`page_impressions`, `unique_visits`, `device_type`, `created_at`)
                VALUES
                    (:id, :sales_channel_id, :date, :page_impressions, :unique_visits, :device_type, :created_at)
                    ON DUPLICATE KEY UPDATE page_impressions=page_impressions+1, unique_visits=unique_visits+1;',
                    [
                        'id' => $randomId,
                        'sales_channel_id' => $salesChannelIdBytes,
                        'date' => $date,
                        'page_impressions' => 1,
                        'unique_visits' => 1,
                        'device_type' => $deviceType,
                        'created_at' => $createdAt
                    ]
                );
            } catch(\Exception) {

            }

        } else {

            try {
                $this->connection->executeStatement('
                UPDATE `cbax_analytics_visitors` SET page_impressions=page_impressions+1
                WHERE `sales_channel_id`=? AND `date`=? AND `device_type`=?;',

                    [$salesChannelIdBytes, $date, $deviceType]

                );
            } catch(\Exception) {

            }
        }

        if ($isNewVisitor && !empty($referer) && $referer != $host) {
            $randomId = Uuid::randomBytes();
            try {
                $this->connection->executeStatement('
                INSERT INTO `cbax_analytics_referer`
                    (`id`, `date`,`referer`, `sales_channel_id`, `counted`, `device_type`, `created_at`)
                VALUES
                    (:id, :date, :referer, :sales_channel_id, :counted, :device_type, :created_at)
                    ON DUPLICATE KEY UPDATE counted=counted+1;',
                    [
                        'id' => $randomId,
                        'date' => $date,
                        'referer' => $referer,
                        'sales_channel_id' => $salesChannelIdBytes,
                        'counted' => 1,
                        'device_type' => $deviceType,
                        'created_at' => $createdAt
                    ]
                );
            } catch(\Exception) {

            }
        }
    }

    private function getCustomerGroupId(SalesChannelContext $salesChannelContext): ?string
    {
        $customerId = $salesChannelContext->getCustomerId();

        if (!empty($customerId) && !empty($salesChannelContext->getCurrentCustomerGroup())) {
            return !empty($salesChannelContext->getCurrentCustomerGroup()->getId()) ?
                Uuid::fromHexToBytes($salesChannelContext->getCurrentCustomerGroup()->getId()) :
                null;

        } else {

            return null;
        }
    }

    private function getDomainString(?string $url): ?string
    {
        if (empty($url)) {
            return '';
        }

        $domainStr = str_replace(['http://', 'https://', 'www.'], '', $url);
        $domainArr = explode('/', $domainStr);

        return $domainArr[0];
    }

}
