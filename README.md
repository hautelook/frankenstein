Frankenstein
============

[![Build Status](https://secure.travis-ci.org/hautelook/frankenstein.png)](http://travis-ci.org/hautelook/frankenstein)

This let you use PHPUnit with [Prophecy](https://github.com/phpspec/prophecy) mocks and [Atoum](http://docs.atoum.org/)
asserters.

Usage
-----

Your test class must extend `Hautelook\Frankenstein\TestCase`:

```php
<?php

namespace AcmeDemoBundle\Tests\Foo;

use Hautelook\Frankenstein\TestCase;

class BarTest extends TestCase
{

}
```

**When extending from the provided `TestCase` everything else than Atoum asserters and Prophecy mocks are restricted.**

You will have to use Prophecy to mock, using `$this->prophesize()`:

```php
public function test()
{
    $routerProphecy = $this->prophesize('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
    $routerProphecy
        ->generate('acme_demo_index')
        ->willReturn('/acme')
    ;

    $foo = new Foo($routerProphecy->reveal());
}
```

You will have to use atoum asserters instead of phpunit's:

```php
public function test()
{
    $foo = new Foo();

    $this
        ->string($foo->getName())
            ->isEqualTo('foo foo foo')
    ;
}
```

Running the Tests
-----------------

Install the [Composer](http://getcomposer.org/) `dev` dependencies:

    php composer.phar install

Then, run the test suite using phpunit:

    bin/phpunit

License
-------

Frankenstein is released under the MIT License. See the bundled LICENSE file for details.

Credits
-------

This library has copies of code from https://github.com/phpspec/prophecy-phpunit and https://github.com/atoum/atoum .