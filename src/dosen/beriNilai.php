<?php
session_start();
include('../../assets/db/config.php');
include('../../auth/aksesDosen.php');

$userID = $_SESSION['U_ID'];

$sql_profile = "SELECT U_Nama, U_Role, U_Foto FROM User WHERE U_ID = ?";
$stmt_profile = $conn->prepare($sql_profile);
$stmt_profile->bind_param('i', $userID);
$stmt_profile->execute();
$stmt_profile->store_result();

$error = '';

if ($stmt_profile->num_rows > 0) {
    $stmt_profile->bind_result($name, $role, $photo);
    $stmt_profile->fetch();
} else {
    header('Location: ../home/login.php');
    exit();
}

$header_sql = "SELECT K_MataKuliah, K_NamaKelas FROM Kelas WHERE K_ID = ?";
$stmt_header = $conn->prepare($header_sql);
$stmt_header->bind_param('i', $kelasID);
$stmt_header->execute();
$stmt_header->store_result();
$stmt_header->bind_result($mataKuliah, $namaKelas);
$stmt_header->fetch();
$stmt_header->close();

if (isset($_GET['IDK']) && isset($_GET['IDT'])) {
    $tugasID = $_GET['IDT'];
    $kelasID = $_GET['IDK'];
} else {
    $error = 'Tugas tidak ditemukan';
}

$header_sql = "SELECT K_MataKuliah, K_NamaKelas FROM Kelas WHERE K_ID = ?";
$stmt_header = $conn->prepare($header_sql);
$stmt_header->bind_param('i', $kelasID);
$stmt_header->execute();
$stmt_header->store_result();
$stmt_header->bind_result($mataKuliah, $namaKelas);
$stmt_header->fetch();
$stmt_header->close();

$nama_sql = "SELECT TD_Judul FROM Tugas_Dosen WHERE TD_ID = ?";
$stmt_nama = $conn->prepare($nama_sql);
$stmt_nama->bind_param('i', $tugasID);
$stmt_nama->execute();
$stmt_nama->store_result();
$stmt_nama->bind_result($judulTugas);
$stmt_nama->fetch();
$stmt_nama->close();

$jawaban_sql = "SELECT TM_ID, TM_FileTugas, TM_WaktuPengumpulan, TM_Status, User_U_ID, TM_NilaiTugas, U.U_Nama FROM Tugas_Mahasiswa TM INNER JOIN User U ON TM.User_U_ID = U.U_ID WHERE TM.Tugas_Dosen_TD_ID = ? AND TM.Kelas_K_ID = ?";
$stmt_jawaban = $conn->prepare($jawaban_sql);
$stmt_jawaban->bind_param('ii', $tugasID, $kelasID);
$stmt_jawaban->execute();
$stmt_jawaban->store_result();

$stmt_jawaban->bind_result($tugasMahasiswaID, $fileTugas, $waktuPengumpulan, $status, $mahasiswaID, $nilaiTugas, $namaMahasiswa);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nilai = $_POST['nilai'];
    $tugasMahasiswaID = $_POST['tugasMahasiswaID'];

    $update_sql = "UPDATE Tugas_Mahasiswa SET TM_NilaiTugas = ? WHERE TM_ID = ?";
    $stmt_update = $conn->prepare($update_sql);
    $stmt_update->bind_param('ii', $nilai, $tugasMahasiswaID);
    $stmt_update->execute();
    $stmt_update->close();
    header('Location: beriNilai.php?IDK=' . $kelasID . '&IDT=' . $tugasID);
    exit();
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
    <div id="utama" class="w-full md:w-5/6 load p-6">
        <div class="bg-white shadow-md rounded-lg p-6 mb-6 flex flex-row justify-between">
            <div class="header mb-4">
                <h1 class="text-3xl font-bold text-dark-teal uppercase mb-2">Penilaian <?php echo htmlspecialchars($namaKelas) ?></h1>
                <p class="text-xl text-teal-600 italic"><?php echo htmlspecialchars($mataKuliah) ?></p>
            </div>
        </div>
        <div class="bg-white shadow-lg rounded-lg p-8">
            <div class="text-dark-teal text-lg px-4 py-2 w-fit h-fit rounded border-dark-teal border mb-6">
                <?php echo htmlspecialchars($judulTugas) ?>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full mt-6 border-collapse">
                    <thead>
                        <tr class="text-dark-teal">
                            <th class="border-b p-4 text-left font-medium">No</th>
                            <th class="border-b p-4 text-left font-medium">Nama</th>
                            <th class="border-b p-4 text-left font-medium">Pengumpulan</th>
                            <th class="border-b p-4 text-left font-medium">File</th>
                            <th class="border-b p-4 text-left font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($stmt_jawaban->fetch()): ?>
                            <tr class="transition duration-300 hover:bg-teal-50">
                                <td class="p-4"><?php echo htmlspecialchars($mahasiswaID) ?></td>
                                <td class="p-4"><?php echo htmlspecialchars($namaMahasiswa) ?></td>
                                <td class="p-4"><?php echo htmlspecialchars($waktuPengumpulan) ?></td>
                                <td class="p-4"><a href="/FP/storage/task/<?php echo htmlspecialchars($fileTugas) ?>" target="_blank"><?php echo htmlspecialchars($fileTugas) ?></a></td>
                                <td class="p-4">
                                    <form action="#" method="POST">
                                        <input type="number" name="nilai" placeholder="Nilai" min="0" max="100"
                                            class="relative bg-white text-dark-teal text-lg px-4 py-2 w-20 h-fit rounded-xl border border-dark-teal focus:outline-none focus:ring-2 focus:ring-light-teal" value="<?php echo htmlspecialchars($nilaiTugas) ?>">
                                        <input type="hidden" name="tugasMahasiswaID" value="<?php echo htmlspecialchars($tugasMahasiswaID) ?>">
                                        <button type="submit"
                                            class="bg-dark-teal text-white text-lg px-4 py-2 w-fit h-fit rounded-xl border hover:bg-white hover:border-light-teal hover:text-light-teal">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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