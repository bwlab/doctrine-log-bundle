<?php

namespace Bwlab\DoctrineLogBundle\EventListener;

use Bwlab\DoctrineLogBundle\Entity\Log as LogEntity;
use Bwlab\DoctrineLogBundle\Entity\LogAbstract;
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

    private LoggerInterface $monolog;
    private ?LoggerHookInterface $loggerHook;

    /**
     * @var array
     */
    private array $ignoreProperties = [];
    private object $entityRemoved;
    private ?string $logEntityClass;

    public function __construct(
        EntityManagerInterface $em,
        Serializer             $serializer,
        LoggerInterface        $monolog,
                               $reader,
        ?LoggerHookInterface   $loggerHook,
        ?string                $logEntityClass,
//        ?array                 $ignoreProperties
    )
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->reader = $reader;
        $this->monolog = $monolog;
        $this->logEntityClass = $logEntityClass;
        $this->loggerHook = $loggerHook;
        //$this->ignoreProperties = $ignoreProperties;
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
        $this->log($entity, LogAbstract::ACTION_CREATE);
    }

    private function log($entity, string $action)
    {
        try {
            $this->reader->init($entity);
            if ($this->reader->isLoggable()) {
                $changes = null;
                if ($action === LogAbstract::ACTION_UPDATE) {
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

                if ($action === LogAbstract::ACTION_UPDATE && !$changes) {
                    // Log nothing
                } else {
                    $this->logs[] = $this->createLogEntity(
                        $entity,
                        $action,
                        $changes
                    );
                }
            }
        } catch (\Exception $e) {
            $this->monolog->error($e->getMessage());
        }
    }

    private function createLogEntity($object, string $action, string $changes = null): LogAbstract
    {
        $today = new \DateTime();

        $log = new $this->logEntityClass;
        if ($action === LogAbstract::ACTION_REMOVE) {
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

        $this->loggerHook->doModifyEntity($object, $action, $changes);
        return $log;
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $this->log($entity, LogAbstract::ACTION_REMOVE);

    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        $this->log($entity, LogAbstract::ACTION_UPDATE);

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

