# Guion de Docker para exponer

## 1. Guion principal para explicar la parte de Docker

En esta parte del proyecto, lo que hice fue migrar el sistema desde un entorno tradicional con XAMPP hacia un entorno con Docker. La razon principal fue que con XAMPP el proyecto depende mucho de la configuracion manual del computador, mientras que con Docker puedo levantar todo el entorno de forma mas controlada, repetible y profesional.

Antes de Docker, mi proyecto corria con Apache y MySQL de XAMPP. Eso funcionaba, pero si otra persona queria ejecutar el proyecto, tenia que configurar PHP, Apache, MySQL, credenciales, puertos y estructura local. Con Docker resolvi eso encapsulando cada parte importante del sistema en contenedores.

Actualmente el entorno Docker del proyecto tiene tres servicios principales. El primero es `app`, que es donde corre la aplicacion PHP con Apache. El segundo es `db`, que es el contenedor de MySQL donde vive la base de datos del sistema. Y el tercero es `phpmyadmin`, que me permite administrar visualmente esa base desde el navegador.

La idea de separar esos servicios fue seguir una arquitectura mas limpia. En lugar de mezclar todo en una sola instalacion local, cada responsabilidad queda aislada. La aplicacion web corre en un contenedor, la base corre en otro, y la herramienta administrativa en otro diferente. Eso hace que el entorno sea mas ordenado y mas facil de mantener.

Para poder construir ese entorno, el archivo mas importante fue `docker-compose.yml`. Ese archivo actua como orquestador. Ahí definí que servicios existen, que imagen usa cada uno, que puertos se publican, que variables de entorno necesita cada servicio, que volumenes se montan y como se relacionan entre si.

El servicio `app` esta conectado al contenedor `db` a traves de variables de entorno. Eso significa que la aplicacion ya no se conecta a `localhost` como en XAMPP, sino al nombre del servicio Docker, que en este caso es `db`. Docker Compose crea una red interna y permite que los contenedores se encuentren por nombre. Entonces cuando PHP necesita conectarse a MySQL, usa `DB_HOST=db`.

En el servicio `db` utilicé MySQL 8.0. Ahí defini el nombre de la base principal como `escuela_db`, el usuario de aplicacion `escuela_user`, su contraseña y tambien una contraseña de `root`. Esa base es la que usa el sistema academico para guardar estudiantes, materias y matriculas.

Ademas, monté un volumen llamado `db_data`. Ese volumen es importante porque es donde queda guardada la base real del proyecto mientras Docker esta funcionando. El archivo SQL exportado no es la base viva, sino una semilla inicial. La base real, una vez creada, se conserva en ese volumen.

Para cargar la informacion inicial del sistema, usé el archivo `database/escuela_db_export.sql`. Ese archivo se monta dentro de la ruta especial `/docker-entrypoint-initdb.d/`. MySQL revisa esa carpeta cuando se crea por primera vez y ejecuta automaticamente los scripts `.sql` que encuentra ahi. Gracias a eso, cuando el contenedor arranca por primera vez, ya aparece la base `escuela_db` con sus tablas y datos iniciales.

Despues agregué un segundo script llamado `database/phpmyadmin_setup.sql`. Ese archivo no pertenece a la base del proyecto como tal, sino a la herramienta `phpMyAdmin`. Lo usé para crear una base auxiliar llamada `phpmyadmin`, junto con varias tablas internas. Esa base guarda favoritos, recientes, configuraciones y funciones administrativas de la herramienta. La agregué para quitar los avisos de configuracion incompleta y dejar el entorno mas profesional.

Tambien fue necesario crear el archivo `config/phpmyadmin/config.user.inc.php`. Ese archivo le dice a `phpMyAdmin` que use la base auxiliar `phpmyadmin` y que tablas internas debe usar. Es decir, no basta con crear la base, tambien habia que indicarle a la herramienta donde guardar esa informacion.

En cuanto a los puertos, decidí no usar los mismos de XAMPP para evitar conflictos. La aplicacion la expuse en `localhost:8080`, la base de datos MySQL en `localhost:3307`, y `phpMyAdmin` en `localhost:8081`. Eso me permite tener Docker funcionando sin pelearse con Apache o MySQL locales.

Otro detalle importante fue el `healthcheck` del contenedor de MySQL. Lo agregué porque queria que Docker supiera cuando la base estaba realmente lista. Si la aplicacion intenta conectarse demasiado pronto, puede fallar aunque el contenedor ya se haya iniciado. Con el `healthcheck`, el servicio `app` espera a que `db` este sano antes de depender de el.

Tambien cree el `Dockerfile`, que define como se construye la imagen de la aplicacion. En ese archivo partí de `php:8.2-apache`, instalé la extension `pdo_mysql`, activé `rewrite` y configuré `ServerName localhost`. Lo hice asi porque mi proyecto es PHP y necesita conectarse a MySQL mediante PDO. Sin esa extension, la aplicacion no podria acceder a la base.

