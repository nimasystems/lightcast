# Lightcast — Developer Reference

Detailed patterns and conventions for building applications on Lightcast.

---

## Table of Contents

1. [Object Hierarchy](#1-object-hierarchy)
2. [Application Bootstrap & Lifecycle](#2-application-bootstrap--lifecycle)
3. [Configuration System](#3-configuration-system)
4. [Controllers](#4-controllers)
5. [Views & Templates](#5-views--templates)
6. [Routing](#6-routing)
7. [Request & Response](#7-request--response)
8. [Database & Models (Propel)](#8-database--models-propel)
9. [Forms & Validation](#9-forms--validation)
10. [Components](#10-components)
11. [Plugin System](#11-plugin-system)
12. [Events & Observer Pattern](#12-events--observer-pattern)
13. [Caching](#13-caching)
14. [Logging](#14-logging)
15. [Internationalization (i18n)](#15-internationalization-i18n)
16. [User & Authentication](#16-user--authentication)
17. [Console Tasks](#17-console-tasks)
18. [Web Services](#18-web-services)
19. [Utility Classes](#19-utility-classes)
20. [Error Handling & Exceptions](#20-error-handling--exceptions)
21. [Autoloading](#21-autoloading)

---

## 1. Object Hierarchy

All framework classes descend from a single hierarchy:

```
lcObj
  └── lcDataObj           # Adds typed property access
       └── lcSysObj       # Adds logger, i18n, events, configuration access
            └── lcResidentObj   # Framework "resident" — has lcApp reference
                 └── lcAppObj   # Application-level objects
                      └── lcPlugin   # Pluggable components
```

When extending framework classes, know which level you need:

- Extend `lcObj` for pure utility classes with no framework dependencies.
- Extend `lcSysObj` when you need logging or event access but no app context.
- Extend `lcAppObj` for classes that need `$this->getApp()`.
- Extend `lcPlugin` for self-contained feature bundles.

---

## 2. Application Bootstrap & Lifecycle

### Bootstrap sequence

```php
// 1. Load bootstrap (sets up include paths, defines constants, loads base classes)
require_once '../lib/boot.php';

// 2. Load per-application config class
require_once '../applications/frontend/config/lcFrontendConfiguration.class.php';

// 3. Create configuration
$configuration = new lcFrontendConfiguration(
    realpath(dirname(__FILE__) . '/../'),
    new ProjectConfiguration()
);

// 4. Bootstrap and dispatch
lcApp::bootstrap($configuration)->dispatch();
```

### Application configuration class

Each application (frontend, backend) has its own class that extends `lcWebConfiguration`:

```php
class lcFrontendConfiguration extends lcWebConfiguration
{
    public function getApplicationName(): string
    {
        return 'frontend';
    }

    public function executeBefore()
    {
        parent::executeBefore();

        // Register app-level event hooks here
        $this->event_dispatcher->connect('request.startup', $this, 'onRequestStartup');
        $this->event_dispatcher->connect('response.startup', $this, 'onResponseStartup');
    }

    public function onRequestStartup(lcEvent $event)
    {
        // Called on every request before routing
    }
}
```

### What `lcApp::bootstrap()` does internally

1. Creates the singleton `lcApp` instance
2. Instantiates `lcEventDispatcher`
3. Loads all YAML configuration files
4. Creates and initializes system objects in order:
   - Error handler, Logger, Class autoloader
   - All loader services (request, response, router, cache, i18n, mailer, storage, user)
   - Database manager
   - Plugin manager
5. Fires `app.bootstrap` event

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

## 3. Configuration System

### Config file locations

Configuration is resolved in layers:
1. Framework defaults
2. `config/default/*.yml` and `config/default/applications/{app}/*.yml`
3. Environment-specific overlay (`dev:`, `prod:`, `test:` keys within the same files)

### Standard config files

| File | What it configures |
|------|--------------------|
| `project.yml` | Timezone, cache, exception handling, plugin locations |
| `databases.yml` | Database connections |
| `loaders.yml` | Service class bindings |
| `routing.yml` | URL routes |
| `plugins.yml` | Enabled plugins |
| `view.yml` | View/layout settings |
| `security.yml` | Auth configuration |
| `settings.yml` | Free-form app settings |

### Accessing config values

```php
$config = $app->getConfiguration();

// Dot-path access
$module  = $config['routing.default_module'];
$driver  = $config['db.databases.primary.driver'];
$timeout = $config['settings.session_timeout'];

// Environment
$env = $config->getEnvironment();  // 'dev', 'prod', 'test'
```

### Environment overrides

```yaml
# databases.yml
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

### .env variable injection

Any YAML value can reference an environment variable:

```yaml
admin_email: env(ADMIN_EMAIL)
```

### Constants defined after bootstrap

```
ROOT          — App root directory (the dir passed to the configuration constructor)
DS            — DIRECTORY_SEPARATOR
LIGHTCAST_VER — Framework version string (e.g. '1.5.3')
LC_VER        — Alias for LIGHTCAST_VER
DO_DEBUG      — Defined by the entry point; controls error reporting
```

---

## 4. Controllers

### Naming conventions

| Thing | Pattern | Example |
|-------|---------|---------|
| Module directory | lowercase | `modules/home/` |
| Controller file | `{module}.php` | `home.php` |
| Controller class | `c{ModuleName}` | `cHome` |
| Action method | `action{Name}()` | `actionIndex()` |
| Partial method | `partial{Name}()` | `partialSiteHeader()` |

### Minimal controller

```php
// app/applications/frontend/modules/home/home.php
class cHome extends lcWebController
{
    public function actionIndex()
    {
        $this->view['greeting'] = 'Hello!';
        return $this->render();
    }
}
```

The template file is resolved automatically: `modules/home/templates/index.htm`  
(the name is the action name without the `action` prefix, lowercased and underscored).

### Declaring plugin and component dependencies

```php
class cProjects extends lcWebController
{
    protected $use_plugins = [
        'core',
        'ispdd',
        'breadcrumbs',
        'email_notifications',
    ];

    protected $use_components = [
        'projects_bar',
    ];

    public function beforeExecute()
    {
        parent::beforeExecute();

        // Assign plugin instances to convenient properties
        $this->core  = $this->getPlugin('core');
        $this->ispdd = $this->getPlugin('ispdd');
    }
}
```

### Useful controller methods

```php
// Services
$this->getApp()
$this->getRequest()
$this->getResponse()
$this->getRouter()
$this->getLogger()
$this->getCache()
$this->getEventDispatcher()
$this->getConfiguration()
$this->getPlugin('plugin_name')

// View data
$this->view['key'] = $value;          // Set template variable
$this->render()                        // Return rendered view
$this->render('other_template')        // Render a different template name

// Layout control
$this->view->setHasLayout(false)       // Disable layout for this action
$this->view->setDecorator('admin')     // Use a different layout

// Page meta
$this->setTitle('Page Title')
$this->setDescription('Meta description')
$this->setKeywords('kw1, kw2')

// Assets
$this->includeJavascript('scripts/app.js')
$this->includeStylesheet('styles/page.css')

// Redirect
$this->redirect('/other/page')

// Paths
$this->getWebPath()       // Web path to current module
$this->getMyActionPath()  // Web path to current action
```

### Lifecycle hooks

```php
public function beforeExecute()
{
    parent::beforeExecute();
    // Runs before any action — ideal for auth checks, plugin setup
}

public function afterExecute()
{
    parent::afterExecute();
    // Runs after the action — modify response if needed
}
```

### Returning from an action

```php
// Render the action's template
return $this->render();

// Render no output (e.g. redirect happened)
return lcController::RENDER_NONE;

// Forward to another module/action
return $this->forward('module', 'action');
```

### Admin model controller

For CRUD admin modules, extend `lcAdminModelWebController`:

```php
class cProjectsManage extends lcAdminModelWebController
{
    protected function getModelMainTableName(): string
    {
        return 'project';
    }

    protected function getModelDataTableName(): string
    {
        return 'project_data';
    }

    protected function getModelPrimaryKey(): string
    {
        return 'project_id';
    }

    protected function getModelFormName($operation): string
    {
        return 'project_update';
    }
}
```

This base class auto-generates list/create/update/delete actions tied to the form.

### Direct actions

Register actions that bypass the normal routing filter:

```php
public function getModuleDirectActions(): array
{
    return [
        ['action' => 'generate_image'],
        ['action' => 'download_pdf'],
    ];
}
```

---

## 5. Views & Templates

### Template location

```
app/applications/{app}/modules/{module}/templates/{action_name}.htm
```

Where `{action_name}` is the method name without the `action` prefix, lowercased with underscores:

| Method | Template |
|--------|----------|
| `actionIndex()` | `templates/index.htm` |
| `actionCatListing()` | `templates/cat_listing.htm` |
| `actionViewOldProjectTemplate()` | `templates/view_old_project_template.htm` |

### Template syntax

Lightcast templates use a custom tag syntax, not raw PHP:

```html
<!-- Variable output (HTML-escaped) -->
{$variable}

<!-- Raw/unescaped output -->
{$variable:asis}

<!-- i18n translation -->
{t}Save{/t}
{t name="$username"}Hello, {name}!{/t}

<!-- Conditional blocks -->
<!-- BEGIN has_items -->
{block}has_items{/block}
  <ul>
    <!-- content -->
  </ul>
<!-- END has_items -->

<!-- Include a partial from another module -->
<!-- PARTIAL partials/site_header -->
<!-- PARTIAL partials/language_bar -->
```

### Setting template variables

From the controller:

```php
$this->view['title']   = 'My Page';
$this->view['items']   = $items_array;
$this->view['user']    = $user_object;
```

### Layouts (decorators)

The default layout is `app/applications/{app}/layouts/index.htm`.

```html
<!-- layouts/index.htm -->
<!DOCTYPE html>
<html>
<head>
  <title>{$page_title}</title>
</head>
<body>
<!-- PARTIAL partials/site_header -->
<main>
  [PAGE_CONTENT]
</main>
<!-- PARTIAL partials/site_footer -->
</body>
</html>
```

`[PAGE_CONTENT]` is replaced with the rendered action template.

The active layout is set in `view.yml`:

```yaml
view:
  has_layout: true
  decorator: index     # references layouts/index.htm
  extension: htm
```

Override per-action:

```php
$this->view->setDecorator('admin');   // uses layouts/admin.htm
$this->view->setHasLayout(false);     // no layout at all
```

### Partial methods

A controller can expose named fragments callable from layouts via `<!-- PARTIAL -->`:

```php
class cPartials extends lcWebController
{
    public function partialSiteHeader()
    {
        $this->view['nav_items'] = $this->getNavItems();
        // Template: templates/site_header.htm
    }

    public function partialSiteFooter()
    {
        // Template: templates/site_footer.htm
    }
}
```

---

## 6. Routing

### Pattern-based routing (`lcPatternRouting`)

Routes match top-to-bottom; first match wins.

```yaml
# routing.yml
all:
  routing:
    send_http_errors: true
    default_module: home
    default_action: index
    not_found_action:
      module: partials
      action: not_found
    routes:
      # Named, specific routes first
      blog_post:
        url: /blog/:slug
        params:
          module: blog
          action: view
      blog_page:
        url: /blog/page/:page
        params:
          module: blog
          action: listing
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

### Accessing route parameters

```php
$id     = $this->getRequest()->getParam('id');
$slug   = $this->getRequest()->getParam('slug');
$page   = $this->getRequest()->getParam('page');
$module = $this->getRequest()->getParam('module');
```

### Route constraints

```yaml
blog_post:
  url: /blog/:id/:slug
  params:
    module: blog
    action: view
  requirements:
    id: \d+
```

---

## 7. Request & Response

### `lcWebRequest`

```php
$request = $this->getRequest();

$request->getMethod()             // 'GET', 'POST', 'PUT', 'DELETE'
$request->isGet()
$request->isPost()
$request->isAjax()                // X-Requested-With: XMLHttpRequest

$request->getParam('key')         // Route / GET parameter
$request->getParams()
$request->getPostParam('key')
$request->getPostParams()
$request->getPutParams()
$request->getDeleteParams()

$request->getHeader('Accept')
$request->getRemoteAddr()
$request->getUri()
$request->getHost()
$request->isSecure()              // HTTPS?
$request->getRawPostData()
$request->getUploadedFile('field')
```

### `lcWebResponse`

```php
$response = $this->getResponse();

$response->setContent($html)
$response->addContent($html)
$response->setHttpStatusCode(404)
$response->setHeader('Content-Type', 'application/json')
$response->setCookie('name', 'value', time() + 3600)
$response->sendResponse()
```

---

## 8. Database & Models (Propel)

### Defining a schema

`app/config/schema.xml`:

```xml
<database name="my_datasource" defaultIdMethod="native">
  <table name="post">
    <column name="id"         type="INTEGER" primaryKey="true" autoIncrement="true"/>
    <column name="title"      type="VARCHAR" size="255" required="true"/>
    <column name="body"       type="LONGVARCHAR"/>
    <column name="created_at" type="TIMESTAMP"/>
  </table>
</database>
```

Generate PHP models:

```bash
./app/shell/cmd propel build
```

Each table generates three files:
- `Post.php` — Active Record object (extend this for business logic)
- `PostPeer.php` — Static query helper (legacy pattern)
- `PostQuery.php` — Fluent query builder (preferred)

### Query builder (preferred)

```php
use Propel\Runtime\ActiveQuery\Criteria;

// Single record by PK
$post = PostQuery::create()->findPk($id);

// Filtered list
$posts = PostQuery::create()
    ->filterByTitle('%Lightcast%', Criteria::LIKE)
    ->orderByCreatedAt(Criteria::DESC)
    ->limit(10)
    ->find();

// Count
$count = PostQuery::create()->filterByIsPublished(true)->count();

// Delete
PostQuery::create()->filterById($id)->delete();
```

### Peer queries (legacy, still used)

```php
$post = PostPeer::retrieveByPk($id);

$criteria = new Criteria();
$criteria->add(PostPeer::IS_PUBLISHED, true);
$posts = PostPeer::doSelect($criteria);
```

### Creating and updating records

```php
// Insert
$post = new Post();
$post->setTitle('My Post');
$post->setBody('Content here.');
$post->setCreatedAt(time());
$post->save();

// Update
$post = PostQuery::create()->findPk(1);
$post->setTitle('Updated');
$post->save();

// insertOrUpdate (framework helper — uses PK to decide)
$post = new Post();
$post->setId(5);
$post->setTitle('Upsert');
$post->insertOrUpdate();
```

### Raw connection

```php
$db  = $this->getApp()->getDatabaseManager()->getDatabase('primary');
$pdo = $db->getConnection();

$stmt = $pdo->prepare('SELECT * FROM post WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
```

### Migrations

```bash
./app/shell/cmd propel diff     # generate migration SQL from schema diff
./app/shell/cmd propel migrate  # apply pending migrations
```

---

## 9. Forms & Validation

### Basic form

For simple input handling extend `lcBaseActionForm`:

```php
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
            $this->addValidationFailure('email', 'Invalid email address');
        }

        return !$this->hasValidationFailures();
    }

    public function execute(): lcActionFormExecuteResponse
    {
        // Do work — send email, save record, etc.
        return new lcActionFormExecuteSubmitResponse();
    }
}
```

### Model-scoped form (common pattern)

For forms that map to a database table, extend `lcModelScopeActionForm` and use the widget schema:

```php
class PostUpdateForm extends lcModelScopeActionForm
{
    public function getName(): string
    {
        return 'post_update';
    }

    public function getModelMainTableName(): string
    {
        return 'post';
    }

    public function getModelDataTableName(): string
    {
        return 'post_data';   // optional i18n/data shadow table
    }

    public function getModelPrimaryKey(): string
    {
        return 'post_id';
    }

    public function getSections(): array
    {
        return [
            lcActionFormSection::getInstance('general', $this->t('General'))
                ->setItems([
                    lcActionFormWidgetSectionItem::getInstance('title'),
                    lcActionFormWidgetSectionItem::getInstance('body'),
                    lcActionFormWidgetSectionItem::getInstance('is_published'),
                ]),
        ];
    }

    public function initializeFormWidgets()
    {
        parent::initializeFormWidgets();

        $t = $this->getModelMainTableName();

        $this->getSchema()
            ->addWidget('title', $t, lcActionFormSchema::widgetFactory('text'), [
                'title'    => $this->t('Title'),
                'required' => true,
            ])
            ->addWidget('body', $t, lcActionFormSchema::widgetFactory('textarea'), [
                'title' => $this->t('Body'),
            ])
            ->addWidget('is_published', $t, lcActionFormSchema::widgetFactory('enum_yes_no'), [
                'title'         => $this->t('Published'),
                'type'          => 'check',
                'value'         => 'yes',
                'default_value' => 'yes',
            ]);

        return $this;
    }
}
```

### Using a form in a controller

```php
public function actionCreate()
{
    $form = new PostUpdateForm();
    $form->setController($this);

    if ($this->getRequest()->isPost()) {
        $form->bindData($this->getRequest()->getPostParams());

        if ($form->validate()) {
            $response = $form->execute();

            if ($response->isSuccess()) {
                $this->redirect('/posts');
                return lcController::RENDER_NONE;
            }
        }
    }

    $this->view['form']   = $form;
    $this->view['errors'] = $form->getValidationFailures();
    return $this->render();
}
```

### Built-in validators

```php
$v = new lcEmailValidator();
$ok = $v->validate('user@example.com');

$v = new lcUrlValidator();
$ok = $v->validate('https://example.com');

$failures = $v->getFailures();
```

---

## 10. Components

Components are reusable HTML fragments managed as first-class objects.

### Component class

```php
// app/addons/plugins/my_plugin/components/user_card/user_card.class.php
class componentUserCard extends lcHtmlComponent
{
    const DEFAULT_MAX_ITEMS = 5;

    private $user_id;

    public function setUserId(int $id): self
    {
        $this->user_id = $id;
        return $this;
    }

    public function execute()
    {
        $user = UserQuery::create()->findPk($this->user_id);

        $view = $this->view;
        $view['user']      = $user;
        $view['component'] = $this->getRandomIdentifier();
        // Template: components/user_card/templates/user_card.htm
    }
}
```

### Declaring components in a controller

```php
class cProfile extends lcWebController
{
    protected $use_components = ['user_card'];

    public function actionIndex()
    {
        $card = $this->getComponent('user_card');
        $card->setUserId($this->getRequest()->getParam('id'));
        $card->execute();
        return $this->render();
    }
}
```

---

## 11. Plugin System

### Plugin class

```php
// app/addons/plugins/my_plugin/plugin.php
class pMyPlugin extends lcPlugin
{
    public function execute(lcEventDispatcher $dispatcher, lcConfiguration $config)
    {
        // Register event listeners
        $dispatcher->connect('user.registered', $this, 'onUserRegistered');
    }

    public function initializeApp(lcApp $app)
    {
        parent::initializeApp($app);
        // Called after full app initialization
    }

    public function initializeWebComponents()
    {
        // Register web-specific resources (assets, etc.)
    }

    public function onUserRegistered(lcEvent $event)
    {
        $user = $event->getSubject();
        $this->getLogger()->info('New user: ' . $user->getName());
    }

    public function shutdown()
    {
        parent::shutdown();
    }
}
```

### Plugin directory layout

```
app/addons/plugins/my_plugin/
  plugin.php              # Main class: pMyPlugin extends lcPlugin
  config/
    config.php            # Plugin configuration
  models/                 # Propel ORM models
    MyModel.php
    MyModelPeer.php
    MyModelQuery.php
  forms/
    my_form/
      my_form.php         # MyForm extends lcBaseActionForm
  modules/
    my_module/
      my_module.php       # cMyModule extends lcWebController
      templates/
        index.htm
  components/
    my_component/
      my_component.class.php
      templates/
        my_component.htm
  tasks/
    my_task.php           # tMyTask extends lcTaskController
  lib/                    # Helper classes
  locale/                 # i18n translation files
  web/                    # Static assets
  ws/                     # Web service definitions
```

### Enabling a plugin

`config/default/applications/frontend/plugins.yml`:

```yaml
all:
  plugins:
    enabled:
      - my_plugin
```

Plugin locations in `project.yml`:

```yaml
plugins:
  locations:
    - addons/plugins
```

### Accessing a plugin from a controller

```php
// Declare dependency
protected $use_plugins = ['my_plugin'];

// Then access via:
$plugin = $this->getPlugin('my_plugin');

// Or from anywhere:
$plugin = $this->getApp()->getPluginManager()->getPlugin('my_plugin');
```

---

## 12. Events & Observer Pattern

### Dispatching events

```php
$dispatcher = $this->getEventDispatcher();

// Notify: fire-and-forget
$dispatcher->notify(new lcEvent('user.registered', $user));

// Filter: listeners can transform the value
$result = $dispatcher->filter(new lcEvent('content.render', $html), $html);
$html   = $result->getReturnValue();

// Provide: first listener to respond wins
$service = $dispatcher->provide('cache.backend', $this);
```

### Subscribing to events

```php
// In a plugin's execute():
$dispatcher->connect('user.registered', $this, 'handleUserRegistered');

public function handleUserRegistered(lcEvent $event)
{
    $user = $event->getSubject();
    // ...
}
```

### Application configuration event hooks

```php
// In lcFrontendConfiguration::executeBefore()
$this->event_dispatcher->connect('request.startup', $this, 'onRequestStartup');

public function onRequestStartup(lcEvent $event)
{
    // Fires before routing on every request
}
```

### Standard framework events

| Event | When fired |
|-------|-----------|
| `app.bootstrap` | App fully initialized |
| `app.shutdown` | App shutting down |
| `request.startup` | Request processing begins |
| `response.startup` | Response processing begins |
| `request.filter_parameters` | Route parameters resolved |
| `controller.change_action` | Action is about to execute |
| `view.filter_parameters` | View about to render |

---

## 13. Caching

### Cache API

```php
$cache = $this->getCache();

$cache->set('key', $value);            // store (default TTL from config)
$cache->set('key', $value, 300);       // store with 300s TTL
$value = $cache->get('key');           // null if missing or expired
$exists = $cache->has('key');
$cache->delete('key');
$cache->flush();                       // clear all
```

### Cache backends (`loaders.yml`)

| Class | Backend |
|-------|---------|
| `lcAdvMemcacheCacheStorage` | Memcache |
| `lcRedisStorage` | Redis |
| `lcApcCacheStorage` | APC/APCu |
| `lcFileCacheStorage` | Files in `tmp/cache/` |

### Defaults in `project.yml`

```yaml
cache:
  default_lifetime: 3600
  namespace: my_project
  servers:
    - 127.0.0.1:11211
```

---

## 14. Logging

### Log levels

```php
$logger = $this->getLogger();

$logger->debug('Detail for debugging');
$logger->info('Normal operation info');
$logger->warning('Non-fatal issue');
$logger->error('Error that needs attention');
$logger->critical('System in critical state');
```

### Configuring log files

`settings.yml`:

```yaml
logger:
  enabled: true
  log_files:
    frontend.warn.log: warning
    frontend.error.log: err
```

### Switching backend (`loaders.yml`)

```yaml
loaders:
  logger: lcFileLoggerNG    # default: file-based
  # logger: lcSyslogLogger
```

---

## 15. Internationalization (i18n)

### Translating strings

```php
// In controllers, plugins, forms:
$text = $this->t('save_button_label');
$text = $this->t('greeting', ['name' => $user->getName()]);
```

In templates:

```html
{t}Save{/t}
{t name="$username"}Hello, {name}!{/t}
```

Global shortcut (available everywhere after bootstrap):

```php
echo t('key');
```

### i18n backends (`loaders.yml`)

| Class | Storage |
|-------|---------|
| `lcDbLanguageSystem` | Database table |
| `lcFileLanguageSystem` | PHP/YAML files |

### Setting locale

```php
$this->getI18n()->setLocale('bg_BG');
```

---

## 16. User & Authentication

### User object

```php
$user = $this->getUser();

$user->isAuthenticated()
$user->authenticate($username, $password)  // returns bool
$user->logout()

$user->getAttribute('key')
$user->setAttribute('key', 'value')

$user->hasCredential('admin')
$user->addCredential('editor')
$user->removeCredential('editor')
```

### Protecting actions

```php
public function beforeExecute()
{
    parent::beforeExecute();

    if (!$this->getUser()->isAuthenticated()) {
        $this->redirect($this->getConfiguration()['security.login_action_url']);
        return;
    }
}
```

Or set `is_secure: true` in `security.yml` to protect the entire application automatically.

### Password utilities

```php
$hashed = lcSecurity::hashPassword($plaintext, $salt, 'sha1');
$valid  = lcSecurity::checkPassword($plaintext, $hashed, $salt, 'sha1');
```

---

## 17. Console Tasks

### Naming conventions

| Thing | Pattern | Example |
|-------|---------|---------|
| Task file | `{task_name}.php` in `tasks/` | `sys.php` |
| Task class | `t{TaskName}` | `tSys` |
| Main method | `executeTask(): bool` | `executeTask()` |

### Minimal task

```php
// app/addons/plugins/my_plugin/tasks/send_report.php
class tSendReport extends lcTaskController
{
    public function executeTask(): bool
    {
        set_time_limit(600);

        $limit = $this->getRequest()->getParam('limit', 100);
        $posts = PostQuery::create()->limit($limit)->find();

        foreach ($posts as $post) {
            $this->getLogger()->info('Processing: ' . $post->getTitle());
        }

        $this->consoleDisplay('Done. Processed ' . count($posts) . ' posts.');
        return true;
    }

    public function getHelpInfo(): string
    {
        return "Usage: send_report [--limit=N]\n";
    }
}
```

Run:

```bash
./app/shell/cmd send_report --limit=50
```

### Dispatching based on a sub-action

```php
public function executeTask(): bool
{
    $action = $this->getRequest()->getParam('action');

    switch ($action) {
        case 'rebuild_index':
            return $this->rebuildIndex();
        case 'cleanup':
            return $this->cleanup();
        default:
            $this->consoleDisplay($this->getHelpInfo(), false);
            return true;
    }
}
```

---

## 18. Web Services

Use `lcWebServiceConfiguration` for endpoints that return JSON/XML:

```php
// webroot/api.php
require_once '../lib/boot.php';
require_once '../applications/ws/config/lcWsConfiguration.class.php';

$configuration = new lcWsConfiguration(
    realpath(dirname(__FILE__) . '/../'),
    new ProjectConfiguration()
);

lcApp::bootstrap($configuration)->dispatch();
```

Web service controller:

```php
class cUsers extends lcWebServiceController
{
    public function actionList()
    {
        $users = UserQuery::create()->find();
        $this->sendJson(['users' => $users->toArray()]);
    }

    public function actionView()
    {
        $id   = $this->getRequest()->getParam('id');
        $user = UserQuery::create()->findPk($id);

        if (!$user) {
            $this->sendError(404, 'User not found');
            return lcController::RENDER_NONE;
        }

        $this->sendJson(['user' => $user->toArray()]);
    }
}
```

Routes for web services are configured in `ws_routing.yml`.

---

## 19. Utility Classes

### Strings

```php
lcStrings::slugify('Hello World!')      // 'hello-world'
lcStrings::camelize('my_method')        // 'MyMethod'
lcStrings::truncate($text, 100)
lcStrings::startsWith($str, 'prefix')
lcStrings::endsWith($str, 'suffix')
```

### Inflector

```php
lcInflector::camelize('post_title')     // 'PostTitle'
lcInflector::underscore('PostTitle')    // 'post_title'
lcInflector::pluralize('post')          // 'posts'
lcInflector::singularize('posts')       // 'post'
```

### Arrays

```php
lcArrays::mergeRecursiveDistinct($base, $override)
lcArrays::arrayFilterDeep($array)
lcArrays::flatten($nested)
```

### Files & Directories

```php
lcFiles::copy($src, $dest)
lcFiles::read($path)
lcDirs::mkdir($path)
lcDirs::remove($path)
```

### Date/Time

```php
UtcDateTime::now()
UtcDateTime::fromString('2025-01-01')
```

### System info

```php
lcSys::getPhpVersion()
lcSys::getMemoryUsage()
lcSys::isWindows()
```

---

## 20. Error Handling & Exceptions

### Exception hierarchy

```
lcException
  ├── lcSystemException         # Internal framework errors
  ├── lcConfigException         # Configuration problems
  ├── lcIOException             # File / IO errors
  ├── lcAuthException           # Authentication failures
  ├── lcAccessDeniedException   # Authorization failures (403)
  ├── lcInvalidArgumentException
  ├── lcAssertException
  └── lcNotAvailableException   # Resource not found (404)
```

### Throwing exceptions

```php
if (!$post) {
    throw new lcNotAvailableException('Post not found');
}

if (!$this->getUser()->hasCredential('admin')) {
    throw new lcAccessDeniedException();
}
```

### Custom error module

`project.yml`:

```yaml
exceptions:
  module: partials
  action: not_found
```

`app/applications/frontend/modules/partials/partials.php`:

```php
public function actionNotFound()
{
    $this->getResponse()->setHttpStatusCode(404);
    return $this->render();
}
```

### Exception email notifications

```yaml
exceptions:
  mail:
    enabled: true
    recipient: devteam@example.com
    skip_exceptions:
      - lcNotAvailableException
      - lcAccessDeniedException
```

---

## 21. Autoloading

Lightcast uses a custom class autoloader backed by a pre-built cache file at  
`source/assets/misc/autoload/autoload.php`.

### In `DO_DEBUG` mode

The cache is bypassed; classes are discovered on-the-fly from registered paths.

### In production

Rebuild the cache after adding new classes:

```bash
./app/shell/cmd build_autoload
```

### Class file naming convention

All framework and project classes use the `.class.php` suffix convention:

```
MyClass.class.php   →   class MyClass
```

Controller and task files follow a shorter convention:

```
home.php            →   class cHome extends lcWebController
sys.php             →   class tSys extends lcTaskController
```

---

## Conventions Summary

| Thing | Pattern | Example |
|-------|---------|---------|
| Application config class | `lc{App}Configuration extends lcWebConfiguration` | `lcFrontendConfiguration` |
| Project config class | `extends lcProjectConfiguration` | `ProjectConfiguration` |
| Controller file | `{module}.php` in `modules/{module}/` | `home.php` |
| Controller class | `c{ModuleName}` | `cHome` |
| Action method | `action{Name}()` | `actionIndex()` |
| Partial method | `partial{Name}()` | `partialSiteHeader()` |
| Template file | `{action_name}.htm` in `templates/` | `index.htm` |
| Layout file | `{name}.htm` in `layouts/` | `layouts/index.htm` |
| Layout placeholder | `[PAGE_CONTENT]` | — |
| Partial include tag | `<!-- PARTIAL module/action -->` | `<!-- PARTIAL partials/header -->` |
| Plugin class | `p{PluginName} extends lcPlugin` | `pCore` |
| Form class | `{Name}Form extends lcBaseActionForm` | `PostUpdateForm` |
| Model-scoped form | `extends lcModelScopeActionForm` | — |
| Task class | `t{TaskName} extends lcTaskController` | `tSys` |
| Task entry method | `executeTask(): bool` | — |
| Component class | `component{Name} extends lcHtmlComponent` | `componentProjectsBar` |
| Model files | Generated by Propel: `Model.php`, `ModelPeer.php`, `ModelQuery.php` | — |
