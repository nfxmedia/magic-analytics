<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class SearchResultEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $searchTerm;

    /**
     * @var string
     */
    protected $salesChannelId;

    /**
     * @var int
     */
    protected $results;

    /**
     * @var int
     */
    protected $searched;

    /**
     * @var SalesChannelEntity|null
     */
    protected $salesChannel;

    /**
     * @return string
     */
    public function getSearchTerm(): string
    {
        return $this->searchTerm;
    }

    /**
     * @param string $searchTerm
     */
    public function setSearchTerm(string $searchTerm): void
    {
        $this->searchTerm = $searchTerm;
    }

    /**
     * @return string
     */
    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    /**
     * @param string $salesChannelId
     */
    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    /**
     * @return int
     */
    public function getResults(): int
    {
        return $this->results;
    }

    /**
     * @param int $results
     */
    public function setResults(int $results): void
    {
        $this->results = $results;
    }

    /**
     * @return SalesChannelEntity|null
     */
    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    /**
     * @param SalesChannelEntity|null $salesChannel
     */
    public function setSalesChannel(?SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }

    /**
     * @return int
     */
    public function getSearched(): int
    {
        return $this->searched;
    }

    /**
     * @param int $searched
     */
    public function setSearched(int $searched): void
    {
        $this->searched = $searched;
    }



}

