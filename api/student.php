<?php
require_once __DIR__ . "/../connectdb.php";

header("Content-Type: application/json; charset=UTF-8");

// Regla de negocio central: cada estudiante solo puede tener 3 materias.
const MAX_SUBJECTS_PER_STUDENT = 3;

// Estandariza todas las respuestas de la API para que el frontend sepa leerlas siempre igual.
function responseJson($success, $message, $extra = []) {
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));
    exit;
}

// El codigo del estudiante sigue el patron 26### por requerimiento del proyecto.
function validarCodigoEstudiante($codigo) {
    return preg_match('/^26\d{3}$/', $codigo) === 1;
}

// Genera el siguiente codigo sugerido a partir del mayor codigo registrado actualmente.
function obtenerSiguienteCodigoEstudiante(PDO $pdo) {
    $sql = "SELECT MAX(CAST(codigo_estudiante AS UNSIGNED))
            FROM student
            WHERE codigo_estudiante REGEXP '^26[0-9]{3}$'";
    $maxCodigo = (int)$pdo->query($sql)->fetchColumn();

    if ($maxCodigo < 26001) {
        return "26001";
    }

    $siguiente = $maxCodigo + 1;
    if ($siguiente > 26999) {
        return "";
    }

    return (string)$siguiente;
}

