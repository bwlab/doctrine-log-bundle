<?php

namespace Bwlab\DoctrineLogBundle\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Loggable
{
    const STRATEGY_EXCLUDE_ALL = 'exclude_all';
    const STRATEGY_INCLUDE_ALL = 'include_all';

    /**
     * @var string
     * @Enum({"exclude_all", "include_all"})
     */
    public string $strategy = self::STRATEGY_INCLUDE_ALL;
}
