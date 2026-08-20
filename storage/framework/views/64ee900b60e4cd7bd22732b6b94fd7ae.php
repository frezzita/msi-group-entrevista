<?php $__env->startSection('titulo', 'Ingresar'); ?>

<?php $__env->startSection('contenido'); ?>
    <div class="centrado">
        <h1>MSI Group</h1>
        <p class="subtitulo">Sistema de reservas</p>

        <div class="panel">
            <form method="POST" action="<?php echo e(route('login.store')); ?>">
                <?php echo csrf_field(); ?>

                <div class="campo">
                    <label for="email">Correo</label>
                    <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus>
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="error-campo"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="campo">
                    <label for="password">Contrasena</label>
                    <input id="password" type="password" name="password" required>
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="error-campo"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <button type="submit">Ingresar</button>
            </form>
        </div>

        <p class="pie-nota">Sin cuenta todavia? <a href="<?php echo e(route('register')); ?>">Crear una</a></p>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/msi-group-entrevista/resources/views/auth/login.blade.php ENDPATH**/ ?>