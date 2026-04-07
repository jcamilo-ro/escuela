PROYECTO: CRUD DE ESTUDIANTES (PHP + MYSQL + FETCH + BOOTSTRAP)

============================================================
1) VISION GENERAL
============================================================
Este proyecto implementa un CRUD (Crear, Leer, Actualizar, Eliminar) de estudiantes.
La interfaz se muestra en index.php y todas las operaciones se hacen con fetch
contra una API en PHP (api/student.php), sin recargar la pagina.

Caracteristicas actuales:
- Tabla dinamica con busqueda por codigo o nombre.
- Campo visible principal: codigo_estudiante (el ID tecnico no se muestra).
- Modales para agregar, editar y eliminar.
- Toasts de exito/error en la esquina inferior derecha.
- Confirmacion extra para actualizar.
- Codigo estudiante editable, obligatorio y sin repetidos.

Tecnologias usadas:
- PHP (backend + API)
- MySQL (base de datos)
- PDO (conexion segura)
- JavaScript fetch (frontend dinamico)
- Bootstrap 5 + Bootstrap Icons
- CSS propio

============================================================
2) ESTRUCTURA DE CARPETAS Y ARCHIVOS
============================================================
Raiz: C:\xampp\htdocs\escuela

- connectdb.php
  Conexion PDO a MySQL.

- index.php
  Vista principal (estructura HTML + modales + links a CSS/JS).

- readme.txt
  Documentacion del proyecto.

- api/
  - student.php
    Endpoint unico del CRUD y utilidades de codigo_estudiante.

- assets/
  - css/
    - app.css
      Estilos personalizados.

  - js/
    - app.js
      Logica frontend (fetch, render, eventos, toasts, modales).

============================================================
3) BASE DE DATOS
============================================================
Base configurada: escuela_db
Tabla: student

Campos usados:
- id (INT, PRIMARY KEY, AUTO_INCREMENT) [interno, oculto en tabla]
- codigo_estudiante (VARCHAR(5)) formato 26###
- first_name
- last_name
- email

Notas:
- El sistema intenta crear la columna codigo_estudiante si no existe.
- El sistema intenta crear indice UNIQUE para codigo_estudiante
  (solo si no hay duplicados previos).

============================================================
4) EXPLICACION POR ARCHIVO
============================================================

------------------------------------------------------------
4.1) connectdb.php
------------------------------------------------------------
Objetivo:
- Crear la conexion PDO y exponer $pdo.

Bloques:
1. Credenciales de DB.
2. Creacion de PDO con charset utf8.
3. Error mode en excepciones.
4. try/catch con mensaje de error si falla conexion.

------------------------------------------------------------
4.2) index.php
------------------------------------------------------------
Objetivo:
- Estructura visual principal.

Bloques:
1. Head:
   - Bootstrap CSS
   - Bootstrap Icons
   - assets/css/app.css

2. Card principal:
   - Titulo Gestion de estudiantes
   - Toolbar con boton Anadir + buscador

3. Tabla:
   - Columnas: Codigo estudiante, Nombre, Apellido, Email, Acciones
   - tbody dinamico: id="studentsTbody"

4. Modales:
   - addModal (crear)
   - editModal (editar)
   - deleteModal (eliminar)
   - confirmModal (confirmacion extra para actualizar)

5. Toast container:
   - Contenedor para mensajes abajo-derecha.

6. Scripts:
   - Bootstrap bundle
   - assets/js/app.js

------------------------------------------------------------
4.3) assets/js/app.js
------------------------------------------------------------
Objetivo:
- Manejar toda la logica del frontend.

Funciones clave:
1. showToast(message, type)
   - Muestra mensajes de exito/error.

2. confirmarAccion(message)
   - Muestra confirmModal y devuelve true/false.

3. escapeHtml(value)
   - Escapa contenido para evitar XSS al renderizar.

4. cargarDatos()
   - Llama a API listar con busca y renderiza tabla.

5. obtenerSiguienteCodigo()
   - Llama a action=siguiente_codigo para sugerir codigo en agregar.

