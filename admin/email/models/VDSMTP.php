<?php
// admin/email/models/VDSMTP.php

class VDSMTP
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption; // none, tls, ssl
    private $senderEmail;
    private $senderName;

    private $conn;
    private $debug = false;
    private $lastError = '';
    private $logCallback = null;

    public function __construct($host, $port, $username, $password, $encryption = 'tls', $senderEmail = '', $senderName = 'VDraw System')
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = strtolower($encryption);
        $this->senderEmail = $senderEmail ?: $username;
        $this->senderName = $senderName;
    }

    public function setDebug($debug, $callback = null)
    {
        $this->debug = $debug;
        $this->logCallback = $callback;
    }

    private function log($msg)
    {
        if ($this->debug && is_callable($this->logCallback)) {
            call_user_func($this->logCallback, $msg);
        } elseif ($this->debug) {
            echo "DEBUG: $msg<br>";
        }
    }

    private function getResponse()
    {
        $res = "";
        if (!$this->conn) return "";

        while ($str = fgets($this->conn, 515)) {
            $res .= $str;
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        $this->log("S: $res");
        return $res;
    }

    private function cmd($cmd)
    {
        $this->log("C: $cmd");
        if (!$this->conn) return false;
        fputs($this->conn, $cmd . "\r\n");
        return $this->getResponse();
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    private function checkResponse($response, $expectedCode)
    {
        $code = (int)substr($response, 0, 3);
        // Allow 2xx for 200, 3xx for 300 range if exact match not required, but strict is better
        if ($code !== $expectedCode) {
            $this->lastError = "SMTP Error: Expected $expectedCode, got $code - $response";
            $this->log("ERROR: " . $this->lastError);
            return false;
        }
        return true;
    }

    public function send($to, $subject, $body, $isHtml = true)
    {
        $this->lastError = '';
        $ctx = stream_context_create();

        // Scheme based on encryption/port
        $scheme = "tcp://";
        if ($this->encryption === 'ssl' || $this->port == 465) {
            $scheme = "ssl://";
        }

        $remote = $scheme . $this->host . ":" . $this->port;

        $this->log("Connecting to $remote");
        $this->conn = stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$this->conn) {
            $this->lastError = "SMTP Connect Error: $errstr (errno: $errno)";
            return false;
        }

        // Get server greeting - expect 220
        $greeting = $this->getResponse();
        if (!$this->checkResponse($greeting, 220)) {
            fclose($this->conn);
            return false;
        }

        // EHLO
        $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $ehloResp = $this->cmd("EHLO " . $serverName);
        if (!$this->checkResponse($ehloResp, 250)) {
            fclose($this->conn);
            return false;
        }

        // STARTTLS if configured and not already SSL
        if ($this->encryption === 'tls' && $this->port != 465) {
            $starttlsResp = $this->cmd("STARTTLS");
            if ($this->checkResponse($starttlsResp, 220)) {
                if (!stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    $this->lastError = "SMTP TLS negotiation failed";
                    fclose($this->conn);
                    return false;
                }
                // Resend EHLO after TLS
                $ehloResp2 = $this->cmd("EHLO " . $serverName);
                if (!$this->checkResponse($ehloResp2, 250)) {
                    fclose($this->conn);
                    return false;
                }
            } else {
                // If STARTTLS failed but we wanted it, logic depends. 
                // Usually logging it is enough, might continue in plain if server rejects it, 
                // but for security we should probably fail. 
                // SimpleSMTP didn't stricly fail. I'll fail.
                $this->lastError = "SMTP Server did not accept STARTTLS";
                fclose($this->conn);
                return false;
            }
        }

        // AUTH LOGIN
        $authResp = $this->cmd("AUTH LOGIN");
        if (!$this->checkResponse($authResp, 334)) {
            fclose($this->conn);
            return false;
        }

        $userResp = $this->cmd(base64_encode($this->username));
        if (!$this->checkResponse($userResp, 334)) {
            fclose($this->conn);
            return false;
        }

        $passResp = $this->cmd(base64_encode($this->password));
        if (!$this->checkResponse($passResp, 235)) {
            $this->lastError = "Authentication failed";
            fclose($this->conn);
            return false;
        }

        // MAIL FROM
        // Use senderEmail. If strictly rejected, user might need to match username.
        $mailFromResp = $this->cmd("MAIL FROM: <" . $this->senderEmail . ">");
        if (!$this->checkResponse($mailFromResp, 250)) {
            fclose($this->conn);
            return false;
        }

        // RCPT TO
        $rcptResp = $this->cmd("RCPT TO: <$to>");
        if (!$this->checkResponse($rcptResp, 250)) {
            fclose($this->conn);
            return false;
        }

        // DATA
        $dataResp = $this->cmd("DATA");
        if (!$this->checkResponse($dataResp, 354)) {
            fclose($this->conn);
            return false;
        }

        // Headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
        $headers .= "From: {$this->senderName} <{$this->senderEmail}>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "X-Mailer: VDraw Mailer\r\n";

        // Send message body
        $msgResp = $this->cmd($headers . "\r\n" . $body . "\r\n.");
        if (!$this->checkResponse($msgResp, 250)) {
            fclose($this->conn);
            return false;
        }

        $this->cmd("QUIT");

        fclose($this->conn);
        return true;
    }
}
