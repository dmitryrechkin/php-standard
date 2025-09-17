<?php

declare(strict_types=1);

namespace DmitryRechkin\Tests\PhpStandard\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for array formatting rules
 */
class ArrayFormattingTest extends TestCase
{
	/**
	 * Test that long array syntax is prohibited
	 */
	public function testLongArraySyntaxProhibited(): void
	{
		$codeWithLongArrays = '<?php

declare(strict_types=1);

class TestClass
{
	public function testMethod(): void
	{
		$oldArray = array("item1", "item2", "item3");
		$newArray = ["item1", "item2", "item3"];
	}
}
';

		$testFile = $this->createTempFile($codeWithLongArrays);
		$output = $this->runPhpcs($testFile);

		// Should detect long array syntax
		$this->assertStringContainsString('Short array syntax must be used', $output);

		unlink($testFile);
	}

	/**
	 * Test that short array syntax is acceptable
	 */
	public function testShortArraySyntaxAcceptable(): void
	{
		$codeWithShortArrays = '<?php

declare(strict_types=1);

class TestClass
{
	public function testMethod(): void
	{
		$array = ["item1", "item2", "item3"];
		$multidimensional = [
			"level1" => [
				"level2" => "value",
			],
		];
	}
}
';

		$testFile = $this->createTempFile($codeWithShortArrays);
		$output = $this->runPhpcs($testFile);

		// Should not detect array syntax violations
		$this->assertStringNotContainsString('DisallowLongArraySyntax', $output);

		unlink($testFile);
	}

	/**
	 * Test that array trailing commas are required in multiline arrays
	 */
	public function testArrayTrailingCommasRequired(): void
	{
		$codeWithoutTrailingComma = '<?php

declare(strict_types=1);

class TestClass
{
	public function testMethod(): void
	{
		$array = [
			"item1",
			"item2",
			"item3"  // Missing trailing comma
		];
	}
}
';

		$testFile = $this->createTempFile($codeWithoutTrailingComma);
		$output = $this->runPhpcs($testFile);

		// Should detect missing trailing comma
		$this->assertStringContainsString('Comma required after last value', $output);

		unlink($testFile);
	}

	/**
	 * Test that proper array formatting passes
	 */
	public function testProperArrayFormattingPasses(): void
	{
		$properArrayCode = '<?php

declare(strict_types=1);

class TestClass
{
	public function testMethod(): void
	{
		$singleLine = ["item1", "item2", "item3"];
		$multiLine = [
			"key1" => "value1",
			"key2" => "value2",
			"key3" => "value3",
		];
	}
}
';

		$testFile = $this->createTempFile($properArrayCode);
		$output = $this->runPhpcs($testFile);

		// Should not contain array formatting errors
		$this->assertStringNotContainsString('Comma required after last value', $output);
		$this->assertStringNotContainsString('DisallowLongArraySyntax', $output);

		unlink($testFile);
	}

	/**
	 * Create temporary file with given content
	 */
	private function createTempFile(string $content): string
	{
		$tempFile = tempnam(sys_get_temp_dir(), 'phpcs_test_') . '.php';
		file_put_contents($tempFile, $content);
		return $tempFile;
	}

	/**
	 * Run PHPCS on a file and return output
	 */
	private function runPhpcs(string $file): string
	{
		$command = sprintf(
			'cd %s && vendor/bin/phpcs %s --standard=php-standard.xml 2>&1',
			escapeshellarg(__DIR__ . '/../..'),
			escapeshellarg($file)
		);

		exec($command, $output, $returnCode);
		return implode("\n", $output);
	}
}
