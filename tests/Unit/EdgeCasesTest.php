<?php

declare(strict_types=1);

namespace DmitryRechkin\Tests\PhpStandard\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for edge cases and problematic code patterns
 */
class EdgeCasesTest extends TestCase
{
	/**
	 * Test that nested ternary operators work correctly
	 */
	public function testNestedTernaryOperators(): void
	{
		$codeWithTernary = '<?php

declare(strict_types=1);

class TestClass
{
	public function testMethod(): void
	{
		$result = $condition1 
			? $value1 
			: ($condition2 
				? $value2 
				: $value3
			);
	}
}
';

		$testFile = $this->createTempFile($codeWithTernary);
		$output = $this->runPhpcs($testFile);

		// Should not cause fatal errors or hang
		$returnCode = $this->getLastReturnCode();
		$this->assertNotEquals(255, $returnCode, 'Should not have fatal errors');
		$this->assertStringNotContainsString('Fatal error', $output);

		unlink($testFile);
	}

	/**
	 * Test that complex array operations work
	 */
	public function testComplexArrayOperations(): void
	{
		$complexArrayCode = '<?php

declare(strict_types=1);

class TestClass
{
	public function testMethod(): void
	{
		$result = array_filter(
			array_map(
				function ($item) {
					return $item["value"] ?? null;
				},
				$inputArray
			),
			function ($value) {
				return !empty($value);
			}
		);

		// Complex array access patterns
		$data = $this->getData();
		if (isset($data["nested"]["deep"]["value"])) {
			$extracted = $data["nested"]["deep"]["value"];
		}
	}

	private function getData(): array
	{
		return [];
	}
}
';

		$testFile = $this->createTempFile($complexArrayCode);
		$output = $this->runPhpcs($testFile);

		// Should handle complex array operations without hanging
		$returnCode = $this->getLastReturnCode();
		$this->assertNotEquals(255, $returnCode);
		$this->assertStringNotContainsString('Fatal error', $output);
		$this->assertStringNotContainsString('bracket_closer', $output);

		unlink($testFile);
	}

	/**
	 * Test that anonymous functions work correctly
	 */
	public function testAnonymousFunctions(): void
	{
		$anonymousFunctionCode = '<?php

declare(strict_types=1);

class TestClass
{
	public function testMethod(): void
	{
		$callback = function ($a, $b) {
			return $a + $b;
		};

		$result = array_reduce($array, $callback, 0);

		// Arrow functions (PHP 7.4+)
		$simpleCallback = fn($x) => $x * 2;
	}
}
';

		$testFile = $this->createTempFile($anonymousFunctionCode);
		$output = $this->runPhpcs($testFile);

		// Should handle anonymous functions without issues
		$returnCode = $this->getLastReturnCode();
		$this->assertNotEquals(255, $returnCode);
		$this->assertStringNotContainsString('Fatal error', $output);

		unlink($testFile);
	}

	/**
	 * Test that complex conditional chains work
	 */
	public function testComplexConditionalChains(): void
	{
		$conditionalCode = '<?php

declare(strict_types=1);

class TestClass
{
	public function testMethod(): void
	{
		if ($condition1) {
			$result = "value1";
		} elseif ($condition2 && $condition3) {
			$result = "value2";
		} elseif ($condition4 || $condition5) {
			if ($nestedCondition) {
				$result = "nested_value";
			} else {
				$result = "other_nested";
			}
		} else {
			$result = "default";
		}
	}
}
';

		$testFile = $this->createTempFile($conditionalCode);
		$output = $this->runPhpcs($testFile);

		// Should handle complex conditionals without hanging
		$returnCode = $this->getLastReturnCode();
		$this->assertNotEquals(255, $returnCode);
		$this->assertStringNotContainsString('Fatal error', $output);
		$this->assertStringNotContainsString('"else" without curly braces', $output);

		unlink($testFile);
	}

	/**
	 * Test that large files process successfully
	 */
	public function testLargeFileProcessing(): void
	{
		$largeCode = $this->generateLargePhpFile();

		$testFile = $this->createTempFile($largeCode);
		$output = $this->runPhpcs($testFile);

		// Should handle large files without memory issues
		$returnCode = $this->getLastReturnCode();
		$this->assertNotEquals(255, $returnCode);
		$this->assertStringNotContainsString('Fatal error', $output);
		$this->assertStringNotContainsString('Allowed memory size', $output);

		unlink($testFile);
	}

	/**
	 * Test that PHP 8 features work correctly
	 */
	public function testPhp8Features(): void
	{
		$php8Code = '<?php

declare(strict_types=1);

class TestClass
{
	public function __construct(
		private string $name,
		private int $age = 0,
	) {
	}

	public function testMethod(): void
	{
		// Named arguments
		$result = $this->someMethod(
			name: "test",
			value: 42,
		);

		// Match expression
		$type = match($value) {
			1, 2, 3 => "small",
			4, 5, 6 => "medium",
			default => "large",
		};

		// Nullsafe operator
		$length = $object?->getProperty()?->getLength();
	}

	private function someMethod(string $name, int $value): string
	{
		return $name . (string) $value;
	}
}
';

		$testFile = $this->createTempFile($php8Code);
		$output = $this->runPhpcs($testFile);

		// Should handle PHP 8 features without issues
		$returnCode = $this->getLastReturnCode();
		$this->assertNotEquals(255, $returnCode);
		$this->assertStringNotContainsString('Fatal error', $output);

		unlink($testFile);
	}

	/**
	 * Generate a large PHP file for testing memory handling
	 */
	private function generateLargePhpFile(): string
	{
		$code = "<?php\n\ndeclare(strict_types=1);\n\nclass LargeTestClass\n{\n";

		// Generate 100 methods to create a substantial file
		for ($i = 1; $i <= 100; $i++) {
			$code .= "\t/**\n";
			$code .= "\t * Method number {$i}\n";
			$code .= "\t *\n";
			$code .= "\t * @param string \$param{$i} Parameter {$i}\n";
			$code .= "\t * @return string\n";
			$code .= "\t */\n";
			$code .= "\tpublic function method{$i}(string \$param{$i}): string\n";
			$code .= "\t{\n";
			$code .= "\t\t\$array{$i} = [];\n";
			$code .= "\t\tfor (\$j = 0; \$j < 20; \$j++) {\n";
			$code .= "\t\t\t\$array{$i}[\$j] = \$param{$i} . '_' . \$j;\n";
			$code .= "\t\t}\n";
			$code .= "\t\treturn implode(',', \$array{$i});\n";
			$code .= "\t}\n\n";
		}

		$code .= "}\n";

		return $code;
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
	 * @var int Last return code from phpcs execution
	 */
	private int $lastReturnCode = 0;

	/**
	 * Run PHPCS on a file and return output
	 */
	private function runPhpcs(string $file): string
	{
		$command = sprintf(
			'cd %s && vendor/bin/phpcs %s --standard=php-standard.xml -d memory_limit=512M 2>&1',
			escapeshellarg(__DIR__ . '/../..'),
			escapeshellarg($file)
		);

		exec($command, $output, $this->lastReturnCode);
		return implode("\n", $output);
	}

	/**
	 * Get the last return code
	 */
	private function getLastReturnCode(): int
	{
		return $this->lastReturnCode;
	}
}
