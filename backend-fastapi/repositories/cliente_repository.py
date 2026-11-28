"""Define la clase 'ClienteRepository'

Métodos: 
    _mapear(row) -> Usuario
    crear(usuario: Usuario) -> int
    listar_todos() -> list[Usuario]
    obtener_por_id(usuario_id: int) -> Usuario | None
    actualizar(usuario: Usuario) -> None
    eliminar(usuario_id: int) -> None
"""

from core.database import obtener_conexion
from domain.cliente import Cliente

class ClienteRepository:
    """Gestiona las operaciones de acceso a datos de los clientes."""

    def _mapear(self, row) -> Cliente:
        """Convierte una fila de la base de datos en un objeto Cliente."""

        return Cliente(
            cliente_id = row["id"],
            usuario_id = row["usuario_id"],
            telefono = row["telefono"] or "",
            direccion = row["direccion"] or ""
        )

    def crear(self, cliente: Cliente) -> int:
        """Registra un nuevo cliente y retorna su identificador."""

        conexion = obtener_conexion()
        cursor = conexion.cursor()

        cursor.execute(
            "INSERT INTO clientes (usuario_id, telefono, direccion) VALUES (?, ?, ?)",
            (cliente.usuario_id, cliente.telefono, cliente.direccion))

        conexion.commit()
        nuevo_id = cursor.lastrowid
        conexion.close()
        return nuevo_id

    def listar_todos(self) -> list[Cliente]:
        """Obtiene todos los clientes registrados."""

        conexion = obtener_conexion()

        filas = conexion.execute(
            "SELECT * FROM clientes"
        ).fetchall()

        conexion.close()
        return [self._mapear(f) for f in filas]

    def obtener_por_id(self, cliente_id: int) -> Cliente | None:
        """Busca un cliente por su identificador, o None si no existe."""

        conexion = obtener_conexion()

        fila = conexion.execute(
            "SELECT * FROM clientes WHERE id = ?",
            (cliente_id,)
        ).fetchone()

        conexion.close()
        return self._mapear(fila) if fila else None

    def obtener_por_usuario_id(self, usuario_id: int) -> Cliente | None:
        """Busca el cliente asociado a un usuario, o None si no existe."""

        conexion = obtener_conexion()

        fila = conexion.execute(
            "SELECT * FROM clientes WHERE usuario_id = ?",
            (usuario_id,)
        ).fetchone()

        conexion.close()
        return self._mapear(fila) if fila else None

    def actualizar(self, cliente: Cliente) -> None:
        """Actualiza el teléfono y la dirección de un cliente existente."""

        conexion = obtener_conexion()

        conexion.execute(
            "UPDATE clientes SET telefono = ?, direccion = ? WHERE id = ?",
            (cliente.telefono, cliente.direccion, cliente.id)
        )

        conexion.commit()
        conexion.close()

    def eliminar(self, cliente_id: int) -> None:
        """Elimina un cliente por su identificador."""

        conexion = obtener_conexion()

        conexion.execute(
            "DELETE FROM clientes WHERE id = ?",
            (cliente_id,)
        )

        conexion.commit()
        conexion.close()
