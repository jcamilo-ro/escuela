<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$subjects = [];
$subjectsError = "";
$studentCount = 0;
$subjectCount = 0;
$enrollmentCount = 0;

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
    $studentCount = (int)$pdo->query("SELECT COUNT(*) FROM student")->fetchColumn();
    $subjectCount = (int)$pdo->query("SELECT COUNT(*) FROM subject")->fetchColumn();
    $enrollmentCount = (int)$pdo->query("SELECT COUNT(*) FROM student_subject")->fetchColumn();
} catch (Throwable $e) {
    $subjectsError = "No se pudieron cargar las materias";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Escuela | Panel Academico</title>
    <meta name="color-scheme" content="light dark">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/vendor/adminlte/css/adminlte.css">
    <link rel="stylesheet" href="assets/css/app.css?v=20260406-adminlte">
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <i class="bi bi-list"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a href="#dashboard" class="nav-link js-section-nav" data-section="dashboard">Dashboard</a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a href="#panel-estudiantes" class="nav-link js-section-nav" data-section="panel-estudiantes">Estudiantes</a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a href="#panel-materias" class="nav-link js-section-nav" data-section="panel-materias">Materias</a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item d-none d-md-flex align-items-center me-3">
                    <span class="text-secondary small">Proyecto academico con AdminLTE</span>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link border-0 bg-transparent" id="sidebarPinToggle" title="Fijar menu lateral">
                        <i class="bi bi-pin-angle" id="sidebarPinIcon"></i>
                    </button>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                        <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                        <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display:none"></i>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <aside class="app-sidebar bg-primary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="index.php" class="brand-link">
                <span class="brand-image adminlte-mark">
                    <i class="bi bi-mortarboard-fill"></i>
                </span>
                <span class="brand-text fw-semibold">Escuela Admin</span>
            </a>
        </div>

        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <ul
                    class="nav sidebar-menu flex-column"
                    data-lte-toggle="treeview"
                    role="navigation"
                    aria-label="Main navigation"
                    data-accordion="false"
                >
                    <li class="nav-item">
                        <a href="#dashboard" class="nav-link active js-section-nav" data-section="dashboard">
                            <i class="nav-icon bi bi-grid-1x2-fill"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#panel-estudiantes" class="nav-link js-section-nav" data-section="panel-estudiantes">
                            <i class="nav-icon bi bi-people-fill"></i>
                            <p>Estudiantes</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#panel-materias" class="nav-link js-section-nav" data-section="panel-materias">
                            <i class="nav-icon bi bi-journal-bookmark-fill"></i>
                            <p>Materias</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="https://github.com/jcamilo-ro/escuela" class="nav-link" target="_blank" rel="noreferrer">
                            <i class="nav-icon bi bi-github"></i>
                            <p>Repositorio</p>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="sidebar-helper px-3 pb-3">
                <div class="sidebar-helper-card">
                    <i class="bi bi-cursor-fill me-2"></i>
                    <span>Pasa el cursor para expandir el menu o fijalo con el pin superior.</span>
                </div>
            </div>
        </div>
    </aside>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h1 class="mb-0">Gestion academica</h1>
                        <p class="text-secondary mb-0">Administra estudiantes, materias y matriculas desde un solo panel.</p>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end mb-0">
                            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                            <li class="breadcrumb-item active" aria-current="page" id="currentSectionLabel">Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">
                <section id="dashboard" class="mb-4 app-panel-section" data-section-title="Dashboard">
                    <div class="row g-3">
                        <div class="col-md-6 col-xl-3">
                            <div class="small-box text-bg-primary h-100">
                                <div class="inner">
                                    <h3><?php echo htmlspecialchars((string)$studentCount, ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p>Estudiantes registrados</p>
                                </div>
                                <i class="small-box-icon bi bi-people-fill"></i>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="small-box text-bg-success h-100">
                                <div class="inner">
                                    <h3><?php echo htmlspecialchars((string)$subjectCount, ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p>Materias disponibles</p>
                                </div>
                                <i class="small-box-icon bi bi-journal-bookmark-fill"></i>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="small-box text-bg-warning h-100">
                                <div class="inner">
                                    <h3><?php echo htmlspecialchars((string)$enrollmentCount, ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p>Matriculas activas</p>
                                </div>
                                <i class="small-box-icon bi bi-clipboard2-check-fill"></i>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="small-box text-bg-danger h-100">
                                <div class="inner">
                                    <h3>3</h3>
                                    <p>Maximo de materias por estudiante</p>
                                </div>
                                <i class="small-box-icon bi bi-exclamation-diamond-fill"></i>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="panel-estudiantes" class="mb-4 app-panel-section d-none" data-section-title="Estudiantes">
                    <div class="card card-outline card-primary shadow-sm">
                        <div class="card-header border-0">
                            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                                <div>
                                    <h3 class="card-title mb-1 fw-semibold">Panel de estudiantes</h3>
                                    <div class="text-secondary small">Control de registros, busqueda y matricula de materias.</div>
                                </div>
                                <button id="openAddModalBtn" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                                    <i class="bi bi-person-plus-fill me-1"></i>
                                    Anadir estudiante
                                </button>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            <div class="toolbar mb-3">
                                <div class="search-wrap w-100">
                                    <div class="input-group input-group-lg search-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input
                                            type="text"
                                            id="searchInput"
                                            class="form-control"
                                            placeholder="Buscar por codigo, nombre o apellido"
                                        >
                                        <button id="searchButton" class="btn btn-primary" type="button">Buscar</button>
                                        <button id="clearButton" class="btn btn-outline-secondary" type="button">Limpiar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 app-table">
                                    <thead>
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
                </section>

                <section id="panel-materias" class="mb-4 app-panel-section d-none" data-section-title="Materias">
                    <div class="card card-outline card-success shadow-sm">
                        <div class="card-header border-0">
                            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                                <div>
                                    <h3 class="card-title mb-1 fw-semibold">Catalogo de materias</h3>
                                    <div class="text-secondary small">Las matriculas se gestionan desde el panel de estudiantes.</div>
                                </div>
                                <button id="openAddSubjectModalBtn" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                                    <i class="bi bi-journal-plus me-1"></i>
                                    Anadir materia
                                </button>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 app-table">
                                    <thead>
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
                                                        <div class="text-secondary small">
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
                </section>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <strong>
            Sistema academico Escuela &copy; 2026
        </strong>
        <div class="float-end d-none d-sm-inline">
            Basado en AdminLTE 4 y Bootstrap 5
        </div>
    </footer>
</div>

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
                        <input class="form-control" id="addStudentCode" name="codigo_estudiante" placeholder="Ej: 26123" pattern="26[0-9]{3}" maxlength="5" required>
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
                    <button type="submit" class="btn btn-primary">Guardar</button>
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
                        <input class="form-control" id="editStudentCode" name="codigo_estudiante" placeholder="Ej: 26123" pattern="26[0-9]{3}" maxlength="5" required>
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
                        <div class="text-secondary small" id="subjectEnrollmentLabel">Selecciona hasta 3 materias para el estudiante</div>
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

<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/adminlte/js/adminlte.js"></script>
<script src="assets/js/app.js?v=20260406-adminlte"></script>
<script>
const SELECTOR_SIDEBAR_WRAPPER = ".sidebar-wrapper";
document.addEventListener("DOMContentLoaded", () => {
    const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
    const isMobile = window.innerWidth <= 992;
    const body = document.body;
    const appSidebar = document.querySelector(".app-sidebar");
    const pinToggle = document.getElementById("sidebarPinToggle");
    const pinIcon = document.getElementById("sidebarPinIcon");
    const sectionLinks = document.querySelectorAll(".js-section-nav[data-section]");
    const sections = document.querySelectorAll(".app-panel-section");
    const currentSectionLabel = document.getElementById("currentSectionLabel");
    let sidebarPinned = false;

    if (sidebarWrapper && window.OverlayScrollbarsGlobal?.OverlayScrollbars && !isMobile) {
        window.OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
                theme: "os-theme-light",
                autoHide: "leave",
                clickScroll: true
            }
        });
    }

    const setSidebarPinnedState = (pinned) => {
        sidebarPinned = pinned;
        pinIcon.className = pinned ? "bi bi-pin-angle-fill" : "bi bi-pin-angle";
        pinToggle.classList.toggle("text-primary", pinned);
        if (!isMobile) {
            body.classList.toggle("sidebar-collapse", !pinned);
        }
    };

    if (pinToggle) {
        pinToggle.addEventListener("click", () => {
            setSidebarPinnedState(!sidebarPinned);
        });
    }

    if (appSidebar && !isMobile) {
        appSidebar.addEventListener("mouseenter", () => {
            if (!sidebarPinned) {
                body.classList.remove("sidebar-collapse");
            }
        });

        appSidebar.addEventListener("mouseleave", () => {
            if (!sidebarPinned) {
                body.classList.add("sidebar-collapse");
            }
        });
    }

    const activateSection = (sectionId) => {
        sections.forEach((section) => {
            section.classList.toggle("d-none", section.id !== sectionId);
        });

        sectionLinks.forEach((link) => {
            link.classList.toggle("active", link.dataset.section === sectionId);
        });

        const currentSection = document.getElementById(sectionId);
        if (currentSection && currentSectionLabel) {
            currentSectionLabel.textContent = currentSection.dataset.sectionTitle || "Dashboard";
        }
        window.location.hash = sectionId;
    };

    sectionLinks.forEach((link) => {
        link.addEventListener("click", (event) => {
            event.preventDefault();
            activateSection(link.dataset.section);
        });
    });

    const initialSection = window.location.hash.replace("#", "");
    if (initialSection && document.getElementById(initialSection)) {
        activateSection(initialSection);
    } else {
        activateSection("dashboard");
    }

    setSidebarPinnedState(false);
});
</script>
</body>
</html>
