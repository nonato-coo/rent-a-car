"""Define la clase 'FacturaRepository'

Métodos:
    _mapear(row, detalles) -> Factura
    _listar_detalles(conexion, factura_id) -> list[DetalleFactura]
    crear(factura: Factura) -> int
    listar_todos() -> list[Factura]
    obtener_por_id(factura_id: int) -> Factura | None
    actualizar(factura: Factura) -> None
    eliminar(factura_id: int) -> None
"""

from core.database import obtener_conexion
from domain.factura import Factura, DetalleFactura

class FacturaRepository:
    """Gestiona las operaciones de acceso a datos de las facturas y sus detalles."""

    def _mapear(self, row, detalles: list[DetalleFactura]) -> Factura:
        """Convierte una fila de la base de datos y sus detalles en un objeto Factura."""

        return Factura(
            id_factura = row["id"],
            alquiler_id = row["alquiler_id"],
            cliente_id = row["cliente_id"],
            fecha = row["fecha"],
            detalles = detalles
        )

    def _listar_detalles(self, conexion, factura_id: int) -> list[DetalleFactura]:
        """Obtiene los detalles de una factura usando una conexión ya abierta."""

        filas = conexion.execute(
            "SELECT * FROM detalle_factura WHERE factura_id = ?",
            (factura_id,)
        ).fetchall()

        return [
            DetalleFactura(
                id_detalle_factura = f["id"],
                descripcion = f["descripcion"],
                cantidad = f["cantidad"],
                precio_unitario = f["precio_unitario"]
            )
            for f in filas
        ]

    def _insertar_detalles(self, cursor, factura: Factura, factura_id: int) -> None:
        """Inserta los detalles de una factura usando un cursor ya abierto."""

        for detalle in factura.detalles:
            cursor.execute("""
                INSERT INTO detalle_factura (factura_id, descripcion, cantidad, precio_unitario, subtotal)
                VALUES (?, ?, ?, ?, ?)""",
                (factura_id, detalle.descripcion, detalle.cantidad,
                detalle.precio_unitario, detalle.subtotal)
            )

    def crear(self, factura: Factura) -> int:
        """Registra una factura con sus detalles y retorna su identificador."""

        conexion = obtener_conexion()
        cursor = conexion.cursor()

        cursor.execute("""
            INSERT INTO facturas (alquiler_id, cliente_id, fecha, subtotal, impuesto, total)
            VALUES (?, ?, ?, ?, ?, ?)""",
            (factura.alquiler_id, factura.cliente_id, factura.fecha,
            factura.subtotal, factura.impuesto, factura.total)
        )

        nuevo_id = cursor.lastrowid
        self._insertar_detalles(cursor, factura, nuevo_id)

        conexion.commit()
        conexion.close()
        return nuevo_id

    def listar_todos(self) -> list[Factura]:
        """Obtiene todas las facturas registradas junto con sus detalles."""

        conexion = obtener_conexion()

        filas = conexion.execute(
            "SELECT * FROM facturas"
        ).fetchall()

        facturas = [self._mapear(f, self._listar_detalles(conexion, f["id"])) for f in filas]

        conexion.close()
        return facturas

    def obtener_por_id(self, factura_id: int) -> Factura | None:
        """Busca una factura por su identificador, o None si no existe."""

        conexion = obtener_conexion()

        fila = conexion.execute(
            "SELECT * FROM facturas WHERE id = ?",
            (factura_id,)
        ).fetchone()

        factura = self._mapear(fila, self._listar_detalles(conexion, factura_id)) if fila else None

        conexion.close()
        return factura

    def actualizar(self, factura: Factura) -> None:
        """Actualiza una factura y reemplaza sus detalles por los actuales."""

        conexion = obtener_conexion()
        cursor = conexion.cursor()

        cursor.execute("""
            UPDATE facturas
            SET alquiler_id = ?, cliente_id = ?, fecha = ?, subtotal = ?, impuesto = ?, total = ?
            WHERE id = ?""",
            (factura.alquiler_id, factura.cliente_id, factura.fecha,
            factura.subtotal, factura.impuesto, factura.total, factura.id)
        )

        cursor.execute("DELETE FROM detalle_factura WHERE factura_id = ?", (factura.id,))
        self._insertar_detalles(cursor, factura, factura.id)

        conexion.commit()
        conexion.close()

    def eliminar(self, factura_id: int) -> None:
        """Elimina una factura junto con sus detalles."""

        conexion = obtener_conexion()
        cursor = conexion.cursor()

        cursor.execute("DELETE FROM detalle_factura WHERE factura_id = ?", (factura_id,))
        cursor.execute("DELETE FROM facturas WHERE id = ?", (factura_id,))

        conexion.commit()
        conexion.close()
