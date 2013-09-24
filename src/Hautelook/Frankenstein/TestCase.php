<?php

namespace Hautelook\Frankenstein;

use mageekguy\atoum\asserters;
use mageekguy\atoum\asserter;
use mageekguy\atoum\test;
use Hautelook\Frankenstein\Atoum\AsserterGenerator;
use Hautelook\Frankenstein\Exception\PHPUnitDeprecatedException;
use PHPUnit_Framework_Constraint;
use PHPUnit_Util_XML;
use PHPUnit_Util_Type;
use PHPUnit_Framework_Constraint_IsIdentical;
use Prophecy\Argument;
use Prophecy\Exception\Prediction\PredictionException;
use Prophecy\PhpUnit\ProphecyTestCase;
use Prophecy\Prophet;

/**
 * @author Adrien Brault <adrien.brault@gmail.com>
 *
 * @method \mageekguy\atoum\test when($mixed)
 * @method \mageekguy\atoum\test assert()
 * @method \mageekguy\atoum\test dump()
 * @method \mageekguy\atoum\test if()
 * @method \mageekguy\atoum\test and()
 * @method \mageekguy\atoum\test then()
 * @method \mageekguy\atoum\test given()
 *
 * @method \mageekguy\atoum\asserters\boolean boolean($value, $label = null)
 * @method \mageekguy\atoum\asserters\castToString castToString($value, $label = null, $charlist = null, $checkType = true)
 * @method \mageekguy\atoum\asserters\dateInterval dateInterval($value, $checkType = true)
 * @method \mageekguy\atoum\asserters\dateTime dateTime($value, $checkType = true)
 * @method \mageekguy\atoum\asserters\error error($message = null, $type = null)
 * @method \mageekguy\atoum\asserters\exception exception($value, $label = null, $check = true)
 * @method \mageekguy\atoum\asserters\extension extension($name)
 * @method \mageekguy\atoum\asserters\float float($value, $label = null)
 * @method \mageekguy\atoum\asserters\hash hash($value, $label = null, $charlist = null, $checkType = true)
 * @method \mageekguy\atoum\asserters\integer integer($value, $label = null)
 * @method \mageekguy\atoum\asserters\mysqlDateTime mysqlDateTime($value, $checkType = true)
 * @method \mageekguy\atoum\asserters\object object($value, $checkType = true)
 * @method \mageekguy\atoum\asserters\output output($value = null, $label = null, $charlist = null, $checkType = true)
 * @method \mageekguy\atoum\asserters\phpArray phpArray($value, $label = null)
 * @method \mageekguy\atoum\asserters\phpArray array($value, $label = null)
 * @method \mageekguy\atoum\asserters\phpClass phpClass($class)
 * @method \mageekguy\atoum\asserters\phpClass class($class)
 * @method \mageekguy\atoum\asserters\sizeOf sizeOf($value, $label = null)
 * @method \mageekguy\atoum\asserters\stream stream($stream)
 * @method \mageekguy\atoum\asserters\string string($value, $label = null, $charlist = null, $checkType = true)
 * @method \mageekguy\atoum\asserters\utf8String utf8String($value, $label = null, $charlist = null, $checkType = true)
 * @method \mageekguy\atoum\asserters\variable variable($value)
 *
 * @property \mageekguy\atoum\test $if
 * @property \mageekguy\atoum\test $and
 * @property \mageekguy\atoum\test $then
 * @property \mageekguy\atoum\test $given
 * @property \mageekguy\atoum\test $assert
 * @property \mageekguy\atoum\asserters\string $string
 * @property \mageekguy\atoum\asserters\output $output
 * @property \mageekguy\atoum\asserters\error $error
 *
 * @property Argument $arg
 */
class TestCase extends ProphecyTestCase
{
    /**
     * @var Argument
     */
    private $argument;

    /**
     * @var test\assertion\manager
     */
    private $assertionManager = null;

    /**
     * @var AsserterGenerator
     */
    private $asserterGenerator = null;

