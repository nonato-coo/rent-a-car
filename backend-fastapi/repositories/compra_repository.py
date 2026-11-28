"""Define la clase 'CompraRepository'

Métodos:
    _mapear(row) -> Compra
    crear(compra: Compra) -> int
    listar_todos() -> list[Compra]
    obtener_por_id(compra_id: int) -> Compra | None
    actualizar(compra: Compra) -> None
    eliminar(compra_id: int) -> None
"""

from core.database import obtener_conexion
from domain.compra import Compra

class CompraRepository:
    """Gestiona las operaciones de acceso a datos de las compras."""

    def _mapear(self, row) -> Compra:
        """Convierte una fila de la base de datos en un objeto Compra."""

        return Compra(
            compra_id = row["id"],
            vehiculo_id = row["vehiculo_id"],
            cantidad = row["cantidad"],
            costo_unitario = row["costo_unitario"],
            proveedor = row["proveedor"] or "",
            fecha = row["fecha"]
        )

    def crear(self, compra: Compra) -> int:
        """Registra una nueva compra y retorna su identificador."""

        conexion = obtener_conexion()
        cursor = conexion.cursor()

        cursor.execute("""
            INSERT INTO compras (vehiculo_id, cantidad, costo_unitario, proveedor, fecha)
            VALUES (?, ?, ?, ?, ?)""",
            (compra.vehiculo_id, compra.cantidad, compra.costo_unitario,
            compra.proveedor, compra.fecha)
        )

        conexion.commit()
        nuevo_id = cursor.lastrowid
        conexion.close()
        return nuevo_id

    def listar_todos(self) -> list[Compra]:
        """Obtiene todas las compras registradas."""

        conexion = obtener_conexion()

        filas = conexion.execute(
            "SELECT * FROM compras"
        ).fetchall()

        conexion.close()
        return [self._mapear(f) for f in filas]

    def obtener_por_id(self, compra_id: int) -> Compra | None:
        """Busca una compra por su identificador, o None si no existe."""

        conexion = obtener_conexion()

        fila = conexion.execute(
            "SELECT * FROM compras WHERE id = ?",
            (compra_id,)
        ).fetchone()

        conexion.close()
        return self._mapear(fila) if fila else None

    def actualizar(self, compra: Compra) -> None:
        """Actualiza los datos de una compra existente."""

        conexion = obtener_conexion()

        conexion.execute(
            "UPDATE compras SET vehiculo_id = ?, cantidad = ?, costo_unitario = ?, proveedor = ?, fecha = ? WHERE id = ?",
            (compra.vehiculo_id, compra.cantidad, compra.costo_unitario,
            compra.proveedor, compra.fecha, compra.id))

        conexion.commit()
        conexion.close()

    def eliminar(self, compra_id: int) -> None:
        """Elimina una compra por su identificador."""

        conexion = obtener_conexion()

        conexion.execute(
            "DELETE FROM compras WHERE id = ?",
            (compra_id,)
        )

        conexion.commit()
        conexion.close()