<?php

namespace Linkin\Bundle\EntityHelperBundle\Helper;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class EntityHelper
{
    const IDENTITY = '__ID__';

    /**
     * @var array
     */
    private $cache = [];

    /**
     * Needed to resolve conflict in getEntityClassFull and isManagedByDoctrine functions
     *
     * @var bool
     */
    private $managed = false;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $className
     * @param array  $fields
     *
     * @return object|null
     */
    public function createEntity($className, array $fields = [])
    {
        if (null !== $metadata = $this->getEntityMetadata($className)) {
            $reflection = $metadata->getReflectionClass();

            try {
                $entity = $reflection->newInstance();
            } catch (\Exception $e) {
                $entity = $reflection->newInstanceWithoutConstructor();
            }

            foreach ($fields as $fieldName => $fieldValue) {
                if ($fieldName == self::IDENTITY) {
                    $property = $reflection->getProperty($this->getEntityIdName($className));
                } else {
                    $property = $reflection->getProperty($fieldName);
                }

                try {
                    $property->setValue($entity, $fieldValue);
                } catch (\ReflectionException $e) {
                    $property->setAccessible(true);
                    $property->setValue($entity, $fieldValue);
                    $property->setAccessible(false);
                }
            }

            return $entity;
        }

        return null;
    }

    /**
     * @param object|string $entity
     *
     * @return string
     */
    public function getEntityClassFull($entity)
    {
        $entity = $this->toString($entity);

        if (isset($this->cache[$entity])) {
            return $entity;
        }

        if ($this->managed || $this->isManagedByDoctrine($entity)) {
            /** @var \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetaData */
            $classMetaData        = $this->entityManager->getClassMetadata($entity);
            $entity               = $classMetaData->getName();
            $this->cache[$entity] = ['metadata' => $classMetaData];
            $this->managed        = false;
        } else {
            $this->cache[$entity] = [];
        }

        return $entity;
    }

    /**
     * @param string|object $entity
     *
     * @return string
     */
    public function getEntityClassShort($entity)
    {
        $cacheKey = 'short';
        $entity   = $this->getEntityClassFull($entity);
        $short    = $this->getFromCache($entity, $cacheKey);

        if (false !== $short) {
            return $short;
        }

        foreach ($this->entityManager->getConfiguration()->getEntityNamespaces() as $short => $full) {
            if (false !== strpos($entity, $full)) {
                $short .= ':' . ltrim(str_replace($full, '', $entity), '\\');
                $this->cache[$full][$cacheKey] = $short;
                return $short;
            }
        }

        return $entity;
    }

    /**
     * @param object|string $entity
     *
     * @return string[]
     */
    public function getEntityIdNames($entity)
    {
        return ($metadata = $this->getEntityMetadata($entity)) ? $metadata->getIdentifierFieldNames() : [];
    }

    /**
     * @param object|string $entity
     *
     * @return string|null
     */
    public function getEntityIdName($entity)
    {
        return ($fieldNames = $this->getEntityIdNames($entity)) ? current($fieldNames) : null;
    }

    /**
     * @param object $entity
     *
     * @return mixed|null
     */
    public function getEntityIdValue($entity)
    {
        return ($entityIdentifier = $this->getEntityIdValues($entity)) ? current($entityIdentifier) : null;
    }

    /**
     * @param object $entity
     *
     * @return array
     */
    public function getEntityIdValues($entity)
    {
        return ($metadata = $this->getEntityMetadata($entity)) ? $metadata->getIdentifierValues($entity) : [];
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param string|object $entity
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata|null
     */
    public function getEntityMetadata($entity)
    {
        if ($this->isManagedByDoctrine($entity)) {
            $cacheKey = 'metadata';
            $entity   = $this->getEntityClassFull($entity);
            $metadata = $this->getFromCache($entity, $cacheKey);

            if (false !== $metadata) {
                return $metadata;
            }

            $metadata = $this->entityManager->getClassMetadata($entity);
            $this->cache[$metadata->getName()][$cacheKey] = $metadata;

            return $metadata;
        }

        return null;
    }

    /**
     * @param string $className
     * @param string $property
     * @param bool   $default
     *
     * @return mixed
     */
    private function getFromCache($className, $property, $default = false)
    {
        return empty($this->cache[$className][$property]) ? $default : $this->cache[$className][$property];
    }

    /**
     * @param object|string $entity
     *
     * @return bool
     */
    public function isManagedByDoctrine($entity)
    {
        $cacheKey  = 'managed';
        $entity    = $this->toString($entity);
        $isManaged = $this->getFromCache($entity, $cacheKey, null);

        if (null !== $isManaged) {
            return $isManaged;
        }

        try {
            $this->managed = $isManaged = (bool) $this->entityManager->getReference($entity, 0);
            $entity = $this->getEntityClassFull($entity);
        } catch (MappingException $e) {
            $isManaged = false;
        }

        $this->cache[$entity][$cacheKey] = $isManaged;

        return $isManaged;
    }

    /**
     * @param object|string $entity
     *
     * @return string
     */
    private function toString($entity)
    {
        return is_object($entity) ? ClassUtils::getClass($entity) : $entity;
    }
}
