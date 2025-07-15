<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\ArrayParameterType;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

use Cbax\ModulAnalytics\Components\Statistics\StatisticsHelper;

class Base
{
    const EXPORT_FILE_NAME = 'cbax-statistics.csv';
    private $config = null;

    public function __construct(
        private readonly EntityRepository $stateMachineRepository,
        private readonly EntityRepository $stateMachineStateRepository,
        private readonly EntityRepository $localeRepository,
        private readonly EntityRepository $languageRepository,
        private readonly EntityRepository $productRepository,
        private readonly EntityRepository $propertyGroupOptionRepository,
        private readonly ConfigReaderHelper $configReaderHelper,
        private readonly FilesystemOperator $fileSystemPrivate,
        private readonly Connection $connection
    ) {

    }

    public function getProductNameFromId(string $prodId, Context $context): string
    {
        $context->setConsiderInheritance(true);
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addAssociation('options');
        $criteria->getAssociation('options')
            ->addSorting(new FieldSorting('groupId'))
            ->addSorting(new FieldSorting('id'));
        $criteria->addFilter(new EqualsFilter('id', $prodId));
        $prod = $this->productRepository->search($criteria, $context)->first();

        $name = '';
        $optionNames = '';
        if (!empty($prod))
        {
            $name = $prod->getName() ?? $prod->getTranslated()['name'];
            if (!empty($prod->getParentId()))
            {
                $options = $prod->getOptions()->getElements();
                foreach($options as $option) {
                    $optionName = $option->getTranslated()['name'];
                    if (!empty($optionName)) {
                        $optionNames .= ' ' . $optionName;
                    }
                }
            }
        }

        return $name . $optionNames;
    }

    public function getBaseParameters(Request $request): array
    {
        $parameters = [];
        $params = $request->request->all()['parameters'] ?? [];

        $this->config ??= $this->configReaderHelper->getConfig();
        $parameters['config'] = $this->config;
        $parameters['salesChannelIds'] = $params['salesChannelIds'] ?? [];
        $parameters['customerGroupIds'] = $params['customerGroupIds'] ?? [];
        $parameters['productSearchIds'] = $params['productSearchIds'] ?? [];
        $parameters['manufacturerSearchIds'] = $params['manufacturerSearchIds'] ?? [];
        $parameters['affiliateCodes'] = $params['affiliateCodes'] ?? [];
        $parameters['notaffiliateCodes'] = $params['notaffiliateCodes'] ?? [];
        $parameters['campaignCodes'] = $params['campaignCodes'] ?? [];
        $parameters['promotionCodes'] = $params['promotionCodes'] ?? [];
        $parameters['adminLocalLanguage'] = trim($params['adminLocaleLanguage'] ?? '');
        $parameters['format'] = trim($params['format'] ?? '');
        $parameters['labels'] = trim($params['labels'] ?? '');
        $parameters['sortBy'] = $params['sortBy'] ?? null;
        $parameters['sortDirection'] = $params['sortDirection'] ?? null;

        $dates = $this->getDates($params);
        $parameters['startDate'] = $dates['startDate'];
        $parameters['endDate'] = $dates['endDate'];
        $parameters['userTimeZone'] = $params['userTimeZone'] ?? 'UTC';
        $parameters['showVariantParent'] = $params['showVariantParent'] ?? false;

        $blacklistedStatesIds = [];
        $blacklistedStatesIds['transaction'] = !empty($parameters['config']['blacklistedTransactionStates']) ? $parameters['config']['blacklistedTransactionStates'] : [];
        $blacklistedStatesIds['delivery'] = !empty($parameters['config']['blacklistedDeliveryStates']) ? $parameters['config']['blacklistedDeliveryStates'] : [];
        $blacklistedStatesIds['order'] = !empty($parameters['config']['blacklistedOrderStates']) ? $parameters['config']['blacklistedOrderStates'] : [];

        $parameters['blacklistedStatesIds'] = $blacklistedStatesIds;

        return $parameters;
    }

