# Lightcast PHP MVC Framework

**Version:** 1.5.3 | **License:** GPL-3.0-or-later | **Requires:** PHP 5.6+

Lightcast is a full-stack PHP MVC framework developed by [Nimasystems Ltd](https://www.nimasystems.com) for building enterprise-grade web applications, console tools, and web services from a single, unified codebase.

---

## Highlights

### MVC Architecture
Clean separation of concerns through Controllers, Views, and Models. Each module is self-contained with its own controller, templates, forms, and models.

### Multiple Execution Contexts
One framework, three runtime modes:
- **Web** (`lcWebConfiguration`) — HTTP request/response cycle
- **Console** (`lcConsoleConfiguration`) — CLI task runner
- **Web Service** (`lcWebServiceConfiguration`) — API / RPC endpoint

### Multi-Application Projects
A single project can host multiple applications (e.g. `frontend`, `backend`), each with its own configuration, modules, layouts, and entry point. Each application extends `lcWebConfiguration` with its own class.

### YAML Configuration
All configuration is environment-aware YAML. Override any value per environment (`dev`, `prod`, `test`) without touching shared config. Supports `.env` variable injection (`env(VAR_NAME)`).

### Plugin System
Extend any application by dropping a plugin into `addons/plugins/`. Plugins are self-contained bundles that register their own models, forms, modules, components, tasks, and event listeners — with zero coupling to the host app.

### Propel ORM Integration
First-class integration with Propel ORM for type-safe, schema-driven database access. Build schemas in XML, generate PHP models, run migrations.

### Event-Driven Core
An `lcEventDispatcher` bus connects every framework component. Fire and listen to named events anywhere without hard dependencies.

### Swappable Loaders
Every system service — request, response, router, logger, cache, mailer, storage, i18n — is defined by a class name in `loaders.yml`. Swap any service without touching application code.

### Template Engine
Built-in template engine using `{$variable}` syntax with block regions, i18n tags (`{t}...{/t}`), and partial inclusion (`<!-- PARTIAL module/action -->`).

### Caching Backends
Built-in adapters for Memcache, Redis, APC, and file-based caching. Configure once in `project.yml`, use the same cache API everywhere.

### Action Forms
Encapsulate form validation and execution logic in dedicated `lcBaseActionForm` (or `lcModelScopeActionForm`) subclasses with a widget schema system.

### Components
Reusable HTML fragments via `lcHtmlComponent` subclasses, includable from any controller or template.

### Built-In Security
Authentication/authorization via `security.yml`, cryptography via [paragonie/halite](https://github.com/paragonie/halite) (libsodium), and role-based access control.

### Internationalization
Database-backed or file-based i18n with `lcDbLanguageSystem`. All translatable strings flow through the `t()` shortcut, including inline template tags.

---

## Architecture at a Glance

```
HTTP Request
    │
    ▼
webroot/index.php  ──►  lcFrontendConfiguration (extends lcWebConfiguration)
                                │
                          lcApp (singleton)
                                │
              ┌─────────────────┼──────────────┐
              ▼                 ▼              ▼
         lcWebRequest      lcPatternRouting   lcPluginManager
              │                 │
              └────────┬────────┘
                       ▼
             lcFrontWebController
                       │
                       ▼
           cModuleName::actionXxx()
                       │
                       ▼
              templates/action.htm
              + layouts/index.htm
                       │
                       ▼
            lcWebResponse::send()
```

---

## Quick Example

**Controller** (`app/applications/frontend/modules/home/home.php`):
```php
class cHome extends lcWebController
{
    protected $use_plugins = ['core'];

    public function actionIndex()
    {
        $this->view['greeting'] = 'Hello from Lightcast!';
        return $this->render();
    }
}
```

**Template** (`app/applications/frontend/modules/home/templates/index.htm`):
```html
<h1>{$greeting}</h1>
```

**Entry point** (`app/webroot/index.php`):
```php
<?php
require_once '../lib/boot.php';
require_once '../applications/frontend/config/lcFrontendConfiguration.class.php';

$configuration = new lcFrontendConfiguration(
    realpath(dirname(__FILE__) . '/../'),
    new ProjectConfiguration()
);

lcApp::bootstrap($configuration)->dispatch();
```

---

## Repository Structure

```
lightcast/
├── source/
│   ├── libs/           # All framework source code
│   ├── assets/         # Templates, autoload cache, default configs
│   ├── framework_app/  # Internal framework bootstrap app
│   └── 3rdparty/       # Bundled third-party libraries
├── shell/              # Console entry points
├── vendor/             # Composer dependencies
└── composer.json
```

**Typical project using Lightcast:**

```
my_project/app/
├── applications/
│   ├── frontend/
│   │   ├── config/lcFrontendConfiguration.class.php
│   │   ├── modules/{module}/{module}.php + templates/
│   │   └── layouts/index.htm
│   └── backend/
│       └── ...
├── addons/plugins/{plugin}/
│   ├── plugin.php
│   ├── modules/, forms/, models/, tasks/, components/
├── config/default/
│   ├── project.yml, databases.yml
│   └── applications/frontend/*.yml
├── lib/boot.php
├── shell/cmd
└── webroot/index.php
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
