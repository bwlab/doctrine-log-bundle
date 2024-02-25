<?php

namespace Bwlab\DoctrineLogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: "entity_log")]
class Log
{
    use BlameableEntity,
        TimestampableEntity;

    const ACTION_CREATE = 'create';

    const ACTION_UPDATE = 'update';

    const ACTION_REMOVE = 'remove';

    #[ORM\Id, ORM\Column(type: "integer"), ORM\GeneratedValue(strategy: "AUTO")]
    protected int $id;

    #[ORM\Column(type: "string", name: "object_class")]
    protected string $objectClass;

    #[ORM\Column(type: "integer", name: "foreign_key")]
    protected int $foreignKey;

    #[ORM\Column(type: "string")]
    protected string $action;

    #[ORM\Column(type: "text", nullable: true)]
    protected string $changes;

    public function __construct()
    {
        $this->created = new \DateTime();
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction($action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getChanges(): string
    {
        return $this->changes;
    }

    public function setChanges($changes): self
    {
        $this->changes = $changes;

        return $this;
    }

    public function getChangesArray()
    {
        return json_decode($this->changes, true);
    }

    public function getChangesSonata()
    {
        return json_encode(json_decode($this->changes), JSON_PRETTY_PRINT);
    }

    public function getForeignKey(): int
    {
        return $this->foreignKey;
    }

    public function setForeignKey($foreignKey): self
    {
        $this->foreignKey = $foreignKey;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getObjectClass(): string
    {
        return $this->objectClass;
    }

    public function setObjectClass($objectClass): self
    {
        $this->objectClass = $objectClass;

        return $this;
    }

    public function getPrettyClass(): string
    {
        return substr($this->objectClass, 18, strlen($this->objectClass));
    }
}
