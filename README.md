# Local Projects Dashboard + Open in Sublime (Windows)

A lightweight local dashboard for browsing projects in `C:\laragon\www` with:
- One-click site access (`https://project.test`)
- Automatic WordPress detection & badges
- One-click **Open in Sublime Text**
- Zero per-project setup
- Works with nginx / PHP running as a service on Windows

Designed for fast local dev workflows.

---

## Features

- Lists all folders in `C:\laragon\www`
- Detects WordPress installs automatically
- Shows plugin/theme badges (Woo, Bricks, Sure*, Fluent*)
- Opens sites in browser
- Opens projects in **Sublime Text** reliably (even when PHP runs as a service)
- Brings Sublime window to the foreground
- No CLI usage required
- No editor plugins required

---

## Requirements

- Windows 10 / 11
- Laragon (nginx or Apache)
- PHP 8+
- Sublime Text 4
- PowerShell (built-in to Windows)

---

## Folder Structure

```
C:\laragon\www\
│
├─ index.php
├─ open-in-sublime.php
├─ open-sublime-watcher.ps1
├─ watcher.bat
├─ open-sublime-queue.txt   (auto-created, NOT committed)
```

---

## Installation (fresh machine — ~5 seconds)

1) Copy the project files into:
```
C:\laragon\www
```

2) Open **PowerShell** and run once:
```powershell
Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy Bypass -Force
```

3) Double-click:
```
watcher.bat
```

4) Choose:
```
[1] Enable (start watcher on login)
```

Done.

---

## Usage

- Visit:
```
http://localhost/
```

- Click:
  - **Open** → open site in browser
  - **Login** → WordPress admin
  - **Sublime** → opens folder in Sublime and brings it to front

New folders work instantly — no setup required.

---

## Sublime Text Settings (Important)

To prevent Sublime from reopening old projects or opening multiple windows, add this to:

**Preferences → Settings (User)**

```json
{
  "hot_exit": "disabled",
  "hot_exit_projects": false,
  "remember_open_files": false,
  "close_windows_when_empty": true,
  "open_files_in_new_window": "never"
}
```

---

## Why the Watcher Is Needed

On Windows, web servers (nginx / PHP-FPM) often run in a non-interactive session and **cannot launch GUI apps**.

This project uses a small PowerShell watcher that:
- Runs in your user session
- Watches a queue file
- Opens Sublime instantly and reliably

No hacks, no third-party tools.

---

## Security Notes

- PHP never executes external programs
- Only folders inside `C:\laragon\www` are allowed
- No credentials or secrets are stored
- Queue/log files should not be committed

---

## Git Ignore

Add this to `.gitignore`:

```
open-sublime-queue.txt
*.log
```

---

## Customization

- Change project root:
  - Edit `$base` in `open-in-sublime.php`
- Change Sublime path:
  - Edit `$SublimeExe` in `open-sublime-watcher.ps1`
- Add/remove badge rules:
  - Edit `$badgeRules` in `index.php`

---

## License

MIT
