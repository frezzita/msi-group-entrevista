# MSI Group — Sistema de reservas

Evaluación técnica: sistema de reservas de restaurante en Laravel 13.

El foco está en los puntos 3 y 4 del enunciado (asignación de mesas con caché de
disponibilidad, y listado por fecha en una sola consulta). El ABM de mesas, la
autenticación y las pantallas existen para poder probar esa lógica de punta a punta.

---

## Requisitos

- PHP **8.3** o superior (mínimo exigido por Laravel 13)
- MySQL 8
- Composer 2

**No hace falta Node ni npm**: el proyecto no tiene paso de compilación de assets.
Redis es opcional (ver *Caché* más abajo).

---

## Puesta en marcha

```bash
mysql -u root -p -e "
  CREATE DATABASE msi_reservas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE DATABASE msi_reservas_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'msi'@'localhost' IDENTIFIED BY 'msi';
  GRANT ALL PRIVILEGES ON msi_reservas.* TO 'msi'@'localhost';
  GRANT ALL PRIVILEGES ON msi_reservas_test.* TO 'msi'@'localhost';"
```

```bash
composer install && cp .env.example .env && php artisan key:generate
```

```bash
php artisan migrate --seed && php artisan serve
```

La aplicación queda en <http://localhost:8000>.

**Usuario de prueba:** `demo@msigroup.test` / `password`

Si tus credenciales de MySQL son otras, ajustá `DB_USERNAME` y `DB_PASSWORD` en `.env`.

### Caché

`.env.example` viene con `CACHE_STORE=redis` porque el enunciado pide *caché en
memoria*. Todo pasa por el facade `Cache`, así que si no tenés Redis a mano, poner
`CACHE_STORE=database` o `CACHE_STORE=file` funciona idéntico sin tocar una línea de
código.

### Tests

```bash
php artisan test
```

100 tests. Corren contra **MySQL** (`msi_reservas_test`), no contra SQLite: la consulta
del punto 4 usa `GROUP_CONCAT(... ORDER BY ... SEPARATOR ...)`, el modo
`ONLY_FULL_GROUP_BY` y `SELECT ... FOR UPDATE`, que en SQLite no existen o se comportan
distinto. Testear sobre otro motor haría que los dos tests más importantes no probaran
nada real.

---

## Qué hay en cada pantalla

| Pantalla | Qué muestra |
|---|---|
| **Mesas** | ABM por ubicación: alta, edición y baja con su capacidad (punto 1) |
| **Reservas** | Alta de reserva y listado por fecha agrupado por ubicación (puntos 3 y 4) |
| **Estado** | Ocupación del salón en este momento, refrescada sola cada 15 segundos |

El listado del punto 4 también está como JSON en `/api/reservas?fecha=YYYY-MM-DD`.

---

## Reglas de negocio implementadas

- **Horarios**: L-V de 10 a 24, sábado de 22 a 2AM, domingo de 12 a 16.
- **Duración fija de 2 horas** por reserva.
- **Anticipación mínima de 15 minutos** respecto del horario pedido.
- **La ubicación la asigna el sistema**, probando las zonas en orden A → B → C → D y
  quedándose con la primera que tenga lugar. Acá va a depender cómo esté seteado el config de reservas. Ahora tiene como default que busque una mesa que coincida con los comensales exactamente mirando por zona y sino encuentra sí la que mesa que tenga menos desperdicio de lugares.
- **Unión de hasta 3 mesas** dentro de la misma ubicación cuando ninguna mesa sola
  alcanza.
- **Caché de la disponibilidad por ubicación**, invalidada al confirmar una reserva.

---

## Supuestos

El enunciado deja varias cosas sin definir. Estas son las decisiones que se tomaron y
por qué; el detalle técnico completo está en [STACK.md](STACK.md).

