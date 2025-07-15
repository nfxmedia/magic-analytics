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

class UnfinishedOrders implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly Connection $connection,
        private readonly EntityRepository $salesChannelRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);
        $limit = 100;

        // first step, get tokens
        $tokenQuery = StatisticsHelper::getCartTokenQuery($this->connection, $parameters);
        $tokenArray = $tokenQuery->fetchFirstColumn();

        if (empty($tokenArray)) {
            if ($parameters['format'] === 'csv') {
                return ['success' => true, 'fileSize' => $this->base->exportCSV([], $parameters['labels'], $parameters['config'])];
            }

            return ["success" => true, "gridData" => []];
        }

        //Second Step: Get Data
        $filteredData = [];

        //Iteration: Only 100 in a loop
        foreach (array_chunk($tokenArray, $limit) as $tokens) {
            $qb = $this->connection->createQueryBuilder();

            $query = $qb
                ->select([
                    'LOWER(HEX(context.customer_id)) as customerId',
                    'context.updated_at as dateTime',
                    'customer.first_name as firstName',
                    'customer.last_name as lastName',
                    'customer.email as email',
                    'LOWER(HEX(customer.default_payment_method_id)) as defaultPaymentMethodId',
                    'LOWER(HEX(context.sales_channel_id)) as salesChannelId',
                    'context.payload as contextPayload',
                    'cart.payload as cartPayload',
                    'cart.compressed as compressed'
                ])
                ->from('`sales_channel_api_context`', 'context')
                ->InnerJoin('context', 'customer', 'customer', 'context.customer_id = customer.id')
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

                    if (!empty($item['contextPayload']['paymentMethodId'])) {
                        $item['paymentMethodId'] = $item['contextPayload']['paymentMethodId'];
                    } else {
                        $item['paymentMethodId'] = $item['defaultPaymentMethodId'];
                    }

                    if (!empty($item['contextPayload']['currencyId'])) {
                        $item['currencyId'] = $item['contextPayload']['currencyId'];
                    } else {
                        $item['currencyId'] = NULL;
                    }
                    unset($item['contextPayload']);

                    $item['lineItems'] = $this->getLineItems($item['cartPayload'], $parameters);
                    $item['phoneNumber'] = $this->getCustomerPhoneNumber($item['cartPayload']);
                    $item['netPrice'] = $item['cartPayload']->getPrice()->getNetPrice();
                    $item['grossPrice'] = $item['cartPayload']->getPrice()->getTotalPrice();
                    $item['position'] = count($item['lineItems']);
                    $item['itemCount'] = array_sum(array_column($item['lineItems'], 'quantity'));
                    unset($item['cartPayload']);
                }
            }
            unset($item);
            $filteredData = array_merge($filteredData, $data);
        }

        $salesChannelIds = array_unique(array_column($filteredData, 'salesChannelId'));

        $criteria = new Criteria($salesChannelIds);
        $criteria->addAssociation('currencies');
        $criteria->addAssociation('paymentMethods');
        $salesChannels = $this->salesChannelRepository->search($criteria, $modifiedContext)->getElements();

        $gridData = [];

        foreach ($filteredData as $cart) {
            if (empty($cart['paymentMethodId'])) {
                continue;
            }
            if (empty($salesChannels[$cart['salesChannelId']])) {
                continue;
            }

            $paymentMethods = $salesChannels[$cart['salesChannelId']]->getPaymentMethods()?->getElements();
            if (!is_array($paymentMethods) || empty($paymentMethods[$cart['paymentMethodId']]))  {
                continue;
            }

            $paymentName = $paymentMethods[$cart['paymentMethodId']]->getTranslated()['name'];
            $salesChannelName = $salesChannels[$cart['salesChannelId']]?->getTranslated()['name'];
            $currencyID = $cart['currencyId'] ?? $salesChannels[$cart['salesChannelId']]?->getCurrencyId();
            if ($currencyID === Defaults::CURRENCY) {
                $currencyFactor = 1;
            } else {
                $currencyFactor = $salesChannels[$cart['salesChannelId']]?->getCurrencies()?->getElements()[$currencyID]?->getFactor() ?? 1;
            }

            foreach ($cart['lineItems'] as &$item) {
                $item['unitPrice'] = round((float)$item['unitPrice']/$currencyFactor, 2);
                $item['totalPrice'] = round((float)$item['totalPrice']/$currencyFactor, 2);
            }
            unset($item);

            $gridData[] = [
                'id' => $cart['customerId'],
                'date' => StatisticsHelper::getFormatedDate($cart['dateTime'], $parameters['adminLocalLanguage']),
                'gross' => round((float)$cart['grossPrice']/$currencyFactor, 2),
                'net' => round((float)$cart['netPrice']/$currencyFactor, 2),
                'position' => $cart['position'],
                'itemCount' => $cart['itemCount'],
                'name' => $cart['firstName'] . ' ' . $cart['lastName'],
                'email' => $cart['email'],
                'phone' => $cart['phoneNumber'],
                'payment' => $paymentName,
                'salesChannel' => $salesChannelName,
                'lineItems' => $cart['lineItems']
            ];
        }

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($gridData, $parameters['labels'], $parameters['config'])];
        }

        return ["success" => true, "gridData" => $gridData];
    }

    private function getCustomerPhoneNumber(Cart $cart): string
    {
        $phoneNumber = '';

        foreach ($cart->getDeliveries() as $delivery) {
            $pn = $delivery->getLocation()?->getAddress()?->getPhoneNumber();
            if (!empty($pn)) {
                $phoneNumber = $pn;
                break;
            }
        }

        return $phoneNumber;
    }

    private function getLineItems(Cart $cartObject, array $parameters): array
    {
        $lineItems = [];

        if (!empty($parameters['manufacturerSearchIds'])) {
            $manufacturerFilteredOut = true;
        } else {
            $manufacturerFilteredOut = false;
        }

        if (!empty($parameters['productSearchIds'])) {
            $productFilteredOut = true;
        } else {
            $productFilteredOut = false;
        }

        $lineItemsColl = $cartObject->getLineItems();

        foreach ($lineItemsColl->getElements() as $item) {
            if ($manufacturerFilteredOut && !empty($parameters['manufacturerSearchIds'])) {
                $manufacturerId = $item->getPayload()['manufacturerId'] ?? '';
                if (in_array($manufacturerId, $parameters['manufacturerSearchIds'])) $manufacturerFilteredOut = false;
            }
            if ($productFilteredOut && !empty($parameters['productSearchIds'])) {
                if (in_array($item->getId(), $parameters['productSearchIds'])) $productFilteredOut = false;
            }
            $lineItems[] = [
                'quantity' => $item->getQuantity(),
                'type' => $item->getType(),
                'unitPrice' => round($item->getPrice()->getUnitPrice(), 2),
                'totalPrice' => round($item->getPrice()->getTotalPrice(), 2),
                'label' => $item->getLabel(),
                'productNumber' => $item->getPayload()['productNumber'] ?? '',
                'id' => $item->getId()
            ];
        }

        return ($manufacturerFilteredOut || $productFilteredOut) ? [] : $lineItems;
    }
}
