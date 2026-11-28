"""Define los esquemas de autenticación.

Clases: LoginRequest | RegistroRequest
"""

from pydantic import BaseModel

class LoginRequest(BaseModel):
    """Define las credenciales necesarias para iniciar sesión.

    Attributes:
        email (str): Correo electrónico del usuario.
        password (str): Contraseña de acceso del usuario.
    """

    email: str
    password: str

class RegistroRequest(BaseModel):
    """Define los datos para que un cliente se registre por sí mismo.

    El rol no se recibe: el registro público siempre crea usuarios con rol "cliente".

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
