<?php

declare(strict_types=1);

namespace DmitryRechkin\Tests\PhpStandard;

use PHPUnit\Framework\TestCase;

/**
 * Tests for PHP Coding Standard validation
 */
class StandardValidationTest extends TestCase
{
	/**
	 * Test that PHPCS configuration loads without errors
	 *
	 * @return void
	 */
	public function testPhpcsConfigurationLoads(): void
	{
		$configFile = __DIR__ . '/../php-standard.xml';
		$this->assertFileExists($configFile, 'PHP Standard configuration file should exist');

		// Test that the XML is valid
		$xml = simplexml_load_file($configFile);
		$this->assertNotFalse($xml, 'PHP Standard configuration should be valid XML');
	}

	/**
	 * Test that all required PHP_CodeSniffer components are installed
	 */
	public function testRequiredComponentsAreInstalled(): void
	{
		// Test that PHP_CodeSniffer is available
		$phpcsPath = __DIR__ . '/../vendor/bin/phpcs';
		$this->assertFileExists($phpcsPath, 'PHPCS executable should be installed');

		// Test that PHPCBF is available
		$phpcbfPath = __DIR__ . '/../vendor/bin/phpcbf';
		$this->assertFileExists($phpcbfPath, 'PHPCBF executable should be installed');
	}

	/**
	 * Test that Slevomat Coding Standard is properly installed
	 */
	public function testSlevomatCodingStandardInstalled(): void
	{
		$slevomatPath = __DIR__ . '/../vendor/slevomat/coding-standard';
		$this->assertDirectoryExists($slevomatPath, 'Slevomat Coding Standard should be installed');

		// Check that the main ruleset file exists
		$rulesetFile = $slevomatPath . '/SlevomatCodingStandard/ruleset.xml';
		$this->assertFileExists($rulesetFile, 'Slevomat ruleset file should exist');
	}

	/**
	 * Test that PHPCS can run without fatal errors on a simple valid file
	 */
	public function testPhpcsRunsWithoutFatalErrors(): void
	{
		// Create a test file that should pass standards
		$testFile = __DIR__ . '/fixtures/ValidCode.php';
		$this->createTestFile($testFile, $this->getValidPhpCode());

		// Run PHPCS on the test file
		$output = [];
		$returnCode = 0;
		$command = sprintf(
			'cd %s && vendor/bin/phpcs %s --standard=php-standard.xml 2>&1',
			escapeshellarg(__DIR__ . '/..'),
			escapeshellarg($testFile)
		);

		exec($command, $output, $returnCode);

		// Clean up
		unlink($testFile);

		// Check that no fatal errors occurred (return code 2 indicates only style violations)
		$this->assertNotEquals(255, $returnCode, 'PHPCS should not have fatal errors. Output: ' . implode("\n", $output));
		$this->assertStringNotContainsString('Fatal error', implode("\n", $output), 'PHPCS should not throw fatal errors');
	}

	/**
	 * Test that PHPCS can handle typeless @param tags without fatal errors
	 */
	public function testTypelessParamTagsHandling(): void
	{
		// Create a test file with typeless @param tags (the previous issue)
		$testFile = __DIR__ . '/fixtures/TypelessParam.php';
		$this->createTestFile($testFile, $this->getTypelessParamCode());

		// Run PHPCS on the test file
		$output = [];
		$returnCode = 0;
		$command = sprintf(
			'cd %s && vendor/bin/phpcs %s --standard=php-standard.xml 2>&1',
			escapeshellarg(__DIR__ . '/..'),
			escapeshellarg($testFile)
		);

		exec($command, $output, $returnCode);

		// Clean up
		unlink($testFile);

		// The key test: should not have fatal errors related to TypelessParamTagValueNode
		$outputText = implode("\n", $output);
		$this->assertStringNotContainsString('Fatal error', $outputText, 'PHPCS should not throw fatal errors on typeless @param tags');
		$this->assertStringNotContainsString('TypelessParamTagValueNode', $outputText, 'Should not have TypelessParamTagValueNode errors');
	}

	/**
	 * Test that phplint works properly
	 */
	public function testPhplintWorks(): void
	{
		$testFile = __DIR__ . '/fixtures/ValidSyntax.php';
		$this->createTestFile($testFile, $this->getValidPhpCode());

		$output = [];
		$returnCode = 0;
		$command = sprintf(
			'cd %s && vendor/bin/phplint %s --no-cache 2>&1',
			escapeshellarg(__DIR__ . '/..'),
			escapeshellarg($testFile)
		);

		exec($command, $output, $returnCode);

		// Clean up
		unlink($testFile);

		$this->assertEquals(0, $returnCode, 'Phplint should pass on valid PHP code. Output: ' . implode("\n", $output));
	}

	/**
	 * Clean up any test fixtures after tests complete
	 */
	protected function tearDown(): void
	{
		$fixturesDir = __DIR__ . '/fixtures';
		if (is_dir($fixturesDir)) {
			array_map('unlink', glob($fixturesDir . '/*'));
			rmdir($fixturesDir);
		}

		parent::tearDown();
	}

	/**
	 * Creates test fixtures directory and file
	 */
	private function createTestFile(string $path, string $content): void
	{
		$dir = dirname($path);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		file_put_contents($path, $content);
	}

	/**
	 * Returns valid PHP code that should pass standards
	 */
	private function getValidPhpCode(): string
	{
		return '<?php

declare(strict_types=1);

namespace Test;

/**
 * Valid test class
 *
 * @category Test
 * @package  Test
 * @author   Test Author <test@example.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://example.com
 */
class ValidClass
{
    /**
     * Valid method with proper documentation
     *
     * @param string $parameter The parameter
     * @return string
     */
    public function validMethod(string $parameter): string
    {
        return $parameter;
    }
}
';
	}

	/**
	 * Returns PHP code with typeless @param tags (the issue we fixed)
	 */
	private function getTypelessParamCode(): string
	{
		return '<?php

declare(strict_types=1);

namespace Test;

/**
 * Test class with typeless param
 *
 * @category Test
 * @package  Test
 * @author   Test Author <test@example.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://example.com
 */
class TypelessParamClass
{
    /**
     * Method with typeless @param tag
     *
     * @param $parameter The parameter without type
     * @return string
     */
    public function methodWithTypelessParam($parameter): string
    {
        return (string) $parameter;
    }
}
';
	}
}
