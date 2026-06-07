<?php

declare(strict_types=1);

namespace App\Navigating\Entity;

use App\Navigating\Repository\NavigationMenuItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NavigationMenuItemRepository::class)]
#[ORM\Table(name: 'navigation_menu_item')]
#[ORM\UniqueConstraint(name: 'uniq_navigation_menu_item_menu_key', columns: ['menu_key'])]
#[ORM\UniqueConstraint(name: 'uniq_navigation_menu_item_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_navigation_menu_item_route_name', columns: ['route_name'])]
#[ORM\Index(name: 'idx_navigation_menu_item_operation', columns: ['operation'])]
#[ORM\Index(name: 'idx_navigation_menu_item_parent_key', columns: ['parent_key'])]
#[ORM\Index(name: 'idx_navigation_menu_item_archived_at', columns: ['archived_at'])]
#[ORM\Index(name: 'idx_navigation_menu_item_enabled_location_position', columns: ['enabled', 'location', 'position'])]
#[ORM\HasLifecycleCallbacks]
class NavigationMenuItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'menu_key', length: 160)]
    private string $menuKey = '';

    #[ORM\Column(name: 'parent_key', length: 160, nullable: true)]
    private ?string $parentKey = null;

    #[ORM\Column(length: 140)]
    private string $label = '';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(name: 'route_name', length: 180)]
    private string $routeName = '';

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'route_parameters', type: Types::JSON)]
    private array $routeParameters = [];

    #[ORM\Column(length: 120)]
    private string $location = 'shell.context.middle';

    #[ORM\Column(length: 60)]
    private string $operation = 'index';

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(name: 'required_role', length: 80, nullable: true)]
    private ?string $requiredRole = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $position = 0;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $enabled = true;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $metadata = [];

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'archived_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMenuKey(): string
    {
        return $this->menuKey;
    }

    public function setMenuKey(string $menuKey): self
    {
        $this->menuKey = trim($menuKey);

        return $this;
    }

    public function getParentKey(): ?string
    {
        return $this->parentKey;
    }

    public function setParentKey(?string $parentKey): self
    {
        $parentKey = null === $parentKey ? null : trim($parentKey);
        $this->parentKey = '' === $parentKey ? null : $parentKey;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = trim($label);

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $slug = null === $slug ? null : trim($slug);
        $this->slug = '' === $slug ? null : $slug;

        return $this;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function setRouteName(string $routeName): self
    {
        $this->routeName = trim($routeName);

        return $this;
    }

    /** @return array<string, mixed> */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    /** @param array<string, mixed> $routeParameters */
    public function setRouteParameters(array $routeParameters): self
    {
        $this->routeParameters = $routeParameters;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = trim($location);

        return $this;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function setOperation(string $operation): self
    {
        $this->operation = trim($operation);

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $icon = null === $icon ? null : trim($icon);
        $this->icon = '' === $icon ? null : $icon;

        return $this;
    }

    public function getRequiredRole(): ?string
    {
        return $this->requiredRole;
    }

    public function setRequiredRole(?string $requiredRole): self
    {
        $requiredRole = null === $requiredRole ? null : trim($requiredRole);
        $this->requiredRole = '' === $requiredRole ? null : $requiredRole;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /** @param array<string, mixed> $metadata */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function archive(): self
    {
        $this->archivedAt = new \DateTimeImmutable();
        $this->enabled = false;

        return $this;
    }

    public function restore(): self
    {
        $this->archivedAt = null;
        $this->enabled = true;

        return $this;
    }

    public function isArchived(): bool
    {
        return null !== $this->archivedAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
