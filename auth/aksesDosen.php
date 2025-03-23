<?php
if (!isset($_SESSION['U_ID'])) {
    header('Location: ../home/login.php');
    exit();
} else if ($_SESSION['U_Role'] !== 'dosen') {
    header('Location: ../../auth/access-denied.php');
}
