// Navegación entre secciones
document.addEventListener('DOMContentLoaded', function() {
    inicializarNavegacion();
    inicializarEventosGlobales();
    inicializarEventosPostulantes();
    inicializarEventosDocumentos();
    inicializarEventosInscripciones();
    inicializarEventosResultados();
    inicializarEventosReportes();
});

// Navegación principal
function inicializarNavegacion() {
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
}

// Eventos globales
function inicializarEventosGlobales() {
    // Cerrar sesión
    const btnCerrarSesion = document.getElementById('btn-cerrar-sesion');
    if (btnCerrarSesion) {
        btnCerrarSesion.addEventListener('click', function() {
                window.location.href = 'login.html';
            
        });
    }

    // Modal global
    const modal = document.getElementById('modal-global');
    const cerrarModal = document.querySelector('.cerrar-modal');
    
    if (cerrarModal) {
        cerrarModal.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    }

    // Cerrar modal al hacer clic fuera
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// Eventos de postulantes
function inicializarEventosPostulantes() {
    // Botón nuevo postulante
    const btnNuevoPostulante = document.getElementById('btn-nuevo-postulante');
    if (btnNuevoPostulante) {
        btnNuevoPostulante.addEventListener('click', mostrarModalPostulante);
    }

    // Botón exportar postulantes
    const btnExportarPostulantes = document.getElementById('btn-exportar-postulantes');
    if (btnExportarPostulantes) {
        btnExportarPostulantes.addEventListener('click', () => {
            exportarAExcel('tabla-postulantes', 'postulantes');
        });
    }

    // Botones de acciones en tabla de postulantes
    document.addEventListener('click', function(e) {
        // Botones ver postulante
        if (e.target.closest('.btn-ver')) {
            const btn = e.target.closest('.btn-ver');
            const id = btn.getAttribute('data-id');
            verPostulante(id);
        }

        // Botones editar postulante
        if (e.target.closest('.btn-editar')) {
            const btn = e.target.closest('.btn-editar');
            const id = btn.getAttribute('data-id');
            editarPostulante(id);
        }

        // Botones inscribir postulante
        if (e.target.closest('.btn-inscribir')) {
            const btn = e.target.closest('.btn-inscribir');
            const id = btn.getAttribute('data-id');
            inscribirPostulante(id);
        }

        // Botones eliminar postulante
        if (e.target.closest('.btn-eliminar')) {
            const btn = e.target.closest('.btn-eliminar');
            const id = btn.getAttribute('data-id');
            eliminarPostulante(id);
        }
    });
}

// Eventos de documentos
function inicializarEventosDocumentos() {
    // Botones de validación de documentos
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-validar')) {
            const btn = e.target.closest('.btn-validar');
            const docId = btn.getAttribute('data-id');
            const estado = btn.getAttribute('data-estado');
            validarDocumento(docId, estado);
        }

        // Botones ver documento
        if (e.target.closest('.btn-ver-doc')) {
            const btn = e.target.closest('.btn-ver-doc');
            const url = btn.getAttribute('data-url');
            window.open(url, '_blank');
        }

        // Botones ver comentario
        if (e.target.closest('.btn-ver-comentario')) {
            const btn = e.target.closest('.btn-ver-comentario');
            const comentario = btn.getAttribute('data-comentario');
            mostrarModal('Comentario', `<p>${comentario}</p>`);
        }
    });
}

// Eventos de inscripciones
function inicializarEventosInscripciones() {
    // Botón nueva inscripción
    const btnNuevaInscripcion = document.getElementById('btn-nueva-inscripcion');
    if (btnNuevaInscripcion) {
        btnNuevaInscripcion.addEventListener('click', mostrarModalInscripcion);
    }

    // Botón exportar inscripciones
    const btnExportarInscripciones = document.getElementById('btn-exportar-inscripciones');
    if (btnExportarInscripciones) {
        btnExportarInscripciones.addEventListener('click', () => {
            exportarAExcel('tabla-inscripciones', 'inscripciones');
        });
    }

    // Botones de acciones en tabla de inscripciones
    document.addEventListener('click', function(e) {
        // Botones editar inscripción
        if (e.target.closest('.btn-editar-insc')) {
            const btn = e.target.closest('.btn-editar-insc');
            const id = btn.getAttribute('data-id');
            editarInscripcion(id);
        }

        // Botones registrar resultado
        if (e.target.closest('.btn-resultado')) {
            const btn = e.target.closest('.btn-resultado');
            const id = btn.getAttribute('data-id');
            registrarResultadoInscripcion(id);
        }

        // Botones eliminar inscripción
        if (e.target.closest('.btn-eliminar-insc')) {
            const btn = e.target.closest('.btn-eliminar-insc');
            const id = btn.getAttribute('data-id');
            eliminarInscripcion(id);
        }
    });
}

