<?php
session_start();

// Carica le variabili dal file .env manualmente
function loadEnv($path) {
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

loadEnv(__DIR__ . '/shaker.env');
  // Nota: correggi il percorso
var_dump($_ENV); // Per vedere se le variabili sono caricate
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/PHPMailer/src/PHPMailer.php';
require 'vendor/PHPMailer/src/SMTP.php';
require 'vendor/PHPMailer/src/Exception.php';

$mail = new PHPMailer(true);

try {
    if (!isset($_POST['email'], $_POST['name'], $_POST['subject'], $_POST['message'])) {
        throw new Exception("Dati incompleti.");
    }

    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email non valida.");
    }

    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['EMAIL_USERNAME'];
    $mail->Password = $_ENV['EMAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;


    $mail->setFrom($_POST['email'], $_POST['name']);
    $mail->addAddress($_ENV['RECEIVER_EMAIL']);

    if (isset($_FILES['cv']) && $_FILES['cv']['error'] == 0) {
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'png'];
        $file_extension = pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $mail->addAttachment($_FILES['cv']['tmp_name'], $_FILES['cv']['name']);
        } else {
            throw new Exception("Tipo di file non supportato.");
        }
    }

    $mail->isHTML(true);
    $mail->Subject = $_POST['subject'];
    $mail->Body    = nl2br(htmlspecialchars($_POST['message']));
    $mail->AltBody = strip_tags($_POST['message']);

    if ($mail->send()) {
        header('Location: ../team.html?status=success&message=' . urlencode('Messaggio inviato con successo. Gazie!'));
    } else {
        throw new Exception("Errore nell'invio dell'email. Dettagli: " . $mail->ErrorInfo);
    }
    
} catch (Exception $e) {
    // Salva l'errore nella sessione per un uso successivo
    $_SESSION['form_status'] = ['error', 'Errore: ' . $e->getMessage()];

    // Reindirizza alla pagina team.html con i parametri per mostrare l'errore
    header('Location: ../team.html?status=error&message=' . urlencode($e->getMessage()));
    exit;
}

exit;
?>
