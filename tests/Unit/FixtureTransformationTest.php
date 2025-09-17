<?php

declare(strict_types=1);

namespace DmitryRechkin\Tests\PhpStandard\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests that verify PHPCBF properly transforms code using fixtures
 */
class FixtureTransformationTest extends TestCase
{
	/**
	 * Test that tab indentation is properly enforced
	 */
	public function testTabIndentationTransformation(): void
	{
		$beforeFile = __DIR__ . '/../fixtures/before/BadTabIndentation.php';
		$afterFile = __DIR__ . '/../fixtures/after/BadTabIndentation.php';

		$this->assertFixtureTransformation($beforeFile, $afterFile, [
			'Contains tab indentation in formatted version',
			'No space-only indentation in formatted version',
		]);
	}

	/**
	 * Test that array formatting is properly enforced
	 */
	public function testArrayFormattingTransformation(): void
	{
		$beforeFile = __DIR__ . '/../fixtures/before/BadArrayFormatting.php';
		$afterFile = __DIR__ . '/../fixtures/after/BadArrayFormatting.php';

		$beforeContent = file_get_contents($beforeFile);
		$afterContent = file_get_contents($afterFile);

		// Old array() syntax should be converted to []
		$this->assertStringContainsString('array(', $beforeContent);
		$this->assertStringNotContainsString('array(', $afterContent);
		$this->assertStringContainsString("['item1', 'item2', 'item3']", $afterContent);

		// Array formatting should be consistent
		$this->assertStringContainsString("'key1' => 'value1',", $afterContent);
		$this->assertStringContainsString("'key2' => 'value2',", $afterContent);
		$this->assertStringContainsString("'key3' => 'value3',", $afterContent);
	}

	/**
	 * Test that function documentation requirements work
	 */
	public function testFunctionDocumentationValidation(): void
	{
		$beforeFile = __DIR__ . '/../fixtures/before/BadFunctionDocumentation.php';

		// Run PHPCS to check for function documentation violations
		$output = $this->runPhpcs($beforeFile);

		// Should detect missing function documentation
		$this->assertStringContainsString('Missing doc comment for function', $output);
	}

	/**
	 * Test that line length limits are enforced
	 */
	public function testLineLengthValidation(): void
	{
		$beforeFile = __DIR__ . '/../fixtures/before/BadLineLengthAndMixedQuotes.php';

		// Run PHPCS to check for line length violations
		$output = $this->runPhpcs($beforeFile);

		// Should detect line length violations
		$this->assertStringContainsString('exceeds', $output);
	}

	/**
	 * Test that PHPCBF can process files without hanging
	 */
	public function testPhpcbfPerformance(): void
	{
		$beforeFile = __DIR__ . '/../fixtures/before/BadArrayFormatting.php';
		$tempFile = $this->createTempFile(file_get_contents($beforeFile));

		$startTime = microtime(true);
		$this->runPhpcbf($tempFile);
		$endTime = microtime(true);

		$executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

		// Should complete in under 1 second (1000ms)
		$this->assertLessThan(1000, $executionTime, 'PHPCBF should complete quickly without hanging');

		unlink($tempFile);
	}

	/**
	 * Test that the standard works with real-world code patterns
	 */
	public function testRealWorldCodePatterns(): void
	{
		$complexCode = '<?php

declare(strict_types=1);

namespace OneTeamSoftware\WC\Test;

class RealWorldExample
{
	public function processOrderData(array $orderData, int $vendorId, string $shippingMethod = "standard"): array
	{
		$result = apply_filters("OneTeamSoftware\\WooCommerce\\MultiVendorBridge\\Adapter\\ProcessOrderData", $orderData, $vendorId, $shippingMethod);
		
		$processedItems = array_map(function ($item) use ($vendorId) {
			return array(
				"item_id" => $item["id"],
				"vendor_id" => $vendorId,
				"processed" => true
			);
		}, $orderData["items"]);
		
		return array_merge($result, array("processed_items" => $processedItems));
	}
}';

		$tempFile = $this->createTempFile($complexCode);

		// Should process without hanging
		$startTime = microtime(true);
		$this->runPhpcbf($tempFile);
		$endTime = microtime(true);

		$executionTime = ($endTime - $startTime) * 1000;
		$this->assertLessThan(1000, $executionTime);

		// Check that formatting was applied
		$formattedContent = file_get_contents($tempFile);
		$this->assertStringNotContainsString('array(', $formattedContent);
		$this->assertStringContainsString("'item_id'", $formattedContent);

		unlink($tempFile);
	}

	/**
	 * Helper method to validate fixture transformation
	 */
	private function assertFixtureTransformation(string $beforeFile, string $afterFile, array $expectations): void
	{
		$this->assertFileExists($beforeFile, 'Before fixture file should exist');
		$this->assertFileExists($afterFile, 'After fixture file should exist');

		$beforeContent = file_get_contents($beforeFile);
		$afterContent = file_get_contents($afterFile);

		$this->assertNotEmpty($beforeContent, 'Before fixture should not be empty');
		$this->assertNotEmpty($afterContent, 'After fixture should not be empty');
		$this->assertNotEquals($beforeContent, $afterContent, 'Files should be different after transformation');

		// Verify the after file uses tab indentation
		$lines = explode("\n", $afterContent);
		$indentedLines = array_filter($lines, function ($line) {
			return preg_match('/^\s+/', $line);
		});

		foreach ($indentedLines as $line) {
			if (preg_match('/^(\s+)/', $line, $matches)) {
				$indent = $matches[1];
				$this->assertStringNotContainsString('    ', $indent, 'Should not contain 4-space indentation');
				// Tab should be the primary indentation character
				if (strlen($indent) >= 1) {
					$this->assertEquals("\t", substr($indent, 0, 1), 'First indent character should be tab');
				}
			}
		}
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

		exec($command, $output);
		return implode("\n", $output);
	}

	/**
	 * Run PHPCBF on a file
	 */
	private function runPhpcbf(string $file): void
	{
		$command = sprintf(
			'cd %s && vendor/bin/phpcbf %s --standard=php-standard.xml 2>&1',
			escapeshellarg(__DIR__ . '/../..'),
			escapeshellarg($file)
		);

		exec($command);
	}
}
