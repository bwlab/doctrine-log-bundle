<?php

namespace Bwlab\DoctrineLogBundle\Interfaces;

interface LoggerHookInterface
{
    public function addLogInfo($logger, $object, string $action, string $changes = null);
}