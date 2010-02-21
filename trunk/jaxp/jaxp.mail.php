<?php
    class JaxpMailContact
    {
        public $Name;
        public $Email;
        
        function __construct($contactName, $contactEmail)
        {
            $this->Name = $contactName;
            $this->Email = $contactEmail;
        }
        
        function __toString()
        {
            return json_encode
            (
                array
                (
                  "name" => $this->Name,
                  "email" => $this->Email
                )
            );
        }
        
        function GetRfc882Header()
        {
            return sprintf('"%s" <%s>', $this->name, $this->email);
        }
    }
    
    class JaxpMailMessage extends JaxpModule
    {
        public $From;
        public $To;
        public $Subject;
        public $Body;
        public $MimeBoundary;
        
        function __construct($fromName, $fromEmail)
        {
            $this->From = new JaxpMailContact($fromName, $fromEmail);
            $this->MimeBoundary = "==" . md5(time());
        }
        
        private function mail_headers($EOL = "\n")
        {
            $headers
              = "From: " . $this->From->GetRfc822Header() . $EOL
              . "Reply-To: <" . $this->From->Email . ">" . $EOL
              . "Return-Path: <" . $this->From->Email . ">" . $EOL
              . "MIME-Version: 1.0" . $EOL
              . "Content-Type: multipart/alternative; boundary=\"{$this->MimeBoundary}\"" . $EOL
              . "User-Agent: JaxpMail/1.0" . $EOL
              . "X-Priority: 3 (Normal)" . $EOL
              . "Importance: Normal" . $EOL
              . "X-Mailer: JaxpMail";
              
            return $headers;
        }

        private function rfc882_body_format($EOL = "\r\n")
        {
            return wordwrap($this->body, 70, $EOL);
        }
        
        function Send()
        {
            $EOL =
                 (
                    stripos($this->To->Email, "hotmail") !== false
                  ||
                    stripos($this->To->Email, "live") !== false
                 )
                  ? "\n"
                  : "\n";
                  
            return mail
            (
                $this->To->GetRfc882Header(),
                $this->Subject,
                $this->multipart_alternative_body($EOL),
                $this->mail_headers($EOL),
                "-f" . $this->From->Email
            );
        }
        
        private function multipart_alternative_body($EOL = "\r\n")
        {
            $multipart
                    = "Content-Transfer-Encoding: 7bit" . $EOL
                    . "This is a multi-part message in MIME format. This part of the E-mail should never be seen. If you are reading this, consider upgrading your e-mail client to a MIME-compatible client." . $EOL . $EOL
                    = "--{$this->mime_boundary}" . $EOL
                    . "Content-Type: text/plain; charset=iso-8859-1" . $EOL
                    . "Content-Transfer-Encoding: 7bit" . $EOL . $EOL
                    . strip_tags($this->br2nl($this->headerTemplate)) . $EOL . $EOL
                    . strip_tags($this->br2nl($this->body)) . $EOL . $EOL
                    . strip_tags($this->br2nl($this->footerTemplate)) . $EOL . $EOL
                    . "--{$this->mime_boundary}" . $EOL
                    . "Content-Type: text/html; charset=iso-8859-1" . $EOL
                    . "Content-Transfer-Encoding: 7bit" . $EOL . $EOL
                    . $this->headerTemplate . $EOL
                    . $this->body . $EOL
                    . $this->footerTemplate . $EOL
                    . "--{$this->mime_boundary}--" . $EOL;
                    
            return $multipart;
        }
        
        private function br2nl($text, $EOL = "\n")
        {
            $text = str_ireplace("<br>", $EOL, $text);
            $text = str_ireplace("<br />", $EOL, $text);
            return $text;
        }
    }
?>