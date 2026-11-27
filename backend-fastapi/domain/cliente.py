"""Define la clase 'cliente', hereda de 'Entidad'

Atributos:
    id (int)
    telefono (str)
    direccion (str)

Metodos:
    actualizar_contacto()
    to_dict()
"""

from domain.entidad import Entidad

class Cliente(Entidad):
    """Representa un cliente con su informacion personal."""

    def __init__(self, telefono: str, direccion: str, id_usuario: int = None):
        super().__init__(id_usuario)
        self.telefono = telefono
        self.direccion = direccion

    def actualizar_contacto(self, telefono: str, direccion: str) -> None:
        """Actualiza el telefono y la direccion del cliente"""
        self.telefono = telefono
        self.direccion = direccion

    def to_dict(self) -> dict:
        """Convierte el cliente en diccionario."""
        return {
            "id_usuario": self.id,
            "telefono": self.telefono,
            "direccion": self.direccion
        }
