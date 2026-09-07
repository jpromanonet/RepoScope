# RepoScope

Radar de repositorios GitHub para uso local (Home Lab o servidor propio).

Sincroniza **tu** cuenta de GitHub, clasifica cada repo y marca lo que falta: README, licencia, descripción, archivos requeridos y mantenimiento. Incluye panel, inbox de atención, métricas con gráficos y login local.

PHP 8.1+, MySQL/MariaDB, HTML/CSS/JS. Sin Composer ni frameworks.

---

## Requisitos

- PHP 8.1+ con extensiones `pdo_mysql` y `curl`
- MySQL 8 o MariaDB
- Un servidor web (Apache, nginx, XAMPP, etc.) que pueda servir el proyecto

---

## Instalación

1. Cloná o copiá el proyecto a la carpeta que sirva tu servidor web.
2. Copiá `.env.example` → `.env` y completá la base:

```env
APP_NAME=RepoScope
APP_ENV=local
APP_DEBUG=true
APP_URL=

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=reposcope
DB_USER=root
DB_PASS=

GITHUB_TOKEN=
GITHUB_OWNER=
```

`APP_URL` es opcional: dejalo vacío si la app corre en una subcarpeta (detecta la ruta sola). `GITHUB_TOKEN` también es opcional acá; podés cargarlo después en la UI.

3. Abrí `install.php` en el navegador. Crea la base y aplica el único SQL del repo: [`sql/schema.sql`](sql/schema.sql).
4. Entrá a `index.php` con el usuario inicial:

| Campo | Valor |
|-------|--------|
| Mail | `admin@local` |
| Contraseña | `reposcope` |

5. Andá a **Perfil** y cambiá mail y contraseña.

El archivo `.env` no se versiona. No lo subas al remoto.

---

## Cómo usarlo

### 1. Token de GitHub

En **Configuración** está la guía completa. Resumen:

1. Creá un [Personal Access Token (classic)](https://github.com/settings/tokens/new?scopes=repo,read:org&description=RepoScope) con alcances `repo` y `read:org`.
2. Pegalo en Configuración (o en `.env` como `GITHUB_TOKEN`).
3. El token es de **tu usuario**: trae repos que posee, de orgs donde seas miembro (y el token esté autorizado, SSO si aplica) y donde seas colaborador. No trae lo que solo estrellaste ni repos ajenos.

### 2. Sincronizar

En **Sincronizar** → escanear. Primero actualiza metadatos; después revisa archivos de raíz en tandas cortas. Podés seguir usando el resto de la app mientras corre.

Por defecto, forks y archivados se clasifican solos y no generan hallazgos (configurable).

### 3. Pantallas

| Ruta | Para qué |
|------|----------|
| **Panel** | Resumen de salud, hallazgos abiertos, categorías y lenguajes |
| **Repositorios** | Inventario con filtros, ficha, notas y categoría |
| **Atención** | Inbox de hallazgos abiertos por tipo |
| **Métricas** | Barras, líneas y tortas (composición, actividad, sync) |
| **Categorías** | Taxonomía (Personal, Trabajo, Cliente, etc.; podés sumar las tuyas) |
| **Sincronizar** | Sync e historial |
| **Configuración** | Token, orgs extra, archivos requeridos, días de mantenimiento, tema |
| **Perfil** | Nombre, mail y contraseña de la instalación |

Las URLs van por query string: `index.php?r=/ruta` (por ejemplo `index.php?r=/metricas`).

### 4. Hallazgos que mira

- README ausente en la raíz
- Licencia no declarada ni archivo `LICENSE`
- Descripción vacía en GitHub
- Archivo requerido faltante (por defecto `.gitignore`)
- Mantenimiento: sin push hace N días (180 por defecto)

---

## Base de datos

Un solo archivo: [`sql/schema.sql`](sql/schema.sql).

Incluye tablas, usuario inicial, settings por defecto y categorías de sistema. `install.php` lo aplica; si la base ya existe, al arrancar la app completa lo que falte.

---

## Estructura

```text
RepoScope/
├── app/           Controllers, Services, Views, Auth
├── assets/        CSS, JS, icono
├── config/        .env loader, app, database
├── sql/           schema.sql
├── index.php      Front controller
├── install.php    Instalador de la base
├── .env.example   Plantilla de entorno
└── README.md
```

---

## Seguridad (checklist antes de publicar o compartir)

- No subas `.env` (está en `.gitignore`).
- Cambiá la contraseña inicial `reposcope` en **Perfil**.
- No commits de tokens `ghp_…` ni claves de MySQL.
- Pensado para red local / Home Lab; si lo exponés a internet, endurecé HTTPS, firewall y credenciales.
