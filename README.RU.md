Entity Helper Bundle [![In English](https://img.shields.io/badge/Switch_To-English-green.svg?style=flat-square)](./README.md)
====================

Введение
--------

Помощник позволяет выполнять востребованные преобразования с сущностями управляемыми `Doctrine`.
Функциональность помощника доступна для работы с объектами, находящимся под управлением доктрины.
Помощник использует внутренний кэш для экономии вычислительных ресурсов.

Установка
---------

### Шаг 1: Загрузка бандла

Откройте консоль и, перейдя в директорию проекта, выполните следующую команду для загрузки наиболее подходящей
стабильной версии этого бандла:
```bash
    composer require adrenalinkin/entity-helper-bundle
```
*Эта команда подразумевает что [Composer](https://getcomposer.org) установлен и доступен глобально.*

### Шаг 2: Подключение бандла

После включите бандл добавив его в список зарегистрированных бандлов в `app/AppKernel.php` файл вашего проекта:

```php
<?php
// app/AppKernel.php

class AppKernel extends Kernel
{
    // ...

    public function registerBundles()
    {
        $bundles = [
            // ...

            new Linkin\Bundle\EntityHelperBundle\LinkinEntityHelperBundle(),
        ];

        return $bundles;
    }

    // ...
}
```

Использование
-------------

Вызов помощника через контейнер зависимостей:

```php
<?php

// ...

/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');

// ...

```

Допустим у нас есть сущность `AcmeBundle\Entity\User`:

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

Создает сущность и заполняет ее поля значениями, принимает на вход имя класса и массив полей.

```php
<?php

// ...

/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');

// Создать пустую сущность User
$user = $entityHelper->createEntity(User::class);

// Создать пустую сущность на основе короткого имени
$user = $entityHelper->createEntity('AcmeBundle:User');

// Создать сущность и заполнить поля значениями
$user = $entityHelper->createEntity('AcmeBundle:User', ['id' => 1, 'username' => 'acme-login']);

// Создать сущность и заполнить идентификатор если имя поля идентификатора заранее неизвестно
foreach (['AcmeBundle:User', 'AcmeBundle:Role'] as $className) {
    $object = $entityHelper->createEntity($className, [EntityHelper::IDENTITY => 1]);
}
```

### getEntityClassFull

Возвращает полное имя класса на основе объекта или имени класса.

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

Во всех случаях будет возвращена строка: `AcmeBundle\Entity\User`.

### getEntityClassShort

Возвращает сокращенное имя класса на основе объекта или имени класса.

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

Во всех случаях будет возвращена строка: `AcmeBundle:User`.

### getEntityIdNames

Возвращает список имен полей идентификаторов на основе объекта или имени класса.

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

Во всех случаях будет возвращен массив: `['id']`.

### getEntityIdName

Возвращает название поля идентификатора класса на основе объекта или имени класса.
**Важно**: если класс имеет два или более идентификатора то метод вернет название только одного первого поля.

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

Во всех случаях будет возвращена строка: `id`.

### getEntityIdValues

Возвращает список значений всех идентификаторов класса на основе объекта класса.

```php
<?php

// ...

/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');
/** @var \AcmeBundle\Entity\User $user */
$user = $entityHelper->createEntity('AcmeBundle:User', ['id' => 1]);

$idValues = $entityHelper->getEntityIdValues($user);
```

Будет возвращен массив: `[1]`.

### getEntityIdValue

Возвращает значение идентификатора класса на основе объекта класса.
**Важно**: если класс имеет два или более идентификатора то метод вернет значение только одного первого поля.

```php
<?php

// ...

/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
$entityHelper = $container->get('linkin_entity_helper.helper');
/** @var \AcmeBundle\Entity\User $user */
$user = $entityHelper->createEntity('AcmeBundle:User', ['id' => 1]);

$idValue = $entityHelper->getEntityIdValues($user);
```

Будет возвращено число: `1`.

### getEntityMetadata

Возвращает мета-данные класса `\Doctrine\ORM\Mapping\ClassMetadata` на основе объекта или имени класса.

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

Во всех случаях будет возвращен объект: `\Doctrine\ORM\Mapping\ClassMetadata`.

### isManagedByDoctrine

Определяет находится ли под управлением `Doctrine` запрашиваемый класс.

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

Во всех случаях будет возвращено логическое значение: `true`.

Лицензия
--------

[![license](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](./LICENSE)
