<?php
session_start();
include('../../assets/db/config.php');
include('../../auth/aksesMahasiswa.php');

if (!isset($_SESSION['U_ID'])) {
    header('Location: ../home/login.php');
    exit();
}

$userID = $_SESSION['U_ID'];

$sql = "SELECT U_Nama, U_Role, U_Foto, U_Email, U_NoPonsel, U_Alamat, U_TanggalLahir, U_Password FROM User WHERE U_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userID);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($name, $role, $photo, $email, $phone, $address, $birthdate, $password);
    $stmt->fetch();
} else {
    header('Location: ../home/login.php');
    exit();
}

$stmt->close();

$formatted_birthdate = date("d F Y", strtotime($birthdate));

if (isset($_POST['save_name'])) {
    $new_name = $_POST['new_name'];
    $update_sql = "UPDATE User SET U_Nama = ? WHERE U_ID = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('si', $new_name, $userID);
    if ($update_stmt->execute()) {
        $name = $new_name;
    }
    $update_stmt->close();
}

if (isset($_POST['save_birthdate'])) {
    $new_birthdate = $_POST['new_birthdate'];
    $update_sql = "UPDATE User SET U_TanggalLahir = ? WHERE U_ID = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('si', $new_birthdate, $userID);
    $update_stmt->execute();
    $update_stmt->close();
}

if (isset($_POST['save_email'])) {
    $new_email = $_POST['new_email'];
    $update_sql = "UPDATE User SET U_Email = ? WHERE U_ID = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('si', $new_email, $userID);
    $update_stmt->execute();
    $update_stmt->close();
}

if (isset($_POST['save_phone'])) {
    $new_phone = $_POST['new_phone'];
    $update_sql = "UPDATE User SET U_NoPonsel = ? WHERE U_ID = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('si', $new_phone, $userID);
    $update_stmt->execute();
    $update_stmt->close();
}

if (isset($_POST['save_address'])) {
    $new_address = $_POST['new_address'];
    $update_sql = "UPDATE User SET U_Alamat = ? WHERE U_ID = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('si', $new_address, $userID);
    $update_stmt->execute();
    $update_stmt->close();
}

if (isset($_POST['save_password'])) {
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $update_sql = "UPDATE User SET U_Password = ? WHERE U_ID = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('si', $new_password, $userID);
    $update_stmt->execute();
    $update_stmt->close();
}

