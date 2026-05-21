<?php
// api_author_fetch.php — à placer dans le dossier admin/
header('Content-Type: application/json');

// On cache les erreurs PHP pour ne pas polluer le JSON
error_reporting(0);
ini_set('display_errors', 0);

// On récupère les paramètres envoyés par JavaScript
$authorKey = trim($_GET['key']   ?? '');
$coverId   = trim($_GET['cover'] ?? '');
$workKey   = trim($_GET['work']  ?? '');

// Dossiers où on va sauvegarder les images téléchargées
$coverDir  = dirname(__DIR__) . '/uploads/book-covers/';
$authorDir = dirname(__DIR__) . '/uploads/authors/';

// Réponse par défaut (vide)
$response = [
    'coverFile'    => '',
    'coverPreview' => '',
    'description'  => '',
    'author'       => null
];

// Contexte HTTP pour les appels API
$ctx = stream_context_create(['http' => [
    'timeout' => 12,
    'header'  => "User-Agent: BookShopAdmin/1.0\r\nAccept: application/json\r\n",
    'ignore_errors' => true,
]]);

// ── 1. Télécharger la couverture du livre ──────────────────────────────────
if ($coverId) {
    $coverUrl = "https://covers.openlibrary.org/b/id/{$coverId}-M.jpg";
    $imgData  = @file_get_contents($coverUrl, false, $ctx);

    // On vérifie que l'image est assez grande (pas une erreur 404)
    if ($imgData && strlen($imgData) > 500) {
        if (!is_dir($coverDir)) mkdir($coverDir, 0755, true);
        $filename = time() . '_cover_' . $coverId . '.jpg';
        file_put_contents($coverDir . $filename, $imgData);
        $response['coverFile']    = $filename;
        $response['coverPreview'] = '../uploads/book-covers/' . $filename;
    }
}

// ── 2. Récupérer la description du livre ──────────────────────────────────
if ($workKey) {
    // On s'assure que la clé commence bien par /
    if ($workKey[0] !== '/') $workKey = '/' . $workKey;

    $workUrl = "https://openlibrary.org" . $workKey . ".json";
    $raw = @file_get_contents($workUrl, false, $ctx);

    if ($raw) {
        $work = json_decode($raw, true);
        if (!empty($work['description'])) {
            $d = $work['description'];
            $response['description'] = is_array($d) ? ($d['value'] ?? '') : $d;
        }
    }
}

// ── 3. Récupérer les informations de l'auteur ─────────────────────────────
// CORRECTION : on vérifie que authorKey n'est pas vide avant d'appeler l'API
if ($authorKey) {

    // On s'assure que la clé commence bien par /
    if ($authorKey[0] !== '/') $authorKey = '/' . $authorKey;

    $authorUrl = "https://openlibrary.org" . $authorKey . ".json";
    $raw = @file_get_contents($authorUrl, false, $ctx);

    if ($raw) {
        $author = json_decode($raw, true);

        if ($author) {
            $name      = $author['name']       ?? '';
            $birthDate = $author['birth_date'] ?? '';
            $deathDate = $author['death_date'] ?? '';
            $photoId   = !empty($author['photos']) ? $author['photos'][0] : null;

            // Biographie de l'auteur (peut être tableau ou chaîne)
            $bio = '';
            if (!empty($author['bio'])) {
                $bio = is_array($author['bio']) ? ($author['bio']['value'] ?? '') : $author['bio'];
            }

            // Formater la date de naissance au format YYYY-MM-DD pour le champ HTML date
            $birthDateFormatted = '';
            if ($birthDate) {
                $ts = @strtotime($birthDate);
                if ($ts && $ts > 0) {
                    $birthDateFormatted = date('Y-m-d', $ts);
                } elseif (preg_match('/(\d{4})/', $birthDate, $m)) {
                    // Si on a seulement l'année, on met le 1er janvier
                    $birthDateFormatted = $m[1] . '-01-01';
                }
            }

            // Séparer le nom complet en prénom + nom
            $parts  = explode(' ', trim($name));
            $prenom = array_shift($parts);       // premier mot = prénom
            $nom    = implode(' ', $parts);       // le reste = nom de famille

            // ── Télécharger la photo de l'auteur ──────────────────────────
            $authorPhotoFile    = '';
            $authorPhotoPreview = '';

            if ($photoId && $photoId > 0) {
                $photoUrl = "https://covers.openlibrary.org/a/id/{$photoId}-M.jpg";
                $imgData  = @file_get_contents($photoUrl, false, $ctx);

                if ($imgData && strlen($imgData) > 500) {
                    if (!is_dir($authorDir)) mkdir($authorDir, 0755, true);
                    $filename = time() . '_author_' . $photoId . '.jpg';
                    file_put_contents($authorDir . $filename, $imgData);
                    $authorPhotoFile    = $filename;
                    $authorPhotoPreview = '../uploads/authors/' . $filename;
                }
            }

            // On construit la réponse auteur
            // CORRECTION : status utilise 'decede' (cohérent avec la BDD et le HTML)
            $response['author'] = [
                'name'         => $name,
                'prenom'       => $prenom,
                'nom'          => $nom,
                'bio'          => $bio,
                'birthDate'    => $birthDateFormatted,
                'deathDate'    => $deathDate,
                'status'       => $deathDate ? 'decede' : 'vivant', // 'decede' ou 'vivant'
                'photoFile'    => $authorPhotoFile,
                'photoPreview' => $authorPhotoPreview,
            ];
        }
    }
}

// On retourne tout en JSON
echo json_encode($response);
exit;