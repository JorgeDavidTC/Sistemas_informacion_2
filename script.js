class GestorPostulantes {
    constructor() {
        this.carreras = [];
        this.periodos = [];
        this.postulantes = [];
        this.iniciar();
    }

    async iniciar() {
        await this.cargarCarreras();
        await this.cargarPeriodos();
        this.configurarEventos();
        await this.cargarPostulantes();
    }

    async cargarCarreras() {
        try {
            const respuesta = await fetch('acciones.php?accion=cargar_carreras');
            this.carreras = await respuesta.json();
            
            const selectCarrera = document.getElementById('carrera_id');
            const selectFiltroCarrera = document.getElementById('filtroCarrera');
            
            this.carreras.forEach(carrera => {
                const opcion = new Option(`${carrera.nombre} (${carrera.codigo})`, carrera.id);
                const opcionFiltro = new Option(`${carrera.nombre} (${carrera.codigo})`, carrera.id);
                
                selectCarrera.add(opcion);
                selectFiltroCarrera.add(opcionFiltro);
            });
        } catch (error) {
            console.error('Error cargando carreras:', error);
        }
    }

    async cargarPeriodos() {
        try {
            const respuesta = await fetch('acciones.php?accion=cargar_periodos');
            this.periodos = await respuesta.json();
            
            const selectPeriodo = document.getElementById('periodo_postulacion');
            const selectFiltroPeriodo = document.getElementById('filtroPeriodo');
            
            this.periodos.forEach(periodo => {
                const opcion = new Option(periodo, periodo);
                const opcionFiltro = new Option(periodo, periodo);
                
                selectPeriodo.add(opcion);
                selectFiltroPeriodo.add(opcionFiltro);
            });
        } catch (error) {
            console.error('Error cargando periodos:', error);
        }
    }

    async cargarPostulantes(filtros = {}) {
        try {
            let url = 'acciones.php?accion=cargar_postulantes';
            
            if (filtros.carrera_id) url += `&carrera_id=${filtros.carrera_id}`;
            if (filtros.estado) url += `&estado=${filtros.estado}`;
            if (filtros.documento) url += `&documento=${filtros.documento}`;
            if (filtros.periodo) url += `&periodo=${filtros.periodo}`;
            
            const respuesta = await fetch(url);
            this.postulantes = await respuesta.json();
            this.mostrarPostulantes();
        } catch (error) {
            console.error('Error cargando postulantes:', error);
        }
    }

    mostrarPostulantes() {
        const tbody = document.getElementById('tablaPostulantes');
        tbody.innerHTML = '';

        this.postulantes.forEach(postulante => {
            const fila = document.createElement('tr');
            
            let claseEstado = '';
            switch(postulante.estado) {
                case 'habilitado': claseEstado = 'estado-habilitado'; break;
                case 'rechazado': claseEstado = 'estado-rechazado'; break;
                default: claseEstado = 'estado-pendiente';
            }

            fila.innerHTML = `
                <td>${postulante.nombre_completo}</td>
                <td>${postulante.documento_tipo}: ${postulante.documento_numero}</td>
                <td>${postulante.edad} años</td>
                <td>${postulante.nombre_carrera}</td>
                <td>${postulante.periodo_postulacion}</td>
                <td><span class="estado ${claseEstado}">${postulante.estado}</span></td>
                <td>${postulante.fecha_postulacion}</td>
                <td>${postulante.nota_examen || 'N/A'}</td>
                <td>
                    <button class="boton boton-sm boton-info boton-ver" data-id="${postulante.id}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="boton boton-sm boton-advertencia boton-examen" data-id="${postulante.id}">
                        <i class="fas fa-edit"></i> Examen
                    </button>
                    <button class="boton boton-sm boton-primario boton-estado" data-id="${postulante.id}">
                        <i class="fas fa-sync"></i> Estado
                    </button>
                </td>
            `;
            
            tbody.appendChild(fila);
        });

        this.configurarEventosTabla();
    }

    configurarEventos() {
        document.getElementById('formularioPostulante').addEventListener('submit', (e) => {
            e.preventDefault();
            this.guardarPostulante();
        });

        document.getElementById('botonBuscar').addEventListener('click', () => {
            this.aplicarFiltros();
        });

        document.getElementById('botonLimpiar').addEventListener('click', () => {
            this.limpiarFiltros();
        });

        this.configurarModales();
    }

    configurarEventosTabla() {
        document.querySelectorAll('.boton-examen').forEach(boton => {
            boton.addEventListener('click', () => {
                const postulanteId = boton.getAttribute('data-id');
                this.abrirModalExamen(postulanteId);
            });
        });

        document.querySelectorAll('.boton-estado').forEach(boton => {
            boton.addEventListener('click', () => {
                const postulanteId = boton.getAttribute('data-id');
                this.abrirModalEstado(postulanteId);
            });
        });

        document.querySelectorAll('.boton-ver').forEach(boton => {
            boton.addEventListener('click', () => {
                const postulanteId = boton.getAttribute('data-id');
                this.verPostulante(postulanteId);
            });
        });
    }

    configurarModales() {
        const modales = document.querySelectorAll('.modal');
        
        modales.forEach(modal => {
            const cerrar = modal.querySelector('.cerrar');
            cerrar.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        });

        document.getElementById('formularioExamen').addEventListener('submit', (e) => {
            e.preventDefault();
            this.programarExamen();
        });

        document.getElementById('formularioEstado').addEventListener('submit', (e) => {
            e.preventDefault();
            this.cambiarEstado();
        });

        document.getElementById('nuevo_estado').addEventListener('change', (e) => {
            this.manejarCambioEstado(e.target.value);
        });
    }

    async guardarPostulante() {
        const datosFormulario = new FormData(document.getElementById('formularioPostulante'));
        
        try {
            const respuesta = await fetch('acciones.php?accion=guardar_postulante', {
                method: 'POST',
                body: datosFormulario
            });
            
            const resultado = await respuesta.json();
            
            if (resultado.exito) {
                alert('Postulante guardado correctamente');
                document.getElementById('formularioPostulante').reset();
                await this.cargarPostulantes();
            } else {
                alert('Error: ' + resultado.mensaje);
            }
        } catch (error) {
            console.error('Error guardando postulante:', error);
            alert('Error al guardar el postulante');
        }
    }

    aplicarFiltros() {
        const filtros = {
            carrera_id: document.getElementById('filtroCarrera').value,
            estado: document.getElementById('filtroEstado').value,
            documento: document.getElementById('buscarDocumento').value,
            periodo: document.getElementById('filtroPeriodo').value
        };
        
        this.cargarPostulantes(filtros);
    }

    limpiarFiltros() {
        document.getElementById('filtroCarrera').value = '';
        document.getElementById('filtroEstado').value = '';
        document.getElementById('buscarDocumento').value = '';
        document.getElementById('filtroPeriodo').value = '';
        this.cargarPostulantes();
    }

    abrirModalExamen(postulanteId) {
        document.getElementById('postulante_id_examen').value = postulanteId;
        document.getElementById('modalExamen').style.display = 'block';
    }

    abrirModalEstado(postulanteId) {
        document.getElementById('postulante_id_estado').value = postulanteId;
        document.getElementById('modalEstado').style.display = 'block';
    }

    manejarCambioEstado(nuevoEstado) {
        if (nuevoEstado === 'habilitado') {
            document.getElementById('modalEstado').style.display = 'none';
            this.abrirModalExamen(document.getElementById('postulante_id_estado').value);
        }
    }

    async programarExamen() {
        const datosFormulario = new FormData(document.getElementById('formularioExamen'));
        
        try {
            const respuesta = await fetch('acciones.php?accion=programar_examen', {
                method: 'POST',
                body: datosFormulario
            });
            
            const resultado = await respuesta.json();
            
            if (resultado.exito) {
                alert('Examen programado correctamente');
                document.getElementById('modalExamen').style.display = 'none';
                document.getElementById('formularioExamen').reset();
                await this.cargarPostulantes();
            } else {
                alert('Error: ' + resultado.mensaje);
            }
        } catch (error) {
            console.error('Error programando examen:', error);
            alert('Error al programar el examen');
        }
    }

    async cambiarEstado() {
        const datosFormulario = new FormData(document.getElementById('formularioEstado'));
        const nuevoEstado = datosFormulario.get('nuevo_estado');
        
        if (nuevoEstado === 'habilitado') {
            this.abrirModalExamen(document.getElementById('postulante_id_estado').value);
            return;
        }
        
        try {
            const respuesta = await fetch('acciones.php?accion=cambiar_estado', {
                method: 'POST',
                body: datosFormulario
            });
            
            const resultado = await respuesta.json();
            
            if (resultado.exito) {
                alert('Estado cambiado correctamente');
                document.getElementById('modalEstado').style.display = 'none';
                document.getElementById('formularioEstado').reset();
                await this.cargarPostulantes();
            } else {
                alert('Error: ' + resultado.mensaje);
            }
        } catch (error) {
            console.error('Error cambiando estado:', error);
            alert('Error al cambiar el estado');
        }
    }

    verPostulante(postulanteId) {
        const postulante = this.postulantes.find(p => p.id == postulanteId);
        
        if (postulante) {
            let detalles = `
                <strong>Información Personal:</strong><br>
                Nombre: ${postulante.nombre_completo}<br>
                Documento: ${postulante.documento_tipo}: ${postulante.documento_numero}<br>
                Fecha Nacimiento: ${postulante.fecha_nacimiento} (${postulante.edad} años)<br>
                Género: ${postulante.genero}<br>
                Email: ${postulante.email}<br>
                Teléfono: ${postulante.telefono || 'N/A'}<br>
                Celular: ${postulante.celular || 'N/A'}<br><br>
                
                <strong>Información Académica:</strong><br>
                Colegio: ${postulante.colegio_procedencia}<br>
                Tipo Colegio: ${postulante.tipo_colegio}<br>
                Año Egreso: ${postulante.año_egreso || 'N/A'}<br>
                Promedio: ${postulante.promedio_secundaria || 'N/A'}<br><br>
                
                <strong>Postulación:</strong><br>
                Carrera: ${postulante.nombre_carrera}<br>
                Periodo: ${postulante.periodo_postulacion}<br>
                Estado: ${postulante.estado}<br>
                Fecha Postulación: ${postulante.fecha_postulacion}<br>
            `;
            
            if (postulante.fecha_examen) {
                detalles += `Fecha Examen: ${postulante.fecha_examen}<br>`;
                detalles += `Aula: ${postulante.aula_examen}<br>`;
                detalles += `Nota: ${postulante.nota_examen || 'N/A'}<br>`;
            }
            
            alert(detalles);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new GestorPostulantes();
});