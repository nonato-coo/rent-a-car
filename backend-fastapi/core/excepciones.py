"""Define las excepciones propias del sistema.

Clases: NoEncontradoError
"""

class NoEncontradoError(ValueError):
    """Indica que un recurso solicitado no existe.

    Hereda de ValueError para que el código existente siga funcionando;
    los routers la traducen a HTTP 404 (y el resto de ValueError a HTTP 400).
    """