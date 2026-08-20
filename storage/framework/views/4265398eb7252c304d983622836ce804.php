<?php $__env->startSection('titulo', 'Nueva reserva'); ?>

<?php $__env->startSection('contenido'); ?>
    <h1>Nueva reserva</h1>
    <p class="subtitulo">La ubicacion y las mesas las asigna el sistema.</p>

    <div class="panel" style="max-width: 520px">
        <form method="POST" action="<?php echo e(route('reservas.store')); ?>">
            <?php echo csrf_field(); ?>

            <div class="campo">
                <label for="fecha">Fecha (noche de servicio)</label>
                <input id="fecha" type="date" name="fecha" value="<?php echo e(old('fecha', $fecha->toDateString())); ?>" required>
                <?php $__errorArgs = ['fecha'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="error-campo"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="campo">
                <label for="hora">Hora</label>
                <select id="hora" name="hora" required>
                    <?php $__currentLoopData = $horarios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $horario): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($horario); ?>" <?php if(old('hora') === $horario): echo 'selected'; endif; ?>><?php echo e($horario); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <span class="pie-nota" id="nota-horarios">
                    Solo se ofrecen horarios en los que la reserva de 2 horas entra completa.
                </span>
                <?php $__errorArgs = ['hora'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="error-campo"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="campo">
                <label for="cantidad_personas">Cantidad de personas</label>
                <input id="cantidad_personas" type="number" name="cantidad_personas" min="1"
                       value="<?php echo e(old('cantidad_personas', 2)); ?>" required>
                <?php $__errorArgs = ['cantidad_personas'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="error-campo"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <button type="submit">Reservar</button>
            <a class="boton secundario" href="<?php echo e(route('reservas.index')); ?>">Volver</a>
        </form>
    </div>

    <script>
        // Los horarios validos dependen del dia: el sabado va de 22:00 a 00:00 y el
        // domingo de 12:00 a 14:00. Al cambiar la fecha se repuebla el selector.
        const fecha = document.getElementById('fecha');
        const hora = document.getElementById('hora');

        fecha.addEventListener('change', async () => {
            const respuesta = await fetch(`<?php echo e(route('api.horarios')); ?>?fecha=${fecha.value}`);
            const datos = await respuesta.json();

            hora.innerHTML = '';
            for (const h of datos.horarios) {
                hora.add(new Option(h, h));
            }
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/msi-group-entrevista/resources/views/reservas/create.blade.php ENDPATH**/ ?>