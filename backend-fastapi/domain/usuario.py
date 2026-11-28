"""Define la clase 'Usuario', hereda de `Entidad`.

Atributos: id (int) | nombre (str) | email (str) | rol (str, property) | password (str, property)
Métodos: es_admin() | verificar_password(password: str) -> bool | to_dict() -> dict
"""

from core.config import ROLES
from domain.entidad import Entidad

class Usuario(Entidad):
    """Representa un usuario del sistema con credenciales y rol asignado."""

    def __init__(self, nombre: str, email: str, password: str, rol: str, usuario_id: int = None):
        """Inicializa una nueva instancia de usuario validando su rol."""

        super().__init__(usuario_id)

        if rol not in ROLES:
            raise ValueError("Rol invalido")

        self.nombre = nombre
        self.email = email
        self._password = password
        self._rol = rol

    @property
    def rol(self) -> str:
        """Retorna el rol del usuario."""
        return self._rol

    @property
    def password(self) -> str:
        """Retorna la contraseña del usuario."""
        return self._password

    def es_admin(self) -> bool:
        """Indica si el usuario tiene rol de administrador."""
        return self._rol == "admin"

    def verificar_password(self, password) -> str:
        """Indica si la contraseña recibida coincide con la del usuario."""
        return self._password == password

    def to_dict(self) -> dict:
        """Convierte el usuario en diccionario, sin exponer la contraseña."""
        return {
            "id": self.id,
            "nombre": self.nombre,
            "email": self.email,
            "rol": self._rol
        }
