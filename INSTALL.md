# Lightcast — Installation & Project Setup

This guide walks through adding Lightcast to a new or existing project.

---

## Requirements

| Requirement | Minimum |
|-------------|---------|
| PHP | 5.6 (7.1+ strongly recommended) |
| ext-yaml | any |
| ext-json | any |
| MySQL / MariaDB | any (for DB features) |
| Memcache / Redis | optional (for cache features) |

---

## 1. Install via Composer

```bash
composer require nimasystems/lightcast
```

Or clone directly:

```bash
git clone https://github.com/nimasystems/lightcast.git
```

---

## 2. Project Directory Structure

A Lightcast project is typically laid out as follows:

```
my_project/
└── app/
    ├── applications/
    │   ├── frontend/
    │   │   ├── config/
    │   │   │   └── lcFrontendConfiguration.class.php
    │   │   ├── layouts/
    │   │   │   └── index.htm                  # Default layout/decorator
    │   │   └── modules/
    │   │       └── home/
    │   │           ├── home.php               # Controller
    │   │           └── templates/
    │   │               └── index.htm          # Action template
    │   └── backend/                           # Optional second application
    │       └── config/
    │           └── lcBackendConfiguration.class.php
    ├── addons/
    │   └── plugins/                           # Custom plugins
    ├── config/
    │   ├── ProjectConfiguration.class.php
    │   └── default/
    │       ├── project.yml
    │       ├── databases.yml
    │       ├── console.yml
    │       └── applications/
    │           ├── frontend/
    │           │   ├── loaders.yml
    │           │   ├── routing.yml
    │           │   ├── plugins.yml
    │           │   ├── security.yml
    │           │   ├── view.yml
    │           │   └── settings.yml
    │           └── backend/
    │               └── ...
    ├── lib/
    │   └── boot.php                           # Framework bootstrap loader
    ├── shell/
    │   └── cmd                                # Console entry point
    ├── tmp/                                   # Writable: logs, cache files
    └── webroot/
        ├── index.php                          # Frontend entry point
        └── backend.php                        # Backend entry point
```

---

## 3. Bootstrap Loader

Create `app/lib/boot.php`:

```php
<?php
define('DO_DEBUG', true);   // false in production

// Path to lightcast bootstrap
require_once __DIR__ . '/../../vendor/nimasystems/lightcast/source/libs/boot/bootstrap.php';
// or if vendored directly:
// require_once __DIR__ . '/../../lightcast/source/libs/boot/bootstrap.php';
```

---

## 4. Project Configuration Class

Create `app/config/ProjectConfiguration.class.php`:

```php
<?php

class ProjectConfiguration extends lcProjectConfiguration
{
    public function getProjectName(): string
    {
        return 'my_project';
    }
}
```

---

## 5. Application Configuration Class

Each application (frontend, backend, etc.) has its own configuration class.

Create `app/applications/frontend/config/lcFrontendConfiguration.class.php`:

```php
<?php

class lcFrontendConfiguration extends lcWebConfiguration
{
    public function getApplicationName(): string
    {
        return 'frontend';
    }

    public function executeBefore()
    {
        parent::executeBefore();

        // Hook into framework events here if needed
        // $this->event_dispatcher->connect('request.startup', $this, 'onRequestStartup');
    }
}
```

---

## 6. Web Entry Points

**`app/webroot/index.php`** (frontend):

```php
<?php
require_once '../lib/boot.php';
require_once '../applications/frontend/config/lcFrontendConfiguration.class.php';

$configuration = new lcFrontendConfiguration(
    realpath(dirname(__FILE__) . '/../'),
    new ProjectConfiguration()
);

@include_once '../config/boot_config.php';   // optional local overrides

lcApp::bootstrap($configuration)->dispatch();
```

**`app/webroot/backend.php`** (backend, if needed):

```php
<?php
require_once '../lib/boot.php';
require_once '../applications/backend/config/lcBackendConfiguration.class.php';

$configuration = new lcBackendConfiguration(
    realpath(dirname(__FILE__) . '/../'),
    new ProjectConfiguration()
);

lcApp::bootstrap($configuration)->dispatch();
```

---

## 7. Console Entry Point

Create `app/shell/cmd`:

```php
#!/usr/bin/env php
<?php
require_once dirname(__FILE__) . '/../lib/boot.php';

$configuration = new lcConsoleConfiguration(
    realpath(dirname(__FILE__) . '/../'),
    new ProjectConfiguration()
);

$configuration->setIsDebugging(false);

lcApp::bootstrap($configuration)->dispatch();
```

```bash
chmod +x app/shell/cmd
./app/shell/cmd my_task_name --option=value
```

---

## 8. Configuration Files

### `config/default/project.yml`

