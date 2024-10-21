<?php
function get_categories(PDO $pdo) {
    try {
        $stmt = $pdo->query("SELECT CategoryID, Category FROM categories");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $categories;
    } catch (PDOException $e) {
        error_log('Ошибка при получении категорий: ' . $e->getMessage());
        return []; 
    }
}

function calculateAge($dateBirth) {
    $dob = new DateTime($dateBirth);
    $now = new DateTime();
    $interval = $now->diff($dob);
    return $interval->y;
}

function calculateExperience($experienceStart) {
    $start = new DateTime($experienceStart);
    $now = new DateTime();
    $interval = $now->diff($start);
    return $interval->y;
}
