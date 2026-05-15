# Frontend Backoffice Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a zero-build static HTML/JS backoffice to `public/` that lets any authenticated user exercise all API endpoints with filters, sorting, pagination, and CRUD modals.

**Architecture:** Five static HTML files served directly by the PHP server from `public/`. Alpine.js (CDN, defer) drives reactivity per page; AdminLTE 3 (CDN) provides the admin shell. Three shared JS files (`auth.js`, `api.js`, `layout.js`) are loaded in `<head>` as normal scripts. jQuery, Bootstrap, and AdminLTE JS load at the bottom of `<body>`. Alpine is scoped to `<body x-data="...">` so modals placed anywhere in the body are reactive.

**Tech Stack:** AdminLTE 3.2 (Bootstrap 4.6 + Font Awesome 5), Alpine.js 3.13, jQuery 3.7 — all via CDN. No Node.js, no bundler.

---

## File Map

| File | Action | Purpose |
|---|---|---|
| `public/js/auth.js` | Create | Token get/set/clear/getEmail from localStorage |
| `public/js/api.js` | Create | Fetch wrapper: injects Bearer token, parses errors, redirects on 401 |
| `public/js/layout.js` | Create | Injects AdminLTE navbar + sidebar HTML, sets email, wires logout |
| `public/index.html` | Create | Login page — no layout shell, just a centred card |
| `public/shops.html` | Create | Shops CRUD, default post-login landing |
| `public/companies.html` | Create | Companies CRUD |
| `public/users.html` | Create | Users CRUD with 403 guard |

## CDN URLs (pinned versions)

| Resource | URL |
|---|---|
| Bootstrap 4.6 CSS | `https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css` |
| Font Awesome 5 CSS | `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css` |
| AdminLTE 3.2 CSS | `https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css` |
| jQuery 3.7 | `https://code.jquery.com/jquery-3.7.1.min.js` |
| Bootstrap 4.6 JS | `https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js` |
| AdminLTE 3.2 JS | `https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js` |
| Alpine.js 3.13 | `https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js` (**defer**) |

## Script Load Order (data pages)

`<head>` — run immediately when parsed, define global functions:
1. `js/auth.js`
2. `js/api.js`
3. `js/layout.js`
4. Alpine.js (`defer` — runs after body scripts, then DOMContentLoaded fires and layout.js callback injects nav/sidebar)

Bottom of `<body>` — synchronous, run after HTML is parsed:
5. jQuery
6. Bootstrap bundle
7. AdminLTE

## API Quick Reference

| Action | Method + URL | Notes |
|---|---|---|
| Login | `POST /api/auth/login` | Returns `{token}` |
| List shops | `GET /api/shops` | Params: `company_id`, `name`, `city`, `country`, `is_digital`, `sort_by`, `sort_direction`, `page`, `limit` |
| Create shop | `POST /api/companies/{companyId}/shops` | `companyId` is a path param |
| Update shop | `PATCH /api/shops/{id}` | |
| Delete shop | `DELETE /api/shops/{id}` | |
| List companies | `GET /api/companies` | Admin-only. Same pagination params. Filters: `name`, `city`, `country` |
| Create company | `POST /api/companies` | |
| Update company | `PATCH /api/companies/{id}` | |
| Delete company | `DELETE /api/companies/{id}` | |
| List users | `GET /api/users` | Filters: `email`, `role`, `is_active`, `company_ids`, `shop_ids`, `created_from`, `created_to` |
| Create user | `POST /api/users` | |
| Update user | `PATCH /api/users/{id}` | |
| Delete user | `DELETE /api/users/{id}` | |

JWT payload claims: `user_id` (UUID), `email` (string).

---

## Task 1: Shared modules — auth.js and api.js

**Files:**
- Create: `public/js/auth.js`
- Create: `public/js/api.js`

- [ ] **Step 1: Create the `public/js/` directory and `auth.js`**

```bash
mkdir -p /path/to/project/public/js
```

Content of `public/js/auth.js`:
```javascript
const AUTH_KEY = 'shm_token';

function getToken() {
    return localStorage.getItem(AUTH_KEY);
}

function setToken(token) {
    localStorage.setItem(AUTH_KEY, token);
}

function clearToken() {
    localStorage.removeItem(AUTH_KEY);
}

function getEmail() {
    const token = getToken();
    if (!token) return '';
    try {
        const payload = JSON.parse(atob(token.split('.')[1]));
        return payload.email || payload.sub || 'User';
    } catch {
        return 'User';
    }
}
```

- [ ] **Step 2: Create `api.js`**

