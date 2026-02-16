<#
setup.ps1 — Laragon Local Dashboard bootstrap (final)

Assumptions / layout:
- Repo root contains: index.php
- Repo root contains: .scripts\ (wp-login-smart.php, open-in-sublime.php, open-sublime-watcher.ps1, watcher.bat, etc.)
- Target install path: C:\laragon\www
- Only file in www root managed by this repo is index.php (projects/folders untouched)
- All helper scripts live in C:\laragon\www\.scripts

Run:
  powershell -ExecutionPolicy Bypass -File .\setup.ps1

Useful switches:
  -Force                    Overwrite existing managed files
  -InstallWpCliLoginPackage Install aaemnnosttv/wp-cli-login-command for WP-CLI (machine-wide WP-CLI context)
  -InstallLoginCompanion    For each detected WP site: wp login install --activate
  -CreateMuLoader           Create MU loader per site to load + hide the companion plugin (no BOM)
  -Repair                   Repair mode: remove UTF-8 BOM from PHP files + fix home/siteurl mismatch (local *.test only)
#>

[CmdletBinding()]
param(
  [switch]$Force,
  [switch]$InstallWpCliLoginPackage,
  [switch]$InstallLoginCompanion,
  [switch]$CreateMuLoader,
  [switch]$Repair
)

$ErrorActionPreference = "Stop"
$tld = "test"

# -------------------- UI helpers --------------------
function Step($m){ Write-Host "==> $m" -ForegroundColor Cyan }
function Ok($m){ Write-Host "OK: $m" -ForegroundColor Green }
function Warn($m){ Write-Host "WARN: $m" -ForegroundColor Yellow }

# -------------------- Paths --------------------
$RepoRoot   = Split-Path -Parent $MyInvocation.MyCommand.Path
$LaragonWww = "C:\laragon\www"
$ScriptsDir = Join-Path $LaragonWww ".scripts"

# -------------------- No-BOM file writer --------------------
function Write-TextNoBom([string]$Path, [string]$Content) {
  $utf8NoBom = New-Object System.Text.UTF8Encoding($false)
  [System.IO.File]::WriteAllText($Path, $Content, $utf8NoBom)
}

# -------------------- Remove UTF-8 BOM from files --------------------
function Remove-Utf8BomFromFiles([string]$Path, [string]$Filter="*.php") {
  if (-not (Test-Path $Path)) { return }
  Get-ChildItem -Path $Path -Recurse -Filter $Filter -ErrorAction SilentlyContinue | ForEach-Object {
    try {
      $bytes = [System.IO.File]::ReadAllBytes($_.FullName)
      if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        $newBytes = $bytes[3..($bytes.Length-1)]
        [System.IO.File]::WriteAllBytes($_.FullName, $newBytes)
        Warn "Removed BOM: $($_.FullName)"
      }
    } catch {
      Warn "Failed BOM check/remove: $($_.FullName)"
    }
  }
}

# -------------------- WP-CLI detection --------------------
function Get-WpPath {
  try { (where.exe wp 2>$null | Select-Object -First 1) } catch { $null }
}

# -------------------- Detect WP sites --------------------
function Get-WpSites([string]$root) {
  if (-not (Test-Path $root)) { return @() }
  Get-ChildItem $root -Directory -ErrorAction SilentlyContinue | Where-Object {
    $_.Name -notin @(".scripts", "cgi-bin", "scripts") -and
    (Test-Path (Join-Path $_.FullName "wp-load.php"))
  } | Select-Object -ExpandProperty FullName
}

# -------------------- Local domain helpers --------------------
function FolderToHost([string]$folderName, [string]$tld="test") {
  return "$folderName.$tld"
}

# -------------------- Safety checks --------------------
if (-not (Test-Path $LaragonWww)) {
  throw "Laragon www folder not found at $LaragonWww. Install Laragon (or edit setup.ps1 path)."
}

# -------------------- Execution policy (CurrentUser) --------------------
try {
  Step "Setting PowerShell ExecutionPolicy -> Bypass (CurrentUser)"
  Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy Bypass -Force | Out-Null
  Ok "ExecutionPolicy set"
} catch {
  Warn "Could not set ExecutionPolicy automatically. You may need to set it manually."
}

# -------------------- Install index.php --------------------
Step "Installing index.php into $LaragonWww"
$srcIndex = Join-Path $RepoRoot "index.php"
$dstIndex = Join-Path $LaragonWww "index.php"

if (-not (Test-Path $srcIndex)) {
  throw "Missing index.php in repo root."
}

if ((Test-Path $dstIndex) -and (-not $Force)) {
  Warn "index.php already exists (use -Force to overwrite)"
} else {
  Copy-Item $srcIndex $dstIndex -Force
  Ok "index.php installed"
}

