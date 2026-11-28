"""Define la clase 'PagoRepository'

Métodos:
    _mapear(row) -> Pago
    crear(pago: Pago) -> int
    listar_todos() -> list[Pago]
    obtener_por_id(pago_id: int) -> Pago | None
    listar_por_alquiler(alquiler_id: int) -> list[Pago]
    actualizar(pago: Pago) -> None
    eliminar(pago_id: int) -> None
"""

from core.database import obtener_conexion
from domain.pago import Pago

class PagoRepository:
    """Gestiona las operaciones de acceso a datos de los pagos."""

    def _mapear(self, row) -> Pago:
        """Convierte una fila de la base de datos en un objeto Pago."""

        return Pago(
            pago_id = row["id"],
            alquiler_id = row["alquiler_id"],
            monto = row["monto"],
            fecha = row["fecha"],
            metodo = row["metodo"]
        )

    def crear(self, pago: Pago) -> int:
        """Registra un nuevo pago y retorna su identificador."""

        conexion = obtener_conexion()
        cursor = conexion.cursor()

        cursor.execute("""
            INSERT INTO pagos (alquiler_id, monto, fecha, metodo)
            VALUES (?, ?, ?, ?)""",
            (pago.alquiler_id, pago.monto, pago.fecha, pago.metodo)
        )

        conexion.commit()
        nuevo_id = cursor.lastrowid
        conexion.close()
        return nuevo_id

    def listar_todos(self) -> list[Pago]:
        """Obtiene todos los pagos registrados."""

        conexion = obtener_conexion()

        filas = conexion.execute(
            "SELECT * FROM pagos"
        ).fetchall()

        conexion.close()
        return [self._mapear(f) for f in filas]

    def obtener_por_id(self, pago_id: int) -> Pago | None:
        """Busca un pago por su identificador, o None si no existe."""

        conexion = obtener_conexion()

        fila = conexion.execute(
            "SELECT * FROM pagos WHERE id = ?",
            (pago_id,)
        ).fetchone()

        conexion.close()
        return self._mapear(fila) if fila else None

    def listar_por_alquiler(self, alquiler_id: int) -> list[Pago]:
        """Obtiene los pagos asociados a un alquiler."""

        conexion = obtener_conexion()

        filas = conexion.execute(
            "SELECT * FROM pagos WHERE alquiler_id = ?",
            (alquiler_id,)
        ).fetchall()

        conexion.close()
        return [self._mapear(f) for f in filas]

    def actualizar(self, pago: Pago) -> None:
        """Actualiza los datos de un pago existente."""

        conexion = obtener_conexion()

        conexion.execute(
            "UPDATE pagos SET alquiler_id = ?, monto = ?, fecha = ?, metodo = ? WHERE id = ?",
            (pago.alquiler_id, pago.monto, pago.fecha, pago.metodo, pago.id))

        conexion.commit()
        conexion.close()

    def eliminar(self, pago_id: int) -> None:
        """Elimina un pago por su identificador."""

        conexion = obtener_conexion()

        conexion.execute(
            "DELETE FROM pagos WHERE id = ?",
            (pago_id,)
        )

        conexion.commit()
        conexion.close()