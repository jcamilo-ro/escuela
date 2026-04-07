const STUDENT_API = "api/student.php";
const SUBJECT_API = "api/subject.php";
const MAX_SUBJECTS_PER_STUDENT = 3;
const API_TIMEOUT_MS = 8000;

const studentsTbody = document.getElementById("studentsTbody");
const subjectsTbody = document.getElementById("subjectsTbody");
const subjectEnrollmentTbody = document.getElementById("subjectEnrollmentTbody");
const searchInput = document.getElementById("searchInput");
const searchButton = document.getElementById("searchButton");
const clearButton = document.getElementById("clearButton");
const openAddModalBtn = document.getElementById("openAddModalBtn");
const openAddSubjectModalBtn = document.getElementById("openAddSubjectModalBtn");
const toastContainer = document.getElementById("toastContainer");
const confirmText = document.getElementById("confirmText");
const confirmAccept = document.getElementById("confirmAccept");
const confirmModalElement = document.getElementById("confirmModal");
const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalElement);
const subjectEnrollmentModalElement = document.getElementById("subjectEnrollmentModal");
const subjectEnrollmentModal = bootstrap.Modal.getOrCreateInstance(subjectEnrollmentModalElement);
const subjectEnrollmentLabel = document.getElementById("subjectEnrollmentLabel");
const subjectEnrollmentId = document.getElementById("subjectEnrollmentId");

let studentsCache = [];
let searchTerm = "";
const studentCodeRegex = /^26\d{3}$/;
window.__subjectsLoaded = false;

// Utilidad simple para mostrar mensajes temporales dentro de una tabla.
function setTableMessage(tbody, colspan, message) {
    tbody.innerHTML = `<tr><td colspan="${colspan}" class="text-center py-4">${escapeHtml(message)}</td></tr>`;
}

// Estado vacio mas visual para que la tabla no quede "muerta" cuando no hay registros.
function setTableEmptyState(tbody, colspan, icon, title, description) {
    tbody.innerHTML = `
        <tr>
            <td colspan="${colspan}" class="py-4">
                <div class="empty-state">
                    <div class="empty-state__icon">
                        <i class="bi ${escapeHtml(icon)}"></i>
                    </div>
                    <div class="empty-state__title">${escapeHtml(title)}</div>
                    <div class="empty-state__text">${escapeHtml(description)}</div>
                </div>
            </td>
        </tr>
    `;
}

// Toast reutilizable para feedback rapido de exito, error o advertencia.
function showToast(message, type = "success") {
    const toast = document.createElement("div");
    toast.className = "toast align-items-center border-0 text-bg-" + type;
    toast.role = "alert";
    toast.ariaLive = "assertive";
    toast.ariaAtomic = "true";
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${escapeHtml(message)}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 2800 });
    bsToast.show();
    toast.addEventListener("hidden.bs.toast", () => toast.remove());
}

// Confirmacion generica basada en un modal central y usada con async/await.
function confirmarAccion(message) {
    confirmText.textContent = message;
    confirmModal.show();

    return new Promise((resolve) => {
        let decided = false;

        const onAccept = () => {
            decided = true;
            cleanup();
            confirmModal.hide();
            resolve(true);
        };

        const onHidden = () => {
            if (!decided) {
                cleanup();
                resolve(false);
            }
        };

        const cleanup = () => {
            confirmAccept.removeEventListener("click", onAccept);
            confirmModalElement.removeEventListener("hidden.bs.modal", onHidden);
        };

        confirmAccept.addEventListener("click", onAccept);
        confirmModalElement.addEventListener("hidden.bs.modal", onHidden);
    });
}

