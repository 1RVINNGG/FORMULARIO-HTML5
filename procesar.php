<?php
// procesar.php - Backend: valida, formatea, guarda la foto y muestra el resultado.
session_start();

// Solo se acepta el envío del formulario por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ---------------------------------------------------------------
// Configuración
// ---------------------------------------------------------------
const EDAD_MIN      = 18;
const EDAD_MAX      = 70;
const TAM_MAX_FOTO  = 2 * 1024 * 1024; // 2 MB
const DIR_FOTOS     = __DIR__ . '/uploaded_files/';
// Extensiones permitidas => tipo MIME real esperado
const TIPOS_FOTO    = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
];

// ---------------------------------------------------------------
// Funciones de saneamiento y normalización
// ---------------------------------------------------------------
function campo(string $nombre): string
{
    $valor = $_POST[$nombre] ?? '';
    return is_string($valor) ? $valor : '';
}

// strip_tags() quita etiquetas HTML/PHP y trim() elimina espacios al inicio y al final
function limpiarTexto(string $texto): string
{
    return trim(strip_tags($texto));
}

// Formato Tipo Título: "sofia" -> "Sofia", "MARÍA josé" -> "María José".
// Equivale a ucwords(strtolower($texto)), pero con soporte de tildes y ñ (UTF-8) cuando mbstring está activo.
function formatoTitulo(string $texto): string
{
    $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;
    if (function_exists('mb_convert_case')) {
        return mb_convert_case($texto, MB_CASE_TITLE, 'UTF-8');
    }
    return ucwords(strtolower($texto));
}

// Escapa la salida para evitar XSS
function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------------
// Recepción y saneamiento de datos
// ---------------------------------------------------------------
$errores = [];

// Token anti-CSRF
$tokenForm = campo('csrf_token');
if ($tokenForm === '' || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $tokenForm)) {
    $errores[] = 'La sesión del formulario expiró o es inválida. Vuelva a cargar el formulario e intente de nuevo.';
}

$nombre         = formatoTitulo(limpiarTexto(campo('nombre')));
$apellido       = formatoTitulo(limpiarTexto(campo('apellido')));
$identificacion = strtoupper(limpiarTexto(campo('identificacion')));
$fechaTxt       = limpiarTexto(campo('fecha_nacimiento'));
$sexo           = limpiarTexto(campo('sexo'));

// ---------------------------------------------------------------
// Validaciones
// ---------------------------------------------------------------
// Nombre y apellido: requeridos, solo letras, espacios, apóstrofes, puntos y guiones
$patronNombre = "/^[\p{L}][\p{L}\s'.\-]{0,49}$/u";
if ($nombre === '') {
    $errores[] = 'El nombre es obligatorio.';
} elseif (!preg_match($patronNombre, $nombre)) {
    $errores[] = 'El nombre solo puede contener letras (máximo 50 caracteres).';
}

if ($apellido === '') {
    $errores[] = 'El apellido es obligatorio.';
} elseif (!preg_match($patronNombre, $apellido)) {
    $errores[] = 'El apellido solo puede contener letras (máximo 50 caracteres).';
}

// Identificación: letras, números y guiones (ej. 8-123-456, PE-1-234)
if ($identificacion === '') {
    $errores[] = 'La identificación es obligatoria.';
} elseif (!preg_match('/^[A-Z0-9]+(-[A-Z0-9]+)*$/', $identificacion) || strlen($identificacion) < 5 || strlen($identificacion) > 20) {
    $errores[] = 'La identificación no es válida (use letras, números y guiones, entre 5 y 20 caracteres. Ej: 8-123-456).';
}

// Fecha de nacimiento y edad entre 18 y 70 años
$edad = null;
if ($fechaTxt === '') {
    $errores[] = 'La fecha de nacimiento es obligatoria.';
} else {
    $fecha = DateTime::createFromFormat('!Y-m-d', $fechaTxt);
    if ($fecha === false || $fecha->format('Y-m-d') !== $fechaTxt) {
        $errores[] = 'La fecha de nacimiento no tiene un formato válido.';
    } else {
        $hoy = new DateTime('today');
        if ($fecha > $hoy) {
            $errores[] = 'La fecha de nacimiento no puede ser futura.';
        } else {
            $edad = $fecha->diff($hoy)->y;
            if ($edad < EDAD_MIN || $edad > EDAD_MAX) {
                $errores[] = 'La edad debe estar entre ' . EDAD_MIN . ' y ' . EDAD_MAX . ' años (edad calculada: ' . $edad . ').';
            }
        }
    }
}

// Sexo: solo valores permitidos
if ($sexo === '') {
    $errores[] = 'Debe seleccionar el sexo.';
} elseif (!in_array($sexo, ['Hombre', 'Mujer'], true)) {
    $errores[] = 'El valor de sexo no es válido.';
}