    public function getDataFromAggregations(EntitySearchResult $result, string $mainValue, string $termAggregation, ?string $entityAggregation = null): array
    {
        $aggregation = $result->getAggregations()->get($termAggregation);
        if (!empty($entityAggregation)) {
            $entityElements = $result->getAggregations()->get($entityAggregation)->getEntities()->getElements();
        }

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket)
        {
            if ($entityAggregation === 'salutations') {
                $name = $entityElements[$bucket->getKey()]?->getTranslated()['displayName'] ?? 'Undefined';
                if (empty($name)) $name = 'Undefined';
            }
            elseif ($entityAggregation === null) {
                $name = $bucket->getKey() == 1 ? 'cbax-analytics.view.salesByAccountTypes.guest' : 'cbax-analytics.view.salesByAccountTypes.nonguest';
            }
            else {
                $name = !empty($entityElements[$bucket->getKey()]) ? $entityElements[$bucket->getKey()]->getTranslated()['name'] : '';
            }

            if (!empty($name))
            {
                if ($mainValue === 'sales')
                {
                    $sum = StatisticsHelper::calculateAmountInSystemCurrency($bucket->getResult());
                    $data[] = [
                        'id' => $bucket->getKey(),
                        'name' => $name,
                        'count' => (int)$bucket->getCount(),
                        'sum' => round($sum, 2)
                    ];
                } else {
                    $sales = StatisticsHelper::calculateAmountInSystemCurrency($bucket->getResult());
                    $data[] = [
                        'id' => $bucket->getKey(),
                        'name' => $name,
                        'sum' => (int)$bucket->getCount(),
                        'sales' => round($sales, 2)
                    ];
                }

            }
        }