// Escapamos HTML antes de insertarlo en plantillas para evitar inyecciones en la vista.
function escapeHtml(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

// Normaliza el codigo para validar siempre el mismo formato.
function normalizarCodigo(codigo) {
    return String(codigo || "").trim();
}

// Presentacion visual del avance de matricula por estudiante.
function formatEnrollmentCount(count) {
    return `${Number(count)}/${MAX_SUBJECTS_PER_STUDENT}`;
}

// El color cambia segun el estado: sin materias, en progreso o en limite.
function getEnrollmentBadgeClass(count) {
    const numericCount = Number(count || 0);
    if (numericCount >= MAX_SUBJECTS_PER_STUDENT) {
        return "enrollment-badge--limit";
    }
    if (numericCount > 0) {
        return "enrollment-badge--progress";
    }
    return "enrollment-badge--empty";
}

// Se usan iniciales para darle identidad visual a cada fila sin depender de imagenes.
function getStudentInitials(student) {
    const first = String(student.first_name || "").trim().charAt(0);
    const last = String(student.last_name || "").trim().charAt(0);
    return (first + last).toUpperCase() || "ST";
}

// Cuando se llega al limite de materias, bloqueamos el resto de checkboxes.
function syncSubjectCheckboxLimit() {
    const checkboxes = Array.from(subjectEnrollmentTbody.querySelectorAll('input[name="subject_ids[]"]'));
    const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

    checkboxes.forEach((checkbox) => {
        checkbox.disabled = !checkbox.checked && selectedCount >= MAX_SUBJECTS_PER_STUDENT;
    });
}

// Wrapper comun para llamadas a la API. Agrega timeout y parseo consistente de JSON.
async function fetchJson(url, options) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), API_TIMEOUT_MS);
    const requestOptions = {
        ...options,
        signal: controller.signal
    };

    let response;
    try {
        response = await fetch(url, requestOptions);
    } catch (error) {
        if (error.name === "AbortError") {
            throw new Error("La API tardo demasiado en responder");
        }
        throw error;
    } finally {
        clearTimeout(timeoutId);
    }

    const raw = await response.text();
    let data;

    try {
        data = JSON.parse(raw);
    } catch (error) {
        throw new Error(raw || "Respuesta invalida del servidor");
    }

    if (!response.ok) {
        throw new Error(data.message || "Error HTTP " + response.status);
    }

    return data;
}

// Carga la tabla de estudiantes desde la API y aplica el filtro de busqueda si existe.
async function cargarEstudiantes() {
    setTableMessage(studentsTbody, 6, "Cargando...");

    try {
        const query = new URLSearchParams({
            action: "listar",
            busca: searchTerm
        });
        const data = await fetchJson(`${STUDENT_API}?${query.toString()}`);

        if (!data.success) {
            setTableMessage(studentsTbody, 6, data.message || "No se pudo cargar la tabla");
            showToast(data.message || "No se pudo cargar la tabla", "danger");
            return;
        }

        renderTablaEstudiantes(Array.isArray(data.datos) ? data.datos : []);
    } catch (error) {
        setTableMessage(studentsTbody, 6, "No se pudo cargar la tabla");
        showToast(error.message || "Error de conexion con la API", "danger");
    }
}

// Carga la tabla de materias desde la API correspondiente.
async function cargarMaterias() {
    setTableMessage(subjectsTbody, 4, "Cargando...");

    try {
        const data = await fetchJson(`${SUBJECT_API}?action=listar`);

        if (!data.success) {
            setTableMessage(subjectsTbody, 4, data.message || "No se pudieron cargar las materias");
            showToast(data.message || "No se pudieron cargar las materias", "danger");
            return;
        }

        renderTablaMaterias(Array.isArray(data.datos) ? data.datos : []);
    } catch (error) {
        setTableMessage(subjectsTbody, 4, "No se pudieron cargar las materias");
        showToast(error.message || "Error de conexion con la API", "danger");
    }
}

// Refrescamos ambos paneles cuando una operacion afecta el estado global del sistema.
async function cargarTodo() {
    await Promise.all([cargarEstudiantes(), cargarMaterias()]);
}

// Pide al backend el siguiente codigo sugerido para no depender de calculos locales.
async function obtenerSiguienteCodigo() {
    try {
        const data = await fetchJson(`${STUDENT_API}?action=siguiente_codigo`);
        if (data.success && data.codigo_estudiante) {
            return String(data.codigo_estudiante);
        }
    } catch (error) {
        // Si falla el servicio, no interrumpe el flujo del modal.
    }
    return "";
}

// Convierte la respuesta JSON en filas HTML para la tabla de estudiantes.
function renderTablaEstudiantes(datos) {
    studentsCache = datos;

    if (!datos.length) {
        setTableEmptyState(
            studentsTbody,
            6,
            "bi-people",
            "No hay estudiantes registrados",
            "Agrega un estudiante para comenzar a gestionar matriculas y seguimiento academico."
        );
        return;
    }

    studentsTbody.innerHTML = datos.map((student) => `
        <tr>
            <td><span class="table-code">${escapeHtml(student.codigo_estudiante || "")}</span></td>
            <td>
                <div class="person-cell">
                    <div class="person-cell__avatar">${escapeHtml(getStudentInitials(student))}</div>
                    <div>
                        <div class="person-cell__name">${escapeHtml(student.first_name)}</div>
                        <div class="person-cell__meta">Registro academico</div>
                    </div>
                </div>
            </td>
            <td><span class="table-strong">${escapeHtml(student.last_name)}</span></td>
            <td>
                <span class="email-pill">
                    <i class="bi bi-envelope-paper me-1"></i>
                    ${escapeHtml(student.email)}
                </span>
            </td>
            <td>
                <span class="enrollment-badge ${getEnrollmentBadgeClass(student.materias_matriculadas)}">
                    ${formatEnrollmentCount(student.materias_matriculadas || 0)}
                </span>
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary me-1 js-manage-enrollment btn-premium-soft" data-id="${escapeHtml(student.id)}">
                    Matricular
                </button>
                <button class="btn btn-sm btn-primary me-1 js-edit-student action-icon-btn" title="Editar" aria-label="Editar" data-id="${escapeHtml(student.id)}">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <button class="btn btn-sm btn-danger js-delete-student action-icon-btn" title="Eliminar" aria-label="Eliminar" data-id="${escapeHtml(student.id)}">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </td>
        </tr>
    `).join("");
}

