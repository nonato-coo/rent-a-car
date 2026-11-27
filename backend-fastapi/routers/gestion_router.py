"""gestion_router.py"""

from fastapi import APIRouter, HTTPException
from database import obtener_conexion
from schemas.modelos import VehiculoCreate, VehiculoUpdate, EstadoAlquilerUpdate
from domain.vehiculo import Vehiculo
from repositories.vehiculo_repository import VehiculoRepository
from repositories.alquiler_repository import AlquilerRepository
from services.inventario_service import ServicioInventario
from utils.funciones import extraer_marcas_unicas, extraer_anios_unicos, sumar_montos

router = APIRouter(tags=["Gestión"])
vehiculo_repo = VehiculoRepository()
alquiler_repo = AlquilerRepository()
inventario_service = ServicioInventario()

@router.get("/marcas")
def obtener_marcas():
    """Obtiene las marcas únicas de los vehículos registrados.

    Returns:
        dict: Diccionario que contiene una lista de marcas únicas
            ordenadas alfabéticamente.
    """
    vehiculos_dict = [v.to_dict() for v in vehiculo_repo.listar_todos()]
    return {"marcas": extraer_marcas_unicas(vehiculos_dict)}

@router.get("/anios")
def obtener_anios():
    """Obtiene los años de fabricación únicos de los vehículos registrados.

    Returns:
        dict: Diccionario que contiene una lista de años únicos ordenados
            de forma descendente.
    """
    vehiculos_dict = [v.to_dict() for v in vehiculo_repo.listar_todos()]
    return {"anios": extraer_anios_unicos(vehiculos_dict)}

@router.post("/vehiculos")
def crear_vehiculo(data: VehiculoCreate):
    """Registra un nuevo vehículo en el inventario.

    Args:
        data (VehiculoCreate): Datos del vehículo que se desea registrar,
            incluyendo marca, modelo, año, categoría, precio y stock inicial.

    Returns:
        dict: Datos del vehículo creado, incluyendo su identificador
            asignado por la base de datos.
    """
    v = Vehiculo(
        marca=data.marca,
        modelo=data.modelo,
        anio=data.anio,
        categoria=data.categoria,
        precio_por_dia=data.precio_por_dia,
        stock_total=data.stock_total,
        habilitado=data.habilitado
    )
    nuevo_id = vehiculo_repo.crear(v)
    v.id = nuevo_id
    return v.to_dict()

@router.put("/vehiculos/{vehiculo_id}")
def actualizar_vehiculo(vehiculo_id: int, data: VehiculoUpdate):
    """Actualiza los datos de un vehículo existente.

    Args:
        vehiculo_id (int): Identificador del vehículo que se desea actualizar.
        data (VehiculoUpdate): Campos del vehículo que se desean modificar.
            Los campos no proporcionados conservan sus valores actuales.

    Returns:
        dict: Datos actualizados del vehículo.

    Raises:
        HTTPException: Si el vehículo no existe, devuelve el código HTTP 404.
    """
    actual = vehiculo_repo.obtener_por_id(vehiculo_id)
    if not actual:
        raise HTTPException(status_code=404, detail="Vehículo no encontrado")

    conexion = obtener_conexion()
    cursor = conexion.cursor()
    marca = data.marca if data.marca is not None else actual.marca
    modelo = data.modelo if data.modelo is not None else actual.modelo
    anio = data.anio if data.anio is not None else actual.anio
    categoria = data.categoria if data.categoria is not None else actual.categoria
    precio = data.precio_por_dia if data.precio_por_dia is not None else actual.precio_por_dia
    habilitado = data.habilitado if data.habilitado is not None else actual.habilitado

    cursor.execute("""
        UPDATE vehiculos
        SET marca = ?, modelo = ?, anio = ?, categoria = ?, precio_por_dia = ?, habilitado = ?
        WHERE id = ?
    """, (marca, modelo, anio, categoria, precio, habilitado, vehiculo_id))
    conexion.commit()
    conexion.close()
    return vehiculo_repo.obtener_por_id(vehiculo_id).to_dict()

@router.get("/alquileres")
def listar_alquileres():
    """Obtiene todos los alquileres registrados en el sistema.

    Returns:
        list[dict]: Lista de alquileres ordenados por identificador,
            del más reciente al más antiguo.
    """
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cursor.execute("SELECT * FROM alquileres ORDER BY id DESC")
    filas = [dict(f) for f in cursor.fetchall()]
    conexion.close()
    return filas

