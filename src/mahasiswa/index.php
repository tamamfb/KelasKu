<?php
session_start();
include('../../assets/db/config.php');
include('../../auth/aksesMahasiswa.php');
require_once '../../vendor/autoload.php';

$userID = $_SESSION['U_ID'];
$sql = "SELECT U_Nama, U_Role, U_Foto FROM User WHERE U_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userID);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($name, $role, $photo);
    $stmt->fetch();
} else {
    header('Location: ../home/login.php');
    exit();
}

$stmt->close();

date_default_timezone_set("Asia/Jakarta");
$date = new DateTime();
$hari = $date->format('l');
$tanggal = $date->format('d F Y');
$bulan = $date->format('m');
$tahun = $date->format('Y');
// $jam = $date->format('H:i:s');


$key = '210b802c-ef69-4346-8174-53b17a97bcb0';
$holiday_api = new \HolidayAPI\Client(['key' => $key]);

try {
    // // Fetch supported countries and subdivisions
    // $countries = $holiday_api->countries();

    // // Fetch supported languages
    // $languages = $holiday_api->languages();

    // Fetch holidays with minimum parameters
    $data = $holiday_api->holidays([
        'country' => 'ID',
        'year' => 2023,
        'language' => 'id',
    ]);

    $filter = array_filter($data['holidays'], function ($holiday) use ($bulan) {
        $holidayMonth = (new DateTime($holiday['date']))->format('m');
        return $holidayMonth == $bulan;
    });

    $data_json = json_encode(array_values($filter));

    // foreach ($data['holidays'] as $holiday) {
    //     echo $holiday['weekday']['date']['name'] . "\n";
    // }
} catch (Exception $e) {
    $data_json = json_encode([]);
}

$listKelas = [];
$listKelas_sql = "SELECT K.K_NamaKelas, K.K_MataKuliah, K.K_ID
    FROM Kelas K
    INNER JOIN User_Kelas UK ON K.K_ID = UK.Kelas_K_ID
    WHERE UK.User_U_ID = ? ";
$stmt_listKelas = $conn->prepare($listKelas_sql);
$stmt_listKelas->bind_param('i', $userID);
$stmt_listKelas->execute();
$hasilListKelas = $stmt_listKelas->get_result();
while ($row = $hasilListKelas->fetch_assoc()) {
    $listKelas[] = $row;
}
$stmt_listKelas->close();

$listTugas = [];
$listTugas_sql = "
    SELECT TD.TD_Judul, TD.TD_Deadline
    FROM Tugas_Dosen TD
    INNER JOIN User_Kelas UK ON TD.Kelas_K_ID = UK.Kelas_K_ID
    INNER JOIN User U ON UK.User_U_ID = U.U_ID
    WHERE U.U_ID = ? 
      AND MONTH(TD.TD_Deadline) = ? 
      AND YEAR(TD.TD_Deadline) = ?";
$stmt_listTugas = $conn->prepare($listTugas_sql);
$stmt_listTugas->bind_param('iii', $userID, $bulan, $tahun);
$stmt_listTugas->execute();
$hasilListTugas = $stmt_listTugas->get_result();
while ($row = $hasilListTugas->fetch_assoc()) {
    $listTugas[] = $row;
}
$stmt_listTugas->close();


$listPertemuan = [];
$listPertemuan_sql = "SELECT AD.AD_Pertemuan, AD.AD_TanggalDibuat, UK.Kelas_K_ID, K.K_MataKuliah
    FROM Absen_Dosen AD
    INNER JOIN User_Kelas UK ON AD.Kelas_K_ID = UK.Kelas_K_ID
    INNER JOIN Kelas K ON AD.Kelas_K_ID = K.K_ID
    WHERE UK.User_U_ID = ? 
      AND MONTH(AD.AD_TanggalDibuat) = ? 
      AND YEAR(AD.AD_TanggalDibuat) = ?
    GROUP BY AD.AD_ID";

