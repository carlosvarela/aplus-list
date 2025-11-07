<?php
// index.php en /resellers-inserts/client/
$directory = __DIR__;

/**
 * PREVIEW MODE : ?preview=folder
 * load index.html folder and add  bubble ZIP button.
 */
if (isset($_GET['preview'])) {
  $folder = basename($_GET['preview']);
  $folderPath = $directory . '/' . $folder;

  if ($folder === '' || !is_dir($folderPath)) {
    http_response_code(404);
    echo 'Folder not found';
    exit;
  }

  // files inside the folder
  $indexCandidates = ['index.html', 'index.htm'];
  $indexFile = null;

  foreach ($indexCandidates as $candidate) {
    $candidatePath = $folderPath . '/' . $candidate;
    if (is_file($candidatePath)) {
      $indexFile = $candidatePath;
      break;
    }
  }

  if (!$indexFile) {
    echo 'No index.html file found inside folder.';
    exit;
  }

  $html = file_get_contents($indexFile);

  // example ZIP usando este mismo index: /resellers-inserts/client/?zip=folder
  $zipUrl    = '?zip=' . rawurlencode($folder);
  $zipUrlEsc = htmlspecialchars($zipUrl, ENT_QUOTES);
  $folderEsc = htmlspecialchars($folder, ENT_QUOTES);

  $bubble = '
<style>
  .zip-bubble-btn {
    position: fixed;
    right: 20px;
    bottom: 20px;
    width: 150px;
    height: 50px;
    border-radius: 9999px;
    background: #f9e54c;
    color: #1c115dff !important;
    font-family: Arial, sans-serif;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    text-decoration: none;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
    z-index: 9999;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
  }
  .zip-bubble-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25);
    background: #ffe24c;
  }
  .zip-bubble-btn span {
    line-height: 1;
  }
</style>
<a href="' . $zipUrlEsc . '"
   class="zip-bubble-btn"
   aria-label="Download ZIP">
 <!-- <span>Download A+ Content<br>' . $folderEsc . '</span> -->
  <span>Download A+ Content</span>
</a>
';

  // Inyectar antes de </body>
  if (stripos($html, '</body>') !== false) {
    $html = preg_replace('~</body>~i', $bubble . '</body>', $html, 1);
  } else {
    $html .= $bubble;
  }

  echo $html;
  exit;
}

/**
 * LISTADO NORMAL
 */

// excluimos este archivo y cualquier otro PHP del listado
$exclude = ['.', '..', '.htaccess', basename(__FILE__)];
$itemsRaw = array_diff(scandir($directory), $exclude);

function cleanName($name) {
  $name = pathinfo($name, PATHINFO_FILENAME);
  $name = str_replace(['_', '-'], ' ', $name);
  return ucwords($name);
}

function getLastModified($path) {
  // si es archivo, devolvemos su mtime directo
  if (is_file($path)) {
    return filemtime($path);
  }

  // si es carpeta, recorremos recursivamente
  $lastModified = 0;

  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
  );

  foreach ($iterator as $file) {
    if ($file->isFile()) {
      $mtime = $file->getMTime();
      if ($mtime > $lastModified) {
        $lastModified = $mtime;
      }
    }
  }

  // fallback: mtime de la propia carpeta
  return $lastModified ?: filemtime($path);
}

// Build structured array
$items = [];
foreach ($itemsRaw as $item) {
  // saltar cualquier .php por seguridad
  if (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
    continue;
  }

  $path = $directory . '/' . $item;
  if (is_dir($path) || is_file($path)) {
    $items[] = [
      'name'   => $item,
      'is_dir' => is_dir($path),
      'mtime'  => getLastModified($path)
    ];
  }
}

// Sorting logic
$sort = $_GET['sort'] ?? 'new';
usort($items, function($a, $b) use ($sort) {
  if ($a['is_dir'] !== $b['is_dir']) {
    return $a['is_dir'] ? -1 : 1;
  }
  return $sort === 'new'
    ? $b['mtime'] <=> $a['mtime']
    : $a['mtime'] <=> $b['mtime'];
});