// Eventos de resultados
function inicializarEventosResultados() {
    // Botón nuevo resultado
    const btnNuevoResultado = document.getElementById('btn-nuevo-resultado');
    if (btnNuevoResultado) {
        btnNuevoResultado.addEventListener('click', mostrarModalResultado);
    }

    // Botón publicar resultados
    const btnPublicarResultados = document.getElementById('btn-publicar-resultados');
    if (btnPublicarResultados) {
        btnPublicarResultados.addEventListener('click', publicarResultados);
    }

    // Botón exportar resultados
    const btnExportarResultados = document.getElementById('btn-exportar-resultados');
    if (btnExportarResultados) {
        btnExportarResultados.addEventListener('click', () => {
            exportarAExcel('tabla-resultados', 'resultados');
        });
    }

    // Botones de acciones en tabla de resultados
    document.addEventListener('click', function(e) {
        // Botones ver resultado
        if (e.target.closest('.btn-ver-resultado')) {
            const btn = e.target.closest('.btn-ver-resultado');
            const id = btn.getAttribute('data-id');
            verResultado(id);
        }

        // Botones editar resultado
        if (e.target.closest('.btn-editar-resultado')) {
            const btn = e.target.closest('.btn-editar-resultado');
            const id = btn.getAttribute('data-id');
            editarResultado(id);
        }

        // Botones eliminar resultado
        if (e.target.closest('.btn-eliminar-resultado')) {
            const btn = e.target.closest('.btn-eliminar-resultado');
            const id = btn.getAttribute('data-id');
            eliminarResultado(id);
        }
    });
}

// Eventos de reportes
function inicializarEventosReportes() {
    const btnGenerarReporte = document.getElementById('btn-generar-reporte');
    if (btnGenerarReporte) {
        btnGenerarReporte.addEventListener('click', generarReporte);
    }
}

// Funciones para postulantes
function mostrarModalPostulante(postulante = null) {
    const esEdicion = postulante !== null;
    const titulo = esEdicion ? 'Editar Postulante' : 'Nuevo Postulante';
    
    const formulario = `
        <form id="form-postulante" class="form-modal">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nombres">Nombres *</label>
                    <input type="text" id="nombres" name="nombres" value="${postulante?.nombres || ''}" required>
                </div>
                
                <div class="form-group">
                    <label for="apellido_paterno">Apellido Paterno *</label>
                    <input type="text" id="apellido_paterno" name="apellido_paterno" value="${postulante?.apellido_paterno || ''}" required>
                </div>
                
                <div class="form-group">
                    <label for="apellido_materno">Apellido Materno *</label>
                    <input type="text" id="apellido_materno" name="apellido_materno" value="${postulante?.apellido_materno || ''}" required>
                </div>
                
                <div class="form-group">
                    <label for="ci">Cédula de Identidad *</label>
                    <input type="text" id="ci" name="ci" value="${postulante?.ci || ''}" required>
                </div>
                
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="${postulante?.fecha_nacimiento || ''}">
                </div>
                
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" value="${postulante?.telefono || ''}">
                </div>
                
                <div class="form-group full-width">
                    <label for="direccion">Dirección</label>
                    <textarea id="direccion" name="direccion" rows="3">${postulante?.direccion_residencia || ''}</textarea>
                </div>
                
                <div class="form-group">
                    <label for="nacionalidad">Nacionalidad</label>
                    <input type="text" id="nacionalidad" name="nacionalidad" value="${postulante?.nacionalidad || 'Boliviana'}">
                </div>
                
                ${esEdicion ? `
                <div class="form-group">
                    <label for="estado_postulacion">Estado de Postulación</label>
                    <select id="estado_postulacion" name="estado_postulacion">
                        <option value="pendiente" ${postulante.estado_postulacion === 'pendiente' ? 'selected' : ''}>Pendiente</option>
                        <option value="documentos_aprobados" ${postulante.estado_postulacion === 'documentos_aprobados' ? 'selected' : ''}>Documentos Aprobados</option>
                        <option value="documentos_rechazados" ${postulante.estado_postulacion === 'documentos_rechazados' ? 'selected' : ''}>Documentos Rechazados</option>
                        <option value="admitido" ${postulante.estado_postulacion === 'admitido' ? 'selected' : ''}>Admitido</option>
                        <option value="no_admitido" ${postulante.estado_postulacion === 'no_admitido' ? 'selected' : ''}>No Admitido</option>
                    </select>
                </div>
                ` : ''}
            </div>
            
            <div class="form-botones">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> ${esEdicion ? 'Actualizar' : 'Guardar'}
                </button>
                <button type="button" class="btn-secondary btn-cancelar">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </form>
    `;
    
    mostrarModal(titulo, formulario);
    
    // Configurar envío del formulario
    const form = document.getElementById('form-postulante');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const datos = Object.fromEntries(formData);
        
        if (esEdicion) {
            datos.id = postulante.id_postulante;
            actualizarPostulante(datos);
        } else {
            crearPostulante(datos);
        }
    });
}

