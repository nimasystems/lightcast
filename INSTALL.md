# Lightcast 2.x — Installation & Project Setup

---

## Requirements

| Requirement | Minimum |
|-------------|---------|
| PHP | 7.4 (8.x recommended) |
| ext-yaml | any |
| ext-json | any |
| Composer | 2.x |
| MySQL / MariaDB | any (for DB features) |
| Memcache / Redis | optional |

---

## 1. Install via Composer

```bash
composer require nimasystems/lightcast
```

Or use a local checkout (typical for development):

```
app/
└── local/
    └── lightcast/   ← clone here
```

The `boot.php` loader checks `local/lightcast` before `vendor/nimasystems/lightcast`, so a local clone always wins.

---

## 2. Project Directory Structure

```
app/
├── src/
│   ├── Config/
│   │   └── Configuration.php               # Project-level config class
│   ├── Applications/
│   │   ├── Frontend/
│   │   │   ├── Config/
│   │   │   │   └── Configuration.php       # App config class
│   │   │   ├── Modules/
│   │   │   │   └── Home/
│   │   │   │       ├── Home.php            # Controller
│   │   │   │       └── templates/
│   │   │   │           └── index.htm       # Action template
│   │   │   ├── layouts/
│   │   │   │   └── index.htm               # Default layout
│   │   │   ├── i18n/                       # Gettext .mo/.po files
│   │   │   └── locale/
│   │   └── Backend/                        # Additional application (optional)
│   │       └── Config/Configuration.php
│   ├── Plugins/
│   │   └── MyPlugin/
│   │       ├── MyPlugin.php
│   │       ├── Config/
│   │       │   └── Configuration.php
│   │       └── Models/ Tasks/ ...
│   ├── Extensions/
│   │   └── Controllers/
│   │       └── CommonWebController.php     # Shared base controller
│   └── Tasks/
│       └── Sys.php
├── config/
│   └── default/
│       ├── applications/
│       │   └── Frontend/
│       │       ├── loaders.yml
│       │       ├── routing.yml
│       │       ├── plugins.yml
│       │       ├── view.yml
│       │       ├── security.yml
│       │       └── settings.yml
│       └── plugins/
│           └── MyPlugin/
│               └── settings.yml
├── Gen/
│   └── Propel/
│       └── Models/                         # Propel-generated model classes
├── lib/
│   ├── boot.php
│   └── boot-app.pre.php                    # Optional pre-boot hook
├── local/
│   └── lightcast/                          # Framework source
├── tmp/                                    # Writable: logs, cache, sessions
├── vendor/                                 # Composer dependencies
└── webroot/
    ├── index.php                           # Frontend entry point
    └── backend.php                         # Backend entry point (optional)
```

---

## 3. composer.json

```json
{
  "name": "acme/my-project",
  "require": {
    "php": ">=7.4"
  },
  "autoload": {
    "psr-4": {
      "Acme\\": "src/"
    }
  }
}
```

Run:

```bash
composer install
```

---

## 4. Bootstrap Loader (`lib/boot.php`)

```php
<?php
declare(strict_types=1);

/**
 * Enforce minimum framework version
 */
define('MIN_LC_VER', '2.0.0');

$current_dir = realpath(dirname(__FILE__) . '/../');

// Maintenance mode check
if (file_exists($current_dir . '/.maintenance')) {
    header('HTTP/1.0 503 Service Unavailable', true, 503);
    die('Maintenance. Be right back.');
}

// Composer autoloader (must come first)
require_once $current_dir . '/vendor/autoload.php';

// Auto-discover framework location
$framework_locations = [
    $current_dir . '/local/lightcast',
    $current_dir . '/vendor/nimasystems/lightcast',
];

$framework_dir = null;

foreach ($framework_locations as $path) {
    if (file_exists($path . '/source/libs/boot/bootstrap.php')) {
        $framework_dir = $path;
        break;
    }
}

if (!$framework_dir) {
    echo 'Cannot find the Lightcast framework.';
    exit(1);
}

require_once $framework_dir . '/source/libs/boot/bootstrap.php';

// Load project configuration
require_once $current_dir . '/src/Config/Configuration.php';
```

