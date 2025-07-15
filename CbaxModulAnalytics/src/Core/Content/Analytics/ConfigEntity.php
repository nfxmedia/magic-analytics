<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ConfigEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $groupName;

    /**
     * @var string
     */
    protected $groupId;

    /**
     * @var string
     */
    protected $label;

	/**
     * @var string
     */
    protected $routeName;

    /**
     * @var string
     */
    protected $pathInfo;

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
     * @var array|null|string
     */
    protected $parameter;

    /**
     * @var GroupEntity|null
     */
    protected $group;

    /**
     * @return GroupEntity|null
     */
    public function getGroup(): ?GroupEntity
    {
        return $this->group;
    }

    /**
     * @param GroupEntity|null $group
     */
    public function setGroup(?GroupEntity $group): void
    {
        $this->group = $group;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function setGroupName(string $groupName): void
    {
        $this->groupName = $groupName;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function setGroupId(string $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

	public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function setRouteName(string $routeName): void
    {
        $this->routeName = $routeName;
    }

    public function getPathInfo(): ?string
    {
        return $this->pathInfo;
    }

    public function setPathInfo(string $pathInfo): void
    {
        $this->pathInfo = $pathInfo;
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

    public function getParameter(): array|string|null
    {
        return $this->parameter;
    }

    public function setParameter(array|string|null $parameter): void
    {
        $this->parameter = $parameter;
    }


}