```yaml
---
all:
  project: ~
  settings:
    timezone: Europe/Sofia
    exception_http_header:
      enabled: false
      header: HTTP/1.1 500 Internal Server Error
  plugins:
    locations:
      - addons/plugins
  exceptions:
    module: partials
    action: error
    mail:
      enabled: false
      recipient: ~
  cache:
    default_lifetime: 3600
    namespace: my_project
    servers:
      - 127.0.0.1:11211
```

### `config/default/databases.yml`

```yaml
---
all:
  db:
    use_database: true
    databases:
      primary:
        classname: lcPropelDatabase
        logging: true
        caching: true
        datasource: my_datasource
        driver: mysql
        url: mysql:host=localhost;dbname=my_db
        user: dbuser
        password: dbpass
        charset: utf8
```

Use `.env` variables to keep secrets out of YAML:

```yaml
        user: env(DB_USER)
        password: env(DB_PASS)
```

### `config/default/applications/frontend/loaders.yml`

```yaml
---
all:
  loaders:
    request: lcWebRequest
    response: lcWebResponse
    controller: lcFrontWebController
    router: lcPatternRouting
    logger: lcFileLoggerNG
    storage: lcDbStorageExtended
    user: SiteUser
    i18n: lcDbLanguageSystem
    mailer: lcPHPMailer
    cache: lcAdvMemcacheCacheStorage
```

### `config/default/applications/frontend/routing.yml`

```yaml
---
all:
  routing:
    send_http_errors: true
    default_module: home
    default_action: index
    not_found_action:
      module: partials
      action: not_found
    routes:
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

### `config/default/applications/frontend/plugins.yml`

```yaml
---
all:
  plugins:
    enabled:
      - core
      - cache
      - languages
```

### `config/default/applications/frontend/security.yml`

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

### `config/default/applications/frontend/view.yml`

```yaml
---
all:
  view:
    filters:
      - lcHTMLTemplateViewFilter
    content_type: text/html
    charset: utf-8
    has_layout: true
    decorator: index
    extension: htm
    javascripts:
      - generic.js
    stylesheets:
      all:
        - main.css
```

### `config/default/applications/frontend/settings.yml`

```yaml
---
all:
  settings:
    charset: utf-8
    admin_email: env(ADMIN_EMAIL)
  logger:
    enabled: true
    log_files:
      frontend.warn.log: warning
      frontend.error.log: err
```

---

## 9. Environment Overrides

Any `all:` key can be overridden per environment:

```yaml
# databases.yml
all:
  db:
    databases:
      primary:
        url: mysql:host=localhost;dbname=dev_db
        user: root
        password: ~

prod:
  db:
    databases:
      primary:
        url: mysql:host=db.internal;dbname=prod_db
        user: env(DB_USER)
        password: env(DB_PASS)
```

Set the active environment in the entry point or application config class:

```php
$configuration->setEnvironment('prod');
```

---

## 10. Your First Module

**Controller** — `app/applications/frontend/modules/home/home.php`:

```php
<?php

class cHome extends lcWebController
{
    public function actionIndex()
    {
        $this->view['title']   = 'Welcome';
        $this->view['message'] = 'Lightcast is running.';
        return $this->render();
    }
}
```

**Template** — `app/applications/frontend/modules/home/templates/index.htm`:

```html
<h1>{$title}</h1>
<p>{$message}</p>
```

**Layout** — `app/applications/frontend/layouts/index.htm`:

```html
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{$page_title}</title>
</head>
<body>
[PAGE_CONTENT]
</body>
</html>
```

`[PAGE_CONTENT]` is replaced by the rendered action template output.

---

## 11. Database Schema & Models

1. Create `app/config/schema.xml` (Propel format):

```xml
<database name="my_datasource" defaultIdMethod="native">
  <table name="user">
    <column name="id"    type="INTEGER" primaryKey="true" autoIncrement="true"/>
    <column name="name"  type="VARCHAR" size="255"/>
    <column name="email" type="VARCHAR" size="255"/>
  </table>
</database>
```

2. Generate models:

```bash
./app/shell/cmd propel build
```

This generates `User.php`, `UserPeer.php`, and `UserQuery.php` into the configured models directory.

---

## 12. Web Server Configuration

Point your vhost document root to `app/webroot/`.

**Apache** (`.htaccess` in `app/webroot/`):

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
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

The `tmp/` directory must be writable by the web server:

```bash
chmod -R 775 app/tmp/
chown -R www-data:www-data app/tmp/
```

---

## 14. Verify Installation

With `DO_DEBUG = true`, visit the site — you should see the home page. Any misconfiguration will produce an error with a full trace.

Console sanity check:

```bash
./app/shell/cmd list_tasks
```