// ZIP creation
if (isset($_GET['zip']) && is_dir($_GET['zip'])) {
  $folder  = basename($_GET['zip']);
  $path    = __DIR__ . '/' . $folder;
  $zipFile = sys_get_temp_dir() . "/{$folder}.zip";
  $zip     = new ZipArchive();

  if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($files as $file) {
      if (!$file->isDir()) {
        $filePath     = $file->getRealPath();
        $relativePath = substr($filePath, strlen($path) + 1);
        $zip->addFile($filePath, $relativePath);
      }
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $folder . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    readfile($zipFile);
    unlink($zipFile);
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Trust LATAM – A+ Content Library</title>

<style>
  @font-face {
    font-family: 'tt_norms_probold';
    src: url('/theme/css/tt_norms_pro_bold-webfont.woff2') format('woff2');
  }
  @font-face {
    font-family: 'tt_norms_prolight';
    src: url('/theme/css/tt_norms_pro_light-webfont.woff2') format('woff2');
  }

  :root {
    --trust-bg: #efedf7;
    --trust-text: #1b1860;
    --trust-hover: #e4e1f2;        /* hover de cards / fondo */
    --trust-border: #cfcde4;
    --trust-yellow: #f9e54c;
    --trust-link-hover: #3e3565;   /* morado oscuro para links */
  }

  body {
    background: var(--trust-bg);
    font-family: 'tt_norms_prolight', sans-serif;
    color: var(--trust-text);
    margin: 0;
    padding: 0;
  }

  a {
    font-family: 'tt_norms_probold', sans-serif;
    color: var(--trust-text);
    text-decoration: none;
    transition: color 0.2s;
  }

  a:hover {
    color: var(--trust-link-hover)!important;
  }

  .btn-warning {
    display: inline-block;
    font-family: 'tt_norms_probold';
    font-weight: 700;
    color: #000;
    background-color: var(--trust-yellow);
    border: 1px solid var(--trust-yellow);
    border-radius: 6px;
    padding: 5px 15px;
    font-size: 13px;
    transition: all 0.2s ease-in-out;
  }
  .btn-warning:hover {
    background-color: #ffca2c;
  }

  .icon {
    width: 18px;
    height: 18px;
    fill: #bfb9d6;
    margin-right: 10px;
  }

  .badge {
    background: var(--trust-border);
    color: var(--trust-text);
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    white-space: nowrap;
  }

  li {
    background: #ffffff;
    border: 1px solid var(--trust-border);
    border-radius: 10px;
    margin-bottom: 10px;
    transition: background-color 0.25s, transform 0.15s;
  }
  li:hover {
    background: var(--trust-hover);
    transform: translateX(2px);
  }

  .highlight {
    box-shadow: 0 0 0 2px var(--trust-yellow);
    background: var(--trust-hover);
  }
</style>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col items-center py-14">
  <header class="flex flex-col items-center mb-8 text-center">
    <img src="https://www.trustlatam.com/wp-content/uploads/2025/10/logo_trust_normal.svg"
         alt="Trust LATAM"
         class="w-44 mb-3 drop-shadow-md">
    <h1 class="text-[13px] uppercase tracking-[0.15em] text-[#1b1860] font-normal">A+ Content Library</h1>
    <p class="text-[14px] text-[#4b477a] max-w-lg mt-2 leading-relaxed">
      Explore and download official <strong>Trust LATAM A+ Content</strong> materials for partner marketplaces.
      Updated assets include enhanced product visuals, feature icons, and promotional layouts for e-commerce listings.
    </p>

    <div class="mt-4 flex flex-col items-center gap-2 text-[12px]">
      <div>
        <a href="?sort=new" class="px-2 hover:text-[var(--trust-link-hover)]">Sort: Newest First</a> |
        <a href="?sort=old" class="px-2 hover:text-[var(--trust-link-hover)]">Oldest First</a>
      </div>
      <div class="flex items-center gap-2 mt-1">
        <input
          id="searchInput"
          type="text"
          placeholder="Search by ID or name"
          class="px-3 py-1 text-[12px] rounded-md border border-[var(--trust-border)] bg-white focus:outline-none focus:ring-1 focus:ring-[var(--trust-yellow)] min-w-[220px]"
        >
        <button
          id="searchButton"
          class="btn-warning text-[12px] py-1 px-3"
        >
          Search
        </button>
      </div>
    </div>
  </header>

  <main class="w-full max-w-3xl px-6">
    <ul>
      <?php foreach ($items as $item):
        $mtimeFmt   = date("d M Y", $item['mtime']);
        $name       = cleanName($item['name']);
        $dataSearch = strtolower($item['name'] . ' ' . $name);

        if ($item['is_dir']): ?>
          <li class="flex items-center justify-between py-4 px-5"
              data-search="<?= htmlspecialchars($dataSearch, ENT_QUOTES) ?>"
              data-folder="<?= htmlspecialchars($item['name']) ?>">
            <div class="flex items-center">
              <svg class="icon" viewBox="0 0 24 24">
                <path d="M3 5a2 2 0 0 1 2-2h5l2 2h9a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5z"/>
              </svg>
              <!-- Preview con bubble usando este mismo index -->
              <a href="?preview=<?= urlencode($item['name']) ?>"
                 class="text-[15px] hover:text-[var(--trust-link-hover)] transition-colors"
                 target="_blank">
                <?= $name ?>
              </a>
            </div>
            <div class="flex items-center space-x-3">
              <span class="badge"><?= $mtimeFmt ?></span>
              <a href="?zip=<?= urlencode($item['name']) ?>" class="btn-warning">Download ZIP</a>
            </div>
          </li>
        <?php else:
          $sizeMB = round(filesize($directory . '/' . $item['name']) / 1024 / 1024, 1); ?>
          <li class="flex items-center justify-between py-4 px-5"
              data-search="<?= htmlspecialchars($dataSearch, ENT_QUOTES) ?>">
            <div class="flex items-center">
              <svg class="icon" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6"/>
              </svg>
              <a href="<?= htmlspecialchars($item['name']) ?>"
                 class="text-[15px] hover:text-[var(--trust-link-hover)] transition-colors">
                <?= $name ?>
              </a>
            </div>
            <span class="text-xs text-gray-500"><?= $sizeMB ?> MB</span>
          </li>
        <?php endif;
      endforeach; ?>
    </ul>
  </main>

  <footer class="mt-12 text-[11px] text-gray-500">
    © Trusted Brands Group – Trust LATAM
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const input = document.getElementById('searchInput');
      const button = document.getElementById('searchButton');
      const items  = Array.from(document.querySelectorAll('main ul li'));

      function clearHighlights() {
        items.forEach(function (li) {
          li.classList.remove('highlight');
        });
      }

      function searchItem() {
        const term = input.value.trim().toLowerCase();
        if (!term) {
          clearHighlights();
          return;
        }

        clearHighlights();

        let match = null;
        for (const li of items) {
          const data = li.getAttribute('data-search') || li.textContent.toLowerCase();
          if (data.indexOf(term) !== -1) {
            match = li;
            break;
          }
        }

        if (match) {
          match.classList.add('highlight');
          match.scrollIntoView({ behavior: 'smooth', block: 'center' });
          setTimeout(function () {
            match.classList.remove('highlight');
          }, 3000);
        } else {
          alert('No items found for: ' + term);
        }
      }

      if (button && input) {
        button.addEventListener('click', searchItem);
        input.addEventListener('keydown', function (e) {
          if (e.key === 'Enter') {
            searchItem();
          }
        });
      }
    });
  </script>
</body>
</html>