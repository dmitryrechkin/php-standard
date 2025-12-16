<?php

declare(strict_types=1);

namespace DmitryRechkin\PhpStandard\Tests;

class ExampleTest
{
    public function testBasicFunctionality(): void
    {
        $unusedVariable = 'test';
        $result = $this->addNumbers(1, 2);
        $this->assertEquals(3, $result);
    }

    private function addNumbers(int $a, int $b): int
    {
        return $a + $b;
    }

    public function assertEquals($expected, $actual): void
    {
        if ($expected !== $actual) {
            throw new \Exception("Expected {$expected}, got {$actual}");
        }
    }
}