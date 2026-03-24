<?php
/* ============================================================
   send_candidature.php — Traitement du formulaire de candidature
   - Valide les données
   - Sauvegarde en base de données
   - Upload le CV
   - Envoie un email au recruteur ET à l'étudiant
   ============================================================ */

session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mail.php';

header('Content-Type: application/json');

// Accepter uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Méthode non autorisée.']));
}

// ─── 1. RÉCUPÉRER ET NETTOYER LES DONNÉES ───────────────────
$prenom    = trim($_POST['prenom']    ?? '');
$nom       = trim($_POST['nom']       ?? '');
$email     = trim($_POST['email']     ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$message   = trim($_POST['message']   ?? '');
$offre_id  = (int) ($_POST['offre_id'] ?? 0);

// ─── 2. VALIDATION ──────────────────────────────────────────
$errors = [];

if (empty($prenom))                         $errors['prenom']  = 'Le prénom est requis.';
if (empty($nom))                            $errors['nom']     = 'Le nom est requis.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                                            $errors['email']   = 'Adresse email invalide.';
if (empty($message))                        $errors['message'] = 'La lettre de motivation est requise.';
if (strlen($message) > 500)                 $errors['message'] = 'Maximum 500 caractères.';
if ($offre_id <= 0)                         $errors['offre']   = 'Offre introuvable.';

// Vérifier que l'offre existe
if ($offre_id > 0) {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT id, titre, entreprise FROM offres WHERE id = ? AND actif = 1');
    $stmt->execute([$offre_id]);
    $offre = $stmt->fetch();
    if (!$offre) $errors['offre'] = 'Cette offre n\'existe plus.';
}

// ─── 3. UPLOAD DU CV ────────────────────────────────────────
$cv_filename = null;

if (!isset($_FILES['cv']) || $_FILES['cv']['error'] === UPLOAD_ERR_NO_FILE) {
    $errors['cv'] = 'Le CV est requis.';
} elseif ($_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
    $errors['cv'] = 'Erreur lors de l\'upload du CV.';
} else {
    $file    = $_FILES['cv'];
    $maxSize = 5 * 1024 * 1024; // 5 Mo
    $allowed = ['application/pdf', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

    if ($file['size'] > $maxSize) {
        $errors['cv'] = 'Le CV ne doit pas dépasser 5 Mo.';
    } elseif (!in_array($file['type'], $allowed)) {
        $errors['cv'] = 'Format non supporté. PDF ou Word uniquement.';
    } else {
        // Générer un nom de fichier unique et sécurisé
        $ext         = pathinfo($file['name'], PATHINFO_EXTENSION);
        $cv_filename = uniqid('cv_', true) . '.' . strtolower($ext);
        $uploadDir   = __DIR__ . '/uploads/cv/';

        // Créer le dossier si nécessaire
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $cv_filename)) {
            $errors['cv'] = 'Impossible de sauvegarder le CV.';
            $cv_filename  = null;
        }
    }
}

// Retourner les erreurs si besoin
if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Formulaire invalide.', 'errors' => $errors]);
    exit;
}

