"""Define los esquemas de usuarios.

Clases: UsuarioCreate | UsuarioUpdate
"""

from typing import Optional
from pydantic import BaseModel

class UsuarioCreate(BaseModel):
    """Define los datos para que un administrador cree un usuario.

    Attributes:
        nombre (str): Nombre completo.
        email (str): Correo electrónico.
        password (str): Contraseña de acceso.
        rol (str): Rol asignado ("admin" o "cliente"). Por defecto, "cliente".
    """

    nombre: str
    email: str
    password: str
    rol: str = "cliente"

class UsuarioUpdate(BaseModel):
    """Define los campos que pueden modificarse de un usuario existente.

    Attributes:
        nombre (Optional[str]): Nuevo nombre.
        email (Optional[str]): Nuevo correo electrónico.
        password (Optional[str]): Nueva contraseña.
        rol (Optional[str]): Nuevo rol.
    """

    nombre: Optional[str] = None
    email: Optional[str] = None
    password: Optional[str] = None
    rol: Optional[str] = None