// Revisa si un codigo ya existe. En actualizacion permite excluir el mismo registro.
function codigoDisponible(PDO $pdo, $codigo, $exceptId = 0) {
    $sql = "SELECT COUNT(*) FROM student WHERE codigo_estudiante = :codigo";
    $params = [":codigo" => $codigo];

    if ($exceptId > 0) {
        $sql .= " AND id <> :id";
        $params[":id"] = $exceptId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn() === 0;
}

// Normaliza la entrada del formulario para obtener una lista unica de IDs validos.
function obtenerIdsMateriasDesdePost() {
    $subjectIds = $_POST["subject_ids"] ?? ($_POST["subject_ids[]"] ?? []);

    if (!is_array($subjectIds)) {
        $subjectIds = trim((string)$subjectIds) === "" ? [] : [$subjectIds];
    }

    $uniqueIds = [];
    foreach ($subjectIds as $subjectId) {
        $id = (int)$subjectId;
        if ($id > 0) {
            $uniqueIds[$id] = $id;
        }
    }

    return array_values($uniqueIds);
}

// Helper para construir consultas IN con placeholders seguros en PDO.
function placeholders($values, $prefix) {
    $map = [];
    foreach (array_values($values) as $index => $value) {
        $map[":" . $prefix . $index] = $value;
    }
    return $map;
}

$action = $_GET["action"] ?? $_POST["action"] ?? "";

try {
    switch ($action) {
        case "siguiente_codigo":
            // Sugiere automaticamente el proximo codigo al abrir el modal de crear estudiante.
            $siguiente = obtenerSiguienteCodigoEstudiante($pdo);
            if ($siguiente === "") {
                responseJson(false, "No hay mas codigos disponibles en el rango 26###");
            }
            responseJson(true, "OK", ["codigo_estudiante" => $siguiente]);
            break;

        case "listar":
            // Lista estudiantes y cuenta cuantas materias tiene cada uno.
            $busca = trim($_GET["busca"] ?? "");
            $sql = "SELECT s.id,
                           s.codigo_estudiante,
                           s.first_name,
                           s.last_name,
                           s.email,
                           COUNT(ss.id) AS materias_matriculadas
                    FROM student s
                    LEFT JOIN student_subject ss ON ss.student_id = s.id";
            $params = [];

            if ($busca !== "") {
                $sql .= " WHERE s.codigo_estudiante LIKE :codigo OR s.first_name LIKE :texto OR s.last_name LIKE :texto";
                $params[":codigo"] = "%" . $busca . "%";
                $params[":texto"] = "%" . $busca . "%";
            }

            $sql .= " GROUP BY s.id, s.codigo_estudiante, s.first_name, s.last_name, s.email
                      ORDER BY s.id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            responseJson(true, "OK", ["datos" => $datos]);
            break;

        case "materias_matricula":
            // Devuelve el detalle del estudiante y las materias para armar el modal de matricula.
            $studentId = (int)($_GET["student_id"] ?? 0);
            if ($studentId <= 0) {
                responseJson(false, "Estudiante invalido");
            }

            $studentStmt = $pdo->prepare("SELECT id, codigo_estudiante, first_name, last_name, email FROM student WHERE id = :id");
            $studentStmt->execute([":id" => $studentId]);
            $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                responseJson(false, "El estudiante no existe");
            }

            $sql = "SELECT sub.id,
                           sub.nombre,
                           sub.codigo,
                           sub.creditos,
                           EXISTS(
                               SELECT 1
                               FROM student_subject ss
                               WHERE ss.student_id = :student_id AND ss.subject_id = sub.id
                           ) AS matriculada
                    FROM subject sub
                    ORDER BY sub.nombre ASC, sub.id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([":student_id" => $studentId]);
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM student_subject WHERE student_id = :student_id");
            $countStmt->execute([":student_id" => $studentId]);

            responseJson(true, "OK", [
                "student" => $student,
                "subjects" => $subjects,
                "selected_count" => (int)$countStmt->fetchColumn(),
                "max_materias" => MAX_SUBJECTS_PER_STUDENT
            ]);
            break;

        case "guardar_matriculas":
            // Reemplaza la matricula completa del estudiante dentro de una transaccion.
            $studentId = (int)($_POST["student_id"] ?? 0);
            if ($studentId <= 0) {
                responseJson(false, "Estudiante invalido");
            }

            $studentStmt = $pdo->prepare("SELECT id, first_name, last_name FROM student WHERE id = :id");
            $studentStmt->execute([":id" => $studentId]);
            $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                responseJson(false, "El estudiante no existe");
            }

            $selectedSubjectIds = obtenerIdsMateriasDesdePost();

            if (count($selectedSubjectIds) > MAX_SUBJECTS_PER_STUDENT) {
                responseJson(false, "Cada estudiante solo puede matricular " . MAX_SUBJECTS_PER_STUDENT . " materias");
            }

            if ($selectedSubjectIds) {
                $subjectPlaceholders = placeholders($selectedSubjectIds, "subject");
                $subjectSql = "SELECT id FROM subject WHERE id IN (" . implode(", ", array_keys($subjectPlaceholders)) . ")";
                $subjectStmt = $pdo->prepare($subjectSql);
                $subjectStmt->execute($subjectPlaceholders);
                $existingSubjectIds = array_map("intval", $subjectStmt->fetchAll(PDO::FETCH_COLUMN));
                sort($existingSubjectIds);
                $sortedSelected = $selectedSubjectIds;
                sort($sortedSelected);

                if ($existingSubjectIds !== $sortedSelected) {
                    responseJson(false, "Una o mas materias ya no existen");
                }
            }

            $pdo->beginTransaction();
            $deleteStmt = $pdo->prepare("DELETE FROM student_subject WHERE student_id = :student_id");
            $deleteStmt->execute([":student_id" => $studentId]);

            if ($selectedSubjectIds) {
                $insertStmt = $pdo->prepare("INSERT INTO student_subject (student_id, subject_id) VALUES (:student_id, :subject_id)");
                foreach ($selectedSubjectIds as $subjectId) {
                    $insertStmt->execute([
                        ":student_id" => $studentId,
                        ":subject_id" => $subjectId
                    ]);
                }
            }

            $pdo->commit();

            responseJson(true, "Materias matriculadas correctamente");
            break;

        case "crear":
            // Crea un estudiante nuevo validando formato y unicidad del codigo.
            $codigoEstudiante = trim($_POST["codigo_estudiante"] ?? "");
            $firstName = trim($_POST["first_name"] ?? "");
            $lastName = trim($_POST["last_name"] ?? "");
            $email = trim($_POST["email"] ?? "");

            if (!validarCodigoEstudiante($codigoEstudiante)) {
                responseJson(false, "El codigo estudiante debe comenzar con 26 y tener 5 digitos");
            }

            if (!codigoDisponible($pdo, $codigoEstudiante)) {
                responseJson(false, "El codigo estudiante ya existe");
            }

            if ($firstName === "" || $lastName === "" || $email === "") {
                responseJson(false, "Todos los campos son obligatorios");
            }

            $sql = "INSERT INTO student (codigo_estudiante, first_name, last_name, email) VALUES (:codigo_estudiante, :first_name, :last_name, :email)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":codigo_estudiante" => $codigoEstudiante,
                ":first_name" => $firstName,
                ":last_name" => $lastName,
                ":email" => $email
            ]);

            responseJson(true, "Estudiante creado correctamente", ["id" => $pdo->lastInsertId()]);
            break;

        case "actualizar":
            // Actualiza un estudiante existente manteniendo la misma validacion del alta.
            $id = (int)($_POST["id"] ?? 0);
            $codigoEstudiante = trim($_POST["codigo_estudiante"] ?? "");
            $firstName = trim($_POST["first_name"] ?? "");
            $lastName = trim($_POST["last_name"] ?? "");
            $email = trim($_POST["email"] ?? "");

            if (!validarCodigoEstudiante($codigoEstudiante)) {
                responseJson(false, "El codigo estudiante debe comenzar con 26 y tener 5 digitos");
            }

            if (!codigoDisponible($pdo, $codigoEstudiante, $id)) {
                responseJson(false, "El codigo estudiante ya existe");
            }

            if ($id <= 0 || $firstName === "" || $lastName === "" || $email === "") {
                responseJson(false, "Datos invalidos para actualizar");
            }

            $sql = "UPDATE student SET codigo_estudiante = :codigo_estudiante, first_name = :first_name, last_name = :last_name, email = :email WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":id" => $id,
                ":codigo_estudiante" => $codigoEstudiante,
                ":first_name" => $firstName,
                ":last_name" => $lastName,
                ":email" => $email
            ]);

            responseJson(true, "Estudiante actualizado correctamente");
            break;

        case "eliminar":
            // Eliminar un estudiante tambien limpia sus matriculas por la relacion con cascada.
            $id = (int)($_POST["id"] ?? 0);

            if ($id <= 0) {
                responseJson(false, "ID invalido para eliminar");
            }

            $stmt = $pdo->prepare("DELETE FROM student WHERE id = :id");
            $stmt->execute([":id" => $id]);

            // Reajustamos AUTO_INCREMENT para que en este proyecto academico los IDs
            // no dejen huecos innecesarios cuando se elimina el ultimo registro.
            $nextId = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM student")->fetchColumn();
            $pdo->exec("ALTER TABLE student AUTO_INCREMENT = $nextId");

            responseJson(true, "Estudiante eliminado correctamente");
            break;

        default:
            responseJson(false, "Accion no valida");
    }
} catch (PDOException $e) {
    if ((int)$e->getCode() === 23000) {
        responseJson(false, "El codigo estudiante ya existe");
    }
    responseJson(false, "Error del servidor: " . $e->getMessage());
} catch (Throwable $e) {
    responseJson(false, "Error del servidor: " . $e->getMessage());
}
