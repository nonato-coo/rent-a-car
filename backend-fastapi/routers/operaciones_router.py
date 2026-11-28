"""Define los endpoints de operaciones de negocio.

Endpoints:
    POST /cotizar: Calcula el costo estimado de un alquiler.
    POST /reservas: Crea una reserva con su factura y pago simulado.
"""

from fastapi import APIRouter, HTTPException
from schemas.cotizacion import CotizacionRequest
from schemas.alquiler import ReservaCreate
from core.excepciones import NoEncontradoError
from domain.alquiler import Alquiler
from domain.calculadora import CalculadoraCostos
from repositories.vehiculo_repository import VehiculoRepository
from services.reservas_service import ServicioReservas

router = APIRouter(tags=["Operaciones"])
vehiculo_repo = VehiculoRepository()
reservas_service = ServicioReservas()

@router.post("/cotizar")
def cotizar_alquiler(req: CotizacionRequest) -> dict:
    """Calcula días, subtotal, impuesto y total del alquiler de un vehículo.

    Raises:
        HTTPException: 404 si el vehículo no existe; 400 si las fechas no son válidas.
    """

    vehiculo = vehiculo_repo.obtener_por_id(req.vehiculo_id)
    if not vehiculo:
        raise HTTPException(status_code=404, detail="Vehículo no encontrado")

    try:
        reservas_service.validar_fechas(req.fecha_inicio, req.fecha_fin)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e)) from e

    dias = Alquiler(cliente_id=0, vehiculo_id=vehiculo.id, fecha_inicio=req.fecha_inicio,
                    fecha_fin=req.fecha_fin).calcular_dias()
    cotizacion = CalculadoraCostos.cotizar(vehiculo.precio_por_dia, dias)
    cotizacion["vehiculo"] = f"{vehiculo.marca} {vehiculo.modelo}"
    return cotizacion

@router.post("/reservas")
def crear_reserva(reserva: ReservaCreate) -> dict:
    """Crea una reserva con su factura y pago simulado.

    Raises:
        HTTPException: 404 si el cliente o el vehículo no existen; 400 si los datos no son válidos.
    """

    try:
        return reservas_service.crear_reserva_completa(reserva.cliente_id, reserva.vehiculo_id,
                                                       reserva.fecha_inicio, reserva.fecha_fin,
                                                       reserva.metodo_pago)
    except NoEncontradoError as e:
        raise HTTPException(status_code=404, detail=str(e)) from e
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e)) from e
