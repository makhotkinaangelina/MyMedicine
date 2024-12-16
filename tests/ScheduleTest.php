<?php
use PHPUnit\Framework\TestCase;

class ScheduleTest extends TestCase {
    protected function setUp(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void {
        session_destroy();
    }

    public function testAddScheduleWithAccess() {
        $_SESSION['user_role'] = 'doctor';
        $_SERVER["REQUEST_METHOD"] = "POST"; 
        $_POST['doctorID'] = 1;
        $_POST['new_date'] = '2025-12-10';
        $_POST['num_time_intervals'] = 3;

        $mockPDO = $this->createMock(PDO::class);
        $mockStmt = $this->createMock(PDOStatement::class);

        $mockPDO->method('prepare')->willReturn($mockStmt);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetchColumn')->willReturn(json_encode([]));

        global $pdo;
        $pdo = $mockPDO;

        ob_start();
        include 'add_schedule_date.php';
        $output = ob_get_clean();

        echo "Test: testAddScheduleWithAccess - Output: " . $output . PHP_EOL;
        $this->assertStringContainsString("Новая дата с временными промежутками успешно добавлена", $output);
    }

    public function testDuplicateScheduleNotAllowed() {
        $_SESSION['user_role'] = 'doctor';
        $_SERVER["REQUEST_METHOD"] = "POST"; 
        $_POST['doctorID'] = 1;
        $_POST['new_date'] = '2025-12-10';
        $_POST['num_time_intervals'] = 2;

        $mockPDO = $this->createMock(PDO::class);
        $mockStmt = $this->createMock(PDOStatement::class);

        $mockPDO->method('prepare')->willReturn($mockStmt);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetchColumn')->willReturn(json_encode(['2025-12-10' => ['09:00-10:00']]));

        global $pdo;
        $pdo = $mockPDO;

        ob_start();
        include 'add_schedule_date.php'; 
        ob_end_clean();

        ob_start();
        include 'add_schedule_date.php'; 
        $output = ob_get_clean();

        echo "Test: testDuplicateScheduleNotAllowed - Output: " . $output . PHP_EOL;
        $this->assertStringContainsString("Расписание на эту дату уже существует", $output);
    }

    public function testAdminAccess() {
        $_SESSION['user_role'] = 'admin';
        $_SERVER["REQUEST_METHOD"] = "POST"; 
        $_POST['doctorID'] = 1;
        $_POST['new_date'] = '2024-12-11';
        $_POST['num_time_intervals'] = 2;

        $mockPDO = $this->createMock(PDO::class);
        $mockStmt = $this->createMock(PDOStatement::class);

        $mockPDO->method('prepare')->willReturn($mockStmt);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetchColumn')->willReturn(json_encode([]));

        global $pdo;
        $pdo = $mockPDO;

        ob_start();
        include 'add_schedule_date.php';
        $output = ob_get_clean();

        echo "Test: testAdminAccess - Output: " . $output . PHP_EOL;
        $this->assertStringContainsString("Новая дата с временными промежутками успешно добавлена", $output);
    }

    public function testNoAccess() {
        $_SESSION['user_role'] = 'user';
        $_SERVER["REQUEST_METHOD"] = "POST"; 
        $_POST['doctorID'] = 1;
        $_POST['new_date'] = '2024-12-12';
        $_POST['num_time_intervals'] = 3;

        ob_start();
        include 'add_schedule_date.php';
        $output = ob_get_clean();

        echo "Test: testNoAccess - Output: " . $output . PHP_EOL;
        $this->assertStringContainsString("У вас нет прав для добавления расписания.", $output);
    }
}