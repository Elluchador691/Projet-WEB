<?php
/* ============================================================
   auth.php — Connexion & Inscription
   Gère les deux actions via le paramètre POST "action"
   ============================================================ */

session_start();
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

// Récupérer l'action demandée
$action = $_POST['action'] ?? '';

match ($action) {
    'inscription' => handleInscription(),
    'connexion'   => handleConnexion(),
    'deconnexion' => handleDeconnexion(),
    default       => jsonError('Action inconnue.', 400)
};


/* ============================================================
   INSCRIPTION
   ============================================================ */
function handleInscription(): void {
    $pdo = getDB();

    // --- Récupérer et nettoyer les données ---
    $prenom = trim($_POST['prenom'] ?? '');
    $nom    = trim($_POST['nom']    ?? '');
    $email  = trim($_POST['email']  ?? '');
    $mdp    = $_POST['mot_de_passe']         ?? '';
    $mdp2   = $_POST['mot_de_passe_confirm'] ?? '';
    $role   = $_POST['role'] ?? 'etudiant';

    // --- Validation ---
    $errors = [];

    if (empty($prenom))            $errors['prenom'] = 'Le prénom est requis.';
    if (empty($nom))               $errors['nom']    = 'Le nom est requis.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                                   $errors['email']  = 'Email invalide.';
    if (strlen($mdp) < 8)          $errors['mot_de_passe'] = 'Minimum 8 caractères.';
    if ($mdp !== $mdp2)            $errors['mot_de_passe_confirm'] = 'Les mots de passe ne correspondent pas.';
    if (!in_array($role, ['etudiant', 'recruteur']))
                                   $errors['role']   = 'Rôle invalide.';

    if (!empty($errors)) {
        jsonError('Formulaire invalide.', 422, $errors);
        return;
    }

    // --- Vérifier si l'email existe déjà ---
    $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonError('Formulaire invalide.', 422, ['email' => 'Cet email est déjà utilisé.']);
        return;
    }

    // --- Hasher le mot de passe et insérer ---
    $hash = password_hash($mdp, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare(
        'INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$prenom, $nom, $email, $hash, $role]);

    $userId = (int) $pdo->lastInsertId();

    // --- Démarrer la session ---
    startSession($userId, $prenom, $nom, $email, $role);

    jsonSuccess([
        'message'  => 'Compte créé avec succès !',
        'redirect' => $role === 'recruteur' ? 'dashboard.php' : 'index.php'
    ]);
}


/* ============================================================
   CONNEXION
   ============================================================ */
function handleConnexion(): void {
    $pdo = getDB();

    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    // --- Validation basique ---
    if (empty($email) || empty($mdp)) {
        jsonError('Email et mot de passe requis.', 422);
        return;
    }

    // --- Chercher l'utilisateur ---
    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // --- Vérifier le mot de passe ---
    if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
        jsonError('Formulaire invalide.', 422, [
            'email' => 'Email ou mot de passe incorrect.'
        ]);
        return;
    }

    // --- Démarrer la session ---
    startSession($user['id'], $user['prenom'], $user['nom'], $user['email'], $user['role']);

    jsonSuccess([
        'message'  => 'Connexion réussie !',
        'redirect' => $user['role'] === 'recruteur' ? 'dashboard.php' : 'index.php'
    ]);
}


/* ============================================================
   DÉCONNEXION
   ============================================================ */
function handleDeconnexion(): void {
    $_SESSION = [];
    session_destroy();
    jsonSuccess(['message' => 'Déconnecté.', 'redirect' => 'index.php']);
}


/* ============================================================
   HELPERS
   ============================================================ */

/** Initialise les variables de session après connexion/inscription */
function startSession(int $id, string $prenom, string $nom, string $email, string $role): void {
    session_regenerate_id(true); // Prévenir la fixation de session
    $_SESSION['user_id']    = $id;
    $_SESSION['user_prenom'] = $prenom;
    $_SESSION['user_nom']   = $nom;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role']  = $role;
}

/** Retourne une réponse JSON de succès */
function jsonSuccess(array $data): void {
    echo json_encode(['success' => true, ...$data]);
    exit;
}

/** Retourne une réponse JSON d'erreur */
function jsonError(string $message, int $code = 400, array $errors = []): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message, 'errors' => $errors]);
    exit;
}

ss
