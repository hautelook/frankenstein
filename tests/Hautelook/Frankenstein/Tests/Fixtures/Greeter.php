<?php

namespace Hautelook\Frankenstein\Tests\Fixtures;

class Greeter
{
    private $format;

    public function __construct($format = null)
    {
        $this->format = $format ?: 'Hello %s!';
    }

    public function greet($name)
    {
        return sprintf($this->format, $name);
    }
}
