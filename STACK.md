# Arquitectura y decisiones

Documento de las decisiones técnicas del proyecto y de por qué se tomaron: qué problema
resolvía cada una, qué alternativa se descartó y con qué costo. Los supuestos de negocio
están en el [README](README.md).

---

## Stack

| Pieza | Elección | Por qué |
|---|---|---|
| Framework | Laravel 13 | Versión actual. Exige PHP 8.3 como mínimo |
| Base de datos | MySQL 8 | El punto 4 pide una consulta SQL óptima: `GROUP_CONCAT` y `FOR UPDATE` son centrales |
| Vistas | Blade, sin compilación | Sin Vite ni npm: el proyecto se levanta con PHP y MySQL solamente |
| Caché | `Cache` facade, Redis por defecto | El enunciado pide caché en memoria; el facade deja cambiar de store sin tocar código |
| Tests | Pest 4 contra MySQL | Ver *Tests* |

---

## Organización del código

```
app/
  Services/Reservas/
    VentanaServicio.php       ventana de servicio de un día (apertura, cierre, último inicio)
    Franja.php                intervalo concreto de una reserva, ya validado
    HorarioService.php        (fecha, hora) -> franja + validaciones de horario y anticipación
    DisponibilidadService.php caché de la ocupación por día y ubicación
    AsignadorDeMesas.php      combinatoria de hasta 3 mesas y criterio de elección
    CombinacionDeMesas.php    conjunto de mesas unidas y su capacidad efectiva
    ReservaService.php        orquesta: transacción, lock, alta, invalidación, evento
  Queries/
    ReservasPorFechaQuery.php punto 4, consulta única
```

La lógica de negocio vive en servicios y no en los controladores ni en los modelos. Los
controladores traducen HTTP y nada más; los servicios no saben que existe una request.
Eso es lo que permite que los tests del núcleo no pasen por el kernel HTTP.

---

## Decisiones

### 1. Datetimes completos más una columna de día de negocio

`reservas` guarda `starts_at`/`ends_at` como `DATETIME`, más una columna
`fecha_servicio` (`DATE`).

**Problema:** con `fecha` + `hora_inicio`/`hora_fin` como `TIME`, el sábado de 22 a 2AM
produce `hora_fin < hora_inicio`. Eso rompe la detección de solapamiento *y* el listado
por fecha, y obliga a un caso especial de medianoche en cada consulta.

**Alternativa descartada:** derivar el día de negocio en el `WHERE` con un rango (de las
06:00 de un día a las 06:00 del siguiente). Convierte una igualdad indexada en un rango
y mete una regla de negocio adentro de la consulta.

**Costo:** una columna redundante que hay que mantener consistente. Se calcula en un
solo lugar (`HorarioService`), así que no puede divergir.

### 2. Ventanas configuradas como apertura + duración

`config/reservas.php` define cada día como `apertura` + `duracion_minutos` de la ventana,
no como apertura + cierre.

**Problema:** el cierre de lunes a viernes es "24:00", que no es una hora válida, y el
del sábado son las 02:00 del día siguiente, que como hora suelta parece anterior a la
apertura. Con apertura + duración los dos casos salen de la misma fórmula, y el último
horario reservable es `apertura + duracion_ventana − duracion_reserva`.

### 3. La caché guarda intervalos del día, no resultados por franja

Clave: `disponibilidad:{fecha_servicio}:{ubicacion_id}`, valor: los intervalos ocupados
de esa ubicación ese día.

**Problema:** cachear "mesas libres en tal franja" parece más directo, pero si la hora de
inicio es libre las franjas son infinitas: no existe *la* entrada a invalidar y hace
falta `Cache::tags` (que el driver `file` no soporta) o borrado por patrón.

**Con esta forma:** la clave es literalmente la disponibilidad de una ubicación —que es
lo que pide el enunciado—, la cardinalidad es días × 4, y la invalidación es un `forget`
de una clave exacta que funciona con cualquier store.

**Qué queda afuera de la caché:** la lista de mesas. Es un dato estable y cachearlo
obligaría a invalidar todas las fechas cada vez que el ABM toca una mesa. Lo volátil
—las reservas— es lo que se cachea.

### 4. La caché nunca decide; la base es la fuente de verdad

`DisponibilidadService` resuelve **lecturas** (la pantalla de estado, la grilla). Cuando
`ReservaService` confirma una reserva **no la consulta**: abre una transacción, toma
`FOR UPDATE` sobre las mesas de la ubicación candidata y re-verifica el solapamiento
contra la base.

El test *"no produce doble reserva aunque la cache esté envenenada"* fija exactamente
esto: se planta en la caché un valor que dice que todo está libre y la reserva igual no
duplica la mesa.