// Convierte la lista de materias en una tabla mas visual y resumida.
function renderTablaMaterias(datos) {
    window.__subjectsLoaded = true;

    if (!datos.length) {
        setTableEmptyState(
            subjectsTbody,
            4,
            "bi-journal-x",
            "No hay materias disponibles",
            "Crea una materia nueva para habilitar matriculas dentro del sistema."
        );
        return;
    }

    subjectsTbody.innerHTML = datos.map((subject) => `
        <tr>
            <td><span class="table-code">#${escapeHtml(subject.id)}</span></td>
            <td>
                <div class="subject-cell">
                    <div class="subject-cell__icon">
                        <i class="bi bi-journal-richtext"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">${escapeHtml(subject.nombre)}</div>
                        <div class="text-muted small">${escapeHtml(subject.codigo)} · ${escapeHtml(subject.creditos)} creditos</div>
                    </div>
                </div>
            </td>
            <td>
                <span class="subject-count-badge">
                    <i class="bi bi-people-fill me-1"></i>
                    ${escapeHtml(subject.estudiantes_matriculados || 0)}
                </span>
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger js-delete-subject btn-premium-soft-danger" data-id="${escapeHtml(subject.id)}">
                    Eliminar
                </button>
            </td>
        </tr>
    `).join("");
}

// Prepara el modal de edicion con los datos ya existentes del estudiante.
function abrirModalEditar(registro) {
    document.getElementById("editId").value = registro.id;
    document.getElementById("editStudentCode").value = registro.codigo_estudiante || "";
    document.getElementById("editFirstName").value = registro.first_name;
    document.getElementById("editLastName").value = registro.last_name;
    document.getElementById("editEmail").value = registro.email;
    bootstrap.Modal.getOrCreateInstance(document.getElementById("editModal")).show();
}

// Solo asigna el ID y abre el modal; la confirmacion real ocurre al enviar el formulario.
function abrirModalEliminar(id) {
    document.getElementById("deleteId").value = id;
    bootstrap.Modal.getOrCreateInstance(document.getElementById("deleteModal")).show();
}

// Dibuja la tabla de materias dentro del modal de matricula por estudiante.
function renderEnrollmentSubjects(subjects, maxMaterias) {
    if (!subjects.length) {
        setTableEmptyState(
            subjectEnrollmentTbody,
            4,
            "bi-journal-text",
            "No hay materias para matricular",
            "Primero crea materias en el catalogo para poder asignarlas a los estudiantes."
        );
        return;
    }

    subjectEnrollmentTbody.innerHTML = subjects.map((subject) => {
        const matriculada = Number(subject.matriculada) === 1;

        return `
            <tr>
                <td>
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="subject_ids[]"
                        value="${escapeHtml(subject.id)}"
                        ${matriculada ? "checked" : ""}
                    >
                </td>
                <td><span class="table-code">${escapeHtml(subject.codigo || "")}</span></td>
                <td><span class="table-strong">${escapeHtml(subject.nombre || "")}</span></td>
                <td>${escapeHtml(subject.creditos || 0)}</td>
            </tr>
        `;
    }).join("");

    syncSubjectCheckboxLimit();

    subjectEnrollmentTbody.querySelectorAll('input[name="subject_ids[]"]').forEach((checkbox) => {
        checkbox.addEventListener("change", () => {
            syncSubjectCheckboxLimit();
            const selectedCount = subjectEnrollmentTbody.querySelectorAll('input[name="subject_ids[]"]:checked').length;
            if (selectedCount > maxMaterias) {
                checkbox.checked = false;
                syncSubjectCheckboxLimit();
                showToast(`Cada estudiante solo puede matricular ${maxMaterias} materias`, "danger");
            }
        });
    });
}

