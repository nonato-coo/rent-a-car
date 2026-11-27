"""modelos.py"""

from typing import Optional

from pydantic import BaseModel
class CompraCreate(BaseModel):
    """Define los datos necesarios para registrar una compra de vehículos.

    Attributes:
        vehiculo_id (int): Identificador del vehículo adquirido.
        cantidad (int): Cantidad de unidades compradas.
        costo_unitario (float): Costo individual de cada unidad.
        proveedor (Optional[str]): Nombre del proveedor. Por defecto,
            utiliza "Proveedor General".
    """

    vehiculo_id: int
    cantidad: int
    costo_unitario: float
    proveedor: Optional[str] = "Proveedor General"

class CotizacionRequest(BaseModel):
    """Define los datos necesarios para solicitar una cotización de alquiler.

    Attributes:
        vehiculo_id (int): Identificador del vehículo que se desea alquilar.
        fecha_inicio (str): Fecha de inicio del alquiler en formato YYYY-MM-DD.
        fecha_fin (str): Fecha de finalización del alquiler en formato YYYY-MM-DD.
    """

    vehiculo_id: int
    fecha_inicio: str  # formato YYYY-MM-DD
    fecha_fin: str     # formato YYYY-MM-DD

class ReservaCreate(BaseModel):
    """Define los datos necesarios para crear una reserva de vehículo.

    Attributes:
        cliente_id (int): Identificador del cliente que realiza la reserva.
        vehiculo_id (int): Identificador del vehículo que se desea reservar.
        fecha_inicio (str): Fecha de inicio del alquiler en formato YYYY-MM-DD.
        fecha_fin (str): Fecha de finalización del alquiler en formato YYYY-MM-DD.
        metodo_pago (Optional[str]): Método de pago utilizado. Por defecto,
            utiliza "Tarjeta Simulada".
    """

    cliente_id: int
    vehiculo_id: int
    fecha_inicio: str  # formato YYYY-MM-DD
    fecha_fin: str     # formato YYYY-MM-DD
    metodo_pago: Optional[str] = "Tarjeta Simulada"

class LoginRequest(BaseModel):
    """Define las credenciales necesarias para iniciar sesión.

    Attributes:
        email (str): Dirección de correo electrónico del usuario.
        password (str): Contraseña de acceso del usuario.
    """

    email: str
    password: str

class RegistroRequest(BaseModel):
    """Define los datos necesarios para registrar un nuevo usuario.

    Attributes:
        nombre (str): Nombre completo del usuario.
        email (str): Dirección de correo electrónico del usuario.
        password (str): Contraseña para acceder al sistema.
        rol (Optional[str]): Rol asignado al usuario. Por defecto, "cliente".
        telefono (Optional[str]): Número de teléfono del usuario.
            Por defecto, una cadena vacía.
        direccion (Optional[str]): Dirección del usuario.
            Por defecto, una cadena vacía.
    """

    nombre: str
    email: str
    password: str
    rol: Optional[str] = "cliente"
    telefono: Optional[str] = ""
    direccion: Optional[str] = ""

class VehiculoCreate(BaseModel):
    """Define los datos necesarios para registrar un nuevo vehículo.

    Attributes:
        marca (str): Marca del vehículo.
        modelo (str): Modelo del vehículo.
        anio (int): Año de fabricación del vehículo.
        categoria (str): Categoría a la que pertenece el vehículo.
        precio_por_dia (float): Precio diario del alquiler.
        stock_total (int): Cantidad inicial de unidades del vehículo.
            Por defecto, 0.
        habilitado (int): Indicador de si el vehículo está habilitado
            para alquilarse (1) o deshabilitado (0). Por defecto, 1.
    """

    marca: str
    modelo: str
    anio: int
    categoria: str
    precio_por_dia: float
    stock_total: int = 0
    habilitado: int = 1

class VehiculoUpdate(BaseModel):
    """Define los campos que pueden modificarse de un vehículo existente.

    Attributes:
        marca (Optional[str]): Nueva marca del vehículo.
        modelo (Optional[str]): Nuevo modelo del vehículo.
        anio (Optional[int]): Nuevo año de fabricación.
        categoria (Optional[str]): Nueva categoría del vehículo.
        precio_por_dia (Optional[float]): Nuevo precio diario del alquiler.
        habilitado (Optional[int]): Nuevo estado de habilitación del vehículo
            (1 para habilitado y 0 para deshabilitado).
    """

    marca: Optional[str] = None
    modelo: Optional[str] = None
    anio: Optional[int] = None
    categoria: Optional[str] = None
    precio_por_dia: Optional[float] = None
    habilitado: Optional[int] = None

class EstadoAlquilerUpdate(BaseModel):
    """Define el nuevo estado que se asignará a un alquiler.

    Attributes:
        estado (str): Estado al que se desea cambiar el alquiler.
            Valores permitidos: "reservado", "activo", "devuelto" o "cancelado".
    """

    estado: str  # 'reservado', 'activo', 'devuelto', 'cancelado'
