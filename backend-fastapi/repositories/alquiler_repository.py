"""Define la clase 'AlquilerRepository'

Métodos:
    _mapear(row) -> Alquiler
    crear(alquiler: Alquiler) -> int
    listar_todos() -> list[Alquiler]
    obtener_por_id(alquiler_id: int) -> Alquiler | None
    listar_por_cliente(cliente_id: int) -> list[Alquiler]
    actualizar(alquiler: Alquiler) -> None
    actualizar_estado(alquiler_id: int, estado: str) -> None
    eliminar(alquiler_id: int) -> None
    contar_alquileres_activos_vehiculo(vehiculo_id: int) -> int
"""

from core.database import obtener_conexion
from domain.alquiler import Alquiler

class AlquilerRepository:
    """Gestiona las operaciones de acceso a datos de los alquileres."""

    def _mapear(self, row) -> Alquiler:
        """Convierte una fila de la base de datos en un objeto Alquiler."""

        return Alquiler(
            id_alquiler = row["id"],
            cliente_id = row["cliente_id"],
            vehiculo_id = row["vehiculo_id"],
            fecha_inicio = row["fecha_inicio"],
            fecha_fin = row["fecha_fin"],
            estado = row["estado"]
        )

    def crear(self, alquiler: Alquiler) -> int:
        """Registra un nuevo alquiler y retorna su identificador."""

        conexion = obtener_conexion()
        cursor = conexion.cursor()

        cursor.execute("""
            INSERT INTO alquileres (cliente_id, vehiculo_id, fecha_inicio, fecha_fin, estado)
            VALUES (?, ?, ?, ?, ?)""",
            (alquiler.cliente_id, alquiler.vehiculo_id,
            alquiler.fecha_inicio, alquiler.fecha_fin, alquiler.estado)
        )

        conexion.commit()
        nuevo_id = cursor.lastrowid
        conexion.close()
        return nuevo_id

    def listar_todos(self) -> list[Alquiler]:
        """Obtiene todos los alquileres registrados."""

        conexion = obtener_conexion()

        filas = conexion.execute(
            "SELECT * FROM alquileres"
        ).fetchall()

        conexion.close()
        return [self._mapear(f) for f in filas]

    def obtener_por_id(self, alquiler_id: int) -> Alquiler | None:
        """Busca un alquiler por su identificador, o None si no existe."""

        conexion = obtener_conexion()

        fila = conexion.execute(
            "SELECT * FROM alquileres WHERE id = ?",
            (alquiler_id,)
        ).fetchone()

        conexion.close()
        return self._mapear(fila) if fila else None

    def listar_por_cliente(self, cliente_id: int) -> list[Alquiler]:
        """Obtiene los alquileres realizados por un cliente."""

        conexion = obtener_conexion()

        filas = conexion.execute(
            "SELECT * FROM alquileres WHERE cliente_id = ?",
            (cliente_id,)
        ).fetchall()

        conexion.close()
        return [self._mapear(f) for f in filas]

    def actualizar(self, alquiler: Alquiler) -> None:
        """Actualiza los datos de un alquiler existente."""

        conexion = obtener_conexion()

        conexion.execute("""
            UPDATE alquileres
            SET cliente_id = ?, vehiculo_id = ?, fecha_inicio = ?, fecha_fin = ?, estado = ?
            WHERE id = ?""",
            (alquiler.cliente_id, alquiler.vehiculo_id, alquiler.fecha_inicio,
            alquiler.fecha_fin, alquiler.estado, alquiler.id))

        conexion.commit()
        conexion.close()

    def actualizar_estado(self, alquiler_id: int, estado: str) -> None:
        """Actualiza únicamente el estado de un alquiler."""

        conexion = obtener_conexion()

        conexion.execute(
            "UPDATE alquileres SET estado = ? WHERE id = ?",
            (estado, alquiler_id))

        conexion.commit()
        conexion.close()

    def eliminar(self, alquiler_id: int) -> None:
        """Elimina un alquiler por su identificador."""

        conexion = obtener_conexion()

        conexion.execute(
            "DELETE FROM alquileres WHERE id = ?",
            (alquiler_id,)
        )

        conexion.commit()
        conexion.close()

    def contar_alquileres_activos_vehiculo(self, vehiculo_id: int) -> int:
        """Cuenta los alquileres reservados o activos de un vehículo.

        Sirve para calcular cuántas unidades están comprometidas
        y, con ello, la disponibilidad real del vehículo.
        """

        conexion = obtener_conexion()

        resultado = conexion.execute("""
            SELECT COUNT(*) AS total FROM alquileres
            WHERE vehiculo_id = ? AND estado IN ('reservado', 'activo')""",
            (vehiculo_id,)
        ).fetchone()

        conexion.close()
        return resultado["total"] if resultado else 0
