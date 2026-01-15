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

    public function send($to, $subject, $body, $fromName = 'PyViz System') {
        $ctx = stream_context_create(); // Can add SSL options here if needed generally
        $remote = "tcp://" . $this->host . ":" . $this->port;
        if ($this->port == 465) $remote = "ssl://" . $this->host . ":" . $this->port;
        // if ($this->port == 587) ... handled via STARTTLS

        $this->conn = stream_socket_client($remote, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
        if (!$this->conn) {
            error_log("SMTP Connect Error: $errstr");
            return false;
        }
        
        $this->getResponse();
        $this->cmd("EHLO " . $_SERVER['SERVER_NAME']);
        
        if ($this->port == 587) {
            $this->cmd("STARTTLS");
            stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->cmd("EHLO " . $_SERVER['SERVER_NAME']);
        }
        
        $this->cmd("AUTH LOGIN");
        $this->cmd(base64_encode($this->username));
        $this->cmd(base64_encode($this->password));
        
        $this->cmd("MAIL FROM: <" . $this->username . ">");
        $this->cmd("RCPT TO: <$to>");
        $this->cmd("DATA");
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $fromName <" . $this->username . ">\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";
        
        $this->cmd($headers . "\r\n" . $body . "\r\n.");
        $this->cmd("QUIT");
        
        fclose($this->conn);
        return true;
    }
}
?>
