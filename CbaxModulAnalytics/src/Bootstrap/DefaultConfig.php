<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Bootstrap;

use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;

class DefaultConfig
{
    const MODUL_NAME = 'CbaxModulAnalytics';
    /*
     * Standardwerte
     */
    private $defaults = [];

    /**
     * beim aktivieren des Plugins die Standardwerte setzen
     */
    public function activate(array $services, Context $context): void
    {
        $systemConfigService = $services['systemConfigService'];

        $shopwareConfig = $systemConfigService->get(self::MODUL_NAME . '.config') ?? [];

        if (!isset($shopwareConfig['blacklistedOrderStates'])) {
            $canceledStateId = [$this->getCanceledStateId($services, $context)];
            if (!empty($canceledStateId)) {
                $systemConfigService->set(self::MODUL_NAME . '.config.blacklistedOrderStates', $canceledStateId);
            }
        }

        // Standardkonfiguration setzen, wenn noch nichts eingetragen wurde
        $configs = $this->checkDefault($shopwareConfig, $this->defaults);
        // speichern der Konfig
        foreach ($configs as $key => $config) {
            $systemConfigService->set(self::MODUL_NAME . '.config.' . $key, $config);
        }
    }

    /**
     * Standardkonfiguration setzen, wenn noch nichts eingetragen wurde
     * @param array $configs aktuelle Konfig
     * @param array $configValues Standardkonfig
     */
    public function checkDefault(array $configs, array $configValues): array
    {
        $config = [];

        // setzen der Standardwerte, wenn in die Felder noch nicht eingetragen wurde
        foreach ($configValues as $key => $value) {
            if (!isset($configs[$key])) {
                $config[$key] = $configValues[$key];
            }
        }

        return $config;
    }

    private function getCanceledStateId(array $services, Context $context): ?string
    {
        $canceledId = '';

        $criteriaSM = new Criteria;
        $criteriaSM->addFilter(new EqualsFilter('technicalName', OrderStates::STATE_MACHINE));
        $orderState = $services['stateMachineRepository']->search($criteriaSM, $context)->first();

        if (!empty($orderState))
        {
            $orderStateId = $orderState->get('id');
            $criteriaSMS = new Criteria();
            $criteriaSMS->addFilter(new EqualsFilter('technicalName', OrderStates::STATE_CANCELLED));
            $criteriaSMS->addFilter(new EqualsFilter('stateMachineId', $orderStateId));
            $canceledState = $services['stateMachineStateRepository']->search($criteriaSMS, $context)->first();
        }

        if (!empty($canceledState))
        {
            $canceledId = $canceledState->get('id');
        }

        return $canceledId;
    }
}
