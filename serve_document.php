<?php
/**
 * Authenticated document gatekeeper.
 *
 * All requests under /assets/uploads/ are rewritten here by
 * assets/uploads/.htaccess. This script enforces authentication and
 * per-record authorisation before streaming a stored file.
 *
 * It never executes uploaded content — files are streamed as
 * attachments / inline with an explicit, safe Content-Type.
 */

require_once __DIR__ . '/config/init.php';

/* ---------------------------------------------------------------------------
 * 1. Must be an authenticated session.
 * ------------------------------------------------------------------------ */
if (!Auth::check()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 Forbidden — authentication required.');
}

/* ---------------------------------------------------------------------------
 * 2. Resolve and contain the requested path.
 * ------------------------------------------------------------------------ */
$requested = $_GET['doc'] ?? ($_SERVER['DOC_PATH'] ?? '');
$requested = str_replace(["\0", "\\"], ['', '/'], (string) $requested);
$requested = ltrim($requested, '/');

// Reject any traversal attempt outright.
if ($requested === '' || strpos($requested, '..') !== false) {
    http_response_code(404);
    exit('404 Not Found');
}

$baseDir = realpath(rtrim(Config::get('upload_path'), '/\\'));
$fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . $requested);

if ($baseDir === false || $fullPath === false
    || strncmp($fullPath, $baseDir . DIRECTORY_SEPARATOR, strlen($baseDir) + 1) !== 0
    || !is_file($fullPath)) {
    http_response_code(404);
    exit('404 Not Found');
}

/* ---------------------------------------------------------------------------
 * 3. Per-record authorisation.
 *
 * Files tracked in the `documents` table are gated by the viewing
 * permission for their asset type and (for command-restricted users) by
 * the owning command. Untracked files (e.g. requisition attachments,
 * profile images) still require an authenticated session, which step 1
 * has already guaranteed.
 * ------------------------------------------------------------------------ */
$relPath = 'assets/uploads/' . str_replace('\\', '/', $requested);

$doc = Database::fetchOne(
    "SELECT asset_type, asset_id, file_name, file_mime
       FROM documents
      WHERE file_path = ? OR file_path = ? OR file_path LIKE ?",
    ['/' . $relPath, $relPath, '%/' . basename($requested)]
);

$permissionMap = [
    'land' => 'land.view',        'building' => 'buildings.view',
    'rented' => 'rented.view',    'project' => 'projects.view',
    'movable' => 'movable.view',  'ict' => 'ict.view',
    'vehicle' => 'fleet.view',    'aircraft' => 'fleet.view',
    'marine' => 'fleet.view',     'motorcycle' => 'fleet.view',
    'weapon' => 'weapons.view',   'ammunition' => 'ammunition.view',
    'audit' => 'audit.view',      'requisition' => 'requisition.view',
];

$commandTableMap = [
    'land' => 'land_assets',        'building' => 'building_assets',
    'rented' => 'rented_properties','project' => 'ongoing_projects',
    'movable' => 'movable_assets',  'ict' => 'ict_assets',
    'vehicle' => 'vehicle_assets',  'aircraft' => 'aircraft_assets',
    'marine' => 'marine_assets',    'motorcycle' => 'motorcycle_assets',
    'weapon' => 'weapons_inventory','ammunition' => 'ammunition_inventory',
];

$downloadName = basename($requested);
$mime = 'application/octet-stream';

if ($doc) {
    $type = $doc['asset_type'] ?? '';
    $needed = $permissionMap[$type] ?? null;

    if ($needed !== null && !Auth::can($needed) && !Auth::isSuperAdmin()) {
        http_response_code(403);
        exit('403 Forbidden');
    }

    // Command isolation: a command-restricted user may only read documents
    // that belong to an asset in their own command.
    if (Auth::isCommandRestricted() && isset($commandTableMap[$type]) && !empty($doc['asset_id'])) {
        $row = Database::fetchOne(
            "SELECT command_id FROM {$commandTableMap[$type]} WHERE id = ?",
            [$doc['asset_id']]
        );
        if ($row && (string) $row['command_id'] !== (string) Auth::commandId()) {
            http_response_code(403);
            exit('403 Forbidden');
        }
    }

    if (!empty($doc['file_name']))  { $downloadName = $doc['file_name']; }
    if (!empty($doc['file_mime']))  { $mime = $doc['file_mime']; }
}

/* ---------------------------------------------------------------------------
 * 4. Force a safe content type. Never trust a stored MIME for rendering
 *    executable/markup types inline.
 * ------------------------------------------------------------------------ */
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$safeInline = [
    'pdf'  => 'application/pdf',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'txt'  => 'text/plain; charset=utf-8',
];
$disposition = 'attachment';
if (isset($safeInline[$ext])) {
    $mime = $safeInline[$ext];
    $disposition = 'inline';
}

$downloadName = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName);

$isAvatar = (strpos($requested, 'avatars/') === 0 || strpos($mime, 'image/') === 0);
$fileMtime = filemtime($fullPath);
$fileSize = filesize($fullPath);
$etag = '"' . md5($fullPath . '_' . $fileMtime . '_' . $fileSize) . '"';
$lastModified = gmdate('D, d M Y H:i:s', $fileMtime) . ' GMT';

// Support browser HTTP 304 Not Modified cache validation
$ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
$ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';

if (($ifNoneMatch && trim($ifNoneMatch, ' "') === trim($etag, ' "')) ||
    ($ifModifiedSince && strtotime($ifModifiedSince) >= $fileMtime)) {
    http_response_code(304);
    header('ETag: ' . $etag);
    header('Last-Modified: ' . $lastModified);
    if ($isAvatar) {
        header('Cache-Control: private, max-age=604800, stale-while-revalidate=86400');
    } else {
        header('Cache-Control: private, max-age=3600');
    }
    exit;
}

header_remove('X-Powered-By');
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . $downloadName . '"');
header('Content-Length: ' . $fileSize);
header('X-Content-Type-Options: nosniff');
header('ETag: ' . $etag);
header('Last-Modified: ' . $lastModified);

if ($isAvatar) {
    // Enable 7-day browser caching for avatars with automatic revalidation
    header('Cache-Control: private, max-age=604800, stale-while-revalidate=86400');
} else {
    header('Content-Security-Policy: default-src \'none\'; sandbox');
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');
}

if (class_exists('AuditLogger') && $doc) {
    try {
        AuditLogger::log('DOCUMENT_VIEW', 'documents', $doc['asset_id'] ?? null, null,
            'Viewed document ' . $downloadName);
    } catch (Throwable $e) { /* logging must not block delivery */ }
}

while (ob_get_level() > 0) { ob_end_clean(); }
readfile($fullPath);
exit;