        return StatisticsHelper::sortArrayByColumn($data);
    }

    public function getProductTranslatedName(ProductEntity $product, array $productSearch, bool $variantsWithOptionNames = true): string
    {
        $productName = $product->getTranslated()['name'];
        if (empty($productName) && !empty($product->getparentId()) && !empty($productSearch[$product->getparentId()]))
        {
            $productName = $productSearch[$product->getparentId()]->getTranslated()['name'];
        }
        if (empty($productName)) return '';
        if ($variantsWithOptionNames && !empty($product->getOptions()) && !empty($product->getOptions()->getElements()))
        {
            $optionNames = '';
            foreach ($product->getOptions() as $option)
            {
                if (!empty($option->getTranslated()['name']))
                {
                    $optionNames .= ' ' . $option->getTranslated()['name'];
                }
            }
            $productName .= ' - ' . $optionNames;
        }

        return $productName;
    }

    public function getOptionsNamesFromProducts(array $products, Context $context): array
    {
        $optionIds = [];
        $optionNames = [];
        foreach ($products as $product) {
            if (is_array($product) && !empty($product['optionIds'])) {
                $optionIds = array_unique(array_merge($optionIds, $product['optionIds']));
            }
            elseif (is_object($product) && !empty($product->getOptionIds())) {
                $optionIds = array_unique(array_merge($optionIds, $product->getOptionIds()));
            }
        }
        if (!empty($optionIds)) {
            $optionCriteria = new Criteria();
            $optionCriteria->addFilter(new EqualsAnyFilter('id', $optionIds));
            $optionSearch = $this->propertyGroupOptionRepository->search($optionCriteria, $context)->getElements();
        } else {
            return $optionNames;
        }

        foreach ($optionSearch as $id => $option) {
            $optionNames[$id] = $option->getTranslated()['name'];
        }

        return $optionNames;
    }

    //obsolet
    public function getProductDataFromAggrgation(EntitySearchResult $result, Context $modifiedContext, string $mainValue, string $termAggregation): array
    {
        $aggregation = $result->getAggregations()->get($termAggregation);
        $products = $result->getAggregations()->get('products')->getEntities()->getElements();
        $parents = $result->getAggregations()->get('parents')->getEntities()->getElements();
        $data = [];
        $allOptionNames = $this->getOptionsNamesFromProducts($products, $modifiedContext);

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            if (empty($key) || empty($products[$key])) continue;
            $productNumber = $products[$key]->getProductNumber();
            $productName = $products[$key]->getTranslated()['name'];
            if (empty($productName) && !empty($products[$key]->getparentId()) && !empty($parents[$products[$key]->getparentId()])) {
                $productName = $parents[$products[$key]->getparentId()]->getTranslated()['name'];
            }
            if (empty($productName)) continue;
            if (!empty($products[$key]->getOptionIds())) {
                $optionNames = '';
                foreach ($products[$key]->getOptionIds() as $optionId) {
                    if (!empty($allOptionNames[$optionId])) {
                        $optionNames .= ' ' . $allOptionNames[$optionId];
                    }
                }
                $productName .= ' -' . $optionNames;
            }
            if ($mainValue === 'count') {
                $data[] = [
                    'id' => $key,
                    'number' => $productNumber,
                    'name' => $productName,
                    'sum' => (int)$bucket->getCount()
                ];
            }
        }

        return StatisticsHelper::sortArrayByColumn($data);
    }

    public function getLanguageIdByLocaleCode(string $code, Context $context): string
    {
        $languageId = '';

        $criteriaLocale = new Criteria;
        $criteriaLocale->addFilter(new EqualsFilter('code', $code));
        $local = $this->localeRepository->search($criteriaLocale, $context)->first();

        if (!empty($local))
        {
            $localId = $local->get('id');
            $criteriaLanguage = new Criteria();
            $criteriaLanguage->addFilter(new EqualsFilter('localeId', $localId));
            $language = $this->languageRepository->search($criteriaLanguage, $context)->first();
        }

        if (!empty($language))
        {
            $languageId = $language->get('id');
        }

        return $languageId;
    }

    // id for order state canceled, to exclude canceled orders from statistics
    public function getCanceledStateId(Context $context): string
    {
        $canceledId = '';

        $criteriaSM = new Criteria;
        $criteriaSM->addFilter(new EqualsFilter('technicalName', OrderStates::STATE_MACHINE));
        $orderState = $this->stateMachineRepository->search($criteriaSM, $context)->first();

        if (!empty($orderState))
        {
            $orderStateId = $orderState->get('id');
            $criteriaSMS = new Criteria();
            $criteriaSMS->addFilter(new EqualsFilter('technicalName', OrderStates::STATE_CANCELLED));
            $criteriaSMS->addFilter(new EqualsFilter('stateMachineId', $orderStateId));
            $canceledState = $this->stateMachineStateRepository->search($criteriaSMS, $context)->first();
        }

        if (!empty($canceledState))
        {
            $canceledId = $canceledState->get('id');
        }

        return $canceledId;
    }

    public function exportCSV(array $gridData, string $labels, array|null $config = null): int|bool|string
    {
        $this->config = $config ?? $this->configReaderHelper->getConfig();

        if (empty($this->config['csvSeparator']))
        {
            $separator = ",";
        } else {
            $separator = match ($this->config['csvSeparator']) {
                'comma' => ",",
                'semicolon' => ";",
                'tab' => "\t",
                'pipe' => "|",
                default => ",",
            };
        }

        if (empty($this->config['csvNumberFormat']))
        {
            $decimalSeperator = ".";
            $thousandsSeperator = "";
        } else {
            switch ($this->config['csvNumberFormat']) {
                case 'pointOnly':
                    $decimalSeperator = ".";
                    $thousandsSeperator = "";
                    break;
                case 'commaOnly':
                    $decimalSeperator = ",";
                    $thousandsSeperator = "";
                    break;
                case 'pointComma':
                    $decimalSeperator = ".";
                    $thousandsSeperator = ",";
                    break;
                case 'commaPoint':
                    $decimalSeperator = ",";
                    $thousandsSeperator = ".";
                    break;
                default:
                    $decimalSeperator = ".";
                    $thousandsSeperator = "";
            }
        }

        //$labels = utf8_decode($labels);
        $labelsArray = explode(';', $labels);
        $labels = implode($separator, $labelsArray);
        $content = $labels . "\r\n";

        foreach ($gridData as $line) {
            if (!empty($line['id'])) unset($line['id']);
            if (!empty($line['lineItems'])) unset($line['lineItems']);
            if (!empty($line['date']) && !empty($line['formatedDate'])) {
                if (!empty($this->config['csvDateFormat']) && $this->config['csvDateFormat'] == 'standard') {
                    unset($line['formatedDate']);
                } else {
                    unset($line['date']);
                }
            }

            if (!empty($this->config['csvTextSeperator'])) {
                foreach ($line as &$entry) {
                    if (is_string($entry)) {
                        $entry = '"' . $entry . '"';
                    } elseif (is_float($entry)) {
                        $entry = number_format($entry, 2, $decimalSeperator, $thousandsSeperator);
                    }
                }
            } else {
                foreach ($line as &$entry) {
                    if (is_float($entry)) {
                        $entry = number_format($entry, 2, $decimalSeperator, $thousandsSeperator);
                    }
                }
            }

            $content .= implode($separator, $line) . "\r\n";
        }

        $this->fileSystemPrivate->write(self::EXPORT_FILE_NAME, $content);
        $size = $this->fileSystemPrivate->fileSize(self::EXPORT_FILE_NAME);

        return $size;
    }

    public function getDownloadResponse(string $fileName, int|bool|string $fileSize): Response|JsonResponse
    {
        //self::EXPORT_FILE_NAME Name des Files in /Files/plugins/cbax_modul_analytics
        //$fileName Name des Files nach Download beim Kunden
        if ($fileSize != $this->fileSystemPrivate->fileSize(self::EXPORT_FILE_NAME)) {
            return new JsonResponse(array("success" => false));
        }

        $headers = [
            'Content-Disposition' => HeaderUtils::makeDisposition(
                'attachment',
                $fileName,
                // only printable ascii
                preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $fileName)
            ),
            'Content-Length' => $fileSize,
            'Content-Type' => 'text/comma-separated-values'
        ];

        $content = $this->fileSystemPrivate->read(self::EXPORT_FILE_NAME);

        if ($content === FALSE) {
            return new JsonResponse(array("success" => false));
        }

        $response = new Response($content, Response::HTTP_OK, $headers);
        $this->fileSystemPrivate->delete(self::EXPORT_FILE_NAME);

        return $response;
    }

    public function getDates(array $params): array
    {
        $start = $params['start'] ?? '';
        $end = $params['end'] ?? '';
        $dates = [];

        if (str_contains($start, 'Z')) {
            $dates['startDate'] = str_replace('Z', '', str_replace('T', ' ', $start));
        } else {
            $date = new \DateTime($start, new \DateTimeZone($params['userTimeZone']));
            $date->setTimezone(new \DateTimeZone('UTC'));
            $dates['startDate'] = date_format($date, Defaults::STORAGE_DATE_TIME_FORMAT);
        }
        if (str_contains($end, 'Z')) {
            $dates['endDate'] = str_replace('Z', '', str_replace('T', ' ', $end));
        } else {
            $date = new \DateTime($end, new \DateTimeZone($params['userTimeZone']));
            $date->setTimezone(new \DateTimeZone('UTC'));
            $dates['endDate'] = date_format($date, Defaults::STORAGE_DATE_TIME_FORMAT);
        }

        return $dates;
    }

    public function getVariantOptionsNames(ProductEntity $product): string
    {
        $optionNames = '';
        foreach ($product->getOptions()->getElements() as $option) {
            $optionNames .= ' ' . $option->getTranslated()['name'];
        }
        return $optionNames;
    }

    public function getCanceledTransactionsStateIds(Context $context): array
    {
        $canceledIds = [];
        $criteriaSM = new Criteria;
        $criteriaSM->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_MACHINE));
        $orderTransactionState = $this->stateMachineRepository->search($criteriaSM, $context)->first();

        if (!empty($orderTransactionState)) {
            $orderTransactionStateId = $orderTransactionState->get('id');
            $criteriaSMS = new Criteria();
            $criteriaSMS->addFilter(new EqualsAnyFilter('technicalName', [OrderTransactionStates::STATE_CANCELLED, OrderTransactionStates::STATE_FAILED]));
            $criteriaSMS->addFilter(new EqualsFilter('stateMachineId', $orderTransactionStateId));
            $idResult = $this->stateMachineStateRepository->searchIds($criteriaSMS, $context);
        }

        if (!empty($idResult)) {
            $canceledIds = $idResult->getIds();
        }

        return $canceledIds;
    }

    public function setMoreQueryConditions(QueryBuilder $query, array $parameters, Context $context): QueryBuilder
    {
        if (!empty($parameters['salesChannelIds'])) {
            $parameters['salesChannelIds'] = UUid::fromHexToBytesList($parameters['salesChannelIds']);

            $query->andWhere('orders.sales_channel_id IN (:salesChannels)')
                ->setParameter('salesChannels', $parameters['salesChannelIds'], ArrayParameterType::STRING);
        }

        if (!empty($parameters['affiliateCodes'])) {
            $query->andWhere('orders.affiliate_code IN (:affiliateCodes)')
                ->setParameter('affiliateCodes', $parameters['affiliateCodes'], ArrayParameterType::STRING);
        }

        if (!empty($parameters['notaffiliateCodes'])) {
            $query->andWhere('orders.affiliate_code IS NULL OR orders.affiliate_code NOT IN (:notaffiliateCodes)')
                ->setParameter('notaffiliateCodes', $parameters['notaffiliateCodes'], ArrayParameterType::STRING);
        }

        if (!empty($parameters['campaignCodes'])) {
            $query->andWhere('orders.campaign_code IN (:campaignCodes)')
                ->setParameter('campaignCodes', $parameters['campaignCodes'], ArrayParameterType::STRING);
        }

        if (!empty($parameters['customerGroupIds'])) {
            $parameters['customerGroupIds'] = UUid::fromHexToBytesList($parameters['customerGroupIds']);

            $query->leftJoin('orders', 'order_customer', 'ordercustomers',
                'orders.id = ordercustomers.order_id AND ordercustomers.version_id = :versionId')
                ->leftJoin('ordercustomers', 'customer', 'customers',
                    'ordercustomers.customer_id = customers.id')
                ->andWhere('customers.customer_group_id IN (:customerGroupIds)')
                ->setParameter('customerGroupIds', $parameters['customerGroupIds'], ArrayParameterType::STRING);
        }

        if (!empty($parameters['blacklistedStatesIds']['order'])) {
            $parameters['blacklistedStatesIds']['order'] = UUid::fromHexToBytesList($parameters['blacklistedStatesIds']['order']);

            $query->andWhere('orders.state_id NOT IN (:modStateIds)')
                ->setParameter('modStateIds', $parameters['blacklistedStatesIds']['order'], ArrayParameterType::STRING);
        }

        if (!empty($parameters['blacklistedStatesIds']['delivery'])) {
            $parameters['blacklistedStatesIds']['delivery'] = UUid::fromHexToBytesList($parameters['blacklistedStatesIds']['delivery']);

            $query->leftJoin('orders', 'order_delivery', 'deliveries',
                //Bei Orders mit Promotion Rabatt auf Versandkosten extra Zeile in deliveries, daher join auf 1 begrenzen
                'orders.id = deliveries.order_id AND
                deliveries.version_id = :versionId AND
                deliveries.created_at = (SELECT MAX(created_at) FROM order_delivery WHERE order_id = orders.id AND version_id = :versionId)')
                ->andWhere('deliveries.state_id NOT IN (:modDeliveryStateIds)')
                ->setParameter('modDeliveryStateIds', $parameters['blacklistedStatesIds']['delivery'], ArrayParameterType::STRING);
        }

        if (!empty($parameters['blacklistedStatesIds']['transaction'])) {
            $disregardedStatesIds = $this->getCanceledTransactionsStateIds($context);
            $blacklistedStatesIds = array_unique(array_merge($disregardedStatesIds, $parameters['blacklistedStatesIds']['transaction']));

            $blacklistedStatesIds = UUid::fromHexToBytesList($blacklistedStatesIds);

            $query->leftJoin('orders', 'order_transaction', 'transactions',
                'orders.id = transactions.order_id AND transactions.version_id = :versionId')
                ->andWhere('transactions.state_id NOT IN (:modTransactionStateIds)')
                ->setParameter('modTransactionStateIds', $blacklistedStatesIds, ArrayParameterType::STRING);
        }

        return $query;
    }

    public function getProductsForOverviews(
        array $parameters,
        Context $context,
        array $includes = ['options' => true, 'stock' => true],
        array $sortings = [],
        array $productIds = []
    ): array
    {
        //Varianten in Parent zusammenfassen
        if (!empty($parameters['showVariantParent'])) {
            return $this->getProductsForOverviewsWithParents($parameters, $context, $includes, $sortings, $productIds);
        }

        //Varianten einzeln listen
        $context->setConsiderInheritance(true);
        $criteria = new Criteria();
        $criteria->setOffset(0)
            ->setLimit(500);
        if (!empty($sortings)) {
            foreach ($sortings as $sorting) {
                $criteria->addSorting(new FieldSorting($sorting['field'], $sorting['direction']));
            }
        }
        if (!empty($includes['options'])) {
            $criteria->addAssociation('options');
            $criteria->getAssociation('options')
                ->addSorting(new FieldSorting('groupId'))
                ->addSorting(new FieldSorting('id'));
        }
        if (!empty($productIds)) {
            $criteria->addFilter(new EqualsAnyFilter('id', $productIds));

        } else {

            if (!empty($includes['inactive'])) {
                $criteria->addFilter(new EqualsAnyFilter('active', [null, 0, false]));
            }
            if (!empty($includes['active'])) {
                $criteria->addFilter(new EqualsAnyFilter('active', [1, true]));
            }
            if (!empty($parameters['salesChannelIds'])) {
                $criteria->addAssociation('visibilities');
                $criteria->addFilter(new EqualsAnyFilter('visibilities.salesChannelId', $parameters['salesChannelIds']));
            }
            if (!empty($parameters['productSearchIds'])) {
                $criteria->addFilter(new EqualsAnyFilter('id', $parameters['productSearchIds']));
            }
            if (!empty($parameters['manufacturerSearchIds'])) {
                $criteria->addFilter(new EqualsAnyFilter('manufacturerId', $parameters['manufacturerSearchIds']));
            }
        }

        if (!empty($includes['prices'])) {
            $criteria->addAssociation('prices');
        }

        $productIterator = new RepositoryIterator($this->productRepository, $context, $criteria);
        $productSearch = [];
        while ($searchResult = $productIterator->fetch()) {
            /** @var ProductEntity $product */
            foreach ($searchResult as $product) {
                if ($product->getChildCount() > 0) continue;
                $prod = [
                    'id' => $product->getId(),
                    'number' => $product->getProductNumber(),
                    'name' => $product->getTranslated()['name']
                ];
                if (!empty($includes['stock'])) {
                    $prod['sum'] = $product->getStock();
                }
                if (!empty($includes['options']) && !empty($product->getParentId()) && !empty($product->getOptions())) {
                    $prod['name'] .= ' -' . $this->getVariantOptionsNames($product);
                }
                if (!empty($includes['price'])) {
                    $prod['cprice'] = $this->calculatePrice($product, $context, $parameters['config']);
                }
                if (!empty($includes['purchasePrice'])) {
                    $prod['pprice'] = $this->calculatePurchasePrice($product, $context, $parameters['config']);
                }
                $productSearch[] = $prod;
                if (!empty($includes['limit']) && count($productSearch) >= $includes['limit']) {
                    break 2;
                }
            }
        }

        return [$productSearch, []];
    }

    public function getProductsForOverviewsWithParents(
        array $parameters,
        Context $context,
        array $includes = ['options' => true, 'stock' => true],
        array $sortings = [],
        array $productIds = []
    ): array
    {
        $context->setConsiderInheritance(true);
        $criteria = new Criteria();
        $criteria->setOffset(0)
            ->setLimit(500);
        if (!empty($sortings)) {
            foreach ($sortings as $sorting) {
                $criteria->addSorting(new FieldSorting($sorting['field'], $sorting['direction']));
            }
        }

        if (!empty($productIds)) {
            $criteria->addFilter(new EqualsAnyFilter('id', $productIds));

        } else {

            if (!empty($includes['inactive'])) {
                $criteria->addFilter(new EqualsAnyFilter('active', [null, 0, false]));
            }
            if (!empty($includes['active'])) {
                $criteria->addFilter(new EqualsAnyFilter('active', [1, true]));
            }
            if (!empty($parameters['salesChannelIds'])) {
                $criteria->addAssociation('visibilities');
                $criteria->addFilter(new EqualsAnyFilter('visibilities.salesChannelId', $parameters['salesChannelIds']));
            }
            if (!empty($parameters['manufacturerSearchIds'])) {
                $criteria->addFilter(new EqualsAnyFilter('manufacturerId', $parameters['manufacturerSearchIds']));
            }
        }
        if (!empty($parameters['productSearchIds'])) {
            $criteria->addFilter(new MultiFilter(
                Multifilter::CONNECTION_OR,
                [
                    new EqualsAnyFilter('parentId', $parameters['productSearchIds']),
                    new EqualsAnyFilter('id', $parameters['productSearchIds'])
                ]
            ));
        }

        if (!empty($includes['prices'])) {
            $criteria->addAssociation('prices');
        }

        $productIterator = new RepositoryIterator($this->productRepository, $context, $criteria);
        $productSearch = [];
        $parents = [];
        $normalProductCounter = 0;
        while ($searchResult = $productIterator->fetch()) {
            /** @var ProductEntity $product */
            foreach ($searchResult as $product) {
                //is parent
                if ($product->getChildCount() > 0) {
                    $parents[$product->getId()] = [
                        'id' => $product->getId(),
                        'number' => $product->getProductNumber(),
                        'name' => $product->getTranslated()['name'] . ' (' . $product->getChildCount() . ')'
                    ];
                    continue;
                }

                if (!empty($includes['limit']) && empty($product->getParentId()) && $normalProductCounter > $includes['limit']) {
                    continue;
                } elseif (!empty($includes['limit']) && empty($product->getParentId())) {
                    $normalProductCounter++;
                }

                $prod = [
                    'id' => $product->getId(),
                    'number' => $product->getProductNumber(),
                    'name' => $product->getTranslated()['name']
                ];
                if (!empty($product->getParentId())) {
                    $prod['parentId'] = $product->getParentId();
                }
                if (!empty($includes['stock'])) {
                    $prod['sum'] = $product->getStock();
                }
                if (!empty($includes['price'])) {
                    $prod['cprice'] = $this->calculatePrice($product, $context, $parameters['config']);
                }
                if (!empty($includes['purchasePrice'])) {
                    $prod['pprice'] = $this->calculatePurchasePrice($product, $context, $parameters['config']);
                }
                $productSearch[] = $prod;
            }
        }

        return [$productSearch, $parents];
    }

    /*
     * erzeugt Array indiziert mit id mit id, Produktnummer, Namen von Produkten
     * Bei showVariantParents werden diese Daten auch für Parents geholt,
     * ansonsten werden bei varianten namen mit Optionnamen angereichert
     */
    public function getProductsBasicData(array $parameters, Context $context, array $productIds): array
    {
        //product Daten name, productNumber, parentId holen
        $productsArray = $this->getProductsForOverviews($parameters, $context, ['options' => true, 'stock' => true], [], $productIds)[0] ?? [];
        $products = [];
        $parentIds = [];
        $parents = [];
        foreach ($productsArray as $prod) {
            if (!empty($parameters['showVariantParent']) && !empty($prod['parentId'])) {
                $parentIds[] = $prod['parentId'];
            }
            $products[$prod['id']] = $prod;
        }
        unset($productsArray);
        unset($productIds);

        //parent Daten name, productNumber holen, falls eingestellt
        if (!empty($parameters['showVariantParent'])) {
            $parentIds = array_unique($parentIds);
            $parents = $this->getProductsForOverviews($parameters, $context, [], [], $parentIds)[1] ?? [];
        }

        return [$products, $parents];
    }

    public function calculatePurchasePrice(ProductEntity $product, Context $context, array $config): int|float|null
    {
        $currencyId = $context->getCurrencyId();
        $purchasePrices = $product->getPurchasePrices();

        if (empty($purchasePrices)) return 0;

        if (!empty($config['grossOrNet']) && $config['grossOrNet'] == 'gross') {
            return round($purchasePrices->getCurrencyPrice($currencyId)->getGross(), 2);
        } else {
            return round($purchasePrices->getCurrencyPrice($currencyId)->getNet(), 2);
        }
    }

    public function calculatePrice(ProductEntity $product, Context $context, array $config): int|float|null
    {
        $currencyId = $context->getCurrencyId();
        $defaultPrice = $product->getPrice();

        if (empty($defaultPrice)) return 0;

        if (!empty($config['grossOrNet']) && $config['grossOrNet'] == 'gross') {
            return round($defaultPrice->getCurrencyPrice($currencyId)->getGross(), 2);
        } else {
            return round($defaultPrice->getCurrencyPrice($currencyId)->getNet(), 2);
        }
    }

    public function getOrderIdBytesFromDateIntervall(array $parameters, Context $context): array
    {
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'orders.id as `id`'
            ])
            ->from('`order`', 'orders')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('orders.order_date_time >= :start')
            ->andWhere('orders.order_date_time <= :end')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate']
            ]);

        $query = $this->setMoreQueryConditions($query, $parameters, $context);

        return $query->fetchFirstColumn();
    }

    public function getProductPWReturnDataFromOrderids(array $parameters, array $orderIdsBytes): array
    {
        $direction = $parameters['sortDirection'] ?? 'DESC';

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(returnlineitems.product_id)) as `id`',
                'SUM(returnlineitems.quantity) as `returnSum`'
            ])
            ->from('`pickware_erp_return_order_line_item`', 'returnlineitems')
            ->innerJoin('returnlineitems', 'pickware_erp_return_order', 'returnOrders', 'returnlineitems.return_order_id = returnOrders.id AND returnOrders.version_id = :versionId')
            ->andWhere('returnlineitems.version_id = :versionId')
            ->andWhere('returnOrders.order_id IN (:orderIds) AND returnOrders.order_version_id = :versionId')
            ->andWhere('returnOrders.order_id IS NOT NULL')
            ->andWhere('returnlineitems.product_id IS NOT NULL')
            ->setParameters(
                [
                    'orderIds' => $orderIdsBytes,
                    'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION)
                ],
                [
                    'orderIds' => ArrayParameterType::STRING
                ]
            )
            ->groupBy('`id`')
            ->orderBy('returnSum', $direction);

        return $query->fetchAllKeyValue();
    }

}
