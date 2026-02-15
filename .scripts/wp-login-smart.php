<?php
// .scripts/wp-login-smart.php
declare(strict_types=1);

$wpCli = '';              // leave empty to auto-detect (where wp)
$tld = 'test';
$autoFixSiteUrl = true;

$expiresSeconds = 900;
$preferredUserLocator = ''; // optional: 'admin' or '1' etc.

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function debug_out(string $title, array $data): void {
  header('Content-Type: text/html; charset=utf-8');
  echo '<h2>' . h($title) . '</h2><pre>' . h(print_r($data, true)) . '</pre>';
  exit;
}

/**
 * Aggressive cleanup for WP-CLI output / option values:
 * - UTF-8 BOM
 * - common zero-width chars
 * - trims whitespace
 */
function clean_str(string $s): string {
  // remove UTF-8 BOM anywhere at start
  $s = preg_replace('/^\xEF\xBB\xBF/u', '', $s) ?? $s;

  // remove zero-width chars that sometimes sneak in
  $s = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $s) ?? $s;

  // normalize line endings and trim
  return trim($s);
}

function ensure_trailing_slash(string $url): string {
  $url = clean_str($url);
  return rtrim($url, "/") . '/';
}

function run_cmd(string $cmd, ?string $cwd = null): array {
  $full = 'cmd.exe /d /s /c "' . $cmd . '"';
  $desc = [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']];
  $proc = proc_open($full, $desc, $pipes, $cwd ?: null);
  if (!is_resource($proc)) return ['code'=>127,'cmd'=>$full,'out'=>'','err'=>'proc_open failed'];

  fclose($pipes[0]);
  $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
  $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
  $code = proc_close($proc);

  return ['code'=>(int)$code,'cmd'=>$full,'out'=>is_string($out)?$out:'','err'=>is_string($err)?$err:''];
}

function redirect(string $url): void {
  $url = clean_str($url);
  header('Location: ' . $url, true, 302);
  exit;
}

function first_nonempty_line(string $text): string {
  $text = clean_str($text);
  $lines = preg_split('/\R/', $text) ?: [];
  foreach ($lines as $line) {
    $line = clean_str((string)$line);
    if ($line !== '') return $line;
  }
  return '';
}

function detect_wp_cli(): string {
  $res = run_cmd('where wp');
  if (($res['code'] ?? 1) === 0) {
    $p = first_nonempty_line($res['out'] ?? '');
    if ($p !== '') return $p;
  }
  return '';
}

function wp_cmd_path_only(string $wp, string $path, string $args): string {
  return '"' . $wp . '" --path="' . $path . '" ' . $args;
}
function wp_cmd(string $wp, string $path, string $url, string $args): string {
  $url = clean_str($url);
  return '"' . $wp . '" --path="' . $path . '" --url="' . $url . '" ' . $args;
}

function current_scheme_guess(): string {
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
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $dir)) {
  http_response_code(400);
  exit('Invalid dir');
}

$base = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
if ($base === false) { http_response_code(500); exit('Base path not found'); }

$projectPath = realpath($base . DIRECTORY_SEPARATOR . $dir);
if ($projectPath === false) { http_response_code(404); exit('Project not found'); }

$baseWithSep = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if (strpos($projectPath, $baseWithSep) !== 0) { http_response_code(403); exit('Forbidden'); }

$wpLoad = $projectPath . DIRECTORY_SEPARATOR . 'wp-load.php';
$expectedUrl = ensure_trailing_slash(current_scheme_guess() . '://' . $dir . '.' . $tld);

if (!file_exists($wpLoad)) redirect($expectedUrl);

// -------------------- WP-CLI path --------------------
$wp = clean_str($wpCli);
if ($wp === '') $wp = detect_wp_cli();
if ($wp === '') {
  if ($debug) debug_out('Fallback: wp not found', []);
  redirect($expectedUrl . 'wp-login.php');
}

$info = run_cmd('"' . $wp . '" --info');
if ($info['code'] !== 0) {
  if ($debug) debug_out('Fallback: wp --info failed', $info);
  redirect($expectedUrl . 'wp-login.php');
}

// -------------------- Read home/siteurl --------------------
$homeRes = run_cmd(wp_cmd_path_only($wp, $projectPath, 'option get home --quiet'));
$siteRes = run_cmd(wp_cmd_path_only($wp, $projectPath, 'option get siteurl --quiet'));
if ($homeRes['code'] !== 0 || $siteRes['code'] !== 0) {
  if ($debug) debug_out('Fallback: could not read home/siteurl', ['home'=>$homeRes,'site'=>$siteRes]);
  redirect($expectedUrl . 'wp-login.php');
}

