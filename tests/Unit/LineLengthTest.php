<?php

declare(strict_types=1);

namespace DmitryRechkin\Tests\PhpStandard\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for line length limits
 */
class LineLengthTest extends TestCase
{
	/**
	 * Test that lines exceeding 150 characters trigger warnings
	 */
	public function testLineLengthWarning(): void
	{
		$longLine = str_repeat('A', 160);
		$codeWithLongLine = "<?php

declare(strict_types=1);

class TestClass
{
	public function testMethod(): void
	{
		\$variable = '{$longLine}';
	}
}
";

		$testFile = $this->createTempFile($codeWithLongLine);
		$output = $this->runPhpcs($testFile);

		// Should detect line length violation
		$this->assertStringContainsString('Line exceeds 150 characters', $output);

		unlink($testFile);
	}

	/**
	 * Test that lines under 150 characters pass
	 */
	public function testAcceptableLineLength(): void
	{
		$acceptableLine = str_repeat('A', 100); // Use shorter line to ensure under 150
		$codeWithAcceptableLine = "<?php

declare(strict_types=1);

namespace Test;

class TestClass
{
	/**
	 * Test method
	 */
	public function testMethod(): void
	{
		\$variable = '{$acceptableLine}';
	}
}
";

		$testFile = $this->createTempFile($codeWithAcceptableLine);
		$output = $this->runPhpcs($testFile);

		// Should not detect line length violations
		$this->assertStringNotContainsString('Line exceeds 150 characters', $output);

		unlink($testFile);
	}

	/**
	 * Test that extremely long lines (>200 chars) trigger errors
	 */
	public function testAbsoluteLineLengthError(): void
	{
		$veryLongLine = str_repeat('A', 220);
		$codeWithVeryLongLine = "<?php

declare(strict_types=1);

class TestClass
{
	public function testMethod(): void
	{
		\$variable = '{$veryLongLine}';
	}
}
";

		$testFile = $this->createTempFile($codeWithVeryLongLine);
		$output = $this->runPhpcs($testFile);

		// Should detect absolute line length violation
		$this->assertStringContainsString('exceeds maximum limit of 200 characters', $output);

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
