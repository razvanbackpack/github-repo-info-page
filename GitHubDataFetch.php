<?php

class GitHubDataFetch {
    private string $token;
    private string $owner;
    private string $repo;
    private string $cacheFile;
    private string $logFile;

    public function __construct(string $token, string $owner, string $repo, string $cacheFile) {
        $this->token = $token;
        $this->owner = $owner;
        $this->repo = $repo;
        $this->cacheFile = $cacheFile;
        $this->logFile = __DIR__ . '/github_fetch.log';
        $this->log("GitHubDataFetch initialized");
    }

    public function getData(): array {
        $this->log("getData() called");
        try {
            if ($this->shouldUpdateCache()) {
                $this->log("Attempting to fetch new data from GitHub");
                $data = $this->fetchDataFromGitHub();
                $this->saveDataToCache($data);
            } else {
                $this->log("Loading data from cache");
                $data = $this->loadDataFromCache();
            }
        } catch (Exception $e) {
            $this->log("Error in getData: " . $e->getMessage());
            return ['error' => 'Unable to fetch data from GitHub and no valid cached data available. Error: ' . $e->getMessage()];
        }
        return $data;
    }

    private function shouldUpdateCache(): bool {
        $this->log("Checking if cache should be updated");
        if (!file_exists($this->cacheFile) || filesize($this->cacheFile) === 0) {
            $this->log("Cache file does not exist or is empty. Will fetch new data.");
            return true;
        }
        $fileTime = filemtime($this->cacheFile);
        $shouldUpdate = (time() - $fileTime) > (12 * 3600);
        $this->log("Cache is " . ($shouldUpdate ? "outdated" : "current") . ". Last updated: " . date('Y-m-d H:i:s', $fileTime));
        return $shouldUpdate;
    }

    private function fetchDataFromGitHub(): array {
        $this->log("Fetching data from GitHub");
        $data = [];

        // Fetch repository data
        $repoData = $this->makeGitHubRequest("/repos/{$this->owner}/{$this->repo}");
        $data['stars'] = $repoData['stargazers_count'] ?? 0;
        $data['watchers'] = $repoData['subscribers_count'] ?? 0;
        $data['forks'] = $repoData['forks_count'] ?? 0;
        $data['issues'] = $repoData['open_issues_count'] ?? 0;
        $data['name'] = $repoData['name'] ?? '';
        $data['description'] = $repoData['description'] ?? '';
        $data['github_url'] = $repoData['html_url'] ?? '';

        // Fetch latest release (if any)
        try {
            $releaseData = $this->makeGitHubRequest("/repos/{$this->owner}/{$this->repo}/releases/latest");
            $data['latest_version'] = $releaseData['tag_name'] ?? '';
        } catch (Exception $e) {
            $this->log("No releases found: " . $e->getMessage());
            $data['latest_version'] = 'No releases';
        }

        // Fetch README
        $readmeData = $this->makeGitHubRequest("/repos/{$this->owner}/{$this->repo}/readme");
        $data['readme'] = base64_decode($readmeData['content'] ?? '');

        // Fetch composer.json
        try {
            $composerData = $this->makeGitHubRequest("/repos/{$this->owner}/{$this->repo}/contents/composer.json");
            $composerJson = json_decode(base64_decode($composerData['content'] ?? ''), true);
            $data['type'] = $composerJson['type'] ?? '';
            $data['license'] = $composerJson['license'] ?? '';
            $data['requirements'] = $composerJson['require'] ?? [];
            $data['dev_requirements'] = $composerJson['require-dev'] ?? [];
        } catch (Exception $e) {
            $this->log("No composer.json found: " . $e->getMessage());
            $data['type'] = 'N/A';
            $data['license'] = 'N/A';
            $data['requirements'] = [];
            $data['dev_requirements'] = [];
        }

        // Fetch latest builds (using releases as proxy)
        try {
            $releasesData = $this->makeGitHubRequest("/repos/{$this->owner}/{$this->repo}/releases?per_page=4");
            $data['latest_builds'] = array_map(function($release) {
                return ['tag' => $release['tag_name'], 'url' => $release['html_url']];
            }, $releasesData);
        } catch (Exception $e) {
            $this->log("No releases found for builds: " . $e->getMessage());
            $data['latest_builds'] = [];
        }

        $this->log("Data fetched successfully from GitHub");
        return $data;
    }

    private function makeGitHubRequest(string $endpoint): array {
        $this->log("Making GitHub API request to: $endpoint");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.github.com" . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Script');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: token {$this->token}",
            'Accept: application/vnd.github.v3+json',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->log("cURL Error: $error");
            throw new Exception("cURL Error: $error");
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->log("GitHub API request failed with status code: $httpCode. Response: $response");
            $errorDetails = json_decode($response, true);
            $errorMessage = $errorDetails['message'] ?? 'Unknown error';
            throw new Exception("GitHub API request failed. Status code: $httpCode. Error: $errorMessage. Endpoint: $endpoint");
        }

        $this->log("GitHub API request successful");
        return json_decode($response, true);
    }

    private function saveDataToCache(array $data): void {
        $this->log("Attempting to save data to cache");
        $jsonData = json_encode($data);
        if ($jsonData === false) {
            $this->log("Failed to encode data to JSON");
            throw new Exception("Failed to encode data to JSON");
        }
        $result = file_put_contents($this->cacheFile, $jsonData);
        if ($result === false) {
            $this->log("Failed to write to cache file. Check permissions.");
            throw new Exception("Failed to write to cache file");
        }
        $this->log("Successfully saved data to cache");
    }

    private function loadDataFromCache(): array {
        $this->log("Attempting to load data from cache");
        if (!file_exists($this->cacheFile) || filesize($this->cacheFile) === 0) {
            $this->log("Cache file does not exist or is empty");
            throw new Exception("Cache file does not exist or is empty");
        }
        $jsonData = file_get_contents($this->cacheFile);
        if ($jsonData === false) {
            $this->log("Failed to read from cache file. Check permissions.");
            throw new Exception("Failed to read from cache file");
        }
        $data = json_decode($jsonData, true);
        if ($data === null) {
            $this->log("Failed to decode JSON data from cache");
            throw new Exception("Failed to decode JSON data from cache");
        }
        $this->log("Successfully loaded data from cache");
        return $data;
    }

    private function log(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        if(LOG) file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}