if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../assets/img/';
    $file_tmp = $_FILES['profile_photo']['tmp_name'];
    $file_name = basename($_FILES['profile_photo']['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($file_ext, $allowed_extensions)) {
        $file_name_safe = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $file_name);
        $file_path = $upload_dir . $file_name_safe;
        
        if (move_uploaded_file($file_tmp, $file_path)) {
            $update_sql = "UPDATE User SET U_Foto = ? WHERE U_ID = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('si', $file_name_safe, $userID);
            
            if ($update_stmt->execute()) {
                header("Location: pengaturan.php?success=1");
                exit();
            } else {
                echo "Failed to update the database with the new image.";
            }
            $update_stmt->close();
        } else {
            echo "Failed to move the uploaded file.";
        }
    } else {
        echo "Invalid file type. Please upload an image file (jpg, jpeg, png, gif).";
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
        <div class="p-6 rounded-lg flex flex-col">
            <div class="header mb-4">
                <h1 class="px-4 text-3xl font-bold text-dark-teal uppercase mb-2">Pengaturan Akun</h1>
            </div>
            <form id="image-upload-form" action="pengaturan.php" method="POST" enctype="multipart/form-data">
                <div class="flex flex-col items-center md:flex-row">
                    <div class="relative group">
                        <img id="profile-img" src="../../assets/img/<?= htmlspecialchars($photo) ?>" alt="profil" class="px-4 w-64 mt-2 rounded-lg transition-all duration-300 group-hover:blur-sm" style="border-radius: 15%">
                        
                        <button id="edit-btn" type="button" class="absolute top-0 right-0 transform translate-x-1 -translate-y-1 w-12 h-12 bg-black rounded-lg text-white opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity duration-300">
                            <span class="material-symbols-outlined">edit</span>
                        </button>

                        <!-- Use the same input name as in PHP -->
                        <input type="file" id="file-input" name="profile_photo" class="absolute inset-0 opacity-0 cursor-pointer" onchange="submitImageForm(event)">
                    </div>
                    <div class="flex flex-col ml-4 justify-center">
                        <span class="font-bold text-xl text-black md:text-4xl"><?= htmlspecialchars($name) ?></span>
                        <span class="text-gray-600 text-md md:text-xl"><?= htmlspecialchars($role) ?></span>
                    </div>
                </div>
                <!-- Hidden submit button if needed -->
                <button type="submit" id="upload-btn" style="display: none;">Upload</button>
            </form>


            <div class="mt-8 w-full pl-4">
                <div class="p-4 bg-gray-100 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold text-dark-teal">Informasi Pribadi</h2>
                    <p class="text-gray-600 text-lg mt-1">Data Pribadi yang ada di KelasKu</p>
                    <div class="mt-4 space-y-4">
                        <form method="POST" action="">
                            <div class="p-4 bg-white rounded-lg shadow-sm hover:bg-gray-200 transition duration-300 flex justify-between items-center overflow-x-auto">
                                <div class="flex items-center">
                                    <span class="font-bold text-lg text-gray-800">Nama:</span>
                                    <span class="text-gray-600 text-lg ml-2"><?= $name ?></span>
                                </div>
                                <button type="submit" name="edit_name" class="text-gray-600">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                            </div>
                            <?php if (isset($_POST['edit_name'])): ?>
                                <input type="text" name="new_name" class="mt-2 p-2 border rounded" value="<?= $name ?>" required>
                                <button type="submit" name="save_name" class="bg-green-500 text-white rounded px-4 py-2 mt-2">Simpan</button>
                            <?php endif; ?>
                        </form>

                        <form method="POST" action="">
                            <div class="p-4 bg-white rounded-lg shadow-sm hover:bg-gray-200 transition duration-300 flex justify-between items-center overflow-x-auto">
                                <div class="flex items-center">
                                    <span class="font-bold text-lg text-gray-800">Tanggal Lahir:</span>
                                    <span class="text-gray-600 text-lg ml-2"><?= $formatted_birthdate ?></span>
                                </div>
                                <button type="submit" name="edit_birthdate" class="text-gray-600">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                            </div>
                            <?php if (isset($_POST['edit_birthdate'])): ?>
                                <input type="date" name="new_birthdate" class="mt-2 p-2 border rounded" value="<?= $birthdate ?>" required>
                                <button type="submit" name="save_birthdate" class="bg-green-500 text-white rounded px-4 py-2 mt-2">Simpan</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="mt-8 w-full pl-4">
                <div class="p-4 bg-gray-100 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold text-dark-teal">Kontak</h2>
                    <p class="text-gray-600 text-lg mt-1">Email dan No Ponsel yang dapat dihubungi kampus</p>
                    <div class="mt-4 space-y-4">
                        <form method="POST" action="">
                            <div class="p-4 bg-white rounded-lg shadow-sm hover:bg-gray-200 transition duration-300 flex justify-between items-center overflow-x-auto">
                                <div class="flex items-center">
                                    <span class="font-bold text-lg text-gray-800">Email:</span>
                                    <span class="text-gray-600 text-lg ml-2"><?= $email ?></span>
                                </div>
                                <button type="submit" name="edit_email" class="text-gray-600">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                            </div>
                            <?php if (isset($_POST['edit_email'])): ?>
                                <input type="email" name="new_email" class="mt-2 p-2 border rounded" value="<?= $email ?>" required>
                                <button type="submit" name="save_email" class="bg-green-500 text-white rounded px-4 py-2 mt-2">Simpan</button>
                            <?php endif; ?>
                        </form>

                        <form method="POST" action="">
                            <div class="p-4 bg-white rounded-lg shadow-sm hover:bg-gray-200 transition duration-300 flex justify-between items-center overflow-x-auto">
                                <div class="flex items-center">
                                    <span class="font-bold text-lg text-gray-800">No. Ponsel:</span>
                                    <span class="text-gray-600 text-lg ml-2"><?= $phone ?></span>
                                </div>
                                <button type="submit" name="edit_phone" class="text-gray-600">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                            </div>
                            <?php if (isset($_POST['edit_phone'])): ?>
                                <input type="text" name="new_phone" class="mt-2 p-2 border rounded" value="<?= $phone ?>" required>
                                <button type="submit" name="save_phone" class="bg-green-500 text-white rounded px-4 py-2 mt-2">Simpan</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="mt-8 w-full pl-4">
                <div class="p-4 bg-gray-100 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold text-dark-teal">Lainnya</h2>
                    <p class="text-gray-600 text-lg mt-1">Password dan Alamat Rumah</p>
                    <div class="mt-4 space-y-4">
                        <form method="POST" action="">
                            <div class="p-4 bg-white rounded-lg shadow-sm hover:bg-gray-200 transition duration-300 flex justify-between items-center overflow-x-auto">
                                <div class="flex items-center">
                                    <span class="font-bold text-lg text-gray-800">Password:</span>
                                    <input type="password" class="bg-transparent text-gray-600 text-lg ml-2 w-full sm:w-2/3 border-none outline-none" value="**********" disabled>
                                </div>
                                <button type="submit" name="edit_password" class="text-gray-600">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                            </div>
                            <?php if (isset($_POST['edit_password'])): ?>
                                <input type="password" name="new_password" class="mt-2 p-2 border rounded" value="" required>
                                <button type="submit" name="save_password" class="bg-green-500 text-white rounded px-4 py-2 mt-2">Simpan</button>
                            <?php endif; ?>
                        </form>
                        <form method="POST" action="">
                            <div class="p-4 bg-white rounded-lg shadow-sm hover:bg-gray-200 transition duration-300 flex justify-between items-center overflow-x-auto">
                                <div class="flex items-center">
                                    <span class="font-bold text-lg text-gray-800">Alamat:</span>
                                    <span class="text-gray-600 text-lg ml-2"><?= $address ?></span>
                                </div>
                                <button type="submit" name="edit_address" class="text-gray-600">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                            </div>
                            <?php if (isset($_POST['edit_address'])): ?>
                                <input type="text" name="new_address" class="mt-2 p-2 border rounded" value="<?= $address ?>" required>
                                <button type="submit" name="save_address" class="bg-green-500 text-white rounded px-4 py-2 mt-2">Simpan</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function submitImageForm(event) {
            const form = document.getElementById('image-upload-form');
            const fileInput = document.getElementById('file-input');
            
            if (fileInput.files && fileInput.files[0]) {
                // Optionally, you can preview the image before submitting
                previewImage(event);
                form.submit();
            }
        }

        function previewImage(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const imgElement = document.getElementById('profile-img');
                imgElement.src = e.target.result;
            };
            
            if (file) {
                reader.readAsDataURL(file);
            }
        }

        document.getElementById('edit-btn').addEventListener('click', function() {
            document.getElementById('file-input').click();
        });

        document.getElementById('edit-btn').addEventListener('click', function() {
            document.getElementById('file-input').click();
        });

        function confirmLogout(event) {
            event.preventDefault(); // Mencegah link untuk navigasi
            const confirmation = confirm("Apakah Anda ingin keluar?");

            if (confirmation) {
                window.location.href = '../home/login.php';
            } else {

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