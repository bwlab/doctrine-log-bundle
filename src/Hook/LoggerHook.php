<?php

namespace Bwlab\DoctrineLogBundle\Hook;


use Bwlab\DoctrineLogBundle\Interfaces\LoggerHookInterface;

class LoggerHook implements LoggerHookInterface
{

    public function doModifyEntity($object, string $action, string $changes = null)
    {
    }
}