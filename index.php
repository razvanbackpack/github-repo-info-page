<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'func/GitHubDataFetch.php';
require_once 'func/config.php';

function debugLog($message) {
    if(DEBUG) echo $message . "<br>";
}

debugLog("Script started");

$cacheFile = 'assets/data.json';
$logFile = 'github_fetch.log';

// Check if we can create files
if (!file_exists($cacheFile)) {
    debugLog("Attempting to create $cacheFile");
    $result = file_put_contents($cacheFile, '');
    if ($result === false) {
        debugLog("Failed to create $cacheFile");
    } else {
        debugLog("Successfully created $cacheFile");
    }
}

if (!file_exists($logFile)) {
    debugLog("Attempting to create $logFile");
    $result = file_put_contents($logFile, '');
    if ($result === false) {
        debugLog("Failed to create $logFile");
    } else {
        debugLog("Successfully created $logFile");
    }
}

debugLog("Initializing GitHubDataFetch");
$fetcher = new GitHubDataFetch(GITHUB_TOKEN, OWNER, REPO, $cacheFile);
debugLog("Calling getData()");
$data = $fetcher->getData();


$errorMessage = $data['error'] ?? null;

if(DEBUG) {
    $debugInfo = "PHP Version: " . phpversion() . "<br>";
    $debugInfo .= "Current Directory: " . getcwd() . "<br>";
    $debugInfo .= "Cache file path: " . realpath($cacheFile) . "<br>";
    $debugInfo .= "Cache file exists: " . (file_exists($cacheFile) ? 'Yes' : 'No') . "<br>";
    if (file_exists($cacheFile)) {
        $debugInfo .= "Cache file permissions: " . substr(sprintf('%o', fileperms($cacheFile)), -4) . "<br>";
        $debugInfo .= "Cache file last modified: " . date("Y-m-d H:i:s", filemtime($cacheFile)) . "<br>";
        $debugInfo .= "Cache file size: " . filesize($cacheFile) . " bytes<br>";
    }
    $debugInfo .= "Log file path: " . realpath($logFile) . "<br>";
    $debugInfo .= "Log file exists: " . (file_exists($logFile) ? 'Yes' : 'No') . "<br>";
    if (file_exists($logFile)) {
        $debugInfo .= "Log file permissions: " . substr(sprintf('%o', fileperms($logFile)), -4) . "<br>";
        $debugInfo .= "Log file content:<br><pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
    }

    debugLog("Debug information collected");
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MicroMesh - Lightweight PHP Router</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/a11y-dark.min.css" rel="stylesheet">
    <link href="assets/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">MicroMesh</a>
            <span class="badge bg-primary" id="latest-version">Latest: <?= htmlspecialchars($data['latest_version'] ?? 'N/A') ?></span>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if(DEBUG): ?>
        <h2>Debug Information</h2>
        <div class="alert alert-info">
            <?php echo $debugInfo; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-warning" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($data) && !isset($data['error'])): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <h1 class="project-title"><?= htmlspecialchars($data['name'] ?? 'MicroMesh') ?></h1>
                    <p class="project-description"><?= htmlspecialchars($data['description'] ?? 'Lightweight PHP Router') ?></p>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <div class="col stats-item">
                                    <div class="stats-value"><?= htmlspecialchars($data['stars'] ?? '0') ?></div>
                                    <div class="stats-label">Stars</div>
                                </div>
                                <div class="col stats-item">
                                    <div class="stats-value"><?= htmlspecialchars($data['watchers'] ?? '0') ?></div>
                                    <div class="stats-label">Watchers</div>
                                </div>
                                <div class="col stats-item">
                                    <div class="stats-value"><?= htmlspecialchars($data['forks'] ?? '0') ?></div>
                                    <div class="stats-label">Forks</div>
                                </div>
                                <div class="col stats-item">
                                    <div class="stats-value"><?= htmlspecialchars($data['issues'] ?? '0') ?></div>
                                    <div class="stats-label">Open Issues</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Installation</h5>
                            <pre><code>composer require razvanbackpack/micromesh</code></pre>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">README</h5>
                            <!-- <button id="toggle-readme" class="btn btn-primary btn-sm mb-3">Toggle Raw/Formatted</button> -->
                            <div id="readme-content"><?= $data['readme'] ?? 'README not available.' ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Package Details</h5>
                            <ul class="list-unstyled">
                                <li><strong>Name:</strong> <?= htmlspecialchars($data['name'] ?? 'N/A') ?></li>
                                <li><strong>Description:</strong> <?= htmlspecialchars($data['description'] ?? 'N/A') ?></li>
                                <li><strong>Type:</strong> <?= htmlspecialchars($data['type'] ?? 'N/A') ?></li>
                                <li><strong>License:</strong> <?= htmlspecialchars($data['license'] ?? 'N/A') ?></li>
                            </ul>
                            <a href="<?= htmlspecialchars($data['github_url'] ?? '#') ?>" class="btn btn-primary btn-sm" id="github-link">View on GitHub</a>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Latest Builds</h5>
                            <ul id="builds-list" class="list-unstyled">
                                <?php foreach (($data['latest_builds'] ?? []) as $build): ?>
                                    <li><a href="<?= htmlspecialchars($build['url']) ?>"><?= htmlspecialchars($build['tag']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Requirements</h5>
                            <ul class="list-unstyled">
                                <?php foreach (($data['requirements'] ?? []) as $package => $version): ?>
                                    <li><strong><?= htmlspecialchars($package) ?>:</strong> <?= htmlspecialchars($version) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Dev Requirements</h5>
                            <ul class="list-unstyled">
                                <?php foreach (($data['dev_requirements'] ?? []) as $package => $version): ?>
                                    <li><strong><?= htmlspecialchars($package) ?>:</strong> <?= htmlspecialchars($version) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger" role="alert">
                No data available. Please check your configuration and try again.
            </div>
        <?php endif; ?>

            <div class = "row">
               <p>made by <a href="https://github.com/razvanbackpack">@razvanbackpack</a></p> 
            </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked@4.0.0/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script src="assets/script.js"></script>
</body>
</html>