**Sobre `Cache::lock`:** se evaluó usar un lock distribuido de Redis en lugar de la
transacción. Se descartó como garantía dura porque depende de que el store sea
compartido: con `CACHE_STORE=array` o `file` cada proceso tendría su propio lock y la
garantía desaparecería sin hacer ruido, justo en el entorno de quien evalúa.

### 5. El orden fijo de ubicaciones también evita deadlocks

El recorrido siempre es A → B → C → D, así que todos los requests adquieren los locks en
el mismo orden. Dos altas simultáneas se serializan, no se traban entre sí.

### 6. La invalidación de caché va en `DB::afterCommit()`

Invalidarla dentro de la transacción abre una ventana en la que un lector concurrente
recalcula desde un snapshot que todavía no ve la reserva nueva y **vuelve a cachear
datos viejos**, que quedan ahí hasta que venza el TTL. El TTL de 10 minutos es una red
de seguridad, no el mecanismo principal.

El test *"espera al commit para invalidar la caché"* lo verifica desde afuera: dentro de
una transacción envolvente la clave sigue viva, y recién desaparece al cerrar.

### 7. Búsqueda de combinaciones por tamaño creciente

`AsignadorDeMesas` prueba combinaciones de 1 mesa, después de 2, después de 3, y corta en
el primer tamaño que da solución. Así el criterio "menos mesas" sale gratis y no se
generan combinaciones de 3 cuando con una alcanza. Dentro de un tamaño se elige por menor
desperdicio y se desempata por menor número de mesa, para que el resultado sea
determinístico y los tests reproducibles.

La búsqueda es O(N³) sobre las mesas libres de una ubicación (una decena): irrelevante a
esta escala. Si el local creciera a cientos de mesas por zona habría que podar por
capacidad antes de combinar.

### 8. Orden entre ubicaciones vs. desperdicio de asientos

El enunciado dice que la ubicacion la define el sistema "por orden", pero no aclara que
hacer cuando la primera zona con lugar solo puede ofrecer una mesa mas grande de la
necesaria y una zona posterior tiene una exacta.

**El costo es medible.** Con A = {mesa de 4} y B = {mesa de 2, mesa de 6}, dos reservas
encadenadas:

| Estrategia | Grupo de 2 | Grupo de 4 | Desperdicio total |
|---|---|---|---|
| `orden_estricto` | A, mesa de 4 | B, mesa de 6 | **4 asientos** |
| `ajuste_exacto_primero` | B, mesa de 2 | A, mesa de 4 | **0** |

No es solo la primera reserva la que sale peor: quemar la mesa de 4 en un grupo de 2
empuja al grupo siguiente a una mesa todavia mas grande.

**El default es `ajuste_exacto_primero`**: se prioriza no desperdiciar asientos. La
lectura literal del enunciado queda disponible como `orden_estricto`.

El argumento en contra, que no se descarta por capricho: el modelo no representa el costo
de **abrir una zona**, que en un local real es el recurso caro — cada zona abierta
necesita personal propio. Llevado al extremo, el ajuste global manda a dos personas solas
a la terraza vacia para ahorrar dos asientos. Si el local prefiere concentrar comensales
en las primeras zonas, se cambia una linea de configuracion y el comportamiento vuelve a
ser el del orden estricto, con sus tests correspondientes ya escritos.

Las dos estan implementadas en `EstrategiaAsignacion` y se cambian por configuracion.
`elegirUbicacion()` recorre las ubicaciones una sola vez y corta apenas una ofrece un
ajuste exacto, asi que la estrategia alternativa no bloquea zonas de mas salvo cuando
realmente necesita mirarlas. Si ninguna zona resulta exacta, devuelve la primera que
tenia lugar: exactamente la misma respuesta que `orden_estricto`.

Un detalle de la definicion de "exacto": se evalua sobre la mejor oferta de cada zona, y
dentro de una zona sigue mandando el criterio de usar menos mesas. Una zona con una mesa
de 6 libre y dos de 2 cuenta como no exacta para un grupo de 4, porque partir al grupo en
dos mesas para no desperdiciar asientos seria peor experiencia que sentarlo junto.

### 9. El punto 4 con query builder, no con Eloquent

`Reserva::with('mesas')` son dos consultas como mínimo y acceder a las mesas por relación
en la vista sería N+1. El join contra el pivote más `GROUP_CONCAT` devuelve cada reserva
en una sola fila con sus mesas concatenadas, en un único viaje.

**Costo:** se pierden los modelos hidratados. Para un listado de sólo lectura es un
intercambio conveniente. El agrupado por ubicación se hace en memoria sobre las
filas ya traídas, así que no agrega consultas.