function crearPostulante(datos) {
    mostrarLoading();
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=crear_postulante&${new URLSearchParams(datos)}`
    })
    .then(response => response.json())
    .then(data => {
        ocultarLoading();
        if (data.success) {
            mostrarMensaje('success', data.message);
            cerrarModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensaje('error', data.message);
        }
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al crear el postulante');
    });
}

function actualizarPostulante(datos) {
    mostrarLoading();
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=actualizar_postulante&${new URLSearchParams(datos)}`
    })
    .then(response => response.json())
    .then(data => {
        ocultarLoading();
        if (data.success) {
            mostrarMensaje('success', data.message);
            cerrarModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensaje('error', data.message);
        }
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al actualizar el postulante');
    });
}

function verPostulante(id) {
    mostrarLoading();
    
    fetch(`obtener_postulante.php?id=${id}`)
    .then(response => response.json())
    .then(postulante => {
        ocultarLoading();
        
        const contenido = `
            <div class="detalles-postulante">
                <div class="detalle-grupo">
                    <h4>Información Personal</h4>
                    <div class="detalle-item">
                        <strong>Nombres Completos:</strong>
                        <span>${postulante.nombres} ${postulante.apellido_paterno} ${postulante.apellido_materno}</span>
                    </div>
                    <div class="detalle-item">
                        <strong>CI:</strong>
                        <span>${postulante.ci}</span>
                    </div>
                    <div class="detalle-item">
                        <strong>Fecha de Nacimiento:</strong>
                        <span>${postulante.fecha_nacimiento || 'No especificado'}</span>
                    </div>
                    <div class="detalle-item">
                        <strong>Teléfono:</strong>
                        <span>${postulante.telefono || 'No especificado'}</span>
                    </div>
                    <div class="detalle-item">
                        <strong>Dirección:</strong>
                        <span>${postulante.direccion_residencia || 'No especificado'}</span>
                    </div>
                    <div class="detalle-item">
                        <strong>Nacionalidad:</strong>
                        <span>${postulante.nacionalidad || 'Boliviana'}</span>
                    </div>
                </div>
                
                ${postulante.carrera_nombre ? `
                <div class="detalle-grupo">
                    <h4>Información Académica</h4>
                    <div class="detalle-item">
                        <strong>Carrera:</strong>
                        <span>${postulante.carrera_nombre}</span>
                    </div>
                    <div class="detalle-item">
                        <strong>Folio:</strong>
                        <span>${postulante.numero_folio}</span>
                    </div>
                    <div class="detalle-item">
                        <strong>Estado:</strong>
                        <span class="estado-${postulante.estado_postulacion}">${postulante.estado_postulacion}</span>
                    </div>
                </div>
                ` : ''}
                
                <div class="detalle-grupo">
                    <h4>Documentos</h4>
                    <div id="documentos-postulante">
                        <p>Cargando documentos...</p>
                    </div>
                </div>
            </div>
        `;
        
        mostrarModal('Detalles del Postulante', contenido);
        cargarDocumentosPostulante(id);
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al cargar los detalles del postulante');
    });
}