// Fotografía
$foto = $_FILES['foto'] ?? null;
$extension = '';
if ($foto === null || !isset($foto['error']) || is_array($foto['error']) || $foto['error'] === UPLOAD_ERR_NO_FILE) {
    $errores[] = 'Debe seleccionar una fotografía.';
} elseif ($foto['error'] === UPLOAD_ERR_INI_SIZE || $foto['error'] === UPLOAD_ERR_FORM_SIZE || $foto['size'] > TAM_MAX_FOTO) {
    $errores[] = 'La fotografía supera el tamaño máximo permitido (2 MB).';
} elseif ($foto['error'] !== UPLOAD_ERR_OK) {
    $errores[] = 'Ocurrió un error al subir la fotografía. Intente de nuevo.';
} else {
    $extension = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
    if (!array_key_exists($extension, TIPOS_FOTO)) {
        $errores[] = 'Extensión no permitida. Solo se aceptan: ' . implode(', ', array_keys(TIPOS_FOTO)) . '.';
    } else {
        // Se verifica el contenido real del archivo, no solo su extensión
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($foto['tmp_name']);
        if ($mimeReal !== TIPOS_FOTO[$extension] || @getimagesize($foto['tmp_name']) === false) {
            $errores[] = 'El archivo no es una imagen válida.';
        }
    }
}

// ---------------------------------------------------------------
// Guardado seguro de la foto (solo si no hubo errores)
// ---------------------------------------------------------------
$rutaFoto = '';
$nombreFoto = '';
if (empty($errores)) {
    if (!is_dir(DIR_FOTOS) && !mkdir(DIR_FOTOS, 0755, true)) {
        $errores[] = 'No se pudo crear la carpeta de fotografías.';
    } elseif (!is_writable(DIR_FOTOS)) {
        $errores[] = 'La carpeta de fotografías no tiene permisos de escritura.';
    } else {
        // Nombre aleatorio: evita colisiones, sobrescritura y ataques de path traversal
        $nombreFoto = bin2hex(random_bytes(16)) . '.' . $extension;
        $rutaFoto = DIR_FOTOS . $nombreFoto;
        if (!move_uploaded_file($foto['tmp_name'], $rutaFoto)) {
            $errores[] = 'No se pudo guardar la fotografía en el servidor.';
        } else {
            chmod($rutaFoto, 0644);
            unset($_SESSION['csrf_token']); // el token es de un solo uso
        }
    }
}

// Como la carpeta uploaded_files/ está bloqueada al navegador, la foto se muestra incrustada (base64)
$fotoDataUri = '';
if (empty($errores) && is_file($rutaFoto)) {
    $fotoDataUri = 'data:' . TIPOS_FOTO[$extension] . ';base64,' . base64_encode(file_get_contents($rutaFoto));
}

include __DIR__ . '/includes/header.php';   // Menú + migas de pan
?>

    <main class="container flex-grow-1 py-4">
        <section class="mx-auto" style="max-width: 640px;">

        <?php if (!empty($errores)): ?>
            <h1 class="h4 fw-bold mb-3">No se pudo registrar al aspirante</h1>
            <div class="alert alert-danger" role="alert">
                <p class="fw-semibold mb-2">Corrija los siguientes errores:</p>
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <a href="index.php" class="btn btn-secondary">Volver al formulario</a>

        <?php else: ?>
            <h1 class="h4 fw-bold mb-3">Aspirante registrado correctamente</h1>
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-4 text-center">
                            <img src="<?php echo $fotoDataUri; ?>" alt="Fotografía de <?php echo e($nombre . ' ' . $apellido); ?>"
                                 class="img-fluid rounded border" style="max-height: 220px;">
                        </div>
                        <div class="col-md-8">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Nombre</dt>
                                <dd class="col-sm-7"><?php echo e($nombre); ?></dd>

                                <dt class="col-sm-5">Apellido</dt>
                                <dd class="col-sm-7"><?php echo e($apellido); ?></dd>

                                <dt class="col-sm-5">Identificación</dt>
                                <dd class="col-sm-7"><?php echo e($identificacion); ?></dd>

                                <dt class="col-sm-5">Fecha de nacimiento</dt>
                                <dd class="col-sm-7"><?php echo e($fecha->format('d/m/Y')); ?></dd>

                                <dt class="col-sm-5">Edad</dt>
                                <dd class="col-sm-7"><?php echo (int) $edad; ?> años</dd>

                                <dt class="col-sm-5">Sexo</dt>
                                <dd class="col-sm-7"><?php echo e($sexo); ?></dd>

                                <dt class="col-sm-5">Foto guardada como</dt>
                                <dd class="col-sm-7 text-break small">uploaded_files/<?php echo e($nombreFoto); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <a href="index.php" class="btn btn-primary mt-3">Registrar otro aspirante</a>
        <?php endif; ?>

        </section>
    </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
