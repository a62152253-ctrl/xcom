<?php
namespace Security;

use PDO;

class SecurityAudit {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Checks if any users have plain-text or weak hashed passwords.
     * Returns an array of user IDs with weak passwords.
     */
    public function checkPasswords(): array {
        $stmt = $this->db->query("SELECT id, password_hash FROM users");
        $weakUsers = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hashInfo = password_get_info($row['password_hash']);
            // If the algorithm is not recognized or is not ARGON2ID (or BCRYPT if configured that way), flag it.
            // In modern PHP, we expect Argon2id or at least valid bcrypt.
            // If password_hash doesn't start with $argon2 or $2y$, it's probably weak/plaintext.
            if ($hashInfo['algo'] === 0 || !preg_match('/^\$(argon2i|argon2id|2y|2b)\$/', $row['password_hash'])) {
                $weakUsers[] = $row['id'];
            }
        }

        return $weakUsers;
    }

    /**
     * Checks if all users have an assigned role ('Owner', 'Administrator', 'Member').
     * Returns an array of user IDs without a valid role.
     */
    public function checkRoles(): array {
        $validRoles = ['Owner', 'Administrator', 'Member'];
        $placeholders = implode(',', array_fill(0, count($validRoles), '?'));

        $stmt = $this->db->prepare("SELECT id FROM users WHERE role NOT IN ($placeholders) OR role IS NULL");
        $stmt->execute($validRoles);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
