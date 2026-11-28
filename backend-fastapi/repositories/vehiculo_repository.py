"""Define la clase 'VehiculoRepository'

Métodos:
    _mapear(row) -> Vehiculo
    crear(vehiculo: Vehiculo) -> int
    listar_todos() -> list[Vehiculo]
    obtener_por_id(vehiculo_id: int) -> Vehiculo | None
    actualizar(vehiculo: Vehiculo) -> None
    actualizar_stock(vehiculo_id: int, nuevo_stock: int) -> None
    eliminar(vehiculo_id: int) -> None
"""

from core.database import obtener_conexion
from domain.vehiculo import Vehiculo

class VehiculoRepository:
    """Gestiona las operaciones de acceso a datos de los vehículos."""

    def _mapear(self, row) -> Vehiculo:
        """Convierte una fila de la base de datos en un objeto Vehiculo."""

        return Vehiculo(
            id_vehiculo = row["id"],
            marca = row["marca"],
            modelo = row["modelo"],
            anio = row["anio"],
            categoria = row["categoria"],
            precio_por_dia = row["precio_por_dia"],
            stock_total = row["stock_total"],
            habilitado = row["habilitado"]
        )

    def crear(self, vehiculo: Vehiculo) -> int:
        """Registra un nuevo vehículo y retorna su identificador."""

        conexion = obtener_conexion()
        cursor = conexion.cursor()

        cursor.execute("""
            INSERT INTO vehiculos (marca, modelo, anio, categoria, precio_por_dia, stock_total, habilitado)
            VALUES (?, ?, ?, ?, ?, ?, ?)""",
            (vehiculo.marca, vehiculo.modelo, vehiculo.anio,
            vehiculo.categoria, vehiculo.precio_por_dia,
            vehiculo.stock_total, vehiculo.habilitado)
        )

        conexion.commit()
        nuevo_id = cursor.lastrowid
        conexion.close()
        return nuevo_id

    def listar_todos(self) -> list[Vehiculo]:
        """Obtiene todos los vehículos registrados."""

        conexion = obtener_conexion()

        filas = conexion.execute(
            "SELECT * FROM vehiculos"
        ).fetchall()

        conexion.close()
        return [self._mapear(f) for f in filas]

    def obtener_por_id(self, vehiculo_id: int) -> Vehiculo | None:
        """Busca un vehículo por su identificador, o None si no existe."""

        conexion = obtener_conexion()

        fila = conexion.execute(
            "SELECT * FROM vehiculos WHERE id = ?",
            (vehiculo_id,)
        ).fetchone()

        conexion.close()
        return self._mapear(fila) if fila else None

    def actualizar(self, vehiculo: Vehiculo) -> None:
        """Actualiza los datos de un vehículo existente."""

        conexion = obtener_conexion()

        conexion.execute("""
            UPDATE vehiculos
            SET marca = ?, modelo = ?, anio = ?, categoria = ?,
                precio_por_dia = ?, stock_total = ?, habilitado = ?
            WHERE id = ?""",
            (vehiculo.marca, vehiculo.modelo, vehiculo.anio, vehiculo.categoria,
            vehiculo.precio_por_dia, vehiculo.stock_total, vehiculo.habilitado,
            vehiculo.id))

        conexion.commit()
        conexion.close()

    def actualizar_stock(self, vehiculo_id: int, nuevo_stock: int) -> None:
        """Actualiza la cantidad de unidades en inventario de un vehículo."""

        conexion = obtener_conexion()

        conexion.execute(
            "UPDATE vehiculos SET stock_total = ? WHERE id = ?",
            (nuevo_stock, vehiculo_id))

        conexion.commit()
        conexion.close()

    def eliminar(self, vehiculo_id: int) -> None:
        """Elimina un vehículo por su identificador."""

        conexion = obtener_conexion()

        conexion.execute(
            "DELETE FROM vehiculos WHERE id = ?",
            (vehiculo_id,)
        )

        conexion.commit()
        conexion.close()
