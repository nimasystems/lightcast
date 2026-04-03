# Lightcast 2.x — Developer Reference

---

## Table of Contents

1. [Namespace & Class Conventions](#1-namespace--class-conventions)
2. [Object Hierarchy](#2-object-hierarchy)
3. [Application Bootstrap & Lifecycle](#3-application-bootstrap--lifecycle)
4. [Configuration System](#4-configuration-system)
5. [Controllers](#5-controllers)
6. [Views & Templates](#6-views--templates)
7. [Routing](#7-routing)
8. [Request & Response](#8-request--response)
9. [Extensions Layer](#9-extensions-layer)
10. [Database & Models (Propel)](#10-database--models-propel)
11. [Forms & Validation](#11-forms--validation)
12. [Plugin System](#12-plugin-system)
13. [Events & Observer Pattern](#13-events--observer-pattern)
14. [Caching](#14-caching)
15. [Logging](#15-logging)
16. [Internationalization (i18n)](#16-internationalization-i18n)
17. [User & Authentication](#17-user--authentication)
18. [Console Tasks](#18-console-tasks)
19. [Web Services](#19-web-services)
20. [Utility Classes](#20-utility-classes)
21. [Error Handling & Exceptions](#21-error-handling--exceptions)
22. [Autoloading](#22-autoloading)

---

## 1. Namespace & Class Conventions

### Two worlds, one framework

The Lightcast kernel (everything under `source/libs/`) uses the legacy `lc` class prefix with **no namespaces**. Application code imports them with bare `use` statements:

```php
use lcApp;
use lcPlugin;
use lcWebController;
use lcTaskController;
use lcEventDispatcher;
use lcInvalidArgumentException;
```

Everything you write in `src/` uses **PSR-4 namespaces** rooted at the project namespace declared in your project `Configuration` class:

```php
// Project namespace is 'Acme'
namespace Acme\Applications\Frontend\Modules\Home;
namespace Acme\Plugins\MyPlugin;
namespace Acme\Extensions\Controllers;
namespace Acme\Tasks;
```

### Naming table

| Thing | Namespace pattern | Class name | File |
|-------|-------------------|-----------|------|
| Project config | `{Ns}\Config` | `Configuration` | `src/Config/Configuration.php` |
| App config | `{Ns}\Applications\{App}\Config` | `Configuration` | `src/Applications/{App}/Config/Configuration.php` |
| Controller | `{Ns}\Applications\{App}\Modules\{Module}` | `{Module}` | `{Module}.php` |
| Plugin | `{Ns}\Plugins\{Plugin}` | `{Plugin}` | `{Plugin}.php` |
| Plugin config | `{Ns}\Plugins\{Plugin}\Config` | `Configuration` | `Config/Configuration.php` |
| Task (project) | `{Ns}\Tasks` | `{Name}` | `src/Tasks/{Name}.php` |
| Task (plugin) | `{Ns}\Plugins\{Plugin}\Tasks` | `{Name}` | `Tasks/{Name}.php` |
| Extension | `{Ns}\Extensions\{Area}` | any | `src/Extensions/{Area}/` |

All files must declare `declare(strict_types=1)`.

---

## 2. Object Hierarchy

Framework kernel classes (no namespaces):

```
lcObj
  └── lcDataObj
       └── lcSysObj            # logging, i18n, event access, configuration
            └── lcResidentObj  # holds lcApp reference
                 └── lcAppObj
                      └── lcPlugin
```

Controller hierarchy:

```
lcWebController
  └── Your\CommonWebController  # project-level shared base (in Extensions/)
       └── Your\Modules\Home\Home
```

Configuration hierarchy:

```
lcProjectConfiguration
  └── {Ns}\Config\Configuration          # your project config

lcWebConfiguration  (extends lcApplicationConfiguration)
  └── {Ns}\Applications\{App}\Config\Configuration

lcPluginConfiguration
  └── {Ns}\Plugins\{Plugin}\Config\Configuration
```

---

## 3. Application Bootstrap & Lifecycle

### Boot sequence

```
webroot/index.php
  1. require lib/boot.php
       - define MIN_LC_VER = '2.0.0'
       - check .maintenance file
       - require vendor/autoload.php
       - auto-discover framework: local/lightcast → vendor/nimasystems/lightcast
       - require framework bootstrap.php
       - require src/Config/Configuration.php
  2. new {App}\Config\Configuration($base_dir, new \{Ns}\Config\Configuration())
  3. include lib/boot-app.pre.php   (optional overrides)
  4. lcApp::bootstrap($config)->dispatch()
       - lcApp::getInstance()->initialize($config)
             a. profiler, error handler
             b. class autoloader + local cache manager
             c. load YAML config files
             d. database model manager
             e. plugin manager → initialize all enabled plugins
             f. all loader services (request, response, router, cache, i18n, etc.)
             g. logger
       - fire app.startup event
       - delegate->didInitializeApp()
       - route → invoke controller action → render → send response
```

### Application configuration class

```php
namespace Acme\Applications\Frontend\Config;

use lcWebConfiguration;

class Configuration extends lcWebConfiguration
{
    public function getApplicationName(): string
    {
        return 'Frontend';   // resolves config/default/applications/Frontend/
    }

    public function executeBefore(): void
    {
        parent::executeBefore();

        // Hook into request processing for every request
        $this->event_dispatcher->connect('request.startup', $this, 'onRequestStartup');
    }

    public function onRequestStartup(\lcEvent $event): void
    {
        // Runs before routing on every request
    }
}
```

### Accessing the singleton

```php
$app = lcApp::getInstance();

$app->getController()
$app->getRequest()
$app->getResponse()
$app->getRouter()
$app->getLogger()
$app->getCache()
$app->getDatabaseManager()
$app->getDatabaseModelManager()
$app->getPluginManager()
$app->getI18n()
$app->getMailer()
$app->getStorage()
```

---

## 4. Configuration System

### Resolution order

1. Framework defaults
2. `config/default/applications/{App}/*.yml`
3. `config/default/plugins/{Plugin}/*.yml`
4. Environment overlay (`dev:`, `prod:` keys in the same files)

### Accessing values

```php
$config = $app->getConfiguration();

// Dot-path access
$module  = $config['routing.default_module'];
$email   = $config['settings.admin_email'];
$driver  = $config['db.databases.primary.driver'];

$env = $config->getEnvironment();  // 'dev', 'prod', 'test'
```

### Environment variable injection

Any YAML value can reference a `.env` variable:

```yaml
admin_email: env(ADMIN_EMAIL)
base_url: env(FRONTEND_BASE_URL)
cache_version: env(COMMON_VIEW_CACHE_VERSION)
```

`symfony/dotenv` loads `.env` (and `.env.secure` if present) automatically during bootstrap.

### Per-environment overrides

```yaml
all:
  db:
    databases:
      primary:
        url: mysql:host=localhost;dbname=dev_db

prod:
  db:
    databases:
      primary:
        url: mysql:host=db.internal;dbname=prod_db
        user: env(DB_USER)
        password: env(DB_PASS)
```

### FQCN loader bindings

Loaders in `loaders.yml` must use fully qualified class names with a leading `\`:

```yaml
loaders:
  request:  \Acme\Extensions\Request\AppWebRequest
  response: \Acme\Extensions\Response\AppWebResponse
  cache:    \Acme\Plugins\Cache\Lib\AdvCacheStore
  user:     \Acme\Extensions\User\SiteUser
  # Framework built-ins work without namespace:
  controller: \lcFrontWebController
  router:     \lcPatternRouting
```

---

## 5. Controllers

### Naming conventions

| Thing | Pattern | Example |
|-------|---------|---------|
| Module directory | PascalCase | `Modules/Home/` |
| Controller file | `{Module}.php` | `Home.php` |
| Controller class | `{Module}` (no prefix) | `Home` |
| Namespace | `{Ns}\Applications\{App}\Modules\{Module}` | — |
| Action method | `action{Name}(): array\|void` | `actionIndex()` |

```php
<?php
declare(strict_types=1);

namespace Acme\Applications\Frontend\Modules\Home;

use Acme\Extensions\Controllers\CommonWebController;

class Home extends CommonWebController
{
    protected $use_plugins = ['Dc', 'Vfs'];

    public function actionIndex(): array
    {
        return [
            'items' => $this->getItems(),
        ];
    }

    public function actionView(): array
    {
        $id = (int)$this->getRequest()->getParam('id');
        $item = ItemPeer::retrieveByPk($id);

        if (!$item) {
            throw new \lcNotAvailableException('Item not found');
        }

        return ['item' => $item];
    }
}
```

The template is resolved from the action name automatically:  
`actionIndex()` → `templates/index.htm`  
`actionProductsList()` → `templates/product_list.htm`  
(camelCase stripped of `action` prefix, converted to snake_case, `.htm` appended)

### Declaring plugin dependencies

```php
protected $use_plugins = [
    'Dc',
    'Languages',
    'AjaxRpc',
    'Bootstrap',
];
```

### Lifecycle hooks

```php
protected function beforeExecute(): void
{
    parent::beforeExecute();

    // Runs before any action — ideal for auth checks, plugin setup
    /** @var Dc $dc */
    $dc = $this->getPlugin('Dc');
    $this->dc = $dc;
}

protected function afterExecute(): void
{
    parent::afterExecute();
    // Runs after the action
}
```

### Useful controller methods

```php
// Services
$this->getApp()
$this->getRequest()          // typed to your custom request class in CommonWebController
$this->getResponse()
$this->getRouter()
$this->getLogger()
$this->getCache()
$this->getEventDispatcher()
$this->getConfiguration()
$this->getUser()
$this->getPlugin('PluginName')

// View
$this->view['key'] = $value;        // set template variable
$this->render()                      // return rendered view explicitly
$this->render('other_name')          // render a different template

// Layout
$this->view->setHasLayout(false)
$this->view->setDecorator('admin')   // uses layouts/admin.htm

// Page meta
$this->setTitle('Page Title')
$this->setDescription('Meta')
$this->setKeywords('kw1, kw2')

// Redirect
$this->redirect('/path')

// Return values
return [];                           // empty data, render template
return ['key' => $val];             // data passed to template
return \lcController::RENDER_NONE;  // no output (after redirect etc.)
return $this->forward('module', 'action');
```

### Request validators (constants on lcController)

```php
// Validate request type before executing business logic
const VPOST  = 'is_post'
const VGET   = 'is_get'
const VPUT   = 'is_put'
const VDELETE = 'is_delete'
const VAJAX  = 'is_ajax'
const VAUTH  = 'is_authenticated'
const VNAUTH = 'not_authenticated'
```

### CommonWebController pattern

Projects typically create a shared base controller in `src/Extensions/Controllers/`:

```php
<?php
declare(strict_types=1);

namespace Acme\Extensions\Controllers;

use Acme\Extensions\Request\AppWebRequest;
use Acme\Extensions\Response\AppWebResponse;
use Acme\Extensions\User\SiteUser;
use lcWebController;

class CommonWebController extends lcWebController
{
    /** @return AppWebRequest */
    public function getRequest(): ?\lcRequest
    {
        return parent::getRequest();
    }

    /** @return AppWebResponse */
    public function getResponse(): ?\lcResponse
    {
        return parent::getResponse();
    }

    /** @return SiteUser */
    public function getUser(): ?\lcUser
    {
        return parent::getUser();
    }

    protected function beforeExecute(): void
    {
        parent::beforeExecute();
        // shared setup — currency, locale, etc.
    }
}
```

All module controllers extend `CommonWebController`, not `lcWebController` directly.

---

## 6. Views & Templates

### Template resolution

```
src/Applications/{App}/Modules/{Module}/templates/{action_name}.htm
```

Where `{action_name}` is the method name without the `action` prefix, converted to snake_case:

| Method | Template |
|--------|----------|
| `actionIndex()` | `templates/index.htm` |
| `actionProductsList()` | `templates/products_list.htm` |
| `actionViewOldItem()` | `templates/view_old_item.htm` |

### Template syntax

```html
<!-- HTML-escaped variable -->
{$title}

<!-- Raw / unescaped output -->
{$html_content:asis}

<!-- Config / view-level variable -->
{$view.res_url_webpath:config}

<!-- i18n translation -->
{t}Save{/t}
{t name="$username"}Hello, {name}!{/t}

<!-- Conditional block -->
<!-- BEGIN has_items -->
{block}has_items{/block}
<ul>...</ul>
<!-- END has_items -->

<!-- Include a partial template from another module action -->
<!-- PARTIAL home/footer -->
<!-- PARTIAL banner/banner -->
```

### Passing data to templates

Return an array from the action — each key becomes a template variable:

```php
public function actionIndex(): array
{
    return [
        'title'   => 'My Page',
        'items'   => $items,
        'is_auth' => $this->getUser()->isAuthenticated(),
    ];
}
```

Or assign directly to the view:

```php
$this->view['title'] = 'My Page';
```

### Layouts (decorators)

Layouts live at `src/Applications/{App}/layouts/{name}.htm`.

```html
<!-- layouts/index.htm -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{$page_title}</title>
  <script src="{$view.res_url_webpath:config}/js/app.js"></script>
</head>
<body>
<!-- PARTIAL home/header -->
<main>
  [PAGE_CONTENT]
</main>
<!-- PARTIAL home/footer -->
</body>
</html>
```

`[PAGE_CONTENT]` is replaced with the rendered action template.

Active layout is configured in `view.yml`:

```yaml
view:
  has_layout: true
  decorator: index     # → layouts/index.htm
  extension: htm
```

Override per action:

```php
$this->view->setDecorator('minimal');   // layouts/minimal.htm
$this->view->setHasLayout(false);       // no layout
```

Multiple layouts per application are common — `index.htm`, `admin.htm`, `err.htm`, etc.

### View filters

Configured in `view.yml`:

```yaml
view:
  filters:
    - \Acme\Plugins\Languages\Lib\Filters\I18nViewFilter
    - \lcHTMLTemplateViewFilter
```

Filters post-process the rendered HTML before it is sent. Order matters: i18n translation runs before template variable substitution.

### Partial methods

A controller can expose named fragments that are included from layouts via `<!-- PARTIAL -->`:

```php
// In Home.php
public function partialHeader(): array
{
    return ['nav' => $this->getNavItems()];
    // Template: templates/header.htm
}

public function partialFooter(): array
{
    return [];
    // Template: templates/footer.htm
}
```

```html
<!-- In layout: -->
<!-- PARTIAL home/header -->
[PAGE_CONTENT]
<!-- PARTIAL home/footer -->
```

---

## 7. Routing

### Pattern routing

Routes in `config/default/applications/{App}/routing.yml`:

```yaml
all:
  routing:
    send_http_errors: true
    default_module: home
    default_action: index
    routes:
      # Named, specific routes come first
      product_view:
        url: /catalog/product/:model_id
        params:
          module: product
          action: view
        requirements:
          model_id: "[0-9]+"

      catalog_list:
        url: /catalog/:nav_type/:nav_name
        params:
          module: catalog
          action: productslist

      # Generic fallbacks last
      default_id:
        url: /:module/:action/:id
      default:
        url: /:module/:action
      module_default:
        url: /:module
        params:
          action: index
      homepage:
        url: /
        params:
          module: home
          action: index
```

Routes match top-to-bottom; first match wins. Module names are case-insensitive; the framework resolves `product` to the `Product` module class.

### Accessing route parameters

```php
$id       = $this->getRequest()->getParam('model_id');
$nav_type = $this->getRequest()->getParam('nav_type');
$module   = $this->getRequest()->getParam('module');
```

---

## 8. Request & Response

### Custom request/response (recommended pattern)

Projects subclass `lcWebRequest` and `lcWebResponse` in `src/Extensions/` to add project-specific helpers, then bind them in `loaders.yml`.

```php
// src/Extensions/Request/AppWebRequest.php
namespace Acme\Extensions\Request;

use lcWebRequest;

class AppWebRequest extends lcWebRequest
{
    public function getClientIp(): string
    {
        return $this->getRemoteAddr();
    }
}
```

### `lcWebRequest` API

```php
$req = $this->getRequest();

$req->getMethod()              // 'GET', 'POST', 'PUT', 'DELETE'
$req->isGet()
$req->isPost()
$req->isAjax()

$req->getParam('key')          // route / query param
$req->getParams()
$req->getPostParam('key')
$req->getPostParams()
$req->getPutParams()
$req->getDeleteParams()

$req->getHeader('Accept')
$req->getRemoteAddr()
$req->getUri()
$req->getHost()
$req->isSecure()
$req->getRawPostData()
$req->getUploadedFile('field')
```

### `lcWebResponse` API

```php
$res = $this->getResponse();

$res->setContent($html)
$res->addContent($html)
$res->setHttpStatusCode(404)
$res->setHeader('X-Custom', 'value')
$res->setCookie('name', 'value', time() + 3600)
$res->sendResponse()
```

---

## 9. Extensions Layer

The `src/Extensions/` directory holds shared logic used across multiple applications and modules. It is **not** a plugin — it has no `Configuration` class and is not registered anywhere. It is simply Composer-autoloaded code.

Common contents:

```
src/Extensions/
├── Controllers/
│   ├── CommonWebController.php    # Shared base for all modules
│   └── Admin/AdminWebController.php
├── Request/
│   └── AppWebRequest.php
├── Response/
│   └── AppWebResponse.php
├── User/
│   └── SiteUser.php               # Extended user class
├── Mail/
│   └── DBMailer.php
├── Utils/
│   ├── Strings.php
│   ├── DataValidator.php
│   └── HtmlUtils.php
├── Navigation/
│   └── NavigationTool.php
└── ...
```

---

## 10. Database & Models (Propel)

### Model location

- Plugin models: `src/Plugins/{Plugin}/Models/`
- Shared generated models: `Gen/Propel/Models/`

Plugin-owned models are declared in the plugin's `Configuration` class (see [Plugin System](#12-plugin-system)).

### Querying with Propel

```php
use Acme\Plugins\Dc\Models\ProductPeer;
use Acme\Plugins\Dc\Models\ProductQuery;
use Criteria;

// Single record by PK
$product = ProductPeer::retrieveByPk($id);

// Fluent query builder
$products = ProductQuery::create()
    ->filterByIsEnabled(true)
    ->orderByCreatedAt(Criteria::DESC)
    ->limit(20)
    ->find();

// Count
$count = ProductQuery::create()->filterByIsEnabled(true)->count();
```

### Creating and updating

```php
use Acme\Plugins\Dc\Models\Product;

$p = new Product();
$p->setTitle('New Product');
$p->setIsEnabled(true);
$p->save();

// insertOrUpdate — uses PK to decide INSERT vs UPDATE
$p = new Product();
$p->setId(5);
$p->setTitle('Upsert');
$p->insertOrUpdate();
```

### Raw PDO access

```php
$pdo = $this->getApp()->getDatabaseManager()->getDatabase('primary')->getConnection();
$stmt = $pdo->prepare('SELECT * FROM product WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
```

### Migrations

```bash
./shell/cmd propel diff     # generate migration from schema diff
./shell/cmd propel migrate  # apply pending migrations
```

---

## 11. Forms & Validation

### Basic action form

```php
<?php
declare(strict_types=1);

namespace Acme\Applications\Frontend\Forms;

use lcBaseActionForm;

class ContactForm extends lcBaseActionForm
{
    public function getName(): string
    {
        return 'contact_form';
    }

    public function validate(): bool
    {
        if (empty($this->email)) {
            $this->addValidationFailure('email', 'Email is required');
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->addValidationFailure('email', 'Invalid email');
        }

        return !$this->hasValidationFailures();
    }

    public function execute(): \lcActionFormExecuteResponse
    {
        // Save, send email, etc.
        return new \lcActionFormExecuteSubmitResponse();
    }
}
```

### Using a form in a controller

```php
public function actionCreate(): array
{
    $form = new \Acme\Applications\Frontend\Forms\ContactForm();
    $form->setController($this);

    if ($this->getRequest()->isPost()) {
        $form->bindData($this->getRequest()->getPostParams());

        if ($form->validate()) {
            $result = $form->execute();

            if ($result->isSuccess()) {
                $this->redirect('/contact/thanks');
                return \lcController::RENDER_NONE;
            }
        }
    }

    return [
        'form'   => $form,
        'errors' => $form->getValidationFailures(),
    ];
}
```

---

## 12. Plugin System

### Plugin class

```php
<?php
declare(strict_types=1);

namespace Acme\Plugins\MyPlugin;

use lcApp;
use lcConfiguration;
use lcEvent;
use lcEventDispatcher;
use lcPlugin;

class MyPlugin extends lcPlugin
{
    public function execute(lcEventDispatcher $dispatcher, lcConfiguration $config): void
    {
        // Register event listeners
        $dispatcher->connect('user.created', $this, 'onUserCreated');
    }

    public function initializeApp(lcApp $context): void
    {
        parent::initializeApp($context);
        // Post-initialization
    }

    public function initializeWebComponents(): void
    {
        // Register web-only resources
    }

    public function onUserCreated(lcEvent $event): void
    {
        $user = $event->getSubject();
        $this->getLogger()->info('New user: ' . $user->getName());
    }
}
```

### Plugin `Configuration` class

This replaces the old `plugin.php` meta file. It declares all plugin capabilities via interfaces:

```php
<?php
declare(strict_types=1);

namespace Acme\Plugins\MyPlugin\Config;

use iConsoleTaskProvider;
use iPluginRequirements;
use iSupportsDbModels;
use lcPluginConfiguration;

class Configuration extends lcPluginConfiguration
    implements iSupportsDbModels, iConsoleTaskProvider, iPluginRequirements
{
    /**
     * Unique identifier for this plugin
     */
    public function getIdentifier(): string
    {
        return 'a1b2c3d4-1234-5678-abcd-ef0123456789';
    }

    /**
     * Locales this plugin provides translations for
     */
    public function getSupportedLocales(): array
    {
        return ['en_US', 'de_DE'];
    }

    /**
     * iSupportsDbModels — Propel model class names managed by this plugin
     */
    public function getDbModels(): array
    {
        return ['Product', 'Category'];
    }

    /**
     * iConsoleTaskProvider — Task class names provided by this plugin
     */
    public function getControllerTasks(): array
    {
        return ['Sync', 'Cleanup'];
    }

    /**
     * iPluginRequirements — Other plugins that must be loaded first
     */
    public function getRequiredPlugins(): array
    {
        return ['Cache', 'Languages'];
    }
}
```

### Plugin directory layout

```
src/Plugins/MyPlugin/
  MyPlugin.php              # Plugin class: MyPlugin extends lcPlugin
  Config/
    Configuration.php       # Capabilities declaration
  Models/
    Product.php             # Propel model (extends BasePropelObject)
    ProductPeer.php
    ProductQuery.php
  Tasks/
    Sync.php                # Task class (no prefix)
    Cleanup.php
  Forms/
    ProductForm/
      ProductForm.php
  Modules/                  # If plugin provides web modules
    Widget/
      Widget.php
      templates/
        index.htm
  locale/                   # Gettext .mo/.po files
    en_US/
    de_DE/
  web/                      # Static assets
```

### Enabling a plugin

`config/default/applications/Frontend/plugins.yml` — plugin names are PascalCase:

```yaml
plugins:
  enabled:
    - Cache
    - Languages
    - MyPlugin
```

### Plugin locations in project config

```php
public function getPluginPaths(): array
{
    return [
        ROOT . '/src/Plugins',
    ];
}
```

### Accessing a plugin from a controller

```php
// Declare dependency (plugin is auto-initialized)
protected $use_plugins = ['MyPlugin'];

// Access the instance
/** @var MyPlugin $my */
$my = $this->getPlugin('MyPlugin');
```

---

## 13. Events & Observer Pattern

### Dispatching events

```php
$dispatcher = $this->getEventDispatcher();

// Fire and forget
$dispatcher->notify(new \lcEvent('user.created', $user));

// Filter (listeners may transform the value)
$result = $dispatcher->filter(new \lcEvent('content.render', $html), $html);
$html   = $result->getReturnValue();

// Service provision (first responder wins)
$service = $dispatcher->provide('cache.backend', $this);
```

### Subscribing

```php
// In plugin execute():
$dispatcher->connect('user.created', $this, 'handleUserCreated');

public function handleUserCreated(\lcEvent $event): void
{
    $user = $event->getSubject();
}
```

### Application-level hooks

```php
// In {App}\Config\Configuration::executeBefore():
$this->event_dispatcher->connect('request.startup', $this, 'onRequestStartup');
$this->event_dispatcher->connect('response.startup', $this, 'onResponseStartup');
```

### Standard framework events

| Event | When |
|-------|------|
| `app.startup` | App fully initialized |
| `app.shutdown` | App shutting down |
| `request.startup` | Request begins |
| `response.startup` | Response begins |
| `request.filter_parameters` | Route params resolved |
| `controller.change_action` | Action about to execute |
| `view.filter_parameters` | View about to render |

---

## 14. Caching

### Cache API

```php
$cache = $this->getCache();

$cache->set('key', $value);
$cache->set('key', $value, 600);   // TTL in seconds
$value = $cache->get('key');       // null if missing
$exists = $cache->has('key');
$cache->delete('key');
$cache->flush();
```

### Backends

Bind via `loaders.yml`:

```yaml
loaders:
  cache: \Acme\Plugins\Cache\Lib\AdvCacheStore
```

Common cache store classes (project-provided wrappers or framework built-ins):

| Class | Backend |
|-------|---------|
| `lcFileCache` | File system |
| `lcRedis` | Redis |
| `lcMemcache` / `lcMemcached` | Memcache |
| `lcAPC` | APCu |

---

## 15. Logging

### Log levels

```php
$logger = $this->getLogger();

$logger->debug('...');
$logger->info('...');
$logger->warning('...');
$logger->error('...');
$logger->critical('...');
```

### Configuring log files in `settings.yml`

```yaml
logger:
  enabled: true
  log_files:
    app.log: info
    app.error.log: err
  email_to: env(ADMIN_EMAIL)
  email_threshold: crit
```

---

## 16. Internationalization (i18n)

### Translation in code

```php
// In controllers, plugins, extensions:
$text = $this->t('key');
$text = $this->t('greeting', ['name' => $name]);
```

### Translation in templates

```html
{t}Save{/t}
{t name="$username"}Hello, {name}!{/t}
```

### Gettext backend

Translation files live at:
- `src/Applications/{App}/locale/{locale}/LC_MESSAGES/`
- `src/Plugins/{Plugin}/locale/{locale}/LC_MESSAGES/`

Compiled `.mo` files are generated from `.po` sources via Phing or a dedicated task.

Plugin `Configuration` must declare supported locales:

```php
public function getSupportedLocales(): array
{
    return ['en_US', 'de_DE', 'fr_FR'];
}
```

### Bound i18n loader

```yaml
loaders:
  i18n: \Acme\Plugins\Languages\Lib\DbLanguageSystem
```

---

## 17. User & Authentication

### User object

Bound via `loaders.yml` → `user: \Acme\Extensions\User\SiteUser`

```php
$user = $this->getUser();

$user->isAuthenticated()
$user->authenticate($username, $password)
$user->logout()
$user->getAttribute('key')
$user->setAttribute('key', 'value')
$user->hasCredential('admin')
$user->addCredential('editor')
```

### Protecting actions (in `beforeExecute`)

```php
protected function beforeExecute(): void
{
    parent::beforeExecute();

    if (!$this->getUser()->isAuthenticated()) {
        $this->redirect($this->getConfiguration()['security.login_action_url']);
        return;
    }
}
```

---

## 18. Console Tasks

### Naming conventions

| Thing | Pattern | Example |
|-------|---------|---------|
| Task file | `{Name}.php` | `Sys.php` |
| Class | `{Name}` (no prefix) | `Sys` |
| Namespace | `{Ns}\Tasks` or `{Ns}\Plugins\{Plugin}\Tasks` | — |
| Entry method | `executeTask(): bool` | — |
| Sub-actions | `action{Name}(): bool` | `actionRebuildIndex()` |

```php
<?php
declare(strict_types=1);

namespace Acme\Tasks;

use lcInflector;
use lcTaskController;

class Sys extends lcTaskController
{
    public function executeTask(): bool
    {
        $action = $this->getRequest()->getParam('action');

        if ($action) {
            // Dispatch to action{CamelCaseName}()
            $method = 'action' . lcInflector::camelize($action);
            return $this->$method();
        }

        $this->displayHelp();
        return true;
    }

    public function actionRebuildIndex(): bool
    {
        $this->consoleDisplay('Rebuilding index...');
        // ... work ...
        $this->consoleDisplay('Done.');
        return true;
    }

    public function getHelpInfo(): string
    {
        return "Available actions: rebuild_index, ...";
    }
}
```

### Running a task

```bash
./shell/cmd sys --action=rebuild_index
./shell/cmd sys --action=rebuild_index --limit=100
```

The task name maps from the class name: `Sys` → `sys`, `UpdateRates` → `update_rates`.

### Plugin tasks

Plugin tasks are declared in the plugin's `Configuration` class:

```php
public function getControllerTasks(): array
{
    return ['Sync', 'Cleanup'];
}
```

And run as:

```bash
./shell/cmd sync --action=full
```

---

## 19. Web Services

Use `lcWebServiceConfiguration` for JSON/REST API applications.

`webroot/api.php`:

```php
<?php
use Acme\Applications\Api\Config\Configuration;

$base_dir = realpath(dirname(__FILE__) . '/../');
require_once $base_dir . '/lib/boot.php';
require_once $base_dir . '/src/Applications/Api/Config/Configuration.php';

$configuration = new Configuration($base_dir, new \Acme\Config\Configuration());
lcApp::bootstrap($configuration)->dispatch();
```

`src/Applications/Api/Config/Configuration.php`:

```php
namespace Acme\Applications\Api\Config;
use lcWebServiceConfiguration;

class Configuration extends lcWebServiceConfiguration
{
    public function getApplicationName(): string { return 'Api'; }
}
```

Web service controller:

```php
namespace Acme\Applications\Api\Modules\Products;
use lcWebServiceController;

class Products extends lcWebServiceController
{
    public function actionList(): void
    {
        $products = ProductQuery::create()->find();
        $this->sendJson(['products' => $products->toArray()]);
    }

    public function actionView(): void
    {
        $id      = (int)$this->getRequest()->getParam('id');
        $product = ProductQuery::create()->findPk($id);

        if (!$product) {
            $this->sendError(404, 'Not found');
            return;
        }

        $this->sendJson(['product' => $product->toArray()]);
    }
}
```

Routes in `config/default/applications/Api/routing.yml` (same format as web routing).

---

## 20. Utility Classes

These are framework kernel classes (no namespace, `lc` prefix), always available:

```php
// Strings
lcStrings::slugify('Hello World!')         // 'hello-world'
lcStrings::camelize('my_method')           // 'MyMethod'
lcStrings::startsWith($str, 'prefix')
lcStrings::endsWith($str, 'suffix')

// Inflector
lcInflector::camelize('rebuild_index')     // 'RebuildIndex'
lcInflector::underscore('RebuildIndex')    // 'rebuild_index'
lcInflector::pluralize('product')          // 'products'

// Arrays
lcArrays::mergeRecursiveDistinct($base, $override)
lcArrays::flatten($nested)

// Files & Dirs
lcFiles::copy($src, $dest)
lcFiles::read($path)
lcDirs::mkdir($path)
lcDirs::remove($path)

// Date/Time
UtcDateTime::now()
UtcDateTime::fromString('2025-01-01')

// System
lcSys::getPhpVersion()
lcSys::getMemoryUsage()
```

---

## 21. Error Handling & Exceptions

### Exception hierarchy (kernel, no namespace)

```
lcException
  ├── lcSystemException
  ├── lcConfigException
  ├── lcIOException
  ├── lcAuthException
  ├── lcAccessDeniedException      # 403
  ├── lcInvalidArgumentException
  ├── lcAssertException
  └── lcNotAvailableException      # 404
```

### Throwing exceptions

```php
use lcNotAvailableException;
use lcAccessDeniedException;

if (!$product) {
    throw new lcNotAvailableException('Product not found');
}

if (!$this->getUser()->hasCredential('admin')) {
    throw new lcAccessDeniedException();
}
```

### Custom error handling in `settings.yml`

```yaml
exceptions:
  module: home
  action: error
  mail:
    enabled: true
    recipient: env(ADMIN_EMAIL)
    skip_exceptions:
      - lcNotAvailableException
      - lcAccessDeniedException
```

---

## 22. Autoloading

### Two autoloaders

1. **Composer PSR-4** — loads all `src/` application code. Configured in `composer.json`:

```json
{
  "autoload": {
    "psr-4": {
      "Acme\\": "src/"
    }
  }
}
```

Run `composer dump-autoload` after adding new classes.

2. **lcClassAutoloader** — loads framework kernel classes (`source/libs/`) using a pre-built cache at `source/assets/misc/autoload/autoload.php`.

### Rebuilding the framework autoload cache

```bash
./shell/cmd build_autoload
```

Run this after adding new framework-level classes.

### In debug mode (`DO_DEBUG = true`)

The framework autoload cache is bypassed; classes are discovered on-the-fly. Composer autoloading works normally regardless of debug mode.

---

## Conventions Summary

| Thing | Pattern | Example |
|-------|---------|---------|
| All files | `declare(strict_types=1)` | — |
| Project config | `{Ns}\Config\Configuration extends lcProjectConfiguration` | `Acme\Config\Configuration` |
| App config | `{Ns}\Applications\{App}\Config\Configuration extends lcWebConfiguration` | — |
| Plugin config | `{Ns}\Plugins\{Plugin}\Config\Configuration extends lcPluginConfiguration` | — |
| Controller file | `{Module}.php` | `Home.php` |
| Controller class | `{Module}` (PascalCase, no prefix) | `Home` |
| Action method | `action{Name}(): array\|void` | `actionIndex()` |
| Template file | `templates/{action_snake_case}.htm` | `templates/product_list.htm` |
| Layout file | `layouts/{name}.htm` | `layouts/index.htm` |
| Layout placeholder | `[PAGE_CONTENT]` | — |
| Partial include | `<!-- PARTIAL module/method -->` | `<!-- PARTIAL home/footer -->` |
| Plugin class | `{Plugin} extends lcPlugin` | `Vfs extends lcPlugin` |
| Plugin config | `Configuration extends lcPluginConfiguration` + interfaces | — |
| Task class | `{Name} extends lcTaskController` | `Sys extends lcTaskController` |
| Task entry | `executeTask(): bool` dispatches to `action{Name}()` | — |
| Loaders in YAML | FQCN with leading `\` | `\Acme\Extensions\Request\AppWebRequest` |
| Plugin names in YAML | PascalCase | `Languages`, `MyPlugin` |
| Extensions | `src/Extensions/` — no plugin registration needed | — |
