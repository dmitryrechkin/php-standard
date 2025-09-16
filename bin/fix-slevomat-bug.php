<?php

/**
 * Fix for multiple SlevomatCodingStandard and PHP_CodeSniffer TypeErrors
 * 
 * This script patches the SlevomatCodingStandard and PHP_CodeSniffer libraries to fix multiple bugs:
 * 1. ReferencedName::__construct TypeError when attribute_closer is null
 * 2. AssertAnnotation::__construct TypeError with wrong parameter types
 * 3. PropertyHelper undefined index: bracket_closer
 * 4. DisallowImplicitArrayCreationSniff undefined index: bracket_closer
 * 5. PSR2 SwitchDeclarationSniff undefined index: scope_opener
 * 6. EarlyExitSniff exception for else without curly braces
 * 7. EarlyExitSniff scope_opener/scope_closer undefined index
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
		'search' => 'if ($tokens[$assignmentPointer][\'code\'] !== T_EQUAL) {',
		'replace' => 'if ($assignmentPointer === null || $tokens[$assignmentPointer][\'code\'] !== T_EQUAL) {',
		'check' => '$assignmentPointer === null ||',
		'description' => 'DisallowImplicitArrayCreationSniff null assignmentPointer fix'
	],
	[
		'file' => 'vendor/squizlabs/php_codesniffer/src/Standards/PSR2/Sniffs/ControlStructures/SwitchDeclarationSniff.php',
		'search' => '$opener     = $tokens[$nextCase][\'scope_opener\'];',
		'replace' => '$opener     = isset($tokens[$nextCase][\'scope_opener\']) ? $tokens[$nextCase][\'scope_opener\'] : null;
            if ($opener === null) {
                continue;
            }',
		'check' => 'isset($tokens[$nextCase][\'scope_opener\'])',
		'description' => 'PSR2 SwitchDeclarationSniff scope_opener undefined index fix'
	],
	[
		'file' => 'vendor/slevomat/coding-standard/SlevomatCodingStandard/Sniffs/ControlStructures/EarlyExitSniff.php',
		'search' => 'if (!array_key_exists(\'scope_closer\', $tokens[$currentConditionPointer])) {
						throw new Exception(
							sprintf(\'"%s" without curly braces is not supported.\', $tokens[$currentConditionPointer][\'content\']),
						);
					}',
		'replace' => 'if (!array_key_exists(\'scope_closer\', $tokens[$currentConditionPointer])) {
						// Skip else/elseif without curly braces instead of throwing exception
						return $conditionsPointers;
					}',
		'check' => 'Skip else/elseif without curly braces',
		'description' => 'EarlyExitSniff exception fix for else without curly braces'
	],
	[
		'file' => 'vendor/slevomat/coding-standard/SlevomatCodingStandard/Sniffs/ControlStructures/EarlyExitSniff.php',
		'search' => 'if ($this->findEarlyExitInScope(
					$phpcsFile,
					$tokens[$conditionPointer][\'scope_opener\'],
					$tokens[$conditionPointer][\'scope_closer\'],
				) === null) {',
		'replace' => 'if (!array_key_exists(\'scope_opener\', $tokens[$conditionPointer]) || !array_key_exists(\'scope_closer\', $tokens[$conditionPointer]) || $this->findEarlyExitInScope(
					$phpcsFile,
					$tokens[$conditionPointer][\'scope_opener\'],
					$tokens[$conditionPointer][\'scope_closer\'],
				) === null) {',
		'check' => '!array_key_exists(\'scope_opener\', $tokens[$conditionPointer])',
		'description' => 'EarlyExitSniff scope_opener/scope_closer undefined index fix'
	],
	[
		'file' => 'vendor/slevomat/coding-standard/SlevomatCodingStandard/Helpers/UseStatementHelper.php',
		'search' => '					$pointer = $token[\'scope_closer\'] + 1;',
		'replace' => '					$pointer = isset($token[\'scope_closer\']) ? $token[\'scope_closer\'] + 1 : $pointer + 1;',
		'check' => 'isset($token[\'scope_closer\']) ? $token[\'scope_closer\']',
		'description' => 'UseStatementHelper scope_closer undefined index fix'
	],
	[
		'file' => 'vendor/slevomat/coding-standard/SlevomatCodingStandard/Helpers/ReferencedNameHelper.php',
		'search' => 'if ($tokens[$nextTokenAfterEndPointer][\'code\'] === T_OPEN_PARENTHESIS) {
			return $tokens[$previousTokenBeforeStartPointer][\'code\'] === T_NEW
				? ReferencedName::TYPE_CLASS
				: ReferencedName::TYPE_FUNCTION;
		}

		if (
			$tokens[$previousTokenBeforeStartPointer][\'code\'] === T_TYPE_UNION
			|| $tokens[$nextTokenAfterEndPointer][\'code\'] === T_TYPE_UNION
		) {
			return ReferencedName::TYPE_CLASS;
		}

		if (
			$tokens[$previousTokenBeforeStartPointer][\'code\'] === T_TYPE_INTERSECTION
			|| $tokens[$nextTokenAfterEndPointer][\'code\'] === T_TYPE_INTERSECTION
		) {
			return ReferencedName::TYPE_CLASS;
		}

		if ($tokens[$nextTokenAfterEndPointer][\'code\'] === T_BITWISE_AND) {
			$tokenAfterNextToken = TokenHelper::findNextEffective($phpcsFile, $nextTokenAfterEndPointer + 1);

			return in_array($tokens[$tokenAfterNextToken][\'code\'], [T_VARIABLE, T_ELLIPSIS], true)
				? ReferencedName::TYPE_CLASS
				: ReferencedName::TYPE_CONSTANT;
		}

		if (
			in_array($tokens[$nextTokenAfterEndPointer][\'code\'], [
				T_VARIABLE,
				// Variadic parameter
				T_ELLIPSIS,
			], true)
		) {
			return ReferencedName::TYPE_CLASS;
		}

		if ($tokens[$previousTokenBeforeStartPointer][\'code\'] === T_COLON) {',
		'replace' => 'if ($nextTokenAfterEndPointer !== null && $tokens[$nextTokenAfterEndPointer][\'code\'] === T_OPEN_PARENTHESIS) {
			return $previousTokenBeforeStartPointer !== null && $tokens[$previousTokenBeforeStartPointer][\'code\'] === T_NEW
				? ReferencedName::TYPE_CLASS
				: ReferencedName::TYPE_FUNCTION;
		}

		if (
			($previousTokenBeforeStartPointer !== null && $tokens[$previousTokenBeforeStartPointer][\'code\'] === T_TYPE_UNION)
			|| ($nextTokenAfterEndPointer !== null && $tokens[$nextTokenAfterEndPointer][\'code\'] === T_TYPE_UNION)
		) {
			return ReferencedName::TYPE_CLASS;
		}

		if (
			($previousTokenBeforeStartPointer !== null && $tokens[$previousTokenBeforeStartPointer][\'code\'] === T_TYPE_INTERSECTION)
			|| ($nextTokenAfterEndPointer !== null && $tokens[$nextTokenAfterEndPointer][\'code\'] === T_TYPE_INTERSECTION)
		) {
			return ReferencedName::TYPE_CLASS;
		}

		if ($nextTokenAfterEndPointer !== null && $tokens[$nextTokenAfterEndPointer][\'code\'] === T_BITWISE_AND) {
			$tokenAfterNextToken = TokenHelper::findNextEffective($phpcsFile, $nextTokenAfterEndPointer + 1);

			return ($tokenAfterNextToken !== null && in_array($tokens[$tokenAfterNextToken][\'code\'], [T_VARIABLE, T_ELLIPSIS], true))
				? ReferencedName::TYPE_CLASS
				: ReferencedName::TYPE_CONSTANT;
		}

		if (
			$nextTokenAfterEndPointer !== null && in_array($tokens[$nextTokenAfterEndPointer][\'code\'], [
				T_VARIABLE,
				// Variadic parameter
				T_ELLIPSIS,
			], true)
		) {
			return ReferencedName::TYPE_CLASS;
		}

		if ($previousTokenBeforeStartPointer !== null && $tokens[$previousTokenBeforeStartPointer][\'code\'] === T_COLON) {',
		'check' => '$nextTokenAfterEndPointer !== null && $tokens[$nextTokenAfterEndPointer]',
		'description' => 'ReferencedNameHelper comprehensive null safety fixes'
	],
	[
		'file' => 'vendor/slevomat/coding-standard/SlevomatCodingStandard/Helpers/ClassHelper.php',
		'search' => 'return $tokens[TokenHelper::findNext($phpcsFile, T_STRING, $classPointer + 1, $tokens[$classPointer][\'scope_opener\'])][\'content\'];',
		'replace' => 'return $tokens[TokenHelper::findNext($phpcsFile, T_STRING, $classPointer + 1, isset($tokens[$classPointer][\'scope_opener\']) ? $tokens[$classPointer][\'scope_opener\'] : null)][\'content\'];',
		'check' => 'isset($tokens[$classPointer][\'scope_opener\']) ? $tokens[$classPointer][\'scope_opener\'] : null',
		'description' => 'ClassHelper scope_opener undefined index fix'
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