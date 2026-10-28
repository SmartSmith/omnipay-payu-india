<?php

declare(strict_types=1);

namespace Omnipay\PayUIndia\Tests\Endpoints;

use Omnipay\Common\Message\RequestInterface;
use Omnipay\PayUIndia\Gateway;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * The set calls every operation a consuming app reaches: each public method `Gateway` itself declares
 * that returns a request. Each is named by exactly one test's #[Calls], so removing that test turns
 * this red, and every #[Calls] names a real operation.
 *
 * Not operations: the gateway's name, default parameters and the key and salt accessors, which every
 * test's gateway sets through `initialize` as SmartPay does.
 */
final class EveryEntryPointIsCalledTest extends EndpointTestCase
{
    #[Test]
    public function every_operation_is_called_once_and_every_call_names_one(): void
    {
        $entries = self::operations();
        $called = self::called();

        $this->assertNotSame([], $entries, 'Gateway declares no operation.');
        $this->assertSame([], array_values(array_diff($entries, array_keys($called))), 'These operations have no end-to-end test.');
        $this->assertSame([], array_values(array_diff(array_keys($called), $entries)), 'These #[Calls] name no operation.');
        foreach ($called as $entry => $tests) {
            $this->assertCount(1, $tests, $entry.' is named by '.implode(', ', $tests).'.');
        }
    }

    /** @return list<string> */
    private static function operations(): array
    {
        $entries = [];
        foreach ((new ReflectionClass(Gateway::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $type = $method->getReturnType();
            if ($method->getDeclaringClass()->getName() === Gateway::class
                && $type instanceof ReflectionNamedType
                && is_subclass_of($type->getName(), RequestInterface::class)) {
                $entries[] = 'Gateway::'.$method->getName();
            }
        }

        return $entries;
    }

    /** @return array<string, list<string>> each named operation, with the tests that name it */
    private static function called(): array
    {
        $called = [];
        foreach (glob(__DIR__.'/*Test.php') as $file) {
            $class = __NAMESPACE__.'\\'.basename($file, '.php');
            foreach ((new ReflectionClass($class))->getMethods() as $method) {
                foreach ($method->getAttributes(Calls::class) as $attribute) {
                    $called[$attribute->newInstance()->entry][] = basename($file, '.php').'::'.$method->getName();
                }
            }
        }

        return $called;
    }
}
