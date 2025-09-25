<?php
require_once 'conexion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Postulantes</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-users"></i> Sistema de Gestión de Postulantes</h1>
    </div>
    
    <div class="container">
        <div class="card">
            <h2><i class="fas fa-user-plus"></i> Nuevo Postulante</h2>
            <form id="formularioPostulante">
                <div class="fila-formulario">
                    <div class="grupo-formulario">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" class="control-formulario" required>
                    </div>
                    
                    <div class="grupo-formulario">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido" class="control-formulario" required>
                    </div>
                </div>

                <div class="fila-formulario">
                    <div class="grupo-formulario">
                        <label for="documento_tipo">Tipo Documento:</label>
                        <select id="documento_tipo" name="documento_tipo" class="control-formulario" required>
                            <option value="DNI">CI</option>
                            <option value="Pasaporte">Pasaporte</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="grupo-formulario">
                        <label for="documento_numero">Número Documento:</label>
                        <input type="text" id="documento_numero" name="documento_numero" class="control-formulario" required>
                    </div>
                </div>

                <div class="fila-formulario">
                    <div class="grupo-formulario">
                        <label for="fecha_nacimiento">Fecha Nacimiento:</label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="control-formulario" required>
                    </div>
                    
                    <div class="grupo-formulario">
                        <label for="genero">Género:</label>
                        <select id="genero" name="genero" class="control-formulario" required>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>

                <div class="grupo-formulario">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="control-formulario" required>
                </div>

                <div class="fila-formulario">
                    <div class="grupo-formulario">
                        <label for="telefono">Teléfono:</label>
                        <input type="text" id="telefono" name="telefono" class="control-formulario">
                    </div>
                    
                    <div class="grupo-formulario">
                        <label for="celular">Celular:</label>
                        <input type="text" id="celular" name="celular" class="control-formulario" required>
                    </div>
                </div>

                <div class="grupo-formulario">
                    <label for="direccion">Dirección:</label>
                    <textarea id="direccion" name="direccion" class="control-formulario"></textarea>
                </div>

                <div class="fila-formulario">
                    <div class="grupo-formulario">
                        <label for="ciudad">Ciudad:</label>
                        <input type="text" id="ciudad" name="ciudad" class="control-formulario">
                    </div>
                    
                    <div class="grupo-formulario">
                        <label for="departamento">Departamento:</label>
                        <input type="text" id="departamento" name="departamento" class="control-formulario">
                    </div>
                </div>

                <div class="grupo-formulario">
                    <label for="colegio_procedencia">Colegio de Procedencia:</label>
                    <input type="text" id="colegio_procedencia" name="colegio_procedencia" class="control-formulario">
                </div>

                <div class="fila-formulario">
                    <div class="grupo-formulario">
                        <label for="tipo_colegio">Tipo de Colegio:</label>
                        <select id="tipo_colegio" name="tipo_colegio" class="control-formulario">
                            <option value="Público">Público</option>
                            <option value="Privado">Privado</option>
                            <option value="Parroquial">Parroquial</option>
                        </select>
                    </div>
                    
                    <div class="grupo-formulario">
                        <label for="año_egreso">Año de Egreso:</label>
                        <input type="number" id="año_egreso" name="año_egreso" class="control-formulario" min="1950" max="2030">
                    </div>
                </div>

                <div class="grupo-formulario">
                    <label for="promedio_secundaria">Promedio Secundaria:</label>
                    <input type="number" id="promedio_secundaria" name="promedio_secundaria" class="control-formulario" step="0.01" min="0" max="20">
                </div>

                <div class="fila-formulario">
                    <div class="grupo-formulario">
                        <label for="carrera_id">Carrera:</label>
                        <select id="carrera_id" name="carrera_id" class="control-formulario" required>
                            <option value="">Seleccionar carrera</option>
                        </select>
                    </div>
                    
                    <div class="grupo-formulario">
                        <label for="periodo_postulacion">Periodo Postulación:</label>
                        <select id="periodo_postulacion" name="periodo_postulacion" class="control-formulario" required>
                            <option value="">Seleccionar periodo</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="boton boton-primario">
                    <i class="fas fa-save"></i> Guardar Postulante
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-filter"></i> Filtros de Búsqueda</h2>
            <div class="filtros">
                <div class="fila-formulario">
                    <div class="grupo-formulario">
                        <label for="filtroCarrera">Carrera:</label>
                        <select id="filtroCarrera" class="control-formulario">
                            <option value="">Todas las carreras</option>
                        </select>
                    </div>
                    
                    <div class="grupo-formulario">
                        <label for="filtroEstado">Estado:</label>
                        <select id="filtroEstado" class="control-formulario">
                            <option value="">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="habilitado">Habilitado</option>
                            <option value="rechazado">Rechazado</option>
                        </select>
                    </div>
                </div>

                <div class="fila-formulario">
                    <div class="grupo-formulario">
                        <label for="filtroPeriodo">Periodo:</label>
                        <select id="filtroPeriodo" class="control-formulario">
                            <option value="">Todos los periodos</option>
                        </select>
                    </div>
                    
                    <div class="grupo-formulario">
                        <label for="buscarDocumento">Buscar por Documento:</label>
                        <input type="text" id="buscarDocumento" class="control-formulario" placeholder="Ingrese documento">
                    </div>
                </div>

                <button id="botonBuscar" class="boton boton-secundario">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <button id="botonLimpiar" class="boton boton-outline">
                    <i class="fas fa-broom"></i> Limpiar
                </button>
            </div>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-list"></i> Lista de Postulantes</h2>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Documento</th>
                            <th>Edad</th>
                            <th>Carrera</th>
                            <th>Periodo</th>
                            <th>Estado</th>
                            <th>Fecha Postulación</th>
                            <th>Nota Examen</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaPostulantes">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modalExamen" class="modal">
        <div class="modal-contenido">
            <span class="cerrar">&times;</span>
            <h3>Programar Examen de Admisión</h3>
            <form id="formularioExamen">
                <input type="hidden" id="postulante_id_examen" name="postulante_id">
                <div class="grupo-formulario">
                    <label for="fecha_examen">Fecha y Hora:</label>
                    <input type="datetime-local" id="fecha_examen" name="fecha_examen" class="control-formulario" required>
                </div>
                <div class="grupo-formulario">
                    <label for="aula_examen">Aula:</label>
                    <input type="text" id="aula_examen" name="aula_examen" class="control-formulario" required>
                </div>
                <div class="grupo-formulario">
                    <label for="nota_examen">Nota Examen:</label>
                    <input type="number" id="nota_examen" name="nota_examen" class="control-formulario" step="0.01" min="0" max="100">
                </div>
                <button type="submit" class="boton boton-primario">Programar Examen</button>
            </form>
        </div>
    </div>

    <div id="modalEstado" class="modal">
        <div class="modal-contenido">
            <span class="cerrar">&times;</span>
            <h3>Cambiar Estado del Postulante</h3>
            <form id="formularioEstado">
                <input type="hidden" id="postulante_id_estado" name="postulante_id">
                <div class="grupo-formulario">
                    <label for="nuevo_estado">Nuevo Estado:</label>
                    <select id="nuevo_estado" name="nuevo_estado" class="control-formulario" required>
                        <option value="pendiente">Pendiente</option>
                        <option value="habilitado">Habilitado</option>
                        <option value="rechazado">Rechazado</option>
                    </select>
                </div>
                <button type="submit" class="boton boton-primario">Cambiar Estado</button>
            </form>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>