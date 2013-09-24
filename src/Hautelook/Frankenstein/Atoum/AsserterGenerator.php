<?php

namespace Hautelook\Frankenstein\Atoum;

use Hautelook\Frankenstein\TestCase;
use mageekguy\atoum\asserter;
use mageekguy\atoum\asserter\generator;
use mageekguy\atoum\exceptions;

/**
 * @author Adrien Brault <adrien.brault@gmail.com>
 */
class AsserterGenerator extends generator
{
    /**
     * @var TestCase
     */
    private $test;

    /**
     * @param TestCase $test
     */
    public function setTest(TestCase $test = null)
    {
        $this->test = $test;
    }

    /**
     * @return TestCase
     */
    public function getTest()
    {
        return $this->test;
    }

    public function asserterPass(asserter $asserter)
    {
        $this->test->addToAssertionCount(1);

        return $this;
    }

    public function getAsserterClass($asserter)
    {
        $asserterLower = strtolower($asserter);

        if (in_array($asserterLower, array('testedClass', 'mock', 'adapter', 'phpFunction'))) {
            return null;
        }

        return parent::getAsserterClass($asserter);
    }

    public function __get($property)
    {
        return $this->test->getAssertionManager()->invoke($property);
    }

    public function __call($method, $arguments)
    {
        return $this->test->getAssertionManager()->invoke($method, $arguments);
    }
}
