<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\Connection;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

use Cbax\ModulAnalytics\Bootstrap\Database;
use Cbax\ModulAnalytics\Components\Base;

class CrossSelling implements StatisticsInterface
{
   public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $productRepository,
        private readonly EntityRepository $crossSellingRepository,
        private readonly Connection $connection,
        private readonly ProductStreamBuilder $productStreamBuilder
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $productId = $parameters['productId'];
        $alsoViewed = [];
        $alsoBought = [];
        if (empty($productId)) return [
            'success' => true,
            'productName' => '',
            'alsoViewed' => $alsoViewed,
            'alsoBought' => $alsoBought
        ];

        $productNamesArray = [];
        $productName = $this->base->getProductNameFromId($productId, $context);
        $gridLimit = (int)$parameters['config']['gridLimit'] ?? 100;

        if (Database::tableExist('cbax_cross_selling_also_viewed', $this->connection))
        {
            $sql = "SELECT LOWER(HEX(related_product_id)) as productId, viewed
                    FROM cbax_cross_selling_also_viewed
                    WHERE HEX(product_id) = ?
                    ORDER BY viewed DESC LIMIT " . $gridLimit . ";";
            $results = $this->connection->fetchAllAssociative($sql, [$productId]);

            if (!empty($results))
            {
                foreach ($results as $result)
                {
                    if (!empty($productNamesArray[$result['productId']]))
                    {
                        $name = $productNamesArray[$result['productId']];

                    } else {

                        $name = $this->base->getProductNameFromId($result['productId'], $context);
                        $productNamesArray[$result['productId']] = $name;
                    }

                    $alsoViewed[] = [
                        'productId' => $result['productId'],
                        'productName' => $name,
                        'viewed' => (int)$result['viewed'],
                        'crossSellings' => []
                    ];
                }
            }
        }

        if (Database::tableExist('cbax_cross_selling_also_bought', $this->connection))
        {
            $sql = "SELECT LOWER(HEX(related_product_id)) as productId, sales
                    FROM cbax_cross_selling_also_bought
                    WHERE HEX(product_id) = ?
                    ORDER BY sales DESC LIMIT " . $gridLimit . ";";
            $results = $this->connection->fetchAllAssociative($sql, [$productId]);

            if (!empty($results))
            {
                foreach ($results as $result)
                {
                    if (!empty($productNamesArray[$result['productId']]))
                    {
                        $name = $productNamesArray[$result['productId']];

                    } else {

                        $name = $this->base->getProductNameFromId($result['productId'], $context);
                    }

                    $alsoBought[] = [
                        'productId' => $result['productId'],
                        'productName' => $name,
                        'sales' => $result['sales'],
                        'crossSellings' => []
                    ];
                }
            }
        }

        if (count($alsoViewed) == 0 && count($alsoBought) == 0)
        {
            return [
                'success' => true,
                'productName' => $productName,
                'alsoViewed' => $alsoViewed,
                'alsoBought' => $alsoBought
            ];
        }

        // cross-sellings der Produkte ermitteln
        $criteria = new Criteria();
        $criteria->addAssociation('productStream');
        $criteria->addAssociation('assignedProducts');
        $criteria->addFilter(new EqualsFilter('productId', $productId));

        $crossSellings = $this->crossSellingRepository->search($criteria, $context)->getElements();

        $crossSellingProducts = [];
        foreach ($crossSellings as $crossSelling)
        {
            /* @var ProductCrossSellingEntity $crossSelling */
            if ($crossSelling->getType() == 'productList' &&
                !empty($crossSelling->getAssignedProducts()) &&
                $crossSelling->getAssignedProducts()->count() > 0
            )
            {
                $crossSellingProducts[$crossSelling->getTranslated()['name']] = $crossSelling->getAssignedProducts()->getProductIds();

            } elseif ($crossSelling->getType() == 'productStream' &&
                !empty($crossSelling->getProductStreamId()) &&
                !empty($crossSelling->getProductStream()) &&
                !empty($crossSelling->getProductStream()->getApiFilter())
            )
            {
                $crossSellingProducts[$crossSelling->getTranslated()['name']] = $this->getProductIdsFromStream($crossSelling, $context);
            }
        }

        foreach ($alsoViewed as &$item)
        {
            foreach ($crossSellingProducts as $key => $value)
            {
                if (in_array($item['productId'], $value))
                {
                    $item['crossSellings'][] = $key;
                }
            }
        }

        foreach ($alsoBought as &$item)
        {
            foreach ($crossSellingProducts as $key => $value)
            {
                if (in_array($item['productId'], $value))
                {
                    $item['crossSellings'][] = $key;
                }
            }
        }

        return [
            'success' => true,
            'productName' => $productName,
            'alsoViewed' => $alsoViewed,
            'alsoBought' => $alsoBought
        ];
    }

    private function getProductIdsFromStream(ProductCrossSellingEntity $crossSelling, Context $context): array
    {
        $id = $crossSelling->getProductStreamId();
        $limit = $crossSelling->getLimit() ?? 30;
        $productCriteria = new Criteria();
        $productCriteria->setLimit($limit);
        $filters = $this->productStreamBuilder->buildFilters($id, $context);
        $productCriteria->addFilter(...$filters);
        $context->setConsiderInheritance(true);

        return $this->productRepository->searchIds($productCriteria, $context)->getIds();
    }

}
