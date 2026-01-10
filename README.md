# Dmitry Rechkin's PHP Coding Standard

A comprehensive PHP coding standard package based on PSR-12 with additional tools for WordPress plugin development compatibility.

## Features

- **PHP CodeSniffer**: PSR-12 based coding standard with Slevomat additions
- **PHPStan**: Static analysis for code quality and bug detection
- **PHPLint**: Fast PHP syntax and linting tool
- **WordPress Compatibility**: Optimized for WordPress minimum PHP 7.4+ requirements

## Requirements

- PHP 7.4 or higher (aligns with WordPress requirements)
- Composer

## Installation

```bash
composer require dmitryrechkin/php-standard
```

## Usage

### Scripts

The package includes several shell scripts for easy integration:

- `bin/phpcs.sh` - Run CodeSniffer analysis
- `bin/phpcbf.sh` - Run CodeSniffer auto-fixing
- `bin/phplint.sh` - Run PHP linting
- `bin/phpstan.sh` - Run PHPStan static analysis

### Composer Scripts

```bash
# Run all tests (lint, phpcs, phpstan, phpunit)
composer test

# Run individual checks
composer test:lint      # PHPLint
composer test:phpcs     # CodeSniffer
composer test:phpstan   # PHPStan
composer test:phpunit   # PHPUnit

# Auto-fix issues
composer fix           # Run both PHPCBF and PHPStan fixes
composer fix:phpcbf    # CodeSniffer auto-fix only
composer fix:phpstan   # PHPStan fixes only
```

## WordPress Compatibility

This coding standard is specifically configured for WordPress plugin development:

- **Minimum PHP Version**: 7.4 (WordPress requirement for 2025)
- **Level 5 PHPStan**: Balanced strictness with WordPress compatibility
- **Hook Compatibility**: Allows unused parameters for WordPress action/filter hooks
- **Dynamic Properties**: Supports WordPress object patterns
- **Mixed Types**: Allows `mixed` type hints for WordPress API compatibility

## Configuration

### PHPStan

The `php-standard.neon` configuration includes:

- Level 5 analysis (good balance for WordPress)
- WordPress-specific error allowances
- Compatibility with dynamic properties
- Support for hook patterns

### CodeSniffer

The `php-standard.xml` standard includes:

- PSR-12 base standard
- Slevomat Coding Standard additions
- Variable analysis for unused variables
- WordPress-compatible rule exclusions
- Tab indentation (4 spaces)
- **JavaScript/CSS exclusions** - `*.js`, `*.css`, and minified files are automatically excluded (use ESLint/Stylelint instead)

## Integration with Projects

To use this standard in your project:

1. Add to your project's `composer.json`:
```json
{
    "require-dev": {
        "dmitryrechkin/php-standard": "^1.0"
    },
    "scripts": {
        "test": [
            "@vendor/dmitryrechkin/php-standard/bin/phpcs.sh",
            "@vendor/dmitryrechkin/php-standard/bin/phpstan.sh"
        ],
        "fix": [
            "@vendor/dmitryrechkin/php-standard/bin/phpcbf.sh"
        ]
    }
}
```

2. Create symlinks to the scripts:
```bash
ln -s vendor/dmitryrechkin/php-standard/bin/phpcs.sh bin/phpcs.sh
ln -s vendor/dmitryrechkin/php-standard/bin/phpcbf.sh bin/phpcbf.sh
ln -s vendor/dmitryrechkin/php-standard/bin/phpstan.sh bin/phpstan.sh
```

## File Structure

```
php-standard/
├── bin/
│   ├── phpcs.sh      # CodeSniffer runner
│   ├── phpcbf.sh     # CodeSniffer fixer
│   ├── phplint.sh    # PHPLint runner
│   └── phpstan.sh    # PHPStan runner
├── php-standard.xml  # CodeSniffer rules
├── php-standard.neon # PHPStan configuration
└── storage/          # Cache directory (gitignored)
```

## License

AGPL-3.0-only

## Author

Dmitry Rechkin <rechkin@gmail.com>

## Changelog

### v1.1.0 (2025)
- Added PHPStan 2.0 support
- Added WordPress PHP 7.4+ compatibility
- Enhanced static analysis capabilities
- Added comprehensive error ignore patterns for WordPress
- Improved script robustness

### v1.0.0
- Initial release with PSR-12 + Slevomat support
- PHPLint integration
- Basic CodeSniffer configuration