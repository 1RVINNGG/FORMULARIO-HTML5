# Taller Aspirantes — Registro de Aspirantes (Laboratorio #3)

Sistema web de registro de aspirantes desarrollado con **HTML5, Bootstrap 5.3.8 y PHP**.
Valida los datos en el servidor, normaliza los textos y guarda la fotografía del aspirante en una carpeta protegida, **sin usar base de datos**.

- **Universidad:** Universidad Tecnológica de Panamá — Facultad de Ingeniería de Sistemas Computacionales
- **Curso:** Desarrollo Web — Módulo II (Diseño Web con HTML5 y CSS3) y Módulo III (Programación de Aplicaciones Web)
- **Laboratorio:** #3 — Elaborado por Ing. Irina Fong (asignado el 18/09/2026, entrega 02/10/2026)
- **Estudiante:** Irving

## Características

- Maquetación con Bootstrap y etiquetas semánticas: `<header>`, `<main>`, `<section>`, `<footer>`.
- El formulario va dentro de `<main><section>`.
- Modularización con `include`: navegación + migas de pan (`header.php`), pie de página (`footer.php`) y formulario (`formulario.php`).
- Migas de pan dinámicas usando `basename($_SERVER['PHP_SELF'])`.
- Metadatos: `charset`, `viewport`, `description`, `author`, `robots` y `theme-color`.
- Footer con copyright de año dinámico (`date('Y')`), redes/contacto, enlaces rápidos y eslogan institucional.

## Estructura de carpetas

```
Taller-Aspirantes/
├── includes/
│   ├── header.php        # <header>, Navbar y Breadcrumb dinámico
│   ├── footer.php        # <footer> con enlaces y año dinámico
│   └── formulario.php    # Formulario de registro
├── uploaded_files/
│   ├── .gitkeep          # Mantiene la carpeta en Git
│   └── .htaccess         # Bloquea el acceso desde el navegador
├── index.php             # Página principal con el formulario
├── procesar.php          # Backend: valida, formatea, guarda y muestra el resultado
├── README.md
└── .gitignore
```

## Requisitos

- PHP 8.0 o superior (extensiones `mbstring` y `fileinfo` activas)
- Apache (WAMP, XAMPP o similar) con `AllowOverride All` para respetar el `.htaccess`
- Conexión a internet para cargar Bootstrap y Bootstrap Icons desde CDN

## Instalación y uso

1. Copie la carpeta `Taller-Aspirantes/` dentro del directorio web del servidor (por ejemplo `C:\wamp64\www\`).
2. Verifique que `uploaded_files/` tenga permisos de escritura.
3. Inicie Apache y abra `http://localhost/Taller-Aspirantes/`.
4. Complete el formulario y presione **Registrar Aspirante**.

## Validaciones (procesar.php)

| Campo | Regla |
|---|---|
| Nombre / Apellido | Requeridos, solo letras; se normalizan a Formato Tipo Título (`sofia` → `Sofia`) |
| Identificación | Requerida; letras, números y guiones; se convierte a mayúsculas con `strtoupper()` |
| Fecha de nacimiento | Requerida, fecha válida y no futura; la **edad calculada debe estar entre 18 y 70 años** |
| Sexo | Requerido; solo `Hombre` o `Mujer` |
| Fotografía | Requerida; extensiones `jpg`, `jpeg`, `png`, `gif`, `webp`; máximo 2 MB; se verifica el tipo MIME real |

## Seguridad

- `trim()`, `strip_tags()` y `htmlspecialchars()` para sanear la entrada y escapar la salida (previene XSS).
- Token CSRF de un solo uso en el formulario.
- La foto se guarda con **nombre aleatorio** en `uploaded_files/` (evita colisiones y path traversal).
- `uploaded_files/.htaccess` deniega todo acceso desde el navegador (`Require all denied`) y desactiva el listado de directorio.
  La foto se muestra en la página de resultado incrustada en base64, leída por PHP desde el servidor.
- Nota: si se usa Nginx, bloquee la carpeta con `location /uploaded_files/ { deny all; }`.

## Repositorio

```bash
git init
git add .
git commit -m "Laboratorio #3: Registro de aspirantes con PHP y Bootstrap"
```

La carpeta `uploaded_files/` se versiona vacía (`.gitkeep`); las fotos subidas están excluidas por `.gitignore`.