@router.put("/alquileres/{alquiler_id}/estado")
def cambiar_estado_alquiler(alquiler_id: int, req: EstadoAlquilerUpdate):
    """Actualiza el estado de un alquiler existente.

    Args:
        alquiler_id (int): Identificador del alquiler que se desea modificar.
        req (EstadoAlquilerUpdate): Nuevo estado que se asignará al alquiler.

    Returns:
        dict: Identificador del alquiler y nuevo estado asignado.

    Raises:
        HTTPException: Si el estado proporcionado no pertenece a los estados
            permitidos, devuelve el código HTTP 400.
    """
    estados_validos = ["reservado", "activo", "devuelto", "cancelado"]
    if req.estado not in estados_validos:
        raise HTTPException(status_code=400, detail="Estado inválido")
    alquiler_repo.actualizar_estado(alquiler_id, req.estado)
    return {"alquiler_id": alquiler_id, "estado": req.estado}

@router.get("/facturas")
def listar_facturas():
    """Obtiene las facturas registradas junto con sus detalles y el total facturado.

    Returns:
        dict: Diccionario que contiene la lista de facturas, cada una con
            sus respectivos detalles, y la suma total de las facturas.
    """
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cursor.execute("SELECT * FROM facturas ORDER BY id DESC")
    facturas = [dict(f) for f in cursor.fetchall()]
    for f in facturas:
        cursor.execute("SELECT * FROM detalle_factura WHERE factura_id = ?", (f["id"],))
        f["detalles"] = [dict(d) for d in cursor.fetchall()]
    conexion.close()
    total_facturado = sumar_montos([f["total"] for f in facturas])
    return {"facturas": facturas, "total_facturado": total_facturado}

@router.get("/clientes/{cliente_id}/alquileres")
def alquileres_por_cliente(cliente_id: int):
    """Obtiene los alquileres realizados por un cliente específico.

    Args:
        cliente_id (int): Identificador del cliente cuyos alquileres
            se desean consultar.

    Returns:
        list[dict]: Lista de alquileres del cliente, incluyendo información
            del vehículo asociado, como marca, modelo y año.
    """
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cursor.execute("""
        SELECT a.*, v.marca, v.modelo, v.anio
        FROM alquileres a
        JOIN vehiculos v ON a.vehiculo_id = v.id
        WHERE a.cliente_id = ?
        ORDER BY a.id DESC
    """, (cliente_id,))
    filas = [dict(f) for f in cursor.fetchall()]
    conexion.close()
    return filas

@router.get("/usuarios")
def listar_usuarios():
    """Obtiene la lista de usuarios registrados en el sistema.

    Returns:
        list[dict]: Lista de usuarios con sus identificadores,
            nombres, correos electrónicos y roles.
    """
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cursor.execute("SELECT id, nombre, email, rol FROM usuarios")
    filas = [dict(f) for f in cursor.fetchall()]
    conexion.close()
    return filas

@router.get("/clientes")
def listar_clientes():
    """Obtiene la lista de clientes registrados en el sistema.

    Returns:
        list[dict]: Lista de clientes con sus identificadores, datos
            de usuario, números de teléfono y direcciones.
    """
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cursor.execute("""
        SELECT c.id, c.usuario_id, u.nombre, u.email, c.telefono, c.direccion
        FROM clientes c
        LEFT JOIN usuarios u ON c.usuario_id = u.id
    """)
    filas = [dict(f) for f in cursor.fetchall()]
    conexion.close()
    return filas

@router.delete("/vehiculos/{vehiculo_id}")
def eliminar_vehiculo(vehiculo_id: int):
    """Elimina un vehículo de la base de datos.

    Args:
        vehiculo_id (int): Identificador del vehículo que se desea eliminar.

    Returns:
        dict: Mensaje de confirmación y el identificador del vehículo eliminado.

    Raises:
        HTTPException: Si el vehículo no existe, devuelve el código HTTP 404.
    """
    actual = vehiculo_repo.obtener_por_id(vehiculo_id)
    if not actual:
        raise HTTPException(status_code=404, detail="Vehículo no encontrado")
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cursor.execute("DELETE FROM vehiculos WHERE id = ?", (vehiculo_id,))
    conexion.commit()
    conexion.close()
    return {"mensaje": "Vehículo eliminado", "id": vehiculo_id}
