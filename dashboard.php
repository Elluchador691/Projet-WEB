<?php
/* ============================================================
   dashboard.php — Espace recruteur
   Accessible uniquement aux utilisateurs avec role = 'recruteur'
   Fonctionnalités :
     - Voir toutes ses offres publiées
     - Voir les candidatures reçues par offre
     - Changer le statut d'une candidature
     - Ajouter / supprimer une offre
   ============================================================ */

session_start();
require_once __DIR__ . '/config/db.php';

// ─── Vérifier que l'utilisateur est connecté et recruteur ───
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'recruteur') {
    header('Location: index.php?error=acces_interdit');
    exit;
}

$pdo         = getDB();
$recruteurId = (int) $_SESSION['user_id'];

// ─── Traiter les actions POST ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Supprimer une offre
    if ($action === 'supprimer_offre') {
        $offre_id = (int) ($_POST['offre_id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE offres SET actif = 0 WHERE id = ? AND recruteur_id = ?');
        $stmt->execute([$offre_id, $recruteurId]);
        header('Location: dashboard.php?msg=offre_supprimee');
        exit;
    }

    // Changer le statut d'une candidature
    if ($action === 'changer_statut') {
        $candidature_id = (int) ($_POST['candidature_id'] ?? 0);
        $statut         = $_POST['statut'] ?? '';
        $statutsValides = ['envoyée', 'vue', 'retenue', 'refusée'];

        if (in_array($statut, $statutsValides)) {
            // Vérifier que la candidature appartient bien à une offre de ce recruteur
            $stmt = $pdo->prepare(
                'UPDATE candidatures c
                 JOIN offres o ON c.offre_id = o.id
                 SET c.statut = ?
                 WHERE c.id = ? AND o.recruteur_id = ?'
            );
            $stmt->execute([$statut, $candidature_id, $recruteurId]);
        }
        header('Location: dashboard.php?msg=statut_mis_a_jour');
        exit;
    }

    // Publier une nouvelle offre
    if ($action === 'publier_offre') {
        $titre       = trim($_POST['titre']       ?? '');
        $entreprise  = trim($_POST['entreprise']  ?? '');
        $type        = $_POST['type']              ?? 'Stage';
        $localisation = trim($_POST['localisation'] ?? '');
        $duree       = trim($_POST['duree']        ?? '');
        $salaire     = (float) ($_POST['salaire']  ?? 0);
        $teletravail = trim($_POST['teletravail']  ?? '');
        $date_debut  = $_POST['date_debut']        ?? null;
        $description = trim($_POST['description']  ?? '');
        $competences = trim($_POST['competences']  ?? ''); // Séparées par des virgules
        $a_la_une    = isset($_POST['a_la_une']) ? 1 : 0;

        // Validation minimale
        if ($titre && $entreprise && $localisation && $description) {
            // Convertir les compétences en JSON
            $competencesArray = array_map('trim', explode(',', $competences));
            $competencesArray = array_filter($competencesArray);
            $competencesJson  = json_encode(array_values($competencesArray));

            $stmt = $pdo->prepare(
                'INSERT INTO offres
                    (recruteur_id, titre, entreprise, type, localisation, duree,
                     salaire, teletravail, date_debut, description, competences, a_la_une)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $recruteurId, $titre, $entreprise, $type, $localisation, $duree,
                $salaire ?: null, $teletravail ?: null, $date_debut ?: null,
                $description, $competencesJson, $a_la_une
            ]);
            header('Location: dashboard.php?msg=offre_publiee');
            exit;
        }
    }
}

// ─── Récupérer les offres du recruteur ───────────────────────
$stmtOffres = $pdo->prepare(
    'SELECT o.*,
            (SELECT COUNT(*) FROM candidatures c WHERE c.offre_id = o.id) AS nb_candidatures,
            (SELECT COUNT(*) FROM candidatures c WHERE c.offre_id = o.id AND c.statut = "envoyée") AS nb_nouvelles
     FROM offres o
     WHERE o.recruteur_id = ? AND o.actif = 1
     ORDER BY o.created_at DESC'
);
$stmtOffres->execute([$recruteurId]);
$offres = $stmtOffres->fetchAll();

// ─── Récupérer les dernières candidatures ────────────────────
$stmtCandidatures = $pdo->prepare(
    'SELECT c.*, o.titre AS offre_titre
     FROM candidatures c
     JOIN offres o ON c.offre_id = o.id
     WHERE o.recruteur_id = ?
     ORDER BY c.created_at DESC
     LIMIT 50'
);
$stmtCandidatures->execute([$recruteurId]);
$candidatures = $stmtCandidatures->fetchAll();

