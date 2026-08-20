<?php $__currentLoopData = $ubicaciones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="panel">
        <h2>
            Ubicacion <?php echo e($u['ubicacion']); ?>

            <span class="etiqueta"><?php echo e($u['seccion']); ?> · <?php echo e($u['libres']); ?> de <?php echo e($u['total']); ?> libres</span>
        </h2>
        <div class="grilla-mesas">
            <?php $__currentLoopData = $u['mesas']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mesa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="mesa-estado <?php echo e($mesa['ocupada'] ? 'ocupada' : ''); ?>">
                    <div class="numero">Mesa <?php echo e($mesa['numero']); ?></div>
                    <div class="detalle"><?php echo e($mesa['capacidad']); ?> lugares</div>
                    <div class="detalle"><?php echo e($mesa['ocupada'] ? 'ocupada hasta '.$mesa['hasta'] : 'libre'); ?></div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH /var/www/msi-group-entrevista/resources/views/estado/_ubicaciones.blade.php ENDPATH**/ ?>