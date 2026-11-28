"""Define los esquemas de clientes.

Clases: ClienteCreate | ClienteUpdate
"""

from typing import Optional
from pydantic import BaseModel

class ClienteCreate(BaseModel):
    """Define los datos para crear un cliente junto con su usuario.

    Attributes:
        nombre (str): Nombre completo.
        email (str): Correo electrónico.
        password (str): Contraseña de acceso.
        telefono (str): Teléfono de contacto. Por defecto, vacío.
        direccion (str): Dirección. Por defecto, vacía.
    """

    nombre: str
    email: str
    password: str
    telefono: str = ""
    direccion: str = ""

class ClienteUpdate(BaseModel):
    """Define los campos de contacto que pueden modificarse de un cliente.

    Attributes:
        telefono (Optional[str]): Nuevo teléfono.
        direccion (Optional[str]): Nueva dirección.
    """

    telefono: Optional[str] = None
    direccion: Optional[str] = None
