<?php $__env->startSection('titulo', 'Reservas'); ?>

<?php $__env->startSection('contenido'); ?>
    <h1>Reservas del <?php echo e($fecha->format('d/m/Y')); ?></h1>
    <p class="subtitulo">
        Agrupadas por seccion y ubicacion. La fecha es la del servicio: una reserva del
        sabado a la noche aparece en el sabado aunque termine el domingo.
    </p>

    <div class="panel">
        <form method="GET" action="<?php echo e(route('reservas.index')); ?>" class="en-linea">
            <div class="campo">
                <label for="fecha">Fecha</label>
                <input id="fecha" type="date" name="fecha" value="<?php echo e($fecha->toDateString()); ?>">
            </div>
            <div class="campo">
                <button type="submit" class="secundario">Ver</button>
            </div>
            <div class="campo" style="margin-left:auto">
                <a class="boton" href="<?php echo e(route('reservas.create', ['fecha' => $fecha->toDateString()])); ?>">Nueva reserva</a>
            </div>
        </form>
        <p class="pie-nota">
            Tambien disponible como JSON:
            <a href="<?php echo e(route('api.reservas', ['fecha' => $fecha->toDateString()])); ?>">/api/reservas?fecha=<?php echo e($fecha->toDateString()); ?></a>
        </p>
    </div>

    <?php $__empty_1 = true; $__currentLoopData = $grupos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $titulo => $reservas): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="panel">
            <h2>
                <?php echo e($titulo); ?>

                <span class="etiqueta"><?php echo e($reservas->count()); ?> reserva(s)</span>
            </h2>
            <table>
                <thead>
                <tr>
                    <th>Horario</th>
                    <th class="numerico">Personas</th>
                    <th>Mesas</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $reservas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reserva): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td>
                            <?php echo e(substr($reserva->starts_at, 11, 5)); ?> a <?php echo e(substr($reserva->ends_at, 11, 5)); ?>

                            <?php if(substr($reserva->starts_at, 0, 10) !== substr($reserva->ends_at, 0, 10)): ?>
                                <span class="etiqueta">(termina el <?php echo e(\Carbon\Carbon::parse($reserva->ends_at)->format('d/m')); ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td class="numerico"><?php echo e($reserva->cantidad_personas); ?></td>
                        <td><?php echo e($reserva->mesas); ?></td>
                        <td style="text-align:right">
                            <form method="POST" action="<?php echo e(route('reservas.destroy', $reserva->reserva_id)); ?>">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button class="peligro chico">Cancelar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="panel">
            <p class="vacio">No hay reservas para esta fecha.</p>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/msi-group-entrevista/resources/views/reservas/index.blade.php ENDPATH**/ ?>