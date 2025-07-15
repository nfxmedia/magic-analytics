<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RequestStack;
use Shopware\Core\PlatformRequest;

class ConfigReaderHelper
{
    const CONFIG_PATH = 'CbaxModulAnalytics.config';

	public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly ?RequestStack $requestStack
    ) {

    }

	public function getConfig(): array
    {
        $salesChannelId = null;
        $request = $this->requestStack?->getCurrentRequest();

        if (!empty($request))
        {
            $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
            if (!empty($context))
            {
                $salesChannelId = $context->getSalesChannel()->getId();
            }
        }

        return $this->systemConfigService->get(self::CONFIG_PATH, $salesChannelId) ?? [];
    }
}
