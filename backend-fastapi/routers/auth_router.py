"""Define los endpoints de autenticación.

Endpoints:
    POST /login: Valida credenciales y retorna los datos del usuario.
    POST /registro: Registra un cliente (el rol siempre es "cliente").
"""

from fastapi import APIRouter, HTTPException
from schemas.auth import LoginRequest, RegistroRequest
from services.auth_service import ServicioAuth

router = APIRouter(tags=["Auth"])
auth_service = ServicioAuth()

@router.post("/login")
def login(req: LoginRequest):
    """Autentica a un usuario; responde 401 si las credenciales son incorrectas."""

    try:
        return auth_service.login(req.email, req.password)
    except ValueError as e:
        raise HTTPException(status_code=401, detail=str(e)) from e

@router.post("/registro")
def registro(req: RegistroRequest):
    """Registra un nuevo cliente; responde 400 si el correo ya existe."""

    try:
        return auth_service.registrar(req.nombre, req.email, req.password,
                                      req.telefono, req.direccion)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e)) from e
