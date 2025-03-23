<?php
session_start();
include('../../assets/db/config.php');
include('../../auth/aksesMahasiswa.php');

$userID = $_SESSION['U_ID'];

$sql_profile = "SELECT U_Nama, U_Role, U_Foto FROM User WHERE U_ID = ?";
$stmt_profile = $conn->prepare($sql_profile);
$stmt_profile->bind_param('i', $userID);
$stmt_profile->execute();
$stmt_profile->store_result();

if ($stmt_profile->num_rows > 0) {
    $stmt_profile->bind_result($name, $role, $photo);
    $stmt_profile->fetch();
} else {
    header('Location: ../home/login.php');
    exit();
}
$stmt_profile->close();

if (isset($_GET['kelas_id'])) {
    $kelasID = intval($_GET['kelas_id']);
} else {
    echo "Kelas tidak ditentukan.";
    exit();
}

$sql_kelas = "SELECT K_NamaKelas, K_MataKuliah FROM Kelas WHERE K_ID = ?";
$stmt_kelas = $conn->prepare($sql_kelas);
$stmt_kelas->bind_param('i', $kelasID);
$stmt_kelas->execute();
$result_kelas = $stmt_kelas->get_result();

if ($result_kelas->num_rows > 0) {
    $kelas = $result_kelas->fetch_assoc();
    $namaKelas = $kelas['K_NamaKelas'];
    $mataKuliah = $kelas['K_MataKuliah'];
} else {
    echo "Kelas tidak ditemukan.";
    exit();
}
$stmt_kelas->close();

$sql_pertemuan_count = "SELECT COUNT(*) AS total_pertemuan FROM Absen_Dosen WHERE Kelas_K_ID = ?";
$stmt_pertemuan_count = $conn->prepare($sql_pertemuan_count);
$stmt_pertemuan_count->bind_param('i', $kelasID);
$stmt_pertemuan_count->execute();
$stmt_pertemuan_count->store_result();
$stmt_pertemuan_count->bind_result($total_pertemuan);
$stmt_pertemuan_count->fetch();
$stmt_pertemuan_count->close();

$sql_status_count = "SELECT 
                        COALESCE(SUM(CASE WHEN AM_Status = 1 THEN 1 ELSE 0 END), 0) AS hadir,
                        COALESCE(SUM(CASE WHEN AM_Status = 2 THEN 1 ELSE 0 END), 0) AS izin,
                        COALESCE(SUM(CASE WHEN AM_Status = 3 THEN 1 ELSE 0 END), 0) AS sakit,
                        COALESCE(SUM(CASE WHEN AM_Status = 4 THEN 1 ELSE 0 END), 0) AS alpa
                    FROM Absen_Mahasiswa
                    WHERE Kelas_K_ID = ? AND User_U_ID = ?";
$stmt_status_count = $conn->prepare($sql_status_count);
$stmt_status_count->bind_param('ii', $kelasID, $userID);
$stmt_status_count->execute();
$stmt_status_count->store_result();
$stmt_status_count->bind_result($hadir, $izin, $sakit, $alpa);
$stmt_status_count->fetch();
$stmt_status_count->close();

$total_status = ($hadir + $izin + $sakit + $alpa);

if ($total_status < $total_pertemuan) {
    $remaining_alpa = $total_pertemuan - $total_status;
    $alpa += $remaining_alpa;
}

$sql_dosen = "SELECT U.U_Nama 
              FROM User U
              INNER JOIN User_Kelas UK ON U.U_ID = UK.User_U_ID
              WHERE UK.Kelas_K_ID = ? AND U.U_Role = 'dosen'";
$stmt_dosen = $conn->prepare($sql_dosen);
$stmt_dosen->bind_param('i', $kelasID);
$stmt_dosen->execute();
$result_dosen = $stmt_dosen->get_result();

$dosen_names = [];
while ($row = $result_dosen->fetch_assoc()) {
    $dosen_names[] = $row['U_Nama'];
}
$stmt_dosen->close();

$sql_absen = "SELECT AD_ID, AD_TanggalDibuat, AD_Deskripsi, AD_Pertemuan, AD_Kode 
              FROM Absen_Dosen 
              WHERE Kelas_K_ID = ?";