# -------------------- Install .scripts folder --------------------
Step "Installing .scripts into $ScriptsDir"
$srcScripts = Join-Path $RepoRoot ".scripts"
if (-not (Test-Path $srcScripts)) {
  throw "Missing .scripts folder in repo root."
}

if ((Test-Path $ScriptsDir) -and $Force) {
  Remove-Item $ScriptsDir -Recurse -Force
}

Copy-Item $srcScripts $ScriptsDir -Recurse -Force
Ok ".scripts installed"

# -------------------- Queue file --------------------
$queue = Join-Path $LaragonWww "open-sublime-queue.txt"
if (-not (Test-Path $queue)) {
  "" | Out-File $queue -Encoding ascii
  Ok "Created open-sublime-queue.txt"
}

# -------------------- Startup watcher --------------------
Step "Enabling Sublime watcher on login (Startup folder)"
$StartupDir = Join-Path $env:APPDATA "Microsoft\Windows\Start Menu\Programs\Startup"
$StartupCmd = Join-Path $StartupDir "LaragonSublimeWatcher.cmd"
$WatcherPs1 = Join-Path $ScriptsDir "open-sublime-watcher.ps1"

if (-not (Test-Path $WatcherPs1)) {
  Warn "Watcher script not found: $WatcherPs1"
} else {
  $cmdText = @"
@echo off
REM Starts the Sublime watcher in the background
powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "$WatcherPs1"
"@
  if ((Test-Path $StartupCmd) -and (-not $Force)) {
    Warn "Startup watcher already exists (use -Force to overwrite)"
  } else {
    # CMD files should be ANSI/ASCII-safe
    $cmdText | Out-File $StartupCmd -Encoding ascii -Force
    Ok "Startup watcher installed: $StartupCmd"
  }
}

