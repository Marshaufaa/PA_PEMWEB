document.addEventListener("DOMContentLoaded", function() {
    
    // --- 1. STATUS LOGIN ---
    // Ambil status login dari variabel PHP yang kita buat di HTML
    let isLoggedIn = (typeof php_isLoggedIn !== 'undefined') ? php_isLoggedIn : false; 

    // --- 2. ELEMEN YANG DIBUTUHKAN ---
    const sidebar = document.querySelector(".sidebar");
    const hamburgerMenu = document.querySelector(".hamburger-menu");
    
    // Elemen Dropdown Kategori
    const kategoriButton = document.querySelector(".kategori-button");
    const kategoriDropdown = document.querySelector(".kategori-dropdown");

    // Elemen Status Login
    const loggedInState = document.getElementById("loggedInState");
    const loggedInNav = document.getElementById("loggedInNav");
    const loggedOutState = document.getElementById("loggedOutState");
    const loggedOutNav = document.getElementById("loggedOutNav");

    
    // --- 3. FUNGSI UTAMA ---

    // Fungsi untuk memperbarui UI berdasarkan status login
    function updateLoginUI() {
        if (!isLoggedIn) {
            // Jika TIDAK login, tampilkan elemen 'loggedOut'
            if(loggedOutState) loggedOutState.classList.add("active-state");
            if(loggedOutNav) loggedOutNav.classList.add("active-state");
            if(loggedInState) loggedInState.classList.remove("active-state");
            if(loggedInNav) loggedInNav.classList.remove("active-state");
        } else {
            // Jika SUDAH login, tampilkan elemen 'loggedIn'
            if(loggedInState) loggedInState.classList.add("active-state");
            if(loggedInNav) loggedInNav.classList.add("active-state");
            if(loggedOutState) loggedOutState.classList.remove("active-state");
            if(loggedOutNav) loggedOutNav.classList.remove("active-state");
        }
    }

    // --- 4. EVENT LISTENERS ---

    // Toggle Sidebar
    if (hamburgerMenu && sidebar) {
        hamburgerMenu.addEventListener("click", function() {
            sidebar.classList.toggle("expanded");
        });
    }

    // Toggle Dropdown Kategori
    if (kategoriButton && kategoriDropdown) {
        kategoriButton.addEventListener("click", (e) => { 
            e.preventDefault(); // Mencegah link '#' melompat
            kategoriDropdown.classList.toggle("show"); 
        });
    }

    // Klik di luar dropdown untuk menutup
    window.addEventListener("click", (event) => {
        if (kategoriButton && !kategoriButton.contains(event.target) && 
            kategoriDropdown && !kategoriDropdown.contains(event.target)) {
            
            kategoriDropdown.classList.remove("show");
        }
    });

    // --- 5. INISIALISASI ---
    updateLoginUI(); // Atur UI yang benar saat halaman dimuat
});