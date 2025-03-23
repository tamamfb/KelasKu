<?php
session_start();
include('/xampp/htdocs/FP/assets/db/config.php');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - KelasKu</title>
    <link rel="stylesheet" href="../../assets/output.css">
</head>

<body class="font-poppins bg-white">
    <section id="about" class="flex flex-col md:flex-row h-screen">
        <!-- NAV -->
        <nav class="flex items-center justify-between p-10 text-light-teal w-full fixed top-0">
            <a href="login.php" class="font-modak text-3xl text-dark-teal">KelasKu</a>
            <div class="hidden md:block">
                <ul class="flex space-x-6">
                    <li><a href="about.php" class="hover:-translate-y-1 transition text-lg px-3 py-2 font-semibold">About</a></li>
                    <li><a href="#" class="hover:-translate-y-1 transition text-lg px-3 py-2">Docs</a></li>
                    <li><a href="#" class="hover:-translate-y-1 transition text-lg px-3 py-2">Tentang</a></li>
                    <li><a href="login.php"
                            class="bg-light-teal text-white text-lg px-4 py-2 rounded border hover:bg-white hover:border-light-teal hover:text-light-teal">Masuk
                            Akun</a></li>
                </ul>
            </div>
            <div class="block md:hidden text-white">
                <span id="hamburger" class="material-symbols-outlined cursor-pointer">
                    menu
                </span>
            </div>
        </nav>
        <!-- SIDEBAR -->
        <div id="sidebar"
            class="fixed top-0 right-0 h-full w-1/2 bg-light-teal transform translate-x-full transition-transform duration-300 z-50 py-10 bg-opacity-95">
            <div class="absolute top-10 right-10 text-white">
                <span class="material-symbols-outlined cursor-pointer" id="closeSidebar">
                    close
                </span>
            </div>
            <ul class="flex flex-col space-y-6 p-10 text-white">
                <li><a href="about.php" class="hover:-translate-y-1 transition text-lg font-semibold">Tentang</a></li>
                <li><a href="#" class="hover:-translate-y-1 transition text-lg">Docs</a></li>
                <li><a href="#" class="hover:-translate-y-1 transition text-lg">Tentang</a></li>
                <li><a href="login.php"
                        class="text-lg bg-white text-light-teal px-4 py-2 rounded border hover:bg-light-teal hover:border-white hover:text-white">Masuk
                        Akun</a>
                </li>
            </ul>
        </div>
        <!-- SISI KIRI -->
        <div class="w-full md:w-1/3 bg-light-teal flex justify-center items-center p-4 md:p-0">
            <img src="../../assets/img/home.png" alt="Home Image">
        </div>
        <!-- SISI KANAN -->
        <div class="w-full md:w-2/3 bg-white flex flex-col justify-center items-center p-8">
            <h1 class="text-2xl mb-4 text-light-teal font-extrabold md:text-4xl md:mb-6">Tentang KelasKu</h1>
            <div id="aboutContent" class="space-y-4">
                <p class="text-lg text-gray-700">
                    <span class="text-light-teal font-bold">KelasKu</span> adalah aplikasi pembelajaran online yang dikembangkan sebagai bagian dari <strong>Final Project</strong> untuk
                    memenuhi persyaratan dalam mata kuliah <strong>Pemrograman Web</strong>.
                    Proyek ini bertujuan untuk memberikan platform yang memfasilitasi interaksi antara dosen dan mahasiswa melalui fitur-fitur
                    yang inovatif dan user-friendly.
                </p>
                <h2 class="text-xl text-light-teal font-semibold">Tujuan Proyek</h2>
                <p class="text-lg text-gray-700">
                    Tujuan utama dari <span class="text-light-teal font-bold">KelasKu</span> adalah untuk:
                </p>
                <ul class="list-disc list-inside text-lg text-gray-700">
                    <li>Menyediakan ruang diskusi yang efektif antara dosen dan mahasiswa.</li>
                    <li>Mengorganisir materi pembelajaran secara terstruktur dan mudah diakses.</li>
                    <li>Menyediakan sistem penilaian yang transparan dan objektif.</li>
                    <li>Meningkatkan pengalaman belajar mahasiswa melalui teknologi digital.</li>
                </ul>
                <h2 class="text-xl text-light-teal font-semibold">Fitur Utama</h2>
                <p class="text-lg text-gray-700">
                    <span class="text-light-teal font-bold">KelasKu</span> dilengkapi dengan berbagai fitur unggulan, antara lain:
                </p>
                <ul class="list-disc list-inside text-lg text-gray-700">
                    <li><strong>Materi Pembelajaran:</strong> Menyediakan akses mudah ke materi kuliah, termasuk dokumen, video, dan presentasi.</li>
                    <li><strong>Sistem Penilaian:</strong> Mengelola tugas, kuis, dan ujian dengan penilaian yang transparan.</li>
                    <li><strong>Notifikasi:</strong> Memberikan pemberitahuan kepada pengguna mengenai pembaruan penting dan tenggat waktu tugas.</li>
                </ul>
                <h2 class="text-xl text-light-teal font-semibold">Teknologi yang Digunakan</h2>
                <p class="text-lg text-gray-700">
                    Aplikasi ini dibangun menggunakan teknologi berikut:
                </p>
                <ul class="list-disc list-inside text-lg text-gray-700">
                    <li><strong>Backend:</strong> PHP dan MySQL untuk manajemen data dan logika aplikasi.</li>
                    <li><strong>Frontend:</strong> HTML, CSS (Tailwind CSS), dan JavaScript untuk antarmuka pengguna yang responsif dan interaktif.</li>
                    <li><strong>Keamanan:</strong> Penggunaan password hashing dan prepared statements untuk melindungi data pengguna.</li>
                </ul>
                <h2 class="text-xl text-light-teal font-semibold">Tim Pengembang</h2>
                <p class="text-lg text-gray-700">
                    <span class="text-light-teal font-bold">KelasKu</span> dikembangkan oleh:
                </p>
                <ul class="list-disc list-inside text-lg text-gray-700">
                    <li><strong>Hamasah Fatiy Dakhilullah</strong> 5025231139</li>
                    <li><strong>Tamam Fajar Briliansyah</strong> 5025231142</li>
                </ul>
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
    </script>
</body>

</html>