# 🚀 Technical Test

Welcome! This project is a technical test implementation featuring a PHP-based API with Docker support. This guide will
help you set up, run, and test the application efficiently. There is an admin dashboard and API documentation available.
It was made for a company during my job search in 48h without any other demands than "Write a Backend-End to manage companies using REST APIs". Beware, the dashboard was vibe-coded without too much care as front-end wasn't evaluated.

The project has three tables:
- `users`
- `companies`
- `shops`

I have implemented a basic CRUD API for each table.

Users have roles and can be assigned to companies and shops.
A shop has a company.

Roles are the following:
- `admin`
- `company_admin`
- `shop_manager`
- `employee`

An admin can manage companies, shops and users without restrictions.
A company admin can manage companies, shops and users within their company.
A shop manager can manage their shop and employees.

---

## 🛠 Tech Stack

- **PHP 8.5+** (using Symfony components)
- **MySQL 8.4** (Using PDO to display my SQL skills)
- **Docker & Docker Compose**
- **Quality Tools**: PHPUnit, PHPStan, PHP CS Fixer, Behat
- **Auth**: JWT (lcobucci/jwt)

---

## 📋 Requirements

Ensure you have the following installed:

- [Docker](https://docs.docker.com/get-docker/) & [Docker Compose](https://docs.docker.com/compose/install/)
- `make` (optional, but highly recommended for automation)

*If running without Docker:*

- PHP 8.5 with `ext-pdo`
- Composer 2
- MySQL 8.4+

---

## ⚡ Quick Start (Docker)

The fastest way to get started is using the provided `Makefile`.

1. **Build and start the stack**:
   ```bash
   make setup
   ```
   > **Note**: This command builds the images, starts the services, and initializes the database with the schema and
   fixtures.

2. **Access the application**:
    - Web API: [http://localhost:8080](http://localhost:8080)
    - MySQL: `localhost:3306`
    - Admin Dashboard (Made with AI so it has issues): [http://localhost:8080/index.html](http://localhost:8080/index.html)
    - Swagger UI: [api-docs/index.html](http://localhost:8080/api-docs/index.html)
    - Postman Collection: In docs/postman

3. **Run the test suite**:
   ```bash
   make tests
   make behat
   ```

4. **Users**
    - Admin: `admin1@fixture.test` with password `password123`
    - Company Admin: `jack.taylor.ca.7710@fixture.test` with password `password123`
    - Shop Manager: `bob.reed.sm.11752@fixture.test` with password `password123`
    - Employee: `zoe.phillips.21376@fixture.test` with password `password123` 

---

## ⌨️ Useful Commands

### 🐳 Docker Management

| Command         | Description                              |
|:----------------|:-----------------------------------------|
| `make setup`    | Build, start services, and init database |
| `make up`       | Start services in background             |
| `make up-build` | Rebuild and start services               |
| `make down`     | Stop and remove containers               |
| `make shell`    | Open a shell in the app container        |
| `make logs`     | Follow application logs                  |
| `make restart`  | Restart the app container                |

### 🧪 Quality & Testing

| Command               | Description                                     |
|:----------------------|:------------------------------------------------|
| `make tests`          | Run all PHPUnit tests                           |
| `make behat`          | Run Behat BDD tests                             |
| `make tests-coverage` | Generate HTML coverage report (requires Xdebug) |
| `make analyse`        | Run PHPStan static analysis                     |
| `make format`         | Auto-format code with PHP CS Fixer              |
| `make format-check`   | Check code style without applying fixes         |

### 🗄️ Database Helpers

| Command          | Description                                |
|:-----------------|:-------------------------------------------|
| `make db-schema` | Re-apply `database/schema.sql`             |
| `make db-seed`   | Load fixtures from `database/fixtures.sql` |

---

## ⚙️ Configuration

Environment variables can be customized via shell or passed to `make` commands.

| Variable      | Default          | Description                |
|:--------------|:-----------------|:---------------------------|
| `DB_HOST`     | `mysql`          | Database host              |
| `DB_PORT`     | `3306`           | Database port              |
| `DB_DATABASE` | `technical_test` | Database name              |
| `DB_USERNAME` | `app`            | Database user              |
| `DB_PASSWORD` | `secret`         | Database password          |
| `JWT_SECRET`  | *(check dc.yml)* | Secret key for JWT signing |

---

## 🛠 Local Workflow (Non-Docker)

If you prefer to work outside of Docker:

1. **Install dependencies**:
   ```bash
   composer install
   ```
2. **Setup Database**:
    - Manually import `database/schema.sql` and `database/fixtures.sql` into your MySQL instance.
3. **Run the server**:
   ```bash
   php -S 0.0.0.0:8080 -t public
   ```
4. **Run tests**:
   ```bash
   ./vendor/bin/phpunit
   ```

---

## ❓ Troubleshooting

- **`libphonenumber\PhoneNumberUtil` not found**: Ensure composer dependencies are installed. In Docker, this happens
  automatically on first boot. Otherwise, run `make install`. I'm not sure why, but sometimes it isn't on the container
  when I install dependencies from local.
- **Database Connection Refused**: Ensure the MySQL container is healthy. Run `make ps` to check status.

---

## 📂 Project Structure
I have tried to use DDD, it may not be perfect as I have not worked with this architecture in my previous companies

- `src/` — Application source code (Domain, Application, Infrastructure)
  - `ModuleName` - (Auth|Company|Shop|Shared)
    - Application - Business logic
    - Domain - Use-case orchestration
    - Infrastructure - Technical implementation details and Persistence layer (Doctrine)
    - Presentation - Presentation layer (API)
- `tests/` — PHPUnit test suite
- `features/` — Behat features
- `database/` — SQL schema and fixtures
- `docker/` — Docker configuration and entrypoints
- `public/` — Entry point for the web server, the admin dashboard and api-docs
- `config/` — Dependency Injection and Routing configs
