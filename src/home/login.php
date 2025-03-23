<?php
session_start();
include('/xampp/htdocs/FP/assets/db/config.php');
include('/xampp/htdocs/FP/auth/auth_redirect.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Masukkan Email dan Password!';
    } else {
        $sql = "SELECT U_ID, U_Password, U_Role FROM User WHERE U_Email = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($userID, $hashedPassword, $role);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                $_SESSION['U_ID'] = $userID;
                $_SESSION['U_Email'] = $email;
                $_SESSION['U_Role'] = $role;

                if ($role == 'dosen') {
                    header('Location: ../dosen/index.php');
                } else {
                    header('Location: ../mahasiswa/index.php');
                }
                exit();
            } else {
                $error = 'Email atau Password Salah!';
            }
        } else {
            $error = 'Email atau Password Salah!';
        }
        $stmt->close();
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
    <section id="login" class="flex flex-col md:flex-row h-screen">
        <!-- NAV -->
        <nav class="flex items-center justify-between p-10 text-light-teal w-full fixed top-0">
            <a href="login.php" class="font-modak text-3xl text-dark-teal">KelasKu</a>
            <div class="hidden md:block">
                <ul class="flex space-x-6">
                    <li><a href="#" class=" hover:-translate-y-1 transition text-lg px-3 py-2">Docs</a>
                    </li>
                    <li><a href="about.php" class=" hover:-translate-y-1 transition text-lg px-3 py-2">Tentang</a></li>
                    <li><a href="#" class=" hover:-translate-y-1 transition text-lg px-3 py-2">Bantuan</a></li>
                    <li><a href="register.php"
                            class="bg-light-teal text-white text-lg px-4 py-2 rounded border hover:bg-white hover:border-light-teal hover:text-light-teal">Buat
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
                <li><a href="about.php" class="hover:-translate-y-1 transition text-lg">Tentang</a></li>
                <li><a href="#" class="hover:-translate-y-1 transition text-lg">Docs</a></li>
                <li><a href="#" class="hover:-translate-y-1 transition text-lg">Tentang</a></li>
                <li><a href="register.php"
                        class="text-lg bg-white text-light-teal px-4 py-2 rounded border hover:bg-light-teal hover:border-white hover:text-white">Buat
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
                <h1 class="text-2xl mb-4 text-light-teal font-extrabold md:text-4xl md:mb-6">Siap belajar? Masuk
                    sekarang!</h1>
                <form method="POST" action="login.php" id="loginForm">
                    <div class="mb-2 w-full md:mb-4">
                        <h2 class="text-lg mb-1 md:text-xl md:mb-2">Email</h2>
                        <div class="flex items-center border p-2 w-full rounded md:p-4">
                            <span class="material-symbols-outlined mr-2 text-light-teal">
                                mail
                            </span>
                            <input type="email" name="email" id="" placeholder="Masukkan Email Anda" class="flex-1 outline-none">
                        </div>
                    </div>
                    <div class="mb-2 w-full md:mb-4">
                        <h2 class="text-lg mb-1 md:text-xl md:mb-2">Password</h2>
                        <div class="flex items-center border p-2 w-full rounded md:p-4">
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
                    <?php if (!empty($error)): ?>
                        <div id="" class="text-red-500 mb-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    <button
                        class="bg-light-teal text-white text-lg px-4 py-2 rounded border border-transparent hover:bg-white hover:border-light-teal hover:text-light-teal w-full">
                        Masuk</button>
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

        togglePassword.addEventListener('click', function(e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.textContent = type === 'password' ? 'visibility' : 'visibility_off';
        });
    </script>
</body>

</html>