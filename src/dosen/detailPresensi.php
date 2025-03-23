<?php
session_start();
include('../../assets/db/config.php');
include('../../auth/aksesDosen.php');

$userID = $_SESSION['U_ID'];
$sql = "SELECT U_Nama, U_Role, U_Foto FROM User WHERE U_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userID);
$stmt->execute();
$stmt->store_result();

$error = '';

if ($stmt->num_rows > 0) {
    $stmt->bind_result($name, $role, $photo);
    $stmt->fetch();
} else {
    header('Location: ../home/login.php');
    exit();
}


if (isset($_GET['IDK']) && isset($_GET['IDA'])) {
    $kelasID = $_GET['IDK'];
    $absenID = $_GET['IDA'];
}

$detail_sql
    = "SELECT K.K_NamaKelas, K.K_MataKuliah, AD.AD_Pertemuan, AD.AD_Kode
        FROM Absen_Dosen AD
        JOIN Kelas K ON AD.Kelas_K_ID = K.K_ID
        WHERE AD.AD_ID = ?";
$detail_stmt = $conn->prepare($detail_sql);
$detail_stmt->bind_param('i', $absenID);
$detail_stmt->execute();
$detail_stmt->store_result();
$detail_stmt->bind_result($namaKelas, $mataKuliah, $pertemuan, $kode);
$detail_stmt->fetch();
$detail_stmt->close();

$sql_absen
    = "
        SELECT U.U_ID, U.U_Nama, AM.AM_Status, AD.AD_Kode
        FROM User U
        LEFT JOIN User_Kelas UK ON UK.User_U_ID = U.U_ID
        LEFT JOIN Absen_Mahasiswa AM ON AM.User_U_ID = U.U_ID AND AM.Absen_Dosen_AD_ID = ?
        LEFT JOIN Absen_Dosen AD ON AD.AD_ID = ?
        WHERE U.U_Role = 'mahasiswa' AND UK.Kelas_K_ID = ?
        ORDER BY U.U_ID ASC
    ";
$stmt_absen = $conn->prepare($sql_absen);
$stmt_absen->bind_param('iii', $absenID, $absenID, $kelasID);
$stmt_absen->execute();
$result_absen = $stmt_absen->get_result();

$hadir = $izin = $sakit = $alpa = 0;

while ($rowCount = $result_absen->fetch_assoc()) {
    $statusAbsen = ($rowCount['AM_Status'] == NULL) ? 4 : $rowCount['AM_Status'];
    switch ($statusAbsen) {
        case 1:
            $hadir++;
            break;
        case 2:
            $izin++;
            break;
        case 3:
            $sakit++;
            break;
        case 4:
            $alpa++;
            break;
    }
}

