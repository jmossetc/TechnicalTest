# 🚀 Technical Test

Welcome! This project is a technical test implementation featuring a PHP-based API with Docker support. This guide will help you set up, run, and test the application efficiently.

---

## 🛠 Tech Stack

- **PHP 8.5+** (using Symfony components)
- **MySQL 8.4**
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
   > **Note**: This command builds the images, starts the services, and initializes the database with the schema and fixtures.

2. **Access the application**:
   - Web API: [http://localhost:8080](http://localhost:8080)
   - MySQL: `localhost:3306`

3. **Run the test suite**:
   ```bash
   make tests
   ```

---

## ⌨️ Useful Commands

### 🐳 Docker Management
| Command | Description |
| :--- | :--- |
| `make setup` | Build, start services, and init database |
| `make up` | Start services in background |
| `make up-build` | Rebuild and start services |
| `make down` | Stop and remove containers |
| `make shell` | Open a shell in the app container |
| `make logs` | Follow application logs |
| `make restart` | Restart the app container |

### 🧪 Quality & Testing
| Command | Description |
| :--- | :--- |
| `make tests` | Run all PHPUnit tests |
| `make behat` | Run Behat BDD tests |
| `make tests-coverage` | Generate HTML coverage report (requires Xdebug) |
| `make analyse` | Run PHPStan static analysis |
| `make format` | Auto-format code with PHP CS Fixer |
| `make format-check` | Check code style without applying fixes |

### 🗄️ Database Helpers
| Command | Description |
| :--- | :--- |
| `make db-schema` | Re-apply `database/schema.sql` |
| `make db-seed` | Load fixtures from `database/fixtures.sql` |

---

## ⚙️ Configuration

Environment variables can be customized via shell or passed to `make` commands.

| Variable | Default | Description |
| :--- | :--- | :--- |
| `DB_HOST` | `mysql` | Database host |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | `technical_test` | Database name |
| `DB_USERNAME` | `app` | Database user |
| `DB_PASSWORD` | `secret` | Database password |
| `JWT_SECRET` | *(check dc.yml)* | Secret key for JWT signing |

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

- **`libphonenumber\PhoneNumberUtil` not found**: Ensure composer dependencies are installed. In Docker, this happens automatically on first boot. Otherwise, run `make install`.
- **Database Connection Refused**: Ensure the MySQL container is healthy. Run `make ps` to check status.
- **Xdebug/Coverage errors**: Ensure `XDEBUG_MODE=coverage` is set (handled automatically by `make tests-coverage`).

---

## 📂 Project Structure

- `src/` — Application source code (Domain, Application, Infrastructure)
- `tests/` — PHPUnit test suite
- `features/` — Behat features
- `database/` — SQL schema and fixtures
- `docker/` — Docker configuration and entrypoints
- `public/` — Entry point for the web server
- `config/` — Dependency Injection and Routing configs