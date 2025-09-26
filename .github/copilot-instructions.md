# Copilot Instructions for AI Agents

## Project Overview
This is a PHP-based web application for user registration, login, and dashboard management. The project uses procedural PHP and interacts with a MySQL database (see `mi_base_datos.sql`).

## Key Components
- `login.html` / `login.php`: User login form and authentication logic.
- `registro.html` / `registro.php`: User registration form and logic.
- `postulante_dashboard.php`: Main dashboard for logged-in users.
- `acciones.php`: Contains core business logic for user actions.
- `conexion.php`: Centralizes MySQL connection logic.
- `css/`: Contains all stylesheets for the application.
- `mi_base_datos.sql`: SQL schema for the application's database.

## Data Flow
- All user data is stored in a MySQL database defined in `mi_base_datos.sql`.
- PHP scripts use `conexion.php` for database access.
- User authentication and registration POST requests are handled by `login.php` and `registro.php` respectively.
- After login, users are redirected to `postulante_dashboard.php`.

## Developer Workflows
- **No build step**: PHP files are interpreted directly by the server.
- **Testing**: Manual testing via browser; no automated test suite present.
- **Debugging**: Use `echo`, `var_dump`, or browser dev tools. No Xdebug or similar tools configured.
- **Database**: Import `mi_base_datos.sql` into MySQL before running the app.

## Project Conventions
- All database access should go through `conexion.php`.
- Use procedural PHP (no classes or namespaces).
- Keep business logic in `acciones.php` when possible.
- Use the `css/` directory for all stylesheets; reference them in HTML using relative paths.
- Spanish is used for variable and file names.

## Integration Points
- MySQL database (see `conexion.php` for connection details).
- No external PHP libraries or frameworks are used.

## Examples
- To add a new user action, implement logic in `acciones.php` and call it from the relevant PHP page.
- To add a new page, create an HTML/PHP file and include `conexion.php` if database access is needed.

## Additional Notes
- No `.env` or config file; credentials are likely hardcoded in `conexion.php`.
- No CI/CD or deployment scripts present.

---

If you add new workflows or conventions, update this file to keep AI agents productive.
