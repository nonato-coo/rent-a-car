
"""vehiculo_repository.py"""

from database import obtener_conexion
from domain.vehiculo import Vehiculo


class VehiculoRepository:
    """Gestiona las operaciones de acceso a datos de los vehículos."""

    def _mapear(self, row) -> Vehiculo:
        """Convierte una fila de la base de datos en un objeto Vehiculo.

        Args:
            row: Fila obtenida de la consulta SQL con los datos del vehículo.

        Returns:
            Vehiculo: Objeto construido a partir de los datos de la fila.
        """

        return Vehiculo(
            id_vehiculo=row["id"],
            marca=row["marca"],
            modelo=row["modelo"],
             anio=row["anio"],
            categoria=row["categoria"],
            precio_por_dia=row["precio_por_dia"],
            stock_total=row["stock_total"],
            habilitado=row["habilitado"]
        )

    def listar_todos(self) -> list[Vehiculo]:
        """Obtiene todos los vehículos registrados en la base de datos.

        Returns:
            list[Vehiculo]: Lista de vehículos registrados.
        """

        conexion = obtener_conexion()
        cursor = conexion.cursor()
        cursor.execute("SELECT * FROM vehiculos")
        filas = cursor.fetchall()
        conexion.close()
        return [self._mapear(f) for f in filas]

    def obtener_por_id(self, vehiculo_id: int) -> Vehiculo | None:
        """Busca un vehículo mediante su identificador.

        Args:
            vehiculo_id (int): Identificador del vehículo que se desea consultar.

        Returns:
            Vehiculo | None: Vehículo encontrado o None si no existe.
        """

        conexion = obtener_conexion()
        cursor = conexion.cursor()
        cursor.execute("SELECT * FROM vehiculos WHERE id = ?", (vehiculo_id,))
        fila = cursor.fetchone()
        conexion.close()
        return self._mapear(fila) if fila else None

    def crear(self, vehiculo: Vehiculo) -> int:
        """Registra un nuevo vehículo en la base de datos.

        Args:
            vehiculo (Vehiculo): Objeto con los datos del vehículo que se desea registrar.

        Returns:
            int: Identificador generado para el nuevo vehículo.
        """

        conexion = obtener_conexion()
        cursor = conexion.cursor()
        cursor.execute("""
            INSERT INTO vehiculos (marca, modelo, anio, categoria, precio_por_dia, stock_total, habilitado)
            VALUES (?, ?, ?, ?, ?, ?, ?)""",
            (vehiculo.marca, vehiculo.modelo, vehiculo.anio,
            vehiculo.categoria, vehiculo.precio_por_dia,
            vehiculo.stock_total, vehiculo.habilitado))
        conexion.commit()
        nuevo_id = cursor.lastrowid
        conexion.close()
        return nuevo_id

    def actualizar_stock(self, vehiculo_id: int, nuevo_stock: int) -> None:
        """Actualiza la cantidad de unidades disponibles de un vehículo.

        Args:
            vehiculo_id (int): Identificador del vehículo que se desea actualizar.
            nuevo_stock (int): Nueva cantidad de unidades en inventario.
        """

        conexion = obtener_conexion()
        cursor = conexion.cursor()
        cursor.execute("""
            UPDATE vehiculos SET stock_total = ? WHERE id = ?
        """, (nuevo_stock, vehiculo_id))
        conexion.commit()
        conexion.close()
