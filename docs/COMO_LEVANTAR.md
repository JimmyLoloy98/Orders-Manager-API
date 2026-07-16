# Cómo levantar Orders Manager API

API REST en **Laravel 12** para gestión de pedidos de restaurante (mesas, menú, órdenes, dashboard). Autenticación con **Laravel Sanctum**.

---

## Requisitos

| Herramienta | Versión mínima |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| MySQL | 5.7+ |
| Node.js + npm | 18+ (solo si usas assets frontend o `composer dev`) |

Extensiones PHP recomendadas: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`.

---

## Instalación (primera vez)

### 1. Clonar e instalar dependencias

```bash
cd Orders-Manager-API
composer install
npm install
```

### 2. Configurar entorno

Copia el archivo de ejemplo y genera la clave de la aplicación:

**Linux / macOS / Git Bash**
```bash
cp .env.example .env
php artisan key:generate
```

**Windows (PowerShell)**
```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Edita `.env` con tu base de datos:

```env
APP_NAME="Orders Manager"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=orders_manager
DB_USERNAME=root
DB_PASSWORD=tu_password
```

Crea la base de datos en MySQL antes de migrar:

```sql
CREATE DATABASE orders_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Migrar y cargar datos de prueba

```bash
php artisan migrate --seed
```

El seeder crea:

| Usuario | Contraseña | Rol |
|---|---|---|
| `admin` | `admin123` | admin |
| `mozo` | `mozo123` | mozo |

También crea **10 mesas** (`Mesa 1` … `Mesa 10`) y **3 categorías de menú** (Comidas, Bebidas, Extras).

### 4. (Opcional) Documentación Swagger

```bash
php artisan l5-swagger:generate
```

---

## Levantar el servidor

### Opción A — Solo API (rápido)

```bash
php artisan serve
```

La API queda en: **http://localhost:8000/api/v1**

### Opción B — Entorno de desarrollo completo

Levanta servidor, cola, logs y Vite en paralelo:

```bash
composer dev
```

### Opción C — Setup automático (instalación + build)

Ejecuta dependencias, `.env`, migraciones y build de assets:

```bash
composer setup
```

Luego inicia con `php artisan serve` o `composer dev`.

---

## Verificar que funciona

### Health check (sin autenticación)

```bash
curl http://localhost:8000/api/v1/health
curl http://localhost:8000/up
```

### Login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"username\":\"admin\",\"password\":\"admin123\"}"
```

En Linux/macOS reemplaza `^` por `\` al final de cada línea.

Respuesta esperada: `user` + `token`. Usa el token en rutas protegidas:

```
Authorization: Bearer {token}
```

### Ejemplo de endpoint protegido

```bash
curl http://localhost:8000/api/v1/dashboard/stats ^
  -H "Authorization: Bearer TU_TOKEN" ^
  -H "Accept: application/json"
```

---

## URLs útiles

| Recurso | URL |
|---|---|
| API base | http://localhost:8000/api/v1 |
| Swagger UI | http://localhost:8000/api/docs |
| Health | http://localhost:8000/api/v1/health |
| Laravel health | http://localhost:8000/up |

Documentación detallada de endpoints: [`docs/PEDIDOS_PANCHITO_API.md`](./PEDIDOS_PANCHITO_API.md)

---

## Comandos útiles

```bash
# Limpiar caché de configuración
php artisan config:clear

# Re-ejecutar seeders (borra datos previos si usas migrate:fresh)
php artisan migrate:fresh --seed

# Regenerar docs OpenAPI
php artisan l5-swagger:generate

# Limpiar sesiones de mozo de días anteriores
php artisan mozo:cleanup-sessions

# Ejecutar tests
composer test
```

---

## Estructura de la API

Prefijo: `/api/v1`

- **Públicos:** `GET /health`, `POST /auth/login`
- **Protegidos (Bearer token):** mesas, pedidos, menú, dashboard, usuarios, logout

Login usa **`username`** y **`password`** (no email).

---

## Solución de problemas

| Problema | Qué revisar |
|---|---|
| `SQLSTATE[HY000] [1049] Unknown database` | Crear la BD en MySQL y revisar `DB_DATABASE` en `.env` |
| `No application encryption key` | Ejecutar `php artisan key:generate` |
| `Class ... not found` | Ejecutar `composer install` |
| Error 401 en endpoints | Incluir header `Authorization: Bearer {token}` |
| Swagger vacío o desactualizado | `php artisan l5-swagger:generate` |
| CORS desde frontend | Ya permite `*` en `config/cors.php` para rutas `api/*` |

---

## Notas

- El proyecto usa **MySQL** por defecto (`SESSION_DRIVER`, `QUEUE_CONNECTION` y `CACHE_STORE` apuntan a `database`).
- Si solo consumes la API desde Postman o un frontend externo, no necesitas `npm run dev`; basta con `php artisan serve`.
- El archivo `.env` no debe subirse a git; usa `.env.example` como referencia.
