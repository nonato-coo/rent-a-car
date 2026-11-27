"""Módulo de autenticación y registro de usuarios.

Endpoints disponibles:
    - POST /login: Valida credenciales y retorna la información básica del usuario.
    - POST /registro: Crea un nuevo usuario y, si aplica, su registro como cliente.
"""

from fastapi import APIRouter, HTTPException
from database import obtener_conexion
from schemas.modelos import LoginRequest, RegistroRequest

router = APIRouter(tags=["Auth"])

@router.post("/login")
def login(req: LoginRequest):
    """Autentica a un usuario mediante sus credenciales de acceso.

    Args:
        req (LoginRequest): Correo electrónico y contraseña del usuario.

    Returns:
        dict: Datos del usuario autenticado, incluyendo su identificador,
            nombre, correo electrónico y rol.

    Raises:
        HTTPException: Si las credenciales son incorrectas, devuelve
            el código HTTP 401.
    """

    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cursor.execute("SELECT id, nombre, email, rol FROM usuarios WHERE email = ? AND password = ?",
                   (req.email, req.password))
    usuario = cursor.fetchone()
    conexion.close()

    if not usuario:
        raise HTTPException(status_code=401, detail="Credenciales incorrectas")

    return {
        "id": usuario["id"],
        "nombre": usuario["nombre"],
        "email": usuario["email"],
        "rol": usuario["rol"]
    }

@router.post("/registro")
def registro(req: RegistroRequest):
    """Registra un nuevo usuario y, si corresponde, crea su perfil de cliente.

    Args:
        req (RegistroRequest): Datos personales, credenciales y rol del
            usuario que se desea registrar.

    Returns:
        dict: Identificadores del usuario y del cliente, si corresponde,
            junto con el nombre y el rol asignados.

    Raises:
        HTTPException: Si ocurre un error al registrar el usuario o crear
            su perfil de cliente, devuelve el código HTTP 400.
    """

    conexion = obtener_conexion()
    cursor = conexion.cursor()
    try:
        cursor.execute("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)",
                       (req.nombre, req.email, req.password, req.rol))
        usuario_id = cursor.lastrowid
        cliente_id = None
        if req.rol == "cliente":
            cursor.execute("INSERT INTO clientes (usuario_id, telefono, direccion)VALUES (?, ?, ?)",
                           (usuario_id, req.telefono, req.direccion))
            cliente_id = cursor.lastrowid
        conexion.commit()

    except Exception as e:
        conexion.close()
        raise HTTPException(
            status_code=400,
            detail=f"No se pudo registrar el usuario: {str(e)}"
        ) from e

    conexion.close()
    return {"usuario_id": usuario_id, "cliente_id": cliente_id,
            "nombre": req.nombre, "rol": req.rol}
