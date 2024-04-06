<?php
namespace carry0987\Helper;

use carry0987\Helper\Utils;
use carry0987\Tag\Tag;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class EmailHelper extends Helper
{
    private static $config;
    private static $email_config = array();
    private static $type_list = array('localhost', 'smtp');

    public function __construct(array $config_array = null)
    {
        if ($config_array) {
            self::$config = $config_array;
        }
    }

    public static function getTypeList()
    {
        return self::$type_list;
    }

    public function setEmailConfig(string $key, array $config_array): self
    {
        self::$email_config[$key] = $config_array;

        return $this;
    }

    public static function getEmailConfig(string $key)
    {
        if (empty($key)) return self::$email_config;
        return (isset(self::$email_config[$key])) ? self::$email_config[$key] : null;
    }

    public static function checkEnable()
    {
        return (self::$config['enable'] !== 1) ? false : true;
    }

    public static function getCurrentType()
    {
        return self::$config['type'];
    }

    public static function checkType(string $type)
    {
        return (self::$config['type'] === $type) ? true : false;
    }

    public static function getDomainList(string $type)
    {
        $tag = new Tag();
        $tag->setValidTagFormat('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/');
        $allow_domain_str = self::$config['allow_domain'];
        $disallow_domain_str = self::$config['disallow_domain'];

        if ($type === 'allow_domain') {
            $tag->setString($allow_domain_str);
            return $tag->getList('|');
        } elseif ($type === 'disallow_domain') {
            $tag->setString($disallow_domain_str);
            return $tag->getList('|');
        } else {
            return array();
        }
    }

    public function buildEmailOption(array $data): array
    {
        if (!Utils::checkEmpty($data, ['to', 'subject', 'html_body'])) return [false, null, null];
        $method = self::$config['type'];
        $config = self::$email_config[$method];
        $emailDetails = array();
        $emailDetails['to'] = $data['to'];
        $emailDetails['to_name'] = '';
        $emailDetails['subject'] = $data['subject'];
        $emailDetails['html_body'] = $data['html_body'];
        $emailDetails['alt_body'] = $data['alt_body'] ?? '';

        return [$method, $config, $emailDetails];
    }

    public function sendEmail(string $method, ?array $config, ?array $emailDetails)
    {
        if (empty($method) || empty($config) || empty($emailDetails)) return false;

        $mail = new PHPMailer(true);
        $mail->setLanguage(self::$param['language'] ?? 'en');

        switch (strtolower($method)) {
            case 'localhost':
                return self::sendByLocalhost($mail, $config, $emailDetails);
            case 'smtp':
                return self::sendBySMTP($mail, $config, $emailDetails);
            default:
                return false;
        }
    }

    private static function sendByLocalhost(PHPMailer $mail, array $config, array $emailDetails)
    {
        try {
            // Set mail server and email details
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            if (function_exists('mail')) {
                $mail->isMail();
            } elseif (function_exists('sendmail')) {
                $mail->isSendmail();
            }
            $mail->Port = $config['port'] ?? 25;
            $mail->CharSet = $config['charset'] ?? 'UTF-8';
            // Recipients
            $mail->setFrom($config['send_from'], $config['send_name']);
            $mail->addAddress($emailDetails['to'], $emailDetails['to_name'] ?? '');
            // Content
            $mail->isHTML(true);
            $mail->Subject = $emailDetails['subject'];
            $mail->Body = $emailDetails['html_body'];
            $mail->AltBody = $emailDetails['alt_body'];
            // Send email
            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            return false;
        }

        return true;
    }

    private static function sendBySMTP(PHPMailer $mail, array $config, array $emailDetails)
    {
        if (!self::checkSecure($config['smtp_secure'])) return false;

        try {
            // Set mail server and email details
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_user'];
            $mail->Password = $config['smtp_pw'];
            $mail->SMTPSecure = $config['smtp_secure'];
            $mail->Port = $config['smtp_port'] ?? 587;
            $mail->CharSet = $config['charset'] ?? 'UTF-8';
            // Recipients
            $mail->setFrom($config['smtp_send_from'], $config['smtp_send_name']);
            $mail->addAddress($emailDetails['to'], $emailDetails['to_name'] ?? '');
            // Content
            $mail->isHTML(true);
            $mail->Subject = $emailDetails['subject'];
            $mail->Body = $emailDetails['html_body'];
            $mail->AltBody = $emailDetails['alt_body'];
            // Send email
            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            return false;
        }

        return true;
    }

    private static function checkSecure(string $secure)
    {
        $secure_list = array('', PHPMailer::ENCRYPTION_SMTPS, PHPMailer::ENCRYPTION_STARTTLS);

        return (in_array($secure, $secure_list)) ? true : false;
    }
}
