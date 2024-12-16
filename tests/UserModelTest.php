<?php

require 'vendor/autoload.php';
require_once 'models/UserModel.php';

use PHPUnit\Framework\TestCase;

class UserModelTest extends TestCase {
    private $pdo;
    private $userModel;

    protected function setUp(): void {
        $this->pdo = $this->createMock(PDO::class);
        $this->userModel = new UserModel($this->pdo);
    }
    public function testGetById() {
        $id = 1;
        $email = 'test@example.com';
        $password = 'password123';
        $nickname = 'testnickname';
    
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn([
            'id' => $id,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'nickname' => $nickname,
        ]);
        $this->pdo->method('prepare')->willReturn($stmt);
    
        $user = $this->userModel->getById($id);
        $this->assertNotNull($user);
        $this->assertEquals($email, $user['email']);
        $this->assertEquals($nickname, $user['nickname']);
    }
    
    public function testGetByEmail() {
        $email = 'test@example.com';
        $password = 'password123';
        $nickname = 'testnickname';
    
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn([
            'id' => 1,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'nickname' => $nickname,
        ]);
        $this->pdo->method('prepare')->willReturn($stmt);
    
        $user = $this->userModel->getByEmail($email);
        $this->assertNotNull($user);
        $this->assertEquals($email, $user['email']);
        $this->assertEquals($nickname, $user['nickname']);
    }
    
    public function testGetByNickname() {
        $nickname = 'testnickname';
        $email = 'test@example.com';
    
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn([
            'id' => 1,
            'email' => $email,
            'nickname' => $nickname,
        ]);
        $this->pdo->method('prepare')->willReturn($stmt);
    
        $user = $this->userModel->getByNickname($nickname);
        $this->assertNotNull($user);
        $this->assertEquals($email, $user['email']);
        $this->assertEquals($nickname, $user['nickname']);
    }
    

    protected function tearDown(): void {
        $this->pdo = null;
    }
}
