"""alquiler_repository.py"""

from database import obtener_conexion
from domain.alquiler import Alquiler

class AlquilerRepository:
    """Gestiona las operaciones de acceso a datos de los alquileres."""

    def crear(self, alquiler: Alquiler) -> int:
        """Registra un nuevo alquiler en la base de datos.

        Args:
            alquiler (Alquiler): Objeto con los datos del alquiler que se desea registrar.

        Returns:
            int: Identificador generado para el nuevo alquiler.
        """

        conexion = obtener_conexion()
        cursor = conexion.cursor()
        cursor.execute("""
            INSERT INTO alquileres (cliente_id, vehiculo_id, fecha_inicio, fecha_fin, estado)
            VALUES (?, ?, ?, ?, ?)""",
            (alquiler.cliente_id, alquiler.vehiculo_id,
            alquiler.fecha_inicio, alquiler.fecha_fin, alquiler.estado))
        conexion.commit()
        nuevo_id = cursor.lastrowid
        conexion.close()
        return nuevo_id

    def actualizar_estado(self, alquiler_id: int, estado: str) -> None:
        """Actualiza el estado de un alquiler registrado.

        Args:
            alquiler_id (int): Identificador del alquiler que se desea actualizar.
            estado (str): Nuevo estado que se asignará al alquiler.
        """

        conexion = obtener_conexion()
        cursor = conexion.cursor()
        cursor.execute("""
            UPDATE alquileres SET estado = ? WHERE id = ?
        """, (estado, alquiler_id))
        conexion.commit()
        conexion.close()

    def contar_alquileres_activos_vehiculo(self, vehiculo_id: int) -> int:
        """Cuenta los alquileres activos y reservados asociados a un vehículo.

        Este conteo permite determinar cuántas unidades están comprometidas
        y calcular la disponibilidad real del vehículo.

        Args:
            vehiculo_id (int): Identificador del vehículo que se desea consultar.

        Returns:
            int: Cantidad de alquileres activos o reservados.
        """

        conexion = obtener_conexion()
        cursor = conexion.cursor()
        cursor.execute("""
            SELECT COUNT(*) as total FROM alquileres 
            WHERE vehiculo_id = ? AND estado IN ('reservado', 'activo')
        """, (vehiculo_id,))
        resultado = cursor.fetchone()
        conexion.close()
        return resultado["total"] if resultado else 0
