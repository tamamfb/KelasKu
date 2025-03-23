<?php
session_start();
include('/xampp/htdocs/FP/assets/db/config.php');
include('/xampp/htdocs/FP/auth/auth_redirect.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    if (empty($name) || empty($dob) || empty($role) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Semua kolom harus diisi!';
    } else {
        if ($password != $confirmPassword) {
            $error = 'Password tidak sama!';
        } else {
            $check_sql = "SELECT U_ID FROM User WHERE U_Email = ?";
            $stmt_check = $conn->prepare($check_sql);
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $error = 'Email sudah terdaftar!';
                $stmt_check->close();
            } else {
                $stmt_check->close();

                $prefixID = $role == 'dosen' ? '10' : '50';

                $sql = "SELECT MAX(U_ID) AS max_id FROM User WHERE U_Role = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $role);
                $stmt->execute();
                $stmt->bind_result($max_id);
                $stmt->fetch();
                $stmt->close();

                if ($max_id) {
                    $new_id = $prefixID . str_pad((intval(substr($max_id, 2)) + 1), 7, '0', STR_PAD_LEFT);
                } else {
                    $new_id = $prefixID . '0000001';
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO User (U_ID, U_Nama, U_TanggalLahir, U_Role, U_Email, U_Password, U_Foto) 
                    VALUES (?, ?, ?, ?, ?, ?, 'default.jpg')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $new_id, $name, $dob, $role, $email, $hashedPassword);

                if ($stmt->execute()) {
                    header('Location: login.php');
                    exit();
                } else {
                    $error = 'Gagal mendaftar. Coba lagi nanti.';
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KelasKu</title>
    <link rel="stylesheet" href="../../assets/output.css">
</head>

<body class="font-poppins bg-white">
    <section id="register" class="flex flex-col md:flex-row h-screen">
        <!-- NAV -->
        <nav class="flex items-center justify-between p-10 text-light-teal w-full fixed top-0">
            <a href="login.php" class="font-modak text-3xl text-dark-teal">KelasKu</a>
            <div class="hidden md:block">
                <ul class="flex space-x-6">
                    <li><a href="about.php" class="hover:-translate-y-1 transition text-lg">Tentang</a></li>
                    <li><a href="#" class="hover:-translate-y-1 transition text-lg">Docs</a></li>
                    <li><a href="#" class="hover:-translate-y-1 transition text-lg">Tentang</a></li>
                    <li><a href="login.php"
                            class="bg-light-teal text-white text-lg px-4 py-2 rounded border hover:bg-white hover:border-light-teal hover:text-light-teal">Masuk
                            Akun</a></li>
                </ul>
            </div>
            <div class="block md:hidden text-white">
                <span id="hamburger" class="material-symbols-outlined">
                    menu
                </span>
            </div>
        </nav>
        <!-- SIDEBAR -->
        <div id="sidebar"
            class="fixed top-0 right-0 h-full w-1/2 bg-light-teal transform translate-x-full transition-transform duration-300 z-50 py-10 bg-opacity-75">
            <div class="absolute top-10 right-10 text-white">
                <span class="material-symbols-outlined" id="closeSidebar">
                    close
                </span>
            </div>
            <ul class="flex flex-col space-y-6 p-10 text-white">
                <li><a href="#" class="text-lg">Docs</a></li>
                <li><a href="#" class="text-lg">Tentang</a></li>
                <li><a href="#" class="text-lg">Bantuan</a></li>
                <li><a href="login.php"
                        class="text-lg bg-white text-light-teal px-4 py-2 rounded border hover:bg-light-teal hover:border-white hover:text-white">Masuk
                        Akun</a>
                </li>
            </ul>
        </div>
        <!-- SISI KIRI -->
        <div class="w-full md:w-1/3 bg-light-teal flex justify-center items-center p-4 md:p-0">
            <img src="../../assets/img/home.png" alt="">
        </div>
        <!-- SISI KANAN -->
        <div class="w-full md:w-2/3 bg-white flex flex-col justify-center items-center p-8">
            <div
                class="border border-light-teal py-8 px-6 space-y-4 rounded-3xl shadow-lg md:py-16 md:px-12 md:space-y-8">
                <h1 class="text-2xl mb-4 text-light-teal font-extrabold md:text-4xl md:mb-6">Siap belajar? Daftar
                    sekarang!</h1>
                <form method="POST" action="register.php" id="registerForm">
                    <div class="mb-4 w-full md:mb-4">
                        <h2 class="text-lg mb-1 md:text-xl md:mb-2">Nama</h2>
                        <div class="flex items-center border md:p-4 p-2 w-full rounded">
                            <span class="material-symbols-outlined mr-2 text-light-teal">
                                id_card
                            </span>
                            <input type="text" name="name" id="name" placeholder="Masukkan Nama Anda"
                                class="flex-1 outline-none">
                        </div>
                    </div>
                    <div class="mb-4 w-full md:mb-4">
                        <h2 class="text-lg mb-1 md:text-xl md:mb-2">Tanggal Lahir</h2>
                        <div class="flex items-center border md:p-4 p-2 w-full rounded">
                            <span class="material-symbols-outlined mr-2 text-light-teal">
                                calendar_today
                            </span>
                            <input type="date" id="dob" name="dob" class="flex-1 outline-none">
                        </div>
                    </div>
                    <div class="mb-4 w-full md:mb-4">
                        <h2 class="text-lg mb-1 md:text-xl md:mb-2">Peran</h2>
                        <div class="flex items-center space-x-4">
                            <input type="radio" id="dosen" name="role" value="dosen"
                                class="h-5 w-5 text-light-teal border-light-teal rounded-md focus:ring-2 focus:ring-light-teal">
                            <label for="Dosen" class="text-lg">Dosen</label>
                            <input type="radio" id="mahasiswa" name="role" value="mahasiswa"
                                class="h-5 w-5 text-light-teal border-light-teal rounded-md focus:ring-2 focus:ring-light-teal">
                            <label for="mahasiswa" class="text-lg">Mahasiswa</label>
                        </div>
                    </div>
                    <div class="mb-4 w-full md:mb-4">
                        <h2 class="text-lg mb-1 md:text-xl md:mb-2">Email</h2>
                        <div class="flex items-center border md:p-4 p-2 w-full rounded">
                            <span class="material-symbols-outlined mr-2 text-light-teal">
                                mail
                            </span>
                            <input type="email" name="email" id="email" placeholder="Masukkan Email Anda"
                                class="flex-1 outline-none">
                        </div>
                    </div>
                    <div class="mb-4 w-full md:mb-4">
                        <h2 class="text-lg mb-1 md:text-xl md:mb-2">Password</h2>
                        <div class="flex items-center border md:p-4 p-2 w-full rounded">
                            <span class="material-symbols-outlined mr-2 text-light-teal">
                                lock
                            </span>
                            <input type="password" name="password" id="password" placeholder="Masukkan Password Anda"
                                class="flex-1 outline-none">
                            <span class="material-symbols-outlined mr-2 text-light-teal" id="togglePassword">
                                visibility
                            </span>
                        </div>
                    </div>
                    <div class="mb-4 w-full md:mb-4">
                        <h2 class="text-lg mb-1 md:text-xl md:mb-2">Konfirmasi Password</h2>
                        <div class="flex items-center border md:p-4 p-2 w-full rounded">
                            <span class="material-symbols-outlined mr-2 text-light-teal">
                                lock
                            </span>
                            <input type="password" name="confirmPassword" id="confirmPassword" placeholder="Konfirmasi Password Anda"
                                class="flex-1 outline-none">
                            <span class="material-symbols-outlined mr-2 text-light-teal" id="toggleConfirmPassword">
                                visibility
                            </span>
                        </div>
                    </div>
                    <?php if (!empty($error)): ?>
                        <div id="" class="text-red-500 mb-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    <button
                        class="bg-light-teal text-white text-lg px-4 py-2 rounded border border-transparent hover:bg-white hover:border-light-teal hover:text-light-teal w-full">
                        Daftar</button>
                </form>
            </div>
        </div>
    </section>

    <script>
        const hamburger = document.querySelector('#hamburger');
        const sidebar = document.querySelector('#sidebar');
        const closeSidebar = document.querySelector('#closeSidebar');

        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('translate-x-full');
            hamburger.classList.toggle('hidden');
        });

        closeSidebar.addEventListener('click', () => {
            sidebar.classList.add('translate-x-full');
            hamburger.classList.remove('hidden');
        });

        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
        const confirmPassword = document.querySelector('#confirmPassword');
        const passwordError = document.querySelector('#passwordError');

        togglePassword.addEventListener('click', function(e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.textContent = type === 'password' ? 'visibility' : 'visibility_off';
        });

        toggleConfirmPassword.addEventListener('click', function(e) {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            this.textContent = type === 'password' ? 'visibility' : 'visibility_off';
        });
    </script>
</body>

</html>