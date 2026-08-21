<?php

declare(strict_types=1);

namespace App\Navigating\Entity;

use App\Navigating\Repository\NavigationItemRepository;
use App\Objecting\EntityInterface\ObjectAuditedInterface;
use App\Objecting\EntityTrait\Embeddable\ObjectAuditEmbeddableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NavigationItemRepository::class)]
#[ORM\Table(name: 'navigation_item')]
#[ORM\UniqueConstraint(name: 'uniq_navigation_item_menu_key', columns: ['menu_id', 'navigation_key'])]
#[ORM\UniqueConstraint(name: 'uniq_navigation_item_menu_slug', columns: ['menu_id', 'slug'])]
#[ORM\Index(name: 'idx_navigation_item_menu_position', columns: ['menu_id', 'position'])]
#[ORM\Index(name: 'idx_navigation_item_parent_position', columns: ['parent_id', 'position'])]
#[ORM\Index(name: 'idx_navigation_item_route_name', columns: ['route_name'])]
#[ORM\Index(name: 'idx_navigation_item_operation', columns: ['operation'])]
#[ORM\Index(name: 'idx_navigation_item_archived_at', columns: ['archived_at'])]
#[ORM\Index(name: 'idx_navigation_item_enabled_position', columns: ['enabled', 'position'])]
#[ORM\HasLifecycleCallbacks]
class NavigationItem implements ObjectAuditedInterface
{
    use ObjectAuditEmbeddableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 1;

    #[ORM\ManyToOne(targetEntity: NavigationMenu::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'menu_id', nullable: false, onDelete: 'CASCADE')]
    private ?NavigationMenu $menu = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $children;

    #[ORM\Column(name: 'navigation_key', length: 160)]
    private string $navigationKey = '';

    #[ORM\Column(length: 140)]
    private string $label = '';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(name: 'route_name', length: 180, nullable: true)]
    private ?string $routeName = null;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'route_parameters', type: Types::JSON)]
    private array $routeParameters = [];

    #[ORM\Column(name: 'path_target', length: 512, nullable: true)]
    private ?string $path = null;

    #[ORM\Column(length: 60)]
    private string $operation = 'index';

    #[ORM\Column(length: 40)]
    private string $type = 'link';

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $badge = null;

    /** @var list<string> */
    #[ORM\Column(name: 'visible_for_roles', type: Types::JSON)]
    private array $visibleForRoles = [];

    /** @var list<string> */
    #[ORM\Column(name: 'visible_for_scopes', type: Types::JSON)]
    private array $visibleForScopes = [];

    /** @var list<string> */
    #[ORM\Column(name: 'visible_for_environments', type: Types::JSON)]
    private array $visibleForEnvironments = [];

    #[ORM\Column(type: Types::INTEGER)]
    private int $position = 100;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $enabled = true;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $metadata = [];

    #[ORM\Column(name: 'archived_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    public function __construct()
    {
        $this->initializeObjectAudit();
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getMenu(): ?NavigationMenu
    {
        return $this->menu;
    }

    /**
     * Cross-field menu/parent consistency is validated at the Doctrine persistence
     * boundary so Symfony forms can change both fields in one submission without
     * becoming dependent on property-mapping order.
     */
    public function setMenu(?NavigationMenu $menu): self
    {
        $this->menu = $menu;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        if ($parent === $this) {
            throw new \DomainException('Navigation item cannot be its own parent.');
        }

        for ($ancestor = $parent; null !== $ancestor; $ancestor = $ancestor->getParent()) {
            if ($ancestor === $this) {
                throw new \DomainException('Navigation item hierarchy cannot contain cycles.');
            }
        }

        $this->parent = $parent;

        return $this;
    }

    /** @return Collection<int, self> */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getNavigationKey(): string
    {
        return $this->navigationKey;
    }

    public function setNavigationKey(string $navigationKey): self
    {
        $this->navigationKey = trim($navigationKey);

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

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function setRouteName(?string $routeName): self
    {
        $routeName = null === $routeName ? null : trim($routeName);
        $this->routeName = '' === $routeName ? null : $routeName;

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

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $path = null === $path ? null : trim($path);
        $this->path = '' === $path ? null : $path;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = trim($type);

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

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function setBadge(?string $badge): self
    {
        $badge = null === $badge ? null : trim($badge);
        $this->badge = '' === $badge ? null : $badge;

        return $this;
    }

    /** @return list<string> */
    public function getVisibleForRoles(): array
    {
        return $this->visibleForRoles;
    }

    /** @param list<string> $visibleForRoles */
    public function setVisibleForRoles(array $visibleForRoles): self
    {
        $this->visibleForRoles = $this->normalizeTokens($visibleForRoles, true);

        return $this;
    }

    /** @return list<string> */
    public function getVisibleForScopes(): array
    {
        return $this->visibleForScopes;
    }

    /** @param list<string> $visibleForScopes */
    public function setVisibleForScopes(array $visibleForScopes): self
    {
        $this->visibleForScopes = $this->normalizeTokens($visibleForScopes, false);

        return $this;
    }

    /** @return list<string> */
    public function getVisibleForEnvironments(): array
    {
        return $this->visibleForEnvironments;
    }

    /** @param list<string> $visibleForEnvironments */
    public function setVisibleForEnvironments(array $visibleForEnvironments): self
    {
        $this->visibleForEnvironments = $this->normalizeTokens($visibleForEnvironments, false);

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

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function archive(): self
    {
        $this->archivedAt = new \DateTimeImmutable();

        return $this;
    }

    public function restore(): self
    {
        $this->archivedAt = null;

        return $this;
    }

    public function isArchived(): bool
    {
        return null !== $this->archivedAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->touchModified();
    }

    public function __toString(): string
    {
        return '' !== $this->label ? $this->label : $this->navigationKey;
    }

    /**
     * @param list<string> $tokens
     *
     * @return list<string>
     */
    private function normalizeTokens(array $tokens, bool $upper): array
    {
        $normalized = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            if ('' === $token) {
                continue;
            }

            $token = $upper ? strtoupper($token) : strtolower($token);
            $normalized[$token] = $token;
        }

        return array_values($normalized);
    }
}