function cargarDocumentosPostulante(postulanteId) {
    fetch(`obtener_documentos_postulante.php?id=${postulanteId}`)
    .then(response => response.json())
    .then(documentos => {
        const contenedor = document.getElementById('documentos-postulante');
        
        if (documentos.length > 0) {
            let html = '<div class="lista-documentos">';
            documentos.forEach(doc => {
                html += `
                    <div class="documento-item">
                        <span class="documento-nombre">${doc.nombre_documento || doc.tipo_documento}</span>
                        <span class="estado-${doc.estado_validacion}">${doc.estado_validacion}</span>
                        ${doc.archivo_url ? `<button class="btn-ver-doc btn-small" data-url="${doc.archivo_url}"><i class="fas fa-eye"></i></button>` : ''}
                    </div>
                `;
            });
            html += '</div>';
            contenedor.innerHTML = html;
        } else {
            contenedor.innerHTML = '<p>No se encontraron documentos</p>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('documentos-postulante').innerHTML = '<p>Error al cargar documentos</p>';
    });
}

function editarPostulante(id) {
    mostrarLoading();
    
    fetch(`obtener_postulante.php?id=${id}`)
    .then(response => response.json())
    .then(postulante => {
        ocultarLoading();
        mostrarModalPostulante(postulante);
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al cargar los datos del postulante');
    });
}

function inscribirPostulante(id) {
    mostrarModalInscripcion(id);
}

function eliminarPostulante(id) {
    if (!confirm('¿Está seguro de que desea eliminar este postulante? Esta acción no se puede deshacer.')) {
        return;
    }
    
    mostrarLoading();
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=eliminar_postulante&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        ocultarLoading();
        if (data.success) {
            mostrarMensaje('success', data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensaje('error', data.message);
        }
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al eliminar el postulante');
    });
}

