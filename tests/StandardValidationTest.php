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
	 * Test that PHPCS handles complex conditionals without fatal errors
	 */
	public function testComplexConditionalsHandling(): void
	{
		$testFile = __DIR__ . '/fixtures/ComplexConditionals.php';
		$this->createTestFile($testFile, $this->getComplexConditionalsCode());

		$output = [];
		$returnCode = 0;
		$command = sprintf(
			'cd %s && vendor/bin/phpcs %s --standard=php-standard.xml -d memory_limit=512M 2>&1',
			escapeshellarg(__DIR__ . '/..'),
			escapeshellarg($testFile)
		);

		exec($command, $output, $returnCode);

		// Clean up
		unlink($testFile);

		$outputText = implode("\n", $output);
		$this->assertStringNotContainsString('Fatal error', $outputText, 'PHPCS should handle complex conditionals without fatal errors');
		$this->assertStringNotContainsString('"else" without curly braces is not supported', $outputText, 'Should handle else statements properly');
	}

	/**
	 * Test that PHPCS handles arrays and property access without fatal errors
	 */
	public function testArraysAndPropertyAccessHandling(): void
	{
		$testFile = __DIR__ . '/fixtures/ArraysAndProperties.php';
		$this->createTestFile($testFile, $this->getArraysAndPropertiesCode());

		$output = [];
		$returnCode = 0;
		$command = sprintf(
			'cd %s && vendor/bin/phpcs %s --standard=php-standard.xml -d memory_limit=512M 2>&1',
			escapeshellarg(__DIR__ . '/..'),
			escapeshellarg($testFile)
		);

		exec($command, $output, $returnCode);

		// Clean up
		unlink($testFile);

		$outputText = implode("\n", $output);
		$this->assertStringNotContainsString('Fatal error', $outputText, 'PHPCS should handle arrays and properties without fatal errors');
		$this->assertStringNotContainsString('Undefined index: bracket_closer', $outputText, 'Should handle property access properly');
		$this->assertStringNotContainsString('ReferencedName::__construct() must be of the type int, null given', $outputText, 'Should handle references properly');
	}

	/**
	 * Test that PHPCS handles WordPress-style code patterns
	 */
	public function testWordPressStylePatternsHandling(): void
	{
		$testFile = __DIR__ . '/fixtures/WordPressPatterns.php';
		$this->createTestFile($testFile, $this->getWordPressStyleCode());

		$output = [];
		$returnCode = 0;
		$command = sprintf(
			'cd %s && vendor/bin/phpcs %s --standard=php-standard.xml -d memory_limit=512M 2>&1',
			escapeshellarg(__DIR__ . '/..'),
			escapeshellarg($testFile)
		);

		exec($command, $output, $returnCode);

		// Clean up
		unlink($testFile);

		$outputText = implode("\n", $output);
		$this->assertStringNotContainsString('Fatal error', $outputText, 'PHPCS should handle WordPress patterns without fatal errors');
		$this->assertNotEquals(255, $returnCode, 'Should not have fatal exit code');
	}

	/**
	 * Test that PHPCS can handle large files without memory exhaustion
	 */
	public function testLargeFileHandling(): void
	{
		$testFile = __DIR__ . '/fixtures/LargeFile.php';
		$this->createTestFile($testFile, $this->getLargeFileCode());

		$output = [];
		$returnCode = 0;
		$command = sprintf(
			'cd %s && vendor/bin/phpcs %s --standard=php-standard.xml -d memory_limit=512M 2>&1',
			escapeshellarg(__DIR__ . '/..'),
			escapeshellarg($testFile)
		);

		exec($command, $output, $returnCode);

		// Clean up
		unlink($testFile);

		$outputText = implode("\n", $output);
		$this->assertStringNotContainsString('Allowed memory size', $outputText, 'PHPCS should not run out of memory on large files');
		$this->assertStringNotContainsString('Fatal error', $outputText, 'Should not have fatal errors on large files');
	}

	/**
	 * Test that PHPCBF can fix files without fatal errors
	 */
	public function testPhpcbfFixesWithoutErrors(): void
	{
		$testFile = __DIR__ . '/fixtures/FixableCode.php';
		$this->createTestFile($testFile, $this->getFixableCode());

		$output = [];
		$returnCode = 0;
		$command = sprintf(
			'cd %s && vendor/bin/phpcbf %s --standard=php-standard.xml -d memory_limit=512M 2>&1',
			escapeshellarg(__DIR__ . '/..'),
			escapeshellarg($testFile)
		);

		exec($command, $output, $returnCode);

		// Clean up
		unlink($testFile);

		$outputText = implode("\n", $output);
		$this->assertStringNotContainsString('Fatal error', $outputText, 'PHPCBF should fix code without fatal errors');
		$this->assertNotEquals(255, $returnCode, 'Should not have fatal exit code');
	}

	/**
	 * Test individual Slevomat rules to identify which ones cause fatal errors
	 */
	public function testIndividualSlevomatRules(): void
	{
		$testFile = __DIR__ . '/fixtures/SlevomatTest.php';
		$this->createTestFile($testFile, $this->getSlevomatTestCode());

		// Test problematic rules that commonly cause TypelessParamTagValueNode errors
		$problematicRules = [
			'SlevomatCodingStandard.Commenting.DocCommentSpacing',
			'SlevomatCodingStandard.Functions.StrictCall',
			'SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint',
			'SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint',
			'SlevomatCodingStandard.Commenting.EmptyComment',
		];

		foreach ($problematicRules as $rule) {
			$output = [];
			$returnCode = 0;
			$command = sprintf(
				'cd %s && vendor/bin/phpcs %s --standard=%s -d memory_limit=512M 2>&1',
				escapeshellarg(__DIR__ . '/..'),
				escapeshellarg($testFile),
				escapeshellarg($rule)
			);

			exec($command, $output, $returnCode);

			$outputText = implode("\n", $output);
			$this->assertStringNotContainsString(
				'Fatal error',
				$outputText,
				'Rule ' . $rule . ' should not cause fatal errors. Output: ' . $outputText
			);
			$this->assertStringNotContainsString(
				'TypelessParamTagValueNode',
				$outputText,
				'Rule ' . $rule . ' should not have TypelessParamTagValueNode errors'
			);
		}

		// Clean up
		unlink($testFile);
	}

	/**
	 * Test SlevomatCodingStandard bug fix for ReferencedName TypeError
	 *
	 * @return void
	 */
	public function testSlevomatBugFix(): void
	{
		$testCode = $this->getSlevomatBugTriggeringCode();
		
		// Create a temporary file with the test code
		$tempFile = tempnam(sys_get_temp_dir(), 'slevomat_test_') . '.php';
		file_put_contents($tempFile, $testCode);
		
		// Run PHPCS with our standard and check for the specific error
		$command = sprintf(
			'cd %s && vendor/bin/phpcs %s --standard=php-standard.xml -d memory_limit=512M 2>&1',
			escapeshellarg(__DIR__ . '/..'),
			escapeshellarg($tempFile)
		);
		
		exec($command, $output, $returnCode);
		$outputText = implode("\n", $output);
		
		// The bug should be fixed, so there should be no Fatal error
		$this->assertStringNotContainsString(
			'Fatal error',
			$outputText,
			'SlevomatCodingStandard bug should be fixed. Output: ' . $outputText
		);
		
		$this->assertStringNotContainsString(
			'TypeError: Argument 3 passed to SlevomatCodingStandard',
			$outputText,
			'SlevomatCodingStandard ReferencedName bug should be fixed'
		);
		
		$this->assertStringNotContainsString(
			'TypeError: Return value of SlevomatCodingStandard\Helpers\ScopeHelper::getRootPointer() must be of the type int, null returned',
			$outputText,
			'SlevomatCodingStandard ScopeHelper bug should be fixed'
		);
		
		$this->assertStringNotContainsString(
			'Undefined index: bracket_closer',
			$outputText,
			'SlevomatCodingStandard PropertyHelper bracket_closer bug should be fixed'
		);
		
		// Clean up
		unlink($tempFile);
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

	/**
	 * Returns PHP code with complex conditional structures
	 */
	private function getComplexConditionalsCode(): string
	{
		return '<?php

declare(strict_types=1);

namespace Test;

/**
 * Complex conditionals test class
 */
class ComplexConditionalsClass
{
	/**
	 * Test method with complex conditionals
	 *
	 * @param string $input Input parameter
	 * @return string
	 */
	public function complexConditionals(string $input): string
	{
		if ($input === "test") {
			$result = "test_value";
		} else {
			$result = "other_value";
		}

		// Nested conditions
		if ($input !== "") {
			if ($input === "special")
				return "special_case";
			else
				return $result;
		}

		// Multiple conditions
		$condition1 = true;
		$condition2 = false;
		if ($condition1 && $condition2) {
			return "both_true";
		} elseif ($condition1 || $condition2) {
			return "one_true";
		} else {
			return "none_true";
		}
	}
}
';
	}

	/**
	 * Returns PHP code with arrays and property access patterns
	 */
	private function getArraysAndPropertiesCode(): string
	{
		return '<?php

declare(strict_types=1);

namespace Test;

/**
 * Arrays and properties test class
 */
class ArraysAndPropertiesClass
{
	/**
	 * @var array<string, mixed>
	 */
	private array $properties = [];

	/**
	 * Test method with array operations
	 *
	 * @param array<string, mixed> $data Input data
	 * @return array<string, mixed>
	 */
	public function processArrays(array $data): array
	{
		$result = [];
		
		foreach ($data as $key => $value) {
			if (isset($value["nested_key"])) {
				$result[$key] = $value["nested_key"];
			}
		}

		// Property access patterns that could cause issues
		$this->properties["dynamic_key"] = "value";
		
		// Complex array operations
		$complexArray = [
			"level1" => [
				"level2" => [
					"level3" => "deep_value"
				]
			]
		];

		return array_merge($result, $complexArray);
	}
}
';
	}

	/**
	 * Returns WordPress-style code patterns
	 */
	private function getWordPressStyleCode(): string
	{
		return '<?php

declare(strict_types=1);

namespace Test;

/**
 * WordPress patterns test class
 */
class WordPressPatternsClass
{
	/**
	 * WordPress-style method with filters and actions
	 *
	 * @param string $content Content to process
	 * @return string
	 */
	public function wordPressPatterns(string $content): string
	{
		// Simulate WordPress functions
		$filtered = $this->applyFilters("test_filter", $content);
		
		// Conditional with WordPress-style checks
		if (false === empty($filtered)) {
			$result = $filtered;
		} else {
			$result = "";
		}

		// Array operations common in WordPress
		$options = [
			"option1" => true,
			"option2" => false,
			"nested" => [
				"deep_option" => "value"
			]
		];

		$processedOptions = [];
		foreach ($options as $key => $value) {
			$processedOptions[$key] = $this->sanitizeOption($value);
		}

		return $result;
	}

	/**
	 * Simulate WordPress apply_filters
	 *
	 * @param string $filter Filter name
	 * @param mixed $value Value to filter
	 * @return mixed
	 */
	private function applyFilters(string $filter, $value)
	{
		return $value;
	}

	/**
	 * Simulate option sanitization
	 *
	 * @param mixed $value Value to sanitize
	 * @return mixed
	 */
	private function sanitizeOption($value)
	{
		return $value;
	}
}
';
	}

	/**
	 * Returns a large PHP file to test memory usage
	 */
	private function getLargeFileCode(): string
	{
		$code = '<?php

declare(strict_types=1);

namespace Test;

/**
 * Large file test class
 */
class LargeFileClass
{
';

		// Generate many methods to create a large file
		for ($i = 1; $i <= 50; $i++) {
			$code .= "\n\t/**\n\t * Generated method " . $i . "\n\t *\n\t * @param string \$param" . $i
				. ' Parameter ' . $i . "\n\t * @return string\n\t */\n\tpublic function generatedMethod" . $i
				. '(string $param' . $i . "): string\n\t{\n\t\t\$result = \"Method " . $i . " result\";\n\t\t\$array"
				. $i . " = [];\n\t\t\n\t\tfor (\$j = 1; \$j <= 10; \$j++) {\n\t\t\t\$array" . $i
				. '[$j] = $param' . $i . " . \$j;\n\t\t}\n\t\t\n\t\treturn \$result . implode(\",\", \$array"
				. $i . ");\n\t}\n";
		}

		$code .= '}
';
		return $code;
	}

	/**
	 * Returns fixable PHP code with common violations
	 */
	private function getFixableCode(): string
	{
		return '<?php
declare(strict_types=1);
namespace Test;
class FixableClass{
private string $property;
public function __construct(string $property){
$this->property=$property;
}
public function getProperty():string{
return $this->property;
}
}';
	}

	/**
	 * Returns PHP code specifically designed to test Slevomat rules
	 */
	private function getSlevomatTestCode(): string
	{
		return '<?php

declare(strict_types=1);

namespace Test;

/**
 * Test class for Slevomat rules
 *
 * @category Test
 * @package  Test
 * @author   Test Author <test@example.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://example.com
 */
class SlevomatTestClass
{
	/**
	 * Test method with typeless @param tag (the core issue)
	 *
	 * @param $parameter The parameter without type
	 * @param string $typedParameter The typed parameter
	 * @return string
	 */
	public function testMethod($parameter, string $typedParameter): string
	{
		$result = (string) $parameter;
		
		// Test strict call
		if ($result === "test") {
			return $typedParameter;
		}
		
		return $result;
	}

	/**
	 * Another method to test documentation spacing
	 *
	 * @param mixed $value Value to process
	 * @return mixed
	 */
	public function anotherMethod($value)
	{
		return $value;
	}
}';
	}

	/**
	 * Returns code that triggers the SlevomatCodingStandard bug
	 */
	private function getSlevomatBugTriggeringCode(): string
	{
		return '<?php

declare(strict_types=1);

namespace OneTeamSoftware\\WC\\MultiVendorBridge;

use OneTeamSoftware\\WC\\MultiVendorBridge\\Adapter\\AbstractAdapter;

/**
 * MultiVendor Bridge class that triggers the SlevomatCodingStandard bug
 * due to dynamic includes and specific code patterns
 */
class MultiVendorBridge
{
	/**
	 * @var string[] list of adapter class names
	 */
	private static array $adapterNames = [
		"Wcfm",
		"Dokan", 
		"Yith",
		"Mvx",
		"Wcpv",
		"PostAuthor",
	];

	/**
	 * returns matching adapter
	 *
	 * @return AbstractAdapter
	 */
	public static function createInstance(): ?AbstractAdapter
	{
		foreach (self::$adapterNames as $adapterName) {
			$adapterFilePath = __DIR__ . "/Adapter/" . $adapterName . ".php";
			if (file_exists($adapterFilePath)) {
				include_once($adapterFilePath);

				$adapterClassName = "\\\\OneTeamSoftware\\\\WC\\\\MultiVendorBridge\\\\Adapter\\\\" . $adapterName;
				if (class_exists($adapterClassName)) {
					$adapter = new $adapterClassName();
					if (method_exists($adapter, "isCompatible") && $adapter->isCompatible()) {
						return $adapter;
					}
				}
			}
		}

		return null;
	}
}';
	}
}
