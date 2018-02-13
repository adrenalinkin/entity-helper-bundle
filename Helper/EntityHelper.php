<?php

/*
 * This file is part of the LinkinEntityHelperBundle package.
 *
 * (c) Viktor Linkin <adrenalinkin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Linkin\Bundle\EntityHelperBundle\Helper;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class EntityHelper
{
    /**
     * Offset for the identity
     */
    const IDENTITY = '__ID__';

    /**
     * Contains already evaluated data
     *
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
     * Create instance of the class, which managed by Doctrine, by received class name
     *
     * @param string $className
     * @param array  $fields
     *
     * @return object|null
     */
    public function createEntity($className, array $fields = [])
    {
        $metadata = $this->getEntityMetadata($className);

        if (is_null($metadata)) {
            return null;
        }

        $reflection = $metadata->getReflectionClass();

        try {
            $entity = $reflection->newInstance();
        } catch (\Exception $e) {
            $entity = $reflection->newInstanceWithoutConstructor();
        }

        foreach ($fields as $fieldName => $fieldValue) {
            if (self::IDENTITY === $fieldName) {
                $property = $reflection->getProperty($this->getEntityIdName($className));
            } else {
                $property = $reflection->getProperty($fieldName);
            }

            try {
                $property->setValue($entity, $fieldValue);
            } catch (\Exception $e) {
                $property->setAccessible(true);
                $property->setValue($entity, $fieldValue);
                $property->setAccessible(false);
            }
        }

        return $entity;
    }

    /**
     * Returns full name of the received entity or class name.
     *
     * @param object|string $entity Entity object or just entity name in the full or shot notation
     *
     * @return string For example 'AcmeBundle\Entity\User'
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
     * Returns short name of the received entity or class name.
     *
     * @param string|object $entity Entity object or just entity name in the full or shot notation
     *
     * @return string For example 'AcmeBundle:User'
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
            if (false === strpos($entity, $full)) {
                continue;
            }

            $short .= ':'.ltrim(str_replace($full, '', $entity), '\\');
            $this->cache[$full][$cacheKey] = $short;

            return $short;
        }

        return $entity;
    }

    /**
     * Returns single identifier field name. In that case when entity have several identifier names
     * method will be return only first identifier name.
     *
     * @param object|string $entity Entity object or just entity name in the full or shot notation
     *
     * @return string|null
     */
    public function getEntityIdName($entity)
    {
        return ($fieldNames = $this->getEntityIdNames($entity)) ? reset($fieldNames) : null;
    }

    /**
     * Returns an array of identifier field names numerically indexed @see ClassMetadataInfo::getIdentifierFieldNames
     *
     * @param object|string $entity Entity object or just entity name in the full or shot notation
     *
     * @return array
     */
    public function getEntityIdNames($entity)
    {
        return ($metadata = $this->getEntityMetadata($entity)) ? $metadata->getIdentifierFieldNames() : [];
    }

    /**
     * Returns single identifier field value. In that case when entity have several identifier
     * method will be return only first identifier field value.
     *
     * @param object $entity Entity object
     *
     * @return int|string|null
     */
    public function getEntityIdValue($entity)
    {
        return ($entityIdentifier = $this->getEntityIdValues($entity)) ? reset($entityIdentifier) : null;
    }

    /**
     * Returns an array of identifier values @see ClassMetadataInfo::getIdentifierValues
     *
     * @param object $entity Entity object
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
     * Returns ClassMetadata object for received entity
     *
     * @param string|object $entity Entity object or just entity name in the full or shot notation
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata|null
     */
    public function getEntityMetadata($entity)
    {
        if (!$this->isManagedByDoctrine($entity)) {
            return null;
        }

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

    /**
     * Returns is received entity managed by doctrine or not
     *
     * @param object|string $entity Entity object or just entity name in the full or shot notation
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
        } catch (\Exception $e) {
            $isManaged = false;
        }

        $this->cache[$entity][$cacheKey] = $isManaged;

        return $isManaged;
    }

    /**
     * Returns appropriated property value form the local cache if exists. Otherwise return default value.
     *
     * @param string     $className Entity entity class name in the FULL notation only
     * @param string     $property  Name of the property
     * @param bool|mixed $default   Value which should be return be default when requested data
     *                              does not found in the cache
     *
     * @return mixed
     */
    private function getFromCache($className, $property, $default = false)
    {
        return empty($this->cache[$className][$property]) ? $default : $this->cache[$className][$property];
    }

    /**
     * Returns full name of the entity when received object and returns value as is when received string
     *
     * @param object|string $entity Entity object or just entity name in the full or shot notation
     *
     * @return string
     */
    private function toString($entity)
    {
        return is_object($entity) ? ClassUtils::getClass($entity) : $entity;
    }
}
