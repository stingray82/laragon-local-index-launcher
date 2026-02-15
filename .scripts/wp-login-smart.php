<?php
// .scripts/wp-login-smart.php
// Smart WordPress login:
// - Uses WP-CLI Login Command if installed (aaemnnosttv/wp-cli-login-command)
// - Falls back to /wp-login.php otherwise
//
// Extra: Automatically fixes mismatched home/siteurl when the DB points to another *.test domain
// (common after cloning DBs). Only fixes home+siteurl + flushes rewrites.

declare(strict_types=1);

// -------------------- Config --------------------

// If wp isn't found automatically, set full path here (wp.bat/wp.cmd/wp.exe)
$wpCli = ''; // e.g. 'C:\\laragon\\bin\\wp-cli\\wp.cmd'

$tld = 'test';

// Auto-fix mismatched home/siteurl (recommended)
$autoFixSiteUrl = true;

// Magic link expiry (seconds)
$expiresSeconds = 900;

// Force a specific user (optional): 'admin' or '1' or 'you@site.com'
$preferredUserLocator = '';

// -------------------- Helpers --------------------
function is_safe_dir(string $dir): bool {
  return $dir !== '' && preg_match('/^[a-zA-Z0-9._-]+$/', $dir) === 1;
}

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function debug_out(string $title, array $data): void {
  header('Content-Type: text/html; charset=utf-8');
  echo '<h2>' . h($title) . '</h2>';
  echo '<pre>' . h(print_r($data, true)) . '</pre>';
  exit;
}

function run_cmd(string $cmd, ?string $cwd = null): array {
  // Run via cmd.exe for Windows .bat/.cmd reliability.
  $full = 'cmd.exe /d /s /c "' . $cmd . '"';

  $descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
  ];

  $proc = proc_open($full, $descriptors, $pipes, $cwd ?: null);
  if (!is_resource($proc)) {
    return ['code' => 127, 'cmd' => $full, 'out' => '', 'err' => 'proc_open failed'];
  }

  fclose($pipes[0]);
  $out = stream_get_contents($pipes[1]);
  $err = stream_get_contents($pipes[2]);
  fclose($pipes[1]);
  fclose($pipes[2]);

  $code = proc_close($proc);

  return [
    'code' => (int)$code,
    'cmd'  => $full,
    'out'  => is_string($out) ? $out : '',
    'err'  => is_string($err) ? $err : '',
  ];
}

function redirect(string $url): void {
  header('Location: ' . $url, true, 302);
  exit;
}

function first_nonempty_line(string $text): string {
  $lines = preg_split('/\R/', $text) ?: [];
  foreach ($lines as $line) {
    $line = trim((string)$line);
    if ($line !== '') return $line;
  }
  return '';
}

function ensure_trailing_slash(string $url): string {
  return rtrim($url, "/ \t\n\r\0\x0B") . '/';
}

function detect_wp_cli(): string {
  $res = run_cmd('where wp');
  if (($res['code'] ?? 1) === 0) {
    $path = first_nonempty_line($res['out'] ?? '');
    if ($path !== '') return $path;
  }
  return '';
}

function wp_cmd_path_only(string $wpCliPath, string $projectPath, string $args): string {
  return '"' . $wpCliPath . '" --path="' . $projectPath . '" ' . $args;
}

function wp_cmd(string $wpCliPath, string $projectPath, string $siteUrl, string $args): string {
  return '"' . $wpCliPath . '" --path="' . $projectPath . '" --url="' . $siteUrl . '" ' . $args;
}

function current_scheme_guess(): string {
  // Best-effort: supports common nginx proxy vars too
  if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    return strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : 'http';
  }
  if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') return 'https';
  if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return 'https';
  return 'http';
}

// -------------------- Input --------------------
$debug = isset($_GET['debug']) && $_GET['debug'] !== '0';

$dir = $_GET['dir'] ?? '';
if (!is_safe_dir($dir)) {
  http_response_code(400);
  exit('Invalid dir');
}

$base = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
if ($base === false) {
  http_response_code(500);
  exit('Base path not found');
}

$projectPath = realpath($base . DIRECTORY_SEPARATOR . $dir);
if ($projectPath === false) {
  http_response_code(404);
  exit('Project not found');
}

$baseWithSep = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if (strpos($projectPath, $baseWithSep) !== 0) {
  http_response_code(403);
  exit('Forbidden');
}

// Confirm WP install
$wpLoad = $projectPath . DIRECTORY_SEPARATOR . 'wp-load.php';
$expectedScheme = current_scheme_guess();
$expectedUrl = ensure_trailing_slash($expectedScheme . '://' . $dir . '.' . $tld);

if (!file_exists($wpLoad)) {
  redirect($expectedUrl);
}

// -------------------- Determine wp-cli path --------------------
$wpCliPath = trim($wpCli);
if ($wpCliPath === '') {
  $wpCliPath = detect_wp_cli();
}
if ($wpCliPath === '') {
  if ($debug) debug_out('Fallback: wp not found in PHP PATH', [
    'hint' => 'Set $wpCli to absolute path of wp.cmd/wp.bat (from `where wp` in terminal).',
  ]);
  redirect($expectedUrl . 'wp-login.php');
}

$info = run_cmd('"' . $wpCliPath . '" --info');
if ($info['code'] !== 0) {
  if ($debug) debug_out('Fallback: wp --info failed', $info);
  redirect($expectedUrl . 'wp-login.php');
}

// -------------------- Read current WP home/siteurl --------------------
$homeRes = run_cmd(wp_cmd_path_only($wpCliPath, $projectPath, 'option get home --quiet'));
$siteRes = run_cmd(wp_cmd_path_only($wpCliPath, $projectPath, 'option get siteurl --quiet'));

