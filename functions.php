<?php
function get_categories(PDO $pdo)
{
    try {
        $stmt = $pdo->query("SELECT CategoryID, Category FROM categories");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $categories;
    } catch (PDOException $e) {
        error_log('Ошибка при получении категорий: ' . $e->getMessage());
        return [];
    }
}

function calculateAge($dateBirth)
{
    $dob = new DateTime($dateBirth);
    $now = new DateTime();
    $interval = $now->diff($dob);
    return $interval->y;
}

function calculateExperience($experienceStart)
{
    $start = new DateTime($experienceStart);
    $now = new DateTime();
    $interval = $now->diff($start);
    return $interval->y;
}

function encryptData($data, $userId)
{
    $key = hash('sha256', $userId);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv); // Шифруем данные
    return base64_encode($iv . $encryptedData);
}

function decryptData($data, $userId)
{
    $key = hash('sha256', $userId);
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLength);
    $encryptedData = substr($data, $ivLength);
    return openssl_decrypt($encryptedData, 'aes-256-cbc', $key, 0, $iv); // Расшифровываем данные
}

function getWeights($pdo)
{
    $weightsQuery = "SELECT criterion_name, weight FROM criteries";
    $weightsStmt = $pdo->query($weightsQuery);
    return $weightsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function getDoctorsInfoSlow($pdo, $weights, $specialization = '', $searchName = '')
{
    $experienceWeight = $weights["Стаж врача"] ?? 0;
    $ratingWeight = $weights['Рейтинг врача'] ?? 0;
    $visitCountWeight = $weights['Частота посещений'] ?? 0;
    $allVisitCountWeight = $weights['Частота посещений общая'] ?? 0;

    $query = "WITH MaxValues AS (
            SELECT 
                MAX(Experience) AS MaxExperience,
                MAX(AvgRating) AS MaxAvgRating,
                MAX(UserVisitCount) AS MaxUserVisitCount,
                MAX(TotalVisitCount) AS MaxTotalVisitCount
            FROM (
                SELECT 
                    d.*,
                    (SELECT AVG(rating) FROM ratings WHERE doctor_id = d.id) AS AvgRating,
                    (SELECT COUNT(*) FROM appointments WHERE DoctorID = d.id AND PatientID IN (
                        SELECT ID FROM Patients WHERE UserID = :userID
                    )) AS UserVisitCount,
                    (SELECT COUNT(*) FROM appointments WHERE DoctorID = d.id) AS TotalVisitCount
                FROM doctors AS d
            ) AS SubQuery
        )
        SELECT 
            d.id AS DoctorID,
            d.Name,
            d.Experience,
            d.Schedule,
            c.*,
            (SELECT AVG(rating) FROM ratings WHERE doctor_id = d.id) AS AverageRating,
            (SELECT COUNT(*) FROM appointments WHERE DoctorID = d.id AND PatientID IN (
                SELECT ID FROM Patients WHERE UserID = :userID
            )) AS UserVisitCount,
            (SELECT COUNT(*) FROM appointments WHERE DoctorID = d.id) AS TotalVisitCount,
            (
                d.Experience / NULLIF((SELECT MaxExperience FROM MaxValues), 0) * :experienceWeight +
                (SELECT AVG(rating) FROM ratings WHERE doctor_id = d.id) / 
                NULLIF((SELECT MaxAvgRating FROM MaxValues), 0) * :ratingWeight +
                (SELECT COUNT(*) FROM appointments WHERE DoctorID = d.id AND PatientID IN (
                    SELECT ID FROM Patients WHERE UserID = :userID
                )) / NULLIF((SELECT MaxUserVisitCount FROM MaxValues), 0) * :visitCountWeight +
                (SELECT COUNT(*) FROM appointments WHERE DoctorID = d.id) / 
                NULLIF((SELECT MaxTotalVisitCount FROM MaxValues), 0) * :allVisitCountWeight
            ) AS Score
        FROM 
            doctors AS d
        LEFT JOIN 
            categories AS c ON c.categoryid = d.specializationid
        WHERE 
            (:specialization = '' OR c.Category = :specialization)
            AND (:searchName = '' OR d.Name LIKE :searchName)
        ORDER BY 
            Score DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':experienceWeight', $experienceWeight, PDO::PARAM_STR);
    $stmt->bindValue(':ratingWeight', $ratingWeight, PDO::PARAM_STR);
    $stmt->bindValue(':visitCountWeight', $visitCountWeight, PDO::PARAM_STR);
    $stmt->bindValue(':allVisitCountWeight', $allVisitCountWeight, PDO::PARAM_STR);
    $stmt->bindValue(':specialization', $specialization, PDO::PARAM_STR);
    $stmt->bindValue(':searchName', '%' . htmlspecialchars($searchName, ENT_QUOTES) . '%', PDO::PARAM_STR);
    $stmt->bindValue(':userID', intval($_SESSION['user_id'] ?? 0), PDO::PARAM_INT);

    try {
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Ошибка выполнения запроса: " . $e->getMessage();
        return [];
    }

    return $results;
}