// ─── 4. SAUVEGARDER EN BASE DE DONNÉES ──────────────────────
$pdo  = getDB();
$stmt = $pdo->prepare(
    'INSERT INTO candidatures (offre_id, prenom, nom, email, telephone, message, cv_filename)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$offre_id, $prenom, $nom, $email, $telephone, $message, $cv_filename]);

// ─── 5. ENVOYER LES EMAILS ──────────────────────────────────

// Email au recruteur
sendEmailRecruteur($offre, $prenom, $nom, $email, $telephone, $message, $cv_filename);

// Email de confirmation à l'étudiant
sendEmailEtudiant($email, $prenom, $offre);

// ─── 6. RÉPONSE SUCCÈS ──────────────────────────────────────
echo json_encode([
    'success' => true,
    'message' => 'Candidature envoyée avec succès !'
]);
exit;


/* ============================================================
   FONCTIONS D'ENVOI D'EMAIL
   Utilise mail() natif de PHP (fonctionne en local avec XAMPP
   si tu configures un serveur SMTP dans php.ini)
   ============================================================ */

/**
 * Email envoyé au recruteur avec les infos du candidat
 */
function sendEmailRecruteur(array $offre, string $prenom, string $nom, string $email,
                             string $telephone, string $message, ?string $cv_filename): void {
    $to      = MAIL_RECRUTEUR;
    $subject = "Nouvelle candidature : {$prenom} {$nom} — {$offre['titre']}";

    $body = "
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family:sans-serif;color:#111827;max-width:600px;margin:auto;'>
        <div style='background:#4f46e5;padding:24px 32px;border-radius:12px 12px 0 0;'>
            <h1 style='color:white;margin:0;font-size:20px;'>Nouvelle candidature reçue</h1>
            <p style='color:#c7d2fe;margin:4px 0 0;font-size:14px;'>{$offre['titre']} · {$offre['entreprise']}</p>
        </div>
        <div style='background:#f9fafb;padding:28px 32px;border:1px solid #e5e7eb;border-top:none;'>
            <h2 style='font-size:16px;color:#374151;margin-bottom:16px;'>Informations du candidat</h2>
            <table style='width:100%;border-collapse:collapse;'>
                <tr><td style='padding:8px 0;color:#6b7280;font-size:14px;width:140px;'>Nom complet</td>
                    <td style='padding:8px 0;font-weight:600;font-size:14px;'>{$prenom} {$nom}</td></tr>
                <tr><td style='padding:8px 0;color:#6b7280;font-size:14px;'>Email</td>
                    <td style='padding:8px 0;font-size:14px;'><a href='mailto:{$email}' style='color:#4f46e5;'>{$email}</a></td></tr>
                <tr><td style='padding:8px 0;color:#6b7280;font-size:14px;'>Téléphone</td>
                    <td style='padding:8px 0;font-size:14px;'>" . ($telephone ?: 'Non renseigné') . "</td></tr>
                <tr><td style='padding:8px 0;color:#6b7280;font-size:14px;'>CV</td>
                    <td style='padding:8px 0;font-size:14px;'>" . ($cv_filename ? "Fichier joint : {$cv_filename}" : 'Aucun CV') . "</td></tr>
            </table>
            <hr style='border-color:#e5e7eb;margin:20px 0;'>
            <h2 style='font-size:16px;color:#374151;margin-bottom:12px;'>Lettre de motivation</h2>
            <p style='font-size:14px;line-height:1.7;color:#374151;background:white;padding:16px;border-radius:8px;border:1px solid #e5e7eb;'>
                " . nl2br(htmlspecialchars($message)) . "
            </p>
        </div>
        <div style='padding:20px 32px;text-align:center;font-size:12px;color:#9ca3af;'>
            © IdeaStage · <a href='http://localhost/ideastage/dashboard.php' style='color:#4f46e5;'>Voir toutes les candidatures</a>
        </div>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: {$email}\r\n";

    mail($to, $subject, $body, $headers);
}

/**
 * Email de confirmation envoyé à l'étudiant
 */
function sendEmailEtudiant(string $to, string $prenom, array $offre): void {
    $subject = "Votre candidature a bien été envoyée — {$offre['titre']}";

    $body = "
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family:sans-serif;color:#111827;max-width:600px;margin:auto;'>
        <div style='background:#4f46e5;padding:24px 32px;border-radius:12px 12px 0 0;'>
            <h1 style='color:white;margin:0;font-size:20px;'>Candidature envoyée !</h1>
            <p style='color:#c7d2fe;margin:4px 0 0;font-size:14px;'>IdeaStage</p>
        </div>
        <div style='background:#f9fafb;padding:28px 32px;border:1px solid #e5e7eb;border-top:none;'>
            <p style='font-size:15px;line-height:1.7;'>Bonjour <strong>{$prenom}</strong>,</p>
            <p style='font-size:14px;line-height:1.7;color:#374151;margin-top:12px;'>
                Votre candidature pour le poste de <strong>{$offre['titre']}</strong> chez
                <strong>{$offre['entreprise']}</strong> a bien été transmise.
            </p>
            <p style='font-size:14px;line-height:1.7;color:#374151;margin-top:12px;'>
                L'entreprise étudiera votre profil et vous contactera directement si votre candidature
                retient leur attention. En général, comptez <strong>5 à 10 jours ouvrés</strong> pour
                recevoir une réponse.
            </p>
            <div style='margin-top:24px;padding:16px 20px;background:#ede9fe;border-radius:10px;'>
                <p style='margin:0;font-size:14px;color:#4f46e5;font-weight:600;'>💡 Conseil</p>
                <p style='margin:6px 0 0;font-size:13.5px;color:#374151;'>
                    Continuez à postuler à d'autres offres ! Plus vous postulez, plus vous augmentez
                    vos chances de trouver le stage ou l'alternance idéal.
                </p>
            </div>
        </div>
        <div style='padding:20px 32px;text-align:center;font-size:12px;color:#9ca3af;'>
            © IdeaStage · La plateforme #1 pour les stages et alternances
        </div>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";

    mail($to, $subject, $body, $headers);
}