# Escuela

Aplicacion web en PHP y MySQL para gestionar estudiantes, materias y matriculas desde una interfaz sencilla con Bootstrap.

## Caracteristicas

- CRUD de estudiantes
- CRUD basico de materias
- Matricula de materias por estudiante
- Limite de 3 materias por estudiante
- Busqueda de estudiantes por codigo o nombre
- Interfaz dinamica con `fetch` sin recargar la pagina

## Tecnologias

- PHP
- MySQL
- PDO
- JavaScript
- Bootstrap 5
- Bootstrap Icons

## Estructura del proyecto

```text
escuela/
|-- api/
|   |-- student.php
|   `-- subject.php
|-- assets/
|   |-- css/
|   |   `-- app.css
|   `-- js/
|       `-- app.js
|-- database/
|   `-- materias_setup.sql
|-- connectdb.php
|-- index.php
|-- README.md
`-- readme.txt
```

## Requisitos

- XAMPP o un entorno local con Apache y MySQL
- PHP 8 o compatible con PDO
- Base de datos MySQL disponible

## Configuracion local

1. Copia el proyecto dentro de `C:\xampp\htdocs\escuela`.
2. Inicia `Apache` y `MySQL` desde XAMPP.
3. Crea la base de datos `escuela_db`.
4. Revisa las credenciales en [connectdb.php](C:\xampp\htdocs\escuela\connectdb.php).
5. Ejecuta el script [database/materias_setup.sql](C:\xampp\htdocs\escuela\database\materias_setup.sql) para crear las tablas y materias base.
6. Abre la aplicacion en [http://localhost/escuela/](http://localhost/escuela/).

## Endpoints principales

### Estudiantes

- `GET api/student.php?action=listar`
- `GET api/student.php?action=siguiente_codigo`
- `GET api/student.php?action=materias_matricula&student_id=<id>`
- `POST api/student.php` con `action=crear`
- `POST api/student.php` con `action=actualizar`
- `POST api/student.php` con `action=eliminar`
- `POST api/student.php` con `action=guardar_matriculas`

### Materias

- `GET api/subject.php?action=listar`
- `POST api/subject.php` con `action=crear`
- `POST api/subject.php` con `action=eliminar`

## Flujo de matricula

- Cada estudiante puede matricular hasta 3 materias.
- La seleccion de materias se realiza desde la tabla de estudiantes.
- La tabla de materias muestra el total de estudiantes matriculados por materia.

## Autor

Juan Camilo Ruales Ospina