1. **El listado del punto 4 va solo por ubicación.** El enunciado pedía originalmente
   listar *"por ubicación y sección"*, pero la mesa nunca tuvo un campo `sección` propio
   (solo `ubicación`, A–D), así que en una primera versión se modeló `secciones →
   ubicaciones → mesas` como una dimensión extra de reporte. El cliente confirmó que
   sección y ubicación son sinónimos en este negocio, así que se eliminó por completo:
   la tabla `secciones`, el modelo y toda referencia en el listado, en Mesas y en Estado.

2. **Unir mesas no descuenta asientos, por defecto.** El enunciado permite unir mesas
   pero no dice si se pierden lugares en los lados que quedan pegados. En un restaurante
   real dos mesas de 4 unidas dan 6 lugares útiles, no 8. Como es un supuesto y no un
   requisito, está implementado como parámetro
   (`config/reservas.php → asientos_perdidos_por_union`) con **default 0**: se cumple la
   letra del enunciado y basta cambiar el valor a 2 para el comportamiento realista.

3. **Una reserva tiene que entrar completa dentro del horario.** Duración fija de 2
   horas más un horario de cierre obliga a elegir: el último horario reservable es
   `cierre − 2 h`, o sea **22:00 de lunes a viernes, 00:00 el sábado y 14:00 el
   domingo**. La alternativa (aceptar las 23:30 de un martes y ocupar la mesa hasta la
   01:30 con el local cerrado) no se sostiene. El mensaje de error explica la regla en
   vez de limitarse a negar.

4. **La fecha del formulario es la fecha de servicio, no la del calendario.** El sábado
   va de 22:00 a 02:00: es un bloque continuo que el enunciado llama "sábado". Una
   reserva del sábado a las 23:30 termina el domingo a la 01:30 pero **pertenece al
   sábado**, y aparece en el listado del sábado. Por eso `reservas` guarda
   `starts_at`/`ends_at` como datetimes completos más una columna `fecha_servicio` con
   el día de negocio.

5. **Los intervalos que se tocan en el borde no se pisan.** Una reserva que termina a
   las 12:00 no bloquea otra que empieza a las 12:00 en la misma mesa.

6. **Dentro de una ubicación se elige por: menos mesas → menor desperdicio → menor
   número de mesa.** El enunciado fija el orden entre ubicaciones pero no dentro de una.
   Con este criterio una mesa sola siempre le gana a una unión, no se sienta a 2 personas
   en la mesa de 10, y el resultado es determinístico (los tests son reproducibles).

7. **Entre ubicaciones se prefiere la que no desperdicia asientos.** El enunciado dice
   que *"la ubicación la debe definir el sistema (por orden)"*, pero no define qué hacer
   cuando la primera zona con lugar sólo puede ofrecer una mesa más grande de la
   necesaria y una zona posterior tiene una exacta.

   El costo de la lectura literal es medible y encadenado: si A sólo tiene libre una mesa
   de 4 y B una de 2, un grupo de 2 ocupa la de 4, y el grupo de 4 que llega después
   termina en una mesa de 6 — **4 asientos desperdiciados en dos reservas, contra 0** si
   el grupo de 2 hubiera ido a B.

   Por eso el comportamiento por defecto recorre las ubicaciones **en orden** buscando
   una que no desperdicie asientos, y sólo si ninguna puede vuelve a la primera que tenía
   lugar (que es exactamente la respuesta de la lectura literal). Las dos estrategias
   están implementadas y se cambian por configuración
   (`config/reservas.php → estrategia_asignacion`, o `RESERVAS_ESTRATEGIA_ASIGNACION`):

   | Valor | Comportamiento |
   |---|---|
   | `ajuste_exacto_primero` *(default)* | Se prefiere la ubicación que no desperdicia asientos, recorriendo en orden |
   | `orden_estricto` | Gana la primera ubicación con lugar, sin mirar las siguientes |

   El argumento a favor de `orden_estricto` está en el código y no se descartó por
   capricho: el modelo no representa el costo de **abrir una zona**, y en un local real
   cada zona abierta necesita personal propio. `EstrategiaAsignacionTest` mide el
   desperdicio de ambas sobre el mismo escenario.

8. **Todas las mesas de una ubicación se consideran combinables.** El enunciado no da
   ninguna noción de posición o layout, así que no hay forma de determinar adyacencia
   física. Es una simplificación consciente.

