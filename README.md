# RepoScope

**Radar de repositorios.** Sincroniza GitHub, clasifica cada repo y marca los que necesitan README, licencia, descripción, un archivo requerido o mantenimiento.

Pensado para Home Lab: PHP + MySQL, sin frameworks de frontend ni Composer.

---

## Qué hace (v0.1)

- **Sincronizar** la cuenta (y orgs extra) con un Personal Access Token.
- **Clasificar** por categoría (Personal, Trabajo, Cliente, Experimento, Aprendizaje, Fork, Archivo) más notas.
- **Detectar** hallazgos:
  - **README** ausente en la raíz
  - **Licencia** no declarada ni archivo `LICENSE`
  - **Descripción** vacía en GitHub
  - **Archivo** requerido faltante (por defecto `.gitignore`, configurable)
  - **Mantenimiento**: sin push hace N días (180 por defecto)
- Panel de salud, **métricas**, inbox de atención y ficha por repositorio.

Los forks y archivados se clasifican solos y, por defecto, no generan hallazgos.

---

## Stack

| Capa | Tecnología |
|------|------------|
| Backend | PHP 8.1+ |
| Base | MySQL 8 / MariaDB |
| Front | HTML, CSS, JavaScript (sin frameworks) |
| Origen | GitHub REST API |

## Requisitos

- PHP con `pdo_mysql` y `curl`
- MySQL en marcha
- Apache (o el servidor que ya sirve `W:\`) — rutas vía `index.php?r=/ruta`

---

## Instalación

1. Copiá el proyecto a `W:\reposcope` (o servilo desde el repo).
2. Copiá `.env.example` → `.env` y ajustá la base:

```env
APP_NAME=RepoScope
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=reposcope
DB_USER=root
DB_PASS=
GITHUB_TOKEN=
```

3. Abrí `install.php` en el navegador.
4. Entrá a `index.php` → **Configuración**. Ahí está el paso a paso para crear un Personal Access Token.

---

## Token de GitHub

El token es de **tu usuario**, no de “todo GitHub”. Con alcance `repo` (+ `read:org` recomendado) RepoScope sincroniza:

- Repos que posee esa cuenta (públicos y privados, incluidos forks)
- Repos de organizaciones donde seas miembro y el token esté autorizado (SSO si aplica)
- Repos donde te hayan puesto como colaborador

No trae lo que solo estrellaste ni repos ajenos. Guía completa en la pantalla **Configuración**, con enlace directo a [crear el token](https://github.com/settings/tokens/new?scopes=repo,read:org&description=RepoScope).

---

## Navegación

```text
Panel · Repositorios · Atención · Métricas · Categorías · Sincronizar · Configuración
```

---

## Estructura

```text
RepoScope/
├── app/            Controllers, Services, Views
├── assets/         CSS, JS, icono
├── config/         .env loader, app, database
├── sql/            schema.sql
├── index.php
└── install.php
```

Proyecto personal. Una sola instalación en casa.
