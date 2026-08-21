<?php

declare(strict_types=1);

namespace App\Navigating\EventSubscriber;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use App\Navigating\Service\Navigation\Snapshot\NavigationAutoBackupService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

final class NavigationAutoBackupSubscriber implements EventSubscriber
{
    private bool $dirty = false;

    public function __construct(
        private readonly NavigationAutoBackupService $autoBackup,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @return list<string> */
    public function getSubscribedEvents(): array
    {
        return [Events::postPersist, Events::postUpdate, Events::postRemove, Events::postFlush];
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $this->markDirty($event->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $this->markDirty($event->getObject());
    }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $this->markDirty($event->getObject());
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if (!$this->dirty) {
            return;
        }

        $this->dirty = false;

        if ($event->getObjectManager()->getConnection()->getTransactionNestingLevel() > 0) {
            return;
        }

        try {
            $this->autoBackup->writeLatest();
        } catch (\Throwable $exception) {
            $this->logger->error('Automatic Navigating backup failed after Doctrine flush.', [
                'exception' => $exception,
            ]);
        }
    }

    private function markDirty(object $entity): void
    {
        if ($entity instanceof NavigationMenu || $entity instanceof NavigationItem) {
            $this->dirty = true;
        }
    }
}
