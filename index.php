<!-- Version 1.6.0 -->
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    :root{
      --bg: #f6f7fb;
      --bg2:#eef2ff;
      --card:#ffffffcc;
      --cardSolid:#ffffff;
      --border: rgba(15,23,42,.10);
      --border2: rgba(15,23,42,.14);
      --text:#0f172a;
      --muted: rgba(15,23,42,.62);
      --shadow: 0 14px 38px rgba(15,23,42,.10);
      --shadow2: 0 10px 22px rgba(15,23,42,.08);
      --radius: 16px;

      --accent:#4f46e5;      /* indigo */
      --accentBg: rgba(79,70,229,.10);

      --ok:#16a34a;          /* green */
      --okBg: rgba(22,163,74,.10);

      /* ===== Badge System Variables =====
         Brand classes set:
           --badge-bg, --badge-tx, optional --badge-glow
         Presentation classes:
           .badge--light / .badge--dark
      */

      /* Light badge palette */
      --wp-bg: rgba(79,70,229,.10);     --wp-tx: #3730a3;
      --sure-bg: rgba(245,158,11,.14);  --sure-tx: #92400e;
      --woo-bg: rgba(124,58,237,.12);   --woo-tx: #5b21b6;
      --fluent-bg: rgba(2,132,199,.12); --fluent-tx: #075985;
      --generic-bg: rgba(100,116,139,.12); --generic-tx: #334155;

      /* Dark brand palette (backgrounds can be near-black; text is brand colour) */
      --bricks-bg: #0b0b0e;
      --bricks-tx: hsl(46, 96%, 53%);
      --bricks-glow: rgba(245,158,11,.22);

      --etch-bg: #071113;
      --etch-tx: #72f8e8;
      --etch-glow: rgba(114,248,232,.22);
    }

    * { box-sizing: border-box; }
    body{
      margin:0;
      min-height:100vh;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji","Segoe UI Emoji";
      color:var(--text);
      background:
        radial-gradient(900px 540px at 15% 15%, rgba(79,70,229,.18), transparent 55%),
        radial-gradient(900px 540px at 85% 10%, rgba(16,185,129,.12), transparent 55%),
        linear-gradient(180deg, var(--bg), var(--bg2));
      padding: 34px 18px;
    }

    .wrap{ max-width: 1040px; margin: 0 auto; }

    .list-wrap{
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: calc(var(--radius) + 6px);
      box-shadow: var(--shadow);
      overflow: hidden;
      backdrop-filter: blur(10px);
    }

    .list-title{
      padding: 18px 18px 14px;
      border-bottom: 1px solid var(--border);
      display:flex;
      align-items:flex-end;
      justify-content:space-between;
      gap: 14px;
      flex-wrap: wrap;
    }

    .list-title h2{
      margin:0;
      font-size: 22px;
      letter-spacing: -0.02em;
      font-weight: 760;
    }

    .subtitle{
      margin: 6px 0 0;
      color: var(--muted);
      font-size: 13px;
      letter-spacing: .01em;
    }

    .controls{
      display:flex;
      align-items:center;
      gap: 10px;
      flex: 1 1 360px;
      justify-content:flex-end;
    }

    .search{
      width: min(460px, 100%);
      display:flex;
      align-items:center;
      gap: 10px;
      background: var(--cardSolid);
      border: 1px solid var(--border2);
      border-radius: 14px;
      padding: 10px 12px;
      box-shadow: var(--shadow2);
    }
    .search svg{ width: 16px; height: 16px; opacity: .65; flex: 0 0 auto; }
    .search input{
      width: 100%;
      border: 0;
      outline: 0;
      background: transparent;
      color: var(--text);
      font-size: 14px;
      letter-spacing: .01em;
    }
    .meta{ color: var(--muted); font-size: 12px; letter-spacing: .02em; white-space: nowrap; }

    /* ===== Layout: 1 column (2 on wide screens) ===== */
    ul.list-dir{
      list-style:none;
      margin:0;
      padding: 14px;
      display:grid;
      grid-template-columns: 1fr;
      gap: 12px;
    }
    @media (min-width: 1100px){
      ul.list-dir{ grid-template-columns: 1fr 1fr; }
    }

    ul.list-dir > li{
      margin:0 !important;
      padding: 14px;
      border-radius: var(--radius);
      background: var(--cardSolid);
      border: 1px solid var(--border);
      box-shadow: var(--shadow2);
      transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 12px;
      min-height: 74px;
    }

    ul.list-dir > li:hover{
      transform: translateY(-2px);
      box-shadow: var(--shadow);
      border-color: rgba(15,23,42,.18);
    }

    .item-left{ display:flex; align-items:flex-start; gap: 12px; min-width: 0; }

    .icon.folder svg,
    .icon.folder-open svg{
      width: 18px;
      height: 18px;
      fill: rgba(15,23,42,.82);
      opacity: .9;
      margin-top: 2px;
    }
    .icon.folder-open{ display:none; }
    li:hover .icon.folder { display:none; }
    li:hover .icon.folder-open{ display:inline-flex; }

    .name-line{
      display:flex;
      align-items:center;
      gap: 8px;
      min-width: 0;
      flex-wrap: wrap;
    }

    a.dir{
      text-decoration:none;
      color: var(--text);
      font-weight: 760;
      letter-spacing: -0.01em;
      overflow:hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      max-width: 100%;
      display:inline-block;
    }
    a.dir:hover{ text-decoration: underline; text-underline-offset: 3px; }

    .pill{
      font-size: 12px;
      color: var(--muted);
      border: 1px solid rgba(15,23,42,.14);
      padding: 2px 8px;
      border-radius: 999px;
      letter-spacing: .02em;
    }

    .badges{
      display:flex;
      align-items:center;
      gap: 6px;
      margin-top: 7px;
      flex-wrap: wrap;
      row-gap: 6px;
    }

    /* ===== Badge System ===== */
    .badge{
      padding: 4px 10px;
      border-radius: 999px;
      font-weight: 750;
      letter-spacing: .01em;
      line-height: 1.05;
      user-select: none;
      font-size: 12px;
    }

    /* light presentation */
    .badge--light{
      background: var(--badge-bg);
      color: var(--badge-tx);
      border: 1px solid rgba(15,23,42,.10);
      box-shadow: inset 0 1px 0 rgba(255,255,255,.65);
    }

    /* dark presentation */
    .badge--dark{
      background:
        linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,0)),
        var(--badge-bg);
      color: var(--badge-tx);
      border: 1px solid rgba(255,255,255,.18);
      box-shadow:
        inset 0 1px 0 rgba(255,255,255,.12),
        0 0 0 1px rgba(0,0,0,.10);
      text-shadow: 0 0 10px var(--badge-glow, transparent);
    }

    /* Brand classes set variables only */
    .b-wp{ --badge-bg: var(--wp-bg); --badge-tx: var(--wp-tx); }
    .b-sure{ --badge-bg: var(--sure-bg); --badge-tx: var(--sure-tx); }
    .b-woo{ --badge-bg: var(--woo-bg); --badge-tx: var(--woo-tx); }
    .b-fluent{ --badge-bg: var(--fluent-bg); --badge-tx: var(--fluent-tx); }
    .b-generic{ --badge-bg: var(--generic-bg); --badge-tx: var(--generic-tx); }

    .b-bricks{ --badge-bg: var(--bricks-bg); --badge-tx: var(--bricks-tx); --badge-glow: var(--bricks-glow); }
    .b-etch{ --badge-bg: var(--etch-bg); --badge-tx: var(--etch-tx); --badge-glow: var(--etch-glow); }

    /* Buttons: fixed */
    .actions{
      display:flex;
      align-items:center;
      gap: 10px;
      flex: 0 0 auto;
    }

    .btn{
      height: 38px;
      padding: 0 12px;
      border-radius: 12px;
      border: 1px solid rgba(15,23,42,.14);
      background: rgba(255,255,255,.92);
      color: var(--text);
      text-decoration:none;
      font-size: 13px;
      font-weight: 720;
      letter-spacing: .01em;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap: 8px;
      box-shadow: 0 6px 14px rgba(15,23,42,.08);
      transition: transform .12s ease, background .12s ease, border-color .12s ease, box-shadow .12s ease;
      white-space: nowrap;
    }

    .btn svg{
      width: 14px;
      height: 14px;
      fill: currentColor;
      opacity: .95;
      flex: 0 0 auto;
    }

    .btn:hover{
      transform: translateY(-1px);
      border-color: rgba(15,23,42,.22);
      box-shadow: 0 10px 20px rgba(15,23,42,.10);
    }

    .btn:focus{
      outline: none;
      box-shadow: 0 10px 20px rgba(15,23,42,.10), 0 0 0 4px rgba(79,70,229,.18);
      border-color: rgba(79,70,229,.35);
    }

    .btn-open{
      border-color: rgba(79,70,229,.25);
      background: var(--accentBg);
      color: #1f2a74;
    }

    .btn-login{
      border-color: rgba(22,163,74,.25);
      background: var(--okBg);
      color: #0f5132;
    }

    .empty{
      display:none;
      padding: 22px 18px 26px;
      color: var(--muted);
      font-size: 14px;
    }

    @media (max-width: 560px){
      ul.list-dir{ grid-template-columns: 1fr; }
      .controls{ justify-content: stretch; }
      .search{ width: 100%; }
      .actions{ gap: 8px; }
      .btn{ height: 36px; padding: 0 10px; }
    }
  </style>
