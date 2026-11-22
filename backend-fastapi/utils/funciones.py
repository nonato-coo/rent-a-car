"""funciones.py"""

from functools import reduce

def extraer_marcas_unicas(vehiculos: list[dict]) -> list[str]:
    """Obtiene las marcas únicas de una lista de vehículos.

    Args:
        vehiculos (list[dict]): Lista de diccionarios con la información
            de los vehículos.

    Returns:
        list[str]: Lista de marcas únicas ordenadas alfabéticamente.
    """

    marcas = map(lambda v: v["marca"], vehiculos)
    return sorted(list(set(marcas)))


def extraer_anios_unicos(vehiculos: list[dict]) -> list[int]:
    """Obtiene los años de fabricación únicos de una lista de vehículos.

    Args:
        vehiculos (list[dict]): Lista de diccionarios con la información
            de los vehículos.

    Returns:
        list[int]: Lista de años únicos ordenados de forma descendente,
            del más reciente al más antiguo.
    """

    anios = map(lambda v: v["anio"], vehiculos)
    return sorted(list(set(anios)), reverse=True)


def filtrar_vehiculos_puro(vehiculos: list[dict], marca: str = None, anio: int = None,
    categoria: str = None,solo_disponibles: bool = False) -> list[dict]:
    """Filtra vehículos según los criterios especificados sin modificar la lista original.

    Args:
        vehiculos (list[dict]): Lista de vehículos que se desea filtrar.
        marca (str): Marca por la que se desea filtrar.
        anio (int): Año de fabricación por el que se desea filtrar.
        categoria (str): Categoría por la que se desea filtrar.
        solo_disponibles (bool): Indica si se deben incluir únicamente
            vehículos habilitados que tengan unidades disponibles.

    Returns:
        list[dict]: Lista de vehículos que cumplen con todos los filtros
            especificados.
    """

    resultado = list(vehiculos)
    if marca:
        resultado = list(filter(lambda v: v["marca"].lower() == marca.lower(), resultado))
    if anio:
        resultado = list(filter(lambda v: v["anio"] == anio, resultado))
    if categoria:
        resultado = list(filter(lambda v: v["categoria"].lower() == categoria.lower(), resultado))
    if solo_disponibles:
        resultado = list(filter(
            lambda v: v.get("habilitado") == 1 and v.get("disponibles", 0) > 0,
            resultado
        ))
    return resultado


def sumar_montos(montos: list[float]) -> float:
    """Calcula la suma de una lista de montos monetarios.

    Args:
        montos (list[float]): Lista de valores numéricos que se desean sumar.

    Returns:
        float: Resultado de la suma redondeado a dos decimales.
            Retorna 0.0 si la lista está vacía.
    """
    
    if not montos:
        return 0.0
    return round(reduce(lambda acc, val: acc + val, montos, 0.0), 2)
