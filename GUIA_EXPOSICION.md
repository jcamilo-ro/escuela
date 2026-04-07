# Guia de exposicion del proyecto

## 1. Monologo principal para exponer

Hola, mi proyecto consiste en un sistema academico web para gestionar estudiantes, materias y matriculas desde una sola interfaz administrativa. Lo desarrollé usando PHP para el backend, MySQL como base de datos, JavaScript para la interaccion dinamica del frontend y AdminLTE con Bootstrap para la parte visual.

La idea principal del sistema es resolver un flujo academico basico pero completo. Por un lado, puedo crear, editar, buscar y eliminar estudiantes. Por otro lado, puedo crear y eliminar materias. Y la parte mas importante es la matricula, porque en este proyecto la matricula ya no se hace desde la materia, sino desde cada estudiante. Eso significa que cada estudiante entra a su propio modulo de matricula y desde ahi selecciona las materias que quiere cursar.

Yo definí como regla del negocio que cada estudiante solo puede matricular un maximo de 3 materias. Esa regla no la dejé solo en la interfaz, sino tambien en el backend. Es decir, si alguien intenta saltarse la interfaz y enviar mas de 3 materias manualmente, el servidor tambien lo bloquea. Eso lo hice asi porque una validacion profesional no debe depender solo de lo visual, sino tambien del servidor.

En cuanto a la arquitectura, el proyecto esta dividido en tres capas principales. La primera capa es la presentacion, que esta en `index.php`, `assets/css/app.css` y `assets/js/app.js`. La segunda capa es la logica del servidor, que esta en `api/student.php` y `api/subject.php`. Y la tercera capa es la persistencia, que depende de MySQL y de la conexion centralizada en `connectdb.php`.

Visualmente, adapté el proyecto a una plantilla administrativa con AdminLTE. Eso me permitió tener un dashboard mas profesional, con sidebar, tarjetas de resumen, secciones separadas y una navegacion mas clara. El dashboard muestra metricas como la cantidad de estudiantes, materias y matriculas activas, y desde el menu lateral puedo entrar al dashboard, al panel de estudiantes o al catalogo de materias.

En la parte frontend usé JavaScript con `fetch`, porque queria que la aplicacion funcionara sin recargar toda la pagina en cada operacion. Entonces, cuando creo un estudiante, lo edito, lo elimino o guardo materias, el navegador llama a las APIs PHP, recibe una respuesta JSON y actualiza solo la tabla necesaria. Eso mejora mucho la experiencia de usuario y hace que el sistema se sienta mas moderno.

En la base de datos tengo tres tablas principales. La tabla `student` guarda la informacion de cada estudiante. La tabla `subject` guarda las materias. Y la tabla `student_subject` resuelve la relacion muchos a muchos entre estudiantes y materias. Elegi ese diseño porque un estudiante puede tener varias materias, y una materia puede pertenecer a varios estudiantes. Esa tabla intermedia es la forma correcta de modelar ese caso en una base relacional.

Despues del desarrollo local en XAMPP, migre el proyecto a Docker. Lo hice para que el sistema no dependiera exclusivamente de XAMPP y pudiera ejecutarse en contenedores reproducibles. Actualmente tengo un contenedor para la aplicacion web con PHP y Apache, otro contenedor para MySQL y otro para phpMyAdmin. La aplicacion abre en `http://localhost:8080` y phpMyAdmin en `http://localhost:8081`.

En Docker tambien organicé la importacion inicial de la base. Para eso exporté la base principal en un archivo SQL, y Docker la usa como semilla al crear el contenedor de MySQL. Ademas agregué una base auxiliar para `phpMyAdmin`, que sirve para guardar su configuracion interna, como favoritos, recientes y preferencias. Esa base no reemplaza mi base del proyecto, solo complementa a la herramienta administrativa.

En cuanto al control de codigo fuente, conecté el proyecto a GitHub y empecé a manejarlo con commits descriptivos. La idea fue dejar un historial profesional, donde cada cambio importante quedara registrado: migracion del dashboard, mejoras visuales, incorporacion de Docker, integracion de phpMyAdmin y correcciones tecnicas. Eso permite mantener trazabilidad y facilita trabajar el proyecto de forma ordenada.

En resumen, este proyecto no solo resuelve un CRUD, sino que implementa una estructura mas cercana a una aplicacion real: interfaz administrativa, reglas de negocio, conexion asincrona con APIs, base de datos relacional y despliegue en Docker. Mi objetivo fue que se viera profesional, pero tambien que mantuviera una estructura clara y entendible.

## 2. Explicacion del proyecto por archivos

### `index.php`

Aqui construí la vista principal del sistema. Lo usé como pagina contenedora del dashboard.

Lo primero que hace este archivo es desactivar cache:

- `Cache-Control`
- `Pragma`
- `Expires`

Eso lo puse para evitar que el navegador muestre una version vieja de la interfaz cuando yo hago cambios frecuentes durante el desarrollo.

Despues, `index.php` se conecta a la base de datos usando:

- [connectdb.php](C:\xampp\htdocs\escuela\connectdb.php)

Con esa conexion carga informacion base para pintar el dashboard desde el servidor:

- lista de materias
- cantidad de estudiantes
- cantidad de materias
- cantidad de matriculas

Esto lo hice en PHP y no en JavaScript porque queria que el panel cargara con informacion inicial apenas se abre la pagina, incluso antes de ejecutar las peticiones del frontend.

La estructura HTML esta dividida en:

- cabecera superior
- menu lateral
- seccion dashboard
- seccion estudiantes
- seccion materias
- modales
- zona de toasts

Use AdminLTE porque da una estructura administrativa ya muy probada:

- sidebar
- navbar
- layout responsive
- estilo de panel real

Yo lo elegí en lugar de hacer todo desde cero porque el profesor pidio una plantilla administrativa y porque asi el proyecto se ve mas profesional.

#### Cosas importantes dentro de `index.php`

- `data-section` en los enlaces del sidebar:
  Lo usé para identificar cada seccion y cambiar de panel sin recargar toda la pagina.

- `app-panel-section`:
  Es la clase comun de cada seccion principal. JavaScript la usa para mostrar u ocultar bloques completos.

- tarjetas de metricas:
  Muestran resumen rapido del sistema y ayudan a que el dashboard no sea solo una tabla.

- modales:
  Los use para crear, editar, eliminar y matricular sin salir de la misma pantalla.

- script final embebido:
  Ese bloque pequeño de JavaScript maneja la navegacion entre secciones y sincroniza el breadcrumb con la seccion actual. Lo dejé embebido porque depende directamente del layout visual de esta vista.

### `connectdb.php`

Este archivo centraliza la conexion PDO a MySQL.

Yo lo hice asi porque no queria repetir la conexion en todos los archivos. En vez de escribir la conexion varias veces, hice un archivo unico y lo importo donde se necesite.

Este archivo tiene dos comportamientos:

- en XAMPP usa valores locales por defecto
- en Docker usa variables de entorno

Las variables son:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

Esto lo hice porque en XAMPP la base vive en `127.0.0.1`, pero en Docker la aplicacion no se conecta a `localhost`, sino al servicio `db`. Entonces necesitaba una conexion flexible.

Use PDO en lugar de `mysqli` porque:

- soporta prepared statements de forma mas limpia
- se integra mejor si mas adelante el proyecto crece
- da una capa mas profesional para manejo de errores

### `assets/js/app.js`

Este es el cerebro del frontend.

Aqui concentre toda la logica del navegador:

- cargar estudiantes
- cargar materias
- crear registros
- editar registros
- eliminar registros
- buscar estudiantes
- abrir modales
- guardar matriculas
- mostrar mensajes toast
- controlar la navegacion entre secciones

Lo hice en un solo archivo porque el proyecto todavia es pequeno y asi es mas facil seguir el flujo completo.

#### Cosas importantes de `app.js`

- `STUDENT_API` y `SUBJECT_API`
  Son rutas base para no repetir URLs.

- `MAX_SUBJECTS_PER_STUDENT = 3`
  Centraliza la regla academica en el frontend.

- `fetchJson()`
  Esta funcion envuelve `fetch`, agrega timeout y parsea JSON. La hice porque no queria repetir el mismo bloque de codigo para cada llamada a la API.

- `escapeHtml()`
  Protege el renderizado en tablas para evitar insertar HTML directo desde datos de usuario.

- `studentsCache`
  Guarda los estudiantes ya cargados para poder abrir el modal de edicion sin volver a consultar al servidor.

- `renderTablaEstudiantes()` y `renderTablaMaterias()`
  Son las funciones que convierten los datos JSON en filas HTML.

- `setTableEmptyState()`
  En lugar de dejar una tabla vacia o un texto plano, diseñé un estado vacio mas presentable.

- `confirmarAccion()`
  Lo hice con `Promise` para reutilizar un modal de confirmacion y manejar acciones destructivas de forma limpia.

- `abrirModalMatricula()`
  Carga las materias disponibles de un estudiante y prepara el formulario de matricula.

- `syncSubjectCheckboxLimit()`
  Deshabilita checkboxes cuando el estudiante ya alcanzó el limite. Esto mejora la experiencia del usuario antes de enviar el formulario.

#### Por que usé `fetch` y no formularios tradicionales

Porque queria evitar recargar la pagina completa despues de cada accion. Con `fetch` puedo:

- enviar datos a PHP
- recibir JSON
- actualizar solo la tabla necesaria

Eso hace que el sistema se sienta mucho mas actual.

### `assets/css/app.css`

Aqui puse los estilos personalizados del proyecto.

AdminLTE ya da una base visual, pero yo queria que no se viera generico, asi que agregué una capa propia para:

- fondo con gradientes
- dashboard tipo hero
- tarjetas estadisticas
- tablas premium
- badges
- avatars
- estados vacios
- modales mas limpios
- toasts con mejor presencia

Lo hice en un archivo aparte para no tocar directamente los archivos del proveedor. Eso es importante porque si algun dia actualizo AdminLTE, mis cambios no se pierden ni se mezclan con la libreria.

### `api/student.php`

Esta API maneja todo lo relacionado con estudiantes y matriculas por estudiante.

Trabaja con diferentes acciones:

- `listar`
- `siguiente_codigo`
- `materias_matricula`
- `guardar_matriculas`
- `crear`
- `actualizar`
- `eliminar`

Yo elegí este diseño por accion en un solo endpoint porque para un proyecto academico pequeño es mas facil de seguir que crear muchos archivos separados.

#### Cosas importantes

- `responseJson()`
  Uniforma todas las respuestas. Siempre devuelve `success`, `message` y datos extra si aplica.

- `validarCodigoEstudiante()`
  Fuerza el patron `26###`. Eso responde a una regla especifica del proyecto.

- `obtenerSiguienteCodigoEstudiante()`
  Calcula el siguiente codigo disponible a partir del maximo actual.

- `codigoDisponible()`
  Evita duplicados.

- `obtenerIdsMateriasDesdePost()`
  Normaliza los IDs recibidos desde el formulario para que lleguen como un arreglo limpio.

- `guardar_matriculas`
  Primero valida el limite de 3 materias, luego valida que las materias existan y despues reemplaza la matricula anterior dentro de una transaccion.

Use transacciones porque si algo falla a mitad de la matricula, no quiero dejar datos inconsistentes.

#### Parte poco comun

En `eliminar`, despues de borrar un estudiante, reajusto el `AUTO_INCREMENT`.

Lo hice para que el siguiente ID no deje huecos si se elimina el ultimo registro. No siempre se hace en sistemas reales grandes, pero en este proyecto academico ayuda a que los IDs se vean mas ordenados para demostracion.

### `api/subject.php`

Esta API maneja materias.

Actualmente se usa principalmente para:

- listar materias
- crear materias
- eliminar materias

Tambien contiene acciones heredadas para matricular desde la materia, como `estudiantes_matricula` y `guardar_matriculas`, pero el flujo actual principal ya esta orientado a matricular desde el estudiante.

Eso significa que el sistema evolucionó: el backend todavia conserva cierta capacidad adicional, pero la interfaz ya esta enfocada en el nuevo flujo pedido.

#### Cosas importantes

- `normalizarTexto()`
  limpia entradas basicas

- `crear`
  obliga a guardar el codigo de la materia en mayusculas

- `listar`
  devuelve tambien cuántos estudiantes estan matriculados por materia

- `guardar_matriculas`
  controla que no se le asigne una materia a un estudiante si ya alcanzó el maximo permitido

### `docker-compose.yml`

Este archivo define el entorno en contenedores.

Yo separé el sistema en tres servicios:

- `app`
- `db`
- `phpmyadmin`

#### `app`

Es el contenedor donde corre la aplicacion PHP con Apache.

Se conecta con la base usando variables de entorno:

- `DB_HOST: db`
- `DB_PORT: 3306`
- `DB_NAME: escuela_db`
- `DB_USER: escuela_user`
- `DB_PASS: 123456`

Lo importante aqui es que `db` no es una IP, sino el nombre del servicio Docker. Docker Compose crea una red interna y permite que los servicios se encuentren por nombre.

#### `db`

Es el contenedor MySQL.

Aqui definí:

- base principal: `escuela_db`
- usuario de aplicacion: `escuela_user`
- clave: `123456`
- root: `root123456`

Tambien agregué:

- puerto `3307:3306`
  para no chocar con MySQL de XAMPP

- volumen `db_data:/var/lib/mysql`
  para que la base no se pierda al reiniciar contenedores

- `healthcheck`
  para que Docker sepa cuando MySQL ya esta realmente listo

- montajes de scripts SQL
  `01-escuela_db.sql` importa la base principal
  `02-phpmyadmin.sql` crea la base auxiliar de phpMyAdmin

#### `phpmyadmin`

Es el contenedor administrativo para ver la base desde navegador.

Lo expuse en:

- `8081:80`

y lo conecté al servicio:

- `PMA_HOST: db`

Tambien monté un archivo de configuracion extra para activar su almacenamiento interno.

