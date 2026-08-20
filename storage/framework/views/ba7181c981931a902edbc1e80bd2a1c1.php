<?php $__env->startSection('titulo', 'Mesas'); ?>

<?php $__env->startSection('contenido'); ?>
    <h1>Mesas</h1>
    <p class="subtitulo">Alta, baja y modificacion del salon.</p>

    <div class="panel">
        <h2>Nueva mesa</h2>
        <form method="POST" action="<?php echo e(route('mesas.store')); ?>" class="en-linea">
            <?php echo csrf_field(); ?>
            <div class="campo">
                <label for="ubicacion_id">Ubicacion</label>
                <select id="ubicacion_id" name="ubicacion_id" required>
                    <?php $__currentLoopData = $ubicaciones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ubicacion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($ubicacion->id); ?>" <?php if(old('ubicacion_id') == $ubicacion->id): echo 'selected'; endif; ?>>
                            <?php echo e($ubicacion->nombre); ?> — <?php echo e($ubicacion->seccion->nombre); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="campo">
                <label for="numero">Numero</label>
                <input id="numero" type="number" name="numero" min="1" value="<?php echo e(old('numero')); ?>" required>
            </div>
            <div class="campo">
                <label for="capacidad">Cantidad de personas</label>
                <input id="capacidad" type="number" name="capacidad" min="1" value="<?php echo e(old('capacidad')); ?>" required>
            </div>
            <div class="campo">
                <button type="submit">Agregar</button>
            </div>
        </form>

        <?php if($errors->any()): ?>
            <div class="aviso error">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <div><?php echo e($error); ?></div> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php $__currentLoopData = $ubicaciones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ubicacion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="panel">
            <h2>
                Ubicacion <?php echo e($ubicacion->nombre); ?>

                <span class="etiqueta">
                    <?php echo e($ubicacion->seccion->nombre); ?> · <?php echo e($ubicacion->mesas->count()); ?> mesas ·
                    <?php echo e($ubicacion->mesas->sum('capacidad')); ?> lugares
                </span>
            </h2>

            <?php if($ubicacion->mesas->isEmpty()): ?>
                <p class="vacio">Sin mesas cargadas.</p>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Mesa</th>
                        <th class="numerico">Capacidad</th>
                        <th style="width: 170px"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__currentLoopData = $ubicacion->mesas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mesa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($mesa->numero); ?></td>
                            <td class="numerico"><?php echo e($mesa->capacidad); ?></td>
                            <td style="text-align: right">
                                <a class="boton secundario chico" href="<?php echo e(route('mesas.edit', $mesa)); ?>">Editar</a>
                                <form method="POST" action="<?php echo e(route('mesas.destroy', $mesa)); ?>" style="display:inline">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button class="peligro chico">Dar de baja</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/msi-group-entrevista/resources/views/mesas/index.blade.php ENDPATH**/ ?>