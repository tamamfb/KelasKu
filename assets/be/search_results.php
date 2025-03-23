<?php
include('../../assets/db/config.php');

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userID = $data['userID'];
    $search = $data['search'];

    if (empty($search)) {
        echo json_encode([]);
        exit;
    }

    $sql = "
        (
            SELECT 
                'kelas' AS type,
                K.K_NamaKelas AS title,
                K.K_MataKuliah AS subtitle,
                K.K_TanggalDibuat AS date,
                K.K_ID AS id,
                K.K_ID AS kelasID
            FROM Kelas K
            JOIN User_Kelas UK ON UK.Kelas_K_ID = K.K_ID
            WHERE UK.User_U_ID = ?
              AND (
                  K.K_MataKuliah LIKE CONCAT('%',?,'%')
                  OR K.K_NamaKelas LIKE CONCAT('%',?,'%')
              )
        )
        UNION
        (
            SELECT 
                'tugas' AS type,
                TD.TD_Judul AS title,
                DATE_FORMAT(TD.TD_Deadline, '%d %M %Y %H:%i') AS subtitle,
                TD.TD_TanggalDibuat AS date,
                TD.TD_ID AS id,
                TD.Kelas_K_ID AS kelasID
            FROM Tugas_Dosen TD
            JOIN Kelas K ON K.K_ID = TD.Kelas_K_ID
            JOIN User_Kelas UK ON UK.Kelas_K_ID = K.K_ID
            WHERE UK.User_U_ID = ?
              AND (
                  TD.TD_Judul LIKE CONCAT('%',?,'%')
                  OR TD.TD_Deskripsi LIKE CONCAT('%',?,'%')
              )
        )
        UNION
        (
            SELECT 
                'pertemuan' AS type,
                K.K_NamaKelas AS title,
                CONCAT('Pertemuan ke-', A.AD_Pertemuan) AS subtitle,
                A.AD_TanggalDibuat AS date,
                A.AD_ID AS id,
                A.Kelas_K_ID AS kelasID
            FROM Absen_Dosen A
            JOIN Kelas K ON A.Kelas_K_ID = K.K_ID
            JOIN User_Kelas UK ON UK.Kelas_K_ID = K.K_ID
            WHERE UK.User_U_ID = ?
              AND (
                  A.AD_Deskripsi LIKE CONCAT('%',?,'%')
                  OR A.AD_Kode LIKE CONCAT('%',?,'%')
              )
        )
        ORDER BY date DESC
    ";

    $stmt_search = $conn->prepare($sql);

    $likeParam = '%' . $search . '%';
    $stmt_search->bind_param(
        'issississ',
        $userID,
        $likeParam,
        $likeParam,
        $userID,
        $likeParam,
        $likeParam,
        $userID,
        $likeParam,
        $likeParam
    );

    $stmt_search->execute();
    $hasil = $stmt_search->get_result();

    $data = [];
    while ($row = $hasil->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
} else {
    echo $_SERVER['REQUEST_METHOD'];
}