</head>

<body>
<div class="wrap">

<?php
  $thelist = "";

  // Domain pattern: https://{folder}.test/
  $scheme = "https";
  $tld = "test";

  function path_exists($path) { return file_exists($path); }

  /**
   * Badge rules now have:
   * - label
   * - brand class (b-*)
   * - tone: 'light' or 'dark'
   * - anyOf paths
   */
  $badgeRules = [
    [ 'label' => 'SureCart',   'brand' => 'b-sure',   'tone' => 'light', 'anyOf' => ['wp-content/plugins/surecart', 'wp-content/plugins/surecart-pro'] ],
    [ 'label' => 'SureForms',  'brand' => 'b-sure',   'tone' => 'light', 'anyOf' => ['wp-content/plugins/sureforms', 'wp-content/plugins/sureforms-pro'] ],
    [ 'label' => 'SureDash',   'brand' => 'b-sure',   'tone' => 'light', 'anyOf' => ['wp-content/plugins/suredash', 'wp-content/plugins/suredash-pro'] ],

    [ 'label' => 'WooCommerce','brand' => 'b-woo',    'tone' => 'light', 'anyOf' => ['wp-content/plugins/woocommerce'] ],

    [ 'label' => 'Bricks',     'brand' => 'b-bricks', 'tone' => 'dark',  'anyOf' => ['wp-content/themes/bricks'] ],
    [ 'label' => 'Etch',       'brand' => 'b-etch',   'tone' => 'dark',  'anyOf' => ['wp-content/plugins/etch'] ],

    [ 'label' => 'DevKit',     'brand' => 'b-woo',    'tone' => 'light', 'anyOf' => ['wp-content/plugins/devkit'] ],

    [ 'label' => 'FluentCRM',  'brand' => 'b-fluent', 'tone' => 'light', 'anyOf' => ['wp-content/plugins/fluent-crm', 'wp-content/plugins/fluentcrm'] ],
    [ 'label' => 'FluentForms','brand' => 'b-fluent', 'tone' => 'light', 'anyOf' => ['wp-content/plugins/fluentform', 'wp-content/plugins/fluent-forms'] ],
  ];

  $count = 0;

  if ($handle = opendir('.')) {
    $ignoreList = array('cgi-bin', '.', '..', '._');

    while (false !== ($file = readdir($handle))) {
      if (
        is_dir($file) &&
        !in_array($file, $ignoreList) &&
        substr($file, 0, 1) != '.'
      ) {
        $count++;

        $hostUrl = $scheme . "://" . $file . "." . $tld . "/";
        $safeHostUrl = htmlspecialchars($hostUrl, ENT_QUOTES, "UTF-8");
        $safeFile = htmlspecialchars($file, ENT_QUOTES, "UTF-8");
        $safeTld = htmlspecialchars($tld, ENT_QUOTES, "UTF-8");

        $isWp = file_exists($file . '/wp-load.php');

        $badges = '';
        if ($isWp) {
          $badges .= '<span class="badge badge--light b-wp">WordPress</span>';

          foreach ($badgeRules as $rule) {
            $found = false;
            foreach ($rule['anyOf'] as $relPath) {
              if (path_exists($file . '/' . $relPath)) { $found = true; break; }
            }
            if ($found) {
              $label = htmlspecialchars($rule['label'], ENT_QUOTES, "UTF-8");
              $brand = htmlspecialchars($rule['brand'], ENT_QUOTES, "UTF-8");
              $tone  = ($rule['tone'] === 'dark') ? 'badge--dark' : 'badge--light';
              $badges .= '<span class="badge ' . $tone . ' ' . $brand . '">' . $label . '</span>';
            }
          }
        } else {
          $badges .= '<span class="badge badge--light b-generic">No WP</span>';
        }

        $thelist .= '<li class="project" data-name="' . strtolower($safeFile) . '">
          <div class="item-left">
            <span class="icon folder">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M464 128H272l-64-64H48C21.49 64 0 85.49 0 112v288c0 26.51 21.49 48 48 48h416c26.51 0 48-21.49 48-48V176c0-26.51-21.49-48-48-48z"/></svg>
            </span>
            <span class="icon folder-open">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M572.694 292.093L500.27 416.248A63.997 63.997 0 0 1 444.989 448H45.025c-18.523 0-30.064-20.093-20.731-36.093l72.424-124.155A64 64 0 0 1 152 256h399.964c18.523 0-30.064 20.093-20.73 36.093zM152 224h328v-48c0-26.51-21.49-48-48-48H272l-64-64H48C21.49 64 0 85.49 0 112v278.046l69.077-118.418C86.214 242.25 117.989 224 152 224z"/></svg>
            </span>

            <div style="min-width:0;">
              <div class="name-line">
                <a target="_blank" class="dir" href="' . $safeHostUrl . '">' . $safeFile . '</a>
                <span class="pill">.' . $safeTld . '</span>
              </div>
              <div class="badges">' . $badges . '</div>
            </div>
          </div>

          <div class="actions">
          <a class="btn" href="open-in-sublime.php?dir=' . rawurlencode($file) . '">Sublime</a>';

        if ($isWp) {
          $loginUrl = $scheme . "://" . $file . "." . $tld . "/wp-login.php";
          $safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, "UTF-8");
          $thelist .= '<a target="_blank" class="btn btn-login" href="' . $safeLoginUrl . '">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M352 128c0-70.7-57.3-128-128-128S96 57.3 96 128v64H64c-35.3 0-64 28.7-64 64v192c0 35.3 28.7 64 64 64h320c35.3 0 64-28.7 64-64V256c0-35.3-28.7-64-64-64h-32V128zm-192 0c0-35.3 28.7-64 64-64s64 28.7 64 64v64H160v-64z"/></svg>
              Login
            </a>';
        }

        $thelist .= '</div></li>';
      }
    }
    closedir($handle);
  }
