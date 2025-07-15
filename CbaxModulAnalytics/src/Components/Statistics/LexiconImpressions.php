<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

use Cbax\ModulAnalytics\Components\Base;

class LexiconImpressions implements StatisticsInterface
{
   public function __construct(
        private readonly Base $base,
        private readonly Connection $connection
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'DISTINCT LOWER(HEX(lexicon.id)) as id',
                'lexicon.impressions as count',
                'clet.title as title',
                'altclet.title as altTitle'
            ])
            ->from('`cbax_lexicon_entry`', 'lexicon')
            ->leftJoin('lexicon', '`cbax_lexicon_entry_translation`', 'clet',
                'lexicon.id = clet.cbax_lexicon_entry_id AND clet.language_id = UNHEX(:language)')
            ->leftJoin('lexicon', '`cbax_lexicon_entry_translation`', 'altclet',
                'lexicon.id = altclet.cbax_lexicon_entry_id AND altclet.language_id = UNHEX(:altLanguage)')
            ->setParameters([
                'language' => $languageId,
                'altLanguage' => Defaults::LANGUAGE_SYSTEM
            ])
            ->orderBy('count', 'DESC');

        if (!empty($parameters['salesChannelIds']))
        {
            $ids = UUid::fromHexToBytesList($parameters['salesChannelIds']);

            $query->leftJoin('lexicon', '`cbax_lexicon_sales_channel`', 'clsc',
                    'lexicon.id = clsc.cbax_lexicon_entry_id AND clsc.sales_channel_id IN (:salesChannels)')
                ->andWhere('clsc.id IS NOT NULL')
                ->setParameter('salesChannels', $ids, ArrayParameterType::STRING);
        }

        $data = $query->fetchAllAssociative();

        $sortedData = [];

        foreach($data as $entry)
        {
            $sortedData[] = [
                'id' => $entry['id'],
                'name' => $entry['title'] ?? $entry['altTitle'],
                'count' => (int)$entry['count']
            ];
        }

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($sortedData, $parameters['labels'], $parameters['config'])];
        }

        $overall = array_sum(array_column($data, 'count'));

        $seriesData = StatisticsHelper::limitData($sortedData, $parameters['config']['chartLimit']);
        $gridData   = StatisticsHelper::limitData($sortedData, $parameters['config']['gridLimit']);

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData, 'overall' => $overall];
    }
}


