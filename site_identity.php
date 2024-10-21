<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'doctor') {
    $redirectPage = 'doctor.php';
} else {
    $redirectPage = 'main.php';
}
?>
<a href="<?php echo $redirectPage; ?>" class="d-inline-flex link-body-emphasis text-decoration-none">
    <h1 class="name-site">MyMedicine</h1>
</a>