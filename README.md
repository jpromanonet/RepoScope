# RepoScope

Radar de repositorios para Home Lab. Sincroniza **tu** cuenta de GitHub, clasifica cada repo y marca lo que falta: README, licencia, descripción, archivos requeridos y mantenimiento.

PHP 8.1+ y MySQL. Sin Composer ni frameworks.

---

## Instalación

1. Copiá el proyecto a donde lo sirva Apache (por ejemplo `W:\reposcope`).
2. Copiá `.env.example` → `.env` y completá la base (`DB_NAME=reposcope`, usuario y clave de MySQL).
3. Abrí `install.php` en el navegador. Aplica el único SQL del repo: `sql/schema.sql`.
4. Entrá con **`admin@local` / `reposcope`**. Cambiá mail y contraseña en **Perfil**.

## Uso

1. **Configuración** — pegá un Personal Access Token de *tu* usuario GitHub (`repo` + `read:org`). Ahí está el paso a paso y el enlace para crearlo.
2. **Sincronizar** — trae repos propios, de orgs donde seas miembro y donde seas colaborador. Los forks y archivados se clasifican solos; por defecto no generan hallazgos.
3. **Panel / Repositorios / Atención** — salud, ficha, notas y categoría.
4. **Métricas** — barras, líneas y tortas de composición (salud, visibilidad, lenguajes, actividad, sync).
5. **Categorías** — Personal, Trabajo, Cliente, Experimento, Aprendizaje, Fork, Archivo; podés sumar las tuyas.

Rutas: `index.php?r=/ruta` (Panel, Repositorios, Atención, Métricas, Categorías, Sincronizar, Configuración, Perfil).

## Base de datos

Un solo archivo: [`sql/schema.sql`](sql/schema.sql). Tablas, usuario inicial y categorías de sistema. `install.php` lo aplica; si la base ya existe, la app completa lo que falte al arrancar.
