<?php
/* ============================================================
   offres.php — API des offres de stage / alternance
   Retourne les offres depuis la base de données en JSON
   Paramètres GET supportés :
     - id        : récupérer une offre précise
     - type      : Stage | Alternance
     - ville     : filtrer par localisation
     - q         : recherche textuelle (titre, entreprise, compétences)
     - a_la_une  : 1 = uniquement les offres à la une
     - limite    : nombre de résultats (défaut 20)
     - page      : pagination (défaut 1)
   ============================================================ */

require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permet les requêtes depuis le front

$pdo = getDB();

// ─── Récupérer une offre par son ID ─────────────────────────
if (isset($_GET['id'])) {
    $id   = (int) $_GET['id'];
    $stmt = $pdo->prepare(
        'SELECT o.*, u.prenom AS recruteur_prenom, u.nom AS recruteur_nom
         FROM offres o
         JOIN utilisateurs u ON o.recruteur_id = u.id
         WHERE o.id = ? AND o.actif = 1'
    );
    $stmt->execute([$id]);
    $offre = $stmt->fetch();

    if (!$offre) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Offre introuvable.']);
        exit;
    }

    // Décoder les compétences JSON
    $offre['competences'] = json_decode($offre['competences'] ?? '[]', true);

    // Compter les candidatures pour cette offre
    $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM candidatures WHERE offre_id = ?');
    $stmtCount->execute([$id]);
    $offre['nb_candidatures'] = (int) $stmtCount->fetchColumn();

    echo json_encode(['success' => true, 'offre' => $offre]);
    exit;
}

// ─── Lister les offres avec filtres ─────────────────────────

// Paramètres de filtrage
$type     = $_GET['type']     ?? '';
$ville    = $_GET['ville']    ?? '';
$q        = $_GET['q']        ?? '';
$a_la_une = isset($_GET['a_la_une']) ? (int) $_GET['a_la_une'] : null;
$limite   = min((int) ($_GET['limite'] ?? 20), 50); // Max 50 par page
$page     = max((int) ($_GET['page']   ?? 1), 1);
$offset   = ($page - 1) * $limite;

// Construire la requête dynamiquement
$where  = ['o.actif = 1'];
$params = [];

if ($type && in_array($type, ['Stage', 'Alternance'])) {
    $where[]  = 'o.type = ?';
    $params[] = $type;
}

if ($ville) {
    $where[]  = 'o.localisation LIKE ?';
    $params[] = '%' . $ville . '%';
}

if ($q) {
    $where[]  = '(o.titre LIKE ? OR o.entreprise LIKE ? OR o.competences LIKE ? OR o.description LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

if ($a_la_une === 1) {
    $where[]  = 'o.a_la_une = 1';
}

$whereSQL = implode(' AND ', $where);

// Compter le total pour la pagination
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM offres o WHERE {$whereSQL}");
$stmtTotal->execute($params);
$total = (int) $stmtTotal->fetchColumn();

// Récupérer les offres
$stmtOffres = $pdo->prepare(
    "SELECT o.id, o.titre, o.entreprise, o.type, o.localisation, o.duree,
            o.salaire, o.teletravail, o.date_debut, o.competences,
            o.a_la_une, o.created_at,
            (SELECT COUNT(*) FROM candidatures c WHERE c.offre_id = o.id) AS nb_candidatures
     FROM offres o
     WHERE {$whereSQL}
     ORDER BY o.a_la_une DESC, o.created_at DESC
     LIMIT {$limite} OFFSET {$offset}"
);
$stmtOffres->execute($params);
$offres = $stmtOffres->fetchAll();

// Décoder les compétences JSON pour chaque offre
foreach ($offres as &$offre) {
    $offre['competences'] = json_decode($offre['competences'] ?? '[]', true);
}

echo json_encode([
    'success' => true,
    'total'   => $total,
    'page'    => $page,
    'limite'  => $limite,
    'pages'   => (int) ceil($total / $limite),
    'offres'  => $offres
]);