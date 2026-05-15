function buildLayout(activePage) {
    function navItem(page, icon, label) {
        const active = activePage === page ? ' active' : '';
        return `
            <li class="nav-item">
                <a href="${page}.html" class="nav-link${active}">
                    <i class="nav-icon fas ${icon}"></i>
                    <p>${label}</p>
                </a>
            </li>`;
    }

    return `
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item d-flex align-items-center mr-3">
                    <i class="fas fa-user-circle mr-1"></i>
                    <span id="user-email" class="text-sm"></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="shops.html" class="brand-link px-4">
                <span class="brand-text font-weight-light">ShopManager</span>
            </a>
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
                        ${navItem('shops',     'fa-store',    'Shops')}
                        ${navItem('companies', 'fa-building', 'Companies')}
                        ${navItem('users',     'fa-users',    'Users')}
                    </ul>
                </nav>
            </div>
        </aside>`;
}

document.addEventListener('DOMContentLoaded', function () {
    const placeholder = document.getElementById('layout-placeholder');
    if (!placeholder) return;

    placeholder.innerHTML = buildLayout(placeholder.dataset.page || '');

    const emailEl = document.getElementById('user-email');
    if (emailEl) emailEl.textContent = getEmail();

    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            e.preventDefault();
            clearToken();
            location.href = 'index.html';
        });
    }
});
