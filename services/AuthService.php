<?php
/**
 * Authentication Service
 * Smart Bin Waste Management System
 */

class AuthService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Authenticate user with username and password
     */
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare('SELECT id, username, email, password, role, status, employee_id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return array('success' => false, 'message' => 'Invalid username or password');
            }
            
            if ($user['status'] !== 'ACTIVE') {
                return array('success' => false, 'message' => 'User account is inactive');
            }
            
            if (!password_verify($password, $user['password'])) {
                return array('success' => false, 'message' => 'Invalid username or password');
            }
            
            // Update last login
            $updateStmt = $this->db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
            $updateStmt->execute([$user['id']]);
            
            // Log audit
            $this->logAudit($user['id'], 'LOGIN', 'users', $user['id'], 'User logged in');
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['employee_id'] = $user['employee_id'];
            $_SESSION['login_time'] = time();
            
            return array('success' => true, 'message' => 'Login successful', 'user' => $user);
            
        } catch (Exception $e) {
            logError('Login error', array('error' => $e->getMessage()));
            return array('success' => false, 'message' => 'An error occurred during login');
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && isset($_SESSION['role']);
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->isAuthenticated() && $_SESSION['role'] === 'ADMIN';
    }
    
    /**
     * Check if user is employee
     */
    public function isEmployee() {
        return $this->isAuthenticated() && $_SESSION['role'] === 'EMPLOYEE';
    }
    
    /**
     * Check session timeout
     */
    public function checkSessionTimeout() {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $currentTime = time();
        $loginTime = $_SESSION['login_time'] ?? $currentTime;
        
        if (($currentTime - $loginTime) > SESSION_TIMEOUT) {
            session_destroy();
            return false;
        }
        
        $_SESSION['login_time'] = $currentTime;
        return true;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        try {
            if (isset($_SESSION['user_id'])) {
                $this->logAudit($_SESSION['user_id'], 'LOGOUT', 'users', $_SESSION['user_id'], 'User logged out');
            }
            
            session_destroy();
            return true;
        } catch (Exception $e) {
            logError('Logout error', array('error' => $e->getMessage()));
            return false;
        }
    }
    
    /**
     * Create new user
     */
    public function createUser($data) {
        try {
            // Validate input
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                return array('success' => false, 'message' => 'Required fields missing');
            }
            
            // Check if user exists
            $stmt = $this->db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$data['username'], $data['email']]);
            
            if ($stmt->fetch()) {
                return array('success' => false, 'message' => 'Username or email already exists');
            }
            
            // Generate employee ID
            $employeeId = $this->generateEmployeeId();
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, array('cost' => BCRYPT_COST));
            
            $stmt = $this->db->prepare(
                'INSERT INTO users (employee_id, username, email, password, role, contact_number, location_id, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            
            $result = $stmt->execute([
                $employeeId,
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['role'] ?? 'EMPLOYEE',
                $data['contact_number'] ?? null,
                $data['location_id'] ?? null,
                $data['status'] ?? 'ACTIVE'
            ]);
            
            if ($result) {
                $userId = $this->db->lastInsertId();
                
                // Assign locations if provided
                if (!empty($data['locations']) && is_array($data['locations'])) {
                    $this->assignUserLocations($userId, $data['locations']);
                }
                
                $this->logAudit($_SESSION['user_id'] ?? null, 'ADD_USER', 'users', $userId, 'User created: ' . $data['username']);
                
                return array('success' => true, 'message' => 'User created successfully', 'user_id' => $userId, 'employee_id' => $employeeId);
            }
            
            return array('success' => false, 'message' => 'Failed to create user');
            
        } catch (Exception $e) {
            logError('Create user error', array('error' => $e->getMessage()));
            return array('success' => false, 'message' => 'An error occurred');
        }
    }
    
    /**
     * Update user
     */
    public function updateUser($userId, $data) {
        try {
            $updates = array();
            $params = array();
            
            if (isset($data['username'])) {
                // Check if username is unique
                $stmt = $this->db->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
                $stmt->execute([$data['username'], $userId]);
                
                if ($stmt->fetch()) {
                    return array('success' => false, 'message' => 'Username already exists');
                }
                
                $updates[] = 'username = ?';
                $params[] = $data['username'];
            }
            
            if (isset($data['email'])) {
                // Check if email is unique
                $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                $stmt->execute([$data['email'], $userId]);
                
                if ($stmt->fetch()) {
                    return array('success' => false, 'message' => 'Email already exists');
                }
                
                $updates[] = 'email = ?';
                $params[] = $data['email'];
            }
            
            if (isset($data['contact_number'])) {
                $updates[] = 'contact_number = ?';
                $params[] = $data['contact_number'];
            }
            
            if (isset($data['location_id'])) {
                $updates[] = 'location_id = ?';
                $params[] = $data['location_id'];
            }
            
            if (isset($data['status'])) {
                $updates[] = 'status = ?';
                $params[] = $data['status'];
            }
            
            if (isset($data['password']) && !empty($data['password'])) {
                $updates[] = 'password = ?, password_changed_at = NOW()';
                $params[] = password_hash($data['password'], PASSWORD_BCRYPT, array('cost' => BCRYPT_COST));
            }
            
            if (empty($updates)) {
                return array('success' => false, 'message' => 'No fields to update');
            }
            
            $updates[] = 'updated_at = NOW()';
            $params[] = $userId;
            
            $query = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                // Update location assignments if provided
                if (isset($data['locations']) && is_array($data['locations'])) {
                    $this->assignUserLocations($userId, $data['locations']);
                }
                
                $this->logAudit($_SESSION['user_id'] ?? null, 'EDIT_USER', 'users', $userId, 'User updated');
                
                return array('success' => true, 'message' => 'User updated successfully');
            }
            
            return array('success' => false, 'message' => 'Failed to update user');
            
        } catch (Exception $e) {
            logError('Update user error', array('error' => $e->getMessage()));
            return array('success' => false, 'message' => 'An error occurred');
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT id, employee_id, username, email, role, contact_number, location_id, status, created_at, last_login 
                 FROM users WHERE id = ?'
            );
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            logError('Get user error', array('error' => $e->getMessage()));
            return null;
        }
    }
    
    /**
     * Get all users
     */
    public function getAllUsers($filters = array()) {
        try {
            $query = 'SELECT id, employee_id, username, email, role, contact_number, location_id, status, created_at, last_login FROM users WHERE 1=1';
            $params = array();
            
            if (!empty($filters['role'])) {
                $query .= ' AND role = ?';
                $params[] = $filters['role'];
            }
            
            if (!empty($filters['status'])) {
                $query .= ' AND status = ?';
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['location_id'])) {
                $query .= ' AND location_id = ?';
                $params[] = $filters['location_id'];
            }
            
            if (!empty($filters['search'])) {
                $query .= ' AND (username LIKE ? OR email LIKE ? OR employee_id LIKE ?)';
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }
            
            $query .= ' ORDER BY employee_id ASC';
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logError('Get all users error', array('error' => $e->getMessage()));
            return array();
        }
    }
    
    /**
     * Deactivate user
     */
    public function deactivateUser($userId) {
        try {
            $stmt = $this->db->prepare('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?');
            $result = $stmt->execute(['INACTIVE', $userId]);
            
            if ($result) {
                $this->logAudit($_SESSION['user_id'] ?? null, 'DEACTIVATE_USER', 'users', $userId, 'User deactivated');
                return array('success' => true, 'message' => 'User deactivated');
            }
            
            return array('success' => false, 'message' => 'Failed to deactivate user');
        } catch (Exception $e) {
            logError('Deactivate user error', array('error' => $e->getMessage()));
            return array('success' => false, 'message' => 'An error occurred');
        }
    }
    
    /**
     * Assign user to locations
     */
    private function assignUserLocations($userId, $locationIds) {
        try {
            // Clear existing assignments
            $stmt = $this->db->prepare('DELETE FROM location_users WHERE user_id = ?');
            $stmt->execute([$userId]);
            
            // Add new assignments
            $stmt = $this->db->prepare('INSERT INTO location_users (location_id, user_id) VALUES (?, ?)');
            
            foreach ($locationIds as $locationId) {
                $stmt->execute([$locationId, $userId]);
            }
            
            return true;
        } catch (Exception $e) {
            logError('Assign locations error', array('error' => $e->getMessage()));
            return false;
        }
    }
    
    /**
     * Generate unique employee ID
     */
    private function generateEmployeeId() {
        try {
            $timestamp = time();
            $random = mt_rand(1000, 9999);
            return 'EMP-' . $timestamp . '-' . $random;
        } catch (Exception $e) {
            return 'EMP-' . uniqid();
        }
    }
    
    /**
     * Log audit trail
     */
    private function logAudit($userId, $action, $targetType, $targetId, $description = '') {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO audit_logs (user_id, action, target_type, target_id, description, ip_address) 
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            
            $stmt->execute([
                $userId,
                $action,
                $targetType,
                $targetId,
                $description,
                getClientIP()
            ]);
        } catch (Exception $e) {
            logError('Audit log error', array('error' => $e->getMessage()));
        }
    }
}
