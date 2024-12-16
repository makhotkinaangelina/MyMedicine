<?php

class CategoryModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addCategory($categoryName) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO categories (Category) VALUES (?)");
            $stmt->execute([$categoryName]);
        } catch (PDOException $e) {
            throw new Exception("Ошибка при добавлении категории: " . $e->getMessage());
        }
    }

    public function updateCategory($categoryId, $categoryName) {
        try {
            $stmt = $this->pdo->prepare("UPDATE categories SET Category = ? WHERE CategoryID = ?");
            $stmt->execute([$categoryName, $categoryId]);
        } catch (PDOException $e) {
            throw new Exception("Ошибка при обновлении категории: " . $e->getMessage());
        }
    }

    public function deleteCategory($categoryId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM categories WHERE CategoryID = ?");
            $stmt->execute([$categoryId]);
        } catch (PDOException $e) {
            throw new Exception("Ошибка при удалении категории: " . $e->getMessage());
        }
    }
}