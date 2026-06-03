<?php
header('Content-Type: application/json; charset=UTF-8');

$okMessage = 'Hvala, sporočilo je bilo uspešno poslano. Odgovorili vam bomo v najkrajšem možnem času.';
$errorMessage = 'Pri pošiljanju je prišlo do napake. Poskusite pozneje ali pokličite.';

function respond($type, $message) {
    echo json_encode(array('type' => $type, 'message' => $message), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('danger', 'Neveljavna zahteva.');
}

$honeypot = trim($_POST['f_email'] ?? '');
if ($honeypot !== '') {
    respond('danger', 'Sporočila ni bilo mogoče poslati.');
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$interest = trim($_POST['interest'] ?? '');
$message = trim($_POST['message'] ?? '');
$agree = trim($_POST['agree'] ?? '');

$errors = array();
if (mb_strlen($name) < 2) $errors[] = 'Vnesite ime in priimek.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Vnesite veljaven e-poštni naslov.';
if ($interest === '') $errors[] = 'Izberite vrsto povpraševanja.';
if (mb_strlen($message) < 15) $errors[] = 'Sporočilo naj ima vsaj 15 znakov.';
if ($agree !== 'Da') $errors[] = 'Potrdite soglasje za odgovor na povpraševanje.';

if (!empty($errors)) {
    respond('danger', implode(' ', $errors));
}

$to = getenv('CONTACT_TO') ?: 'alesbobic@gmail.com';
$from = getenv('CONTACT_FROM') ?: 'alesbobic@g;
$subject = 'Novo povpraševanje iz terapevt.si';
$body = "Novo sporočilo iz obrazca terapevt.si\n";
$body .= "=====================================\n";
$body .= "Ime: {$name}\n";
$body .= "E-pošta: {$email}\n";
$body .= "Telefon: {$phone}\n";
$body .= "Vrsta povpraševanja: {$interest}\n";
$body .= "Soglasje: {$agree}\n\n";
$body .= "Sporočilo:\n{$message}\n";

try {
    $autoload = __DIR__ . '/PHPMailer-master/PHPMailerAutoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        $smtpHost = getenv('SMTP_HOST');
        $smtpUser = getenv('SMTP_USER');
        $smtpPass = getenv('SMTP_PASS');
        $smtpPort = getenv('SMTP_PORT') ?: 587;
        $smtpSecure = getenv('SMTP_SECURE') ?: 'tls';

        if ($smtpHost && $smtpUser && $smtpPass) {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = $smtpSecure;
            $mail->Port = (int) $smtpPort;
        }

        $mail->setFrom($from, 'terapevt.si');
        $mail->addAddress($to);
        $mail->addReplyTo($email, $name);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
    } else {
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $from,
            'Reply-To: ' . $email,
            'Return-Path: ' . $from
        );
        if (!mail($to, $subject, $body, implode("\r\n", $headers))) {
            throw new Exception('mail() failed');
        }
    }
    respond('success', $okMessage);
} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    respond('danger', $errorMessage);
}
