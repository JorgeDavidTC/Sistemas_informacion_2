// Navegación entre secciones
document.addEventListener('DOMContentLoaded', function() {
    // Navegación del menú (solo para enlaces, no para botones)
    const enlacesMenu = document.querySelectorAll('.enlace-menu');
    const secciones = document.querySelectorAll('.seccion-contenido');

    enlacesMenu.forEach(enlace => {
        enlace.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remover clase activa de todos los enlaces y secciones
            enlacesMenu.forEach(item => item.classList.remove('activo'));
            secciones.forEach(seccion => seccion.classList.remove('activo'));
            
            // Agregar clase activa al enlace clickeado
            this.classList.add('activo');
            
            // Mostrar la sección correspondiente
            const seccionId = this.getAttribute('data-seccion');
            const seccionActiva = document.getElementById(seccionId);
            if (seccionActiva) {
                seccionActiva.classList.add('activo');
            }
        });
    });

    // Cerrar sesión
    document.getElementById('btn-cerrar-sesion').addEventListener('click', function() {
        if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
            window.location.href = 'logout.php';
        }
    });

    // Botón nuevo postulante
    document.getElementById('btn-nuevo-postulante').addEventListener('click', function() {
        mostrarModalPostulante();
    });

    // Botones de validación de documentos
    document.querySelectorAll('.btn-validar').forEach(btn => {
        btn.addEventListener('click', function() {
            const docId = this.getAttribute('data-id');
            const estado = this.getAttribute('data-estado');
            validarDocumento(docId, estado);
        });
    });

    // Botón nueva inscripción
    document.getElementById('btn-nueva-inscripcion').addEventListener('click', function() {
        mostrarModalInscripcion();
    });

    // Botón exportar inscripciones
    document.getElementById('btn-exportar-inscripciones').addEventListener('click', function() {
        exportarAExcel('tabla-inscripciones', 'inscripciones');
    });

    // Botón publicar resultados
    document.getElementById('btn-publicar-resultados').addEventListener('click', function() {
        if (confirm('¿Estás seguro de que deseas publicar los resultados?')) {
            publicarResultados();
        }
    });

    // Botones de acciones en tablas
    inicializarEventosTablas();
});

// Función para inicializar eventos de las tablas
function inicializarEventosTablas() {
    // Botones ver/editar postulantes
    document.querySelectorAll('.btn-ver, .btn-editar').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const esEdicion = this.classList.contains('btn-editar');
            if (esEdicion) {
                editarPostulante(id);
            } else {
                verPostulante(id);
            }
        });
    });

    // Botones eliminar inscripción
    document.querySelectorAll('.btn-eliminar-insc').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            eliminarInscripcion(id);
        });
    });

    // Botones ver/editar resultados
    document.querySelectorAll('.btn-ver-resultado, .btn-editar-resultado').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const esEdicion = this.classList.contains('btn-editar-resultado');
            if (esEdicion) {
                editarResultado(id);
            } else {
                verResultado(id);
            }
        });
    });
}

// Función para validar documentos
function validarDocumento(docId, estado) {
    const comentario = prompt(`Ingrese un comentario para ${estado === 'aprobado' ? 'aprobar' : 'rechazar'} el documento:`, '');
    
    if (comentario !== null) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=validar_documento&doc_id=${docId}&estado=${estado}&comentario=${encodeURIComponent(comentario)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Documento ${estado === 'aprobado' ? 'aprobado' : 'rechazado'} exitosamente`);
                location.reload();
            } else {
                alert('Error al validar el documento');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al validar el documento');
        });
    }
}

// Modal para nuevo postulante
function mostrarModalPostulante() {
    const modal = `
        <div id="modal-postulante" class="modal" style="display: block;">
            <div class="modal-contenido">
                <span class="cerrar">&times;</span>
                <h3>Nuevo Postulante</h3>
                <form id="form-postulante">
                    <div class="form-group">
                        <label>Nombres:</label>
                        <input type="text" name="nombres" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido Paterno:</label>
                        <input type="text" name="apellido_paterno" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido Materno:</label>
                        <input type="text" name="apellido_materno" required>
                    </div>
                    <div class="form-group">
                        <label>CI:</label>
                        <input type="text" name="ci" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha de Nacimiento:</label>
                        <input type="date" name="fecha_nacimiento" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono:</label>
                        <input type="text" name="telefono">
                    </div>
                    <div class="form-group">
                        <label>Dirección:</label>
                        <textarea name="direccion"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Nacionalidad:</label>
                        <input type="text" name="nacionalidad" value="Boliviana">
                    </div>
                    <div class="form-botones">
                        <button type="submit">Guardar</button>
                        <button type="button" class="btn-cancelar">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modal);
    
    const modalElement = document.getElementById('modal-postulante');
    modalElement.querySelector('.cerrar').addEventListener('click', () => modalElement.remove());
    modalElement.querySelector('.btn-cancelar').addEventListener('click', () => modalElement.remove());
    
    document.getElementById('form-postulante').addEventListener('submit', function(e) {
        e.preventDefault();
        crearPostulante(new FormData(this));
    });
}

