<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics;

use Doctrine\DBAL\Connection;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;

use Cbax\ModulAnalytics\Bootstrap\Database;
use Cbax\ModulAnalytics\Bootstrap\DefaultConfig;
use Cbax\ModulAnalytics\Bootstrap\Updater;

class CbaxModulAnalytics extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        $services = $this->getServices();
        $builder = new DefaultConfig();
        $builder->activate($services, $updateContext->getContext());

        $updater = new Updater();
        $updater->updateStatistics($services);

        parent::update($updateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            parent::uninstall($uninstallContext);

            return;
        }

        $services = $this->getServices();
        // Datenbank Tabellen löschen
        $db = new Database();
        $db->removeDatabaseTables($services);

        $this->removePluginConfig($services, $uninstallContext->getContext());

        parent::uninstall($uninstallContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        $services = $this->getServices();
        $builder = new DefaultConfig();
        $builder->activate($services, $activateContext->getContext());

        parent::activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
    }

    private function removePluginConfig(array $services, Context $context): void
    {
        $systemConfigRepository = $services['systemConfigRepository'];

        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('configurationKey', $this->getName() . '.config.'));
        $idSearchResult = $systemConfigRepository->searchIds($criteria, $context)->getIds();

        if (!empty($idSearchResult)) {
            $ids = array_map(static function ($id) {
                return ['id' => $id];
            }, $idSearchResult);

            $systemConfigRepository->delete($ids, $context);
        }
    }

    private function getServices(): array
    {
        $services = [];

        /* Standard Services */
        $services['systemConfigService'] = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');
        $services['systemConfigRepository'] = $this->container->get('system_config.repository');
        $services['stateMachineRepository'] = $this->container->get('state_machine.repository');
        $services['stateMachineStateRepository'] = $this->container->get('state_machine_state.repository');
        $services['connectionService'] =  $this->container->get(Connection::class);

        /* spezifische Services */

        return $services;
    }
}

