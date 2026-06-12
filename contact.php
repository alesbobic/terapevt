<?php
header('Content-Type: application/json; charset=UTF-8');

/*
 * Kontaktni obrazec za terapevt.si
 * Prilagojeno za PHPMailer 5.2.x, ki uporablja:
 *   PHPMailer-master/class.phpmailer.php
 *   PHPMailer-master/class.smtp.php
 *
 * Če na hostingu uporabljaš SMTP, lahko vrednosti nastaviš kot environment variable
 * ali jih po potrebi vpišeš spodaj namesto getenv(...).
 */

$CONTACT_DEBUG = isset($_GET['debug']) && $_GET['debug'] === '1';

$okMessage = 'Hvala, sporočilo je bilo uspešno poslano. Odgovorili vam bomo v najkrajšem možnem času.';
$errorMessage = 'Pri pošiljanju je prišlo do napake. Poskusite pozneje ali pokličite.';

function respond($type, $message, $debug = null) {
    global $CONTACT_DEBUG;

    $payload = array(
        'type' => $type,
        'message' => $message
    );

    if ($CONTACT_DEBUG && $debug) {
        $payload['debug'] = $debug;
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function form_value($key) {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

function string_length($value) {
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('danger', 'Neveljavna zahteva.');
}

$honeypot = form_value('f_email');
if ($honeypot !== '') {
    respond('danger', 'Sporočila ni bilo mogoče poslati.');
}

$name = form_value('name');
$email = form_value('email');
$phone = form_value('phone');
$interest = form_value('interest');
$message = form_value('message');
$agree = form_value('agree');

$errors = array();

if (string_length($name) < 2) {
    $errors[] = 'Vnesite ime in priimek.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Vnesite veljaven e-poštni naslov.';
}

if ($interest === '') {
    $errors[] = 'Izberite vrsto povpraševanja.';
}

if (string_length($message) < 15) {
    $errors[] = 'Sporočilo naj ima vsaj 15 znakov.';
}

if ($agree !== 'Da') {
    $errors[] = 'Potrdite soglasje za odgovor na povpraševanje.';
}

if (!empty($errors)) {
    respond('danger', implode(' ', $errors));
}

/*
 * Osnovne nastavitve
 * CONTACT_TO je prejemnik sporočila.
 * CONTACT_FROM naj bo praviloma e-naslov iste domene kot spletna stran.
 */

$to = 'alesbobic@gmail.com';
$from = 'tomaz@terapevt.si';
$fromName = 'terapevt.si';
$subject = 'Novo povpraševanje iz terapevt.si';

$body = "Novo sporočilo iz obrazca terapevt.si\n";
$body .= "=====================================\n";
$body .= "Ime: {$name}\n";
$body .= "E-pošta: {$email}\n";
$body .= "Telefon: {$phone}\n";
$body .= "Vrsta povpraševanja: {$interest}\n";
$body .= "Soglasje: {$agree}\n\n";
$body .= "Sporočilo:\n{$message}\n";

/*
 * SMTP nastavitve
 * Če jih imaš na hostingu, jih nastavi v nadzorni plošči kot environment variable.
 * Lahko pa jih začasno vpišeš neposredno tukaj:
 *
 * $smtpHost = 'mail.terapevt.si';
 * $smtpUser = 'tomaz@terapevt.si';
 * $smtpPass = 'GESLO';
 * $smtpPort = 587;
 * $smtpSecure = 'tls';
 */
/*
$smtpHost = getenv('SMTP_HOST') ?: '';
$smtpUser = getenv('SMTP_USER') ?: '';
$smtpPass = getenv('SMTP_PASS') ?: '';
$smtpPort = getenv('SMTP_PORT') ?: 587;
$smtpSecure = getenv('SMTP_SECURE') ?: 'tls';
*/
/* SMTP Domenca */

$smtpHost = 'mail.terapevt.si';
$smtpUser = 'tomaz@terapevt.si';

/*
 * VPIŠI GESLO POŠTNEGA PREDALA
 * isto geslo kot ga uporabljaš za Webmail
 */
$smtpPass = 'BZ@5Yk#Wjnm@m';

$smtpPort = 465;
$smtpSecure = 'ssl';

try {
    $phpmailerFile = __DIR__ . '/PHPMailer-master/class.phpmailer.php';
    $smtpFile = __DIR__ . '/PHPMailer-master/class.smtp.php';
    $autoloadFile = __DIR__ . '/PHPMailer-master/PHPMailerAutoload.php';

    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
    } elseif (file_exists($phpmailerFile)) {
        require_once $phpmailerFile;

        if (file_exists($smtpFile)) {
            require_once $smtpFile;
        }
    } else {
        throw new Exception('PHPMailer datoteke niso najdene. Preveri mapo PHPMailer-master.');
    }

    if (!class_exists('PHPMailer')) {
        throw new Exception('Razred PHPMailer ni na voljo.');
    }

    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->Timeout = 30;

    if ($smtpHost !== '' && $smtpUser !== '' && $smtpPass !== '') {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = $smtpSecure; // tls ali ssl
        $mail->Port = (int)$smtpPort;

        /*
         * Nekateri starejši hosti imajo težave s preverjanjem certifikata.
         * Če SMTP s pravilnimi podatki še vedno pade na TLS certifikatu,
         * lahko ta blok pomaga. Najbolj pravilno pa je urediti certifikat na hostingu.
         */
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
    } else {
        // Fallback na lokalno PHP mail() funkcijo, če SMTP podatki niso nastavljeni.
        $mail->isMail();
    }

    $mail->setFrom($from, $fromName);
    $mail->addAddress($to);
    $mail->addReplyTo($email, $name);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = $body;

    if (!$mail->send()) {
        throw new Exception($mail->ErrorInfo ?: 'PHPMailer send() failed.');
    }

    respond('success', $okMessage);
} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    respond('danger', $errorMessage, $e->getMessage());
}
