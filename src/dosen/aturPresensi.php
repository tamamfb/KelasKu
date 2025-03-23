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

if (isset($_GET['ID'])) {
    $kelasID = $_GET['ID'];
    $kelasID = htmlspecialchars($kelasID);
} else {
    $error = 'Kelas tidak ditemukan';
}

$header_sql = "SELECT K_MataKuliah, K_NamaKelas FROM Kelas WHERE K_ID = ?";
$stmt_header = $conn->prepare($header_sql);
$stmt_header->bind_param('i', $kelasID);
$stmt_header->execute();
$stmt_header->store_result();
$stmt_header->bind_result($mataKuliah, $namaKelas);
$stmt_header->fetch();
$stmt_header->close();

$absen_sql = "SELECT AD_ID, AD_Pertemuan, AD_Deskripsi, AD_TanggalDibuat, AD_Kode FROM Absen_Dosen WHERE Kelas_K_ID = ? AND USER_U_ID = ?";
$stmt_absen = $conn->prepare($absen_sql);
$stmt_absen->bind_param('ii', $kelasID, $userID);
$stmt_absen->execute();
$stmt_absen->store_result();

if ($stmt_absen->num_rows > 0) {
    $stmt_absen->bind_result($absenID,  $pertemuan, $deskripsi, $tanggalDibuat, $kodeAbsen);
} else {
    $absenID = $deskripsi = $pertemuan = $tanggalDibuat = $kodeAbsen = '';
}