El join contra `mesas` **no** filtra `deleted_at`: una mesa dada de baja tiene que seguir
apareciendo en las reservas históricas que la ocuparon.

### 10. Autenticación propia en lugar de un starter kit

Laravel Breeze dejó de recibir actualizaciones a partir de Laravel 12 y los starter kits
oficiales actuales (React, Vue, Livewire) arrastran Vite, npm y un paso de compilación.
Para dos formularios no compensa. La autenticación manual son dos controladores y tres
vistas, regenera la sesión después de autenticar (fijación de sesión) y limita los
intentos con `RateLimiter`.

**Beneficio colateral:** sin build step, el proyecto se levanta con `composer install`,
`migrate --seed` y `serve`.

### 11. Baja lógica de mesas con bloqueo por reservas futuras

Un `DELETE` con cascada sobre el pivote haría desaparecer mesas de reservas históricas y
el listado del punto 4 empezaría a perder filas hacia atrás (el join es `INNER`). Una FK
`restrict` a secas dejaría las mesas sin poder darse de baja nunca, porque siempre tienen
alguna reserva vieja.

Con `SoftDeletes` más el bloqueo por reservas futuras: la mesa sale del ABM y del motor
de asignación, y su historial queda intacto. El índice único incluye `deleted_at`, lo que
en MySQL significa "único entre las filas vivas" y permite reutilizar el número.

### 12. Sin broadcasting

El enunciado no pide tiempo real. Reverb implica un proceso extra (`reverb:start`),
compilar assets y cuatro variables de entorno más; si quien evalúa no levanta el proceso,
la pantalla se ve rota sin explicación. La pantalla de estado se refresca con polling
cada 15 segundos contra `/api/estado`.

El evento `ReservaCreada` se emite igual, después del commit. Para tiempo real estricto:
implementar `ShouldBroadcast` en el evento devolviendo un canal por ubicación, y en
`estado/index.blade.php` reemplazar el `setInterval` por una suscripción de Echo.

### 13. Tests contra MySQL

SQLite en memoria arranca sin configurar nada, pero no soporta la sintaxis de
`GROUP_CONCAT` que usa el punto 4 ni `SELECT ... FOR UPDATE`, y no aplica
`ONLY_FULL_GROUP_BY`. Testear ahí dejaría los dos tests más importantes probando algo
que no es lo que corre en producción.

**Costo:** hay que crear una base de test (dos líneas en el README) y la suite tarda
minutos en lugar de segundos.

Los tests de concurrencia viven en una suite aparte porque **no pueden usar
`RefreshDatabase`**: ese trait envuelve cada test en una transacción que nunca se
commitea, y la segunda conexión no vería nada de lo que ocurre adentro. Esa suite limpia
la base a mano y baja `innodb_lock_wait_timeout` a 1 segundo para no esperar el default
de 50.

---

## Changelog

- **2026-08-20 — Rate limit en el alta de reservas.** `POST /reservas` no tenia
  ningun limite: un usuario autenticado podia llamarlo en bucle y acaparar los
  locks de la zona mas pedida (cada llamada abre una transaccion con `SELECT
  ... FOR UPDATE`). Se agrego `throttle:20,1` solo a esa ruta, configurable via
  `RESERVAS_THROTTLE_RESERVAS` en `config/reservas.php`, mas un test que
  verifica que la request 21 dentro del minuto devuelve 429.
- **2026-08-20 — Estrategia de asignacion configurable, con ajuste exacto por default.**
  Se detecto que el orden estricto entre ubicaciones desperdicia asientos de forma
  encadenada (un grupo chico ocupa la mesa grande y empuja al siguiente a una todavia mas
  grande). Se implementaron las dos estrategias y se dejo `ajuste_exacto_primero` como
  default: la eficiencia de asientos es medible y el costo de abrir una zona no esta
  modelado. `orden_estricto` queda a una linea de configuracion.
- **2026-08-20 — Higiene del repositorio.** Se dejaron de versionar las vistas Blade
  compiladas y los caches de `bootstrap/cache`: se habian colado al armar el proyecto
  porque los `.gitignore` internos de `storage/` y `bootstrap/cache` no se copiaron.
- **2026-08-20 — Confiar en el reverse proxy.** `trustProxies` para que las URLs
  generadas respeten el esquema https detras de un proxy que termina TLS.
- **2026-08-20 — Proyecto inicial.** Laravel 13 sin starter kit ni build step, modelo de
  datos con día de negocio explícito, asignación de mesas con caché y transacción con
  lock, listado del punto 4 en una consulta, 100 tests contra MySQL.