6. renderTabla(datos)
   - Pinta filas y botones de editar/eliminar con iconos.

7. abrirModalEditar(registro)
   - Carga datos en formulario de edicion.

8. abrirModalEliminar(id)
   - Carga id en formulario de eliminacion.

9. Eventos de formularios:
   - addForm: crear
   - editForm: confirmar + actualizar
   - deleteForm: eliminar

10. Validacion frontend de codigo_estudiante:
   - Regex: /^26\d{3}$/

11. Busqueda:
   - Boton Buscar, Enter y Limpiar.

12. Init:
   - DOMContentLoaded => cargarDatos()

------------------------------------------------------------
4.4) api/student.php
------------------------------------------------------------
Objetivo:
- Resolver todas las acciones backend y responder JSON.

Utilidades internas:
1. responseJson(...)
   - Respuesta estandar JSON.

2. validarCodigoEstudiante($codigo)
   - Valida formato 26###.

3. obtenerSiguienteCodigoEstudiante($pdo)
   - Busca maximo codigo valido y sugiere +1.
   - Rango contemplado: 26001 a 26999.

4. codigoDisponible($pdo, $codigo, $exceptId=0)
   - Verifica no repetido.

5. asegurarColumnaCodigo($pdo)
   - Crea columna si falta.

6. asegurarIndiceUnicoCodigo($pdo)
   - Crea indice UNIQUE si no hay duplicados previos.

Acciones API:
A) action=listar (GET)
- Lista con filtros por codigo_estudiante, first_name o last_name.
- Ordenado por id ASC.

B) action=siguiente_codigo (GET)
- Devuelve codigo sugerido incremental.

C) action=crear (POST)
- Valida codigo 26###.
- Valida no repetido.
- Inserta registro.

D) action=actualizar (POST)
- Valida id.
- Valida codigo 26###.
- Valida no repetido (excepto el propio registro).
- Actualiza registro.

E) action=eliminar (POST)
- Elimina por id.
- Reajusta AUTO_INCREMENT a MAX(id)+1.

Manejo de errores:
- Captura PDOException y Throwable.
- Si es duplicado (23000), responde mensaje de codigo repetido.

------------------------------------------------------------
4.5) assets/css/app.css
------------------------------------------------------------
Objetivo:
- Dar estilo visual consistente.

Bloques:
1. Fondo general.
2. Card y cabecera.
3. Toolbar y buscador.
4. Tabla y hover de filas.
5. Botones de iconos.
6. Estilo de modales.
7. Estilo de toasts.
8. Ajustes responsive.

============================================================
5) SERVICIOS API DISPONIBLES
============================================================
GET  api/student.php?action=listar&busca=<texto>
GET  api/student.php?action=siguiente_codigo
POST api/student.php  action=crear
POST api/student.php  action=actualizar
POST api/student.php  action=eliminar

Respuesta base JSON:
- success: true/false
- message: texto
- datos/codigo_estudiante/id segun accion

============================================================
6) REGLAS DE CODIGO ESTUDIANTE
============================================================
1. Formato obligatorio: 26### (5 digitos).
2. Debe ser unico (sin repetidos).
3. Es editable por usuario.
4. En Agregar se autocompleta con sugerencia (max + 1).
5. Si quieres, puedes cambiar manualmente el sugerido antes de guardar.

============================================================
7) FLUJO CRUD (RESUMEN)
============================================================
1. Carga pagina -> cargarDatos() -> listar.
2. Agregar -> codigo sugerido -> crear -> refresca tabla.
3. Editar -> confirmacion -> actualizar -> refresca tabla.
4. Eliminar -> confirmacion modal eliminar -> elimina -> refresca tabla.
5. Todas las respuestas muestran toast.

============================================================
8) EJECUCION RAPIDA
============================================================
1. Inicia Apache y MySQL en XAMPP.
2. Verifica DB escuela_db y tabla student.
3. Revisa credenciales en connectdb.php.
4. Abre:
   http://localhost/escuela/

============================================================
FIN DEL README
============================================================
