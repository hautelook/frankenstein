<?php

namespace Hautelook\Frankenstein\Tests\Fixtures;

class GroupGreeter
{
    private $greeter;

    public function __construct(Greeter $greeter)
    {
        $this->greeter = $greeter;
    }

    public function greet(array $names)
    {
        return array_map(array($this->greeter, 'greet'), $names);
    }
}
