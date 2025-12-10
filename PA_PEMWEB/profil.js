// Isi untuk profil-script.js
document.addEventListener("DOMContentLoaded", function() {
    
    // --- HANYA LOGIKA BURGER ---
    const sidebar = document.querySelector(".sidebar");
    const hamburgerMenu = document.querySelector(".hamburger-menu");

    if (hamburgerMenu && sidebar) {
        hamburgerMenu.addEventListener("click", function() {
            sidebar.classList.toggle("expanded");
        });
    }
    

});