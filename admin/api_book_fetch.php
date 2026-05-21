<?php
// api_book_fetch.php — place in your admin/ folder
header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
if (!$query) {
    echo json_encode(['error' => 'No query provided']);
    exit;
}

$ctx = stream_context_create(['http' => [
    'timeout' => 10,
    'header'  => "User-Agent: BookShopAdmin/1.0\r\n"
]]);

// ── 1. Search Open Library ────────────────────────────────
$searchUrl = "https://openlibrary.org/search.json?q=" . urlencode($query)
           . "&limit=6&fields=title,author_name,author_key,first_publish_year,cover_i,subject,key";

$raw = @file_get_contents($searchUrl, false, $ctx);
if (!$raw) {
    echo json_encode(['error' => 'Could not reach Open Library API']);
    exit;
}

$data = json_decode($raw, true);
if (empty($data['docs'])) {
    echo json_encode(['results' => []]);
    exit;
}

$results = [];

foreach ($data['docs'] as $doc) {
    $title      = $doc['title']              ?? '';
    $authors    = $doc['author_name']        ?? [];
    $year       = $doc['first_publish_year'] ?? '';
    $coverId    = $doc['cover_i']            ?? null;
    $subjects   = $doc['subject']            ?? [];
    $authorKeys = $doc['author_key']         ?? [];
    $workKey    = $doc['key']                ?? ''; // e.g. /works/OL45883W

    // ── 2. Fetch full work description ───────────────────
    $description = '';
    if ($workKey) {
        $workUrl = "https://openlibrary.org" . $workKey . ".json";
        $workRaw = @file_get_contents($workUrl, false, $ctx);
        if ($workRaw) {
            $work = json_decode($workRaw, true);
            if (!empty($work['description'])) {
                $description = is_array($work['description'])
                    ? ($work['description']['value'] ?? '')
                    : $work['description'];
            }
        }
    }

    $results[] = [
        'title'       => $title,
        'authors'     => $authors,
        'authorKeys'  => $authorKeys,
        'year'        => $year,
        'coverId'     => $coverId,
        'thumbUrl'    => $coverId ? "https://covers.openlibrary.org/b/id/{$coverId}-S.jpg" : '',
        'coverUrl'    => $coverId ? "https://covers.openlibrary.org/b/id/{$coverId}-M.jpg" : '',
        'subjects'    => array_slice($subjects, 0, 8),
        'description' => $description,   // ← full book summary
    ];
}

echo json_encode(['results' => $results]);
exit;