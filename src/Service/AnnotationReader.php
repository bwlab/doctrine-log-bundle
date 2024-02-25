<?php

namespace Bwlab\DoctrineLogBundle\Service;

use Bwlab\DoctrineLogBundle\Annotation\Exclude;
use Bwlab\DoctrineLogBundle\Annotation\Log;
use Bwlab\DoctrineLogBundle\Annotation\Loggable;
use ReflectionClass;
use Doctrine\Common\Annotations\Reader;

class AnnotationReader
{
    private Reader $reader;

    private Loggable $classAnnotation;

    private $entity;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function init($entity)
    {
        $this->entity = $entity;
        $class = new ReflectionClass(str_replace('Proxies\__CG__\\', '', get_class($entity)));
        $this->classAnnotation = $this->reader->getClassAnnotation($class, Loggable::class);
    }

    public function isLoggable(?string $property): bool
    {
        return !$property ? $this->classAnnotation instanceof Loggable : $this->isPropertyLoggable($property);
    }

    private function isPropertyLoggable(string $property): bool
    {
        $property = new \ReflectionProperty(
            str_replace('Proxies\__CG__\\', '', get_class($this->entity)),
            $property
        );

        if ($this->classAnnotation->strategy === Loggable::STRATEGY_EXCLUDE_ALL) {
            // check for log annotation
            $annotation = $this->reader->getPropertyAnnotation($property, Log::class);

            return $annotation instanceof Log;
        }

        // include all strategy, check for exclude
        $annotation = $this->reader->getPropertyAnnotation($property, Exclude::class);

        return !$annotation instanceof Exclude;
    }
}
