Pool-DBM (Pool Database Manager)
=======

**Requires** at least *PHP 5.3.3* with Doctrine 2 library. Compatible PHP 5.4 too.

PoolDBM package support doctrine common. You should know that this is an additional layer. But for to limit
potential performance lowering, the mapping does not use reflection.
It only dispatch features to doctrine managers.

Compatibility
-------------

The `composer.json` file in each branch indicates Doctrine2 compatibility.
Additionally, several tags are available:

 * `1.1.x` for Doctrine 2.3
 * `1.0.x` for Doctrine 2.2

Usage
-------------

The package has several class in debug mode, use these classes during development of your application.
Example `Pok\PoolDBM\ModelManagerDebug` check method parameter and class-metadata info.

Mapping:

``` xml

<multi-model model="MultiModel\User" repository-class="Repository\UserRepository">
    <model-reference manager="odm" field="id" />

    <model manager="orm" name="Entity\User" repository-method="findByIds">
        <field name="name" />
    </model>

    <model manager="odm" name="Document\User">
        <field name="profileContent" />
    </model>
</multi-model>
```