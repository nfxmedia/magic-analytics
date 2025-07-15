<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;

class ExtensionHelper
{
    const GROUP_DATA_FIELDS = ['name', 'label', 'position', 'active', 'plugin', 'parameter'];
    const STATISTICS_DATA_FIELDS = ['name', 'groupName', 'routeName', 'pathInfo', 'label', 'position', 'active', 'plugin', 'parameter'];

    public function __construct(
        private readonly EntityRepository $statisticsConfigRepository,
        private readonly EntityRepository $groupsConfigRepository
    ) {
    }

    public function createGroup(array $data, Context $context): array
    {
        $success = false;

        $check = $this->checkData($data, 'group');

        if ($check === false)
        {
            $error = 'Fehler! Datenstruktur der Gruppe falsch: ' . $data['name'];
            return ['success' => $success, 'error' => $error];
        } else {
            $data = $check;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $data['name']));
        $found = $this->groupsConfigRepository->search($criteria, $context)->first();

        if (!empty($found))
        {
            $error = 'Fehler! Eine Gruppe mit diesem Namen existiert schon: ' . $data['name'];
            return ['success' => $success, 'error' => $error];
        }

        $this->groupsConfigRepository->create([$data], $context);

        $success = true;
        return ['success' => $success];
    }

    public function createStatistic(array $data, Context $context): array
    {
        $success = false;

        $check = $this->checkData($data, 'statistic');

        if ($check === false)
        {
            $error = 'Fehler! Datenstruktur einer Statistik falsch: ' . $data['name'];
            return ['success' => $success, 'error' => $error];
        } else {
            $data = $check;
        }

        $criteria1 = new Criteria();
        $criteria1->addFilter(new EqualsFilter('name', $data['groupName']));
        $group = $this->groupsConfigRepository->search($criteria1, $context)->first();

        if (empty($group))
        {
            $error = 'Fehler! Keine Gruppe mit dem Namen ' . $data['groupName'];
            return ['success' => $success, 'error' => $error];
        }

        $criteria2 = new Criteria();
        $criteria2->addFilter(new EqualsFilter('name', $data['name']));
        $stat = $this->statisticsConfigRepository->search($criteria2, $context)->first();

        if (!empty($stat))
        {
            $error = 'Fehler! Eine Statistik mit diesem Namen existiert schon: ' . $data['name'];
            return ['success' => $success, 'error' => $error];
        }

        $data['groupId'] = $group->getId();

        $this->statisticsConfigRepository->create([$data], $context);

        $success = true;
        return ['success' => $success];
    }

    public function createGroupWithStatistics(Context $context, array $groupData = [], array $statisticsData = []): array
    {
        $success = false;

        if (!is_array($statisticsData))
        {
            $error = 'Fehler! Datenstruktur falsch.';

            return ['success' => $success, 'error' => $error];
        }

        if (!empty($groupData))
        {
            $groupCreation = $this->createGroup($groupData, $context);
            if (empty($groupCreation['success']))
            {
                $error = !empty($groupCreation['error']) ? $groupCreation['error'] : 'Fehlerbeim Erstellen der Gruppe.';

                return ['success' => $success, 'error' => $error];
            }
        }

        foreach ($statisticsData as $data)
        {
            if (!empty($data))
            {
                $statCreation = $this->createStatistic($data, $context);
                if (empty($statCreation['success']))
                {
                    $error = !empty($statCreation['error']) ? $statCreation['error'] : 'Fehlerbeim Erstellen der Statistiken.';

                    return ['success' => $success, 'error' => $error];
                }
            }
        }

        $success = true;
        return ['success' => $success];

    }

    public function removeStatisticsAndGroups(string $plugin, Context $context): void
    {
        if (empty($plugin))
        {
            return;
        }
        //Statistiken zuerst löschen
        $idsToDelete = [];

        $criteria1 = new Criteria();
        $criteria1->addFilter(new EqualsFilter('plugin', (string)$plugin));
        $stats = $this->statisticsConfigRepository->search($criteria1, $context)->getElements();
        if (!empty($stats))
        {
            foreach ($stats as $stat)
            {
                $idsToDelete[] = ['id' => $stat->getId()];
            }
        }

        if (!empty($idsToDelete))
        {
            $this->statisticsConfigRepository->delete($idsToDelete, $context);
        }

        //danach Gruppen löschen
        $idsToDelete = [];

        $criteria2 = new Criteria();
        $criteria2->addFilter(new EqualsFilter('plugin', (string)$plugin));
        $groups = $this->groupsConfigRepository->search($criteria2, $context)->getElements();
        if (!empty($groups))
        {
            foreach ($groups as $group)
                $criteria3 = new Criteria();
                $criteria3->addFilter(new EqualsFilter('groupId', $group->getId()));
                $stats = $this->statisticsConfigRepository->search($criteria3, $context)->getElements();
                if (empty($stats))
                {
                    $idsToDelete[] = ['id' => $group->getId()];
                }
        }

        if (!empty($idsToDelete))
        {
            $this->groupsConfigRepository->delete($idsToDelete, $context);
        }
    }

    private function checkData(array $data, string $type): bool|array
    {
        $check = true;

        if (!is_array($data))
        {
            return false;
        }
        if (!in_array($type, ['group', 'statistic']))
        {
            return false;
        }

        if (!array_key_exists('parameter', $data))
        {
            $data['parameter'] = ($type === 'group') ? '' : [];
        }

        if ($type === 'group')
        {
            foreach (self::GROUP_DATA_FIELDS as $field)
            {
                if (!array_key_exists($field, $data))
                {
                    $check = false;
                } else {
                    $fieldType = gettype($data[$field]);
                    if (!in_array($fieldType, ['string', 'integer']))
                    {
                        $check = false;
                    }
                }
            }
            if ($check)
            {
                foreach (array_keys($data) as $key)
                {
                    if (!in_array($key, self::GROUP_DATA_FIELDS))
                    {
                        $check = false;
                    }
                }
            }
        }

        if ($type === 'statistic')
        {
            foreach (self::STATISTICS_DATA_FIELDS as $field)
            {
                if (!array_key_exists($field, $data))
                {
                    $check = false;
                } else {
                    $fieldType = gettype($data[$field]);
                    if (!in_array($fieldType, ['string', 'integer', 'array']))
                    {
                        $check = false;
                    }
                }
            }
            if ($check)
            {
                foreach (array_keys($data) as $key)
                {
                    if (!in_array($key, self::STATISTICS_DATA_FIELDS))
                    {
                        $check = false;
                    }
                }
            }
        }

        if ($check)
        {
            return $data;
        } else {
            return false;
        }
    }
}