function generateKodeAbsen()
{
    $kode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    return $kode;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'tambah') {
        $pertemuan = $_POST['pertemuan'];
        $deskripsi = $_POST['deskripsi'];
        $tanggal = $_POST['tanggal'];

        if (empty($pertemuan) || empty($deskripsi) || empty($tanggal)) {
            $error = 'Semua kolom harus diisi';
        } else {
            $check_sql = "SELECT AD_Pertemuan FROM Absen_Dosen WHERE AD_Pertemuan = ? AND Kelas_K_ID = ?";
            $stmt_check = $conn->prepare($check_sql);
            $stmt_check->bind_param('ii', $pertemuan, $kelasID);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $error = 'Pertemuan sudah ada';
            } else {
                $kodeAbsen = generateKodeAbsen();

                $pertemuan_sql = "INSERT INTO Absen_Dosen (AD_Pertemuan, AD_Deskripsi, AD_TanggalDibuat, AD_Kode, Kelas_K_ID, USER_U_ID) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_pertemuan = $conn->prepare($pertemuan_sql);
                $stmt_pertemuan->bind_param('issiii', $pertemuan, $deskripsi, $tanggal, $kodeAbsen, $kelasID, $userID);
                $stmt_pertemuan->execute();

                header('Location: ./aturPresensi.php?ID=' . $kelasID);
                exit();

                $stmt_pertemuan->close();
            }
            $stmt_check->close();
        }
    } elseif ($_POST['action'] == 'edit') {
        $absenID = $_POST['absenID'];
        $deskripsi = $_POST['deskripsi'];
        $tanggal = $_POST['tanggal'];

        if (empty($deskripsi) || empty($tanggal)) {
            $error = 'Deskripsi dan tanggal tidak boleh kosong';
        } else {
            $update_sql = "UPDATE Absen_Dosen SET AD_Deskripsi = ?, AD_TanggalDibuat = ? WHERE AD_ID = ? AND USER_U_ID = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param('ssii', $deskripsi, $tanggal, $absenID, $userID);
            $stmt_update->execute();
            $stmt_update->close();

            header('Location: ./aturPresensi.php?ID=' . $kelasID);
            exit();
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

            .modal-content {
                width: 90%;
                /* Lebar modal lebih kecil pada mobile */
                max-width: 90%;
                /* Maksimal 90% pada perangkat kecil */
                margin: 10% auto;
                /* Penyesuaian margin untuk lebih terpusat */
            }

            /* Tombol close lebih kecil dan mudah dijangkau */
            .close {
                font-size: 24px;
                /* Ukuran tombol close lebih kecil */
                padding: 8px;
                /* Peningkatan ukuran tombol close */
            }

            .modal-content input {
                font-size: 14px;
                /* Ukuran input lebih kecil */
                padding: 8px;
                /* Penyesuaian padding input agar tidak terlalu besar */
            }
        }

        /* Style untuk modal (overlay) */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }

        /* Modal Content untuk lebih responsif */
        .modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #ddd;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            /* Lebar standar pada layar besar */
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

        /* Tombol simpan pada modal */
        .modal-content button {
            background-color: #0d9488;
            /* warna serupa tabel */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .modal-content button:hover {
            background-color: #0f766e;
        }

        /* Form input dalam modal */
        .modal-content input {
            width: calc(100% - 24px);
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
    <div id="utama" class="w-full md:w-5/6 load p-4 md:p-6">
        <div class="bg-white shadow-md rounded-lg p-4 md:p-6 mb-6 flex flex-col sm:flex-row justify-between">
            <div class="header mb-4 sm:mb-0">
                <h1 class="text-2xl sm:text-3xl font-bold text-dark-teal uppercase mb-2">Presensi <?php echo $namaKelas ?></h1>
                <p class="text-lg sm:text-xl text-teal-600 italic"><?php echo $mataKuliah ?></p>
            </div>
            <button
                class="bg-dark-teal text-white text-lg px-4 py-2 h-fit rounded-xl border hover:bg-white hover:border-light-teal hover:text-light-teal transition duration-300"
                onclick="openModal()">Tambah
                Pertemuan</button>
        </div>
        <div class="bg-white shadow-lg rounded-lg p-4 sm:p-8">
            <div class="overflow-x-auto">
                <table class="w-full mt-6 border-collapse">
                    <thead>
                        <tr class="text-dark-teal">
                            <th class="border-b p-4 text-left font-medium">Pertemuan</th>
                            <th class="border-b p-4 text-left font-medium">Deskripsi</th>
                            <th class="border-b p-4 text-left font-medium">Tanggal</th>
                            <th class="border-b p-4 text-left font-medium">Kode</th>
                            <th class="border-b p-4 text-left font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($stmt_absen->fetch()): ?>
                            <tr class="transition duration-300 hover:bg-teal-50">
                                <td class="p-4"><a href="./detailPresensi.php?IDK=<?php echo $kelasID; ?>&IDA=<?php echo $absenID; ?>"><?php echo $pertemuan ?></a></td>
                                <td class="p-4"><?php echo htmlspecialchars($deskripsi) ?></td>
                                <td class="p-4"><?php echo htmlspecialchars($tanggalDibuat) ?></td>
                                <td class="p-4"><?php echo htmlspecialchars($kodeAbsen) ?></td>
                                <td class="p-4">
                                    <button onclick="openEditModal('<?php echo $absenID; ?>',<?php echo htmlspecialchars($pertemuan) ?>, '<?php echo htmlspecialchars($deskripsi); ?>', '<?php echo htmlspecialchars($tanggalDibuat); ?>')"
                                        class="relative bg-yellow-700 text-white text-lg px-4 py-2 w-fit h-fit rounded-xl border hover:bg-white hover:border-yellow-500 hover:text-yellow-500 cursor-pointer">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div id="myModal" class="modal justify-center">
                <form class="modal-content" action="" method="POST">
                    <!-- <span class="close" onclick="closeModal()"></span> -->
                    <span class="material-symbols-outlined close" onclick="closeModal()">close</span>
                    <div class="mt-5">
                        <form action="POST">
                            <label for="pertemuan" class="block text-sm font-medium text-gray-700">Pertemuan Ke:</label>
                            <input type="number" id="pertemuan" name="pertemuan" min="1"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500" placeholder="Masukkan nomor pertemuan">
                            <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi:</label>
                            <textarea id="deskripsi" name="deskripsi"
                                class="border border-teal-300 rounded-lg w-full p-4 focus:outline-none focus:border-teal-500 transition duration-300"
                                placeholder="Tambahkan Deskripsi Pertemuan" rows="4"></textarea>
                            <label for="date" class="block text-sm font-medium text-gray-700">Tanggal:</label>
                            <input type="date" id="tanggal" name="tanggal"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500">
                            <input type="hidden" name="action" value="tambah">
                            <div class="flex justify-center">
                                <button type="submit"
                                    class="w-1/2 bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Tambah</button>
                            </div>
                        </form>
                    </div>
                </form>
            </div>
            <div id="editModal" class="modal justify-center">
                <form class="modal-content" action="" method="POST">
                    <span class="material-symbols-outlined close" onclick="closeEditModal()">close</span>
                    <div class="mt-5">
                        <p class="block text-sm font-medium text-gray-700">Pertemuan Ke:</p>
                        <p id="editPertemuan" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500"></p>
                        <label for="editDeskripsi" class="block text-sm font-medium text-gray-700">Deskripsi:</label>
                        <textarea id="editDeskripsi" name="deskripsi"
                            class="border border-teal-300 rounded-lg w-full p-4 focus:outline-none focus:border-teal-500 transition duration-300"
                            placeholder="Edit Deskripsi" rows="4"></textarea>
                        <label for="editTanggal" class="block text-sm font-medium text-gray-700">Tanggal:</label>
                        <input type="date" id="editTanggal" name="tanggal"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="editAbsenID" name="absenID">
                        <div class="flex justify-center mt-4">
                            <button type="submit"
                                class="w-1/2 bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
            <?php if (!empty($error)): ?>
                <div id="" class="text-red-500 mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
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
            document.body.style.overflow = "hidden"; // Menonaktifkan scroll saat modal muncul
        }

        // Fungsi untuk menutup modal
        function closeModal() {
            modal.style.display = "none";
            document.body.style.overflow = "auto"; // Mengaktifkan scroll kembali
        }

        // Menutup modal jika klik di luar modal
        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        }

        var editModal = document.getElementById("editModal");

        function openEditModal(absenID, pertemuan, deskripsi, tanggal) {
            editModal.style.display = "block";
            document.body.style.overflow = "auto";

            document.getElementById('editPertemuan').innerHTML = pertemuan;
            document.getElementById('editAbsenID').value = absenID;
            document.getElementById('editDeskripsi').value = deskripsi;
            document.getElementById('editTanggal').value = tanggal;
        }

        function closeEditModal() {
            const modal = document.getElementById("editModal");
            modal.style.display = "none";
            document.body.style.overflow = "auto";
        }

        window.onclick = function(event) {
            const modal = document.getElementById("editModal");
            if (event.target === modal) {
                closeEditModal();
            }
        };

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

<?php
$stmt_absen->close();
?>