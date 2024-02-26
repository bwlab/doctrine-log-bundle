<?php

namespace Bwlab\DoctrineLogBundle\Interfaces;

interface LoggerHookInterface
{
    public function doModifyEntity($object, string $action, string $changes = null);
}