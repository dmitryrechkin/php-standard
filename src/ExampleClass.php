<?php

declare(strict_types=1);

namespace DmitryRechkin\PhpStandard;

class ExampleClass
{
    private string $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function setProperty(string $property): void
    {
        $this->property = $property;
    }

    public function exampleMethod(int $param): string
    {
        $unusedVar = 'test'; // This should trigger unused variable warning
        return $this->property . ' - ' . $param;
    }
}