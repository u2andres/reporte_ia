# Lrvl_curso_reporte

Aplicación web construida con **Laravel 12**. El proyecto está pensado como base para un sistema de **reportes de cursos**. Actualmente se encuentra en estado inicial (esqueleto de Laravel recién instalado), listo para empezar a desarrollar la lógica de negocio.

> **Estado actual:** proyecto base. Incluye la página de bienvenida, el modelo `User` y las migraciones estándar de Laravel. Todavía no hay controladores, rutas ni vistas propias del dominio de "cursos/reportes".

---

## Tabla de contenidos

- [Stack tecnológico](#stack-tecnológico)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Ejecución en desarrollo](#ejecución-en-desarrollo)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Base de datos](#base-de-datos)
- [Autenticación](#autenticación)
- [Frontend](#frontend)
- [Reportes en PDF](#reportes-en-pdf)
- [Comandos útiles](#comandos-útiles)
- [Testing](#testing)
- [Próximos pasos sugeridos](#próximos-pasos-sugeridos)

---

## Stack tecnológico

| Componente        | Tecnología                          |
|-------------------|-------------------------------------|
| Framework         | Laravel 12                          |
| Lenguaje          | PHP 8.2+                            |
| Base de datos     | SQLite (por defecto)                |
| Frontend / build  | Vite 7                              |
| CSS               | Tailwind CSS 4                       |
| Reportes PDF      | tc-lib-pdf 8 ([guía](INTEGRAR-TC-LIB.md)) · TCPDF 6 / WrapTcpLib ([guía](INTEGRAR-WRAPTCPLIB.md)) |
| HTTP cliente JS   | Axios                               |
| Cola / Cache / Sesión | driver `database`               |
| REPL              | Laravel Tinker                      |
| Linter            | Laravel Pint                        |
| Tests             | PHPUnit 11                          |

---

## Requisitos

- **PHP** >= 8.2 (con las extensiones habituales de Laravel: `pdo_sqlite`, `mbstring`, `openssl`, etc.)
- **Composer**
- **Node.js** y **npm**
- (Opcional) **XAMPP** — el proyecto vive bajo `c:\eid\Xampp_82\htdocs\desarrollo\`

---

## Instalación

El proyecto define un script `setup` en `composer.json` que automatiza todo el proceso:

```bash
composer setup
```

Este comando ejecuta, en orden:

1. `composer install` — instala dependencias PHP
2. Copia `.env.example` a `.env` (si no existe)
3. `php artisan key:generate` — genera la clave de aplicación
4. `php artisan migrate --force` — corre las migraciones
5. `npm install` — instala dependencias JS
6. `npm run build` — compila los assets

### Instalación manual (paso a paso)

Si preferís hacerlo a mano:

```bash
composer install
copy .env.example .env          # En PowerShell: Copy-Item .env.example .env
php artisan key:generate

# Crear la base SQLite vacía (Windows / PowerShell)
New-Item -ItemType File database\database.sqlite

php artisan migrate
npm install
npm run build
```

---

## Ejecución en desarrollo

La forma recomendada es usar el script `dev`, que levanta **servidor + cola + logs + Vite** en paralelo:

```bash
composer dev
```

Esto ejecuta concurrentemente:

| Proceso | Comando                                      | Para qué                  |
|---------|----------------------------------------------|---------------------------|
| server  | `php artisan serve`                          | Servidor HTTP de Laravel  |
| queue   | `php artisan queue:listen --tries=1`         | Procesa los jobs en cola  |
| logs    | `php artisan pail`                           | Stream de logs en vivo    |
| vite    | `npm run dev`                                | Hot reload de assets      |

La app queda disponible en **http://localhost:8000**.

Si solo necesitás el servidor:

```bash
php artisan serve
npm run dev   # en otra terminal, para los assets
```

---

## Estructura del proyecto

```
Lrvl_curso_reporte/
├── app/
│   ├── Http/Controllers/
│   │   └── Controller.php          # Controlador base abstracto
│   ├── Models/
│   │   └── User.php                # Modelo de usuario (Authenticatable)
│   └── Providers/
│       └── AppServiceProvider.php  # Service provider (vacío por ahora)
├── bootstrap/
│   └── app.php                     # Configuración de la app (rutas, middleware, health check /up)
├── config/                         # Configuración (database, auth, etc.)
├── database/
│   ├── factories/
│   │   └── UserFactory.php         # Factory de usuarios (datos falsos)
│   ├── migrations/                 # Migraciones (users, cache, jobs)
│   ├── seeders/
│   │   └── DatabaseSeeder.php      # Seeder: crea un usuario de prueba
│   └── database.sqlite             # Base SQLite (se crea en la instalación)
├── resources/
│   ├── css/app.css                 # Entrada Tailwind CSS v4
│   ├── js/app.js                   # Entrada JS (Axios)
│   └── views/
│       └── welcome.blade.php       # Página de bienvenida
├── routes/
│   ├── web.php                     # Rutas web (solo "/")
│   └── console.php                 # Comandos de consola (inspire)
├── tests/                          # Tests (PHPUnit)
├── composer.json
├── package.json
└── vite.config.js
```

### Rutas actuales

| Método | URI   | Acción                          | Nombre |
|--------|-------|---------------------------------|--------|
| GET    | `/`   | Closure → vista `welcome`       | —      |
| GET    | `/up` | Health check (Laravel)          | —      |

> Aún no existe `routes/api.php` ni controladores de dominio.

---

## Base de datos

Por defecto el proyecto usa **SQLite** (`database/database.sqlite`), ideal para desarrollo y aprendizaje.

### Tablas creadas por las migraciones

- **users** — `id`, `name`, `email` (único), `email_verified_at`, `password`, `remember_token`, timestamps
- **password_reset_tokens** — `email`, `token`, `created_at`
- **sessions** — `id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`
- **cache** / **cache_locks** — almacenamiento de caché
- **jobs** / **job_batches** / **failed_jobs** — sistema de colas

### Cambiar a MySQL/MariaDB (XAMPP)

Si querés usar MySQL de XAMPP en lugar de SQLite, editá el `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=curso_reporte
DB_USERNAME=root
DB_PASSWORD=
```

Luego creá la base en phpMyAdmin y corré `php artisan migrate`.

### Datos de prueba (seeders)

```bash
php artisan db:seed
```

Crea un usuario de prueba:

- **Email:** `test@example.com`
- **Nombre:** `Test User`

---

## Autenticación

La autenticación está **configurada pero no scaffoldeada**:

- Guard `web` con driver `session` (`config/auth.php`)
- Provider `users` basado en Eloquent (`App\Models\User`)
- Soporte de "recordarme" y reseteo de contraseña (tokens con expiración de 60 min)

La vista `welcome.blade.php` ya referencia las rutas `login`, `register` y `/dashboard`, pero esas rutas todavía no están definidas. Para habilitar login/registro completos podés instalar un starter kit:

```bash
# Opción liviana
composer require laravel/breeze --dev
php artisan breeze:install
```

---

## Frontend

- **Tailwind CSS v4** integrado vía `@tailwindcss/vite` (configurado en `vite.config.js`).
- Entradas de assets: `resources/css/app.css` y `resources/js/app.js`.
- **Axios** preconfigurado en `resources/js/bootstrap.js` con el header `X-Requested-With`.
- Build de producción en `public/build/`.

```bash
npm run dev      # desarrollo con hot reload
npm run build    # build de producción
```

---

## Reportes en PDF

El proyecto integra **dos** librerías de generación de PDF, cada una con su guía:

| Librería | Para qué | Endpoint | Guía |
|----------|----------|----------|------|
| **tc-lib-pdf** 8 | API moderna OO (HTML, tablas) | `/reporte/test` | 📄 [INTEGRAR-TC-LIB.md](INTEGRAR-TC-LIB.md) |
| **TCPDF** 6 + `WrapTcpLib` | Reporteador legacy por YAML (grillas, grupos, cabecera/pie, marca de agua) | `/reporte/cursos` | 📄 [INTEGRAR-WRAPTCPLIB.md](INTEGRAR-WRAPTCPLIB.md) |

> Ambas comparten la constante global `K_PATH_FONTS` con formatos de fuente distintos; por eso **no** se define globalmente sino dentro de cada controlador. Ver detalle en las guías.

Prueba rápida:

```bash
php artisan pdf:import-font   # Solo para tc-lib-pdf (importa una fuente, 1ra vez)
php artisan serve             # Levanta el servidor
```

- **tc-lib-pdf:** http://localhost:8000/reporte/test
- **WrapTcpLib (TCPDF):** http://localhost:8000/reporte/cursos

---

## Comandos útiles

```bash
php artisan migrate              # Correr migraciones
php artisan migrate:fresh --seed # Recrear DB y poblar con seeders
php artisan tinker               # REPL interactivo
php artisan route:list           # Listar todas las rutas
php artisan pail                 # Ver logs en tiempo real
./vendor/bin/pint                # Formatear código (linter)
```

---

## Testing

```bash
composer test
# o directamente:
php artisan test
```

El script `test` limpia la configuración cacheada antes de ejecutar los tests con PHPUnit.

---

## Próximos pasos sugeridos

Como base para un sistema de **reportes de cursos**, los siguientes pasos típicos serían:

1. Definir los modelos del dominio (p. ej. `Curso`, `Alumno`, `Inscripcion`, `Reporte`) con sus migraciones.
2. Crear los controladores y rutas CRUD correspondientes.
3. Construir las vistas Blade con un layout común.
4. Instalar un starter kit de autenticación si se requiere login.
5. Conectar la generación de PDF (ya integrada con tc-lib-pdf, ver [INTEGRAR-TC-LIB.md](INTEGRAR-TC-LIB.md)) a datos reales de la base.

---

_Generado a partir del análisis del código del proyecto._
