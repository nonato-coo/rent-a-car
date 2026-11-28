"""Define los endpoints del CRUD de usuarios.

Endpoints:
    GET /usuarios | GET /usuarios/{id} | POST /usuarios
    PUT /usuarios/{id} | DELETE /usuarios/{id}
"""

from fastapi import APIRouter, HTTPException
from domain.usuario import Usuario
from schemas.usuario import UsuarioCreate, UsuarioUpdate
from repositories.usuario_repository import UsuarioRepository
from repositories.cliente_repository import ClienteRepository
from repositories.alquiler_repository import AlquilerRepository
from services.auth_service import ServicioAuth

router = APIRouter(tags=["Usuarios"])
usuario_repo = UsuarioRepository()
cliente_repo = ClienteRepository()
alquiler_repo = AlquilerRepository()
auth_service = ServicioAuth()

@router.get("/usuarios")
@router.get("/usuarios/", include_in_schema=False)
def listar_usuarios():
    """Obtiene todos los usuarios (sin contraseñas)."""

    return [u.to_dict() for u in usuario_repo.listar_todos()]

@router.get("/usuarios/{usuario_id}")
def obtener_usuario(usuario_id: int):
    """Obtiene un usuario por su id; responde 404 si no existe."""

    usuario = usuario_repo.obtener_por_id(usuario_id)
    if not usuario:
        raise HTTPException(status_code=404, detail="Usuario no encontrado")
    return usuario.to_dict()

@router.post("/usuarios")
@router.post("/usuarios/", include_in_schema=False)
def crear_usuario(data: UsuarioCreate):
    """Crea un usuario con el rol indicado (y su perfil de cliente si aplica)."""

    try:
        return auth_service.crear_usuario(data.nombre, data.email, data.password, data.rol)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e)) from e

@router.put("/usuarios/{usuario_id}")
def actualizar_usuario(usuario_id: int, data: UsuarioUpdate):
    """Actualiza los campos recibidos de un usuario; una contraseña vacía se ignora."""

    actual = usuario_repo.obtener_por_id(usuario_id)
    if not actual:
        raise HTTPException(status_code=404, detail="Usuario no encontrado")

    email = data.email or actual.email
    existente = usuario_repo.buscar_por_email(email)
    if existente and existente.id != usuario_id:
        raise HTTPException(status_code=400, detail="El correo ya está registrado.")

    try:
        editado = Usuario(usuario_id=usuario_id, nombre=data.nombre or actual.nombre, email=email,
                          password=data.password or actual.password, rol=data.rol or actual.rol)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e)) from e

    usuario_repo.actualizar(editado)
    return editado.to_dict()

@router.delete("/usuarios/{usuario_id}")
def eliminar_usuario(usuario_id: int):
    """Elimina un usuario y su perfil de cliente; responde 400 si el cliente tiene alquileres."""

    if not usuario_repo.obtener_por_id(usuario_id):
        raise HTTPException(status_code=404, detail="Usuario no encontrado")

    cliente = cliente_repo.obtener_por_usuario_id(usuario_id)
    if cliente:
        if alquiler_repo.listar_por_cliente(cliente.id):
            raise HTTPException(status_code=400,
                                detail="No se puede eliminar: el cliente tiene alquileres registrados.")
        cliente_repo.eliminar(cliente.id)

    usuario_repo.eliminar(usuario_id)
    return {"mensaje": "Usuario eliminado", "id": usuario_id}