$stmt_absen = $conn->prepare($sql_absen);
$stmt_absen->bind_param('i', $kelasID);
$stmt_absen->execute();
$result_absen = $stmt_absen->get_result();
$stmt_absen->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = $_POST['code'];
    $attendance = $_POST['attendance'];

    // Memeriksa apakah kode presensi benar
    $sql_check_code = "SELECT AD_ID FROM Absen_Dosen WHERE AD_Kode = ? AND Kelas_K_ID = ?";
    $stmt_check_code = $conn->prepare($sql_check_code);
    $stmt_check_code->bind_param('si', $code, $kelasID);
    $stmt_check_code->execute();
    $stmt_check_code->store_result();

    if ($stmt_check_code->num_rows > 0) {
        $stmt_check_code->bind_result($absenDosenID);
        $stmt_check_code->fetch();

        $status = 1; 

        $insert_sql = "INSERT INTO Absen_Mahasiswa (AM_Status, Absen_Dosen_AD_ID, Kelas_K_ID, User_U_ID, AM_Deskripsi) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($insert_sql);
        $stmt_insert->bind_param('iiiss', $status, $absenDosenID, $kelasID, $userID, $attendance);
        $stmt_insert->close();

        header('Location: absen.php?kelas_id=' . $kelasID);
        exit();
    } else {
        echo "<script>alert('Kode presensi tidak valid.'); window.location.href='absen.php?kelas_id=" . $kelasID . "';</script>";
        exit();
    }

    $stmt_check_code->close();
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

        /* Style untuk modal (overlay) */
        .modal {
            display: none;
            /* Tersembunyi secara default */
            position: fixed;
            /* Tetap di tempat */
            z-index: 1;
            /* Berada di atas */
            left: 0;
            top: 0;
            width: 100%;
            /* Lebar penuh */
            height: 100%;
            /* Tinggi penuh */
            overflow: auto;
            /* Aktifkan scroll jika diperlukan */
            background-color: rgba(0, 0, 0, 0.4);
            /* Hitam dengan opasitas */
        }

        /* Tombol Tutup */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: red;
            text-decoration: none;
            cursor: pointer;
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
        <div class="hamburger text-white px-6 py-2 cursor-pointer flex md:flex">
            <span class="material-symbols-outlined text-3xl">menu</span>
        </div>
        <div>
            <ul class="flex flex-col space-y-6 px-6 pt-2 pb-6 text-white">
                <li>
                    <a href="../mahasiswa/index.php"
                        class="flex items-center hover:-translate-y-1 transition menu-item text-xl relative">
                        <span class="material-symbols-outlined text-light-teal text-3xl">home</span>
                        <span class="link-text ml-3">Beranda</span>
                        <span class="tooltip">Beranda</span>
                    </a>
                </li>
                <li>
                    <a href="../mahasiswa/kelas.php"
                        class="flex items-center hover:-translate-y-1 transition menu-item text-xl relative">
                        <span class="material-symbols-outlined text-light-teal text-3xl">school</span>
                        <span class="link-text ml-3">Kelas</span>
                        <span class="tooltip">Kelas</span>
                    </a>
                </li>
                <li>
                    <a href="../mahasiswa/tugas.php"
                        class="flex items-center hover:-translate-y-1 transition menu-item text-xl relative">
                        <span class="material-symbols-outlined text-light-teal text-3xl">task</span>
                        <span class="link-text ml-3">Tugas</span>
                        <span class="tooltip">Tugas</span>
                    </a>
                </li>
                <li>
                    <a href="../mahasiswa/presensi.php"
                        class="flex items-center hover:-translate-y-1 transition menu-item text-xl relative">
                        <span class="material-symbols-outlined text-light-teal text-3xl">overview</span>
                        <span class="link-text ml-3">Presensi</span>
                        <span class="tooltip">Presensi</span>
                    </a>
                </li>
                <li>
                    <a href="../mahasiswa/pengaturan.php"
                        class="flex items-center hover:-translate-y-1 transition menu-item text-xl relative">
                        <span class="material-symbols-outlined text-light-teal text-3xl">settings</span>
                        <span class="link-text ml-3">Pengaturan</span>
                        <span class="tooltip">Pengaturan</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center hover:-translate-y-1 transition menu-item text-xl relative" onclick="confirmLogout(event)">
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

    </div>
    <!-- UTAMA -->
    <div id="utama" class="w-full md:w-5/6 load">
        <div class="p-6 rounded-lg shadow-md flex flex-row justify-between">
            <div class="header mb-4">
                <h1 class="px-4 text-3xl font-bold text-dark-teal uppercase mb-1">
                    <?php echo htmlspecialchars($mataKuliah); ?>
                </h1>

                <h2 class="px-4 text-2xl text-teal-600 font-bold mb-2">Dosen:</h2>

                <?php
                if (!empty($dosen_names)) {
                    foreach ($dosen_names as $dosen) {
                        echo '<p class="px-4 text-xl text-teal-600 italic mb-1">' . htmlspecialchars($dosen) . '</p>';
                    }
                } else {
                    echo '<p class="px-4 text-xl text-teal-600 italic mb-1">Tidak ada dosen yang terdaftar.</p>';
                }
                ?>
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

            <div class="w-full md:w-1/2 mt-4 md:mt-0">
                <table class="w-full text-center md:text-right border-collapse text-lg lg:text-xl">
                    <thead class="font-bold">
                        <tr>
                            <th class="py-2">TOTAL TATAP MUKA TERLAKSANA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="py-2"><?= $total_pertemuan ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="p-6 rounded-lg flex flex-col md:flex-row items-center justify-center w-5/6 mx-auto h-auto bg-gray-100 mt-8 overflow-x-auto">
            <table class="class-table w-full border-collapse">
                <thead>
                    <tr class="text-dark-teal w-1/5">
                        <th class="border-b p-4 text-left font-medium">Tatap muka</th>
                        <th class="border-b p-4 text-left font-medium">Jadwal</th>
                        <th class="border-b p-4 text-left font-medium">Topik</th>
                        <th class="border-b p-4 text-left font-medium">Status</th>
                        <th class="border-b p-4 text-left font-medium">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_absen->num_rows > 0) : ?>
                        <?php while ($row = $result_absen->fetch_assoc()) : ?>
                            <tr class="transition">
                                <td class="p-4"><?= htmlspecialchars($row['AD_Pertemuan']) ?></td>
                                <td class="p-4"><?= date('d F Y', strtotime($row['AD_TanggalDibuat'])); ?></td>
                                <td class="p-4"><?= htmlspecialchars($row['AD_Deskripsi']) ?></td>
                                <td class="p-4">
                                    <?php
                                    $stmt_status = $conn->prepare("SELECT AM_Status FROM Absen_Mahasiswa WHERE Absen_Dosen_AD_ID = ? AND User_U_ID = ?");
                                    $stmt_status->bind_param('ii', $row['AD_ID'], $userID);
                                    $stmt_status->execute();
                                    $stmt_status->store_result();

                                    if ($stmt_status->num_rows > 0) {
                                        $stmt_status->bind_result($status);
                                        $stmt_status->fetch();
                                        switch ($status) {
                                            case 1:
                                                $statusClass = 'text-blue-700 py-2 font-bold';
                                                $statusText = 'HADIR';
                                                break;
                                            case 2:
                                                $statusClass = 'text-purple-700 py-2 font-bold';
                                                $statusText = 'IZIN';
                                                break;
                                            case 3:
                                                $statusClass = 'text-yellow-600 py-2 font-bold';
                                                $statusText = 'SAKIT';
                                                break;
                                            case 4:
                                                $statusClass = 'text-red-600 py-2 font-bold';
                                                $statusText = 'ALPA';
                                                break;
                                            default:
                                                $statusClass = 'text-red-600 py-2 font-bold';
                                                $statusText = 'ALPA';
                                                break;
                                        }
                                    } else {
                                        $statusClass = 'text-red-600 py-2 font-bold';
                                        $statusText = 'ALPA';
                                    }
                                    ?>
                                    <span class="<?= $statusClass ?>"><?= $statusText ?></span>
                                </td>
                                <td class="p-4">
                                    <button
                                        class="relative bg-dark-teal text-white text-lg px-4 py-2 w-fit h-fit rounded-xl border hover:bg-white hover:border-light-teal hover:text-light-teal"
                                        onclick="openModal('<?= htmlspecialchars($row['AD_Kode']) ?>')">Kode
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="myModal" class="modal">
            <form class="modal-content bg-white p-5 border border-gray-300 rounded-lg shadow-lg mx-auto mt-20 w-1/3" action="absen.php?kelas_id=<?php echo $kelasID; ?>" method="POST">
                <span class="close text-2xl font-bold text-gray-500 cursor-pointer float-right" onclick="closeModal()">&times;</span>
                <div class="mt-5">
                    <label for="code" class="block text-sm font-medium text-gray-700">Masukkan 6 Digit Kode Presensi: <span class="text-red-500">*</span></label>
                    <input type="text" id="code" name="code" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="mt-4 mb-2 text-sm font-medium text-gray-700">Kehadiran Kuliah <span class="text-red-500">*</span></p>
                    <div class="flex items-center mb-4">
                        <input type="radio" id="class" name="attendance" value="Kehadiran di kelas" required class="text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <label for="class" class="ml-2 text-sm text-gray-700">Saya hadir kuliah di kelas</label>
                    </div>
                    <div class="flex items-center mb-4">
                        <input type="radio" id="online" name="attendance" value="Kehadiran secara online" required class="text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <label for="online" class="ml-2 text-sm text-gray-700">Saya hadir kuliah secara online</label>
                    </div>
                    <div class="flex items-center mb-4 italic">
                        <p>*Untuk Perizinan, harap menghubungi Dosen</p>
                    </div>
                    <button type="submit" class="w-full bg-dark-teal hover:bg-light-teal text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Simpan</button>
                </div>
            </form>
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

        // Mendapatkan modal
        var modal = document.getElementById("myModal");

        // Mendapatkan tombol yang membuka modal
        var btn = document.querySelector(".open-modal-btn");

        // Fungsi untuk membuka modal
        function openModal() {
            modal.style.display = "block";
        }

        // Fungsi untuk menutup modal
        function closeModal() {
            modal.style.display = "none";
        }

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
                        link = `./kelas.php`;
                    } else if (item.type === 'tugas') {
                        icon = '<span class="material-symbols-outlined text-dark-teal mr-2">task</span>';
                        link = `./tugaskelas.php?kelas_id=${item.kelasID}`;
                    } else if (item.type === 'pertemuan') {
                        icon = '<span class="material-symbols-outlined text-dark-teal mr-2">event</span>';
                        link = `./absen.php?kelas_id=${item.kelasID}`;
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