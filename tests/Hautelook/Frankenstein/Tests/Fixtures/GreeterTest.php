<?php

namespace Hautelook\Frankenstein\Tests\Fixtures;

use Hautelook\Frankenstein\TestCase;

class GreeterTest extends TestCase
{
    public function testGreet()
    {
        $greeter = new Greeter();

        $this
            ->string($greeter->greet('Adrien'))
                ->isEqualTo('Hello Adrien!')
        ;
    }
}
