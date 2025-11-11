<?php
/**
 * Simple SMTP Mailer Class
 * Supports SMTP authentication and TLS/SSL
 */
class Mailer {
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $smtpEncryption;
    private $fromEmail;
    private $fromName;
    private $debug = false;

    private $to = [];
    private $subject = '';
    private $body = '';
    private $headers = [];
    private $attachments = [];

    /**
     * Constructor
     */
    public function __construct() {
        // Load SMTP settings from database
        $this->smtpHost = getSetting('smtp_host', 'localhost');
        $this->smtpPort = getSetting('smtp_port', 587);
        $this->smtpUsername = getSetting('smtp_username', '');
        $this->smtpPassword = getSetting('smtp_password', '');
        $this->smtpEncryption = getSetting('smtp_encryption', 'tls');
        $this->fromEmail = getSetting('smtp_from_email', MAIL_FROM);
        $this->fromName = getSetting('smtp_from_name', MAIL_FROM_NAME);
        $this->debug = getSetting('smtp_debug', '0') == '1';
    }

    /**
     * Add recipient
     */
    public function addAddress($email, $name = '') {
        $this->to[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    /**
     * Set subject
     */
    public function setSubject($subject) {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set HTML body
     */
    public function setBody($body) {
        $this->body = $body;
        return $this;
    }

    /**
     * Set from
     */
    public function setFrom($email, $name = '') {
        $this->fromEmail = $email;
        $this->fromName = $name;
        return $this;
    }

    /**
     * Send email via SMTP
     */
    public function send() {
        // Check if SMTP is enabled
        $smtpEnabled = getSetting('smtp_enabled', '0') == '1';

        if (!$smtpEnabled) {
            // Use PHP mail() function
            return $this->sendWithPhpMail();
        }

        try {
            // Connect to SMTP server
            $socket = $this->connectToServer();

            if (!$socket) {
                throw new Exception('Could not connect to SMTP server');
            }

            // Send SMTP commands
            $this->smtpCommand($socket, "EHLO " . $this->smtpHost);

            // Start TLS if needed
            if ($this->smtpEncryption === 'tls') {
                $this->smtpCommand($socket, "STARTTLS");
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->smtpCommand($socket, "EHLO " . $this->smtpHost);
            }

            // Authenticate
            if (!empty($this->smtpUsername) && !empty($this->smtpPassword)) {
                $this->smtpCommand($socket, "AUTH LOGIN");
                $this->smtpCommand($socket, base64_encode($this->smtpUsername));
                $this->smtpCommand($socket, base64_encode($this->smtpPassword));
            }

            // Send email
            $this->smtpCommand($socket, "MAIL FROM: <" . $this->fromEmail . ">");

            foreach ($this->to as $recipient) {
                $this->smtpCommand($socket, "RCPT TO: <" . $recipient['email'] . ">");
            }

            $this->smtpCommand($socket, "DATA");

            // Prepare email content
            $emailContent = $this->buildEmailContent();

            fputs($socket, $emailContent . "\r\n.\r\n");
            $this->getResponse($socket);

            $this->smtpCommand($socket, "QUIT");
            fclose($socket);

            return true;

        } catch (Exception $e) {
            if ($this->debug) {
                error_log("SMTP Error: " . $e->getMessage());
            }

            // Fallback to PHP mail()
            return $this->sendWithPhpMail();
        }
    }

    /**
     * Connect to SMTP server
     */
    private function connectToServer() {
        $host = $this->smtpHost;
        $port = $this->smtpPort;

        if ($this->smtpEncryption === 'ssl') {
            $host = 'ssl://' . $host;
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, 30);

        if ($socket) {
            stream_set_timeout($socket, 30);
            $this->getResponse($socket);
        }

        return $socket;
    }

    /**
     * Send SMTP command
     */
    private function smtpCommand($socket, $command) {
        fputs($socket, $command . "\r\n");
        return $this->getResponse($socket);
    }

    /**
     * Get server response
     */
    private function getResponse($socket) {
        $response = '';

        while ($line = fgets($socket, 515)) {
            $response .= $line;

            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }

        if ($this->debug) {
            error_log("SMTP Response: " . $response);
        }

        return $response;
    }

    /**
     * Build email content
     */
    private function buildEmailContent() {
        $boundary = md5(uniqid(time()));

        $content = "From: " . $this->fromName . " <" . $this->fromEmail . ">\r\n";
        $content .= "To: " . $this->to[0]['email'] . "\r\n";
        $content .= "Subject: " . $this->encodeSubject($this->subject) . "\r\n";
        $content .= "MIME-Version: 1.0\r\n";
        $content .= "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"\r\n";
        $content .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $content .= "\r\n";

        // Plain text version
        $content .= "--" . $boundary . "\r\n";
        $content .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $content .= "Content-Transfer-Encoding: 7bit\r\n";
        $content .= "\r\n";
        $content .= strip_tags($this->body) . "\r\n";

        // HTML version
        $content .= "--" . $boundary . "\r\n";
        $content .= "Content-Type: text/html; charset=UTF-8\r\n";
        $content .= "Content-Transfer-Encoding: 7bit\r\n";
        $content .= "\r\n";
        $content .= $this->body . "\r\n";

        $content .= "--" . $boundary . "--\r\n";

        return $content;
    }

    /**
     * Encode subject for UTF-8
     */
    private function encodeSubject($subject) {
        return '=?UTF-8?B?' . base64_encode($subject) . '?=';
    }

    /**
     * Fallback: Send with PHP mail()
     */
    private function sendWithPhpMail() {
        $headers = [
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];

        $headerString = implode("\r\n", $headers);

        foreach ($this->to as $recipient) {
            $sent = mail($recipient['email'], $this->subject, $this->body, $headerString);

            if (!$sent) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear recipients
     */
    public function clearAddresses() {
        $this->to = [];
        return $this;
    }
}