$result_absen->data_seek(0);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['status'])) {
    $userID = $_POST['userID'];
    $absenID = $_POST['absenID'];
    $status = $_POST['status'];

    $check_sql = "SELECT AM_Status FROM Absen_Mahasiswa WHERE User_U_ID = ? AND Absen_Dosen_AD_ID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $userID, $absenID);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $update_sql = "UPDATE Absen_Mahasiswa SET AM_Status = ? WHERE User_U_ID = ? AND Absen_Dosen_AD_ID = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('iii', $status, $userID, $absenID);

        if ($update_stmt->execute()) {
            header('Location: detailPresensi.php?IDK=' . $kelasID . '&IDA=' . $absenID);
            exit();
        } else {
            echo "Gagal" . $conn->error;
        }
    } else {
        $insert_sql = "INSERT INTO Absen_Mahasiswa (User_U_ID, Absen_Dosen_AD_ID, AM_Status, Kelas_K_ID) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param('iiii', $userID, $absenID, $status, $kelasID);

        if ($insert_stmt->execute()) {
            header('Location: detailPresensi.php?IDK=' . $kelasID . '&IDA=' . $absenID);
            exit();
        } else {
            echo "Gagal" . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KelasKu</title>
    <link rel="stylesheet" href="../../assets/output.css">
    <style>
        .load {
            animation: transitionIn 0.75s;
        }

        @keyframes transitionIn {
            from {
                opacity: 0;
                transform: rotateX(-10deg);
            }

            to {
                opacity: 1;
                transform: rotateX(0);
            }
        }

        #sidebar {
            transition: width 0.3s ease;
            overflow: visible;
        }

        .sidebar-collapsed {
            width: 70px;
        }

        .sidebar-collapsed .link-text,
        .sidebar-collapsed .profile-text {
            display: none;
        }

        .sidebar-collapsed .menu-item {
            justify-content: center;
        }

        .sidebar-collapsed .hamburger {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }

        .sidebar-collapsed .profile-container {
            flex-direction: column;
            align-items: center;
        }

        .profile-container img {
            object-fit: cover;
            width: 50px;
            height: 50px;
        }

        .menu-item,
        .hamburger,
        .profile-container {
            transition: all 0.3s ease;
        }

        .menu-item {
            position: relative;
        }

        .menu-item .tooltip {
            position: absolute;
            right: 100%;
            margin-right: 0.5rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            background-color: rgba(55, 65, 81, 1);
            color: rgba(255, 255, 255, 1);
            font-size: 0.875rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
            white-space: nowrap;
            z-index: 1000;
        }

        .sidebar-collapsed .menu-item:hover .tooltip {
            opacity: 1;
        }

        /* Sidebar tersembunyi pada mobile */
        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(100%);
                /* Sembunyikan sidebar di luar layar kanan */
                width: 50%;
                /* Lebar sidebar pada mobile, sesuaikan jika diperlukan */
            }

            /* Sidebar terlihat saat memiliki kelas 'active' */
            #sidebar.active {
                transform: translateX(0);
            }

            /* Sembunyikan teks pada sidebar untuk mobile */
            .profile-container,
            .tooltip {
                display: none;
            }

            /* Tampilkan hamburger-mobile dan closeSidebar-mobile pada mobile */
            #hamburger-mobile,
            #closeSidebar-mobile {
                display: block;
            }

            /* Sembunyikan ikon hamburger default di sidebar pada mobile */
            .hamburger {
                display: none;
            }
        }

        /* Sidebar terlihat pada desktop */
        @media (min-width: 769px) {

            #hamburger-mobile,
            #closeSidebar-mobile {
                display: none;
            }
        }

        /* Tambahkan animasi buka tutup untuk sidebar di mode mobile */
        @media (max-width: 768px) {
            #sidebar {
                transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
                opacity: 0;
                /* Sidebar tersembunyi secara default */
            }

            /* Sidebar terlihat saat memiliki kelas 'active' */
            #sidebar.active {
                opacity: 1;
                /* Sidebar terlihat */
            }
        }
    </style>
</head>

