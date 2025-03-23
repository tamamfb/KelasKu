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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $namaTugas = $_POST['newTask'];
    $deskripsi = $_POST['deskripsi'];
    $deadline = $_POST['deadline'];

    $targetDir = "/xampp/htdocs/FP/storage/task/";
    $status = 1;
    $fileName = '';
    $uploadOK = 1;

    if (isset($_FILES["fileUpload"]) && $_FILES["fileUpload"]["error"] == 0) {
        $fileName = basename($_FILES["fileUpload"]["name"]);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (file_exists($targetDir . $fileName)) {
            $error = "File sudah ada";
            $uploadOK = 0;
        }

        if ($_FILES["fileUpload"]["size"] > 5000000) {
            $error = "Ukuran file terlalu besar";
            $uploadOK = 0;
        }

        if ($fileType != "pdf" && $fileType != "doc" && $fileType != "docx") {
            $error = "Hanya file PDF, DOC, dan DOCX yang diperbolehkan";
            $uploadOK = 0;
        }

        if ($uploadOK == 1) {
            if (!move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $targetDir . $fileName)) {
                $error = "Terjadi kesalahan saat mengupload file";
                $uploadOK = 0;
            }
        }
    }

    if ($uploadOK == 1 || $fileName == '') {
        $task_sql = "INSERT INTO TUGAS_DOSEN(TD_Judul, TD_Deskripsi, TD_Deadline, TD_Status, TD_FileSoal, Kelas_K_ID, User_U_ID, TD_TanggalDibuat) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt_task = $conn->prepare($task_sql);
        $stmt_task->bind_param('sssisii', $namaTugas, $deskripsi, $deadline, $status, $fileName, $kelasID, $userID);

        if ($stmt_task->execute()) {
            header('Location: tugas.php?ID=' . $kelasID);
        } else {
            $error = "Terjadi kesalahan saat menyimpan data tugas";
        }

        $stmt_task->close();
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
    <div id="utama" class="w-full md:w-5/6 load p-6 rounded-lg">
        <div class="bg-white shadow-md rounded-lg p-6 mb-6 flex flex-row justify-between">
            <div class="header mb-4">
                <h1 class="text-3xl font-bold text-dark-teal uppercase mb-2">Tugas <?php echo $namaKelas ?></h1>
                <p class="text-xl text-teal-600 italic"><?php echo $mataKuliah ?></p>
            </div>
        </div>
        <div class="bg-white shadow-lg rounded-lg p-8">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-6">
                    <label for="newTask" class="block text-dark-teal font-semibold mb-2 text-lg">Nama Tugas:</label>
                    <input type="text" id="newTask" name="newTask"
                        class="border border-teal-300 rounded-lg w-full p-4 focus:outline-none focus:border-teal-500 transition duration-300"
                        placeholder="Tambahkan Tugas Baru">
                </div>
                <div class="mb-6">
                    <label for="newTask" class="block text-dark-teal font-semibold mb-2 text-lg">Deskripsi:</label>
                    <textarea id="deskripsi" name="deskripsi"
                        class="border border-teal-300 rounded-lg w-full p-4 focus:outline-none focus:border-teal-500 transition duration-300"
                        placeholder="Tambahkan Deskripsi Tugas Baru" rows="4"></textarea>
                </div>
                <div class="mb-6">
                    <label for="deadline" class="block text-dark-teal font-semibold mb-2 text-lg">Deadline:</label>
                    <input type="date" id="deadline" name="deadline"
                        class="border border-teal-300 rounded-lg w-full p-4 focus:outline-none focus:border-teal-500 transition duration-300">
                </div>
                <div class="mb-6">
                    <label for="fileUpload" class="block text-dark-teal font-semibold mb-2 text-lg">Upload File:</label>
                    <div id="drop-area"
                        class="border-dashed border-2 border-teal-400 rounded-lg p-6 text-center w-full flex flex-col items-center justify-center transition duration-300 hover:border-teal-600 cursor-pointer">
                        <span id="uploadIcon" class="material-symbols-outlined text-teal-500 mb-2">
                            file_upload
                        </span>
                        <p id="uploadText" class="text-teal-600 mb-4">Drag & Drop your files here or click to upload</p>
                        <input type="file" id="fileElem" name="fileUpload" multiple accept="*/*" class="hidden" onchange="handleFiles(this.files)">
                        <div id="fileName" class="flex items-center mt-4 text-teal-600">
                            <span id="fileIcon" class="material-symbols-outlined text-teal-500 mr-2" style="display:none;">insert_drive_file</span>
                            <span id="fileText" style="display:none;">No file chosen</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit"
                        class="bg-dark-teal text-white text-lg px-4 py-2 h-fit rounded-xl border hover:bg-white hover:border-light-teal hover:text-light-teal transition duration-300">Tambah
                        Tugas</button>
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

        let dropArea = document.getElementById('drop-area');
        let fileInput = document.getElementById('fileElem');

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
        });

        dropArea.addEventListener('click', () => {
            fileInput.click();
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

        function handleFiles(files) {
            const fileNameDisplay = document.getElementById('fileName');
            const fileIcon = document.getElementById('fileIcon');
            const fileText = document.getElementById('fileText');
            const uploadIcon = document.getElementById('uploadIcon');
            const uploadText = document.getElementById('uploadText');
            const file = files[0];

            if (file) {
                uploadIcon.style.display = 'none';
                uploadText.style.display = 'none';

                fileIcon.style.display = 'inline';
                fileText.style.display = 'inline';
                fileText.textContent = file.name;
            } else {
                uploadIcon.style.display = 'inline';
                uploadText.style.display = 'inline';

                fileIcon.style.display = 'none';
                fileText.style.display = 'none';
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