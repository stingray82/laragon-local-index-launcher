# C:\laragon\www\open-sublime-watcher.ps1
# Watches a queue file and opens each queued folder in Sublime Text, then brings it to front.

$QueueFile  = "C:\laragon\www\open-sublime-queue.txt"
$SublimeExe = "C:\Program Files\Sublime Text\sublime_text.exe"

if (-not (Test-Path $SublimeExe)) {
  Write-Host "Sublime not found at: $SublimeExe"
  Write-Host "Edit SublimeExe in this script to match your install path."
  exit 1
}

Add-Type @"
using System;
using System.Runtime.InteropServices;

public static class Win32 {
  [DllImport("user32.dll")] public static extern bool SetForegroundWindow(IntPtr hWnd);
  [DllImport("user32.dll")] public static extern bool ShowWindowAsync(IntPtr hWnd, int nCmdShow);
  [DllImport("user32.dll")] public static extern IntPtr GetForegroundWindow();
  [DllImport("user32.dll")] public static extern uint GetWindowThreadProcessId(IntPtr hWnd, out uint lpdwProcessId);
  [DllImport("kernel32.dll")] public static extern uint GetCurrentThreadId();
  [DllImport("user32.dll")] public static extern bool AttachThreadInput(uint idAttach, uint idAttachTo, bool fAttach);
}
"@

function Bring-ToFrontSublime {
  param(
    [int]$Attempts = 10,
    [int]$DelayMs = 120
  )

  for ($i = 0; $i -lt $Attempts; $i++) {
    $p = Get-Process -Name "sublime_text" -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($p -and $p.MainWindowHandle -ne 0) {
      $hWnd = $p.MainWindowHandle

      # Restore window (9 = SW_RESTORE)
      [Win32]::ShowWindowAsync($hWnd, 9) | Out-Null

      # First try normal foreground
      if ([Win32]::SetForegroundWindow($hWnd)) { return $true }

      # If Windows blocks focus, use AttachThreadInput trick
      $fg = [Win32]::GetForegroundWindow()
      if ($fg -ne [IntPtr]::Zero) {
        [uint32]$fgPid = 0
        $fgThread = [Win32]::GetWindowThreadProcessId($fg, [ref]$fgPid)
        $thisThread = [Win32]::GetCurrentThreadId()

        # Attach, set foreground, detach
        [Win32]::AttachThreadInput($thisThread, $fgThread, $true) | Out-Null
        [Win32]::SetForegroundWindow($hWnd) | Out-Null
        [Win32]::AttachThreadInput($thisThread, $fgThread, $false) | Out-Null
      }

      # Check again
      if ([Win32]::SetForegroundWindow($hWnd)) { return $true }
      return $true  # even if SetForegroundWindow returns false, we likely restored/raised it
    }

    Start-Sleep -Milliseconds $DelayMs
  }

  return $false
}

Write-Host "Watching: $QueueFile"
Write-Host "Launching: $SublimeExe"
Write-Host "Press Ctrl+C to stop."

while ($true) {
  if (Test-Path $QueueFile) {
    try {
      $lines = Get-Content -Path $QueueFile -ErrorAction Stop
      if ($lines.Count -gt 0) {
        Clear-Content -Path $QueueFile -ErrorAction SilentlyContinue

        foreach ($line in $lines) {
          $target = $line.Trim()
          if ($target -and (Test-Path $target)) {

            # Launch Sublime
            Start-Process -FilePath $SublimeExe -ArgumentList @($target) -WindowStyle Normal | Out-Null

            # Bring it to the front (with retries)
            Bring-ToFrontSublime | Out-Null
          }
        }
      }
    } catch {
      # ignore transient read/lock issues
    }
  }

  Start-Sleep -Milliseconds 250
}