if ($homeRes['code'] !== 0 || $siteRes['code'] !== 0) {
  if ($debug) debug_out('Fallback: could not read home/siteurl', [
    'home' => $homeRes,
    'siteurl' => $siteRes,
  ]);
  redirect($expectedUrl . 'wp-login.php');
}

$homeUrl = ensure_trailing_slash(trim($homeRes['out'] ?? ''));
$siteUrl = ensure_trailing_slash(trim($siteRes['out'] ?? ''));

$homeHost = strtolower((string)parse_url($homeUrl, PHP_URL_HOST));
$expectedHost = strtolower((string)parse_url($expectedUrl, PHP_URL_HOST));

// -------------------- Auto-fix mismatch --------------------
$didFix = false;
$fixLog = [];

if ($autoFixSiteUrl && $homeHost && $expectedHost && $homeHost !== $expectedHost) {
  // Only auto-fix if the existing host looks like a local *.test domain.
  // This prevents accidentally rewriting real/staging/production domains.
  $looksLocal = str_ends_with($homeHost, '.' . strtolower($tld));

  if ($looksLocal) {
    $setHome = run_cmd(wp_cmd_path_only($wpCliPath, $projectPath, 'option set home "' . $expectedUrl . '"'));
    $setSite = run_cmd(wp_cmd_path_only($wpCliPath, $projectPath, 'option set siteurl "' . $expectedUrl . '"'));
    $flush   = run_cmd(wp_cmd_path_only($wpCliPath, $projectPath, 'rewrite flush --hard'));

    $fixLog = [
      'setHome' => $setHome,
      'setSiteurl' => $setSite,
      'flush' => $flush,
    ];

    // Re-read after fix
    $homeRes2 = run_cmd(wp_cmd_path_only($wpCliPath, $projectPath, 'option get home --quiet'));
    $siteRes2 = run_cmd(wp_cmd_path_only($wpCliPath, $projectPath, 'option get siteurl --quiet'));

    if ($homeRes2['code'] === 0 && $siteRes2['code'] === 0) {
      $homeUrl = ensure_trailing_slash(trim($homeRes2['out'] ?? ''));
      $siteUrl = ensure_trailing_slash(trim($siteRes2['out'] ?? ''));
      $didFix = true;
    }
  }
}

// Use home as the canonical URL for wp-cli --url + fallback
$canonicalUrl = $homeUrl !== '/' ? $homeUrl : $expectedUrl;
$fallback = $canonicalUrl . 'wp-login.php';

// -------------------- Ensure login command exists --------------------
$helpLogin = run_cmd(wp_cmd($wpCliPath, $projectPath, $canonicalUrl, 'help login'));
if ($helpLogin['code'] !== 0) {
  if ($debug) debug_out('Fallback: wp help login failed (login command not installed)', [
    'expectedUrl' => $expectedUrl,
    'canonicalUrl' => $canonicalUrl,
    'help' => $helpLogin,
    'didFix' => $didFix,
    'fixLog' => $fixLog,
  ]);
  redirect($fallback);
}

// -------------------- Pick user locator --------------------
$userLocator = trim($preferredUserLocator);

if ($userLocator === '') {
  $admins = run_cmd(wp_cmd($wpCliPath, $projectPath, $canonicalUrl, 'user list --role=administrator --field=user_login --format=csv'));
  if ($admins['code'] !== 0) {
    if ($debug) debug_out('Fallback: could not list admin users', [
      'admins' => $admins,
      'canonicalUrl' => $canonicalUrl,
      'didFix' => $didFix,
      'fixLog' => $fixLog,
    ]);
    redirect($fallback);
  }
  $userLocator = first_nonempty_line($admins['out'] ?? '');
  if ($userLocator === '') {
    if ($debug) debug_out('Fallback: no administrator user found', [
      'out' => $admins['out'],
      'canonicalUrl' => $canonicalUrl,
      'didFix' => $didFix,
      'fixLog' => $fixLog,
    ]);
    redirect($fallback);
  }
}

// -------------------- Create magic login URL --------------------
$loginCmd = 'login create ' . escapeshellarg($userLocator)
          . ' --expires=' . (int)$expiresSeconds
          . ' --url-only';

$login = run_cmd(wp_cmd($wpCliPath, $projectPath, $canonicalUrl, $loginCmd));

if ($login['code'] !== 0) {
  if ($debug) debug_out('Fallback: wp login create failed', [
    'login' => $login,
    'canonicalUrl' => $canonicalUrl,
    'didFix' => $didFix,
    'fixLog' => $fixLog,
  ]);
  redirect($fallback);
}

$magicUrl = trim($login['out'] ?? '');
if (!preg_match('#^https?://#i', $magicUrl)) {
  if ($debug) debug_out('Fallback: wp login create did not return a URL', [
    'login' => $login,
    'canonicalUrl' => $canonicalUrl,
    'didFix' => $didFix,
    'fixLog' => $fixLog,
  ]);
  redirect($fallback);
}

if ($debug) {
  debug_out('Success: magic URL generated', [
    'wp' => $wpCliPath,
    'projectPath' => $projectPath,
    'expectedUrl' => $expectedUrl,
    'home(before/after)' => $homeUrl,
    'siteurl(before/after)' => $siteUrl,
    'canonicalUrl(used for --url)' => $canonicalUrl,
    'userLocator' => $userLocator,
    'magicUrl' => $magicUrl,
    'didFix' => $didFix,
    'fixLog' => $fixLog,
    'raw' => $login,
    'note' => 'If content still references the old domain, run wp search-replace manually.',
  ]);
}

redirect($magicUrl);
