<?php

namespace Bwlab\DoctrineLogBundle\EventListener;

use Bwlab\DoctrineLogBundle\Entity\AbstractLog;
use Bwlab\DoctrineLogBundle\Entity\Log as LogEntity;
use Bwlab\DoctrineLogBundle\Interfaces\LoggerHookInterface;
use Bwlab\DoctrineLogBundle\Service\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;


class Logger
{
    protected array $logs;

    private EntityManagerInterface $em;

    private Serializer $serializer;

    private AnnotationReader $reader;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $monolog;

    /**
     * @var array
     */
    private array $ignoreProperties = [];
    private object $entityRemoved;
    private string $logEntityClass;
    private LoggerHookInterface $loggerHook;

    public function __construct(
        EntityManagerInterface $em,
        Serializer             $serializer,
        LoggerInterface        $monolog,
                               $reader,
        LoggerHookInterface    $loggerHook,
        string                 $logEntityClass,
    )
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->reader = $reader;
        $this->monolog = $monolog;
        $this->logEntityClass = $logEntityClass;
        $this->loggerHook = $loggerHook;
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if (!empty($this->logs)) {
            foreach ($this->logs as $log) {
                $this->em->persist($log);
            }

            $this->logs = [];
            $this->em->flush();
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $this->log($entity, AbstractLog::ACTION_CREATE);
    }

    private function log($entity, string $action)
    {
            $this->reader->init($entity);
            if ($this->reader->isLoggable()) {
                $changes = null;
                if ($action === AbstractLog::ACTION_UPDATE) {
                    $uow = $this->em->getUnitOfWork();

                    // get changes => should be already computed here (is a listener)
                    $changeSet = $uow->getEntityChangeSet($entity);
                    // if we have no changes left => don't create revision log
                    if (count($changeSet) == 0) {
                        return;
                    }
                    // just getting the changed objects ids
                    foreach ($changeSet as $key => &$values) {
                        if (in_array($key, $this->ignoreProperties) || !$this->reader->isLoggable($key)) {
                            // ignore configured properties
                            unset($changeSet[$key]);
                        }

                        if (is_object($values[0]) && method_exists($values[0], 'getId')) {
                            $values[0] = $values[0]->getId();
                        }

                        if (is_object($values[1]) && method_exists($values[1], 'getId')) {
                            $values[1] = $values[1]->getId();
                        } elseif ($values[1] instanceof StreamInterface) {
                            $values[1] = (string)$values[1];
                        }
                    }
                    if (!empty($changeSet)) {
                        $changes = $this->serializer->serialize($changeSet, 'json');
                    }
                }

                if ($action === AbstractLog::ACTION_UPDATE && !$changes) {
                    // Log nothing
                } else {
                    $this->logs[] = $this->createLogEntity(
                        $entity,
                        $action,
                        $changes
                    );
                }
            }

    }

    private function createLogEntity($object, string $action, string $changes = null): AbstractLog
    {
        $today = new \DateTime();
        $log = new $this->logEntityClass;
        if ($action === AbstractLog::ACTION_REMOVE) {
            $foreignKey = $this->entityRemoved->getLogId();
        } else {
            $foreignKey = $object->getLogId();
        }

        $log
            ->setObjectClass(str_replace('Proxies\__CG__\\', '', get_class($object)))
            ->setForeignKey($foreignKey)
            ->setAction($action)
            ->setChanges($changes)
            ->setCreatedAt($today)
            ->setUpdatedAt($today);

        $this->loggerHook->addLogInfo($log, $object, $action, $changes);

        return $log;
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $this->log($entity, AbstractLog::ACTION_REMOVE);

    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        $this->log($entity, AbstractLog::ACTION_UPDATE);

    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $this->entityRemoved = clone $entity;

    }

    public function save(LogEntity $log): bool
    {
        $this->em->persist($log);
        $this->em->flush();

        return true;
    }
}

