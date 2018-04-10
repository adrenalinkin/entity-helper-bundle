Entity Helper Bundle [![На Русском](https://img.shields.io/badge/Перейти_на-Русский-green.svg?style=flat-square)](./README.RU.md)
====================

Introduction
--------

Helper allows you to perform often required operations with entities which managed by `Doctrine`.

Installation
------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the following command to download the latest stable
version of this bundle:
```bash
    composer require adrenalinkin/entity-helper-bundle
```
*This command requires you to have [Composer](https://getcomposer.org) install globally.*

### Step 2: Enable the Bundle

```php
<?php
// app/AppKernel.php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * The Kernel is the heart of the Symfony system
 */
class AppKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            // ...

            new Linkin\Bundle\EntityHelperBundle\LinkinEntityHelperBundle(),
        ];

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
```

Usage
-----

Get entity helper by dependencies container:

```php
<?php

// ...

/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');

// ...

```

Let's say we have an entity `AcmeBundle\Entity\User`:

```php
<?php
// AcmeBundle\Entity\User.php

// ...
class User
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id_user")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    private $password;

    /**
     * @ORM\Column(type="string", nullable=false, length=50, unique=true)
     *
     * @var string
     */
    private $username;

    // ...
}
```

### createEntity

Create instance of the class, which managed by Doctrine, by received class name.

```php
<?php

// ...

/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');

// Create empty User entity
$user = $entityHelper->createEntity(User::class);

// Create empty User entity by received short name
$user = $entityHelper->createEntity('AcmeBundle:User');

// Create User entity and fill some fields
$user = $entityHelper->createEntity('AcmeBundle:User', ['id' => 1, 'username' => 'acme-login']);

// Create User entity and fill identity field in that case when you don't know the name of the identity field
foreach (['AcmeBundle:User', 'AcmeBundle:Role'] as $className) {
    $object = $entityHelper->createEntity($className, [EntityHelper::IDENTITY => 1]);
}
```

### getEntityClassFull

Returns full name of the received entity or class name.

```php
<?php

// ...

/** @var \AcmeBundle\Entity\User $user */
$user = new User();
/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');

$className = $entityHelper->getEntityClassFull(User::class);
$className = $entityHelper->getEntityClassFull('AcmeBundle:User');
$className = $entityHelper->getEntityClassFull($user);
```

In the all cases will be return string value: `AcmeBundle\Entity\User`.

### getEntityClassShort

Returns short name of the received entity or class name.

```php
<?php

// ...

/** @var \AcmeBundle\Entity\User $user */
$user = new User();
/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');

$className = $entityHelper->getEntityClassShort(User::class);
$className = $entityHelper->getEntityClassShort('AcmeBundle\Entity\User');
$className = $entityHelper->getEntityClassShort($user);
```

In the all cases will be return string value: `AcmeBundle:User`.

### getEntityIdNames

Returns an array of identifier field names numerically indexed.

```php
<?php

// ...

/** @var \AcmeBundle\Entity\User $user */
$user = new User();
/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');

$names = $entityHelper->getEntityIdNames(User::class);
$names = $entityHelper->getEntityIdNames('AcmeBundle\Entity\User');
$names = $entityHelper->getEntityIdNames('AcmeBundle:User');
$names = $entityHelper->getEntityIdNames($user);
```

In the all cases will be return array value: `['id']`.

### getEntityIdName

Returns single identifier field name by received entity object or class name. 
**Important**: In that case when entity have several identifier names method will be return only first identifier name.

```php
<?php

// ...

/** @var \AcmeBundle\Entity\User $user */
$user = new User();
/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');

$idName = $entityHelper->getEntityIdName(User::class);
$idName = $entityHelper->getEntityIdName('AcmeBundle\Entity\User');
$idName = $entityHelper->getEntityIdName('AcmeBundle:User');
$idName = $entityHelper->getEntityIdName($user);
```

In the all cases will be return string value: `id`.

### getEntityIdValues

Returns an array of identifier values by received entity object.

```php
<?php

// ...

/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');
/** @var \AcmeBundle\Entity\User $user */
$user = $entityHelper->createEntity('AcmeBundle:User', ['id' => 1]);

$idValues = $entityHelper->getEntityIdValues($user);
```

Will be return array value: `[1]`.

### getEntityIdValue

Returns single identifier field value by received entity object. 
**Important**: In that case when entity have several identifier method will be return only first identifier field value.

```php
<?php

// ...

/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');
/** @var \AcmeBundle\Entity\User $user */
$user = $entityHelper->createEntity('AcmeBundle:User', ['id' => 1]);

$idValue = $entityHelper->getEntityIdValues($user);
```

Will be return numeric value: `1`.

### getEntityMetadata

Returns `\Doctrine\ORM\Mapping\ClassMetadata` object for received entity or class name.

```php
<?php

// ...

/** @var \AcmeBundle\Entity\User $user */
$user = new User();
/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');

$metaData = $entityHelper->getEntityMetadata(User::class);
$metaData = $entityHelper->getEntityMetadata('AcmeBundle\Entity\User');
$metaData = $entityHelper->getEntityMetadata('AcmeBundle:User');
$metaData = $entityHelper->getEntityMetadata($user);
```

In the all cases will be return object value: `\Doctrine\ORM\Mapping\ClassMetadata`.

### isManagedByDoctrine

Determines whether the requested class is under the control of `Doctrine`.

```php
<?php

// ...

/** @var \AcmeBundle\Entity\User $user */
$user = new User();
/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');

$isManaged = $entityHelper->isManagedByDoctrine(User::class);
$isManaged = $entityHelper->isManagedByDoctrine('AcmeBundle\Entity\User');
$isManaged = $entityHelper->isManagedByDoctrine('AcmeBundle:User');
$isManaged = $entityHelper->isManagedByDoctrine($user);
```

In the all cases will be return bool value: `true`.

License
-------

[![license](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](./LICENSE)
