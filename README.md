[![GitHub Workflow Status][ico-tests]][link-tests]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

------

# message-bus

A lightweight, extensible message bus implementation for PHP supporting both Command and Query patterns with middleware pipeline architecture.

## Requirements

> **Requires [PHP 8.4+](https://php.net/releases/)**

## Installation

```bash
composer require cline/message-bus
```

## Documentation

- **[Getting Started](https://docs.cline.sh/message-bus/getting-started/)** - Installation and basic usage
- **[Command Bus](https://docs.cline.sh/message-bus/command-bus/)** - Write operations and handlers
- **[Query Bus](https://docs.cline.sh/message-bus/query-bus/)** - Read operations and CQRS
- **[Middleware](https://docs.cline.sh/message-bus/middleware/)** - Pipeline and cross-cutting concerns
- **[Handler Discovery](https://docs.cline.sh/message-bus/handler-discovery/)** - Automatic handler registration
- **[API Reference](https://docs.cline.sh/message-bus/api-reference/)** - Complete API documentation

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please use the [GitHub security reporting form][link-security] rather than the issue queue.

## Credits

- [Brian Faust][link-maintainer]
- [All Contributors][link-contributors]

## License

The MIT License. Please see [License File](LICENSE.md) for more information.

[ico-tests]: https://git.cline.sh/faustbrian/message-bus/actions/workflows/quality-assurance.yaml/badge.svg
[ico-version]: https://img.shields.io/packagist/v/cline/message-bus.svg
[ico-license]: https://img.shields.io/badge/License-MIT-green.svg
[ico-downloads]: https://img.shields.io/packagist/dt/cline/message-bus.svg

[link-tests]: https://git.cline.sh/faustbrian/message-bus/actions
[link-packagist]: https://packagist.org/packages/cline/message-bus
[link-downloads]: https://packagist.org/packages/cline/message-bus
[link-security]: https://git.cline.sh/faustbrian/message-bus/security
[link-maintainer]: https://git.cline.sh/faustbrian
[link-contributors]: ../../contributors
