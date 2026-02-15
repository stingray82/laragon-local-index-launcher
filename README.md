# Local Projects Dashboard + Open in Sublime (Windows)

A lightweight local dashboard for browsing projects in `C:\laragon\www`
with:

-   One-click site access (`https://project.test`)
-   Automatic WordPress detection & badges
-   One-click **Open in Sublime Text**
-   Quick access to **Mailpit inbox**
-   Zero per-project setup
-   Works with nginx / PHP running as a service on Windows

Designed for fast local dev workflows.

------------------------------------------------------------------------

## Features

-   Lists all folders in `C:\laragon\www`
-   Detects WordPress installs automatically
-   Shows plugin/theme badges (Woo, Bricks, Sure*, Fluent*)
-   Opens sites in browser
-   Opens projects in **Sublime Text** reliably (even when PHP runs as a
    service)
-   Brings Sublime window to the foreground
-   One‑click access to **Mailpit** (`http://localhost:8025`)
-   Optional **keyboard shortcut (M)** to open Mailpit
-   No CLI usage required
-   No editor plugins required

------------------------------------------------------------------------

## Requirements

-   Windows 10 / 11
-   Laragon (nginx or Apache)
-   PHP 8+
-   Sublime Text 4
-   PowerShell (built-in to Windows)
-   Mailpit (running locally on port **8025**)

------------------------------------------------------------------------

## Folder Structure

    C:\laragon\www\
    │
    ├─ index.php
    ├─ open-in-sublime.php
    ├─ open-sublime-watcher.ps1
    ├─ watcher.bat
    ├─ open-sublime-queue.txt   (auto-created, NOT committed)

------------------------------------------------------------------------

## Installation (fresh machine --- \~5 seconds)

1)  Copy the project files into:

```{=html}
<!-- -->
```

    C:\laragon\www

2)  Open **PowerShell** and run once:

``` powershell
Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy Bypass -Force
```

3)  Double-click:

```{=html}
<!-- -->
```

    watcher.bat

4)  Choose:

```{=html}
<!-- -->
```

    [1] Enable (start watcher on login)

Done.

------------------------------------------------------------------------

## Usage

Visit:

    http://localhost/

### Actions

-   **Open** → open site in browser\
-   **Login** → WordPress admin\
-   **Sublime** → opens folder in Sublime and brings it to front\
-   **Mailpit** → opens the local email inbox UI

### Keyboard Shortcuts

-   `/` → focus search\
-   `Esc` → clear search\
-   `M` → open Mailpit in a new tab

New folders work instantly --- no setup required.

------------------------------------------------------------------------

## Sublime Text Settings (Important)

To prevent Sublime from reopening old projects or opening multiple
windows, add this to:

**Preferences → Settings (User)**

``` json
{
  "hot_exit": "disabled",
  "hot_exit_projects": false,
  "remember_open_files": false,
  "close_windows_when_empty": true,
  "open_files_in_new_window": "never"
}
```

------------------------------------------------------------------------

## Why the Watcher Is Needed

On Windows, web servers (nginx / PHP-FPM) often run in a non-interactive
session and **cannot launch GUI apps**.

This project uses a small PowerShell watcher that:

-   Runs in your user session
-   Watches a queue file
-   Opens Sublime instantly and reliably

No hacks, no third-party tools.

------------------------------------------------------------------------

## Smart WordPress Login (WP-CLI)

The **Login** button can use the WP-CLI Login Command (magic login links) when available, and will automatically fall back to the normal `wp-login.php` page if it isn’t.

### What you install (one-time)

1) Install the WP-CLI package (global / per-machine):

```bash
wp package install aaemnnosttv/wp-cli-login-command
```

2) Install + activate the companion server plugin **per site**:

```bash
wp login install --activate
```

After that, the dashboard can generate one-time login URLs via WP-CLI and redirect you straight into wp-admin.

### Optional: Make the companion plugin “always on” (MU-plugin)

You *can’t* turn the WP-CLI package into an MU-plugin, but you **can** make the *companion server plugin* load as an MU-plugin so it’s always enabled.

**Option A (recommended):** copy the companion plugin code directly into:

```
wp-content/mu-plugins/wp-cli-login-server.php
```

This keeps it out of the regular Plugins page (it will appear only under **Must-Use**).

**Option B:** keep the plugin in `wp-content/plugins/` for easier updates, but load + hide it using an MU-loader:

Create:

```
wp-content/mu-plugins/wp-cli-login-server-loader.php
```

```php
<?php
/**
 * MU loader for WP-CLI Login Command Server
 * - Loads the plugin from wp-content/plugins/...
 * - Hides it from the normal Plugins screen
 */

add_filter('all_plugins', function ($plugins) {
    // Change this to match your plugin folder/file
    $key = 'wp-cli-login-server/wp-cli-login-server.php';
    if (isset($plugins[$key])) {
        unset($plugins[$key]);
    }
    return $plugins;
});

// Load the actual plugin file
$plugin = WP_CONTENT_DIR . '/plugins/wp-cli-login-server/wp-cli-login-server.php';
if (file_exists($plugin)) {
    require_once $plugin;
}
```

If the plugin was previously activated as a normal plugin, deactivate it once:

```bash
wp plugin deactivate wp-cli-login-server
```

### Automatic URL mismatch fix (local clones)

If you clone/copy a database between sites and the WordPress `home`/`siteurl` still points to another `*.test` domain, magic login signatures can fail.

The provided `wp-login-smart.php` handler can automatically fix a **local-only** mismatch by updating:

- `home`
- `siteurl`

to the expected `https://{folder}.test/`, and flushing rewrites.

> It does **not** run `wp search-replace` automatically (that can change content); run that manually only when needed.

### Troubleshooting

- Confirm the login command exists:

```bash
wp help login
```

- Generate a link manually (this package requires a user locator):

```bash
wp login create admin --url-only
```

If magic URLs 404 on nginx, ensure permalinks/rewrite rules are working and try flushing rewrites:

```bash
wp rewrite flush --hard
```

## Mailpit Integration

Mailpit provides a local email testing inbox for development.

This dashboard includes:

-   A **Mailpit button** in the top toolbar\
-   Direct link to:

```{=html}
<!-- -->
```

    http://localhost:8025

-   Optional **keyboard shortcut (M)** to open Mailpit instantly

No configuration required if Mailpit is already running.

------------------------------------------------------------------------

## Security Notes

-   PHP never executes external programs
-   Only folders inside `C:\laragon\www` are allowed
-   No credentials or secrets are stored
-   Queue/log files should not be committed

------------------------------------------------------------------------

## Git Ignore

Add this to `.gitignore`:

    open-sublime-queue.txt
    *.log

------------------------------------------------------------------------

## Customization

-   Change project root:
    -   Edit `$base` in `open-in-sublime.php`
-   Change Sublime path:
    -   Edit `$SublimeExe` in `open-sublime-watcher.ps1`
-   Add/remove badge rules:
    -   Edit `$badgeRules` in `index.php`
-   Change Mailpit URL or port:
    -   Edit the Mailpit link in `index.php`

------------------------------------------------------------------------

## License

MIT
