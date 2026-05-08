<?php
require_once __DIR__ . "/mail.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function loadPhpMailer(&$errorMessage)
{
    $errorMessage = "";

    $autoloadPath = __DIR__ . "/../vendor/autoload.php";
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        return true;
    }

    $localBases = [
        __DIR__ . "/../PHPMailer-master/src/",
        __DIR__ . "/../PHPMailer/src/"
    ];

    foreach ($localBases as $localBase) {
        $exceptionFile = $localBase . "Exception.php";
        $phpMailerFile = $localBase . "PHPMailer.php";
        $smtpFile = $localBase . "SMTP.php";

        if (file_exists($exceptionFile) && file_exists($phpMailerFile) && file_exists($smtpFile)) {
            require_once $exceptionFile;
            require_once $phpMailerFile;
            require_once $smtpFile;
            return true;
        }
    }

    $errorMessage = "PHPMailer not found. Install via Composer or place PHPMailer-master or PHPMailer in project root.";
    return false;
}

function sendOtpEmail($toEmail, $toName, $otp, &$errorMessage)
{
    global $mailConfig;

    if (!loadPhpMailer($errorMessage)) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $mailConfig["host"];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig["username"];
        $mail->Password = $mailConfig["password"];
        $mail->SMTPSecure = $mailConfig["encryption"];
        $mail->Port = $mailConfig["port"];
        $mail->CharSet = "UTF-8";

        $mail->setFrom($mailConfig["from_email"], $mailConfig["from_name"]);
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        $mail->isHTML(true);
        $mail->Subject = "Your password reset OTP";

        $safeOtp = htmlspecialchars($otp, ENT_QUOTES, "UTF-8");
        $mail->Body = "<p>Your OTP is <strong>{$safeOtp}</strong>. It expires in 10 minutes.</p>";
        $mail->AltBody = "Your OTP is {$otp}. It expires in 10 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorMessage = $mail->ErrorInfo ?: $e->getMessage();
        return false;
    }
}

function sendVerificationEmail($toEmail, $toName, $verifyLink, &$errorMessage)
{
    global $mailConfig;

    if (!loadPhpMailer($errorMessage)) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $mailConfig["host"];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig["username"];
        $mail->Password = $mailConfig["password"];
        $mail->SMTPSecure = $mailConfig["encryption"];
        $mail->Port = $mailConfig["port"];
        $mail->CharSet = "UTF-8";

        $mail->setFrom($mailConfig["from_email"], $mailConfig["from_name"]);
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        $mail->isHTML(true);
        $mail->Subject = "Verify your email";

        $safeLink = htmlspecialchars($verifyLink, ENT_QUOTES, "UTF-8");
        $mail->Body = "<p>Please verify your email by clicking the link below:</p><p><a href=\"{$safeLink}\">Verify Email</a></p>";
        $mail->AltBody = "Verify your email: {$verifyLink}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorMessage = $mail->ErrorInfo ?: $e->getMessage();
        return false;
    }
}
