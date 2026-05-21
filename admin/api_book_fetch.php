<?php
// api_book_fetch.php — à placer dans le dossier admin/
header('Content-Type: application/json');

// On cache les erreurs PHP pour ne pas polluer le JSON
error_reporting(0);
ini_set('display_errors', 0);

// On récupère le mot de recherche envoyé par JavaScript
$query = trim($_GET['q'] ?? '');

// Si aucun mot de recherche, on retourne une erreur
if (!$query) {
    echo json_encode(['error' => 'Aucun mot de recherche fourni']);
    exit;
}

// Contexte HTTP pour les appels API (timeout + User-Agent)
$ctx = stream_context_create(['http' => [
    'timeout' => 10,
    'header'  => "User-Agent: BookShopAdmin/1.0\r\n"
]]);

// ── 1. Recherche sur Open Library ─────────────────────────────────────────
$searchUrl = "https://openlibrary.org/search.json?q=" . urlencode($query)
           . "&limit=6&fields=title,author_name,author_key,first_publish_year,cover_i,subject,key";

$raw = @file_get_contents($searchUrl, false, $ctx);

// Si l'API est inaccessible
if (!$raw) {
    echo json_encode(['error' => 'Impossible de contacter l\'API Open Library']);
    exit;
}

$data = json_decode($raw, true);

// Si aucun résultat trouvé
if (empty($data['docs'])) {
    echo json_encode(['results' => []]);
    exit;
}

$results = [];

// ── 2. On parcourt chaque livre trouvé ────────────────────────────────────
foreach ($data['docs'] as $doc) {

    $title      = $doc['title']              ?? '';
    $authors    = $doc['author_name']        ?? [];
    $year       = $doc['first_publish_year'] ?? '';
    $coverId    = $doc['cover_i']            ?? null;
    $subjects   = $doc['subject']            ?? [];
    $authorKeys = $doc['author_key']         ?? [];
    $workKey    = $doc['key']                ?? ''; // ex: /works/OL45883W

    // ── 3. On récupère la description complète du livre ───────────────────
    $description = '';
    if ($workKey) {
        $workUrl = "https://openlibrary.org" . $workKey . ".json";
        $workRaw = @file_get_contents($workUrl, false, $ctx);
        if ($workRaw) {
            $work = json_decode($workRaw, true);
            if (!empty($work['description'])) {
                // La description peut être un tableau ou une chaîne
                $description = is_array($work['description'])
                    ? ($work['description']['value'] ?? '')
                    : $work['description'];
            }
        }
    }

    // ── 4. On ajoute le livre au tableau de résultats ─────────────────────
    // CORRECTION : on ajoute 'workKey' pour que JavaScript puisse l'envoyer à api_author_fetch.php
    $results[] = [
        'title'       => $title,
        'authors'     => $authors,
        'authorKeys'  => $authorKeys,
        'year'        => $year,
        'coverId'     => $coverId,
        'workKey'     => $workKey, // ← AJOUT : manquait dans la version originale
        'thumbUrl'    => $coverId ? "https://covers.openlibrary.org/b/id/{$coverId}-S.jpg" : '',
        'coverUrl'    => $coverId ? "https://covers.openlibrary.org/b/id/{$coverId}-M.jpg" : '',
        'subjects'    => array_slice($subjects, 0, 8),
        'description' => $description,
    ];
}

// On retourne le JSON final
echo json_encode(['results' => $results]);
exit;