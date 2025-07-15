<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Defaults;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cbax\ModulAnalytics\Components\Base;

class UnfinishedOrdersByCart implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly Connection $connection,
        private readonly EntityRepository $salesChannelRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $limit = 400;

        // first step, get tokens
        $tokenQuery = StatisticsHelper::getCartTokenQuery($this->connection, $parameters);
        $tokenArray = $tokenQuery->fetchFirstColumn();

        if (empty($tokenArray)) {
            if ($parameters['format'] === 'csv') {
                return ['success' => true, 'fileSize' => $this->base->exportCSV([], $parameters['labels'], $parameters['config'])];
            }

            return ['success' => true, 'seriesData' => []];
        }

        $filteredData = [];

        //Iteration: Only 100 in a loop
        foreach (array_chunk($tokenArray, $limit) as $tokens) {
            $qb = $this->connection->createQueryBuilder();

            $query = $qb
                ->select([
                    'DATE(context.updated_at) as date',
                    'LOWER(HEX(context.sales_channel_id)) as salesChannelId',
                    'context.payload as contextPayload',
                    'cart.payload as cartPayload',
                    'cart.compressed as compressed'
                ])
                ->from('`sales_channel_api_context`', 'context')
                ->InnerJoin('context', 'cart', 'cart', 'context.token = cart.token')
                ->andWhere('cart.token IN (:tokens)')
                ->andWhere('context.sales_channel_id IS NOT NULL')
                ->setParameter('tokens', $tokens, ArrayParameterType::STRING)
                ->orderBy('context.updated_at', 'DESC');

            $data = $query->fetchAllAssociative();

            foreach ($data as $key => &$item) {
                if (!empty($item['contextPayload'])) {
                    $item['contextPayload'] = json_decode($item['contextPayload'], true);
                    if (empty($item['contextPayload'])) {
                        unset($data[$key]);
                        continue;
                    } else {
                        if (!empty($item['cartPayload'])) {
                            if ($item['compressed'] != 0) {
                                $item['cartPayload'] = StatisticsHelper::detectAndDecompress($item['cartPayload']);
                            }
                            try {
                                $item['cartPayload'] = \unserialize($item['cartPayload']);
                                if (!($item['cartPayload'] instanceof Cart)) {
                                    unset($data[$key]);
                                    continue;
                                }
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    }

                    if (!empty($item['contextPayload']['currencyId'])) {
                        $item['currencyId'] = $item['contextPayload']['currencyId'];
                    } else {
                        $item['currencyId'] = NULL;
                    }
                    unset($item['contextPayload']);
                    $item['netPrice'] = $item['cartPayload']->getPrice()->getNetPrice();
                    $item['grossPrice'] = $item['cartPayload']->getPrice()->getTotalPrice();
                    $item['position'] = $item['cartPayload']->getLineItems()->count();
                    $item['itemCount'] = $item['cartPayload']->getLineItems()->getTotalQuantity();
                    unset($item['cartPayload']);
                }
            }
            unset($item);
            $filteredData = array_merge($filteredData, $data);
        }

        $salesChannelIds = array_unique(array_column($filteredData, 'salesChannelId'));

        $criteria = new Criteria($salesChannelIds);
        $criteria->addAssociation('currencies');

        $salesChannels = $this->salesChannelRepository->search($criteria, $context)->getElements();

        $gridData = [];

        foreach ($filteredData as $cart) {
            if (empty($salesChannels[$cart['salesChannelId']])) {
                continue;
            }

            $currencyID = $cart['currencyId'] ?? $salesChannels[$cart['salesChannelId']]->getCurrencyId();
            if ($currencyID === Defaults::CURRENCY) {
                $currencyFactor = 1;
            } else {
                $currencyFactor = $salesChannels[$cart['salesChannelId']]?->getCurrencies()?->getElements()[$currencyID]?->getFactor() ?? 1;
            }

            if (!isset($gridData[$cart['date']])) {
                $gridData[$cart['date']] = [
                    'date' => $cart['date'],
                    'formatedDate' => StatisticsHelper::getFormatedDate($cart['date'], $parameters['adminLocalLanguage']),
                    'count' => 1,
                    'gross' => round((float)$cart['grossPrice']/$currencyFactor, 2),
                    'net' => round((float)$cart['netPrice']/$currencyFactor, 2),
                    'avgGross' => round((float)$cart['grossPrice']/$currencyFactor, 2),
                    'position' => $cart['position'],
                    'itemCount' => $cart['itemCount'],
                    'avgCount' => $cart['itemCount']
                ];
            } else {
                $gridData[$cart['date']]['count']++;
                $gridData[$cart['date']]['gross'] += (float)$cart['grossPrice']/$currencyFactor;
                $gridData[$cart['date']]['net'] += (float)$cart['netPrice']/$currencyFactor;
                $gridData[$cart['date']]['avgGross'] = round($gridData[$cart['date']]['gross']/$gridData[$cart['date']]['count'], 2);
                $gridData[$cart['date']]['itemCount'] += $cart['itemCount'];
                $gridData[$cart['date']]['position'] += $cart['position'];
                $gridData[$cart['date']]['avgCount'] = round($gridData[$cart['date']]['itemCount']/$gridData[$cart['date']]['count'], 1);
            }
        }

        foreach ($gridData as &$item) {
            $item['gross'] = round($item['gross'], 2);
            $item['net'] = round($item['gross'], 2);
        }

        $gridData = array_values($gridData);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($gridData, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'seriesData' => $gridData];
    }
}