function getDoctorsInfoFast($pdo, $weights, $specialization = '', $searchName = '')
{
    $experienceWeight = $weights["Стаж врача"] ?? 0;
    $ratingWeight = $weights['Рейтинг врача'] ?? 0;
    $visitCountWeight = $weights['Частота посещений'] ?? 0;
    $allVisitCountWeight = $weights['Частота посещений общая'] ?? 0;

    $query = "WITH DoctorAggregates AS (
            SELECT 
                d.id AS DoctorID,
                d.Name,
                d.Experience,
                d.Schedule,
                c.Category,
                COALESCE(AVG(r.rating), 0) AS AverageRating,
                COUNT(DISTINCT a1.id) AS UserVisitCount,
                COUNT(DISTINCT a2.id) AS TotalVisitCount
            FROM 
                doctors AS d
            LEFT JOIN 
                categories AS c ON c.categoryid = d.specializationid
            LEFT JOIN 
                ratings AS r ON r.doctor_id = d.id
            LEFT JOIN 
                appointments AS a1 ON a1.DoctorID = d.id AND a1.PatientID = :userID
            LEFT JOIN 
                appointments AS a2 ON a2.DoctorID = d.id
            WHERE 
                (:specialization = '' OR c.Category = :specialization)
                AND (:searchName = '' OR d.Name LIKE :searchName)
            GROUP BY 
                d.id, d.Name, d.Experience, d.Schedule, c.Category
        ),
        MaxValues AS (
            SELECT 
                MAX(Experience) AS MaxExperience,
                MAX(AverageRating) AS MaxAvgRating,
                MAX(UserVisitCount) AS MaxUserVisitCount,
                MAX(TotalVisitCount) AS MaxTotalVisitCount
            FROM 
                DoctorAggregates
        )
        SELECT 
            da.DoctorID,
            da.Name,
            da.Experience,
            da.Schedule,
            da.Category,
            da.AverageRating,
            da.UserVisitCount,
            da.TotalVisitCount,
            (
                (da.Experience / NULLIF(mv.MaxExperience, 0)) * :experienceWeight +
                (da.AverageRating / NULLIF(mv.MaxAvgRating, 0)) * :ratingWeight +
                (da.UserVisitCount / NULLIF(mv.MaxUserVisitCount, 0)) * :visitCountWeight +
                (da.TotalVisitCount / NULLIF(mv.MaxTotalVisitCount, 0)) * :allVisitCountWeight
            ) AS Score
        FROM 
            DoctorAggregates AS da
        CROSS JOIN 
            MaxValues AS mv
        ORDER BY 
            Score DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':experienceWeight', $experienceWeight, PDO::PARAM_STR);
    $stmt->bindValue(':ratingWeight', $ratingWeight, PDO::PARAM_STR);
    $stmt->bindValue(':visitCountWeight', $visitCountWeight, PDO::PARAM_STR);
    $stmt->bindValue(':allVisitCountWeight', $allVisitCountWeight, PDO::PARAM_STR);
    $stmt->bindValue(':specialization', $specialization, PDO::PARAM_STR);
    $stmt->bindValue(':searchName', '%' . $searchName . '%', PDO::PARAM_STR);
    $stmt->bindValue(':userID', $_SESSION['user_id'] ?? 0, PDO::PARAM_INT);

    try {
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Ошибка выполнения запроса: " . $e->getMessage();
        return [];
    }

    return $results;
}