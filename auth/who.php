<?php
session_start();
include('../assets/db/config.php');

if (!isset($_SESSION['U_ID'])) {
    echo "UserID tidak ditemukan dalam sesi.";
    exit();
}

$sql_who = "SELECT U_Nama, U_Role FROM User WHERE U_ID = ?";
$stmt_who = $conn->prepare($sql_who);
$stmt_who->bind_param("i", $_SESSION['U_ID']);
$stmt_who->execute();
$stmt_who->store_result();

if ($stmt_who->num_rows > 0) {
    $stmt_who->bind_result($name, $role);
    $stmt_who->fetch();
    echo $_SESSION['U_ID'] . $name . $role;
} else {
    echo "Nama User Tidak Ditemukan";
}

$stmt_who->close();
$conn->close();
