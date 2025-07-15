<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class GroupEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $label;
	

    /**
     * @var int
     */
    protected $position;

    /**
     * @var int
     */
    protected $active;
    
    /**
     * @var string
     */
    protected $plugin;
	
	/**
     * @var string
     */
    protected $parameter;
    
    /**
     * @var ConfigCollection|null
     */
    protected $statistics;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return int
     */
    public function getActive(): int
    {
        return $this->active;
    }

    /**
     * @param int $active
     */
    public function setActive(int $active): void
    {
        $this->active = $active;
    }
    
    public function getPlugin(): ?string
    {
        return $this->plugin;
    }

    public function setPlugin(string $plugin): void
    {
        $this->plugin = $plugin;
    }
	
    public function getParameter(): ?string
    {
        return $this->parameter;
    }

    public function setParameter(string $parameter): void
    {
        $this->parameter = $parameter;
    }
    
    /**
     * @return ConfigCollection|null
     */
    public function getStatistics(): ?ConfigCollection
    {
        return $this->statistics;
    }

    /**
     * @param ConfigCollection|null $statistics
     */
    public function setStatistics(?ConfigCollection $statistics): void
    {
        $this->statistics = $statistics;
    }
}



