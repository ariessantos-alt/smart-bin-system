<?php
/**
 * Email Service
 * Smart Bin Waste Management System
 */

class EmailService {
    private $from;
    private $fromName;
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    
    public function __construct() {
        $this->from = MAIL_FROM_ADDRESS;
        $this->fromName = MAIL_FROM_NAME;
        $this->host = MAIL_HOST;
        $this->port = MAIL_PORT;
        $this->username = MAIL_USERNAME;
        $this->password = MAIL_PASSWORD;
        $this->encryption = MAIL_ENCRYPTION;
    }
    
    /**
     * Send email using PHPMailer
     */
    public function send($to, $subject, $body, $isHtml = true) {
        try {
            // Check if PHPMailer is available
            $phpMailerPath = __DIR__ . '/../vendor/autoload.php';
            
            if (file_exists($phpMailerPath)) {
                return $this->sendWithPHPMailer($to, $subject, $body, $isHtml);
            } else {
                // Fallback to native mail function
                return $this->sendWithNativeMail($to, $subject, $body, $isHtml);
            }
        } catch (Exception $e) {
            logError('Email send error', array(
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ));
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Send with PHPMailer (recommended)
     */
    private function sendWithPHPMailer($to, $subject, $body, $isHtml = true) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = $this->host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->username;
            $mail->Password = $this->password;
            $mail->SMTPSecure = $this->encryption;
            $mail->Port = $this->port;
            $mail->SMTPDebug = 0; // Set to 2 for debugging
            
            // Message
            $mail->setFrom($this->from, $this->fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML($isHtml);
            
            // Send
            if ($mail->send()) {
                logInfo('Email sent', array('to' => $to, 'subject' => $subject));
                return array('success' => true, 'message' => 'Email sent successfully');
            } else {
                return array('success' => false, 'error' => $mail->ErrorInfo);
            }
        } catch (Exception $e) {
            logError('PHPMailer error', array('error' => $e->getMessage()));
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Send with native mail function (fallback)
     */
    private function sendWithNativeMail($to, $subject, $body, $isHtml = true) {
        try {
            $headers = "From: " . $this->fromName . " <" . $this->from . ">\r\n";
            $headers .= "Reply-To: " . $this->from . "\r\n";
            
            if ($isHtml) {
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            }
            
            $headers .= "X-Mailer: Smart Bin System\r\n";
            
            if (mail($to, $subject, $body, $headers)) {
                logInfo('Email sent (native)', array('to' => $to, 'subject' => $subject));
                return array('success' => true, 'message' => 'Email sent successfully');
            } else {
                return array('success' => false, 'error' => 'Failed to send email');
            }
        } catch (Exception $e) {
            logError('Native mail error', array('error' => $e->getMessage()));
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Send notification email
     */
    public function sendNotification($to, $toName, $binId, $location, $fillLevel, $type) {
        try {
            if ($type === 'NEARLY_FULL') {
                $subject = "Smart Bin Alert - " . $binId . " Nearly Full";
                $message = "The trash bin <strong>" . htmlspecialchars($binId) . "</strong> at <strong>" . htmlspecialchars($location) . "</strong> is nearly full at <strong>" . $fillLevel . "%</strong>. Please prepare for collection.";
            } else if ($type === 'FULL') {
                $subject = "CRITICAL Smart Bin Alert - " . $binId . " Full";
                $message = "The trash bin <strong>" . htmlspecialchars($binId) . "</strong> at <strong>" . htmlspecialchars($location) . "</strong> is FULL at <strong>" . $fillLevel . "%</strong>. Immediate collection is required!";
            } else {
                $subject = "Smart Bin System Alert";
                $message = "System notification for bin " . htmlspecialchars($binId) . ".";
            }
            
            $html = $this->getEmailTemplate($subject, $toName, $message, $binId, $location, $fillLevel);
            
            return $this->send($to, $subject, $html, true);
        } catch (Exception $e) {
            logError('Send notification email error', array('error' => $e->getMessage()));
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($subject, $toName, $message, $binId, $location, $fillLevel) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f5f5f5; padding: 20px; }
                .footer { text-align: center; padding: 10px; color: #999; font-size: 12px; }
                .alert-box { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
                .critical-alert { background-color: #f8d7da; border-left: 4px solid #dc3545; }
                .details { background-color: white; border: 1px solid #ddd; padding: 15px; margin: 15px 0; }
                .detail-row { margin: 10px 0; padding: 10px 0; border-bottom: 1px solid #eee; }
                .detail-row:last-child { border-bottom: none; }
                .label { color: #666; font-weight: bold; }
                .value { color: #333; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>" . APP_NAME . "</h1>
                </div>
                
                <div class="content">
                    <p>Hello " . htmlspecialchars($toName) . ",</p>
                    
                    <div class="alert-box" . (strpos($subject, 'CRITICAL') !== false ? ' style="background-color: #f8d7da; border-left: 4px solid #dc3545;"' : '') . ">
                        " . $message . "
                    </div>
                    
                    <div class="details">
                        <div class="detail-row">
                            <span class="label">Bin ID:</span>
                            <span class="value">" . htmlspecialchars($binId) . "</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Location:</span>
                            <span class="value">" . htmlspecialchars($location) . "</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Fill Level:</span>
                            <span class="value">" . $fillLevel . "%</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Timestamp:</span>
                            <span class="value">" . date('Y-m-d H:i:s') . "</span>
                        </div>
                    </div>
                    
                    <p>Please log in to the Smart Bin System dashboard for more details and to manage collections.</p>
                </div>
                
                <div class="footer">
                    <p>This is an automated message from Smart Bin Waste Management System. Please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " " . APP_NAME . ". All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
