<?php
// includes/formulario.php - Formulario de registro de aspirantes (se incluye dentro de <main><section>)
// Requiere que index.php haya iniciado la sesión y creado $_SESSION['csrf_token'].
?>
<form action="procesar.php" method="POST" enctype="multipart/form-data" class="card shadow-sm border-0 p-4" novalidate>
    <!-- Token anti-CSRF -->
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

    <div class="mb-3">
        <label for="nombre" class="form-label fw-semibold">Nombre (Requerido):</label>
        <input type="text" class="form-control" id="nombre" name="nombre"
               placeholder="Ej: Sofía" maxlength="50" required>
    </div>

    <div class="mb-3">
        <label for="apellido" class="form-label fw-semibold">Apellido (Requerido):</label>
        <input type="text" class="form-control" id="apellido" name="apellido"
               placeholder="Ej: Ramírez" maxlength="50" required>
    </div>

    <div class="mb-3">
        <label for="identificacion" class="form-label fw-semibold">Identificación (Requerido):</label>
        <input type="text" class="form-control" id="identificacion" name="identificacion"
               placeholder="Ej: 8-123-456" maxlength="20" required>
    </div>

    <div class="mb-3">
        <label for="fecha_nacimiento" class="form-label fw-semibold">Fecha de Nacimiento (Requerido):</label>
        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
               max="<?php echo date('Y-m-d'); ?>" required>
    </div>

    <div class="mb-3">
        <span class="form-label fw-semibold d-block">Sexo (Requerido):</span>
        <div class="row g-2">
            <div class="col-6">
                <input type="radio" class="btn-check" name="sexo" id="sexoHombre" value="Hombre" autocomplete="off" required>
                <label class="btn btn-outline-secondary w-100" for="sexoHombre">Hombre</label>
            </div>
            <div class="col-6">
                <input type="radio" class="btn-check" name="sexo" id="sexoMujer" value="Mujer" autocomplete="off" required>
                <label class="btn btn-outline-secondary w-100" for="sexoMujer">Mujer</label>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <label for="foto" class="form-label fw-semibold">Fotografía del Aspirante (png, jpg, jpeg, gif):</label>
        <input type="file" class="form-control" id="foto" name="foto"
               accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" required>
    </div>

    <button type="submit" class="btn btn-primary w-100">Registrar Aspirante</button>
</form>