// Abre el modal, consulta al backend y arma la vista editable de matriculas.
async function abrirModalMatricula(studentId) {
    setTableMessage(subjectEnrollmentTbody, 4, "Cargando...");
    subjectEnrollmentLabel.textContent = "Selecciona hasta 3 materias para el estudiante";
    subjectEnrollmentId.value = studentId;
    subjectEnrollmentModal.show();

    try {
        const data = await fetchJson(`${STUDENT_API}?action=materias_matricula&student_id=${encodeURIComponent(studentId)}`);

        if (!data.success) {
            setTableMessage(subjectEnrollmentTbody, 4, data.message || "No se pudo cargar la matricula");
            showToast(data.message || "No se pudo cargar la matricula", "danger");
            return;
        }

        const student = data.student || {};
        const maxMaterias = Number(data.max_materias || MAX_SUBJECTS_PER_STUDENT);
        const fullName = `${student.first_name || ""} ${student.last_name || ""}`.trim();
        subjectEnrollmentLabel.textContent = `${fullName} (${student.codigo_estudiante || ""}) - maximo ${maxMaterias} materias`;
        renderEnrollmentSubjects(Array.isArray(data.subjects) ? data.subjects : [], maxMaterias);
    } catch (error) {
        setTableMessage(subjectEnrollmentTbody, 4, "No se pudo cargar la matricula");
        showToast(error.message || "No se pudo cargar la matricula", "danger");
    }
}

// Delegamos eventos en el tbody para no registrar listeners por cada fila renderizada.
studentsTbody.addEventListener("click", (event) => {
    const manageButton = event.target.closest(".js-manage-enrollment");
    if (manageButton) {
        abrirModalMatricula(manageButton.dataset.id);
        return;
    }

    const editButton = event.target.closest(".js-edit-student");
    if (editButton) {
        const id = Number(editButton.dataset.id);
        const student = studentsCache.find((item) => Number(item.id) === id);
        if (student) {
            abrirModalEditar(student);
        }
        return;
    }

    const deleteButton = event.target.closest(".js-delete-student");
    if (deleteButton) {
        abrirModalEliminar(deleteButton.dataset.id);
    }
});

// Un solo listener cubre todos los botones de eliminar materia.
subjectsTbody.addEventListener("click", (event) => {
    const deleteButton = event.target.closest(".js-delete-subject");
    if (deleteButton) {
        confirmarEliminacionMateria(deleteButton.dataset.id);
    }
});

// La eliminacion de materias pasa primero por una confirmacion y luego por la API.
async function confirmarEliminacionMateria(subjectId) {
    const ok = await confirmarAccion("Estas seguro de eliminar esta materia?");
    if (!ok) {
        return;
    }

    const formData = new FormData();
    formData.append("action", "eliminar");
    formData.append("id", subjectId);

    let data;
    try {
        data = await fetchJson(SUBJECT_API, { method: "POST", body: formData });
    } catch (error) {
        showToast(error.message || "No se pudo eliminar la materia", "danger");
        return;
    }

    if (data.success) {
        await cargarTodo();
        showToast(data.message || "Materia eliminada", "success");
        return;
    }

    showToast(data.message || "No se pudo eliminar la materia", "danger");
}

// Crear estudiante: valida codigo, envia al backend y refresca la tabla.
document.getElementById("addForm").addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    const codigo = normalizarCodigo(formData.get("codigo_estudiante"));
    if (!studentCodeRegex.test(codigo)) {
        showToast("El codigo debe iniciar con 26 y tener 5 digitos en total", "danger");
        return;
    }
    formData.set("codigo_estudiante", codigo);
    formData.append("action", "crear");

    let data;
    try {
        data = await fetchJson(STUDENT_API, { method: "POST", body: formData });
    } catch (error) {
        showToast(error.message || "No se pudo crear", "danger");
        return;
    }

    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById("addModal")).hide();
        event.target.reset();
        await cargarEstudiantes();
        showToast(data.message || "Registro creado", "success");
        return;
    }

    showToast(data.message || "No se pudo crear", "danger");
});

// Al abrir el modal, sugerimos el siguiente codigo disponible.
openAddModalBtn.addEventListener("click", async () => {
    const inputCodigo = document.getElementById("addStudentCode");
    const sugerido = await obtenerSiguienteCodigo();
    if (sugerido) {
        inputCodigo.value = sugerido;
    }
});

