"""calculadora.py"""

class CalculadoraCostos:
    """Proporciona métodos para calcular los costos asociados al alquiler de vehículos."""
    TASA_IMPUESTO = 0.13

    @staticmethod
    def cotizar(precio_por_dia: float, dias: int) -> dict:
        """Calcula el costo total de alquiler de un vehículo.

        Determina el subtotal según el precio diario y la duración del alquiler,
        calcula el impuesto correspondiente y obtiene el total a pagar.

        Argumentos:
            precio_por_dia (float): Precio del alquiler por cada día.
            dias (int): Cantidad de días que durará el alquiler.

        Return:
            dict: Diccionario con la cantidad de días, precio diario,
                  subtotal, impuesto y total de la cotización.
        """
        subtotal = precio_por_dia * dias
        impuesto = subtotal * CalculadoraCostos.TASA_IMPUESTO
        total = subtotal + impuesto

        return {
            "dias": dias,
            "precio_por_dia": precio_por_dia,
            "subtotal": round(subtotal, 2),
            "impuesto": round(impuesto, 2),
            "total": round(total, 2)
        }
