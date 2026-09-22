# SKIM Auth Extension — Developer Guide

This document outlines how to maintain and develop the `skim/auth` extension, specifically highlighting integration with the SKIM Framework static manifest specifications and local testing workflows.

## Development Environment Setup

To run tests and develop `skim/auth` in isolation, use the `skim_framework` Docker environment:

```bash
cd /Users/liquan/Documents/web/skim_extensions/auth

# Set up local symlink repository for skim/framework
docker compose \
  -f /Users/liquan/Documents/web/skim_framework/docker-compose.yml \
  run --rm -T \
  -v /Users/liquan/Documents/web/skim_extensions/auth:/work \
  -v /Users/liquan/Documents/web/skim_framework:/skim_framework \
  -w /work \
  app sh -lc 'composer config repositories.skim_framework "{\"type\":\"path\",\"url\":\"/skim_framework\",\"options\":{\"versions\":{\"skim/framework\":\"1.0.0\"}}}" && composer install'
```

## Manifest Contracts (`static manifest()`)

To allow SKIM Framework to discover, topologically sort, and map capabilities for AI agents without instantiating extension classes, the extension must maintain its metadata statically in two locations:

1. **`auth_extension::manifest()`**: Returning structured array metadata.
2. **`composer.json` (`extra.skim`)**: Mirroring the same metadata for fast scanning.

### Capability Structure Invariance

When declaring `capabilities`, use an associative array mapping the capability name to its features. This is loaded by the framework's AI documentation generator:

```php
public static function manifest(): array {
    return [
        'name'        => 'auth',
        'requires'    => ['session'],
        'provides'    => ['auth'],
        'capabilities' => [
            'auth' => [
                'strategies'    => ['session', 'api-token'],
                'middleware'    => 'auth_middleware',
                'routes_prefix' => '/auth',
            ],
            // ...
        ],
    ];
}
```

## SKIM Framework Core Features Integration

As an extension developer, your code interacts with several framework-level mechanisms configured to assist AI agents and debuggers:

### 1. Dependency Validation & Topological Sorting
- **What it does**: The SKIM Framework reads your extension's static manifest and/or `composer.json` `extra.skim.requires` section. It automatically validates that all dependencies (like `session`) are satisfied by active extensions before invoking `register()` or `boot()`.
- **How it boots**: Extensions are sorted topologically so that dependencies always boot before dependents.
- **Developer Action**: Always keep `requires` in `auth_extension::manifest()` and `composer.json` in sync. Do not dynamically require capabilities during the boot cycle.

### 2. Architecture Invariant Violation Audits
- **What it does**: The framework enforces a strict lifecycle mutation guard. Post-boot, the app is "frozen", preventing register, route, service, or middleware alterations.
- **Audit Logging**: Any post-boot violation throws a `LogicException` and writes an audit log in PHP's error stream containing the offending extension name:
  `[skim][invariant-violation] extension='auth' attempted 'bind' after freeze`
- **Developer Action**: Never register bindings or append routes in callback listeners that execute after the `boot()` lifecycle phase finishes.

### 3. Structured Request Tracing (`request_trace`)
- **What it does**: In debug mode (`APP_DEBUG=true`), the framework captures a high-resolution structured trace timeline of each HTTP request, including active extensions, loaded middleware, and controller execution times.
- **Developer Action**: You can trace custom extension actions or database/cache transactions using the `request_trace` API:
  ```php
  use Skim\Dev\RequestTrace;

  request_trace::event('oauth_callback_received', ['provider' => 'github']);
  ```

## Running Tests

Execute Pest feature and unit tests inside the container environment:

```bash
docker compose \
  -f /Users/liquan/Documents/web/skim_framework/docker-compose.yml \
  run --rm -T \
  -v /Users/liquan/Documents/web/skim_extensions/auth:/work \
  -v /Users/liquan/Documents/web/skim_framework:/skim_framework \
  -w /work \
  app ./vendor/bin/pest
```