$homeUrl = ensure_trailing_slash($homeRes['out'] ?? '');
$siteUrl = ensure_trailing_slash($siteRes['out'] ?? '');

$homeHost = strtolower((string)parse_url($homeUrl, PHP_URL_HOST));
$expectedHost = strtolower((string)parse_url($expectedUrl, PHP_URL_HOST));

$didFix = false;
$fixLog = [];

if ($autoFixSiteUrl && $homeHost && $expectedHost && $homeHost !== $expectedHost) {
  $looksLocal = str_ends_with($homeHost, '.' . strtolower($tld));
  if ($looksLocal) {
    $setHome = run_cmd(wp_cmd_path_only($wp, $projectPath, 'option set home "' . $expectedUrl . '"'));
    $setSite = run_cmd(wp_cmd_path_only($wp, $projectPath, 'option set siteurl "' . $expectedUrl . '"'));
    $flush   = run_cmd(wp_cmd_path_only($wp, $projectPath, 'rewrite flush --hard'));

    $fixLog = ['setHome'=>$setHome,'setSiteurl'=>$setSite,'flush'=>$flush];
    $didFix = true;

    // Re-read
    $homeRes2 = run_cmd(wp_cmd_path_only($wp, $projectPath, 'option get home --quiet'));
    $siteRes2 = run_cmd(wp_cmd_path_only($wp, $projectPath, 'option get siteurl --quiet'));
    if ($homeRes2['code'] === 0) $homeUrl = ensure_trailing_slash($homeRes2['out'] ?? '');
    if ($siteRes2['code'] === 0) $siteUrl = ensure_trailing_slash($siteRes2['out'] ?? '');
  }
}

$canonicalUrl = $homeUrl ?: $expectedUrl;
$fallback = $canonicalUrl . 'wp-login.php';

// -------------------- Ensure login command exists --------------------
$helpLogin = run_cmd(wp_cmd($wp, $projectPath, $canonicalUrl, 'help login'));
if ($helpLogin['code'] !== 0) {
  if ($debug) debug_out('Fallback: wp help login failed', ['help'=>$helpLogin,'canonicalUrl'=>$canonicalUrl,'didFix'=>$didFix,'fixLog'=>$fixLog]);
  redirect($fallback);
}

// -------------------- Pick user --------------------
$userLocator = clean_str($preferredUserLocator);
if ($userLocator === '') {
  $admins = run_cmd(wp_cmd($wp, $projectPath, $canonicalUrl, 'user list --role=administrator --field=user_login --format=csv'));
  if ($admins['code'] !== 0) {
    if ($debug) debug_out('Fallback: could not list admins', ['admins'=>$admins,'canonicalUrl'=>$canonicalUrl]);
    redirect($fallback);
  }
  $userLocator = first_nonempty_line($admins['out'] ?? '');
  if ($userLocator === '') {
    if ($debug) debug_out('Fallback: no admin found', ['out'=>$admins['out'] ?? '']);
    redirect($fallback);
  }
}

// -------------------- Create magic URL --------------------
$loginCmd = 'login create ' . escapeshellarg($userLocator) . ' --expires=' . (int)$expiresSeconds . ' --url-only';
$login = run_cmd(wp_cmd($wp, $projectPath, $canonicalUrl, $loginCmd));

$magicUrl = clean_str($login['out'] ?? '');

if ($login['code'] !== 0 || !preg_match('#^https?://#i', $magicUrl)) {
  if ($debug) debug_out('Fallback: wp login create did not return a URL', [
    'login'=>$login,
    'canonicalUrl'=>$canonicalUrl,
    'userLocator'=>$userLocator,
    'magicUrl_clean'=>$magicUrl,
    'didFix'=>$didFix,
    'fixLog'=>$fixLog,
  ]);
  redirect($fallback);
}

if ($debug) debug_out('Success: magic URL generated', [
  'wp'=>$wp,
  'projectPath'=>$projectPath,
  'expectedUrl'=>$expectedUrl,
  'home'=>$homeUrl,
  'siteurl'=>$siteUrl,
  'canonicalUrl'=>$canonicalUrl,
  'userLocator'=>$userLocator,
  'magicUrl'=>$magicUrl,
  'didFix'=>$didFix,
  'fixLog'=>$fixLog,
  'raw'=>$login
]);

redirect($magicUrl);