// Función para crear postulante
function crearPostulante(formData) {
    const datos = Object.fromEntries(formData);
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=crear_postulante&${new URLSearchParams(datos)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Postulante creado exitosamente');
            document.getElementById('modal-postulante').remove();
            location.reload();
        } else {
            alert('Error al crear el postulante');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al crear el postulante');
    });
}

// Modal para nueva inscripción
function mostrarModalInscripcion() {
    const modal = `
        <div id="modal-inscripcion" class="modal" style="display: block;">
            <div class="modal-contenido">
                <span class="cerrar">&times;</span>
                <h3>Nueva Inscripción</h3>
                <form id="form-inscripcion">
                    <div class="form-group">
                        <label>Postulante:</label>
                        <select name="id_postulante" required>
                            <option value="">Seleccionar postulante</option>
                            <?php foreach ($postulantes as $post): ?>
                            <option value="<?php echo $post['id_postulante']; ?>">
                                <?php echo htmlspecialchars($post['nombres'] . ' ' . $post['apellido_paterno'] . ' ' . $post['apellido_materno']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Carrera:</label>
                        <select name="id_carrera" required>
                            <option value="">Seleccionar carrera</option>
                            <?php foreach ($carreras as $carrera): ?>
                            <option value="<?php echo $carrera['id_carrera']; ?>">
                                <?php echo htmlspecialchars($carrera['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Periodo Académico:</label>
                        <select name="periodo_id" required>
                            <option value="">Seleccionar periodo</option>
                            <?php foreach ($periodos as $periodo): ?>
                            <option value="<?php echo $periodo['id_periodo']; ?>">
                                <?php echo htmlspecialchars($periodo['nombre_periodo']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-botones">
                        <button type="submit">Guardar</button>
                        <button type="button" class="btn-cancelar">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modal);
    
    const modalElement = document.getElementById('modal-inscripcion');
    modalElement.querySelector('.cerrar').addEventListener('click', () => modalElement.remove());
    modalElement.querySelector('.btn-cancelar').addEventListener('click', () => modalElement.remove());
    
    document.getElementById('form-inscripcion').addEventListener('submit', function(e) {
        e.preventDefault();
        crearInscripcion(new FormData(this));
    });
}

// Función para crear inscripción
function crearInscripcion(formData) {
    const datos = Object.fromEntries(formData);
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=crear_inscripcion&${new URLSearchParams(datos)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Inscripción creada exitosamente');
            document.getElementById('modal-inscripcion').remove();
            location.reload();
        } else {
            alert('Error al crear la inscripción');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al crear la inscripción');
    });
}

// Función para eliminar inscripción
function eliminarInscripcion(id) {
    if (confirm('¿Está seguro de que desea eliminar esta inscripción?')) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=eliminar_inscripcion&id_inscripcion=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Inscripción eliminada exitosamente');
                location.reload();
            } else {
                alert('Error al eliminar la inscripción');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar la inscripción');
        });
    }
}

// Función para exportar a Excel
function exportarAExcel(tablaId, nombreArchivo) {
    const tabla = document.getElementById(tablaId);
    let csv = [];
    
    // Obtener headers
    const headers = [];
    for (let i = 0; i < tabla.rows[0].cells.length; i++) {
        headers.push(tabla.rows[0].cells[i].innerText);
    }
    csv.push(headers.join(','));
    
    // Obtener datos
    for (let i = 1; i < tabla.rows.length; i++) {
        const row = [];
        for (let j = 0; j < tabla.rows[i].cells.length; j++) {
            row.push(tabla.rows[i].cells[j].innerText);
        }
        csv.push(row.join(','));
    }
    
    // Descargar archivo
    const csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `${nombreArchivo}_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Función para publicar resultados
function publicarResultados() {
    // Aquí puedes implementar la lógica para publicar resultados
    // Por ejemplo, cambiar el estado de los resultados o notificar a los postulantes
    alert('Funcionalidad de publicación de resultados implementada correctamente');
}

// Funciones auxiliares para editar y ver (placeholder)
function editarPostulante(id) {
    alert('Funcionalidad de editar postulante para ID: ' + id);
    // Implementar lógica de edición
}

function verPostulante(id) {
    alert('Funcionalidad de ver postulante para ID: ' + id);
    // Implementar lógica de visualización
}

function editarResultado(id) {
    alert('Funcionalidad de editar resultado para ID: ' + id);
    // Implementar lógica de edición
}

function verResultado(id) {
    alert('Funcionalidad de ver resultado para ID: ' + id);
    // Implementar lógica de visualización
}