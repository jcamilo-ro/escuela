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

function setTableMessage(tbody, colspan, message) {
    tbody.innerHTML = `<tr><td colspan="${colspan}" class="text-center py-4">${escapeHtml(message)}</td></tr>`;
}

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

function escapeHtml(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function normalizarCodigo(codigo) {
    return String(codigo || "").trim();
}

function formatEnrollmentCount(count) {
    return `${Number(count)}/${MAX_SUBJECTS_PER_STUDENT}`;
}

function syncSubjectCheckboxLimit() {
    const checkboxes = Array.from(subjectEnrollmentTbody.querySelectorAll('input[name="subject_ids[]"]'));
    const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

    checkboxes.forEach((checkbox) => {
        checkbox.disabled = !checkbox.checked && selectedCount >= MAX_SUBJECTS_PER_STUDENT;
    });
}

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

async function cargarTodo() {
    await Promise.all([cargarEstudiantes(), cargarMaterias()]);
}

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

function renderTablaEstudiantes(datos) {
    studentsCache = datos;

    if (!datos.length) {
        setTableMessage(studentsTbody, 6, "Sin estudiantes");
        return;
    }

    studentsTbody.innerHTML = datos.map((student) => `
        <tr>
            <td>${escapeHtml(student.codigo_estudiante || "")}</td>
            <td>${escapeHtml(student.first_name)}</td>
            <td>${escapeHtml(student.last_name)}</td>
            <td>${escapeHtml(student.email)}</td>
            <td>${formatEnrollmentCount(student.materias_matriculadas || 0)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary me-1 js-manage-enrollment" data-id="${escapeHtml(student.id)}">
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

function renderTablaMaterias(datos) {
    window.__subjectsLoaded = true;

    if (!datos.length) {
        setTableMessage(subjectsTbody, 4, "Sin materias");
        return;
    }

    subjectsTbody.innerHTML = datos.map((subject) => `
        <tr>
            <td>${escapeHtml(subject.id)}</td>
            <td>
                <div class="fw-semibold">${escapeHtml(subject.nombre)}</div>
                <div class="text-muted small">${escapeHtml(subject.codigo)} · ${escapeHtml(subject.creditos)} creditos</div>
            </td>
            <td>${escapeHtml(subject.estudiantes_matriculados || 0)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger js-delete-subject" data-id="${escapeHtml(subject.id)}">
                    Eliminar
                </button>
            </td>
        </tr>
    `).join("");
}

function abrirModalEditar(registro) {
    document.getElementById("editId").value = registro.id;
    document.getElementById("editStudentCode").value = registro.codigo_estudiante || "";
    document.getElementById("editFirstName").value = registro.first_name;
    document.getElementById("editLastName").value = registro.last_name;
    document.getElementById("editEmail").value = registro.email;
    bootstrap.Modal.getOrCreateInstance(document.getElementById("editModal")).show();
}

function abrirModalEliminar(id) {
    document.getElementById("deleteId").value = id;
    bootstrap.Modal.getOrCreateInstance(document.getElementById("deleteModal")).show();
}

function renderEnrollmentSubjects(subjects, maxMaterias) {
    if (!subjects.length) {
        setTableMessage(subjectEnrollmentTbody, 4, "No hay materias disponibles");
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
                <td>${escapeHtml(subject.codigo || "")}</td>
                <td>${escapeHtml(subject.nombre || "")}</td>
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

subjectsTbody.addEventListener("click", (event) => {
    const deleteButton = event.target.closest(".js-delete-subject");
    if (deleteButton) {
        confirmarEliminacionMateria(deleteButton.dataset.id);
    }
});

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

openAddModalBtn.addEventListener("click", async () => {
    const inputCodigo = document.getElementById("addStudentCode");
    const sugerido = await obtenerSiguienteCodigo();
    if (sugerido) {
        inputCodigo.value = sugerido;
    }
});

if (openAddSubjectModalBtn) {
    openAddSubjectModalBtn.addEventListener("click", () => {
        document.getElementById("addSubjectForm").reset();
        document.getElementById("addSubjectCredits").value = 3;
    });
}

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

searchButton.addEventListener("click", async () => {
    searchTerm = searchInput.value.trim();
    await cargarEstudiantes();
});

searchInput.addEventListener("keydown", async (event) => {
    if (event.key === "Enter") {
        event.preventDefault();
        searchTerm = searchInput.value.trim();
        await cargarEstudiantes();
    }
});

clearButton.addEventListener("click", async () => {
    searchInput.value = "";
    searchTerm = "";
    await cargarEstudiantes();
});

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", cargarTodo);
} else {
    cargarTodo();
}
