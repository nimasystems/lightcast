# Lightcast PHP MVC Framework — 2.x

**Version:** 2.0+ | **License:** GPL-3.0-or-later | **Requires:** PHP 7.4+

Lightcast 2.x is a full-stack PHP MVC framework developed by [Nimasystems Ltd](https://www.nimasystems.com) for building enterprise-grade web applications, console tools, and web services. It is the successor to Lightcast 1.x and introduces PHP 7.4+ strict typing, PSR-4 namespaces throughout application code, and a formalized multi-application architecture.

---

## What's New in 2.x

- **PHP 7.4+ required** with `declare(strict_types=1)` throughout
- **PSR-4 namespaces** for all application and plugin code
- **Project namespace** declared centrally in the project configuration class
- **Multi-application architecture** — a single codebase runs Frontend, Backend, API, and any number of other applications
- **Plugin `Configuration` class** replaces simple `plugin.php` meta — declares DB models, tasks, requirements and locales via interfaces
- **Framework auto-discovery** — `boot.php` finds the framework in `local/lightcast` or `vendor/nimasystems/lightcast` automatically
- **Composer `vendor/autoload.php`** is loaded first; framework adds its own class autoloader on top
- **FQCN loaders** — all service bindings in `loaders.yml` use fully qualified class names with leading backslash

The framework kernel classes still use the legacy `lc` prefix without namespaces. Application code imports them with bare `use lcApp;`, `use lcPlugin;` etc.

---

## Highlights

### Multi-Application Support
One project, many applications. Each application has its own configuration class, modules, layouts, routing, and entry point — while sharing plugins, models, and extensions.

### PSR-4 Namespaces
All your code lives under a project namespace (e.g. `Designconnected\`). Controllers, plugins, tasks, extensions — everything is auto-loaded via Composer.

### Plugin System
Plugins declare their capabilities through PHP interfaces on their `Configuration` class: DB models, console tasks, web service endpoints, locales, and plugin requirements. No magic, no convention scanning.

### YAML Configuration with `.env` Injection
All config is in YAML. Any value can pull from the environment: `env(VAR_NAME)`. Environment-specific overrides live in the same file under a `prod:` / `dev:` key.

### Event-Driven Core
`lcEventDispatcher` connects every component. Application config classes hook into framework events (`request.startup`, `response.startup`, etc.) for request-level customization.

### Extensions Layer
Shared cross-cutting logic lives in `src/Extensions/` — custom base controllers, request/response subclasses, mailers, utilities. All namespaced and Composer-autoloaded.

### Template Engine
Built-in template engine: `{$variable}`, `{$variable:asis}`, `{t}...{/t}` i18n tags, `<!-- PARTIAL module/partial -->` includes, `<!-- BEGIN block -->...<!-- END block -->` conditionals.

### Propel ORM
Full Propel ORM integration. Generated model classes live in `Gen/Propel/Models/` or inside plugin `Models/` directories.

---

## Architecture at a Glance

```
webroot/index.php
    │
    ├── require app/lib/boot.php          ← finds framework, loads Composer, project config
    ├── new Frontend\Config\Configuration  ← per-application config class
    └── lcApp::bootstrap($config)->dispatch()
                │
          ┌─────┴──────────────────────┐
          ▼                            ▼
   lcPatternRouting              lcPluginManager
          │                       (declared in plugins.yml
          ▼                        + plugin Config classes)
   {Ns}\Applications\{App}\
   Modules\{Module}\{Module}
          │
          ▼  action{Name}()
   returns array  →  templates/{action}.htm
   + layouts/index.htm  →  lcWebResponse::send()
```

---

## Quick Example

**Project config** (`src/Config/Configuration.php`):
```php
namespace Acme\Config;
use lcProjectConfiguration;

class Configuration extends lcProjectConfiguration
{
    public function getProjectName(): string    { return 'acme'; }
    public function getProjectNamespace(): string { return 'Acme'; }
    public function getSupportedLocales(): array { return ['en_US', 'de_DE']; }
}
```

**App config** (`src/Applications/Frontend/Config/Configuration.php`):
```php
namespace Acme\Applications\Frontend\Config;
use lcWebConfiguration;

class Configuration extends lcWebConfiguration
{
    public function getApplicationName(): string { return 'Frontend'; }
}
```

**Controller** (`src/Applications/Frontend/Modules/Home/Home.php`):
```php
namespace Acme\Applications\Frontend\Modules\Home;
use Acme\Extensions\Controllers\CommonWebController;

class Home extends CommonWebController
{
    protected $use_plugins = ['Dc'];

    public function actionIndex(): array
    {
        return ['greeting' => 'Hello from Lightcast 2!'];
    }
}
```

**Template** (`src/Applications/Frontend/Modules/Home/templates/index.htm`):
```html
<h1>{$greeting}</h1>
```

**Entry point** (`webroot/index.php`):
```php
use Acme\Applications\Frontend\Config\Configuration;

$base_dir = realpath(dirname(__FILE__) . '/../');
require_once $base_dir . '/lib/boot.php';
require_once $base_dir . '/src/Applications/Frontend/Config/Configuration.php';

$configuration = new Configuration($base_dir, new \Acme\Config\Configuration());
include_once $base_dir . '/lib/boot-app.pre.php';
lcApp::bootstrap($configuration)->dispatch();
```

---

## Project Layout

```
app/
├── src/
│   ├── Config/
│   │   └── Configuration.php           # Project-level config
│   ├── Applications/
│   │   ├── Frontend/
│   │   │   ├── Config/Configuration.php
│   │   │   ├── Modules/{Module}/{Module}.php + templates/
│   │   │   ├── layouts/index.htm
│   │   │   ├── i18n/
│   │   │   └── locale/
│   │   └── Backend/  Api/  ...
│   ├── Plugins/
│   │   └── {Plugin}/
│   │       ├── {Plugin}.php
│   │       ├── Config/Configuration.php
│   │       ├── Models/
│   │       ├── Tasks/
│   │       └── ...
│   ├── Extensions/
│   │   ├── Controllers/CommonWebController.php
│   │   ├── Request/
│   │   ├── Response/
│   │   ├── User/
│   │   └── ...
│   └── Tasks/
│       └── {Task}.php
├── config/default/applications/{App}/*.yml
├── local/lightcast/                    # Framework source (or vendor/nimasystems/lightcast)
├── lib/boot.php
├── webroot/index.php
└── vendor/                             # Composer
```

---

## Third-Party Bundled Libraries

| Library | Purpose |
|---------|---------|
| Propel ORM | Database models & migrations |
| PHPMailer | Email sending |
| Spyc | YAML parser |
| Phing | Build tool |
| paragonie/halite | Cryptography (libsodium) |
| symfony/dotenv | `.env` file support |

---

## Authors

- **Martin Kovachev** — miracle@nimasystems.com — [Nimasystems Ltd](https://www.nimasystems.com)

---

See [INSTALL.md](INSTALL.md) for setup instructions and [DEV.md](DEV.md) for developer patterns.
