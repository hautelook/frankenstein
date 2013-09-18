<?php

namespace Hautelook\Frankenstein\Tests\Fixtures;

use Hautelook\Frankenstein\TestCase;

class GroupGreeterTest extends TestCase
{
    public function testGreet()
    {
        $greeterProphecy = $this->prophesize(__NAMESPACE__ . '\\Greeter');
        $groupGreeter = new GroupGreeter($greeterProphecy->reveal());

        $greeterProphecy
            ->greet($this->arg->any())
            ->will(function ($args) {
                return 'Hey ' . $args[0];
            })
            ->shouldBeCalledTimes(2)
        ;

        $this
            ->array($groupGreeter->greet(array('Adrien', 'Baldur')))
                ->hasSize(2)
                ->contains('Hey Adrien')
                ->contains('Hey Baldur')
        ;
    }
}
