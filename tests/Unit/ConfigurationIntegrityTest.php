<?php

declare(strict_types=1);

namespace DmitryRechkin\Tests\PhpStandard\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for php-standard.xml configuration integrity
 */
class ConfigurationIntegrityTest extends TestCase
{
	/**
	 * Test that our configuration file is valid XML
	 */
	public function testConfigurationIsValidXml(): void
	{
		$configFile = __DIR__ . '/../../php-standard.xml';
		$this->assertFileExists($configFile, 'Configuration file should exist');

		$xml = simplexml_load_file($configFile);
		$this->assertNotFalse($xml, 'Configuration should be valid XML');

		// Check that it has the expected structure
		$this->assertEquals('StablePHPStandard', (string) $xml['name']);
	}

	/**
	 * Test that no deprecated sniffs are used
	 */
	public function testNoDeprecatedSniffsUsed(): void
	{
		$configFile = __DIR__ . '/../../php-standard.xml';
		$content = file_get_contents($configFile);

		// Check that we're using Generic.WhiteSpace.LanguageConstructSpacing, not Squiz
		$this->assertStringNotContainsString(
			'Squiz.WhiteSpace.LanguageConstructSpacing',
			$content,
			'Should use Generic.WhiteSpace.LanguageConstructSpacing instead of deprecated Squiz version'
		);

		$this->assertStringContainsString(
			'Generic.WhiteSpace.LanguageConstructSpacing',
			$content,
			'Should use the non-deprecated Generic.WhiteSpace.LanguageConstructSpacing'
		);
	}

	/**
	 * Test that tab indentation is properly configured
	 */
	public function testTabIndentationConfiguration(): void
	{
		$configFile = __DIR__ . '/../../php-standard.xml';
		$content = file_get_contents($configFile);

		// Should disallow space indentation
		$this->assertStringContainsString(
			'Generic.WhiteSpace.DisallowSpaceIndent',
			$content,
			'Should disallow space indentation'
		);

		// Should exclude tab indentation restriction from PSR12
		$this->assertStringContainsString(
			'Generic.WhiteSpace.DisallowTabIndent',
			$content,
			'Should exclude tab indentation restriction'
		);

		// Should configure scope indent for tabs
		$this->assertStringContainsString(
			'tabIndent',
			$content,
			'Should configure tab indentation'
		);
	}

	/**
	 * Test that array rules are properly configured
	 */
	public function testArrayRulesConfiguration(): void
	{
		$configFile = __DIR__ . '/../../php-standard.xml';
		$content = file_get_contents($configFile);

		// Should disallow long array syntax
		$this->assertStringContainsString(
			'Generic.Arrays.DisallowLongArraySyntax',
			$content,
			'Should disallow array() syntax'
		);

		// Should include array formatting rules
		$this->assertStringContainsString(
			'Squiz.Arrays.ArrayDeclaration',
			$content,
			'Should include array declaration rules'
		);
	}

	/**
	 * Test that line length limits are configured
	 */
	public function testLineLengthConfiguration(): void
	{
		$configFile = __DIR__ . '/../../php-standard.xml';
		$content = file_get_contents($configFile);

		// Should configure line length limits
		$this->assertStringContainsString(
			'Generic.Files.LineLength',
			$content,
			'Should include line length rules'
		);

		$this->assertStringContainsString(
			'lineLimit',
			$content,
			'Should configure line length limit'
		);

		$this->assertStringContainsString(
			'150',
			$content,
			'Should set 150 character limit'
		);
	}

	/**
	 * Test that file formatting rules are included
	 */
	public function testFileFormattingRules(): void
	{
		$configFile = __DIR__ . '/../../php-standard.xml';
		$content = file_get_contents($configFile);

		// Should require newline at end of file
		$this->assertStringContainsString(
			'Generic.Files.EndFileNewline',
			$content,
			'Should require newline at end of file'
		);

		// Should enforce Unix line endings
		$this->assertStringContainsString(
			'Generic.Files.LineEndings',
			$content,
			'Should enforce line endings'
		);
	}

