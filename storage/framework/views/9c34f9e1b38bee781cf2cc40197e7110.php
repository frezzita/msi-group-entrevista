<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('titulo', 'Reservas'); ?> · MSI Group</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
</head>
<body>
<?php if(auth()->guard()->check()): ?>
    <header class="barra">
        <div class="barra-interior">
            <a class="marca" href="<?php echo e(route('reservas.index')); ?>">MSI Group</a>
            <nav>
                <a href="<?php echo e(route('mesas.index')); ?>" class="<?php echo e(request()->routeIs('mesas.*') ? 'activo' : ''); ?>">Mesas</a>
                <a href="<?php echo e(route('reservas.index')); ?>" class="<?php echo e(request()->routeIs('reservas.*') ? 'activo' : ''); ?>">Reservas</a>
                <a href="<?php echo e(route('estado.index')); ?>" class="<?php echo e(request()->routeIs('estado.*') ? 'activo' : ''); ?>">Estado</a>
            </nav>
            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button class="secundario chico">Salir</button>
            </form>
        </div>
    </header>
<?php endif; ?>

<main class="contenido">
    <?php if(session('ok')): ?>
        <div class="aviso ok"><?php echo e(session('ok')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="aviso error"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <?php echo $__env->yieldContent('contenido'); ?>
</main>
</body>
</html>
<?php /**PATH /var/www/msi-group-entrevista/resources/views/layouts/app.blade.php ENDPATH**/ ?>