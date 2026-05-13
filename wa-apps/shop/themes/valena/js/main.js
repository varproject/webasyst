document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('legrand-valena-sidebar-toggle');
    var backdrop = document.querySelector('.legrand-valena-sidebar-backdrop');

    function toggleSidebar() {
        document.body.classList.toggle('legrand-valena-sidebar-open');
    }

    if (toggle) {
        toggle.addEventListener('click', toggleSidebar);
    }

    if (backdrop) {
        backdrop.addEventListener('click', toggleSidebar);
    }
});
