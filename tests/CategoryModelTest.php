<?php

require_once 'models/CategoryModel.php';

use PHPUnit\Framework\TestCase;

class CategoryModelTest extends TestCase {
    private $pdo;
    private $categoryModel;

    protected function setUp(): void {
        $this->pdo = $this->createMock(PDO::class);
        $this->categoryModel = new CategoryModel($this->pdo);
    }

    public function testAddCategory() {
        $categoryName = 'Unique Category';

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $this->pdo->method('prepare')->willReturn($stmt);

        $this->categoryModel->addCategory($categoryName);
        $this->assertTrue(true);
    }

    public function testAddDuplicateCategory() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Ошибка при добавлении категории:");

        $categoryName = 'Duplicate Category';

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->will($this->throwException(new PDOException("SQLSTATE[23000]: Integrity constraint violation")));
        $this->pdo->method('prepare')->willReturn($stmt);

        $this->categoryModel->addCategory($categoryName);
    }

    public function testUpdateCategory() {
        $categoryId = 1;
        $categoryName = 'Updated Category';

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $this->pdo->method('prepare')->willReturn($stmt);

        $this->categoryModel->updateCategory($categoryId, $categoryName);
        $this->assertTrue(true); 
    }

    public function testUpdateNonExistingCategory() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Ошибка при обновлении категории:");

        $categoryId = 999; 
        $categoryName = 'Non-existing Category';

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->will($this->throwException(new PDOException("SQLSTATE[23000]: Integrity constraint violation")));
        $this->pdo->method('prepare')->willReturn($stmt);

        $this->categoryModel->updateCategory($categoryId, $categoryName);
    }

    public function testDeleteCategory() {
        $categoryId = 1;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $this->pdo->method('prepare')->willReturn($stmt);

        $this->categoryModel->deleteCategory($categoryId);
        $this->assertTrue(true); 
    }

    public function testDeleteNonExistingCategory() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Ошибка при удалении категории:");

        $categoryId = 999; 

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->will($this->throwException(new PDOException("SQLSTATE[23000]: Integrity constraint violation")));
        $this->pdo->method('prepare')->willReturn($stmt);

        $this->categoryModel->deleteCategory($categoryId);
    }

    protected function tearDown(): void {
        $this->pdo = null;
        $this->categoryModel = null;
    }
}