// Funciones para documentos
function validarDocumento(docId, estado) {
    const accion = estado === 'aprobado' ? 'aprobar' : 'rechazar';
    const comentario = prompt(`Ingrese un comentario para ${accion} el documento:`, '');
    
    if (comentario !== null) {
        mostrarLoading();
        
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=validar_documento&doc_id=${docId}&estado=${estado}&comentario=${encodeURIComponent(comentario)}`
        })
        .then(response => response.json())
        .then(data => {
            ocultarLoading();
            if (data.success) {
                mostrarMensaje('success', data.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarMensaje('error', data.message);
            }
        })
        .catch(error => {
            ocultarLoading();
            console.error('Error:', error);
            mostrarMensaje('error', 'Error al validar el documento');
        });
    }
}

// Funciones para inscripciones
function mostrarModalInscripcion(postulanteId = null) {
    const titulo = 'Nueva Inscripción';
    
    // Obtener postulantes disponibles (sin inscripción activa en el periodo actual)
    fetch('obtener_postulantes_inscripcion.php')
    .then(response => response.json())
    .then(postulantes => {
        const optionsPostulantes = postulantes.map(p => 
            `<option value="${p.id_postulante}" ${p.id_postulante == postulanteId ? 'selected' : ''}>${p.nombres} ${p.apellido_paterno} ${p.apellido_materno} - ${p.ci}</option>`
        ).join('');
        
        const formulario = `
            <form id="form-inscripcion" class="form-modal">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="id_postulante">Postulante *</label>
                        <select id="id_postulante" name="id_postulante" required>
                            <option value="">Seleccionar postulante</option>
                            ${optionsPostulantes}
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_carrera">Carrera *</label>
                        <select id="id_carrera" name="id_carrera" required>
                            <option value="">Seleccionar carrera</option>
                            <?php foreach ($carreras as $carrera): ?>
                            <option value="<?php echo $carrera['id_carrera']; ?>">
                                <?php echo htmlspecialchars($carrera['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="periodo_id">Periodo Académico *</label>
                        <select id="periodo_id" name="periodo_id" required>
                            <option value="">Seleccionar periodo</option>
                            <?php foreach ($periodos as $periodo): ?>
                            <option value="<?php echo $periodo['id_periodo']; ?>">
                                <?php echo htmlspecialchars($periodo['nombre_periodo']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="opcion_carrera">Opción de Carrera</label>
                        <select id="opcion_carrera" name="opcion_carrera">
                            <option value="primera">Primera Opción</option>
                            <option value="segunda">Segunda Opción</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-botones">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" class="btn-secondary btn-cancelar">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        `;
        
        mostrarModal(titulo, formulario);
        
        // Configurar envío del formulario
        const form = document.getElementById('form-inscripcion');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const datos = Object.fromEntries(formData);
            crearInscripcion(datos);
        });
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al cargar los postulantes');
    });
}

function crearInscripcion(datos) {
    mostrarLoading();
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=crear_inscripcion&${new URLSearchParams(datos)}`
    })
    .then(response => response.json())
    .then(data => {
        ocultarLoading();
        if (data.success) {
            mostrarMensaje('success', data.message);
            cerrarModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensaje('error', data.message);
        }
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al crear la inscripción');
    });
}
function editarInscripcion(id) {
    mostrarLoading();
    
    fetch(`obtener_inscripcion.php?id=${id}`)
    .then(response => response.json())
    .then(inscripcion => {
        ocultarLoading();
        
        const formulario = `
            <form id="form-inscripcion" class="form-modal">
                <input type="hidden" name="id" value="${inscripcion.id_inscripcion}">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Postulante</label>
                        <input type="text" value="${inscripcion.nombres} ${inscripcion.apellido_paterno} ${inscripcion.apellido_materno}" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Folio</label>
                        <input type="text" value="${inscripcion.numero_folio}" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_carrera">Carrera *</label>
                        <select id="id_carrera" name="id_carrera" required>
                            <option value="">Seleccionar carrera</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="periodo_id">Periodo Académico *</label>
                        <select id="periodo_id" name="periodo_id" required>
                            <option value="">Seleccionar periodo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="opcion_carrera">Opción de Carrera</label>
                        <select id="opcion_carrera" name="opcion_carrera">
                            <option value="primera" ${inscripcion.opcion_carrera === 'primera' ? 'selected' : ''}>Primera Opción</option>
                            <option value="segunda" ${inscripcion.opcion_carrera === 'segunda' ? 'selected' : ''}>Segunda Opción</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado_inscripcion">Estado de Inscripción</label>
                        <select id="estado_inscripcion" name="estado_inscripcion">
                            <option value="inscrito" ${inscripcion.estado_inscripcion === 'inscrito' ? 'selected' : ''}>Inscrito</option>
                            <option value="confirmada" ${inscripcion.estado_inscripcion === 'confirmada' ? 'selected' : ''}>Confirmada</option>
                            <option value="presento_examen" ${inscripcion.estado_inscripcion === 'presento_examen' ? 'selected' : ''}>Presentó Examen</option>
                            <option value="admitido" ${inscripcion.estado_inscripcion === 'admitido' ? 'selected' : ''}>Admitido</option>
                            <option value="no_admitido" ${inscripcion.estado_inscripcion === 'no_admitido' ? 'selected' : ''}>No Admitido</option>
                            <option value="rechazada" ${inscripcion.estado_inscripcion === 'rechazada' ? 'selected' : ''}>Rechazada</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-botones">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Actualizar
                    </button>
                    <button type="button" class="btn-secondary btn-cancelar">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        `;
        
        mostrarModal('Editar Inscripción', formulario);
        
        // Cargar carreras y periodos después de mostrar el modal
        cargarCarrerasYPeriodos(inscripcion.id_carrera, inscripcion.periodo_id);
        
        // Configurar envío del formulario
        const form = document.getElementById('form-inscripcion');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const datos = Object.fromEntries(formData);
            actualizarInscripcion(datos);
        });
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al cargar los datos de la inscripción');
    });
}

function cargarCarrerasYPeriodos(carreraId, periodoId) {
    Promise.all([
        fetch('obtener_carreras.php').then(r => r.json()),
        fetch('obtener_periodos.php').then(r => r.json())
    ]).then(([carreras, periodos]) => {
        const selectCarrera = document.getElementById('id_carrera');
        const selectPeriodo = document.getElementById('periodo_id');
        
        selectCarrera.innerHTML = '<option value="">Seleccionar carrera</option>' +
            carreras.map(c => `<option value="${c.id_carrera}" ${c.id_carrera == carreraId ? 'selected' : ''}>${c.nombre}</option>`).join('');
        
        selectPeriodo.innerHTML = '<option value="">Seleccionar periodo</option>' +
            periodos.map(p => `<option value="${p.id_periodo}" ${p.id_periodo == periodoId ? 'selected' : ''}>${p.nombre_periodo}</option>`).join('');
    }).catch(error => {
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al cargar carreras o periodos');
    });
}