---

## 5. Project Configuration Class

`src/Config/Configuration.php`:

```php
<?php
declare(strict_types=1);

namespace Acme\Config;

use lcProjectConfiguration;

class Configuration extends lcProjectConfiguration
{
    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getProjectName(): string
    {
        return 'acme';
    }

    /**
     * The root PSR-4 namespace for all application code.
     * Used by the framework to resolve module and plugin classes.
     */
    public function getProjectNamespace(): string
    {
        return 'Acme';
    }

    public function getSupportedLocales(): array
    {
        return ['en_US', 'de_DE'];
    }
}
```

---

## 6. Application Configuration Class

Each application has its own class. Frontend example:

`src/Applications/Frontend/Config/Configuration.php`:

```php
<?php
declare(strict_types=1);

namespace Acme\Applications\Frontend\Config;

use lcWebConfiguration;

class Configuration extends lcWebConfiguration
{
    public function getApplicationName(): string
    {
        return 'Frontend';   // Maps to config/default/applications/Frontend/
    }

    /**
     * Optional: hook into framework events at the application level.
     */
    public function executeBefore(): void
    {
        parent::executeBefore();
        // $this->event_dispatcher->connect('request.startup', $this, 'onRequestStartup');
    }
}
```

---

## 7. Entry Points

**`webroot/index.php`** (Frontend):

```php
<?php
use Acme\Applications\Frontend\Config\Configuration;

$base_dir = realpath(dirname(__FILE__) . '/../');

require_once $base_dir . '/lib/boot.php';
require_once $base_dir . '/src/Applications/Frontend/Config/Configuration.php';

$configuration = new Configuration($base_dir, new \Acme\Config\Configuration());

// Optional: override settings before boot
include_once $base_dir . '/lib/boot-app.pre.php';

lcApp::bootstrap($configuration)->dispatch();
```

**`webroot/backend.php`** (Backend, if needed):

```php
<?php
use Acme\Applications\Backend\Config\Configuration;

$base_dir = realpath(dirname(__FILE__) . '/../');

require_once $base_dir . '/lib/boot.php';
require_once $base_dir . '/src/Applications/Backend/Config/Configuration.php';

$configuration = new Configuration($base_dir, new \Acme\Config\Configuration());
lcApp::bootstrap($configuration)->dispatch();
```

**`shell/cmd`** (Console):

```php
#!/usr/bin/env php
<?php
declare(strict_types=1);

$base_dir = realpath(dirname(__FILE__) . '/../');
require_once $base_dir . '/lib/boot.php';

$configuration = new \lcConsoleConfiguration($base_dir, new \Acme\Config\Configuration());
$configuration->setIsDebugging(false);
lcApp::bootstrap($configuration)->dispatch();
```

```bash
chmod +x shell/cmd
./shell/cmd sys --action=my_action
```

---

## 8. Configuration Files

### `config/default/applications/Frontend/loaders.yml`

Use fully qualified class names with a leading backslash:

```yaml
---
all:
  loaders:
    request: \Acme\Extensions\Request\AppWebRequest
    response: \Acme\Extensions\Response\AppWebResponse
    controller: \lcFrontWebController
    router: \lcPatternRouting
    logger: \lcFileLoggerNG
    storage: \Acme\Plugins\DbSessions\Lib\DbStorageExtended
    user: \Acme\Extensions\User\SiteUser
    i18n: \Acme\Plugins\Languages\Lib\DbLanguageSystem
    mailer: \lcPHPMailer
    cache: \Acme\Plugins\Cache\Lib\AdvCacheStore
```

### `config/default/applications/Frontend/routing.yml`

```yaml
---
all:
  routing:
    send_http_errors: true
    default_module: home
    default_action: index
    routes:
      product_view:
        url: /catalog/product/:id
        params:
          module: product
          action: view
        requirements:
          id: "[0-9]+"
      catalog_list:
        url: /catalog/:type/:name
        params:
          module: catalog
          action: productslist
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

### `config/default/applications/Frontend/plugins.yml`

Plugin names are PascalCase, matching the class/directory name:

```yaml
---
plugins:
  enabled:
    - Config
    - Cache
    - Dc
    - AppSecurity
    - Languages
    - AjaxRpc
    - Vfs
    - Currencies
    - UrlSeo
