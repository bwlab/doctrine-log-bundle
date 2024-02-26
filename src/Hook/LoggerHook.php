<?php

namespace Bwlab\DoctrineLogBundle\Hook;


use Bwlab\DoctrineLogBundle\Interfaces\LoggerHookInterface;

class LoggerHook implements LoggerHookInterface
{

    public function addLogInfo($logger, $object, string $action, string $changes = null)
    {
    }
}