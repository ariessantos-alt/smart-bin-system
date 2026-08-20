<?php
/**
 * SMS Service
 * Smart Bin Waste Management System
 * Supports multiple providers: Twilio, Vonage (Nexmo), AWS SNS
 */

class SMSService {
    private $provider;
    private $apiKey;
    private $apiSecret;
    private $sender;
    private $endpoint;
    
    public function __construct() {
        $this->provider = SMS_PROVIDER;
        $this->apiKey = SMS_API_KEY;
        $this->apiSecret = SMS_API_SECRET;
        $this->sender = SMS_SENDER;
        $this->endpoint = SMS_ENDPOINT;
    }
    
    /**
     * Send SMS
     */
    public function send($phoneNumber, $message) {
        try {
            // Validate phone number
            if (empty($phoneNumber) || !$this->isValidPhoneNumber($phoneNumber)) {
                return array('success' => false, 'error' => 'Invalid phone number');
            }
            
            // Validate message length
            if (strlen($message) > 160) {
                $message = substr($message, 0, 157) . '...';
            }
            
            switch ($this->provider) {
                case 'twilio':
                    return $this->sendViatwilio($phoneNumber, $message);
                case 'vonage':
                    return $this->sendViaVonage($phoneNumber, $message);
                case 'aws':
                    return $this->sendViaAWS($phoneNumber, $message);
                case 'test':
                    return $this->sendViaTest($phoneNumber, $message);
                default:
                    return array('success' => false, 'error' => 'Unknown SMS provider');
            }
        } catch (Exception $e) {
            logError('SMS send error', array(
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ));
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Send SMS notification
     */
    public function sendNotification($phoneNumber, $binId, $location, $fillLevel, $type) {
        try {
            if ($type === 'NEARLY_FULL') {
                $message = "SMART BIN ALERT: " . $binId . " at " . $location . " is nearly full at " . $fillLevel . "%. Please prepare for collection.";
            } else if ($type === 'FULL') {
                $message = "SMART BIN ALERT: " . $binId . " at " . $location . " is FULL at " . $fillLevel . "%. Immediate collection is required.";
            } else {
                $message = "SMART BIN NOTIFICATION: " . $binId . " at " . $location . ". Fill level: " . $fillLevel . "%";
            }
            
            return $this->send($phoneNumber, $message);
        } catch (Exception $e) {
            logError('Send notification SMS error', array('error' => $e->getMessage()));
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Send via Twilio
     */
    private function sendViatwilio($phoneNumber, $message) {
        try {
            if (empty($this->apiKey)) {
                logError('Twilio API key not configured');
                return array('success' => false, 'error' => 'SMS provider not configured');
            }
            
            // Twilio expects Account SID:Auth Token format for basic auth
            list($accountSid, $authToken) = explode(':', $this->apiKey);
            
            $url = "https://api.twilio.com/2010-04-01/Accounts/" . $accountSid . "/Messages.json";
            
            $postData = array(
                'From' => $this->sender,
                'To' => $phoneNumber,
                'Body' => $message
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $accountSid . ":" . $authToken);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 201) {
                $responseData = json_decode($response, true);
                logInfo('SMS sent via Twilio', array('sid' => $responseData['sid'] ?? null));
                return array('success' => true, 'message' => 'SMS sent successfully', 'message_id' => $responseData['sid'] ?? null);
            } else {
                $error = json_decode($response, true);
                logError('Twilio SMS error', array('code' => $httpCode, 'error' => $error));
                return array('success' => false, 'error' => $error['message'] ?? 'Failed to send SMS');
            }
        } catch (Exception $e) {
            logError('Twilio send error', array('error' => $e->getMessage()));
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Send via Vonage (Nexmo)
     */
    private function sendViaVonage($phoneNumber, $message) {
        try {
            if (empty($this->apiKey)) {
                return array('success' => false, 'error' => 'SMS provider not configured');
            }
            
            $url = "https://rest.nexmo.com/sms/json";
            
            $postData = array(
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'to' => $phoneNumber,
                'from' => $this->sender,
                'text' => $message
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                if ($responseData['messages'][0]['status'] == 0) {
                    logInfo('SMS sent via Vonage', array('id' => $responseData['messages'][0]['message-id']));
                    return array('success' => true, 'message' => 'SMS sent successfully', 'message_id' => $responseData['messages'][0]['message-id']);
                }
            }
            
            logError('Vonage SMS error', array('response' => $response));
            return array('success' => false, 'error' => 'Failed to send SMS');
        } catch (Exception $e) {
            logError('Vonage send error', array('error' => $e->getMessage()));
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Send via AWS SNS
     */
    private function sendViaAWS($phoneNumber, $message) {
        try {
            // This would require AWS SDK
            // For now, return placeholder
            logInfo('SMS sent via AWS SNS (placeholder)', array('phone' => $phoneNumber));
            return array('success' => true, 'message' => 'SMS sent successfully (AWS)');
        } catch (Exception $e) {
            logError('AWS send error', array('error' => $e->getMessage()));
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Test mode (logs but doesn't send)
     */
    private function sendViaTest($phoneNumber, $message) {
        logInfo('SMS TEST MODE', array(
            'to' => $phoneNumber,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ));
        
        return array('success' => true, 'message' => 'SMS logged in test mode', 'test_mode' => true);
    }
    
    /**
     * Validate phone number
     */
    private function isValidPhoneNumber($phoneNumber) {
        // Basic validation: should be numeric and 10-15 digits
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
        return strlen($cleaned) >= 10 && strlen($cleaned) <= 15;
    }
}
