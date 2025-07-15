<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Core\Checkout\Document\Event\InvoiceOrdersEvent;
use Shopware\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Shopware\Core\Defaults;

use Cbax\ModulAnalytics\Components\DetectionHelper;

class BackendSubscriber implements EventSubscriberInterface
{
    const MODUL_NAME = 'CbaxModulAnalytics';
    private $config = null;

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $invoiceDateRepository
    ) {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutFinishPageLoadedEvent::class => ['onOrderFinished', -10],
            InvoiceOrdersEvent::class => ['onInvoiceCreated', -10]
        ];
    }

    public function onInvoiceCreated(InvoiceOrdersEvent $event): void
    {
        $this->config = $this->config ?? ($this->systemConfigService->get(self::MODUL_NAME . '.config') ?? []);

        if (empty($this->config['recordAdditionalOrderData'])) {
            return;
        }

        $operations = $event->getOperations();
        if (empty($operations)) {
            return;
        }
        $newEntries = [];
        $updates = [];

        /** @var $operation DocumentGenerateOperation **/
        foreach ($operations as $operation) {
            $orderId = $operation->getOrderId();
            if (empty($orderId)) {
                continue;
            }
            $documentDate = $operation->getConfig()['documentDate'] ?? null;
            if (empty($documentDate)) {
                continue;
            }
            if (is_string($documentDate)) {
                try {
                    $date = new \DateTime($documentDate);
                } catch (\Exception) {
                    continue;
                }
            } elseif (is_object($documentDate) && $documentDate instanceof \DateTime) {
                $date = $documentDate;
            } else {
                continue;
            }

            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('orderId', $orderId));
            $found = $this->invoiceDateRepository->searchIds($criteria, $event->getContext())->firstId();
            if (empty($found)) {
                $newEntries[] = [
                    'orderId' => $orderId,
                    'invoiceDateTime' => $date->format(Defaults::STORAGE_DATE_TIME_FORMAT)
                ];
            } else {
                $updates[] = [
                    'id' => $found,
                    'invoiceDateTime' => $date->format(Defaults::STORAGE_DATE_TIME_FORMAT)
                ];
            }
        }

        try {
            if (!empty($newEntries)) {
                $this->invoiceDateRepository->create($newEntries, $event->getContext());
            }
            if (!empty($updates)) {
                $this->invoiceDateRepository->update($updates, $event->getContext());
            }

        } catch (\Exception) {

        }
    }

    public function onOrderFinished(CheckoutFinishPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        $this->config = $this->config ?? ($this->systemConfigService->get(self::MODUL_NAME . '.config', $salesChannelId) ?? []);

        if (empty($this->config['recordAdditionalOrderData'])) return;
        if (empty($_SERVER)) return;
        if (empty($_SERVER['HTTP_USER_AGENT'])) return;

        $order = $event->getPage()?->getOrder();
        if (empty($order)) return;

        $httpUserAgent = (string)$_SERVER['HTTP_USER_AGENT'];
        $customFields = $order->getCustomFields() ?? [];

        $customFields['cbaxStatistics'] = [
            'device' => DetectionHelper::getDeviceType($httpUserAgent),
            'os' => DetectionHelper::getOS($httpUserAgent),
            'browser' => DetectionHelper::getBrowser($httpUserAgent)
        ];

        $updates = [
            [
                'id' => $order->getId(),
                'customFields' => $customFields
            ]
        ];

        $this->orderRepository->update($updates, $event->getContext());
    }

}