Content of `public/js/api.js`:
```javascript
const api = {
    async request(method, path, body = null, params = null) {
        const token = getToken();
        if (!token) { location.href = 'index.html'; throw new Error('No token'); }

        let url = path;
        if (params) {
            const qs = new URLSearchParams(
                Object.fromEntries(
                    Object.entries(params).filter(([, v]) => v !== '' && v !== null && v !== undefined)
                )
            ).toString();
            if (qs) url += '?' + qs;
        }

        const headers = { 'Authorization': 'Bearer ' + token };
        if (body !== null) headers['Content-Type'] = 'application/json';

        let response;
        try {
            response = await fetch(url, {
                method,
                headers,
                body: body !== null ? JSON.stringify(body) : undefined,
            });
        } catch {
            throw { error: 'Network error. Is the server running?' };
        }

        if (response.status === 401) {
            clearToken();
            location.href = 'index.html';
            throw { status: 401, error: 'Session expired' };
        }

        let data;
        try { data = await response.json(); } catch { data = {}; }

        if (!response.ok) {
            throw { status: response.status, error: data.error || response.statusText };
        }

        return data;
    },

    get(path, params)  { return this.request('GET',    path, null, params); },
    post(path, body)   { return this.request('POST',   path, body); },
    patch(path, body)  { return this.request('PATCH',  path, body); },
    delete(path)       { return this.request('DELETE', path); },
};
```

- [ ] **Step 3: Verify manually**

Open browser devtools console on any page after login, run:
```javascript
getEmail();       // Should return logged-in email string
getToken();       // Should return JWT string
```

- [ ] **Step 4: Commit**

```bash
git add public/js/auth.js public/js/api.js
git commit -m "feat: add shared auth and API JS modules for frontend"
```

---

## Task 2: Shared layout injector — layout.js

**Files:**
- Create: `public/js/layout.js`

- [ ] **Step 1: Create `public/js/layout.js`**

```javascript
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
```

- [ ] **Step 2: Commit**

```bash
git add public/js/layout.js
git commit -m "feat: add layout.js AdminLTE shell injector"
```

---

## Task 3: Login page — index.html

**Files:**
- Create: `public/index.html`

- [ ] **Step 1: Create `public/index.html`**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShopManager – Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <script src="js/auth.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
</head>
<body class="hold-transition login-page" x-data="loginPage()" x-init="init()">

<div class="login-box">
    <div class="login-logo"><b>Shop</b>Manager</div>
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Sign in to manage your stores</p>
            <div x-show="error" class="alert alert-danger py-2" x-text="error"></div>
            <form @submit.prevent="login()">
                <div class="input-group mb-3">
                    <input type="email" class="form-control" placeholder="Email"
                           x-model="form.email" required autocomplete="email">
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-envelope"></span></div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" class="form-control" placeholder="Password"
                           x-model="form.password" required autocomplete="current-password">
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-lock"></span></div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
                    <i x-show="loading" class="fas fa-spinner fa-spin mr-1"></i>
                    <span x-text="loading ? 'Signing in…' : 'Sign In'"></span>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