Para que el mismo proyecto siga funcionando tanto en XAMPP como en Docker, modifiqué `connectdb.php`. Ahí la conexion primero intenta leer variables de entorno como `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS`. Si no existen, usa valores por defecto compatibles con XAMPP. Eso me permitió no duplicar codigo ni mantener dos versiones distintas de la aplicacion.

Una vez listo el entorno, el comando principal para levantar todo es `docker compose up -d --build`. Ese comando construye la imagen de la aplicacion, levanta los tres contenedores y los deja corriendo en segundo plano. Para detenerlos uso `docker compose down`, y si quiero borrar tambien la base persistente y recrear todo desde cero uso `docker compose down -v`.

En resumen, la parte de Docker me sirvió para convertir el proyecto en un entorno mas portable y controlado. En lugar de depender solo de XAMPP, ahora el sistema puede ejecutarse por contenedores, con servicios separados, puertos definidos, base inicial automatizada y administracion visual en `phpMyAdmin`.

## 2. Explicacion de Docker archivo por archivo

### `docker-compose.yml`

Este es el archivo principal de Docker en el proyecto.

Yo lo explicaria asi:

“Este archivo define todo el entorno en contenedores. Aqui indico que servicios existen, como se llaman, que imagen usan, que variables necesitan, que puertos se abren y que archivos o volumenes se montan.”

#### Servicio `app`

Este servicio representa la aplicacion web.

Usa:

- el `Dockerfile` del proyecto

Expone:

- `8080:80`

Eso significa:

- el puerto `80` existe dentro del contenedor
- el puerto `8080` es el que yo uso desde el navegador en mi computador

Tambien tiene variables de entorno:

- `DB_HOST: db`
- `DB_PORT: 3306`
- `DB_NAME: escuela_db`
- `DB_USER: escuela_user`
- `DB_PASS: 123456`

Estas variables son importantes porque la aplicacion necesita saber a que base conectarse. En Docker no se usa `localhost` para hablar entre contenedores, sino el nombre del servicio. Por eso `DB_HOST` es `db`.

Tambien tiene este volumen:

- `./:/var/www/html`

Eso hace que el codigo local del proyecto se monte dentro del contenedor. Gracias a eso, si edito un archivo en Visual Studio Code, el contenedor ve el cambio sin tener que copiar el proyecto manualmente.

#### Servicio `db`

Este servicio representa la base de datos principal.

Usa la imagen:

- `mysql:8.0`

Eso lo elegí porque queria una version moderna y estable de MySQL.

Tiene:

- `container_name: escuela_db`

Ese es el nombre del contenedor, no de la base.

Usa el puerto:

- `3307:3306`

Eso quiere decir:

- dentro del contenedor MySQL escucha en `3306`
- desde mi computador lo accedo en `3307`

Lo hice asi para no chocar con el `3306` de XAMPP.

En `environment` definí:

- `MYSQL_DATABASE: escuela_db`
- `MYSQL_USER: escuela_user`
- `MYSQL_PASSWORD: 123456`
- `MYSQL_ROOT_PASSWORD: root123456`

Eso hace que al inicializarse el contenedor, MySQL cree la base y el usuario principal del proyecto.

Luego agregué:

- `healthcheck`

Eso sirve para que Docker compruebe si MySQL ya responde bien antes de dejar depender de el a otros servicios.

En `volumes` puse tres cosas:

- `db_data:/var/lib/mysql`
- `./database/escuela_db_export.sql:/docker-entrypoint-initdb.d/01-escuela_db.sql:ro`
- `./database/phpmyadmin_setup.sql:/docker-entrypoint-initdb.d/02-phpmyadmin.sql:ro`

La primera es la base real persistente.

La segunda importa la base principal del sistema.

La tercera crea la base auxiliar de `phpMyAdmin`.

#### Servicio `phpmyadmin`

Este servicio es una herramienta de administracion visual.

Usa:

- `phpmyadmin:5.2-apache`

Se expone en:

- `8081:80`

Entonces en navegador yo entro a:

- `http://localhost:8081`

En `environment` se conecta a:

- `PMA_HOST: db`
- `PMA_PORT: 3306`

Eso quiere decir que `phpMyAdmin` no se conecta a XAMPP ni a otra base externa, sino exactamente al contenedor `db`.

Tambien monté:

- `./config/phpmyadmin/config.user.inc.php:/etc/phpmyadmin/conf.d/config.user.inc.php:ro`

Ese archivo agrega la configuracion interna necesaria para que `phpMyAdmin` use su propia base auxiliar y no muestre errores de configuracion incompleta.

### `Dockerfile`

Este archivo define la imagen del servicio `app`.

Empieza con:

- `FROM php:8.2-apache`

Eso significa que parto de una imagen oficial que ya trae PHP y Apache.

Despues puse:

