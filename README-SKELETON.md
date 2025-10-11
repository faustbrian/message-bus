# PHP Package Skeleton

This is a skeleton/template for creating new PHP packages. It's based on the well-organized structure of the `fp` package.

## Usage

To create a new package from this skeleton:

1. Copy this directory to your new package location:
   ```bash
   cp -r skeleton-php your-package-name
   cd your-package-name
   ```

2. Replace all placeholders in the files:
   - `:package_name` - The package name (e.g., `my-awesome-package`)
   - `:package_description` - A short description of the package
   - `:vendor_namespace` - The vendor namespace in PascalCase (e.g., `MyAwesomePackage`)

3. Files that contain placeholders:
   - `composer.json` - Package name, description, and namespace
   - `README.md` - Package name and description
   - `CONTRIBUTING.md` - Package name
   - `SECURITY.md` - Package name

4. You can use this command to replace all placeholders at once:
   ```bash
   # Set your variables
   PACKAGE_NAME="my-package"
   PACKAGE_DESC="My awesome PHP package"
   VENDOR_NS="MyPackage"

   # Replace in all files
   find . -type f \( -name "*.json" -o -name "*.md" \) -exec sed -i '' "s/:package_name/$PACKAGE_NAME/g" {} +
   find . -type f \( -name "*.json" -o -name "*.md" \) -exec sed -i '' "s/:package_description/$PACKAGE_DESC/g" {} +
   find . -type f -name "composer.json" -exec sed -i '' "s/:vendor_namespace/$VENDOR_NS/g" {} +
   ```

5. Remove this README-SKELETON.md file and use README.md instead

6. Initialize git repository:
   ```bash
   git init
   git add .
   git commit -m "chore: initial commit from skeleton"
   ```

7. Install dependencies:
   ```bash
   composer install
   ```

## What's Included

### Configuration Files

- **composer.json** - Composer configuration with standard dev dependencies (PHPStan, Rector, Pest, PHP-CS-Fixer)
- **phpstan.neon.dist** - PHPStan configuration at max level
- **rector.php** - Rector configuration with PHP 8.4 and Laravel sets
- **phpunit.xml.dist** - PHPUnit configuration for Pest
- **.php-cs-fixer.php** - PHP-CS-Fixer configuration using cline/php-cs-fixer preset
- **.editorconfig** - Editor configuration for consistent coding styles
- **.gitignore** - Standard PHP package gitignore
- **.gitattributes** - Git attributes for export-ignore

### Development Tools

- **Makefile** - Convenient make commands for common tasks:
  - `make build` - Build Docker containers
  - `make shell` - Open shell in PHP container
  - `make composer` - Install dependencies
  - `make lint` - Run PHP-CS-Fixer
  - `make refactor` - Run Rector
  - `make test` - Run full test suite
  - `make test:lint`, `test:types`, `test:unit`, etc. - Individual test commands

- **docker-compose.yml** - Docker setup with PHP 8.4
- **docker/php/84/** - PHP 8.4 Docker configuration with Xdebug

### Documentation

- **README.md** - Template README with badges and standard sections
- **CHANGELOG.md** - Changelog template following Keep a Changelog format
- **CONTRIBUTING.md** - Contributing guidelines
- **CODE_OF_CONDUCT.md** - Code of conduct
- **SECURITY.md** - Security policy
- **LICENSE.md** - MIT license

### GitHub Templates

- **.github/workflows/quality-assurance.yaml** - GitHub Actions workflow for CI/CD
- **.github/ISSUE_TEMPLATE.md** - Issue template
- **.github/PULL_REQUEST_TEMPLATE.md** - Pull request template

### Test Setup

- **tests/Pest.php** - Pest configuration
- **tests/TestCase.php** - Base test case extending Orchestra Testbench
- **tests/Unit/** - Directory for unit tests

### Source

- **src/** - Empty source directory (with .gitkeep)

## Composer Scripts

The following composer scripts are available:

- `composer lint` - Fix code style issues
- `composer refactor` - Run refactoring with Rector
- `composer test` - Run all tests (lint, type-coverage, unit, types, refactor)
- `composer test:lint` - Check code style
- `composer test:types` - Run PHPStan type checks
- `composer test:unit` - Run unit tests with 100% coverage requirement
- `composer test:type-coverage` - Run type coverage checks (100% required)
- `composer test:refactor` - Check refactoring suggestions

## Standards

This skeleton enforces high code quality standards:

- PHP 8.4+ required
- PHPStan at max level
- 100% test coverage required
- 100% type coverage required
- Strict coding style via PHP-CS-Fixer
- Automated refactoring suggestions via Rector

## License

MIT