    public function __get($property)
    {
        if ('arg' === $property) {
            return $this->argument;
        }

        return $this->assertionManager->__get($property);
    }

    public function __call($method, array $arguments)
    {
        return $this->assertionManager->__call($method, $arguments);
    }

    /**
     * @return Argument
     */
    protected function arg()
    {
        return $this->argument;
    }

    protected function setUp()
    {
        parent::setUp();

        $this->argument = new Argument();
        $this->setAsserterGenerator();
        $this->setAssertionManager();
    }

    protected function tearDown()
    {
        $this->argument = null;
        $this->asserterGenerator->setTest(null);
        $this->asserterGenerator = null;
        $this->assertionManager = null;

        parent::teardown();
    }

    protected function onNotSuccessfulTest(\Exception $e)
    {
        if ($e instanceof asserter\exception) {
            $e = new \PHPUnit_Framework_AssertionFailedError($e->getMessage(), $e->getCode());
        }

        return parent::onNotSuccessfulTest($e);
    }

    public function setAssertionManager(test\assertion\manager $assertionManager = null)
    {
        $this->assertionManager = $assertionManager ?: new test\assertion\manager();

        $test = $this;

        $this->assertionManager
            ->setHandler('when', function($mixed) use ($test) {
                if ($mixed instanceof \closure) {
                    $mixed();
                }

                return $test;
            })
            ->setHandler('dump', function() use ($test) {
                call_user_func_array('var_dump', func_get_args());

                return $test;
            })
            ->setPropertyHandler('exception', function() {
                return asserters\exception::getLastValue();
            })
        ;

        $returnTest = function() use ($test) { return $test; };

        $this->assertionManager
            ->setHandler('assert', $returnTest)
            ->setHandler('if', $returnTest)
            ->setHandler('and', $returnTest)
            ->setHandler('then', $returnTest)
            ->setHandler('given', $returnTest)
        ;

        $asserterGenerator = $this->asserterGenerator;

        $this->assertionManager
            ->setDefaultHandler(function($asserter, $arguments) use ($asserterGenerator) {
                return $asserterGenerator->getAsserterInstance($asserter, $arguments);
            })
        ;

        return $this;
    }

    public function setAsserterGenerator(AsserterGenerator $generator = null)
    {
        if ($generator === null) {
            $generator = new AsserterGenerator();
        }

        $generator->setTest($this);

        $this->asserterGenerator = $generator
            ->setAlias('array', 'phpArray')
            ->setAlias('class', 'phpClass')
        ;

        return $this;
    }

    public function getAsserterGenerator()
    {
        return $this->asserterGenerator;
    }

    public function getAssertionManager()
    {
        return $this->assertionManager;
    }

