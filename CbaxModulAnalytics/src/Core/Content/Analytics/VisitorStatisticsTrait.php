<?php
declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

trait VisitorStatisticsTrait
{
    /**
     * @var string|null
     */
    protected $salesChannelId;

    /**
     * @var \DateTimeInterface|null|string
     */
    protected $date;

    /**
     * @return string|null
     */
    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    /**
     * @param string|null $salesChannelId
     */
    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    /**
     * @return \DateTimeInterface|string|null
     */
    public function getDate(): \DateTimeInterface|string|null
    {
        return $this->date;
    }

    /**
     * @param \DateTimeInterface|string|null $date
     */
    public function setDate(\DateTimeInterface|string|null $date): void
    {
        $this->date = $date;
    }
}


