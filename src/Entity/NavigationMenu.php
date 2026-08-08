<?php

declare(strict_types=1);

namespace App\Navigating\Entity;

use App\Navigating\Repository\NavigationMenuRepository;
use App\Objecting\EntityInterface\ObjectAuditedInterface;
use App\Objecting\EntityTrait\Embeddable\ObjectAuditEmbeddableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NavigationMenuRepository::class)]
#[ORM\Table(name: 'navigation_menu')]
#[ORM\UniqueConstraint(name: 'uniq_navigation_menu_key', columns: ['menu_key'])]
#[ORM\UniqueConstraint(name: 'uniq_navigation_menu_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_navigation_menu_location_enabled_priority', columns: ['location', 'enabled', 'priority'])]
#[ORM\HasLifecycleCallbacks]
final class NavigationMenu implements ObjectAuditedInterface
{
    use ObjectAuditEmbeddableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 1;

    #[ORM\Column(name: 'menu_key', length: 160)]
    private string $menuKey = '';

    #[ORM\Column(length: 180)]
    private string $slug = '';

    #[ORM\Column(length: 140)]
    private string $label = '';

    #[ORM\Column(length: 120)]
    private string $location = 'shell.context.middle';

    #[ORM\Column(length: 60)]
    private string $type = 'navigation';

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
    private int $priority = 100;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $enabled = true;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $metadata = [];

    /** @var Collection<int, NavigationItem> */
    #[ORM\OneToMany(mappedBy: 'menu', targetEntity: NavigationItem::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $items;

    public function __construct()
    {
        $this->initializeObjectAudit();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        if ($version < 1) {
            throw new \DomainException('Navigation menu version must be positive.');
        }

        $this->version = $version;

        return $this;
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = trim($slug);

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

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = trim($location);

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

    /** @return list<string> */
    public function getVisibleForRoles(): array
    {
        return $this->visibleForRoles;
    }

    /** @param list<string> $roles */
    public function setVisibleForRoles(array $roles): self
    {
        $this->visibleForRoles = $this->normalizeTokens($roles, true);

        return $this;
    }

    /** @return list<string> */
    public function getVisibleForScopes(): array
    {
        return $this->visibleForScopes;
    }

    /** @param list<string> $scopes */
    public function setVisibleForScopes(array $scopes): self
    {
        $this->visibleForScopes = $this->normalizeTokens($scopes, false);

        return $this;
    }

    /** @return list<string> */
    public function getVisibleForEnvironments(): array
    {
        return $this->visibleForEnvironments;
    }

    /** @param list<string> $environments */
    public function setVisibleForEnvironments(array $environments): self
    {
        $this->visibleForEnvironments = $this->normalizeTokens($environments, false);

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

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

    /** @return Collection<int, NavigationItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(NavigationItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setMenu($this);
        }

        return $this;
    }

    public function removeItem(NavigationItem $item): self
    {
        if ($this->items->removeElement($item) && $item->getMenu() === $this) {
            $item->setMenu(null);
        }

        return $this;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->touchModified();
    }

    public function __toString(): string
    {
        return '' !== $this->label ? $this->label : $this->menuKey;
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
