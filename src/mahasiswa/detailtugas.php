<?php
session_start();
include('../../assets/db/config.php');
include('../../auth/aksesMahasiswa.php');

$userID = $_SESSION['U_ID'];

$sql_user = "SELECT U_Nama, U_Role, U_Foto FROM User WHERE U_ID = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param('i', $userID);
$stmt_user->execute();
$stmt_user->store_result();

if ($stmt_user->num_rows > 0) {
    $stmt_user->bind_result($name, $role, $photo);
    $stmt_user->fetch();
} else {
    header('Location: ../home/login.php');
    exit();
}

$stmt_user->close();

if (isset($_GET['tugas_id'])) {
    $tugas_id = $_GET['tugas_id'];

    $sql_tugas = "SELECT td.TD_Judul, td.TD_Deskripsi, td.TD_Deadline, td.TD_Status, td.TD_FileSoal, td.Kelas_K_ID
                  FROM Tugas_Dosen td
                  WHERE td.TD_ID = ?";
    $stmt_tugas = $conn->prepare($sql_tugas);
    $stmt_tugas->bind_param('i', $tugas_id);
    $stmt_tugas->execute();
    $result_tugas = $stmt_tugas->get_result();

    if ($result_tugas->num_rows > 0) {
        $tugas = $result_tugas->fetch_assoc();
        $kelas_id = $tugas['Kelas_K_ID'];
    } else {
        echo "Tugas tidak ditemukan.";
        exit;
    }

    $sql_tugas_mahasiswa = "SELECT TM_Status, TM_NilaiTugas, TM_WaktuPengumpulan FROM Tugas_Mahasiswa 
                            WHERE Tugas_Dosen_TD_ID = ? AND User_U_ID = ?";
    $stmt_tugas_mahasiswa = $conn->prepare($sql_tugas_mahasiswa);
    $stmt_tugas_mahasiswa->bind_param('ii', $tugas_id, $userID);
    $stmt_tugas_mahasiswa->execute();
    $result_tugas_mahasiswa = $stmt_tugas_mahasiswa->get_result();

    if ($result_tugas_mahasiswa->num_rows > 0) {
        $tugas_mahasiswa = $result_tugas_mahasiswa->fetch_assoc();
    } else {
        $tugas_mahasiswa = null;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['fileUpload'])) {
        $file = $_FILES['fileUpload'];

        $fileName = basename($file['name']);
        $targetDir = "../../storage/submission/";
        $targetFile = $targetDir . $fileName;

        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($file['type'], $allowedTypes)) {
            echo "File type not allowed.";
            exit();
        }

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $waktuPengumpulan = date('Y-m-d H:i:s');
            $status = 1;

            // Jika sudah ada data sebelumnya, lakukan UPDATE, jika tidak insert baru
            if ($tugas_mahasiswa) {
                // Update data jika sudah ada
                $sql_update = "UPDATE Tugas_Mahasiswa 
                               SET TM_WaktuPengumpulan = ?, TM_Status = ?, TM_FileTugas = ? 
                               WHERE Tugas_Dosen_TD_ID = ? AND User_U_ID = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ssi", $waktuPengumpulan, $status, $fileName, $tugas_id, $userID);
                if ($stmt_update->execute()) {
                    header("Location: detailtugas.php?tugas_id=$tugas_id");
                    exit();
                } else {
                    echo "Failed to update data.";
                    exit();
                }
            } else {
                // Insert data baru
                $sql_insert = "INSERT INTO Tugas_Mahasiswa (TM_WaktuPengumpulan, TM_Status, TM_FileTugas, Tugas_Dosen_TD_ID, Kelas_K_ID, User_U_ID) 
                               VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("ssssii", $waktuPengumpulan, $status, $fileName, $tugas_id, $kelas_id, $userID);
                if ($stmt_insert->execute()) {
                    header("Location: detailtugas.php?tugas_id=$tugas_id");
                    exit();
                } else {
                    echo "Failed to insert data into database.";
                }
            }
        } else {
            echo "File upload failed.";
        }
    }
} else {
    echo "Tugas ID tidak ditemukan.";
    exit;
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
        <div class="p-6 rounded-lg flex flex-col justify-between">
            <div class="header mb-4">
                <h1 class="px-4 text-3xl font-bold text-dark-teal uppercase mb-1">Pemrograman Web</h1>
            </div>
            <div class="mt-8 w-full pl-4">
                <div class="p-4 bg-gray-100 rounded-lg">
                    <h2 class="text-4xl text-dark-teal"><?= strtoupper(htmlspecialchars($tugas['TD_Judul'])); ?></h2>
                    <p class="text-gray-600 text-lg mt-2 mb-2"><?= htmlspecialchars($tugas['TD_Deskripsi']); ?></p>
                    <div class="mt-8 mb-8">
                        <a href="/FP/storage/task/<?= htmlspecialchars($tugas['TD_FileSoal']); ?>" target="_blank"
                            class="relative bg-light-teal text-white text-xl px-4 py-2 w-fit h-fit rounded-xl border hover:bg-white hover:border-light-teal hover:text-light-teal">File Tugas
                        </a>
                    </div>
                    <div class="mb-6" style="margin-top: 100px;">
                        <label for="status" class="block text-dark-teal font-semibold mb-6 text-2xl">Status:
                            <?php if ($tugas_mahasiswa) { ?>
                                <span class="text-green-600 font-bold ml-2">SUDAH MENGUMPULKAN</span>
                            <?php } else { ?>
                                <span class="text-red-600 font-bold ml-2">BELUM MENGUMPULKAN</span>
                            <?php } ?>
                        </label>
                        <?php if ($tugas_mahasiswa): ?>
                            <label for="deadline" class="block text-dark-teal font-semibold mb-6 text-2xl">Dikumpulkan pada:
                                <span class="text-gray-600 ml-2"><?= date('d F Y, H:i', strtotime($tugas_mahasiswa['TM_WaktuPengumpulan'])); ?></span>
                            </label>
                        <?php endif; ?>

                        <label for="deadline" class="block text-dark-teal font-semibold mb-6 text-2xl">Deadline:
                            <span class="text-gray-600 ml-2"><?= date('d F Y, H:i', strtotime($tugas['TD_Deadline'])); ?></span>
                        </label>

                        <label for="nilai" class="block text-dark-teal font-semibold mb-2 text-2xl">Nilai:
                            <span class="text-blue-600 ml-2">
                                <?= ($tugas_mahasiswa && isset($tugas_mahasiswa['TM_NilaiTugas']) && $tugas_mahasiswa['TM_NilaiTugas'] !== 0) ? htmlspecialchars($tugas_mahasiswa['TM_NilaiTugas']) : 'Belum Dinilai'; ?>
                            </span>
                        </label>
                    </div>
                    <form action="detailtugas.php?tugas_id=<?= $tugas_id ?>" method="POST" enctype="multipart/form-data">
                        <div class="mb-6">
                            <label for="fileUpload" class="block text-dark-teal font-semibold mb-2 text-2xl">Upload File:</label>
                            <div id="drop-area" class="bg-white border-dashed border-2 border-teal-400 rounded-lg p-6 text-center w-full flex flex-col items-center justify-center transition duration-300 hover:border-teal-600">
                                <span id="uploadIcon" class="material-symbols-outlined text-teal-500 mb-2">file_upload</span>
                                <p id="uploadText" class="text-teal-600 mb-4">Drag & Drop your files here or click to upload</p>
                                <input type="file" id="fileElem" name="fileUpload" multiple accept="*/*" class="hidden" onchange="handleFiles(this.files)">
                                <div id="fileName" class="flex items-center mt-4 text-teal-600" style="display: none;">
                                    <!-- Hanya menampilkan nama file -->
                                    <span id="fileText" style="display:none;">No file chosen</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 mb-8">
                            <button type="submit" id="submitButton" class="relative bg-dark-teal text-white text-lg px-4 py-2 w-fit h-fit rounded-xl border hover:bg-white hover:border-light-teal hover:text-light-teal" style="display:none;">
                                Kumpulkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        function confirmLogout(event) {
            event.preventDefault(); // Mencegah link untuk navigasi
            const confirmation = confirm("Apakah Anda ingin keluar?");

            if (confirmation) {
                window.location.href = '../../auth/logout.php';
            } else {
                return;
            }
        }
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

        let dropArea = document.getElementById('drop-area');
        let fileInput = document.getElementById('fileElem');
        let submitButton = document.getElementById('submitButton');
        let uploadIcon = document.getElementById('uploadIcon');
        let uploadText = document.getElementById('uploadText');
        let fileNameDisplay = document.getElementById('fileName');
        let fileText = document.getElementById('fileText');

        dropArea.addEventListener('dragover', (event) => {
            event.preventDefault();
            dropArea.classList.add('border-teal-600');
        });

        dropArea.addEventListener('dragleave', () => {
            dropArea.classList.remove('border-teal-600');
        });

        dropArea.addEventListener('drop', (event) => {
            event.preventDefault();
            dropArea.classList.remove('border-teal-600');
            let files = event.dataTransfer.files;
            handleFiles(files);
        });

        dropArea.addEventListener('click', () => {
            fileInput.click();
        });

        function handleFiles(files) {
            const file = files[0];

            if (file) {
                // Menyembunyikan ikon upload dan teks default
                uploadIcon.style.display = 'none';
                uploadText.style.display = 'none';

                // Menampilkan nama file yang dipilih
                fileNameDisplay.style.display = 'flex';
                fileText.style.display = 'inline';
                fileText.textContent = file.name;

                // Menampilkan tombol submit setelah file dipilih
                submitButton.style.display = 'inline-block';
            } else {
                // Jika tidak ada file yang dipilih
                fileNameDisplay.style.display = 'none';
                submitButton.style.display = 'none';

                // Menampilkan kembali ikon upload dan teks default
                uploadIcon.style.display = 'inline';
                uploadText.style.display = 'inline';
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