?>

  <div class="list-wrap">
    <div class="list-title">
      <div>
        <h2>Projects</h2>
        <div class="subtitle">Local sites on <strong>.test</strong> — press <strong>/</strong> to search</div>
      </div>

      <div class="controls">
        <div class="search">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M505 442.7L405.3 343c28.4-34.9 45.7-79.4 45.7-128.0C451 96.5 354.5 0 231 0S11 96.5 11 215s96.5 215 220 215c48.6 0 93.1-17.3 128-45.7L458.7 494c6.2 6.2 14.4 9.4 22.6 9.4s16.4-3.1 22.6-9.4c12.5-12.5 12.5-32.8.1-45.3zM231 382c-92.3 0-167-74.7-167-167S138.7 48 231 48s167 74.7 167 167-74.7 167-167 167z"/></svg>
          <input id="search" type="search" placeholder="Search projects…" autocomplete="off" />
        </div>
        <div class="meta"><span id="count"><?php echo (int)$count; ?></span> shown</div>
      </div>
    </div>

    <ul class="list-dir" id="list">
      <?php echo $thelist; ?>
    </ul>

    <div class="empty" id="empty">No projects match your search.</div>
  </div>

</div>

<script>
  (function(){
    const input = document.getElementById('search');
    const items = Array.from(document.querySelectorAll('li.project'));
    const countEl = document.getElementById('count');
    const emptyEl = document.getElementById('empty');

    function applyFilter(){
      const q = (input.value || '').trim().toLowerCase();
      let shown = 0;

      for (const li of items){
        const name = (li.getAttribute('data-name') || '');
        const match = !q || name.includes(q);
        li.style.display = match ? '' : 'none';
        if (match) shown++;
      }

      countEl.textContent = shown;
      emptyEl.style.display = shown === 0 ? 'block' : 'none';
    }

    input.addEventListener('input', applyFilter);

    document.addEventListener('keydown', (e) => {
      if (e.key === '/' && document.activeElement !== input) {
        e.preventDefault();
        input.focus();
      }
      if (e.key === 'Escape' && document.activeElement === input) {
        input.value = '';
        applyFilter();
        input.blur();
      }
    });
  })();
</script>

</body>
</html>