- `RUN docker-php-ext-install pdo_mysql`

Eso instala la extension necesaria para que PHP pueda conectarse a MySQL usando PDO. Sin esto, la aplicacion no podria ejecutar consultas.

Luego:

- `RUN a2enmod rewrite`

Eso activa el modulo `rewrite` de Apache. En este proyecto no es la pieza mas critica, pero es una configuracion util y profesional por si el proyecto crece o necesita reglas de reescritura.

Luego:

- `RUN echo "ServerName localhost" ...`

Eso lo hice para evitar avisos de Apache sobre el nombre del servidor cuando el contenedor inicia.

Y por ultimo:

- `WORKDIR /var/www/html`

Eso define la carpeta principal donde se va a servir el proyecto.

### `connectdb.php`

Aunque este archivo no es de Docker como tal, es clave para que Docker funcione.

Lo que hace es leer:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

Si encuentra esas variables, se conecta con ellas.

Si no las encuentra, usa valores por defecto pensados para XAMPP.

Eso lo hace un archivo puente entre ambos entornos.

### `database/escuela_db_export.sql`

Este archivo es una exportacion de la base principal del proyecto.

Lo importante que puedes decir es:

“Este archivo no es la base viva, sino una copia inicial. Docker la usa solo para importar la estructura y los datos la primera vez que el contenedor MySQL se crea.”

Despues de eso, los cambios reales se guardan en el volumen `db_data`.

### `database/phpmyadmin_setup.sql`

Este archivo crea una base auxiliar llamada `phpmyadmin`.

No guarda:

- estudiantes
- materias
- matriculas

Lo que guarda es informacion interna de la herramienta `phpMyAdmin`, por ejemplo:

- favoritos
- recientes
- configuraciones de interfaz

Lo agregué para que `phpMyAdmin` quedara bien configurado y sin mensajes de advertencia.

### `config/phpmyadmin/config.user.inc.php`

Este archivo le indica a `phpMyAdmin`:

- que use la base `phpmyadmin`
- que use el usuario tecnico `pma`
- que tablas internas tiene disponibles

Es decir, el SQL crea la base, pero este archivo le enseña a la herramienta como utilizarla.

### `.dockerignore`

Este archivo le dice a Docker que cosas no debe tomar al construir la imagen.

Sirve para evitar copiar contenido innecesario como:

- logs
- archivos temporales
- carpetas irrelevantes

Con eso el build queda mas limpio y mas ligero.

## 3. Como se relaciona todo el flujo Docker

Yo lo explicaria asi:

1. Yo ejecuto `docker compose up -d --build`.
2. Docker lee `docker-compose.yml`.
3. Construye la imagen de `app` usando el `Dockerfile`.
4. Levanta el contenedor `db`.
5. MySQL ejecuta los scripts SQL montados en `/docker-entrypoint-initdb.d/`.
6. Se crea la base `escuela_db`.
7. Se crea tambien la base `phpmyadmin`.
8. Docker marca el servicio `db` como sano cuando responde al `healthcheck`.
9. Luego arranca `app`, que se conecta a `db` usando variables de entorno.
10. Tambien arranca `phpmyadmin`, que se conecta al mismo `db`.
11. La aplicacion queda visible en `localhost:8080`.
12. `phpMyAdmin` queda visible en `localhost:8081`.

## 4. Preguntas posibles y respuestas

### “Por que no dejaste solo XAMPP?”

Porque con Docker el entorno queda mas reproducible y menos dependiente del computador local. Se vuelve mas facil compartirlo y volver a levantarlo.

### “Por que separaste app, db y phpmyadmin?”

Porque cada uno cumple una responsabilidad distinta: aplicacion, base de datos y administracion visual. Separarlos mejora organizacion y mantenimiento.

### “Por que usaste un volumen?”

Porque si no usara volumen, al borrar el contenedor se perderian los datos reales. El volumen mantiene la base persistente.

### “Por que usaste dos archivos SQL?”

Porque uno corresponde a la base principal del sistema (`escuela_db`) y el otro a la base auxiliar de `phpMyAdmin` (`phpmyadmin`).

### “Entonces cual es la base real del proyecto?”

La base funcional del proyecto es `escuela_db`. El archivo `escuela_db_export.sql` solo es una semilla inicial, y la base viva luego queda en el volumen Docker.

### “Por que phpMyAdmin tiene su propia base?”

Porque necesita un espacio propio para guardar favoritos, recientes y configuraciones internas sin mezclar eso con los datos academicos del proyecto.

## 5. Cierre corto para esta parte

En conclusion, con Docker convertí el proyecto en un entorno mas profesional. Separé la aplicacion, la base y la administracion visual en contenedores distintos, automaticé la carga inicial de la base de datos, mantuve persistencia con volumenes y dejé el sistema accesible por puertos independientes. Eso hizo que el proyecto fuera mas portable, mas ordenado y mas facil de explicar y ejecutar.
