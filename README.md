# Escuela

Panel academico desarrollado con PHP, MySQL, JavaScript y AdminLTE para gestionar estudiantes, materias y matriculas desde una interfaz administrativa moderna.

## Vista general

Este proyecto centraliza en una sola pantalla:

- registro, edicion y eliminacion de estudiantes
- creacion y eliminacion de materias
- matricula de materias por estudiante
- control de maximo 3 materias por estudiante
- visualizacion rapida de metricas academicas

La aplicacion esta pensada para ejecutarse en un entorno local con XAMPP y base de datos MySQL.

## Caracteristicas principales

- interfaz administrativa basada en AdminLTE 4
- sidebar lateral, barra superior y tarjetas de resumen
- CRUD dinamico con `fetch` y respuestas JSON
- buscador de estudiantes por codigo, nombre o apellido
- generacion automatica de codigo de estudiante
- validaciones en frontend y backend
- matricula de materias desde el modulo de estudiantes
- estructura simple para uso academico y mantenimiento facil

## Tecnologias utilizadas

- PHP
- MySQL
- PDO
- JavaScript
- Bootstrap 5
- Bootstrap Icons
- AdminLTE 4

## Estructura del proyecto

```text
escuela/
|-- api/
|   |-- student.php
|   `-- subject.php
|-- assets/
|   |-- css/
|   |   `-- app.css
|   |-- js/
|   |   `-- app.js
|   `-- vendor/
|       `-- adminlte/
|           |-- css/
|           `-- js/
|-- database/
|   `-- materias_setup.sql
|-- connectdb.php
|-- index.php
|-- README.md
`-- readme.txt
```

## Modulos del sistema

### Estudiantes

- listar estudiantes
- buscar por texto
- crear nuevos registros
- editar informacion existente
- eliminar estudiantes
- matricular materias desde un modal

### Materias

- listar materias disponibles
- crear nuevas materias
- eliminar materias
- mostrar cuantos estudiantes tiene matriculados cada curso

### Matriculas

- cada estudiante puede seleccionar hasta 3 materias
- la matricula se administra desde el boton `Matricular` en la tabla de estudiantes
- el sistema valida el limite tambien desde el backend

## Requisitos

- XAMPP o equivalente con Apache y MySQL
- PHP con soporte PDO
- MySQL activo
- navegador web moderno

## Instalacion local

1. Clona o copia el proyecto en `C:\xampp\htdocs\escuela`.
2. Inicia `Apache` y `MySQL` desde XAMPP.
3. Crea la base de datos `escuela_db`.
4. Verifica las credenciales en [connectdb.php](C:\xampp\htdocs\escuela\connectdb.php).
5. Ejecuta el script [database/materias_setup.sql](C:\xampp\htdocs\escuela\database\materias_setup.sql).
6. Abre [http://localhost/escuela/](http://localhost/escuela/).

## Endpoints principales

### API de estudiantes

- `GET api/student.php?action=listar`
- `GET api/student.php?action=siguiente_codigo`
- `GET api/student.php?action=materias_matricula&student_id=<id>`
- `POST api/student.php` con `action=crear`
- `POST api/student.php` con `action=actualizar`
- `POST api/student.php` con `action=eliminar`
- `POST api/student.php` con `action=guardar_matriculas`

### API de materias

- `GET api/subject.php?action=listar`
- `POST api/subject.php` con `action=crear`
- `POST api/subject.php` con `action=eliminar`

## Git y flujo de trabajo

Desde este punto el repositorio sigue estas reglas:

- los commits se escriben en espanol
- los mensajes deben ser claros, concretos y profesionales
- primero se implementa, luego se valida y por ultimo se hace commit y push

Ejemplos de estilo recomendado:

- `caracteristica: integrar panel academico con AdminLTE`
- `ajuste: mejorar documentacion y estructura del proyecto`
- `correccion: validar limite de materias por estudiante`

## Estado actual del proyecto

- interfaz migrada a AdminLTE
- assets necesarios de AdminLTE integrados localmente
- repositorio configurado con `.gitignore`
- documentacion principal actualizada

## Autor

Juan Camilo Ruales Ospina
