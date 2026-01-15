<?php
// frontend/PyViz/auth/SimpleSMTP.php

class SimpleSMTP {
    private $host;
    private $port;
    private $username;
    private $password;
    private $conn;
    private $debug = false;

    public function __construct($host, $port, $username, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    private function log($msg) {
        if ($this->debug) echo "DEBUG: $msg<br>";
    }

    private function getResponse() {
        $res = "";
        while($str = fgets($this->conn, 515)) {
            $res .= $str;
            if(substr($str, 3, 1) == " ") { break; }
        }
        $this->log("S: $res");
        return $res;
    }

    private function cmd($cmd) {
        $this->log("C: $cmd");
        fputs($this->conn, $cmd . "\r\n");
        return $this->getResponse();
    }

    private $lastError = '';
    
    public function getLastError() {
        return $this->lastError;
    }
    
    private function checkResponse($response, $expectedCode) {
        $code = (int)substr($response, 0, 3);
        if ($code !== $expectedCode && !($expectedCode === 200 && $code >= 200 && $code < 300) && !($expectedCode === 300 && $code >= 300 && $code < 400)) {
            $this->lastError = "SMTP Error: Expected $expectedCode, got $code - $response";
            error_log($this->lastError);
            return false;
        }
        return true;
    }

    public function send($to, $subject, $body, $fromName = 'VDraw System') {
        $this->lastError = '';
        $ctx = stream_context_create(); // Can add SSL options here if needed generally
        $remote = "tcp://" . $this->host . ":" . $this->port;
        if ($this->port == 465) $remote = "ssl://" . $this->host . ":" . $this->port;
        // if ($this->port == 587) ... handled via STARTTLS

        $this->conn = stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$this->conn) {
            $this->lastError = "SMTP Connect Error: $errstr (errno: $errno)";
            error_log($this->lastError);
            return false;
        }
        
        // Get server greeting - expect 220
        $greeting = $this->getResponse();
        if (!$this->checkResponse($greeting, 220)) {
            fclose($this->conn);
            return false;
        }
        
        // EHLO - expect 250
        $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $ehloResp = $this->cmd("EHLO " . $serverName);
        if (!$this->checkResponse($ehloResp, 250)) {
            fclose($this->conn);
            return false;
        }
        
        if ($this->port == 587) {
            $starttlsResp = $this->cmd("STARTTLS");
            if (!$this->checkResponse($starttlsResp, 220)) {
                fclose($this->conn);
                return false;
            }
            if (!stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->lastError = "SMTP TLS negotiation failed";
                error_log($this->lastError);
                fclose($this->conn);
                return false;
            }
            $ehloResp2 = $this->cmd("EHLO " . $serverName);
            if (!$this->checkResponse($ehloResp2, 250)) {
                fclose($this->conn);
                return false;
            }
        }
        
        // AUTH LOGIN - expect 334
        $authResp = $this->cmd("AUTH LOGIN");
        if (!$this->checkResponse($authResp, 334)) {
            fclose($this->conn);
            return false;
        }
        
        // Username - expect 334
        $userResp = $this->cmd(base64_encode($this->username));
        if (!$this->checkResponse($userResp, 334)) {
            fclose($this->conn);
            return false;
        }
        
        // Password - expect 235 (authentication successful)
        $passResp = $this->cmd(base64_encode($this->password));
        if (!$this->checkResponse($passResp, 235)) {
            $this->lastError = "SMTP Authentication failed. Check username/password.";
            error_log($this->lastError);
            fclose($this->conn);
            return false;
        }
        
        // MAIL FROM - expect 250
        $mailFromResp = $this->cmd("MAIL FROM: <" . $this->username . ">");
        if (!$this->checkResponse($mailFromResp, 250)) {
            fclose($this->conn);
            return false;
        }
        
        // RCPT TO - expect 250
        $rcptResp = $this->cmd("RCPT TO: <$to>");
        if (!$this->checkResponse($rcptResp, 250)) {
            fclose($this->conn);
            return false;
        }
        
        // DATA - expect 354
        $dataResp = $this->cmd("DATA");
        if (!$this->checkResponse($dataResp, 354)) {
            fclose($this->conn);
            return false;
        }
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $fromName <" . $this->username . ">\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";
        
        // Send message body and end with . on its own line - expect 250
        $msgResp = $this->cmd($headers . "\r\n" . $body . "\r\n.");
        if (!$this->checkResponse($msgResp, 250)) {
            fclose($this->conn);
            return false;
        }
        
        $this->cmd("QUIT");
        
        fclose($this->conn);
        error_log("SMTP: Email sent successfully to $to");
        return true;
    }
}
?>
