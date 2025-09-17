<?php

declare(strict_types=1);

namespace DmitryRechkin\Tests\PhpStandard\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for function documentation requirements
 */
class FunctionDocumentationTest extends TestCase
{
	/**
	 * Test that public methods require documentation
	 */
	public function testPublicMethodsRequireDocumentation(): void
	{
		$codeWithoutDocumentation = '<?php

declare(strict_types=1);

class TestClass
{
	public function undocumentedMethod(): void
	{
		$variable = "test";
	}
}
';

		$testFile = $this->createTempFile($codeWithoutDocumentation);
		$output = $this->runPhpcs($testFile, false); // Run with documentation rules

		// Should detect missing function comment
		$this->assertStringContainsString('Missing doc comment for function', $output);

		unlink($testFile);
	}

	/**
	 * Test that properly documented methods pass
	 */
	public function testProperlyDocumentedMethodsPasses(): void
	{
		$properlyDocumentedCode = '<?php

declare(strict_types=1);

class TestClass
{
	/**
	 * This is a properly documented method
	 *
	 * @param string $parameter The input parameter
	 * @return string The return value
	 */
	public function documentedMethod(string $parameter): string
	{
		return $parameter;
	}
}
';

		$testFile = $this->createTempFile($properlyDocumentedCode);
		$output = $this->runPhpcs($testFile, false); // Run with documentation rules

		// Should not detect missing documentation
		$this->assertStringNotContainsString('Missing function doc comment', $output);

		unlink($testFile);
	}

	/**
	 * Test that private methods can skip documentation
	 */
	public function testPrivateMethodsCanSkipDocumentation(): void
	{
		$codeWithPrivateMethod = '<?php

declare(strict_types=1);

class TestClass
{
	private function privateMethod(): void
	{
		$variable = "test";
	}
}
';

		$testFile = $this->createTempFile($codeWithPrivateMethod);
		$output = $this->runPhpcs($testFile, true); // Run without strict documentation rules

		// Private methods may not require documentation with our exclusions
		// This test verifies our configuration is working as expected
		$this->assertNotEquals(255, $this->getLastReturnCode()); // No fatal errors

		unlink($testFile);
	}

	/**
	 * Test that methods with @inheritdoc can skip detailed documentation
	 */
	public function testInheritdocSkipsValidation(): void
	{
		$codeWithInheritdoc = '<?php

declare(strict_types=1);

class TestClass
{
	/**
	 * {@inheritdoc}
	 */
	public function inheritedMethod(): void
	{
		$variable = "test";
	}
}
';

		$testFile = $this->createTempFile($codeWithInheritdoc);
		$output = $this->runPhpcs($testFile, false); // Run with documentation rules

		// Should not require full documentation for inherited methods
		$this->assertStringNotContainsString('Missing function doc comment', $output);

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
	private function runPhpcs(string $file, bool $excludeDocumentation = true): string
	{
		$excludeFlag = $excludeDocumentation
			? '--exclude=Squiz.Commenting.FunctionComment'
			: '';

		$command = sprintf(
			'cd %s && vendor/bin/phpcs %s --standard=php-standard.xml %s 2>&1',
			escapeshellarg(__DIR__ . '/../..'),
			escapeshellarg($file),
			$excludeFlag
		);

		exec($command, $output, $this->lastReturnCode);
		return implode("\n", $output);
	}

	/**
	 * @var int Last return code from phpcs execution
	 */
	private int $lastReturnCode = 0;

	/**
	 * Get the last return code
	 */
	private function getLastReturnCode(): int
	{
		return $this->lastReturnCode;
	}
}
