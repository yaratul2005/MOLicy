<?php

namespace Admin;

use Core\Database;
use Core\Middleware;

class UpdateCenter {

    private const GITHUB_API = 'https://api.github.com/repos/yaratul2005/antigravity-forum/releases/latest';
    private const MANIFEST   = ROOT_PATH . '/updates/manifest.json';

    public function __construct() {
        Middleware::requireAdmin();
    }

    public function index(): void {
        $local   = $this->getLocalManifest();
        $remote  = null;
        $hasUpdate = false;
        $changelog = '';

        // Check remote (cached for 5 minutes)
        $cacheFile = ROOT_PATH . '/storage/cache/update_check.json';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 300)) {
            $remote = json_decode(file_get_contents($cacheFile), true);
        } else {
            $remote = $this->fetchRemote();
            if ($remote) {
                file_put_contents($cacheFile, json_encode($remote));
            }
        }

        if ($remote && isset($remote['tag_name'])) {
            $hasUpdate = version_compare(
                ltrim($remote['tag_name'], 'v'),
                $local['version'],
                '>'
            );
            $changelog = $remote['body'] ?? '';
        }

        require ROOT_PATH . '/admin/views/update-center.php';
    }

    /**
     * Perform the update process.
     */
    public function perform(): void {
        Middleware::verifyCSRF();
        header('Content-Type: application/json');

        set_time_limit(120);
        $remote = $this->fetchRemote();
        if (!$remote || !isset($remote['zipball_url'])) {
            echo json_encode(['error' => 'Cannot fetch release info.']); return;
        }

        $steps  = [];
        $tmpZip = ROOT_PATH . '/storage/tmp/agf-update.zip';
        $tmpDir = ROOT_PATH . '/storage/tmp/agf-update/';

        // Step 1: Download ZIP
        if (!is_dir(dirname($tmpZip))) mkdir(dirname($tmpZip), 0755, true);
        $zip = $this->download($remote['zipball_url']);
        if (!$zip) {
            echo json_encode(['error' => 'Download failed.']); return;
        }
        file_put_contents($tmpZip, $zip);
        $steps[] = '✅ Downloaded release ZIP';

        // Step 2: Extract
        if (is_dir($tmpDir)) $this->rmdir($tmpDir);
        mkdir($tmpDir, 0755, true);
        $za = new \ZipArchive();
        if ($za->open($tmpZip) !== true) {
            echo json_encode(['error' => 'Failed to open ZIP.']); return;
        }
        $za->extractTo($tmpDir);
        $za->close();
        $steps[] = '✅ Extracted update files';

        // Step 3: Backup current core, modules, themes
        $backupDir = ROOT_PATH . '/storage/backups/v' . $this->getLocalManifest()['version'] . '-' . date('Ymd-His') . '/';
        mkdir($backupDir, 0755, true);
        foreach (['core', 'modules', 'themes'] as $dir) {
            $this->copyDir(ROOT_PATH . "/{$dir}", $backupDir . $dir);
        }
        $steps[] = '✅ Backed up current version';

        // Step 4: Run migration scripts
        $migrationsDir = $tmpDir . 'migrations/';
        if (is_dir($migrationsDir)) {
            $local        = $this->getLocalManifest();
            $schemaVer    = (int)($local['schema_version'] ?? 0);
            $sqlFiles     = glob($migrationsDir . '*.sql');
            sort($sqlFiles);
            $db = Database::getInstance();
            foreach ($sqlFiles as $file) {
                preg_match('/(\d+)_/', basename($file), $m);
                $fileVer = isset($m[1]) ? (int)$m[1] : 0;
                if ($fileVer > $schemaVer) {
                    $sql = file_get_contents($file);
                    foreach (explode(';', $sql) as $statement) {
                        $statement = trim($statement);
                        if ($statement) $db->query($statement);
                    }
                }
            }
        }
        $steps[] = '✅ Ran database migrations';

        // Step 5: Swap files
        // Find the actual extracted subdirectory (GitHub adds a random prefix)
        $subDirs = glob($tmpDir . '*', GLOB_ONLYDIR);
        $srcDir  = $subDirs ? $subDirs[0] : $tmpDir;
        foreach (['core', 'modules', 'themes', 'admin', 'api'] as $dir) {
            if (is_dir($srcDir . '/' . $dir)) {
                $this->copyDir($srcDir . '/' . $dir, ROOT_PATH . '/' . $dir);
            }
        }
        $steps[] = '✅ Swapped in new files';

        // Step 6: Update manifest
        $newVersion = ltrim($remote['tag_name'], 'v');
        $manifest   = $this->getLocalManifest();
        $manifest['version']        = $newVersion;
        $manifest['build']          = date('Ymd');
        $manifest['last_updated']   = date('c');
        $manifest['schema_version'] = $manifest['schema_version'] + 1;
        file_put_contents(self::MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT));
        $steps[] = '✅ Updated manifest.json';

        // Cleanup
        @unlink($tmpZip);
        $this->rmdir($tmpDir);

        echo json_encode(['success' => true, 'steps' => $steps, 'version' => $newVersion]);
    }

    private function fetchRemote(): ?array {
        $ctx = stream_context_create(['http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: AntiGravity-Forum-Updater/1.0\r\n",
            'timeout' => 10,
        ]]);
        $res = @file_get_contents(self::GITHUB_API, false, $ctx);
        return $res ? json_decode($res, true) : null;
    }

    private function download(string $url): ?string {
        $ctx = stream_context_create(['http' => [
            'method'          => 'GET',
            'header'          => "User-Agent: AntiGravity-Forum-Updater/1.0\r\n",
            'follow_location' => true,
            'timeout'         => 60,
        ]]);
        $data = @file_get_contents($url, false, $ctx);
        return $data ?: null;
    }

    private function getLocalManifest(): array {
        if (!file_exists(self::MANIFEST)) {
            return ['version' => '1.0.0', 'build' => '20250422', 'schema_version' => 1,
                    'channel' => 'stable', 'installed_modules' => [], 'last_updated' => ''];
        }
        return json_decode(file_get_contents(self::MANIFEST), true) ?? [];
    }

    private function copyDir(string $src, string $dst): void {
        if (!is_dir($src)) return;
        if (!is_dir($dst)) mkdir($dst, 0755, true);
        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') continue;
            $s = $src . '/' . $item;
            $d = $dst . '/' . $item;
            is_dir($s) ? $this->copyDir($s, $d) : copy($s, $d);
        }
    }

    private function rmdir(string $dir): void {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}

// Make Database accessible here (not in namespace)
use Core\Database;