9. **Un solo perfil de usuario.** No se pidieron roles. Como el punto 4 requiere ver
   *todas* las reservas del día, el usuario autenticado se modela como personal del
   local. `reservas.user_id` se guarda como trazabilidad de quién cargó la reserva, no
   como dueño. Agregar un rol cliente sería una policy sobre `Reserva` y un scope en el
   listado.

10. **La baja de mesas es lógica y se bloquea si hay reservas por venir.** Un `DELETE`
   con cascada haría desaparecer mesas de reservas históricas y el listado del punto 4
   empezaría a mentir hacia atrás. El índice único de `(ubicacion_id, numero)` incluye
   `deleted_at`, así que un número liberado se puede reutilizar.

11. **Editar la capacidad de una mesa no revalida las reservas ya asignadas.** Bajar la
    capacidad de una mesa no expulsa a un grupo que ya tenía lugar.

12. **Zona horaria `America/Argentina/Buenos_Aires`.** Sin esto, la anticipación de 15
    minutos y la pantalla de estado quedan corridas respecto de la hora local.

13. **Sin *broadcasting*.** El enunciado no pide tiempo real; sólo caché. La pantalla de
    estado se refresca con polling cada 15 segundos, lo que evita depender de un proceso
    extra y de compilar assets. El evento `ReservaCreada` se emite igual y está
    documentado como punto de enganche para Reverb.

---

## Punto 4: listado por fecha en una sola consulta

```sql
SELECT r.id AS reserva_id, r.starts_at, r.ends_at, r.cantidad_personas,
       u.nombre AS ubicacion,
       GROUP_CONCAT(m.numero ORDER BY m.numero SEPARATOR ', ') AS mesas
FROM reservas r
JOIN ubicaciones u   ON u.id = r.ubicacion_id
JOIN mesa_reserva mr ON mr.reserva_id = r.id
JOIN mesas m         ON m.id = mr.mesa_id
WHERE r.fecha_servicio = ? AND r.deleted_at IS NULL
GROUP BY r.id, r.starts_at, r.ends_at, r.cantidad_personas, u.nombre, u.orden
ORDER BY u.orden, r.starts_at;
```

Está en [`app/Queries/ReservasPorFechaQuery.php`](app/Queries/ReservasPorFechaQuery.php),
resuelta con el query builder y no con Eloquent: `Reserva::with('mesas')` son dos
consultas como mínimo, y acceder a las mesas por relación dentro de la vista sería N+1.
Con el join contra el pivote más `GROUP_CONCAT`, cada reserva vuelve en **una sola fila**
con sus mesas ya concatenadas y ordenadas, en un único viaje a la base. El test
`ListadoPorFechaTest` lo fija con `expectsDatabaseQueryCount(1)`.

### EXPLAIN

```
+----+-------+--------+--------------------------------------------+---------+------+----------------------------------------------+
| id | table | type   | key                                        | key_len | rows | Extra                                        |
+----+-------+--------+--------------------------------------------+---------+------+----------------------------------------------+
|  1 | r     | ref    | reservas_fecha_servicio_ubicacion_id_index | 3       |    2 | Using where; Using temporary; Using filesort |
|  1 | u     | eq_ref | PRIMARY                                    | 8       |    1 | NULL                                         |
|  1 | mr    | ref    | mesa_reserva_reserva_id_index              | 8       |   10 | Using index                                  |
|  1 | m     | eq_ref | PRIMARY                                    | 8       |    1 | NULL                                         |
+----+-------+--------+--------------------------------------------+---------+------+----------------------------------------------+
```

`reservas` entra por el índice `(fecha_servicio, ubicacion_id)` y el resto de los joins
son `eq_ref` por clave primaria, con el pivote resuelto por índice cubridor. No hay
ningún scan completo de tabla.

---

## Fuera de alcance

Edición de reservas · roles y permisos diferenciados · *broadcasting* en tiempo real ·
adyacencia física entre mesas · política de *no-show* o confirmación de asistencia.