	/**
	 * Test that security rules are included but practical
	 */
	public function testSecurityRulesConfiguration(): void
	{
		$configFile = __DIR__ . '/../../php-standard.xml';
		$content = file_get_contents($configFile);

		// Should include eval protection
		$this->assertStringContainsString(
			'Squiz.PHP.Eval',
			$content,
			'Should protect against eval usage'
		);

		// Should include global keyword detection
		$this->assertStringContainsString(
			'Squiz.PHP.GlobalKeyword',
			$content,
			'Should detect global keyword usage'
		);
	}

	/**
	 * Test that PHPCS can load our configuration without errors
	 */
	public function testConfigurationLoadsSuccessfully(): void
	{
		$configFile = __DIR__ . '/../../php-standard.xml';

		// Try to run phpcs with just the configuration to see if it loads
		$command = sprintf(
			'cd %s && vendor/bin/phpcs --config-show --standard=%s 2>&1',
			escapeshellarg(__DIR__ . '/../..'),
			escapeshellarg($configFile)
		);

		exec($command, $output, $returnCode);
		$outputString = implode("\n", $output);

		// Should not have fatal errors when loading configuration
		$this->assertStringNotContainsString('Fatal error', $outputString);
		$this->assertStringNotContainsString('Parse error', $outputString);
		$this->assertNotEquals(255, $returnCode, 'PHPCS should load configuration successfully');
	}

	/**
	 * Test that configuration works with a simple valid file
	 */
	public function testConfigurationWorksWithValidCode(): void
	{
		$validCode = '<?php

declare(strict_types=1);

namespace Test;

/**
 * Test class for configuration validation
 */
class ValidTestClass
{
	/**
	 * Test method with proper formatting
	 *
	 * @param string $input Input parameter
	 * @return string
	 */
	public function testMethod(string $input): string
	{
		$result = [
			"key1" => "value1",
			"key2" => "value2",
		];

		return $input . json_encode($result);
	}
}
';

		$testFile = tempnam(sys_get_temp_dir(), 'phpcs_config_test_') . '.php';
		file_put_contents($testFile, $validCode);

		$command = sprintf(
			'cd %s && vendor/bin/phpcs %s --standard=php-standard.xml --exclude=Squiz.Commenting.FunctionComment 2>&1',
			escapeshellarg(__DIR__ . '/../..'),
			escapeshellarg($testFile)
		);

		exec($command, $output, $returnCode);
		$outputString = implode("\n", $output);

		// Should not have any errors on well-formatted code
		$this->assertStringNotContainsString('Fatal error', $outputString);
		$this->assertStringNotContainsString('WARNING', $outputString);
		$this->assertNotEquals(255, $returnCode);

		unlink($testFile);
	}

	/**
	 * Test that deprecated sniff warning is gone
	 */
	public function testNoDeprecatedSniffWarnings(): void
	{
		$testCode = '<?php

declare(strict_types=1);

class TestClass
{
	public function testMethod(): void
	{
		echo "test";
	}
}
';

		$testFile = tempnam(sys_get_temp_dir(), 'phpcs_deprecated_test_') . '.php';
		file_put_contents($testFile, $testCode);

		$command = sprintf(
			'cd %s && vendor/bin/phpcs %s --standard=php-standard.xml 2>&1',
			escapeshellarg(__DIR__ . '/../..'),
			escapeshellarg($testFile)
		);

		exec($command, $output, $returnCode);
		$outputString = implode("\n", $output);

		// Should not contain deprecated sniff warnings
		$this->assertStringNotContainsString(
			'deprecated sniff',
			$outputString,
			'Should not have deprecated sniff warnings'
		);

		$this->assertStringNotContainsString(
			'Squiz.WhiteSpace.LanguageConstructSpacing',
			$outputString,
			'Should not reference deprecated Squiz.WhiteSpace.LanguageConstructSpacing'
		);

		unlink($testFile);
	}
}
