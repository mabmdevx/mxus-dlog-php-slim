<?php

class User
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Find user by username
     *
     * @param string $username
     * @return array|null
     */
    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE user_username = ? AND is_deleted = 0',
            [$username]
        );
    }

    /**
     * Find user by UUID
     *
     * @param string $uuid
     * @return array|null
     */
    public function findByUuid(string $uuid): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE user_uuid = ? AND is_deleted = 0',
            [$uuid]
        );
    }

    /**
     * Find user by ID
     *
     * @param int $userId
     * @return array|null
     */
    public function findById(int $userId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE user_id = ? AND is_deleted = 0',
            [$userId]
        );
    }

    /**
     * Authenticate user with username and password
     *
     * @param string $username
     * @param string $password
     * @return array|null User data if authenticated, null otherwise
     */
    public function authenticate(string $username, string $password): ?array
    {
        $user = $this->findByUsername($username);

        if ($user && password_verify($password, $user['user_password'])) {
            // Remove password from returned data for security
            unset($user['user_password']);
            return $user;
        }

        return null;
    }

    /**
     * Create new user
     *
     * @param array $data User data (username, password, email)
     * @return int User ID
     * @throws Exception
     */
    public function create(array $data): int
    {
        // Check if username already exists
        if ($this->findByUsername($data['username'])) {
            throw new Exception('Username already exists');
        }

        $uuid = $this->generateUuid();
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $now = date('Y-m-d H:i:s');

        $userId = $this->db->insert(
            'INSERT INTO users (user_uuid, user_username, user_password, user_email, created_on) 
             VALUES (?, ?, ?, ?, ?)',
            [$uuid, $data['username'], $hashedPassword, $data['email'], $now]
        );

        return $userId;
    }

    /**
     * Generate UUID v4
     *
     * @return string
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Update user password
     *
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $now = date('Y-m-d H:i:s');

        $this->db->query(
            'UPDATE users SET user_password = ?, updated_on = ? WHERE user_id = ?',
            [$hashedPassword, $now, $userId]
        );

        return true;
    }
}
