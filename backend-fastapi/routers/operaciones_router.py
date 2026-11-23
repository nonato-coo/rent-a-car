"""operaciones_router.py"""

from datetime import datetime

from fastapi import APIRouter, HTTPException
from schemas.modelos import CompraCreate, CotizacionRequest, ReservaCreate
from repositories.vehiculo_repository import VehiculoRepository
from domain.calculadora import CalculadoraCostos
from services.inventario_service import ServicioInventario
from services.reservas_service import ServicioReservas

router = APIRouter(tags=["Operaciones"])
vehiculo_repo = VehiculoRepository()
inventario_service = ServicioInventario()
reservas_service = ServicioReservas()

@router.post("/cotizar")
def cotizar_alquiler(req: CotizacionRequest) -> dict:
    """Calcula el costo estimado del alquiler de un vehículo.

    Args:
        req (CotizacionRequest): Datos del vehículo y fechas del alquiler.

    Returns:
        dict: Desglose de la cotización, incluyendo subtotal, impuesto,
            total y descripción del vehículo.

    Raises:
        HTTPException: Si el vehículo no existe, devuelve el código HTTP 404.
        HTTPException: Si la fecha final es anterior a la inicial o si
            alguna fecha tiene un formato inválido, devuelve el código HTTP 400.
    """

    vehiculo = vehiculo_repo.obtener_por_id(req.vehiculo_id)
    if not vehiculo:
        raise HTTPException(status_code=404, detail="Vehículo no encontrado")

    try:
        f1 = datetime.strptime(req.fecha_inicio, "%Y-%m-%d")
        f2 = datetime.strptime(req.fecha_fin, "%Y-%m-%d")
        if f2 < f1:
            raise HTTPException(status_code=400,
                                detail="La fecha fin no puede ser menor a la fecha inicio")
        dias = max((f2 - f1).days, 1)
    except ValueError:
        raise HTTPException(status_code=400, detail="Formato de fecha inválido. Usa YYYY-MM-DD")

    cotizacion = CalculadoraCostos.cotizar(vehiculo.precio_por_dia, dias)
    cotizacion["vehiculo"] = vehiculo.marca + " " + vehiculo.modelo
    return cotizacion

@router.post("/compras")
def registrar_compra(compra: CompraCreate) -> dict:
    """Registra la compra de unidades de un vehículo y actualiza el inventario.

    Args:
        compra (CompraCreate): Datos del vehículo adquirido, cantidad,
            costo unitario y proveedor.

    Returns:
        dict: Información de la compra registrada y del inventario actualizado.

    Raises:
        HTTPException: Si los datos de la compra no son válidos o el vehículo
            no existe, devuelve el código HTTP 400.
    """
    try:
        return inventario_service.registrar_compra(
            vehiculo_id=compra.vehiculo_id,
            cantidad=compra.cantidad,
            costo_unitario=compra.costo_unitario,
            proveedor=compra.proveedor
        )

    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e)) from e


@router.post("/reservas")
def crear_reserva(reserva: ReservaCreate) -> dict:
    """Crea una reserva de vehículo y gestiona su facturación y pago.

    Args:
        reserva (ReservaCreate): Datos del cliente, vehículo, fechas
            del alquiler y método de pago.

    Returns:
        dict: Información de la reserva creada, su factura y el identificador
            del pago registrado.

    Raises:
        HTTPException: Si los datos de la reserva no son válidos, el vehículo
            no existe o no tiene unidades disponibles, devuelve el código
            HTTP 400.
    """

    try:
        return reservas_service.crear_reserva_completa(
            cliente_id=reserva.cliente_id,
            vehiculo_id=reserva.vehiculo_id,
            fecha_inicio=reserva.fecha_inicio,
            fecha_fin=reserva.fecha_fin,
            metodo_pago=reserva.metodo_pago
        )
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e)) from e
