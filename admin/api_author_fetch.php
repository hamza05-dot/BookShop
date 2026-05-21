<?php
// api_author_fetch.php — place in your admin/ folder
header('Content-Type: application/json');

$authorKey = trim($_GET['key']   ?? '');  // e.g. /authors/OL34184A
$coverId   = trim($_GET['cover'] ?? '');  // book cover id to download
$coverDir  = dirname(__DIR__) . '/uploads/book-covers/';
$authorDir = dirname(__DIR__) . '/uploads/authors/';

$response = [];

$ctx = stream_context_create(['http' => [
    'timeout' => 10,
    'header'  => "User-Agent: BookShopAdmin/1.0\r\n"
]]);

// ── 1. Download book cover ─────────────────────────────────
if ($coverId) {
    $coverUrl = "https://covers.openlibrary.org/b/id/{$coverId}-M.jpg";
    $filename = time() . '_cover_' . $coverId . '.jpg';
    $savePath = $coverDir . $filename;

    $imgData = @file_get_contents($coverUrl, false, $ctx);
    if ($imgData && strlen($imgData) > 500) {
        if (!is_dir($coverDir)) mkdir($coverDir, 0755, true);
        file_put_contents($savePath, $imgData);
        $response['coverFile']    = $filename;
        $response['coverPreview'] = '../uploads/book-covers/' . $filename;
    } else {
        $response['coverFile'] = '';
    }
}

// ── 2. Fetch author details ────────────────────────────────
if ($authorKey) {
    $authorUrl = "https://openlibrary.org" . $authorKey . ".json";
    $raw = @file_get_contents($authorUrl, false, $ctx);

    if ($raw) {
        $author = json_decode($raw, true);

        $name      = $author['name']       ?? '';
        $birthDate = $author['birth_date'] ?? '';
        $deathDate = $author['death_date'] ?? '';
        $photoId   = !empty($author['photos']) ? $author['photos'][0] : null;

        // ── Biography ──────────────────────────────────────
        // Open Library stores bio in 'bio' field (string or object)
        $bio = '';
        if (!empty($author['bio'])) {
            $bio = is_array($author['bio'])
                ? ($author['bio']['value'] ?? '')
                : $author['bio'];
        }

        // If no bio, try fetching from the works list
        if (!$bio) {
            $worksUrl = "https://openlibrary.org" . $authorKey . "/works.json?limit=1";
            $worksRaw = @file_get_contents($worksUrl, false, $ctx);
            if ($worksRaw) {
                $works = json_decode($worksRaw, true);
                if (!empty($works['entries'][0]['description'])) {
                    $d = $works['entries'][0]['description'];
                    $bio = is_array($d) ? ($d['value'] ?? '') : $d;
                }
            }
        }

        // ── Parse birth date to YYYY-MM-DD ─────────────────
        $birthDateFormatted = '';
        if ($birthDate) {
            // Handle formats like "29 June 1892" or "1892" or "1892-06-29"
            $ts = @strtotime($birthDate);
            if ($ts && $ts > 0) {
                $birthDateFormatted = date('Y-m-d', $ts);
            } elseif (preg_match('/(\d{4})/', $birthDate, $m)) {
                $birthDateFormatted = $m[1] . '-01-01'; // fallback: year only
            }
        }

        // ── Split name into first / last ───────────────────
        $parts  = explode(' ', trim($name));
        $prenom = array_shift($parts);
        $nom    = implode(' ', $parts);

        // ── Download author photo ──────────────────────────
        $authorPhotoFile = '';
        if ($photoId && $photoId > 0) {
            $photoUrl = "https://covers.openlibrary.org/a/id/{$photoId}-M.jpg";
            $filename = time() . '_author_' . $photoId . '.jpg';
            $savePath = $authorDir . $filename;
            $imgData  = @file_get_contents($photoUrl, false, $ctx);
            if ($imgData && strlen($imgData) > 500) {
                if (!is_dir($authorDir)) mkdir($authorDir, 0755, true);
                file_put_contents($savePath, $imgData);
                $authorPhotoFile = $filename;
            }
        }

        $response['author'] = [
            'name'         => $name,
            'prenom'       => $prenom,
            'nom'          => $nom,
            'bio'          => $bio,                 // ← full biography
            'birthDate'    => $birthDateFormatted,  // ← YYYY-MM-DD ready for <input type="date">
            'birthDateRaw' => $birthDate,           // ← original string e.g. "29 June 1892"
            'deathDate'    => $deathDate,
            'status'       => $deathDate ? 'decede' : 'vivant',
            'photoFile'    => $authorPhotoFile,
            'photoPreview' => $authorPhotoFile ? '../uploads/authors/' . $authorPhotoFile : '',
        ];
    }
}

echo json_encode($response);
exit;