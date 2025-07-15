<?php
declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

trait ImpressionStatisticsTrait
{
    /**
     * @var string|null
     */
    protected $salesChannelId;

    /**
     * @var string|null
     */
    protected $customerGroupId;

    /**
     * @var \DateTimeInterface|null|string
     */
    protected $date;

    /**
     * @var int|null
     */
    protected $impressions;

    /**
     * @var string|null
     */
    protected $deviceType;

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
     * @return string|null
     */
    public function getCustomerGroupId(): ?string
    {
        return $this->customerGroupId;
    }

    /**
     * @param string|null $customerGroupId
     */
    public function setCustomerGroupId(?string $customerGroupId): void
    {
        $this->customerGroupId = $customerGroupId;
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

    /**
     * @return int|null
     */
    public function getImpressions(): ?int
    {
        return $this->impressions;
    }

    /**
     * @param int|null $impressions
     */
    public function setImpressions(?int $impressions): void
    {
        $this->impressions = $impressions;
    }

    /**
     * @return string|null
     */
    public function getDeviceType(): ?string
    {
        return $this->deviceType;
    }

    /**
     * @param string|null $deviceType
     */
    public function setDeviceType(?string $deviceType): void
    {
        $this->deviceType = $deviceType;
    }

}

