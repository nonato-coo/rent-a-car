"""factura_repository.py"""

from database import obtener_conexion
from domain.factura import Factura


class FacturaRepository:
    """Gestiona las operaciones de acceso a datos de las facturas y sus pagos."""

    def crear(self, factura: Factura) -> int:
        """Registra una factura y sus respectivos detalles en la base de datos.

        Primero almacena la información general de la factura y luego
        registra cada uno de sus detalles asociados.

        Args:
            factura (Factura): Objeto con la información y los detalles de la factura.

        Returns:
            int: Identificador generado para la nueva factura.
        """

        conexion = obtener_conexion()
        cursor = conexion.cursor()

        cursor.execute("""
            INSERT INTO facturas (alquiler_id, cliente_id, fecha, subtotal, impuesto, total)
            VALUES (?, ?, ?, ?, ?, ?)
        """, (factura.alquiler_id, factura.cliente_id,
            factura.fecha, factura.subtotal,
            factura.impuesto, factura.total))
        factura_id = cursor.lastrowid

        for detalle in factura.detalles:
            cursor.execute("""
                INSERT INTO detalle_factura (factura_id, descripcion, cantidad, precio_unitario, subtotal)
                VALUES (?, ?, ?, ?, ?)
            """, (factura_id, detalle.descripcion,
                detalle.cantidad, detalle.precio_unitario,
                detalle.subtotal))

        conexion.commit()
        conexion.close()
        return factura_id

    def registrar_pago(self, alquiler_id: int, monto: float, fecha: str, metodo: str) -> int:
        """Registra un pago asociado a un alquiler en la base de datos.

        Args:
            alquiler_id (int): Identificador del alquiler al que corresponde el pago.
            monto (float): Cantidad de dinero pagada.
            fecha (str): Fecha en que se realizó el pago.
            metodo (str): Método utilizado para realizar el pago.

        Returns:
            int: Identificador generado para el nuevo pago.
        """

        conexion = obtener_conexion()
        cursor = conexion.cursor()
        cursor.execute("""
            INSERT INTO pagos (alquiler_id, monto, fecha, metodo)
            VALUES (?, ?, ?, ?)
        """, (alquiler_id, monto, fecha, metodo))
        conexion.commit()
        pago_id = cursor.lastrowid
        conexion.close()
        return pago_id
