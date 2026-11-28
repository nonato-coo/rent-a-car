"""Define los endpoints del CRUD de clientes.

Endpoints:
    GET /clientes | GET /clientes/{id} | POST /clientes
    PUT /clientes/{id} | DELETE /clientes/{id}
    GET /clientes/{id}/alquileres
"""

from fastapi import APIRouter, HTTPException
from schemas.cliente import ClienteCreate, ClienteUpdate
from repositories.cliente_repository import ClienteRepository
from repositories.usuario_repository import UsuarioRepository
from repositories.alquiler_repository import AlquilerRepository
from repositories.vehiculo_repository import VehiculoRepository
from services.auth_service import ServicioAuth

router = APIRouter(tags=["Clientes"])
cliente_repo = ClienteRepository()
usuario_repo = UsuarioRepository()
alquiler_repo = AlquilerRepository()
vehiculo_repo = VehiculoRepository()
auth_service = ServicioAuth()

def _a_dict(cliente) -> dict:
    """Convierte un cliente en diccionario e incluye el nombre y correo de su usuario."""

    datos = cliente.to_dict()
    usuario = usuario_repo.obtener_por_id(cliente.usuario_id)
    datos["nombre"] = usuario.nombre if usuario else None
    datos["email"] = usuario.email if usuario else None
    return datos

def _obtener_o_404(cliente_id: int):
    """Retorna el cliente o responde 404 si no existe."""

    cliente = cliente_repo.obtener_por_id(cliente_id)
    if not cliente:
        raise HTTPException(status_code=404, detail="Cliente no encontrado")
    return cliente

@router.get("/clientes")
@router.get("/clientes/", include_in_schema=False)
def listar_clientes():
    """Obtiene todos los clientes con su nombre y correo."""

    return [_a_dict(c) for c in cliente_repo.listar_todos()]

@router.get("/clientes/{cliente_id}")
def obtener_cliente(cliente_id: int):
    """Obtiene un cliente por su id; responde 404 si no existe."""

    return _a_dict(_obtener_o_404(cliente_id))

@router.post("/clientes")
@router.post("/clientes/", include_in_schema=False)
def crear_cliente(data: ClienteCreate):
    """Crea un cliente junto con su usuario."""

    try:
        creado = auth_service.registrar(data.nombre, data.email, data.password,
                                        data.telefono, data.direccion)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e)) from e
    return _a_dict(cliente_repo.obtener_por_id(creado["cliente_id"]))

@router.put("/clientes/{cliente_id}")
def actualizar_cliente(cliente_id: int, data: ClienteUpdate):
    """Actualiza el teléfono y la dirección de un cliente."""

    cliente = _obtener_o_404(cliente_id)
    cliente.actualizar_contacto(
        data.telefono if data.telefono is not None else cliente.telefono,
        data.direccion if data.direccion is not None else cliente.direccion)
    cliente_repo.actualizar(cliente)
    return _a_dict(cliente)

@router.delete("/clientes/{cliente_id}")
def eliminar_cliente(cliente_id: int):
    """Elimina un cliente; responde 400 si tiene alquileres registrados."""

    _obtener_o_404(cliente_id)
    if alquiler_repo.listar_por_cliente(cliente_id):
        raise HTTPException(status_code=400,
                            detail="No se puede eliminar: el cliente tiene alquileres registrados.")
    cliente_repo.eliminar(cliente_id)
    return {"mensaje": "Cliente eliminado", "id": cliente_id}

@router.get("/clientes/{cliente_id}/alquileres")
def listar_alquileres_cliente(cliente_id: int):
    """Obtiene los alquileres de un cliente con la marca, modelo y año del vehículo."""

    _obtener_o_404(cliente_id)
    resultado = []
    for alquiler in sorted(alquiler_repo.listar_por_cliente(cliente_id), key=lambda a: a.id, reverse=True):
        datos = alquiler.to_dict()
        vehiculo = vehiculo_repo.obtener_por_id(alquiler.vehiculo_id)
        datos["marca"] = vehiculo.marca if vehiculo else None
        datos["modelo"] = vehiculo.modelo if vehiculo else None
        datos["anio"] = vehiculo.anio if vehiculo else None
        resultado.append(datos)
    return resultado
