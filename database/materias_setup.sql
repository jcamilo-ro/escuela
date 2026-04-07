CREATE TABLE IF NOT EXISTS subject (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    creditos TINYINT UNSIGNED NOT NULL DEFAULT 3,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ux_subject_nombre (nombre),
    UNIQUE KEY ux_subject_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS student_subject (
    id INT NOT NULL AUTO_INCREMENT,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    enrolled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ux_student_subject (student_id, subject_id),
    KEY ix_student_subject_subject (subject_id),
    CONSTRAINT fk_student_subject_student FOREIGN KEY (student_id) REFERENCES student (id) ON DELETE CASCADE,
    CONSTRAINT fk_student_subject_subject FOREIGN KEY (subject_id) REFERENCES subject (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO subject (nombre, codigo, creditos)
SELECT 'Programacion I', 'SIS101', 4
WHERE NOT EXISTS (SELECT 1 FROM subject WHERE codigo = 'SIS101');

INSERT INTO subject (nombre, codigo, creditos)
SELECT 'Bases de Datos', 'SIS102', 4
WHERE NOT EXISTS (SELECT 1 FROM subject WHERE codigo = 'SIS102');

INSERT INTO subject (nombre, codigo, creditos)
SELECT 'Ingenieria de Software', 'SIS201', 4
WHERE NOT EXISTS (SELECT 1 FROM subject WHERE codigo = 'SIS201');

INSERT INTO subject (nombre, codigo, creditos)
SELECT 'Estructuras de Datos', 'SIS202', 4
WHERE NOT EXISTS (SELECT 1 FROM subject WHERE codigo = 'SIS202');

INSERT INTO subject (nombre, codigo, creditos)
SELECT 'Redes de Computadores', 'SIS203', 3
WHERE NOT EXISTS (SELECT 1 FROM subject WHERE codigo = 'SIS203');