function actualizarInscripcion(datos) {
    mostrarLoading();
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=actualizar_inscripcion&${new URLSearchParams(datos)}`
    })
    .then(response => response.json())
    .then(data => {
        ocultarLoading();
        if (data.success) {
            mostrarMensaje('success', data.message);
            cerrarModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensaje('error', data.message);
        }
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al actualizar la inscripción');
    });
}

function registrarResultadoInscripcion(idInscripcion) {
    mostrarLoading();
    
    fetch(`obtener_inscripcion.php?id=${idInscripcion}`)
    .then(response => response.json())
    .then(inscripcion => {
        ocultarLoading();
        mostrarModalResultado(inscripcion);
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al cargar los datos de la inscripción');
    });
}

function eliminarInscripcion(id) {
    if (!confirm('¿Está seguro de que desea eliminar esta inscripción? Esta acción no se puede deshacer.')) {
        return;
    }
    
    mostrarLoading();
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=eliminar_inscripcion&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        ocultarLoading();
        if (data.success) {
            mostrarMensaje('success', data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensaje('error', data.message);
        }
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al eliminar la inscripción');
    });
}

// Funciones para resultados
function mostrarModalResultado(inscripcion = null) {
    const esEdicion = inscripcion?.id_resultado;
    const titulo = esEdicion ? 'Editar Resultado' : 'Nuevo Resultado';
    
    let formulario = '';
    
    if (!esEdicion && !inscripcion) {
        // Modal para resultado desde cero - necesitamos cargar postulantes y carreras
        formulario = `
            <form id="form-resultado" class="form-modal">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="id_postulante">Postulante *</label>
                        <select id="id_postulante" name="id_postulante" required>
                            <option value="">Seleccionar postulante</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_carrera">Carrera *</label>
                        <select id="id_carrera" name="id_carrera" required>
                            <option value="">Seleccionar carrera</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="puntaje">Puntaje *</label>
                        <input type="number" id="puntaje" name="puntaje" min="0" max="100" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="aprobado">Estado</label>
                        <select id="aprobado" name="aprobado">
                            <option value="1">Aprobado</option>
                            <option value="0">No Aprobado</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-botones">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> ${esEdicion ? 'Actualizar' : 'Guardar'}
                    </button>
                    <button type="button" class="btn-secondary btn-cancelar">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        `;
        
        mostrarModal(titulo, formulario);
        cargarDatosResultadoModal();
    } else {
        // Modal con datos de inscripción o resultado existente
        formulario = `
            <form id="form-resultado" class="form-modal">
                ${esEdicion ? `<input type="hidden" name="id" value="${inscripcion.id_resultado}">` : ''}
                <input type="hidden" name="id_postulante" value="${inscripcion.id_postulante}">
                <input type="hidden" name="id_carrera" value="${inscripcion.id_carrera}">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Postulante</label>
                        <input type="text" value="${inscripcion.nombres} ${inscripcion.apellido_paterno} ${inscripcion.apellido_materno}" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Carrera</label>
                        <input type="text" value="${inscripcion.carrera_nombre}" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="puntaje">Puntaje *</label>
                        <input type="number" id="puntaje" name="puntaje" value="${inscripcion.puntaje || ''}" min="0" max="100" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="aprobado">Estado</label>
                        <select id="aprobado" name="aprobado">
                            <option value="1" ${(inscripcion.aprobado == 1 || inscripcion.puntaje >= 60) ? 'selected' : ''}>Aprobado</option>
                            <option value="0" ${(inscripcion.aprobado == 0 || (inscripcion.puntaje && inscripcion.puntaje < 60)) ? 'selected' : ''}>No Aprobado</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-botones">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> ${esEdicion ? 'Actualizar' : 'Guardar'}
                    </button>
                    <button type="button" class="btn-secondary btn-cancelar">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        `;
        
        mostrarModal(titulo, formulario);
    }
    
    // Configurar envío del formulario
    const form = document.getElementById('form-resultado');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const datos = Object.fromEntries(formData);
        
        if (esEdicion) {
            actualizarResultado(datos);
        } else {
            crearResultado(datos);
        }
    });
}

function cargarDatosResultadoModal() {
    // Cargar postulantes y carreras para el modal de nuevo resultado
    Promise.all([
        fetch('obtener_postulantes_inscripcion.php').then(r => r.json()),
        fetch('obtener_carreras.php').then(r => r.json())
    ])
    .then(([postulantes, carreras]) => {
        const selectPostulante = document.getElementById('id_postulante');
        const selectCarrera = document.getElementById('id_carrera');
        
        selectPostulante.innerHTML = '<option value="">Seleccionar postulante</option>' +
            postulantes.map(p => `<option value="${p.id_postulante}">${p.nombres} ${p.apellido_paterno} ${p.apellido_materno} - ${p.ci}</option>`).join('');
        
        selectCarrera.innerHTML = '<option value="">Seleccionar carrera</option>' +
            carreras.map(c => `<option value="${c.id_carrera}">${c.nombre}</option>`).join('');
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al cargar los datos del formulario');
    });
}

function crearResultado(datos) {
    mostrarLoading();
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=crear_resultado&${new URLSearchParams(datos)}`
    })
    .then(response => response.json())
    .then(data => {
        ocultarLoading();
        if (data.success) {
            mostrarMensaje('success', data.message);
            cerrarModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensaje('error', data.message);
        }
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al crear el resultado');
    });
}

