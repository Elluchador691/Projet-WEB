<?php
/* ============================================================
   config/mail.php — Configuration email
   En local : utilise ton vrai email Gmail/Outlook pour tester
   En production : remplace par les infos de ton hébergeur
   ============================================================ */
 
define('MAIL_FROM',    'noreply@ideastage.fr');
define('MAIL_FROM_NAME', 'IdeaStage');
define('MAIL_RECRUTEUR', 'recrutement@ideastage.fr'); // Email qui reçoit les candidatures
 
/* ============================================================
   Pour utiliser PHPMailer (recommandé pour Gmail) :
   1. Télécharge PHPMailer : https://github.com/PHPMailer/PHPMailer
   2. Place le dossier dans /vendor/phpmailer/
   3. Décommente la section PHPMailer dans send_candidature.php
   ============================================================ 123321323123123123123*/
 