# -------------------- Start watcher now (current session) --------------------
if (Test-Path $WatcherPs1) {
  Step "Starting Sublime watcher for this session"
  Start-Process powershell.exe `
    -ArgumentList "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$WatcherPs1`"" `
    -WindowStyle Hidden
  Ok "Watcher started"
}


# -------------------- Mailpit check --------------------
Step "Checking Mailpit (http://localhost:8025)"
try {
  $resp = Invoke-WebRequest -Uri "http://localhost:8025" -UseBasicParsing -TimeoutSec 2
  Ok "Mailpit reachable (status $($resp.StatusCode))"
} catch {
  Warn "Mailpit not reachable on http://localhost:8025 (OK if not started yet)"
}

# -------------------- WP-CLI info --------------------
$wp = Get-WpPath
if ($wp) { Ok "WP-CLI found: $wp" } else { Warn "WP-CLI not found in PATH for this session (Smart Login will fall back)." }

# -------------------- Optional: install WP-CLI login package --------------------
if ($InstallWpCliLoginPackage) {
  if (-not $wp) {
    Warn "Skipping WP-CLI package install (wp not found)."
  } else {
    Step "Installing WP-CLI package: aaemnnosttv/wp-cli-login-command"
    try {
      & wp package install aaemnnosttv/wp-cli-login-command | Out-Host
      Ok "WP-CLI login package installed (or already present)"
    } catch {
      Warn "Failed to install WP-CLI package. Try manually: wp package install aaemnnosttv/wp-cli-login-command"
    }
  }
}

# -------------------- Detect WP sites --------------------
$wpSites = Get-WpSites $LaragonWww
if ($wpSites.Count -gt 0) {
  Ok "Detected WordPress sites: $($wpSites.Count)"
} else {
  Warn "No WordPress sites detected in $LaragonWww (no wp-load.php found)."
}

# -------------------- Optional: install companion plugin per site (non-interactive) --------------------
if ($InstallLoginCompanion) {
  if (-not $wp) {
    Warn "Skipping companion install (wp not found)."
  } elseif ($wpSites.Count -eq 0) {
    Warn "Skipping companion install (no WP sites detected)."
  } else {
    Step "Ensuring WP-CLI Login companion plugin exists per WP site (non-interactive)"

    foreach ($site in $wpSites) {
      $pluginsDir = Join-Path $site "wp-content\plugins"

      # Helper: locate server plugin main file by header
      function Find-LoginServerPluginFile([string]$pluginsDir) {
        if (-not (Test-Path $pluginsDir)) { return $null }
        return Get-ChildItem -Path $pluginsDir -Recurse -Filter "*.php" -ErrorAction SilentlyContinue |
          Where-Object {
            (Get-Content $_.FullName -TotalCount 40 -ErrorAction SilentlyContinue) -match "WP CLI Login Command Server"
          } | Select-Object -First 1
      }

      $found = Find-LoginServerPluginFile $pluginsDir

      if ($found) {
        Ok "Companion already present: $site"
        continue
      }

      try {
        Push-Location $site
        # Install only (avoid --activate to reduce failure modes)
        & wp --path="$site" login install | Out-Host
        Pop-Location
      } catch {
        Warn "Install failed on $site (wp login install)."
        try { Pop-Location } catch {}
      }

      # Re-scan
      $found = Find-LoginServerPluginFile $pluginsDir
      if ($found) {
        Ok "Companion installed: $site"
      } else {
        Warn "Companion still not found after install: $site"
      }
    }
  }
}

# -------------------- Optional: MU loader to load + hide companion plugin --------------------
if ($CreateMuLoader) {
  if ($wpSites.Count -eq 0) {
    Warn "Skipping MU loader creation (no WP sites)."
  } else {
    Step "Creating MU loader per site (no BOM) to load + hide companion plugin"

    foreach ($site in $wpSites) {
      $muDir = Join-Path $site "wp-content\mu-plugins"
      if (-not (Test-Path $muDir)) {
        New-Item -ItemType Directory -Path $muDir | Out-Null
      }

      $pluginsDir = Join-Path $site "wp-content\plugins"
      if (-not (Test-Path $pluginsDir)) {
        Warn "No plugins dir: $pluginsDir (did you run wp login install?)"
        continue
      }

      $found = Get-ChildItem -Path $pluginsDir -Recurse -Filter "*.php" -ErrorAction SilentlyContinue |
        Where-Object {
          (Get-Content $_.FullName -TotalCount 40 -ErrorAction SilentlyContinue) -match "WP CLI Login Command Server"
        } |
        Select-Object -First 1

      if (-not $found) {
        Warn "Companion plugin not found in $site/wp-content/plugins. Run: wp login install"
        continue
      }

      $rel = $found.FullName.Substring($pluginsDir.Length).TrimStart("\")
      $pluginKey = ($rel -replace "\\","/")  # folder/file.php

      $loaderPath = Join-Path $muDir "wp-cli-login-server-loader.php"

      if ((Test-Path $loaderPath) -and (-not $Force)) {
        Warn "MU loader exists (use -Force to overwrite): $loaderPath"
        continue
      }

      $loaderTemplate = @'
<?php
/**
 * MU loader for WP-CLI Login Command Server
 * - Loads the companion plugin from wp-content/plugins
 * - Hides it from the normal Plugins screen
 */

add_filter('all_plugins', function ($plugins) {
    $key = '__PLUGIN_KEY__';
    if (isset($plugins[$key])) {
        unset($plugins[$key]);
    }
    return $plugins;
});

$plugin = WP_CONTENT_DIR . '/plugins/' . '__PLUGIN_KEY__';
if (file_exists($plugin)) {
    require_once $plugin;
}
'@

      $loader = $loaderTemplate.Replace('__PLUGIN_KEY__', $pluginKey)
      Write-TextNoBom -Path $loaderPath -Content $loader
      Ok "MU loader written: $loaderPath (hiding: $pluginKey)"
    }
  }
}




# -------------------- Repair mode --------------------
if ($Repair) {
  Step "Repair mode: removing UTF-8 BOM from PHP files"
  Remove-Utf8BomFromFiles -Path $LaragonWww -Filter "*.php"

  if ($wp -and $wpSites.Count -gt 0) {
    Step "Repair mode: fixing local home/siteurl mismatches (only *.test)"
    foreach ($site in $wpSites) {
      $folder = Split-Path $site -Leaf
      $expected = "https://$(FolderToHost $folder $tld)/"

      try {
        $home = (& wp --path="$site" option get home --quiet) 2>$null
        $siteurl = (& wp --path="$site" option get siteurl --quiet) 2>$null

        $home = ($home | Out-String).Trim()
        $siteurl = ($siteurl | Out-String).Trim()

        $homeHost = ""
        try { $homeHost = ([Uri]$home).Host } catch {}

        if ($homeHost -and $homeHost.EndsWith(".$tld") -and ($home -ne $expected -or $siteurl -ne $expected)) {
          Warn "Fixing URLs for ${folder}: $homeHost -> $(FolderToHost $folder $tld)"
          & wp --path="$site" option set home "$expected" | Out-Host
          & wp --path="$site" option set siteurl "$expected" | Out-Host
          & wp --path="$site" rewrite flush --hard | Out-Host
          Ok "Fixed: $folder"
        }
      } catch {
        Warn "Repair failed for $site"
      }
    }
  } else {
    Warn "Repair mode URL-fix skipped (wp not found or no WP sites)."
  }
}

# Final BOM cleanup pass for generated loaders (belt + braces)
Remove-Utf8BomFromFiles -Path $LaragonWww -Filter "*.php"

Step "Done"
Write-Host ""
Write-Host "Next:" -ForegroundColor Cyan
Write-Host "  1) Start Laragon (nginx/Apache + PHP)"
Write-Host "  2) Visit: http://localhost/"
Write-Host "  3) Login uses magic login when available (otherwise wp-login.php)"
Write-Host ""
Ok "Local dashboard ready"
