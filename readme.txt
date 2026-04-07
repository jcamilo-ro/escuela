PROYECTO: SISTEMA ACADEMICO ESCUELA

============================================================
1) RESUMEN
============================================================
Este proyecto es un panel academico desarrollado en PHP y MySQL
para administrar estudiantes, materias y matriculas desde una
interfaz administrativa basada en AdminLTE.

El sistema permite:
- crear, editar y eliminar estudiantes
- crear y eliminar materias
- matricular materias por estudiante
- limitar cada estudiante a 3 materias
- consultar informacion en tiempo real sin recargar la pagina

============================================================
2) TECNOLOGIAS
============================================================
- PHP
- MySQL
- PDO
- JavaScript
- Bootstrap 5
- Bootstrap Icons
- AdminLTE 4

============================================================
3) ESTRUCTURA GENERAL
============================================================
Raiz del proyecto:
C:\xampp\htdocs\escuela

Archivos y carpetas principales:

- connectdb.php
  Conexion a la base de datos.

- index.php
  Vista principal del panel administrativo.

- README.md
  Documentacion principal del repositorio.

- readme.txt
  Documentacion tecnica en texto plano.

- api\
  - student.php
    API para estudiantes y matriculas.
  - subject.php
    API para materias.

- assets\
  - css\
    - app.css
      Estilos personalizados del proyecto.
  - js\
    - app.js
      Logica frontend del panel.
  - vendor\
    - adminlte\
      Archivos locales necesarios de AdminLTE.

- database\
  - materias_setup.sql
    Script base para tablas y materias iniciales.

============================================================
4) FUNCIONALIDADES
============================================================

4.1) ESTUDIANTES
- listar registros
- buscar por codigo, nombre o apellido
- crear estudiantes
- editar estudiantes
- eliminar estudiantes
- mostrar cantidad de materias matriculadas

4.2) MATERIAS
- listar materias
- crear materias
- eliminar materias
- ver cantidad de estudiantes matriculados por materia

4.3) MATRICULAS
- la matricula se hace desde el modulo de estudiantes
- cada estudiante puede matricular maximo 3 materias
- el control se valida en frontend y backend

============================================================
5) BASE DE DATOS
============================================================
Base de datos esperada:
- escuela_db

Tablas principales:
- student
- subject
- student_subject

Relacion:
- un estudiante puede tener varias materias
- una materia puede pertenecer a varios estudiantes
- la relacion se guarda en student_subject

============================================================
6) INSTALACION LOCAL
============================================================
1. Copiar el proyecto a:
   C:\xampp\htdocs\escuela

2. Iniciar Apache y MySQL desde XAMPP.

3. Crear la base de datos escuela_db.

4. Revisar credenciales en connectdb.php.

5. Ejecutar database\materias_setup.sql

6. Abrir en navegador:
   http://localhost/escuela/

============================================================
7) ENDPOINTS DISPONIBLES
============================================================

ESTUDIANTES
- GET  api/student.php?action=listar
- GET  api/student.php?action=siguiente_codigo
- GET  api/student.php?action=materias_matricula&student_id=<id>
- POST api/student.php action=crear
- POST api/student.php action=actualizar
- POST api/student.php action=eliminar
- POST api/student.php action=guardar_matriculas

MATERIAS
- GET  api/subject.php?action=listar
- POST api/subject.php action=crear
- POST api/subject.php action=eliminar

============================================================
8) ESTANDAR DE COMMITS
============================================================
El proyecto se mantiene con commits en espanol y mensajes claros.

Ejemplos recomendados:
- caracteristica: integrar panel academico con AdminLTE
- mejora: reorganizar estructura y documentacion del proyecto
- correccion: validar limite de 3 materias por estudiante

============================================================
9) ESTADO ACTUAL
============================================================
- panel visual migrado a AdminLTE
- repositorio conectado a GitHub
- documentacion mejorada
- estructura mas limpia para trabajo academico