<body class="font-poppins">
    <!-- NAV -->
    <nav class="flex flex-col md:flex-row md:items-center justify-between p-10 text-light-teal w-full">
        <div class="flex items-center justify-between w-full md:w-auto">
            <a href="../home/login.php" class="font-modak text-4xl text-dark-teal">KelasKu</a>
            <!-- Ikon Hamburger untuk Mobile -->
            <div class="md:hidden">
                <span id="hamburger-mobile" class="material-symbols-outlined text-3xl cursor-pointer">
                    menu
                </span>
            </div>
        </div>
        <div class="w-full mt-4 md:mt-0 md:flex md:justify-center">
            <div class="relative w-full md:w-2/5 lg:w-1/4">
                <div class="flex items-center border rounded p-2 md:p-4">
                    <span class="material-symbols-outlined mr-2 text-light-teal">
                        search
                    </span>
                    <input type="text" name="search" id="pencarian" placeholder="Cari kelas, tugas, atau absen..." class="flex-1 outline-none">
                </div>
                <div id="hasilPencarian" class="absolute w-full mt-2 bg-white border rounded shadow-lg z-50 hidden">
                </div>
            </div>
        </div>
    </nav>
    <!-- SIDEBAR -->
    <div id="sidebar"
        class="fixed top-0 right-0 h-full md:w-1/6 bg-dark-teal transform translate-x-full md:translate-x-0 transition-transform duration-300 z-50 bg-opacity-90 shadow-lg flex flex-col">
        <!-- Ikon Hamburger untuk Mobile (Berfungsi Sebagai Tombol Close) -->
        <div class="text-white px-6 py-2 cursor-pointer flex md:hidden">
            <span id="closeSidebar-mobile" class="material-symbols-outlined text-3xl">
                menu
            </span>
        </div>
        <!-- Ikon Hamburger Default di Sidebar untuk Desktop (Collapse) -->
        <div class="hamburger text-white px-6 py-2 cursor-pointer md:flex hidden">
            <span class="material-symbols-outlined text-3xl">menu</span>
        </div>
        <div>
            <ul class="flex flex-col space-y-6 px-6 pt-2 pb-6 text-white">
                <li>
                    <a href="../dosen/index.php"
                        class="flex items-center hover:-translate-y-1 transition menu-item text-xl relative">
                        <span class="material-symbols-outlined text-light-teal text-3xl">home</span>
                        <span class="link-text ml-3">Beranda</span>
                        <span class="tooltip">Beranda</span>
                    </a>
                </li>
                <li>
                    <a href="../dosen/kelas.php"
                        class="flex items-center hover:-translate-y-1 transition menu-item text-xl relative">
                        <span class="material-symbols-outlined text-light-teal text-3xl">school</span>
                        <span class="link-text ml-3">Kelas</span>
                        <span class="tooltip">Kelas</span>
                    </a>
                </li>
                <li>
                    <a href="../dosen/tugas.php"
                        class="flex items-center hover:-translate-y-1 transition menu-item text-xl relative">
                        <span class="material-symbols-outlined text-light-teal text-3xl">task</span>
                        <span class="link-text ml-3">Tugas</span>
                        <span class="tooltip">Tugas</span>
                    </a>
                </li>
                <li>
                    <a href="../dosen/presensi.php"
                        class="flex items-center hover:-translate-y-1 transition menu-item text-xl relative">
                        <span class="material-symbols-outlined text-light-teal text-3xl">overview</span>
                        <span class="link-text ml-3">Presensi</span>
                        <span class="tooltip">Presensi</span>
                    </a>
                </li>
                <li>
                    <a href="../dosen/pengaturan.php"
                        class="flex items-center hover:-translate-y-1 transition menu-item text-xl relative">
                        <span class="material-symbols-outlined text-light-teal text-3xl">settings</span>
                        <span class="link-text ml-3">Pengaturan</span>
                        <span class="tooltip">Pengaturan</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center hover:-translate-y-1 transition menu-item text-xl relative"
                        onclick="confirmLogout(event)">
                        <span class="material-symbols-outlined text-light-teal text-3xl">logout</span>
                        <span class="link-text ml-3">Keluar</span>
                        <span class="tooltip">Keluar</span>
                    </a>
                </li>
            </ul>
        </div>
        <!-- Profil -->
        <div class="profile-container flex items-center space-x-4 p-6 mt-auto">
            <img src="../../assets/img/<?php echo $photo ?>" alt="Foto Profil" class="rounded-xl w-12 h-12">
            <div class="flex flex-col profile-text">
                <span class="font-bold text-xl text-white"><?php echo htmlspecialchars($name); ?></span>
                <span class="text-white"><?php echo htmlspecialchars(strtoupper($role)); ?></span>
            </div>
        </div>
    </div>
    <!-- UTAMA -->
    <div id="utama" class="w-full md:w-5/6 load p-4 md:p-6">
        <div class="bg-white shadow-md rounded-lg p-4 md:p-6 mb-6 flex flex-col sm:flex-row justify-between">
            <div class="header mb-4 sm:mb-0">
                <h1 class="text-2xl sm:text-3xl font-bold text-dark-teal uppercase mb-2">Presensi
                    <?php echo $namaKelas ?>
                </h1>
                <p class="text-lg sm:text-xl text-teal-600 italic"><?php echo htmlspecialchars($mataKuliah) ?> <span class="font-bold">[Pertemuan <?php echo htmlspecialchars($pertemuan) ?>]</span></p>
            </div>
            <div
                class="flex items-center text-xl text-dark-teal border-2 border-dashed border-dark-teal rounded cursor-pointer hover:bg-light-teal transition h-fit w-fit p-2" onclick="navigator.clipboard.writeText('<?php echo $kode ?>')">
                <?php echo $kode ?>
                <span class="material-symbols-outlined ml-2 text-dark-teal hover:text-white transition">
                    content_copy
                </span>
            </div>
        </div>
        <div class="p-6 rounded-lg flex flex-col md:flex-row items-center justify-center w-1/2 mx-auto h-auto bg-green-100 mt-8">
            <div class="w-full md:w-1/2 overflow-x-auto">
                <table class="w-full text-center border-collapse text-lg lg:text-xl">
                    <thead class="font-bold">
                        <tr>
                            <th class="border-r border-gray-300 w-1/4 text-blue-700 py-2">HADIR</th>
                            <th class="border-r border-gray-300 w-1/4 text-purple-700">IZIN</th>
                            <th class="border-r border-gray-300 w-1/4 text-yellow-600">SAKIT</th>
                            <th class="w-1/4 text-red-600">ALPA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border-r border-gray-300 py-2"><?= $hadir ?></td>
                            <td class="border-r border-gray-300"><?= $izin ?></td>
                            <td class="border-r border-gray-300"><?= $sakit ?></td>
                            <td><?= $alpa ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabel -->
        <div class="bg-white shadow-lg rounded-lg p-4 sm:p-8">
            <table class="w-full mt-6 border-collapse">
                <thead>
                    <tr class="text-dark-teal">
                        <th class="border-b p-4 text-left font-medium">No</th>
                        <th class="border-b p-4 text-left font-medium">Nama</th>
                        <th class="border-b p-4 text-left font-medium">Status</th>
                        <th class="border-b p-4 text-left font-medium">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = $result_absen->fetch_assoc()) {
                        $statusAbsen = ($row['AM_Status'] == NULL) ? 4 : $row['AM_Status'];
                        $statusText = '';
                        $statusClass = '';
                        switch ($statusAbsen) {
                            case 1:
                                $statusClass = 'text-blue-700';
                                $statusText = 'Hadir';
                                break;
                            case 2:
                                $statusClass = 'text-purple-700';
                                $statusText = 'Izin';
                                break;
                            case 3:
                                $statusClass = 'text-yellow-600';
                                $statusText = 'Sakit';
                                break;
                            case 4:
                                $statusClass = 'text-red-600';
                                $statusText = 'Alpa';
                                break;
                            default:
                                $statusClass = 'text-red-600';
                                $statusText = 'Alpa';
                                break;
                        }
                    ?>
                        <tr class="transition duration-300 hover:bg-teal-50">
                            <td class="p-4"><?php echo $row['U_ID'] ?></td>
                            <td class="p-4"><?php echo $row['U_Nama']; ?></td>
                            <td class="p-4 <?php echo $statusClass; ?>"><?php echo $statusText; ?></td>
                            <td class="p-4">
                                <form method="POST" action="">
                                    <input type="hidden" name="userID" value="<?php echo $row['U_ID']; ?>">
                                    <input type="hidden" name="absenID" value="<?php echo $absenID; ?>">
                                    <button type="submit" name="status" value="1" class="relative bg-blue-700 text-white text-lg px-4 py-2 w-12 h-12 rounded-full border hover:bg-white hover:border-blue-500 hover:text-blue-500">H</button>
                                    <button type="submit" name="status" value="2" class="relative bg-purple-700 text-white text-lg px-4 py-2 w-12 h-12 rounded-full border hover:bg-white hover:border-purple-500 hover:text-purple-500">I</button>
                                    <button type="submit" name="status" value="3" class="relative bg-yellow-600 text-white text-lg px-4 py-2 w-12 h-12 rounded-full border hover:bg-white hover:border-yellow-400 hover:text-yellow-400">S</button>
                                    <button type="submit" name="status" value="4" class="relative bg-red-600 text-white text-lg px-4 py-2 w-12 h-12 rounded-full border hover:bg-white hover:border-red-400 hover:text-red-400">A</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        const hamburger = document.querySelector('.hamburger');
        const sidebar = document.getElementById('sidebar');
        const hamburgerMobile = document.getElementById('hamburger-mobile');
        const closeSidebarMobile = document.getElementById('closeSidebar-mobile');

        const utama = document.getElementById('utama');

        let isMobile = window.innerWidth <= 768;

        window.addEventListener('resize', function() {
            const currentIsMobile = window.innerWidth <= 768;

            if (currentIsMobile !== isMobile) {
                isMobile = currentIsMobile;
                location.reload();
            }
        });


        hamburger.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-collapsed');

            if (sidebar.classList.contains('sidebar-collapsed')) {
                // console.log('tutup');
                utama.classList.remove('md:w-5/6');
                utama.classList.add('mr-[70px]');
                utama.classList.remove('w-full');
            } else {
                // console.log('buka');
                utama.classList.add('md:w-5/6');
                utama.classList.remove('mr-[70px]');
                utama.classList.add('w-full');
            }
        });

        // Fungsi untuk toggle sidebar pada mobile
        function toggleSidebar(e) {
            e.stopPropagation(); // Mencegah event bubbling
            sidebar.classList.toggle('active');
        }

        // Event listeners untuk hamburger di navbar dan tombol close di sidebar
        hamburgerMobile.addEventListener('click', toggleSidebar);
        closeSidebarMobile.addEventListener('click', toggleSidebar);

        // Menutup sidebar saat mengklik di luar sidebar pada mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) { // Hanya berlaku pada mobile
                if (!sidebar.contains(event.target) && !hamburgerMobile.contains(event.target) && !closeSidebarMobile.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Mencegah penutupan sidebar saat mengklik di dalam sidebar
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        function confirmLogout(event) {
            event.preventDefault(); // Mencegah link untuk navigasi
            const confirmation = confirm("Apakah Anda ingin keluar?");

            if (confirmation) {
                window.location.href = '../../auth/logout.php';
            } else {
                return;
            }
        }

        document.getElementById('pencarian').addEventListener('input', function() {
            const searchTerm = this.value;
            // console.log(searchTerm);
            if (searchTerm.length > 2) {
                // console.log(searchTerm);
                document.getElementById('hasilPencarian').classList.remove('hidden');
                fetchHasil(searchTerm);
            } else {
                document.getElementById('hasilPencarian').classList.add('hidden');
            }
        });

        function fetchHasil(searchTerm) {
            fetch('../../assets/be/search_results.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    redirect: 'manual',
                    body: JSON.stringify({
                        userID: <?php echo $userID; ?>,
                        search: searchTerm,
                    })
                })
                .then(response => {
                    // console.log(response.status);
                    // console.log(response.headers.get('Location'));
                    // console.log(response);
                    return response.json();
                })
                .then(data => {
                    // console.log('Data JSON:', data);
                    displayHasil(data);
                })
                .catch(error => console.error('Error:', error));
        }

        function displayHasil(data) {
            // console.log(data.length);
            let hasilPencarian = '';
            if (data.length > 0) {
                hasilPencarian = '<ul class="p-2">';
                data.forEach(item => {
                    let icon = '';
                    let link = '#';
                    if (item.type === 'kelas') {
                        icon = '<span class="material-symbols-outlined text-dark-teal mr-2">school</span>';
                        link = `./detailKelas.php?ID=${item.id}`;
                    } else if (item.type === 'tugas') {
                        icon = '<span class="material-symbols-outlined text-dark-teal mr-2">task</span>';
                        link = `./beriNilai.php?IDK=${item.kelasID}&IDT=${item.id}`;
                    } else if (item.type === 'pertemuan') {
                        icon = '<span class="material-symbols-outlined text-dark-teal mr-2">event</span>';
                        link = `./detailPresensi.php?IDK=${item.kelasID}&IDA=${item.id}`;
                    }

                    hasilPencarian += `
                <li class="py-2 border-b flex items-center">
                    ${icon}
                    <a href="${link}" class="hover:underline">
                    <div>
                        <div class="font-semibold text-dark-teal">${item.title}</div>
                        <div class="text-gray-600 text-sm">${item.subtitle}</div>
                    </div>
                    </a>
                </li>`;
                });
                hasilPencarian += '</ul>';
            } else {
                hasilPencarian = 'Tidak ada hasil yang ditemukan.';
            }
            document.getElementById('hasilPencarian').innerHTML = hasilPencarian;
        }
    </script>
</body>

</html>
<?php $conn->close(); ?>