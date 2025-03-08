document.getElementById('menu-toggle').addEventListener('click', function() {
    document.getElementById('nav-menu').classList.toggle('hidden');
});

const menuToggle = document.getElementById('menu-toggle');
const navMenu = document.getElementById('nav-menu');

menuToggle.addEventListener('click', () => {
    navMenu.classList.toggle('hidden');
});