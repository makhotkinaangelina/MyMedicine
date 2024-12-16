<?php

class UserModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function register($email, $password, $nickname)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (email, password, nickname) VALUES (?, ?, ?)");
            if ($stmt === false) {
                throw new Exception('Failed to prepare statement: ' . implode(", ", $this->pdo->errorInfo()));
            }
            
            return $stmt->execute([$email, $hashedPassword, $nickname]);
            
        } catch (Exception $e) {
            throw new Exception('Registration failed: ' . $e->getMessage());
        }
    }

    public function getById($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            if ($stmt === false) {
                throw new Exception('Failed to prepare statement: ' . implode(", ", $this->pdo->errorInfo()));
            }

            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception('Fetch by ID failed: ' . $e->getMessage());
        }
    }

    public function getByEmail($email)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            if ($stmt === false) {
                throw new Exception('Failed to prepare statement: ' . implode(", ", $this->pdo->errorInfo()));
            }

            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception('Fetch by email failed: ' . $e->getMessage());
        }
    }

    public function getByNickname($nickname)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE nickname = ?");
            if ($stmt === false) {
                throw new Exception('Failed to prepare statement: ' . implode(", ", $this->pdo->errorInfo()));
            }

            $stmt->execute([$nickname]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception('Fetch by nickname failed: ' . $e->getMessage());
        }
    }
}