$stmt_listPertemuan = $conn->prepare($listPertemuan_sql);
$stmt_listPertemuan->bind_param('iii', $userID, $bulan, $tahun);
$stmt_listPertemuan->execute();
$hasilListPertemuan = $stmt_listPertemuan->get_result();
while ($row = $hasilListPertemuan->fetch_assoc()) {
    $listPertemuan[] = $row;
}
$stmt_listPertemuan->close();
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

        .class-card,
        .task-card {
            max-height: 200px;
            overflow-y: auto;
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
    <!-- UTAMA -->
    <div id="utama" class="w-full md:w-5/6 load">
        <div id="default-carousel" class="relative w-full" data-carousel="slide">
            <!-- Carousel wrapper -->
            <div class="relative h-56 overflow-hidden rounded-lg md:h-96">
                <!-- Item 1 -->
                <div class="hidden duration-700 ease-in-out" data-carousel-item>
                    <img src="../../assets/img/carousel.png" class="absolute block w-full h-full object-contain -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="...">
                </div>
                <!-- Item 2 -->
                <div class="hidden duration-700 ease-in-out" data-carousel-item>
                    <img src="../../assets/img/carousel.png" class="absolute block w-full h-full object-contain -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="...">
                </div>
                <!-- Item 3 -->
                <div class="hidden duration-700 ease-in-out" data-carousel-item>
                    <img src="../../assets/img/carousel.png" class="absolute block w-full h-full object-contain -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="...">
                </div>
                <!-- Item 4 -->
                <div class="hidden duration-700 ease-in-out" data-carousel-item>
                    <img src="../../assets/img/carousel.png" class="absolute block w-full h-full object-contain -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="...">
                </div>
                <!-- Item 5 -->
                <div class="hidden duration-700 ease-in-out" data-carousel-item>
                    <img src="../../assets/img/carousel.png" class="absolute block w-full h-full object-contain -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="...">
                </div>
            </div>
            <!-- Slider indicators -->
            <div class="absolute z-30 flex -translate-x-1/2 bottom-5 left-1/2 space-x-3 rtl:space-x-reverse">
                <button type="button" class="w-3 h-3 rounded-full" aria-current="true" aria-label="Slide 1" data-carousel-slide-to="0"></button>
                <button type="button" class="w-3 h-3 rounded-full" aria-current="false" aria-label="Slide 2" data-carousel-slide-to="1"></button>
                <button type="button" class="w-3 h-3 rounded-full" aria-current="false" aria-label="Slide 3" data-carousel-slide-to="2"></button>
                <button type="button" class="w-3 h-3 rounded-full" aria-current="false" aria-label="Slide 4" data-carousel-slide-to="3"></button>
                <button type="button" class="w-3 h-3 rounded-full" aria-current="false" aria-label="Slide 5" data-carousel-slide-to="4"></button>
            </div>
            <!-- Slider controls -->
            <button type="button" class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-prev>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-800/30 group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-gray-800/70 group-focus:outline-none">
                    <svg class="w-4 h-4 text-gray-800 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4" />
                    </svg>
                    <span class="sr-only">Previous</span>
                </span>
            </button>
            <button type="button" class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-next>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-800/30 group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-gray-800/70 group-focus:outline-none">
                    <svg class="w-4 h-4 text-gray-800 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4" />
                    </svg>
                    <span class="sr-only">Next</span>
                </span>
            </button>
        </div>
        <div class="bg-white rounded-lg p-8 flex flex-col items-center text-center mb-6">
            <h1 class="text-4xl font-bold text-dark-teal uppercase mb-4">Selamat Datang, <?php echo htmlspecialchars($name); ?></h1>
            <p class="text-xl text-gray-700 italic mb-6"><?php echo $hari . ', ' . $tanggal; ?></p>
            <div id="clock" class="text-2xl text-gray-700"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white shadow-lg rounded-lg p-8 mb-6">
                <h2 class="text-2xl font-semibold text-dark-teal mb-4">Daftar Kelas</h2>
                <div class="class-card space-y-4">
                    <?php if (!empty($listKelas)): ?>
                        <?php foreach ($listKelas as $kelas): ?>
                            <div class="flex justify-between border-b py-2">
                                <a href="kelas.php" class="text-dark-teal"><?php echo htmlspecialchars($kelas['K_NamaKelas']); ?></a>
                                <span class="text-gray-500"><?php echo htmlspecialchars($kelas['K_MataKuliah']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500">Tidak ada kelas.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-lg p-8 mb-6">
                <h2 class="text-2xl font-semibold text-dark-teal mb-4">Daftar Tugas</h2>
                <div class="task-card space-y-4">
                    <?php if (!empty($listTugas)): ?>
                        <?php foreach ($listTugas as $tugas): ?>
                            <div class="flex justify-between border-b py-2">
                                <a href="#" class="text-dark-teal"><?php echo htmlspecialchars($tugas['TD_Judul']); ?></a>
                                <span class="text-gray-500"><?php echo htmlspecialchars($tugas['TD_Deadline']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500">Tidak ada tugas.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-lg p-8 mb-6">
                <h2 class="text-2xl font-semibold text-dark-teal mb-4">Daftar Pertemuan</h2>
                <div class="class-card space-y-4">
                    <?php if (!empty($listPertemuan)): ?>
                        <?php foreach ($listPertemuan as $pertemuan): ?>
                            <div class="flex justify-between border-b py-2">
                                <a href="absen.php?kelas_id=<?= htmlspecialchars($pertemuan['Kelas_K_ID']) ?>" class="text-dark-teal">
                                    <?php echo htmlspecialchars($pertemuan['AD_Pertemuan']); ?>
                                </a>
                                <span class="text-gray-500"><?php echo htmlspecialchars($pertemuan['K_MataKuliah']); ?></span>
                                <span class="text-gray-500"><?php echo htmlspecialchars($pertemuan['AD_TanggalDibuat']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500">Tidak ada pertemuan.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-lg p-8 mb-6">
                <h2 class="text-2xl font-semibold text-dark-teal mb-4">Hari Libur Nasional</h2>
                <div id="kalender" class="task-card space-y-4"></div>
            </div>
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

        function updateClock() {
            const now = new Date();
            const timeNow = new Date(now.toLocaleString("en-US", {
                timeZone: "Asia/Jakarta"
            }));
            const hours = String(timeNow.getHours()).padStart(2, '0');
            const minutes = String(timeNow.getMinutes()).padStart(2, '0');
            const seconds = String(timeNow.getSeconds()).padStart(2, '0');
            document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // console.log(<?php echo $data_json; ?>);
        dataLibur = <?php echo $data_json; ?>;
        const kalender = document.getElementById('kalender');
        if (dataLibur.length) {
            dataLibur.forEach(holiday => {
                const divLibur = document.createElement('div');
                divLibur.classList.add('flex', 'justify-between', 'border-b', 'py-2');
                divLibur.innerHTML = `
                    <a href="#" class="text-dark-teal">${holiday.name}</a>
                    <span class="text-gray-500">${holiday.date}</span>
                `;
                kalender.appendChild(divLibur);
            });
        } else {
            kalender.innerHTML = '<p class="text-gray-500">Tidak ada hari libur</p>';
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
    <script src="../../node_modules/flowbite/dist/flowbite.min.js"></script>
</body>

</html>