// ─── Statistiques globales ───────────────────────────────────
$stats = [
    'total_offres'       => count($offres),
    'total_candidatures' => count($candidatures),
    'nouvelles'          => count(array_filter($candidatures, fn($c) => $c['statut'] === 'envoyée')),
    'retenues'           => count(array_filter($candidatures, fn($c) => $c['statut'] === 'retenue')),
];

// Message flash
$msg = $_GET['msg'] ?? '';
$msgTextes = [
    'offre_supprimee'   => ['type' => 'success', 'texte' => '✓ Offre supprimée avec succès.'],
    'offre_publiee'     => ['type' => 'success', 'texte' => '✓ Offre publiée avec succès !'],
    'statut_mis_a_jour' => ['type' => 'success', 'texte' => '✓ Statut mis à jour.'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace recruteur — IdeaStage</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/style.css">
    <style>
        /* Styles spécifiques au dashboard */
        .dashboard-layout { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
        .sidebar-nav { background: #111827; padding: 24px 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        .sidebar-nav .logo { padding: 0 20px 24px; border-bottom: 1px solid #1f2937; margin-bottom: 16px; }
        .sidebar-nav .logo-text { color: white; font-size: 17px; font-weight: 700; }
        .sidebar-nav .logo-text span { color: #818cf8; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 20px; color: #9ca3af; font-size: 14px; font-weight: 500; text-decoration: none; border-radius: 0; transition: .15s; cursor: pointer; border: none; background: none; width: 100%; }
        .nav-item:hover { background: #1f2937; color: white; }
        .nav-item.active { background: #1f2937; color: white; border-left: 3px solid #818cf8; }
        .nav-item svg { flex-shrink: 0; }

        .dashboard-main { background: #f4f6fb; padding: 32px; overflow-y: auto; }
        .dash-header { margin-bottom: 28px; }
        .dash-header h1 { font-size: 24px; font-weight: 700; color: #111827; }
        .dash-header p { color: #6b7280; margin-top: 4px; font-size: 14px; }

        .flash { padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .flash.success { background: #d1fae5; color: #065f46; }
        .flash.error   { background: #fee2e2; color: #991b1b; }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        .stat-card { background: white; border-radius: 14px; padding: 20px; border: 1px solid #e5e7eb; }
        .stat-value { font-size: 28px; font-weight: 700; color: #111827; }
        .stat-label { font-size: 13px; color: #6b7280; margin-top: 4px; }
        .stat-card.blue .stat-value { color: #4f46e5; }
        .stat-card.green .stat-value { color: #10b981; }
        .stat-card.orange .stat-value { color: #f59e0b; }
        .stat-card.red .stat-value { color: #ef4444; }

        /* Section tabs */
        .section-tabs { display: flex; gap: 4px; margin-bottom: 20px; background: white; border-radius: 12px; padding: 6px; border: 1px solid #e5e7eb; width: fit-content; }
        .tab-btn { padding: 8px 18px; border-radius: 9px; border: none; font-size: 14px; font-weight: 500; cursor: pointer; font-family: inherit; transition: .15s; color: #6b7280; background: none; }
        .tab-btn.active { background: #4f46e5; color: white; }

        /* Table */
        .table-card { background: white; border-radius: 14px; border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 24px; }
        .table-header { padding: 18px 22px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between; }
        .table-header h2 { font-size: 16px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 20px; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #f3f4f6; background: #fafafa; }
        td { padding: 14px 20px; font-size: 14px; color: #374151; border-bottom: 1px solid #f9fafb; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        /* Badges statut */
        .badge-statut { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-envoyée  { background: #dbeafe; color: #1d4ed8; }
        .badge-vue      { background: #fef3c7; color: #92400e; }
        .badge-retenue  { background: #d1fae5; color: #065f46; }
        .badge-refusée  { background: #fee2e2; color: #991b1b; }

        /* Boutons action */
        .btn-sm { padding: 5px 12px; border-radius: 7px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; font-family: inherit; transition: .15s; }
        .btn-danger { background: #fee2e2; color: #ef4444; }
        .btn-danger:hover { background: #fecaca; }
        .btn-primary-sm { background: #4f46e5; color: white; }
        .btn-primary-sm:hover { background: #3730a3; }

        /* Formulaire nouvelle offre */
        .form-card { background: white; border-radius: 14px; border: 1px solid #e5e7eb; padding: 24px; margin-bottom: 24px; display: none; }
        .form-card.visible { display: block; }
        .form-card h2 { font-size: 17px; font-weight: 700; margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-full { grid-column: 1 / -1; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 5px; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 10px 12px; border: 1.5px solid #e5e7eb; border-radius: 9px;
            font-size: 14px; font-family: inherit; outline: none; transition: border-color .15s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { border-color: #4f46e5; }
        .checkbox-row { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500; }
        .checkbox-row input { width: auto; }
        .form-actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn-publier { background: #4f46e5; color: white; padding: 11px 24px; border-radius: 10px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; font-family: inherit; }
        .btn-annuler { background: #f3f4f6; color: #374151; padding: 11px 20px; border-radius: 10px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; font-family: inherit; }

        .select-statut { padding: 4px 8px; border: 1px solid #e5e7eb; border-radius: 7px; font-size: 12px; font-family: inherit; cursor: pointer; }
    </style>
</head>
<body>

<div class="dashboard-layout">

    <!-- SIDEBAR -->
    <nav class="sidebar-nav">
        <div class="logo">
            <div class="logo-text">Idea<span>Stage</span></div>
            <div style="font-size:12px;color:#6b7280;margin-top:4px;"><?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?></div>
        </div>

        <a href="dashboard.php" class="nav-item active">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            Tableau de bord
        </a>
        <a href="#offres" class="nav-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2"/></svg>
            Mes offres <span style="background:#4f46e5;color:white;border-radius:20px;padding:1px 7px;font-size:11px;margin-left:auto;"><?= $stats['total_offres'] ?></span>
        </a>
        <a href="#candidatures" class="nav-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            Candidatures <?php if ($stats['nouvelles'] > 0): ?><span style="background:#ef4444;color:white;border-radius:20px;padding:1px 7px;font-size:11px;margin-left:auto;"><?= $stats['nouvelles'] ?></span><?php endif; ?>
        </a>
        <a href="index.php" class="nav-item" style="margin-top:auto;position:absolute;bottom:24px;width:200px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Voir le site
        </a>
    </nav>

    <!-- CONTENU PRINCIPAL -->
    <main class="dashboard-main">

        <div class="dash-header">
            <h1>Tableau de bord</h1>
            <p>Bienvenue, <?= htmlspecialchars($_SESSION['user_prenom']) ?> 👋</p>
        </div>

        <!-- Message flash -->
        <?php if ($msg && isset($msgTextes[$msg])): ?>
            <div class="flash <?= $msgTextes[$msg]['type'] ?>">
                <?= $msgTextes[$msg]['texte'] ?>
            </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-value"><?= $stats['total_offres'] ?></div>
                <div class="stat-label">Offres actives</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-value"><?= $stats['total_candidatures'] ?></div>
                <div class="stat-label">Candidatures reçues</div>
            </div>
            <div class="stat-card red">
                <div class="stat-value"><?= $stats['nouvelles'] ?></div>
                <div class="stat-label">Nouvelles (non vues)</div>
            </div>
            <div class="stat-card green">
                <div class="stat-value"><?= $stats['retenues'] ?></div>
                <div class="stat-label">Candidatures retenues</div>
            </div>
        </div>

        <!-- FORMULAIRE NOUVELLE OFFRE -->
        <div class="form-card" id="form-offre">
            <h2>✏️ Publier une nouvelle offre</h2>
            <form method="POST" action="dashboard.php">
                <input type="hidden" name="action" value="publier_offre">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Titre du poste *</label>
                        <input type="text" name="titre" placeholder="Ex : Développeur React" required>
                    </div>
                    <div class="form-group">
                        <label>Entreprise *</label>
                        <input type="text" name="entreprise" placeholder="Ex : TechVision" required>
                    </div>
                    <div class="form-group">
                        <label>Type *</label>
                        <select name="type">
                            <option value="Stage">Stage</option>
                            <option value="Alternance">Alternance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Localisation *</label>
                        <input type="text" name="localisation" placeholder="Ex : Paris" required>
                    </div>
                    <div class="form-group">
                        <label>Durée</label>
                        <input type="text" name="duree" placeholder="Ex : 6 mois">
                    </div>
                    <div class="form-group">
                        <label>Salaire mensuel (€)</label>
                        <input type="number" name="salaire" placeholder="Ex : 1200" min="0">
                    </div>
                    <div class="form-group">
                        <label>Télétravail</label>
                        <input type="text" name="teletravail" placeholder="Ex : 2 jours/semaine">
                    </div>
                    <div class="form-group">
                        <label>Date de début</label>
                        <input type="date" name="date_debut">
                    </div>
                    <div class="form-group form-full">
                        <label>Description *</label>
                        <textarea name="description" rows="4" placeholder="Décrivez le poste..." required></textarea>
                    </div>
                    <div class="form-group form-full">
                        <label>Compétences (séparées par des virgules)</label>
                        <input type="text" name="competences" placeholder="React, Node.js, TypeScript">
                    </div>
                    <div class="form-group form-full">
                        <label class="checkbox-row">
                            <input type="checkbox" name="a_la_une" value="1">
                            Mettre cette offre "À la une"
                        </label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-publier">Publier l'offre</button>
                    <button type="button" class="btn-annuler" onclick="toggleForm()">Annuler</button>
                </div>
            </form>
        </div>

        <!-- MES OFFRES -->
        <div class="table-card" id="offres">
            <div class="table-header">
                <h2>Mes offres (<?= $stats['total_offres'] ?>)</h2>
                <button class="btn-sm btn-primary-sm" onclick="toggleForm()">+ Nouvelle offre</button>
            </div>
            <?php if (empty($offres)): ?>
                <p style="padding:24px;color:#6b7280;text-align:center;">Aucune offre publiée pour l'instant.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Ville</th>
                        <th>Candidatures</th>
                        <th>Nouvelles</th>
                        <th>Publiée le</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($offres as $o): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($o['titre']) ?></strong><br><span style="color:#6b7280;font-size:12px;"><?= htmlspecialchars($o['entreprise']) ?></span></td>
                        <td><?= htmlspecialchars($o['type']) ?></td>
                        <td><?= htmlspecialchars($o['localisation']) ?></td>
                        <td><?= (int)$o['nb_candidatures'] ?></td>
                        <td><?php if ($o['nb_nouvelles'] > 0): ?><span class="badge-statut badge-envoyée"><?= (int)$o['nb_nouvelles'] ?> nouvelle(s)</span><?php else: ?>—<?php endif; ?></td>
                        <td><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette offre ?')">
                                <input type="hidden" name="action" value="supprimer_offre">
                                <input type="hidden" name="offre_id" value="<?= $o['id'] ?>">
                                <button type="submit" class="btn-sm btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- CANDIDATURES -->
        <div class="table-card" id="candidatures">
            <div class="table-header">
                <h2>Candidatures reçues (<?= $stats['total_candidatures'] ?>)</h2>
            </div>
            <?php if (empty($candidatures)): ?>
                <p style="padding:24px;color:#6b7280;text-align:center;">Aucune candidature reçue pour l'instant.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Candidat</th>
                        <th>Offre</th>
                        <th>Email</th>
                        <th>CV</th>
                        <th>Reçue le</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidatures as $c): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></strong></td>
                        <td style="max-width:180px;"><span style="font-size:13px;"><?= htmlspecialchars($c['offre_titre']) ?></span></td>
                        <td><a href="mailto:<?= htmlspecialchars($c['email']) ?>" style="color:#4f46e5;font-size:13px;"><?= htmlspecialchars($c['email']) ?></a></td>
                        <td>
                            <?php if ($c['cv_filename']): ?>
                                <a href="uploads/cv/<?= htmlspecialchars($c['cv_filename']) ?>" target="_blank" class="btn-sm btn-primary-sm">📄 Voir</a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td style="font-size:13px;"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="changer_statut">
                                <input type="hidden" name="candidature_id" value="<?= $c['id'] ?>">
                                <select name="statut" class="select-statut" onchange="this.form.submit()">
                                    <option value="envoyée"  <?= $c['statut'] === 'envoyée'  ? 'selected' : '' ?>>Envoyée</option>
                                    <option value="vue"      <?= $c['statut'] === 'vue'      ? 'selected' : '' ?>>Vue</option>
                                    <option value="retenue"  <?= $c['statut'] === 'retenue'  ? 'selected' : '' ?>>Retenue</option>
                                    <option value="refusée"  <?= $c['statut'] === 'refusée'  ? 'selected' : '' ?>>Refusée</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
function toggleForm() {
    const form = document.getElementById('form-offre');
    form.classList.toggle('visible');
    if (form.classList.contains('visible')) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
</script>

</body>
</html>