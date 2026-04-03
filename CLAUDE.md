# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Repo Is

This is the **Lightcast PHP MVC Framework** (v2.x) by Nimasystems — a proprietary full-stack PHP framework. It is typically used as a local checkout at `app/local/lightcast/` in a host project (takes priority over `vendor/nimasystems/lightcast`).

## Repository Layout

```
source/
├── libs/               # Framework kernel (no namespaces, lc* prefix)
│   ├── boot/           # bootstrap.php — entry point for the framework
│   ├── app/            # lcApp singleton, lifecycle
│   ├── controller/     # lcWebController, lcTaskController, lcApiController
│   ├── configuration/  # YAML loaders, lcProjectConfiguration, lcWebConfiguration
│   ├── plugins/        # lcPlugin, lcPluginManager, lcPluginConfiguration
│   ├── routing/        # lcPatternRouting
│   ├── request/        # lcWebRequest
│   ├── response/       # lcWebResponse
│   ├── view/           # Template engine, filters
│   ├── events/         # lcEventDispatcher, lcEvent
│   ├── database/       # Propel integration, lcPropelDatabase
│   ├── caching/        # lcCacheStore and adapters
│   ├── logger/         # lcFileLoggerNG
│   ├── forms/          # Form builder and validators
│   ├── user/           # lcUser, session-based auth
│   └── ...
├── packages/           # Bundled 3rd-party libs (Propel, PHPMailer, Spyc, Phing, etc.)
└── framework_app/      # Framework-level configuration stubs
shell/
├── bootstrap.php       # Console bootstrap (used by cmd)
└── cmd                 # Console entry point script
```

## Two Worlds — One Codebase

The framework kernel (`source/libs/`) uses the **legacy `lc` prefix with no namespaces**:

```php
use lcApp;
use lcWebController;
use lcPlugin;
use lcEventDispatcher;
```

Application and plugin code written against the framework uses **PSR-4 namespaces** rooted at a project namespace (e.g. `Designconnected\`).

All framework files use `declare(strict_types=1)`.

## Core Concepts

### Bootstrap Sequence

`lib/boot.php` (in host project) → `source/libs/boot/bootstrap.php` → `lcApp::bootstrap($config)->dispatch()`

The boot file auto-discovers the framework: checks `local/lightcast` before `vendor/nimasystems/lightcast`.

### Configuration Resolution

1. `lcProjectConfiguration` — project-level (`src/Config/Configuration.php`)
2. `lcWebConfiguration` / `lcConsoleConfiguration` — per-application (`src/Applications/{App}/Config/Configuration.php`)
3. `lcPluginConfiguration` — per-plugin (`src/Plugins/{Plugin}/Config/Configuration.php`)

YAML config files live at `config/default/applications/{App}/*.yml`. Keys: `loaders.yml`, `routing.yml`, `plugins.yml`, `view.yml`, `security.yml`, `settings.yml`. Any YAML value can reference `.env` variables via `env(VAR_NAME)`.

### Controllers

- Extend `lcWebController` (or a project-level `CommonWebController`)
- One controller class per module, file named `{Module}.php`
- Actions: `actionIndex(): array` — camelCase suffix becomes snake_case template name
- Plugin dependencies declared in `protected $use_plugins = ['Dc', 'Languages']`
- Lifecycle hooks: `beforeExecute()`, `afterExecute()`
- `RENDER_NONE` constant for actions with no output (redirects, APIs)

### Template Engine

Built-in, not Twig. Template files: `templates/{action_name}.htm`.

```html
{$variable}            <!-- escaped -->
{$variable:asis}       <!-- raw -->
{t}Translate{/t}       <!-- i18n -->
<!-- BEGIN block_name --> ... <!-- END block_name -->   <!-- conditional -->
<!-- PARTIAL module/partial -->                          <!-- include -->
[PAGE_CONTENT]                                          <!-- layout slot -->
```

### Plugin System

Plugins declare capabilities through PHP interfaces on their `Configuration` class — no convention scanning. Enabled plugins listed in `plugins.yml`.

### Events

`lcEventDispatcher` is the central bus. Connect: `$dispatcher->connect('event.name', $obj, 'methodName')`. Fire: `$dispatcher->notify(new lcEvent('event.name', $this))`.

### Routing

Pattern-based routing in `routing.yml`. URL patterns use `:param` placeholders with optional `requirements` regex. Route matching is top-down; put specific routes before generic ones.

### Console Tasks

Task classes extend `lcTaskController`. Invoked via `./shell/cmd {namespace} --action={action}`.

## Key Conventions

- All new framework kernel classes: `lc` prefix, no namespace, in `source/libs/`
- All application-layer code (when used in a host project): PSR-4 namespace, `declare(strict_types=1)`
- Loader bindings in `loaders.yml` require a leading `\` on FQCNs: `\Acme\Extensions\Request\AppWebRequest`
- Plugin names in `plugins.yml` are PascalCase, matching the directory and class name
- Action method → template: strip `action` prefix, convert to snake_case, append `.htm`
- `lcApp::getInstance()` gives access to all services: `getRequest()`, `getResponse()`, `getRouter()`, `getLogger()`, `getCache()`, `getDatabaseManager()`, `getPluginManager()`, `getI18n()`

## Bundled 3rd-Party Libraries

Located in `source/packages/`:

| Library | Purpose |
|---------|---------|
| propel | ORM — models generated into host project's `Gen/` directory |
| PHPMailer | Email |
| spyc | YAML parser |
| phing | Build tool |
| jsmin | JS minification |

## Development Notes

- No automated test suite exists in this repo — testing is done via integration with a host project
- The `source/packages/propel/` Propel version is patched (custom builders for `Gen/` output directory)
- The framework version constant is defined in `source/libs/boot/bootstrap.php` as `LIGHTCAST_VER`
- Healthcheck: `boot.php` responds `OK` to `?healthcheck=1` before any framework code runs
- Maintenance mode: create a `.maintenance` file in the project root to serve 503 immediately
