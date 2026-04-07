<?php
require_once __DIR__ . "/../connectdb.php";

header("Content-Type: application/json; charset=UTF-8");

// Se mantiene la misma regla tambien en esta API para cubrir flujos alternos.
const MAX_SUBJECTS_PER_STUDENT = 3;

// Respuesta uniforme para que el frontend no tenga que adivinar formatos.
function responseJson($success, $message, $extra = []) {
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));
    exit;
}

// Limpieza basica de entradas de texto para evitar espacios sobrantes.
function normalizarTexto($value) {
    return trim((string)$value);
}

// Convierte IDs enviados por formulario en un arreglo unico y seguro de enteros.
function obtenerIdsEstudiantesDesdePost() {
    $studentIds = $_POST["student_ids"] ?? ($_POST["student_ids[]"] ?? []);

    if (!is_array($studentIds)) {
        $studentIds = trim((string)$studentIds) === "" ? [] : [$studentIds];
    }

    $uniqueIds = [];
    foreach ($studentIds as $studentId) {
        $id = (int)$studentId;
        if ($id > 0) {
            $uniqueIds[$id] = $id;
        }
    }

    return array_values($uniqueIds);
}

// Helper para construir consultas IN con sentencias preparadas.
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
        case "listar":
            // Lista materias con el total de estudiantes matriculados en cada una.
            $sql = "SELECT sub.id,
                           sub.nombre,
                           sub.codigo,
                           sub.creditos,
                           COUNT(ss.id) AS estudiantes_matriculados
                    FROM subject sub
                    LEFT JOIN student_subject ss ON ss.subject_id = sub.id
                    GROUP BY sub.id, sub.nombre, sub.codigo, sub.creditos
                    ORDER BY sub.id ASC";
            $datos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            responseJson(true, "OK", ["datos" => $datos]);
            break;

        case "crear":
            // Crea una materia nueva y normaliza el codigo en mayusculas.
            $nombre = normalizarTexto($_POST["nombre"] ?? "");
            $codigo = strtoupper(normalizarTexto($_POST["codigo"] ?? ""));
            $creditos = (int)($_POST["creditos"] ?? 0);

            if ($nombre === "" || $codigo === "" || $creditos <= 0) {
                responseJson(false, "Todos los campos de la materia son obligatorios");
            }

            $stmt = $pdo->prepare("INSERT INTO subject (nombre, codigo, creditos) VALUES (:nombre, :codigo, :creditos)");
            $stmt->execute([
                ":nombre" => $nombre,
                ":codigo" => $codigo,
                ":creditos" => $creditos
            ]);

            responseJson(true, "Materia creada correctamente", ["id" => $pdo->lastInsertId()]);
            break;

        case "eliminar":
            // Elimina la materia y, por la FK, tambien se limpian sus relaciones asociadas.
            $subjectId = (int)($_POST["id"] ?? 0);
            if ($subjectId <= 0) {
                responseJson(false, "Materia invalida");
            }

            $stmt = $pdo->prepare("DELETE FROM subject WHERE id = :id");
            $stmt->execute([":id" => $subjectId]);

            if ($stmt->rowCount() === 0) {
                responseJson(false, "La materia no existe");
            }

            responseJson(true, "Materia eliminada correctamente");
            break;

        case "estudiantes_matricula":
            // Conserva el flujo inverso: ver que estudiantes tiene una materia.
            $subjectId = (int)($_GET["subject_id"] ?? 0);
            if ($subjectId <= 0) {
                responseJson(false, "Materia invalida");
            }

            $subjectStmt = $pdo->prepare("SELECT id, nombre, codigo, creditos FROM subject WHERE id = :id");
            $subjectStmt->execute([":id" => $subjectId]);
            $subject = $subjectStmt->fetch(PDO::FETCH_ASSOC);

            if (!$subject) {
                responseJson(false, "La materia no existe");
            }

            $sql = "SELECT s.id,
                           s.codigo_estudiante,
                           s.first_name,
                           s.last_name,
                           s.email,
                           (SELECT COUNT(*) FROM student_subject ss_total WHERE ss_total.student_id = s.id) AS materias_matriculadas,
                           EXISTS(
                               SELECT 1
                               FROM student_subject ss_actual
                               WHERE ss_actual.student_id = s.id AND ss_actual.subject_id = :subject_id
                           ) AS matriculado
                    FROM student s
                    ORDER BY s.first_name ASC, s.last_name ASC, s.id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([":subject_id" => $subjectId]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            responseJson(true, "OK", [
                "subject" => $subject,
                "students" => $students,
                "max_materias" => MAX_SUBJECTS_PER_STUDENT
            ]);
            break;

        case "guardar_matriculas":
            // Flujo inverso de matricula: actualiza estudiantes de una materia especifica.
            $subjectId = (int)($_POST["subject_id"] ?? 0);
            if ($subjectId <= 0) {
                responseJson(false, "Materia invalida");
            }

            $subjectStmt = $pdo->prepare("SELECT id, nombre FROM subject WHERE id = :id");
            $subjectStmt->execute([":id" => $subjectId]);
            $subject = $subjectStmt->fetch(PDO::FETCH_ASSOC);

            if (!$subject) {
                responseJson(false, "La materia no existe");
            }

            $selectedStudentIds = obtenerIdsEstudiantesDesdePost();

            $currentStmt = $pdo->prepare("SELECT student_id FROM student_subject WHERE subject_id = :subject_id");
            $currentStmt->execute([":subject_id" => $subjectId]);
            $currentStudentIds = array_map("intval", $currentStmt->fetchAll(PDO::FETCH_COLUMN));

            $currentMap = array_fill_keys($currentStudentIds, true);
            $selectedMap = array_fill_keys($selectedStudentIds, true);

            $toInsert = [];
            foreach ($selectedStudentIds as $studentId) {
                if (!isset($currentMap[$studentId])) {
                    $toInsert[] = $studentId;
                }
            }

            $toDelete = [];
            foreach ($currentStudentIds as $studentId) {
                if (!isset($selectedMap[$studentId])) {
                    $toDelete[] = $studentId;
                }
            }

            if ($selectedStudentIds) {
                $studentPlaceholders = placeholders($selectedStudentIds, "student");
                $studentSql = "SELECT id FROM student WHERE id IN (" . implode(", ", array_keys($studentPlaceholders)) . ")";
                $studentStmt = $pdo->prepare($studentSql);
                $studentStmt->execute($studentPlaceholders);
                $existingStudentIds = array_map("intval", $studentStmt->fetchAll(PDO::FETCH_COLUMN));
                sort($existingStudentIds);
                $sortedSelected = $selectedStudentIds;
                sort($sortedSelected);

                if ($existingStudentIds !== $sortedSelected) {
                    responseJson(false, "Uno o mas estudiantes ya no existen");
                }
            }

            foreach ($toInsert as $studentId) {
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM student_subject WHERE student_id = :student_id");
                $countStmt->execute([":student_id" => $studentId]);
                $count = (int)$countStmt->fetchColumn();

                if ($count >= MAX_SUBJECTS_PER_STUDENT) {
                    $nameStmt = $pdo->prepare("SELECT first_name, last_name FROM student WHERE id = :id");
                    $nameStmt->execute([":id" => $studentId]);
                    $student = $nameStmt->fetch(PDO::FETCH_ASSOC);
                    $fullName = trim(($student["first_name"] ?? "") . " " . ($student["last_name"] ?? ""));
                    responseJson(false, "El estudiante " . $fullName . " ya tiene 3/3 materias matriculadas");
                }
            }

            $pdo->beginTransaction();

            if ($toDelete) {
                $deletePlaceholders = placeholders($toDelete, "delete_student");
                $deleteSql = "DELETE FROM student_subject
                              WHERE subject_id = :subject_id
                              AND student_id IN (" . implode(", ", array_keys($deletePlaceholders)) . ")";
                $deleteStmt = $pdo->prepare($deleteSql);
                $deleteStmt->execute(array_merge([":subject_id" => $subjectId], $deletePlaceholders));
            }

            if ($toInsert) {
                $insertStmt = $pdo->prepare("INSERT INTO student_subject (student_id, subject_id) VALUES (:student_id, :subject_id)");
                foreach ($toInsert as $studentId) {
                    $insertStmt->execute([
                        ":student_id" => $studentId,
                        ":subject_id" => $subjectId
                    ]);
                }
            }

            $pdo->commit();

            responseJson(true, "Matriculas actualizadas correctamente");
            break;

        default:
            responseJson(false, "Accion no valida");
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ((int)$e->getCode() === 23000) {
        responseJson(false, "Ya existe una materia con ese nombre o codigo");
    }
    responseJson(false, "Error del servidor: " . $e->getMessage());
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    responseJson(false, "Error del servidor: " . $e->getMessage());
}
