<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
$subjects = [];
$subjectsError = "";

try {
    require_once __DIR__ . "/connectdb.php";
    $subjectSql = "SELECT sub.id,
                          sub.nombre,
                          sub.codigo,
                          sub.creditos,
                          COUNT(ss.id) AS estudiantes_matriculados
                   FROM subject sub
                   LEFT JOIN student_subject ss ON ss.subject_id = sub.id
                   GROUP BY sub.id, sub.nombre, sub.codigo, sub.creditos
                   ORDER BY sub.id ASC";
    $subjects = $pdo->query($subjectSql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $subjectsError = "No se pudieron cargar las materias";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tabla escuela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-4">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            <h1 class="h4 mb-0">Gestion de estudiantes</h1>
        </div>

        <div class="card-body">
            <div class="toolbar mb-3">
                <button id="openAddModalBtn" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                    Anadir estudiante
                </button>
                <div class="search-wrap">
                    <input
                        type="text"
                        id="searchInput"
                        class="form-control form-control-sm"
                        placeholder="Buscar por codigo o nombre"
                    >
                    <button id="searchButton" class="btn btn-outline-primary btn-sm" type="button">
                        Buscar
                    </button>
                    <button id="clearButton" class="btn btn-outline-secondary btn-sm" type="button">
                        Limpiar
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Codigo estudiante</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Email</th>
                            <th>Materias matriculadas</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTbody">
                        <tr>
                            <td colspan="6" class="text-center py-4">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h2 class="h4 mb-0">Materias de Ingenieria de Sistemas</h2>
        </div>

        <div class="card-body">
            <div class="toolbar mb-3">
                <button id="openAddSubjectModalBtn" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                    Anadir materia
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Materia</th>
                            <th>Estudiantes matriculados</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="subjectsTbody">
                        <?php if ($subjectsError !== ""): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4"><?php echo htmlspecialchars($subjectsError, ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php elseif (!$subjects): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">Sin materias</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$subject["id"], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($subject["nombre"], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-muted small">
                                            <?php echo htmlspecialchars($subject["codigo"], ENT_QUOTES, 'UTF-8'); ?> ·
                                            <?php echo htmlspecialchars((string)$subject["creditos"], ENT_QUOTES, 'UTF-8'); ?> creditos
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars((string)$subject["estudiantes_matriculados"], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-danger js-delete-subject" data-id="<?php echo htmlspecialchars((string)$subject["id"], ENT_QUOTES, 'UTF-8'); ?>">
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addForm">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar estudiante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="addStudentCode">Codigo estudiante</label>
                        <input
                            class="form-control"
                            id="addStudentCode"
                            name="codigo_estudiante"
                            placeholder="Ej: 26123"
                            pattern="26[0-9]{3}"
                            maxlength="5"
                            required
                        >
                        <div class="form-text">Se genera automaticamente, pero lo puedes editar.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="addFirstName">Nombre</label>
                        <input class="form-control" id="addFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="addLastName">Apellido</label>
                        <input class="form-control" id="addLastName" name="last_name" required>
                    </div>
                    <div>
                        <label class="form-label" for="addEmail">Email</label>
                        <input type="email" class="form-control" id="addEmail" name="email" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm">
                <div class="modal-header">
                    <h5 class="modal-title">Editar estudiante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editId" name="id">
                    <div class="mb-3">
                        <label class="form-label" for="editStudentCode">Codigo estudiante</label>
                        <input
                            class="form-control"
                            id="editStudentCode"
                            name="codigo_estudiante"
                            placeholder="Ej: 26123"
                            pattern="26[0-9]{3}"
                            maxlength="5"
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editFirstName">Nombre</label>
                        <input class="form-control" id="editFirstName" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editLastName">Apellido</label>
                        <input class="form-control" id="editLastName" name="last_name" required>
                    </div>
                    <div>
                        <label class="form-label" for="editEmail">Email</label>
                        <input type="email" class="form-control" id="editEmail" name="email" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteForm">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar estudiante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="deleteId" name="id">
                    <p class="mb-0">Estas seguro de eliminar este estudiante?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar accion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p id="confirmText" class="mb-0">Estas seguro de continuar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="confirmAccept" class="btn btn-primary">Si, continuar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addSubjectForm">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar materia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="addSubjectName">Materia</label>
                        <input class="form-control" id="addSubjectName" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="addSubjectCode">Codigo</label>
                        <input class="form-control" id="addSubjectCode" name="codigo" placeholder="Ej: SIS301" required>
                    </div>
                    <div>
                        <label class="form-label" for="addSubjectCredits">Creditos</label>
                        <input type="number" min="1" max="10" class="form-control" id="addSubjectCredits" name="creditos" value="3" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="subjectEnrollmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="subjectEnrollmentForm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">Matricular materias</h5>
                        <div class="text-muted small" id="subjectEnrollmentLabel">Selecciona hasta 3 materias para el estudiante</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="subjectEnrollmentId" name="student_id">
                    <div class="alert alert-info py-2 small mb-3">
                        Maximo permitido por estudiante: <strong>3 materias</strong>.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Seleccionar</th>
                                    <th>Codigo</th>
                                    <th>Materia</th>
                                    <th>Creditos</th>
                                </tr>
                            </thead>
                            <tbody id="subjectEnrollmentTbody">
                                <tr>
                                    <td colspan="4" class="text-center py-4">Cargando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar materias</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js?v=20260406-2124"></script>
<script>
window.setTimeout(async () => {
    const tbody = document.getElementById("subjectsTbody");
    if (!tbody || window.__subjectsLoaded) {
        return;
    }

    try {
        const response = await fetch("api/subject.php?action=listar");
        const data = await response.json();

        if (!data.success || !Array.isArray(data.datos)) {
            throw new Error(data.message || "No se pudieron cargar las materias");
        }

        tbody.innerHTML = data.datos.map((subject) => `
            <tr>
                <td>${String(subject.id)}</td>
                <td>
                    <div class="fw-semibold">${String(subject.nombre)}</div>
                    <div class="text-muted small">${String(subject.codigo)} · ${String(subject.creditos)} creditos</div>
                </td>
                <td>${String(subject.estudiantes_matriculados || 0)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-danger js-delete-subject" data-id="${String(subject.id)}">
                        Eliminar
                    </button>
                </td>
            </tr>
        `).join("");

        window.__subjectsLoaded = true;
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4">No se pudieron cargar las materias</td></tr>';
    }
}, 1200);
</script>
</body>
</html>