```

### `config/default/applications/Frontend/view.yml`

```yaml
---
all:
  view:
    content_type: text/html
    charset: utf-8
    has_layout: true
    decorator: index
    extension: htm
    filters:
      - \Acme\Plugins\Languages\Lib\Filters\I18nViewFilter
      - \lcHTMLTemplateViewFilter
    res_url_webpath: env(RES_URL_WEBPATH)
    stylesheets:
      all:
        - main
    javascripts:
      - app
    cache_version: env(COMMON_VIEW_CACHE_VERSION)
```

### `config/default/applications/Frontend/security.yml`

```yaml
---
all:
  security:
    is_secure: false
    login_module: members
    login_action: login
    logout_module: members
    logout_action: logout
    login_action_url: /members/login
    logout_action_url: /members/logout
    credentials_module: members
    credentials_action: no_access
    secure_login: false
    password:
      encryption: sha1
      salt: ""
```

### `config/default/applications/Frontend/settings.yml`

```yaml
---
all:
  settings:
    charset: utf-8
    admin_email: env(ADMIN_EMAIL)
    base_url: env(FRONTEND_BASE_URL)
  exceptions:
    module: home
    action: error
  logger:
    enabled: true
    log_files:
      app.log: info
      app.error.log: err
```

---

## 9. Environment Variables

Create `.env` in the project root (alongside `app/`):

```dotenv
ADMIN_EMAIL=admin@example.com
FRONTEND_BASE_URL=https://www.example.com
RES_URL_WEBPATH=https://res.example.com
DB_HOST=localhost
DB_NAME=mydb
DB_USER=dbuser
DB_PASS=secret
COMMON_VIEW_CACHE_VERSION=1
```

These are loaded by `symfony/dotenv` during the bootstrap. Reference them in YAML as `env(VAR_NAME)`.

---

## 10. Your First Module

**Controller** (`src/Applications/Frontend/Modules/Home/Home.php`):

```php
<?php
declare(strict_types=1);

namespace Acme\Applications\Frontend\Modules\Home;

use Acme\Extensions\Controllers\CommonWebController;

class Home extends CommonWebController
{
    public function actionIndex(): array
    {
        return [
            'title'   => 'Welcome',
            'message' => 'Lightcast 2 is running.',
        ];
    }
}
```

**Template** (`src/Applications/Frontend/Modules/Home/templates/index.htm`):

```html
<h1>{$title}</h1>
<p>{$message}</p>
```

**Layout** (`src/Applications/Frontend/layouts/index.htm`):

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{$page_title}</title>
</head>
<body>
[PAGE_CONTENT]
</body>
</html>
```

---

## 11. Database Setup

1. Add database config in your app `settings.yml` or a shared `databases.yml`:

```yaml
---
all:
  db:
    use_database: true
    databases:
      primary:
        classname: lcPropelDatabase
        driver: mysql
        url: mysql:host=env(DB_HOST);dbname=env(DB_NAME)
        user: env(DB_USER)
        password: env(DB_PASS)
        charset: utf8mb4
```

2. Create a Propel schema:

```xml
<!-- app/config/schema.xml -->
<database name="primary" defaultIdMethod="native">
  <table name="product">
    <column name="id"    type="INTEGER" primaryKey="true" autoIncrement="true"/>
    <column name="title" type="VARCHAR" size="255" required="true"/>
  </table>
</database>
```

3. Generate models:

```bash
./shell/cmd propel build
```

Generated classes land in `Gen/Propel/Models/` and are autoloaded via Composer.

---

## 12. Web Server Configuration

Point the vhost document root to `webroot/`.

**Apache** (`.htaccess` in `webroot/`):

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Nginx:**

```nginx
root /path/to/app/webroot;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## 13. File Permissions

```bash
chmod -R 775 tmp/
chown -R www-data:www-data tmp/
```

---

## 14. Healthcheck

The `boot.php` responds to `?healthcheck=1` with a plain `OK` before any framework code runs — suitable for load balancer probes.
