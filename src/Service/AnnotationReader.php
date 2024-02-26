<?php

namespace Bwlab\DoctrineLogBundle\Service;

use AllowDynamicProperties;
use Bwlab\DoctrineLogBundle\Annotation\Exclude;
use Bwlab\DoctrineLogBundle\Annotation\Log;
use Bwlab\DoctrineLogBundle\Annotation\Loggable;
use Doctrine\ORM\Mapping\Id;
use ReflectionAttribute;
use ReflectionClass;
use Doctrine\Common\Annotations\Reader;

#[AllowDynamicProperties] class AnnotationReader
{

    private ?ReflectionAttribute $attribute;

    private $entity;
    private array $ids = [];


    public function init($entity): void
    {

        $this->entity = $entity;
        $reflectionClass = new ReflectionClass(str_replace('Proxies\__CG__\\', '', get_class($entity)));
        if(count($reflectionClass->getAttributes(Loggable::class))===0) {
            $this->attribute = null;
            return;
        }
       $this->attribute = $reflectionClass->getAttributes(Loggable::class)[0];
    }

    public function isLoggable(?string $property = null): bool
    {
        if(!$property) {
            return $this->attribute ? $this->attribute->getName() === Loggable::class: false;
        }
        
        return  $this->isPropertyLoggable($property);
    }

    private function isPropertyLoggable(string $property): bool
    {
        return true;
        //@todo correggere
        $property = new \ReflectionProperty(
            str_replace('Proxies\__CG__\\', '', get_class($this->entity)),
            $property
        );

        if ($this->attribute->strategy === Loggable::STRATEGY_EXCLUDE_ALL) {
            // check for log annotation
            $annotation = $this->reader->getPropertyAnnotation($property, Log::class);

            return $annotation instanceof Log;
        }

        // include all strategy, check for exclude
        $annotation = $this->reader->getPropertyAnnotation($property, Exclude::class);

        return !$annotation instanceof Exclude;
    }
}
