<?php
// session_start();

if (isset($_SESSION['U_ID'])) {
    if ($_SESSION['U_Role'] == 'dosen') {
        header("Location: ../dosen/index.php");
    } else if ($_SESSION['U_Role'] == 'mahasiswa') {
        header("Location: ../mahasiswa/index.php");
    }
}
// exit();
