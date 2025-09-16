<?php

/**
 * Fix for multiple SlevomatCodingStandard TypeErrors
 * 
 * This script patches the SlevomatCodingStandard library to fix multiple bugs:
 * 1. ReferencedName::__construct TypeError when attribute_closer is null
 * 2. AssertAnnotation::__construct TypeError with wrong parameter types
 * 3. PropertyHelper undefined index: bracket_closer
 * 4. DisallowImplicitArrayCreationSniff undefined index: bracket_closer
 */

$fixes = [
	[
		'file' => 'vendor/slevomat/coding-standard/SlevomatCodingStandard/Helpers/ReferencedNameHelper.php',
		'search' => '$tokens[$attributeStartPointer][\'attribute_closer\']',
		'replace' => '$tokens[$attributeStartPointer][\'attribute_closer\'] ?? $attributeStartPointer',
		'check' => '?? $attributeStartPointer',
		'description' => 'ReferencedName attribute_closer null safety'
	],
	[
		'file' => 'vendor/slevomat/coding-standard/SlevomatCodingStandard/Helpers/ScopeHelper.php',
		'search' => 'public static function getRootPointer(File $phpcsFile, int $pointer): int',
		'replace' => 'public static function getRootPointer(File $phpcsFile, int $pointer): ?int',
		'check' => 'getRootPointer(File $phpcsFile, int $pointer): ?int',
		'description' => 'ScopeHelper getRootPointer return type fix'
	],
	[
		'file' => 'vendor/slevomat/coding-standard/SlevomatCodingStandard/Helpers/AnnotationHelper.php',
		'search' => 'new AssertAnnotation(',
		'replace' => 'new AssertAnnotation(',
		'check' => 'instanceof AssertTagValueNode',
		'description' => 'AssertAnnotation type checking',
		'custom_fix' => true
	],
	[
		'file' => 'vendor/slevomat/coding-standard/SlevomatCodingStandard/Helpers/PropertyHelper.php',
		'search' => '&& $tokens[$previousCurlyBracketPointer][\'bracket_closer\'] > $variablePointer',
		'replace' => '&& isset($tokens[$previousCurlyBracketPointer][\'bracket_closer\']) && $tokens[$previousCurlyBracketPointer][\'bracket_closer\'] > $variablePointer',
		'check' => 'isset($tokens[$previousCurlyBracketPointer][\'bracket_closer\'])',
		'description' => 'PropertyHelper bracket_closer undefined index fix'
	],
	[
		'file' => 'vendor/slevomat/coding-standard/SlevomatCodingStandard/Sniffs/Arrays/DisallowImplicitArrayCreationSniff.php',
		'search' => '$assignmentPointer = TokenHelper::findNextEffective($phpcsFile, $tokens[$bracketOpenerPointer][\'bracket_closer\'] + 1);',
		'replace' => '$assignmentPointer = TokenHelper::findNextEffective($phpcsFile, (isset($tokens[$bracketOpenerPointer][\'bracket_closer\']) ? $tokens[$bracketOpenerPointer][\'bracket_closer\'] : $bracketOpenerPointer) + 1);',
		'check' => 'isset($tokens[$bracketOpenerPointer][\'bracket_closer\'])',
		'description' => 'DisallowImplicitArrayCreationSniff bracket_closer undefined index fix'
	]
];

$fixesApplied = 0;

foreach ($fixes as $fix) {
	$file = $fix['file'];
	
	if (!file_exists($file)) {
		echo "File not found: {$file}, skipping.\n";
		continue;
	}
	
	$content = file_get_contents($file);
	
	// Check if already patched
	if (strpos($content, $fix['check']) !== false) {
		echo "Already patched: {$fix['description']}\n";
		continue;
	}
	
	if (isset($fix['custom_fix']) && $fix['custom_fix']) {
		// Custom fix for AssertAnnotation
		$pattern = '/new AssertAnnotation\(\s*(\$[^,]+),\s*(\$[^,]+),\s*(\$[^,]+),\s*(\$[^,]+),\s*(\$[^)]+)\s*\)/s';
		$replacement = 'new AssertAnnotation($1, $2, $3, $4, ($5 instanceof \PHPStan\PhpDocParser\Ast\PhpDoc\AssertTagValueNode) ? $5 : null)';
		$newContent = preg_replace($pattern, $replacement, $content);
	} else {
		// Simple string replacement
		$newContent = str_replace($fix['search'], $fix['replace'], $content);
	}
	
	if ($content === $newContent) {
		echo "Pattern not found for: {$fix['description']}\n";
		continue;
	}
	
	file_put_contents($file, $newContent);
	echo "Fixed: {$fix['description']}\n";
	$fixesApplied++;
}

if ($fixesApplied === 0) {
	echo "No fixes applied - SlevomatCodingStandard may already be patched or updated.\n";
} else {
	echo "Applied {$fixesApplied} fix(es) to SlevomatCodingStandard.\n";
}