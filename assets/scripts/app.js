/* assets/app.js */
import '../styles/app.css';
import './modal.js';

document.addEventListener('DOMContentLoaded', () => {
    // Custom JS here

    // Mobile Navbar Toggle
    const toggle = document.querySelector('.app-header__toggle');
    const nav = document.querySelector('.app-header__nav');
    
    if (toggle && nav) {
        toggle.addEventListener('click', () => {
             nav.classList.toggle('app-header__nav--open');
        });
    }

    // Dropdown Toggles
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', (e) => {
            e.stopPropagation();
            const parent = dropdown.parentElement;
            parent.classList.toggle('open');
            dropdown.setAttribute('aria-expanded', parent.classList.contains('open'));
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown.open').forEach(openDropdown => {
            openDropdown.classList.remove('open');
            openDropdown.querySelector('.dropdown-toggle').setAttribute('aria-expanded', 'false');
        });
    });
});
