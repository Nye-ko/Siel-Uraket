<?php

class AuthService
{
    public function __construct(private mysqli $db)
    {
    }

    public function login(string $email, string $password): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, email, full_name, role, password_hash
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $normalizedEmail = strtolower(trim($email));
        $stmt->bind_param('s', $normalizedEmail);
        $stmt->execute();
        $stmt->bind_result($id, $userEmail, $fullName, $role, $passwordHash);

        $user = $stmt->fetch()
            ? [
                'id' => $id,
                'email' => $userEmail,
                'full_name' => $fullName,
                'role' => $role,
                'password_hash' => $passwordHash,
            ]
            : null;
        $stmt->close();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        unset($user['password_hash']);
        return $user;
    }
}
