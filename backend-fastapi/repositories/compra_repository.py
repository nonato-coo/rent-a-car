"""compra_repository.py"""

from database import obtener_conexion


class CompraRepository:
    """Gestiona las operaciones de acceso a datos de las compras."""

    def crear(self, vehiculo_id: int, cantidad: int,
        costo_unitario: float, proveedor: str, fecha: str) -> int:
        """Registra una nueva compra en la base de datos.

        Args:
            vehiculo_id (int): Identificador del vehículo adquirido.
            cantidad (int): Cantidad de unidades compradas.
            costo_unitario (float): Costo individual de cada unidad.
            proveedor (str): Nombre del proveedor de la compra.
            fecha (str): Fecha en que se realizó la compra.

        Returns:
            int: Identificador generado para la nueva compra.
        """

        conexion = obtener_conexion()
        cursor = conexion.cursor()
        cursor.execute("""
            INSERT INTO compras (vehiculo_id, cantidad, costo_unitario, proveedor, fecha)
            VALUES (?, ?, ?, ?, ?)
        """, (vehiculo_id, cantidad, costo_unitario, proveedor, fecha))
        conexion.commit()
        nuevo_id = cursor.lastrowid
        conexion.close()
        return nuevo_id