if (openAddSubjectModalBtn) {
    // Resetea el modal para no reutilizar accidentalmente datos anteriores.
    openAddSubjectModalBtn.addEventListener("click", () => {
        document.getElementById("addSubjectForm").reset();
        document.getElementById("addSubjectCredits").value = 3;
    });
}

// Actualizar estudiante reutiliza el modal y vuelve a validar antes de persistir.
document.getElementById("editForm").addEventListener("submit", async (event) => {
    event.preventDefault();
    const ok = await confirmarAccion("Estas seguro de actualizar este estudiante?");
    if (!ok) {
        return;
    }

    const formData = new FormData(event.target);
    const codigo = normalizarCodigo(formData.get("codigo_estudiante"));
    if (!studentCodeRegex.test(codigo)) {
        showToast("El codigo debe iniciar con 26 y tener 5 digitos en total", "danger");
        return;
    }
    formData.set("codigo_estudiante", codigo);
    formData.append("action", "actualizar");

    let data;
    try {
        data = await fetchJson(STUDENT_API, { method: "POST", body: formData });
    } catch (error) {
        showToast(error.message || "No se pudo actualizar", "danger");
        return;
    }

    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById("editModal")).hide();
        await cargarEstudiantes();
        showToast(data.message || "Registro actualizado", "success");
        return;
    }

    showToast(data.message || "No se pudo actualizar", "danger");
});

// Eliminar estudiante impacta lista y metricas, por eso recargamos todo.
document.getElementById("deleteForm").addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append("action", "eliminar");

    let data;
    try {
        data = await fetchJson(STUDENT_API, { method: "POST", body: formData });
    } catch (error) {
        showToast(error.message || "No se pudo eliminar", "danger");
        return;
    }

    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById("deleteModal")).hide();
        await cargarTodo();
        showToast(data.message || "Registro eliminado", "success");
        return;
    }

    showToast(data.message || "No se pudo eliminar", "danger");
});

// Guardar matriculas es una operacion clave del negocio: el frontend ayuda,
// pero el backend vuelve a validar el limite de 3 materias.
document.getElementById("subjectEnrollmentForm").addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    const selectedCount = Array.from(formData.keys()).filter((key) => key === "subject_ids[]").length;

    if (selectedCount > MAX_SUBJECTS_PER_STUDENT) {
        showToast(`Cada estudiante solo puede matricular ${MAX_SUBJECTS_PER_STUDENT} materias`, "danger");
        return;
    }

    formData.append("action", "guardar_matriculas");

    let data;
    try {
        data = await fetchJson(STUDENT_API, { method: "POST", body: formData });
    } catch (error) {
        showToast(error.message || "No se pudieron guardar las materias", "danger");
        return;
    }

    if (data.success) {
        subjectEnrollmentModal.hide();
        await cargarTodo();
        showToast(data.message || "Materias actualizadas", "success");
        return;
    }

    showToast(data.message || "No se pudieron guardar las materias", "danger");
});

// Crear materia normaliza el codigo en mayusculas para mantener consistencia.
document.getElementById("addSubjectForm").addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.set("codigo", String(formData.get("codigo") || "").trim().toUpperCase());
    formData.append("action", "crear");

    let data;
    try {
        data = await fetchJson(SUBJECT_API, { method: "POST", body: formData });
    } catch (error) {
        showToast(error.message || "No se pudo crear la materia", "danger");
        return;
    }

    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById("addSubjectModal")).hide();
        event.target.reset();
        await cargarMaterias();
        showToast(data.message || "Materia creada", "success");
        return;
    }

    showToast(data.message || "No se pudo crear la materia", "danger");
});

// Busqueda manual por boton.
searchButton.addEventListener("click", async () => {
    searchTerm = searchInput.value.trim();
    await cargarEstudiantes();
});

// Busqueda por Enter para una experiencia mas rapida.
searchInput.addEventListener("keydown", async (event) => {
    if (event.key === "Enter") {
        event.preventDefault();
        searchTerm = searchInput.value.trim();
        await cargarEstudiantes();
    }
});

// Limpia filtro y recarga toda la tabla.
clearButton.addEventListener("click", async () => {
    searchInput.value = "";
    searchTerm = "";
    await cargarEstudiantes();
});

// Estos botones del dashboard reutilizan la misma navegacion del sidebar.
document.querySelectorAll(".js-section-trigger").forEach((button) => {
    button.addEventListener("click", () => {
        const link = document.querySelector(`.js-section-nav[data-section="${button.dataset.section}"]`);
        if (link) {
            link.click();
        }
    });
});

// Iniciamos la carga cuando el DOM ya esta listo.
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", cargarTodo);
} else {
    cargarTodo();
}