function actualizarResultado(datos) {
    mostrarLoading();
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=actualizar_resultado&${new URLSearchParams(datos)}`
    })
    .then(response => response.json())
    .then(data => {
        ocultarLoading();
        if (data.success) {
            mostrarMensaje('success', data.message);
            cerrarModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensaje('error', data.message);
        }
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al actualizar el resultado');
    });
}

function verResultado(id) {
    mostrarLoading();
    
    fetch(`obtener_resultado.php?id=${id}`)
    .then(response => response.json())
    .then(resultado => {
        ocultarLoading();
        
        const contenido = `
            <div class="detalles-resultado">
                <div class="detalle-grupo">
                    <h4>Información del Resultado</h4>
                    <div class="detalle-item">
                        <strong>Folio de Consulta:</strong>
                        <span>${resultado.folio_consulta}</span>
                    </div>
                    <div class="detalle-item">
                        <strong>Postulante:</strong>
                        <span>${resultado.nombres} ${resultado.apellido_paterno} ${resultado.apellido_materno}</span>
                    </div>
                    <div class="detalle-item">
                        <strong>Carrera:</strong>
                        <span>${resultado.carrera_nombre}</span>
                    </div>
                    <div class="detalle-item">
                        <strong>Puntaje:</strong>
                        <span class="puntaje ${resultado.puntaje >= 60 ? 'puntaje-alto' : 'puntaje-bajo'}">${resultado.puntaje}</span>
                    </div>
                    <div class="detalle-item">
                        <strong>Estado:</strong>
                        <span class="estado-${resultado.aprobado ? 'aprobado' : 'no_aprobado'}">
                            ${resultado.aprobado ? 'Aprobado' : 'No Aprobado'}
                        </span>
                    </div>
                    <div class="detalle-item">
                        <strong>Fecha del Resultado:</strong>
                        <span>${new Date(resultado.fecha_resultado).toLocaleDateString()}</span>
                    </div>
                </div>
                
                ${resultado.carta_url ? `
                <div class="detalle-grupo">
                    <h4>Documentos</h4>
                    <div class="detalle-item">
                        <strong>Carta de Resultado:</strong>
                        <a href="${resultado.carta_url}" target="_blank" class="btn btn-primary">
                            <i class="fas fa-download"></i> Descargar Carta
                        </a>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
        
        mostrarModal('Detalles del Resultado', contenido);
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al cargar los detalles del resultado');
    });
}

function editarResultado(id) {
    mostrarLoading();
    
    fetch(`obtener_resultado.php?id=${id}`)
    .then(response => response.json())
    .then(resultado => {
        ocultarLoading();
        mostrarModalResultado(resultado);
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al cargar los datos del resultado');
    });
}

function eliminarResultado(id) {
    if (!confirm('¿Está seguro de que desea eliminar este resultado? Esta acción no se puede deshacer.')) {
        return;
    }
    
    mostrarLoading();
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=eliminar_resultado&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        ocultarLoading();
        if (data.success) {
            mostrarMensaje('success', data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensaje('error', data.message);
        }
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al eliminar el resultado');
    });
}

function publicarResultados() {
    if (!confirm('¿Está seguro de que desea publicar los resultados? Esta acción notificará a todos los postulantes.')) {
        return;
    }
    
    mostrarLoading();
    
    // Aquí puedes implementar la lógica para publicar resultados
    // Por ejemplo, cambiar el estado o notificar a los postulantes
    
    setTimeout(() => {
        ocultarLoading();
        mostrarMensaje('success', 'Resultados publicados exitosamente');
    }, 2000);
}

// Funciones para reportes
function generarReporte() {
    const fechaInicio = document.getElementById('fecha-inicio').value;
    const fechaFin = document.getElementById('fecha-fin').value;
    const carreraId = document.getElementById('carrera-reporte').value;
    
    mostrarLoading();
    
    fetch(`generar_reporte.php?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&carrera_id=${carreraId}`)
    .then(response => response.json())
    .then(reporte => {
        ocultarLoading();
        actualizarReportes(reporte);
    })
    .catch(error => {
        ocultarLoading();
        console.error('Error:', error);
        mostrarMensaje('error', 'Error al generar el reporte');
    });
}

function actualizarReportes(reporte) {
    // Actualizar gráficos y tablas con los datos del reporte
    // Esta función necesita ser implementada según la estructura de datos del reporte
    console.log('Datos del reporte:', reporte);
}

// Funciones de utilidad
function mostrarModal(titulo, contenido) {
    const modal = document.getElementById('modal-global');
    const modalContenido = document.getElementById('modal-contenido');
    
    modalContenido.innerHTML = `
        <h2>${titulo}</h2>
        ${contenido}
    `;
    
    modal.style.display = 'block';
}

function cerrarModal() {
    const modal = document.getElementById('modal-global');
    modal.style.display = 'none';
}

function mostrarLoading() {
    document.getElementById('loading').style.display = 'flex';
}

function ocultarLoading() {
    document.getElementById('loading').style.display = 'none';
}

function mostrarMensaje(tipo, mensaje) {
    // Crear elemento de mensaje
    const mensajeDiv = document.createElement('div');
    mensajeDiv.className = `mensaje mensaje-${tipo}`;
    mensajeDiv.innerHTML = `
        <span>${mensaje}</span>
        <button class="cerrar-mensaje">&times;</button>
    `;
    
    // Agregar al documento
    document.body.appendChild(mensajeDiv);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (mensajeDiv.parentNode) {
            mensajeDiv.parentNode.removeChild(mensajeDiv);
        }
    }, 5000);
    
    // Cerrar mensaje al hacer clic
    mensajeDiv.querySelector('.cerrar-mensaje').addEventListener('click', () => {
        mensajeDiv.parentNode.removeChild(mensajeDiv);
    });
}

function exportarAExcel(tablaId, nombreArchivo) {
    const tabla = document.getElementById(tablaId);
    if (!tabla) {
        mostrarMensaje('error', 'No se encontró la tabla para exportar');
        return;
    }

    let csv = [];
    
    // Obtener headers
    const headers = [];
    for (let i = 0; i < tabla.rows[0].cells.length; i++) {
        // Excluir columna de acciones
        if (!tabla.rows[0].cells[i].textContent.includes('Acciones')) {
            headers.push(tabla.rows[0].cells[i].textContent);
        }
    }
    csv.push(headers.join(','));
    
    // Obtener datos
    for (let i = 1; i < tabla.rows.length; i++) {
        const row = [];
        for (let j = 0; j < tabla.rows[i].cells.length; j++) {
            // Excluir columna de acciones
            if (j !== tabla.rows[i].cells.length - 1) {
                let cellContent = tabla.rows[i].cells[j].textContent;
                // Limpiar contenido (remover iconos, etc.)
                cellContent = cellContent.replace(/<[^>]*>/g, '').trim();
                // Escapar comas y comillas
                cellContent = cellContent.replace(/"/g, '""');
                if (cellContent.includes(',') || cellContent.includes('"')) {
                    cellContent = `"${cellContent}"`;
                }
                row.push(cellContent);
            }
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
    
    mostrarMensaje('success', `Archivo ${nombreArchivo}.csv descargado exitosamente`);
}

// Inicializar eventos de cancelar en modales
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-cancelar')) {
        cerrarModal();
    }
});

// Manejar cambios en el puntaje para actualizar automáticamente el estado de aprobación
document.addEventListener('input', function(e) {
    if (e.target.id === 'puntaje' && document.getElementById('aprobado')) {
        const puntaje = parseFloat(e.target.value) || 0;
        const selectAprobado = document.getElementById('aprobado');
        selectAprobado.value = puntaje >= 60 ? '1' : '0';
    }
});