### `Dockerfile`

Este archivo define como se construye la imagen de la aplicacion.

Use:

- `php:8.2-apache`

porque el proyecto corre con PHP y se sirve por Apache.

Adentro instalé:

- `pdo_mysql`

porque sin esa extension PHP no puede conectarse a MySQL con PDO.

Tambien activé:

- `rewrite`

porque es una configuracion comun y util en Apache, sobre todo si mas adelante se agregan rutas o reglas.

Y agregué:

- `ServerName localhost`

para evitar avisos innecesarios del servidor.

### `database/escuela_db_export.sql`

Este archivo es una exportacion de la base principal.

No es la base viva. Es una copia inicial.

Sirve para que Docker importe la estructura y los datos la primera vez que crea MySQL.

Despues de la importacion, la base real ya vive en el volumen Docker, no dentro de este archivo.

### `database/phpmyadmin_setup.sql`

Este archivo crea la base `phpmyadmin`, que no es tu base del proyecto sino una base auxiliar para la herramienta `phpMyAdmin`.

Sirve para guardar:

- favoritos
- recientes
- configuracion interna
- preferencias de interfaz

Lo agregué para quitar el aviso de almacenamiento incompleto y dejar el administrador visual bien configurado.

### `config/phpmyadmin/config.user.inc.php`

Este archivo le dice a `phpMyAdmin` que use la base auxiliar `phpmyadmin` y sus tablas internas.

Lo hice porque no basta con crear la base; tambien hay que indicarle a la herramienta donde guardar esa informacion.

## 3. Como se conectan las piezas

Yo lo explicaria asi:

1. El usuario abre `index.php`.
2. `index.php` consulta la base para cargar metricas iniciales.
3. El navegador carga `app.js`.
4. `app.js` llama a `api/student.php` y `api/subject.php` usando `fetch`.
5. Las APIs usan [connectdb.php](C:\xampp\htdocs\escuela\connectdb.php) para conectarse a MySQL.
6. MySQL devuelve los datos.
7. `app.js` renderiza las tablas y muestra la interfaz actualizada.

En Docker, la idea es la misma, pero la diferencia es que:

- la aplicacion vive en el contenedor `app`
- la base vive en el contenedor `db`
- el administrador visual vive en `phpmyadmin`

## 4. Explicacion del control de codigo fuente

Yo conecté el proyecto a GitHub para llevar trazabilidad de los cambios.

La idea del control de codigo fuente en este proyecto fue:

- guardar avances por etapas
- poder volver a un punto anterior si algo falla
- dejar evidencia tecnica del proceso
- mostrar un flujo de trabajo mas profesional

Los commits mas representativos hasta ahora son:

- `caracteristica: migrar el proyecto de xampp a docker`
- `ajuste: agregar phpmyadmin en docker para la base de datos`
- `correccion: configurar almacenamiento interno de phpmyadmin`
- `mejora: refinar dashboard, tablas y estados visuales`
- `ajuste: simplificar cabecera y menu lateral`

Cada commit representa una decision concreta:

- migracion tecnica
- mejora visual
- ajuste funcional
- correccion de configuracion

Eso hace que el historial no sea solo una lista de cambios, sino una narrativa del proyecto.

## 5. Preguntas que podrian hacerte y como responderlas

### “Por que usaste una tabla intermedia?”

Porque la relacion entre estudiantes y materias es muchos a muchos. Un estudiante puede tener varias materias y una materia puede pertenecer a varios estudiantes. La forma correcta de modelar eso en SQL es con una tabla intermedia, en este caso `student_subject`.

### “Por que validas el limite de 3 materias en frontend y backend?”

Porque el frontend mejora la experiencia del usuario y evita errores visualmente, pero el backend es el que realmente protege la integridad de la regla de negocio.

### “Por que usaste APIs con JSON?”

Porque queria una interfaz dinamica, sin recarga completa de pagina. JSON me permite intercambiar datos entre PHP y JavaScript de forma simple y clara.

### “Por que usar Docker si ya tenias XAMPP?”

Porque Docker permite un entorno mas reproducible y portable. Con Docker el proyecto no depende tanto de la configuracion manual del equipo.

### “Por que usaste AdminLTE?”

Porque necesitaba una plantilla administrativa real, con sidebar, panel, componentes y apariencia profesional. Ademas fue un requerimiento del trabajo.

## 6. Cierre corto para la exposicion

En conclusion, el proyecto paso de ser un CRUD basico a una aplicacion academica mas completa. Integra una interfaz administrativa moderna, una logica de matriculas con reglas de negocio, persistencia en MySQL, consumo asincrono de APIs y despliegue con Docker. Lo que busqué fue combinar funcionamiento tecnico, organizacion del codigo y una presentacion visual profesional.
