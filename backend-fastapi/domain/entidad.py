"""entidad.py"""
class Entidad:
    """Define la estructura base y los comportamientos comunes de las entidades del sistema."""

    def __init__(self, id_entidad: int | None = None):
        """Inicializa una entidad con un identificador opcional."""
        self._id = id_entidad

    @property
    def id(self) -> int | None:
        """Retorna el identificador único de la entidad."""
        return self._id

    @id.setter
    def id(self, value: int | None) -> None:
        """Establece o actualiza el identificador de la entidad."""
        self._id = value

    def to_dict(self) -> dict:
        """Convierte los atributos de la entidad en un diccionario.

        Este método debe ser implementado por las clases hijas
        según los atributos específicos de cada entidad.

        Raises:
            NotImplementedError: Si una clase hija no implementa este método.
        """
        raise NotImplementedError("Las clases hijas deben implementar to_dict()")