    // -----------------------------------------------------------------------------------------------------------------
    //
    // DEPRECATED STUFF
    //
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @deprecated
     */
    public static function assertArrayHasKey($key, $array, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertArrayNotHasKey($key, $array, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertContains($needle, $haystack, $message = '', $ignoreCase = FALSE, $checkForObjectIdentity = TRUE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeContains($needle, $haystackAttributeName, $haystackClassOrObject, $message = '', $ignoreCase = FALSE, $checkForObjectIdentity = TRUE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertNotContains($needle, $haystack, $message = '', $ignoreCase = FALSE, $checkForObjectIdentity = TRUE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeNotContains($needle, $haystackAttributeName, $haystackClassOrObject, $message = '', $ignoreCase = FALSE, $checkForObjectIdentity = TRUE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertContainsOnly($type, $haystack, $isNativeType = NULL, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertContainsOnlyInstancesOf($classname, $haystack, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeContainsOnly($type, $haystackAttributeName, $haystackClassOrObject, $isNativeType = NULL, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertNotContainsOnly($type, $haystack, $isNativeType = NULL, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeNotContainsOnly($type, $haystackAttributeName, $haystackClassOrObject, $isNativeType = NULL, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertCount($expectedCount, $haystack, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeCount($expectedCount, $haystackAttributeName, $haystackClassOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertNotCount($expectedCount, $haystack, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeNotCount($expectedCount, $haystackAttributeName, $haystackClassOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertEquals($expected, $actual, $message = '', $delta = 0, $maxDepth = 10, $canonicalize = FALSE, $ignoreCase = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeEquals($expected, $actualAttributeName, $actualClassOrObject, $message = '', $delta = 0, $maxDepth = 10, $canonicalize = FALSE, $ignoreCase = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertNotEquals($expected, $actual, $message = '', $delta = 0, $maxDepth = 10, $canonicalize = FALSE, $ignoreCase = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeNotEquals($expected, $actualAttributeName, $actualClassOrObject, $message = '', $delta = 0, $maxDepth = 10, $canonicalize = FALSE, $ignoreCase = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertEmpty($actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeEmpty($haystackAttributeName, $haystackClassOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertNotEmpty($actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeNotEmpty($haystackAttributeName, $haystackClassOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertGreaterThan($expected, $actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeGreaterThan($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertGreaterThanOrEqual($expected, $actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeGreaterThanOrEqual($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertLessThan($expected, $actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeLessThan($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertLessThanOrEqual($expected, $actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeLessThanOrEqual($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertFileEquals($expected, $actual, $message = '', $canonicalize = FALSE, $ignoreCase = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertFileNotEquals($expected, $actual, $message = '', $canonicalize = FALSE, $ignoreCase = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertStringEqualsFile($expectedFile, $actualString, $message = '', $canonicalize = FALSE, $ignoreCase = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertStringNotEqualsFile($expectedFile, $actualString, $message = '', $canonicalize = FALSE, $ignoreCase = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertFileExists($filename, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertFileNotExists($filename, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertTrue($condition, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertFalse($condition, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertNotNull($actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertNull($actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertClassHasAttribute($attributeName, $className, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertClassNotHasAttribute($attributeName, $className, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertClassHasStaticAttribute($attributeName, $className, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertClassNotHasStaticAttribute($attributeName, $className, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertObjectHasAttribute($attributeName, $object, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertObjectNotHasAttribute($attributeName, $object, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertSame($expected, $actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeSame($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertNotSame($expected, $actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeNotSame($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertInstanceOf($expected, $actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeInstanceOf($expected, $attributeName, $classOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertNotInstanceOf($expected, $actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeNotInstanceOf($expected, $attributeName, $classOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertInternalType($expected, $actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeInternalType($expected, $attributeName, $classOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertNotInternalType($expected, $actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertAttributeNotInternalType($expected, $attributeName, $classOrObject, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertRegExp($pattern, $string, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertNotRegExp($pattern, $string, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertSameSize($expected, $actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertNotSameSize($expected, $actual, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertStringMatchesFormat($format, $string, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertStringNotMatchesFormat($format, $string, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertStringMatchesFormatFile($formatFile, $string, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertStringNotMatchesFormatFile($formatFile, $string, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertStringStartsWith($prefix, $string, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertStringStartsNotWith($prefix, $string, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertStringEndsWith($suffix, $string, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertStringEndsNotWith($suffix, $string, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertXmlFileEqualsXmlFile($expectedFile, $actualFile, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertXmlFileNotEqualsXmlFile($expectedFile, $actualFile, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertXmlStringEqualsXmlFile($expectedFile, $actualXml, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertXmlStringNotEqualsXmlFile($expectedFile, $actualXml, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertXmlStringEqualsXmlString($expectedXml, $actualXml, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertXmlStringNotEqualsXmlString($expectedXml, $actualXml, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertEqualXMLStructure(\DOMElement $expectedElement, \DOMElement $actualElement, $checkAttributes = FALSE, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertSelectCount($selector, $count, $actual, $message = '', $isHtml = TRUE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertSelectRegExp($selector, $pattern, $count, $actual, $message = '', $isHtml = TRUE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertSelectEquals($selector, $content, $count, $actual, $message = '', $isHtml = TRUE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertTag($matcher, $actual, $message = '', $isHtml = TRUE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertNotTag($matcher, $actual, $message = '', $isHtml = TRUE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertThat($value, PHPUnit_Framework_Constraint $constraint, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertJson($expectedJson, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertJsonStringEqualsJsonString($expectedJson, $actualJson, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertJsonStringNotEqualsJsonString($expectedJson, $actualJson, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertJsonStringEqualsJsonFile($expectedFile, $actualJson, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertJsonStringNotEqualsJsonFile($expectedFile, $actualJson, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertJsonFileNotEqualsJsonFile($expectedFile, $actualFile, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function assertJsonFileEqualsJsonFile($expectedFile, $actualFile, $message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function logicalAnd()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function logicalOr()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function logicalNot(PHPUnit_Framework_Constraint $constraint)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function logicalXor()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function anything()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function isTrue()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function callback($callback)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function isFalse()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function isJson()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function isNull()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function attribute(PHPUnit_Framework_Constraint $constraint, $attributeName)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function contains($value, $checkForObjectIdentity = TRUE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function containsOnly($type)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function containsOnlyInstancesOf($classname)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function arrayHasKey($key)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function equalTo($value, $delta = 0, $maxDepth = 10, $canonicalize = FALSE, $ignoreCase = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function attributeEqualTo($attributeName, $value, $delta = 0, $maxDepth = 10, $canonicalize = FALSE, $ignoreCase = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function isEmpty()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function fileExists()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function greaterThan($value)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function greaterThanOrEqual($value)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function classHasAttribute($attributeName)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function classHasStaticAttribute($attributeName)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function objectHasAttribute($attributeName)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function identicalTo($value)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function isInstanceOf($className)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function isType($type)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function lessThan($value)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function lessThanOrEqual($value)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function matchesRegularExpression($pattern)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function matches($string)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function stringStartsWith($prefix)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function stringContains($string, $case = TRUE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function stringEndsWith($suffix)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function fail($message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function readAttribute($classOrObject, $attributeName)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function markTestIncomplete($message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function markTestSkipped($message = '')
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function getCount()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function resetCount()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function any()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function never()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function atLeastOnce()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function once()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function exactly($count)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function at($index)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function returnValue($value)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function returnValueMap(array $valueMap)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function returnArgument($argumentIndex)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function returnCallback($callback)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function returnSelf()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function throwException(\Exception $exception)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public static function onConsecutiveCalls()
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public function getMock($originalClassName, $methods = array(), array $arguments = array(), $mockClassName = '', $callOriginalConstructor = TRUE, $callOriginalClone = TRUE, $callAutoload = TRUE, $cloneArguments = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public function getMockBuilder($className)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    protected function getMockClass($originalClassName, $methods = array(), array $arguments = array(), $mockClassName = '', $callOriginalConstructor = FALSE, $callOriginalClone = TRUE, $callAutoload = TRUE, $cloneArguments = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    public function getMockForAbstractClass($originalClassName, array $arguments = array(), $mockClassName = '', $callOriginalConstructor = TRUE, $callOriginalClone = TRUE, $callAutoload = TRUE, $mockedMethods = array(), $cloneArguments = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    protected function getMockFromWsdl($wsdlFile, $originalClassName = '', $mockClassName = '', array $methods = array(), $callOriginalConstructor = TRUE)
    {
        throw new PHPUnitDeprecatedException();
    }

    /**
     * @deprecated
     */
    protected function getObjectForTrait($traitName, array $arguments = array(), $traitClassName = '', $callOriginalConstructor = TRUE, $callOriginalClone = TRUE, $callAutoload = TRUE, $cloneArguments = FALSE)
    {
        throw new PHPUnitDeprecatedException();
    }
}