function loginPage() {
    return {
        form: { email: '', password: '' },
        loading: false,
        error: '',

        init() {
            if (getToken()) location.href = 'shops.html';
        },

        async login() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (!res.ok) { this.error = data.error || 'Login failed'; return; }
                setToken(data.token);
                location.href = 'shops.html';
            } catch {
                this.error = 'Network error. Is the server running?';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
</body>
</html>
```

- [ ] **Step 2: Verify manually**

Start the PHP server (`php -S localhost:8080 -t public`), open `http://localhost:8080/index.html`:
- Submitting wrong credentials shows the error message.
- Submitting correct admin credentials redirects to `shops.html`.
- Visiting `index.html` while already logged in (token in localStorage) redirects immediately to `shops.html`.

- [ ] **Step 3: Commit**

```bash
git add public/index.html
git commit -m "feat: add login page"
```

---

## Task 4: Shops page — shops.html

**Files:**
- Create: `public/shops.html`

- [ ] **Step 1: Create `public/shops.html`**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShopManager – Shops</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <script src="js/auth.js"></script>
    <script src="js/api.js"></script>
    <script src="js/layout.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
</head>
<body class="hold-transition sidebar-mini layout-fixed" x-data="shopsPage()" x-init="init()">
<div class="wrapper">

    <div id="layout-placeholder" data-page="shops"></div>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1>Shops</h1>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">

                <div x-show="pageError" class="alert alert-danger" x-text="pageError"></div>

                <template x-if="!pageError">
                    <div>
                        <!-- Filters -->
                        <div class="card card-default collapsed-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Filters</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="col-md-3 form-group">
                                        <label>Company ID</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="UUID"
                                               x-model="filters.company_id">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Name</label>
                                        <input type="text" class="form-control form-control-sm"
                                               x-model="filters.name">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>City</label>
                                        <input type="text" class="form-control form-control-sm"
                                               x-model="filters.city">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Country</label>
                                        <input type="text" class="form-control form-control-sm"
                                               x-model="filters.country">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Type</label>
                                        <select class="form-control form-control-sm" x-model="filters.is_digital">
                                            <option value="">All</option>
                                            <option value="true">Digital</option>
                                            <option value="false">Physical</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Sort by</label>
                                        <select class="form-control form-control-sm" x-model="filters.sort_by">
                                            <option value="name">Name</option>
                                            <option value="company_id">Company ID</option>
                                            <option value="city">City</option>
                                            <option value="country">Country</option>
                                            <option value="created_at">Created at</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Direction</label>
                                        <select class="form-control form-control-sm" x-model="filters.sort_direction">
                                            <option value="asc">Asc</option>
                                            <option value="desc">Desc</option>
                                        </select>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-primary" @click="applyFilters()">Apply</button>
                                <button class="btn btn-sm btn-default ml-1" @click="resetFilters()">Reset</button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Shops</h3>
                                <div class="card-tools">
                                    <button class="btn btn-sm btn-success" @click="openCreate()">
                                        <i class="fas fa-plus"></i> New Shop
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div x-show="loading" class="text-center p-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                </div>
                                <table x-show="!loading" class="table table-striped table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Company ID</th>
                                            <th>City</th>
                                            <th>Country</th>
                                            <th>Type</th>
                                            <th>Active</th>
                                            <th>Created</th>
                                            <th style="width:80px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in rows" :key="row.id">
                                            <tr>
                                                <td x-text="row.name"></td>
                                                <td class="text-monospace small" x-text="row.company_id"></td>
                                                <td x-text="row.city || '–'"></td>
                                                <td x-text="row.country || '–'"></td>
                                                <td>
                                                    <span x-show="row.is_digital" class="badge badge-info">Digital</span>
                                                    <span x-show="!row.is_digital" class="badge badge-secondary">Physical</span>
                                                </td>
                                                <td>
                                                    <span x-show="row.is_active" class="badge badge-success">Yes</span>
                                                    <span x-show="!row.is_active" class="badge badge-danger">No</span>
                                                </td>
                                                <td x-text="(row.created_at || '').slice(0, 10)"></td>
                                                <td>
                                                    <button class="btn btn-xs btn-info mr-1" title="Edit"
                                                            @click="openEdit(row)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-xs btn-danger" title="Delete"
                                                            @click="deleteRow(row.id)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="!loading && rows.length === 0">
                                            <td colspan="8" class="text-center text-muted py-3">No shops found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer py-2">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <small class="text-muted"
                                               x-text="pagination.total + ' total'"></small>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-sm btn-default"
                                                @click="prevPage()" :disabled="pagination.page <= 1">‹</button>
                                        <span class="mx-2 small"
                                              x-text="'Page ' + pagination.page + ' of ' + (pagination.pages || 1)"></span>
                                        <button class="btn btn-sm btn-default"
                                                @click="nextPage()" :disabled="pagination.page >= pagination.pages">›</button>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-control form-control-sm"
                                                x-model.number="pagination.limit" @change="applyFilters()">
                                            <option :value="10">10</option>
                                            <option :value="25">25</option>
                                            <option :value="50">50</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

            </div>
        </section>
    </div>

    <footer class="main-footer py-2">
        <strong>ShopManager API</strong>
    </footer>
</div>

<!-- Modal -->
<div x-show="modal.open" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;overflow-y:auto;">
    <div class="modal-dialog mt-5">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"
                    x-text="modal.mode === 'create' ? 'New Shop' : 'Edit Shop'"></h5>
                <button type="button" class="close" @click="modal.open = false">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div x-show="modal.error" class="alert alert-danger py-2"
                     x-text="modal.error"></div>
                <div x-show="modal.mode === 'create'" class="form-group">
                    <label>Company ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" placeholder="UUID"
                           x-model="modal.form.company_id">
                </div>
                <div class="form-group">
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" x-model="modal.form.name">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" x-model="modal.form.email">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" class="form-control" placeholder="+33612345678"
                           x-model="modal.form.phone_number">
                </div>
                <div class="form-group">
                    <label>Address line 1</label>
                    <input type="text" class="form-control" x-model="modal.form.addressLine1">
                </div>
                <div class="form-row">
                    <div class="col form-group">
                        <label>City</label>
                        <input type="text" class="form-control" x-model="modal.form.city">
                    </div>
                    <div class="col form-group">
                        <label>Postal code</label>
                        <input type="text" class="form-control" x-model="modal.form.postal_code">
                    </div>
                    <div class="col form-group">
                        <label>Country</label>
                        <input type="text" class="form-control" x-model="modal.form.country">
                    </div>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="shopDigital"
                           x-model="modal.form.is_digital">
                    <label class="form-check-label" for="shopDigital">Digital shop</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        @click="modal.open = false">Cancel</button>
                <button type="button" class="btn btn-primary"
                        @click="save()" :disabled="modal.saving">
                    <i x-show="modal.saving" class="fas fa-spinner fa-spin mr-1"></i>
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
function shopsPage() {
    return {
        filters: {
            company_id: '', name: '', city: '', country: '',
            is_digital: '', sort_by: 'name', sort_direction: 'asc',
        },
        rows: [],
        pagination: { page: 1, limit: 10, total: 0, pages: 0 },
        modal: { open: false, mode: 'create', form: {}, error: '', saving: false },
        loading: false,
        pageError: '',

        init() {
            if (!getToken()) { location.href = 'index.html'; return; }
            this.load();
        },

        async load() {
            this.loading = true;
            try {
                const params = {
                    page: this.pagination.page,
                    limit: this.pagination.limit,
                    ...Object.fromEntries(
                        Object.entries(this.filters).filter(([, v]) => v !== '')
                    ),
                };
                const data = await api.get('/api/shops', params);
                this.rows = data.data;
                Object.assign(this.pagination, data.pagination);
            } catch (e) {
                this.pageError = e.error || 'Failed to load shops';
            } finally {
                this.loading = false;
            }
        },

        applyFilters() { this.pagination.page = 1; this.load(); },

        resetFilters() {
            this.filters = {
                company_id: '', name: '', city: '', country: '',
                is_digital: '', sort_by: 'name', sort_direction: 'asc',
            };
            this.applyFilters();
        },

        prevPage() { if (this.pagination.page > 1) { this.pagination.page--; this.load(); } },
        nextPage() { if (this.pagination.page < this.pagination.pages) { this.pagination.page++; this.load(); } },

        openCreate() {
            this.modal = {
                open: true, mode: 'create', error: '', saving: false,
                form: {
                    company_id: '', name: '', email: '', phone_number: '',
                    addressLine1: '', city: '', postal_code: '', country: '', is_digital: false,
                },
            };
        },

        openEdit(row) {
            this.modal = {
                open: true, mode: 'edit', error: '', saving: false,
                form: { ...row },
            };
        },

        async save() {
            this.modal.saving = true;
            this.modal.error = '';
            try {
                if (this.modal.mode === 'create') {
                    const { company_id, ...body } = this.modal.form;
                    await api.post(`/api/companies/${company_id}/shops`, body);
                } else {
                    const { id, company_id, created_at, updated_at, ...body } = this.modal.form;
                    await api.patch(`/api/shops/${id}`, body);
                }
                this.modal.open = false;
                this.load();
            } catch (e) {
                this.modal.error = e.error || 'An error occurred';
            } finally {
                this.modal.saving = false;
            }
        },

        async deleteRow(id) {
            if (!confirm('Delete this shop?')) return;
            try {
                await api.delete(`/api/shops/${id}`);
                this.load();
            } catch (e) {
                alert(e.error || 'Failed to delete');
            }
        },
    };
}
</script>
</body>
</html>
```

- [ ] **Step 2: Verify manually**

Navigate to `http://localhost:8080/shops.html`:
- Sidebar shows "Shops" active.
- Logged-in email appears in the top-right navbar.
- Table loads and shows shops (or "No shops found" if empty).
- Clicking "New Shop" opens the modal; filling name + company ID and saving creates a shop.
- Edit button populates the modal with existing data.
- Delete button confirms and removes the row.
- Filter "Apply" filters the table; "Reset" clears filters.
- Page size selector updates results.

- [ ] **Step 3: Commit**

```bash
git add public/shops.html
git commit -m "feat: add shops CRUD page"
```

---

## Task 5: Companies page — companies.html

**Files:**
- Create: `public/companies.html`

Note: The companies list endpoint is **admin-only** (`authorizeAdminOnlyAction`). Non-admin users will receive a 403, which the page handles by showing an alert instead of the table.

- [ ] **Step 1: Create `public/companies.html`**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShopManager – Companies</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <script src="js/auth.js"></script>
    <script src="js/api.js"></script>
    <script src="js/layout.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
</head>
<body class="hold-transition sidebar-mini layout-fixed" x-data="companiesPage()" x-init="init()">
<div class="wrapper">

    <div id="layout-placeholder" data-page="companies"></div>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1>Companies</h1>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">

                <div x-show="pageError" class="alert alert-danger" x-text="pageError"></div>

                <template x-if="!pageError">
                    <div>
                        <!-- Filters -->
                        <div class="card card-default collapsed-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Filters</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="col-md-3 form-group">
                                        <label>Name</label>
                                        <input type="text" class="form-control form-control-sm"
                                               x-model="filters.name">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>City</label>
                                        <input type="text" class="form-control form-control-sm"
                                               x-model="filters.city">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Country</label>
                                        <input type="text" class="form-control form-control-sm"
                                               x-model="filters.country">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Sort by</label>
                                        <select class="form-control form-control-sm"
                                                x-model="filters.sort_by">
                                            <option value="name">Name</option>
                                            <option value="city">City</option>
                                            <option value="country">Country</option>
                                            <option value="created_at">Created at</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Direction</label>
                                        <select class="form-control form-control-sm"
                                                x-model="filters.sort_direction">
                                            <option value="asc">Asc</option>
                                            <option value="desc">Desc</option>
                                        </select>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-primary" @click="applyFilters()">Apply</button>
                                <button class="btn btn-sm btn-default ml-1" @click="resetFilters()">Reset</button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Companies</h3>
                                <div class="card-tools">
                                    <button class="btn btn-sm btn-success" @click="openCreate()">
                                        <i class="fas fa-plus"></i> New Company
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div x-show="loading" class="text-center p-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                </div>
                                <table x-show="!loading" class="table table-striped table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>City</th>
                                            <th>Country</th>
                                            <th>Active</th>
                                            <th>Created</th>
                                            <th style="width:80px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in rows" :key="row.id">
                                            <tr>
                                                <td x-text="row.name"></td>
                                                <td x-text="row.email || '–'"></td>
                                                <td x-text="row.city || '–'"></td>
                                                <td x-text="row.country || '–'"></td>
                                                <td>
                                                    <span x-show="row.is_active"
                                                          class="badge badge-success">Yes</span>
                                                    <span x-show="!row.is_active"
                                                          class="badge badge-danger">No</span>
                                                </td>
                                                <td x-text="(row.created_at || '').slice(0, 10)"></td>
                                                <td>
                                                    <button class="btn btn-xs btn-info mr-1" title="Edit"
                                                            @click="openEdit(row)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-xs btn-danger" title="Delete"
                                                            @click="deleteRow(row.id)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="!loading && rows.length === 0">
                                            <td colspan="7"
                                                class="text-center text-muted py-3">No companies found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer py-2">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <small class="text-muted"
                                               x-text="pagination.total + ' total'"></small>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-sm btn-default"
                                                @click="prevPage()" :disabled="pagination.page <= 1">‹</button>
                                        <span class="mx-2 small"
                                              x-text="'Page ' + pagination.page + ' of ' + (pagination.pages || 1)"></span>
                                        <button class="btn btn-sm btn-default"
                                                @click="nextPage()"
                                                :disabled="pagination.page >= pagination.pages">›</button>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-control form-control-sm"
                                                x-model.number="pagination.limit" @change="applyFilters()">
                                            <option :value="10">10</option>
                                            <option :value="25">25</option>
                                            <option :value="50">50</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

            </div>
        </section>
    </div>

    <footer class="main-footer py-2">
        <strong>ShopManager API</strong>
    </footer>
</div>

<!-- Modal -->
<div x-show="modal.open" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;overflow-y:auto;">
    <div class="modal-dialog mt-5">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"
                    x-text="modal.mode === 'create' ? 'New Company' : 'Edit Company'"></h5>
                <button type="button" class="close" @click="modal.open = false">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div x-show="modal.error" class="alert alert-danger py-2"
                     x-text="modal.error"></div>
                <div class="form-group">
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" x-model="modal.form.name">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" x-model="modal.form.email">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" class="form-control" placeholder="+33612345678"
                           x-model="modal.form.phone_number">
                </div>
                <div class="form-group">
                    <label>Website</label>
                    <input type="url" class="form-control" x-model="modal.form.website">
                </div>
                <div class="form-group">
                    <label>Address line 1</label>
                    <input type="text" class="form-control" x-model="modal.form.addressLine1">
                </div>
                <div class="form-row">
                    <div class="col form-group">
                        <label>City</label>
                        <input type="text" class="form-control" x-model="modal.form.city">
                    </div>
                    <div class="col form-group">
                        <label>Postal code</label>
                        <input type="text" class="form-control" x-model="modal.form.postal_code">
                    </div>
                    <div class="col form-group">
                        <label>Country</label>
                        <input type="text" class="form-control" x-model="modal.form.country">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        @click="modal.open = false">Cancel</button>
                <button type="button" class="btn btn-primary"
                        @click="save()" :disabled="modal.saving">
                    <i x-show="modal.saving" class="fas fa-spinner fa-spin mr-1"></i>
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
function companiesPage() {
    return {
        filters: {
            name: '', city: '', country: '', sort_by: 'name', sort_direction: 'asc',
        },
        rows: [],
        pagination: { page: 1, limit: 10, total: 0, pages: 0 },
        modal: { open: false, mode: 'create', form: {}, error: '', saving: false },
        loading: false,
        pageError: '',

        init() {
            if (!getToken()) { location.href = 'index.html'; return; }
            this.load();
        },

        async load() {
            this.loading = true;
            try {
                const params = {
                    page: this.pagination.page,
                    limit: this.pagination.limit,
                    ...Object.fromEntries(
                        Object.entries(this.filters).filter(([, v]) => v !== '')
                    ),
                };
                const data = await api.get('/api/companies', params);
                this.rows = data.data;
                Object.assign(this.pagination, data.pagination);
            } catch (e) {
                this.pageError = e.error || 'Failed to load companies';
            } finally {
                this.loading = false;
            }
        },

        applyFilters() { this.pagination.page = 1; this.load(); },

        resetFilters() {
            this.filters = { name: '', city: '', country: '', sort_by: 'name', sort_direction: 'asc' };
            this.applyFilters();
        },

        prevPage() { if (this.pagination.page > 1) { this.pagination.page--; this.load(); } },
        nextPage() { if (this.pagination.page < this.pagination.pages) { this.pagination.page++; this.load(); } },

        openCreate() {
            this.modal = {
                open: true, mode: 'create', error: '', saving: false,
                form: {
                    name: '', email: '', phone_number: '', website: '',
                    addressLine1: '', city: '', postal_code: '', country: '',
                },
            };
        },

        openEdit(row) {
            this.modal = { open: true, mode: 'edit', error: '', saving: false, form: { ...row } };
        },

        async save() {
            this.modal.saving = true;
            this.modal.error = '';
            try {
                if (this.modal.mode === 'create') {
                    await api.post('/api/companies', this.modal.form);
                } else {
                    const { id, created_at, updated_at, is_active, ...body } = this.modal.form;
                    await api.patch(`/api/companies/${id}`, body);
                }
                this.modal.open = false;
                this.load();
            } catch (e) {
                this.modal.error = e.error || 'An error occurred';
            } finally {
                this.modal.saving = false;
            }
        },

        async deleteRow(id) {
            if (!confirm('Delete this company?')) return;
            try {
                await api.delete(`/api/companies/${id}`);
                this.load();
            } catch (e) {
                alert(e.error || 'Failed to delete');
            }
        },
    };
}
</script>
</body>
</html>
```

- [ ] **Step 2: Verify manually**

Navigate to `http://localhost:8080/companies.html` as admin:
- Table loads and shows companies.
- Create, edit, delete modals work.
- As a non-admin user, the page shows "You do not have permission…" alert.

- [ ] **Step 3: Commit**

```bash
git add public/companies.html
git commit -m "feat: add companies CRUD page"
```

---

## Task 6: Users page — users.html

**Files:**
- Create: `public/users.html`

Note: The users list is restricted to Admin and CompanyAdmin. A 403 response shows an alert instead of the table. The edit modal excludes the password field (the PATCH endpoint supports it, but keeping the UI simple).

- [ ] **Step 1: Create `public/users.html`**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShopManager – Users</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <script src="js/auth.js"></script>
    <script src="js/api.js"></script>
    <script src="js/layout.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
</head>
<body class="hold-transition sidebar-mini layout-fixed" x-data="usersPage()" x-init="init()">
<div class="wrapper">

    <div id="layout-placeholder" data-page="users"></div>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1>Users</h1>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">

                <div x-show="pageError" class="alert alert-danger" x-text="pageError"></div>

                <template x-if="!pageError">
                    <div>
                        <!-- Filters -->
                        <div class="card card-default collapsed-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Filters</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool"
                                            data-card-widget="collapse">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="col-md-3 form-group">
                                        <label>Email</label>
                                        <input type="text" class="form-control form-control-sm"
                                               x-model="filters.email">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Role</label>
                                        <select class="form-control form-control-sm"
                                                x-model="filters.role">
                                            <option value="">All roles</option>
                                            <option value="admin">Admin</option>
                                            <option value="company_admin">Company Admin</option>
                                            <option value="shop_manager">Shop Manager</option>
                                            <option value="employee">Employee</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Active</label>
                                        <select class="form-control form-control-sm"
                                                x-model="filters.is_active">
                                            <option value="">All</option>
                                            <option value="true">Active</option>
                                            <option value="false">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Company IDs</label>
                                        <input type="text" class="form-control form-control-sm"
                                               placeholder="UUID,UUID,…" x-model="filters.company_ids">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Shop IDs</label>
                                        <input type="text" class="form-control form-control-sm"
                                               placeholder="UUID,UUID,…" x-model="filters.shop_ids">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Created from</label>
                                        <input type="date" class="form-control form-control-sm"
                                               x-model="filters.created_from">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Created to</label>
                                        <input type="date" class="form-control form-control-sm"
                                               x-model="filters.created_to">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Sort by</label>
                                        <select class="form-control form-control-sm"
                                                x-model="filters.sort_by">
                                            <option value="email">Email</option>
                                            <option value="first_name">First name</option>
                                            <option value="last_name">Last name</option>
                                            <option value="role">Role</option>
                                            <option value="created_at">Created at</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Direction</label>
                                        <select class="form-control form-control-sm"
                                                x-model="filters.sort_direction">
                                            <option value="asc">Asc</option>
                                            <option value="desc">Desc</option>
                                        </select>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-primary"
                                        @click="applyFilters()">Apply</button>
                                <button class="btn btn-sm btn-default ml-1"
                                        @click="resetFilters()">Reset</button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Users</h3>
                                <div class="card-tools">
                                    <button class="btn btn-sm btn-success" @click="openCreate()">
                                        <i class="fas fa-plus"></i> New User
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div x-show="loading" class="text-center p-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                </div>
                                <table x-show="!loading"
                                       class="table table-striped table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Email</th>
                                            <th>First name</th>
                                            <th>Last name</th>
                                            <th>Role</th>
                                            <th>Company</th>
                                            <th>Shop</th>
                                            <th>Active</th>
                                            <th>Created</th>
                                            <th style="width:80px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in rows" :key="row.id">
                                            <tr>
                                                <td x-text="row.email"></td>
                                                <td x-text="row.first_name"></td>
                                                <td x-text="row.last_name"></td>
                                                <td>
                                                    <span class="badge badge-secondary"
                                                          x-text="row.role"></span>
                                                </td>
                                                <td class="text-monospace small"
                                                    x-text="(row.company_id || '').slice(0, 8) || '–'"></td>
                                                <td class="text-monospace small"
                                                    x-text="(row.shop_id || '').slice(0, 8) || '–'"></td>
                                                <td>
                                                    <span x-show="row.is_active"
                                                          class="badge badge-success">Yes</span>
                                                    <span x-show="!row.is_active"
                                                          class="badge badge-danger">No</span>
                                                </td>
                                                <td x-text="(row.created_at || '').slice(0, 10)"></td>
                                                <td>
                                                    <button class="btn btn-xs btn-info mr-1"
                                                            title="Edit" @click="openEdit(row)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-xs btn-danger"
                                                            title="Delete"
                                                            @click="deleteRow(row.id)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="!loading && rows.length === 0">
                                            <td colspan="9"
                                                class="text-center text-muted py-3">No users found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer py-2">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <small class="text-muted"
                                               x-text="pagination.total + ' total'"></small>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-sm btn-default"
                                                @click="prevPage()"
                                                :disabled="pagination.page <= 1">‹</button>
                                        <span class="mx-2 small"
                                              x-text="'Page ' + pagination.page + ' of ' + (pagination.pages || 1)"></span>
                                        <button class="btn btn-sm btn-default"
                                                @click="nextPage()"
                                                :disabled="pagination.page >= pagination.pages">›</button>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-control form-control-sm"
                                                x-model.number="pagination.limit"
                                                @change="applyFilters()">
                                            <option :value="10">10</option>
                                            <option :value="25">25</option>
                                            <option :value="50">50</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

            </div>
        </section>
    </div>

    <footer class="main-footer py-2">
        <strong>ShopManager API</strong>
    </footer>
</div>

<!-- Modal -->
<div x-show="modal.open"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;overflow-y:auto;">
    <div class="modal-dialog mt-5">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"
                    x-text="modal.mode === 'create' ? 'New User' : 'Edit User'"></h5>
                <button type="button" class="close" @click="modal.open = false">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div x-show="modal.error" class="alert alert-danger py-2"
                     x-text="modal.error"></div>

                <!-- Create-only fields -->
                <div x-show="modal.mode === 'create'" class="form-group">
                    <label>Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" x-model="modal.form.email">
                </div>
                <div x-show="modal.mode === 'create'" class="form-group">
                    <label>Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" autocomplete="new-password"
                           x-model="modal.form.password">
                </div>

                <!-- Shared fields -->
                <div class="form-row">
                    <div class="col form-group">
                        <label>First name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" x-model="modal.form.first_name">
                    </div>
                    <div class="col form-group">
                        <label>Last name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" x-model="modal.form.last_name">
                    </div>
                </div>

                <!-- Edit-only: email -->
                <div x-show="modal.mode === 'edit'" class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" x-model="modal.form.email">
                </div>

                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" class="form-control" placeholder="+33612345678"
                           x-model="modal.form.phone_number">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select class="form-control" x-model="modal.form.role">
                        <option value="employee">Employee</option>
                        <option value="shop_manager">Shop Manager</option>
                        <option value="company_admin">Company Admin</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Company ID</label>
                    <input type="text" class="form-control" placeholder="UUID"
                           x-model="modal.form.company_id">
                </div>
                <div class="form-group">
                    <label>Shop ID</label>
                    <input type="text" class="form-control" placeholder="UUID"
                           x-model="modal.form.shop_id">
                </div>

                <!-- Edit-only: is_active -->
                <div x-show="modal.mode === 'edit'" class="form-check">
                    <input type="checkbox" class="form-check-input" id="userActive"
                           x-model="modal.form.is_active">
                    <label class="form-check-label" for="userActive">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        @click="modal.open = false">Cancel</button>
                <button type="button" class="btn btn-primary"
                        @click="save()" :disabled="modal.saving">
                    <i x-show="modal.saving" class="fas fa-spinner fa-spin mr-1"></i>
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
function usersPage() {
    return {
        filters: {
            email: '', role: '', is_active: '', company_ids: '', shop_ids: '',
            created_from: '', created_to: '', sort_by: 'email', sort_direction: 'asc',
        },
        rows: [],
        pagination: { page: 1, limit: 10, total: 0, pages: 0 },
        modal: { open: false, mode: 'create', form: {}, error: '', saving: false },
        loading: false,
        pageError: '',

        init() {
            if (!getToken()) { location.href = 'index.html'; return; }
            this.load();
        },

        async load() {
            this.loading = true;
            try {
                const params = {
                    page: this.pagination.page,
                    limit: this.pagination.limit,
                    ...Object.fromEntries(
                        Object.entries(this.filters).filter(([, v]) => v !== '')
                    ),
                };
                const data = await api.get('/api/users', params);
                this.rows = data.data;
                Object.assign(this.pagination, data.pagination);
            } catch (e) {
                this.pageError = e.error || 'Failed to load users';
            } finally {
                this.loading = false;
            }
        },

        applyFilters() { this.pagination.page = 1; this.load(); },

        resetFilters() {
            this.filters = {
                email: '', role: '', is_active: '', company_ids: '', shop_ids: '',
                created_from: '', created_to: '', sort_by: 'email', sort_direction: 'asc',
            };
            this.applyFilters();
        },

        prevPage() { if (this.pagination.page > 1) { this.pagination.page--; this.load(); } },
        nextPage() { if (this.pagination.page < this.pagination.pages) { this.pagination.page++; this.load(); } },

        openCreate() {
            this.modal = {
                open: true, mode: 'create', error: '', saving: false,
                form: {
                    email: '', password: '', first_name: '', last_name: '',
                    phone_number: '', role: 'employee', company_id: '', shop_id: '',
                },
            };
        },

        openEdit(row) {
            this.modal = {
                open: true, mode: 'edit', error: '', saving: false,
                form: { ...row },
            };
        },

        async save() {
            this.modal.saving = true;
            this.modal.error = '';
            try {
                if (this.modal.mode === 'create') {
                    await api.post('/api/users', this.modal.form);
                } else {
                    const { id, created_at, updated_at, last_login_at, password, ...body } = this.modal.form;
                    await api.patch(`/api/users/${id}`, body);
                }
                this.modal.open = false;
                this.load();
            } catch (e) {
                this.modal.error = e.error || 'An error occurred';
            } finally {
                this.modal.saving = false;
            }
        },

        async deleteRow(id) {
            if (!confirm('Delete this user?')) return;
            try {
                await api.delete(`/api/users/${id}`);
                this.load();
            } catch (e) {
                alert(e.error || 'Failed to delete');
            }
        },
    };
}
</script>
</body>
</html>
```

- [ ] **Step 2: Verify manually**

Navigate to `http://localhost:8080/users.html` as admin:
- All filters work (email partial match, role dropdown, is_active dropdown, date pickers, company_ids, shop_ids).
- Create user form validates and saves.
- Edit modal pre-fills fields; saving PATCHes the user.
- Delete confirms and removes.
- As a non-admin/non-company-admin, the page shows the 403 alert.

- [ ] **Step 3: Final commit**

```bash
git add public/users.html
git commit -m "feat: add users CRUD page"
```
