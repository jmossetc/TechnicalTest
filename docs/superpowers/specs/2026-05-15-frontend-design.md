# Design: Simple Backoffice Frontend

**Date:** 2026-05-15
**Status:** Approved

## Overview

A zero-build-step HTML/JS backoffice served from `public/` that lets any authenticated user exercise all API endpoints — with filters, sorting, pagination, and basic CRUD modals.

Stack: **AdminLTE 3** (Bootstrap 4 + Font Awesome, CDN) + **Alpine.js v3** (CDN) for reactivity. No Node.js, no bundler, no `npm install`.

---

## File Structure

```
public/
  index.html          ← login page
  users.html          ← users list, filters, CRUD
  companies.html      ← companies list, filters, CRUD
  shops.html          ← shops list, filters, CRUD (default landing after login)
  js/
    api.js            ← fetch wrapper: injects Bearer token, redirects on 401
    auth.js           ← token helpers: get/set/clear shm_token in localStorage
```

All files are static and served by the existing PHP server alongside `index.php`.

---

## CDN Dependencies

| Library | Purpose |
|---|---|
| AdminLTE 3 | Admin layout shell (sidebar, top-bar, cards, tables) |
| Bootstrap 4 (bundled with AdminLTE) | Grid, modals, forms, badges |
| Font Awesome (bundled with AdminLTE) | Icons |
| Alpine.js v3 | Reactive state per page (no build step) |

---

## Authentication Flow

### Login (`index.html`)
- Centered Bootstrap card with email + password inputs and an error message area.
- On page load: if `localStorage.shm_token` exists → redirect to `shops.html`.
- On submit: `POST /api/auth/login`; on success store token as `localStorage.shm_token` and redirect to `shops.html`; on failure display `response.error` inline.

### Auth guard (all other pages)
- On page `init()`: if `shm_token` is absent → redirect to `index.html`.
- Any API response with status `401` clears `shm_token` and redirects to `index.html`.

### Top-bar (AdminLTE navbar)
- Displays the logged-in user's email, decoded from the JWT payload (base64, no extra API call).
- **Logout** button: clears `shm_token`, redirects to `index.html`.

---

## Shared Modules

### `js/auth.js`
```
getToken()   → string | null     reads localStorage.shm_token
setToken(t)  → void              writes localStorage.shm_token
clearToken() → void              removes localStorage.shm_token
getEmail()   → string            decodes JWT payload, returns email claim
```

### `js/api.js`
Thin wrapper around `fetch`:
- Attaches `Authorization: Bearer <token>` header on every request.
- On 401 response: calls `clearToken()` then redirects to `index.html`.
- Returns parsed JSON body on success, throws `{ status, error }` on error.

```
api.get(path, queryParams)        → Promise<any>
api.post(path, body)              → Promise<any>
api.patch(path, body)             → Promise<any>
api.delete(path)                  → Promise<any>
```

---

## Page Layout (all data pages)

Each page uses the AdminLTE shell:

```
┌─────────────────────────────────────────────────────┐
│ Top-bar: logo | [email] [Logout]                    │
├──────────┬──────────────────────────────────────────┤
│ Sidebar  │ Content area                             │
│  Shops   │  ┌─ Filter card (collapsible) ─────────┐ │
│  Companies│  │ [inputs…] [Apply] [Reset]           │ │
│  Users   │  └────────────────────────────────────┘ │
│          │  ┌─ Table card ───────────────── [New+] ┐ │
│          │  │ sortable headers | rows | actions    │ │
│          │  │ [← Prev] Page N of M [Next →] [10▾] │ │
│          │  └────────────────────────────────────┘ │
└──────────┴──────────────────────────────────────────┘
```

Alpine.js `x-data` per page manages:
- `filters` — current filter field values
- `sort` — `{ by, direction }` (toggled by clicking column headers)
- `rows` — current page of results
- `pagination` — `{ page, limit, total, pages }`
- `modal` — `{ open, mode: 'create'|'edit', form, error }`
- `loading`, `error` (page-level)

---

## Pages

### Shops (`shops.html`) — default post-login landing

**Filters:** `company_id` (UUID text), `name` (text)
**Sort:** `name`, `created_at`
**Columns:** ID · Company ID · Name · Address · Actions

**Create modal fields:** name (required), address (optional), company\_id (required — sent as a path parameter: `POST /api/companies/{companyId}/shops`)
**Edit modal fields:** name, address (PATCH `/api/shops/{id}`)

---

### Companies (`companies.html`)

**Filters:** `name` (text)
**Sort:** `name`, `created_at`
**Columns:** ID · Name · Actions

**Create modal fields:** name (required)
**Edit modal fields:** name (PATCH `/api/companies/{id}`)

---

### Users (`users.html`)

**Access guard:** If the API returns 403 on list, display a "You don't have permission to view this list" alert instead of the table.

**Filters:** `email` (text), `role` (select: all / admin / company\_admin / shop\_manager / employee), `company_id` (UUID text), `shop_id` (UUID text), `is_active` (select: All / Active / Inactive), `created_from` (date), `created_to` (date)
**Sort:** `email`, `created_at`
**Columns:** ID · Email · First name · Last name · Role · Company · Shop · Active · Created at · Actions

**Create modal fields:** email, password, first\_name, last\_name, role (select), company\_id, shop\_id
**Edit modal fields (PATCH):** first\_name, last\_name, email, phone\_number, is\_active (checkbox), role, company\_id, shop\_id
_Password change is excluded from the edit modal to keep the UI simple._

---

## Modals

All three pages use the same modal pattern:
- Bootstrap modal triggered by Alpine state (`modal.open`).
- **Save** button: calls `POST` (create) or `PATCH` (edit); on success closes modal and reloads the table; on error shows `response.error` inline below the form.
- **Delete** row: native `confirm()` dialog before calling `DELETE`; on success reloads table.

---

## Error Handling

| Scenario | Behaviour |
|---|---|
| 401 from any endpoint | Clear token, redirect to `index.html` |
| 403 on list | Show "not authorized" alert, hide table |
| 403 on create/edit/delete | Show error in modal / inline alert |
| 404 on edit/delete | Show error inline |
| 409 / 422 | Show `response.error` in modal |
| Network error | Show generic "Network error" message |

---

## Out of Scope

- Password change UI in the edit modal (endpoint supports it; UI does not for simplicity).
- Role-based sidebar hiding (all links always visible; 403 is handled per-page).
- Automated frontend tests.
- A build pipeline or package manager.
