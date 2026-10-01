<?php
// index.php - Página principal con el formulario visual de registro
session_start();

// Token anti-CSRF (seguridad en el manejo del formulario)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include __DIR__ . '/includes/header.php';   // Menú + migas de pan
?>

    <main class="container flex-grow-1 py-4">
        <section class="mx-auto" style="max-width: 540px;">
            <h1 class="h4 fw-bold mb-4">Formulario de Registro de Aspirantes</h1>
            <?php include __DIR__ . '/includes/formulario.php'; ?>
        </section>
    </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
