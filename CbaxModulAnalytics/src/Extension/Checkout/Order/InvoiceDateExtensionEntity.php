<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Extension\Checkout\Order;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Checkout\Order\OrderEntity;

class InvoiceDateExtensionEntity extends Entity
{
    use EntityIdTrait;

    protected string $orderId;

    protected \DateTimeInterface|null|string $invoiceDateTime;

    protected ?OrderEntity $order;

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getInvoiceDateTime(): \DateTimeInterface|string|null
    {
        return $this->invoiceDateTime;
    }

    public function setInvoiceDateTime(\DateTimeInterface|string|null $invoiceDateTime): void
    {
        $this->invoiceDateTime = $invoiceDateTime;
    }

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function setOrder(?OrderEntity $order): void
    {
        $this->order = $order;
    }

    

}