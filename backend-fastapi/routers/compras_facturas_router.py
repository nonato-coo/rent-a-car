from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from database import obtener_conexion

router = APIRouter(tags=["Compras, Facturas y Pagos"])


class CompraCreate(BaseModel):
    vehiculo_id: int
    cantidad: int
    costo_unitario: float
    proveedor: str = ""
    concepto: str = ""
    fecha: str
    estado: str = "completada"


class CompraUpdate(BaseModel):
    vehiculo_id: int | None = None
    cantidad: int | None = None
    costo_unitario: float | None = None
    proveedor: str | None = None
    concepto: str | None = None
    fecha: str | None = None
    estado: str | None = None


class FacturaCreate(BaseModel):
    numero: str | None = None
    fecha: str | None = None
    fecha_emision: str | None = None
    cliente_id: int
    alquiler_id: int | None = None
    subtotal: float
    impuesto: float = 0.0
    total: float | None = None
    estado: str = "pendiente"


class FacturaUpdate(BaseModel):
    numero: str | None = None
    fecha: str | None = None
    fecha_emision: str | None = None
    cliente_id: int | None = None
    alquiler_id: int | None = None
    subtotal: float | None = None
    impuesto: float | None = None
    total: float | None = None
    estado: str | None = None


class PagoCreate(BaseModel):
    alquiler_id: int
    monto: float
    metodo: str = "Tarjeta"
    fecha: str
    estado: str = "completado"


class PagoUpdate(BaseModel):
    alquiler_id: int | None = None
    monto: float | None = None
    metodo: str | None = None
    fecha: str | None = None
    estado: str | None = None


def _asegurar_esquema(conexion, cursor):
    cursor.execute("PRAGMA table_info(facturas)")
    cols_facturas = {fila[1] for fila in cursor.fetchall()}
    if cols_facturas and "estado" not in cols_facturas:
        cursor.execute("ALTER TABLE facturas ADD COLUMN estado TEXT DEFAULT 'pendiente'")
        cols_facturas.add("estado")

    cursor.execute(
        """
        CREATE TABLE IF NOT EXISTS pagos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            alquiler_id INTEGER NOT NULL,
            monto REAL NOT NULL,
            metodo TEXT NOT NULL DEFAULT 'Tarjeta',
            fecha TEXT NOT NULL,
            estado TEXT NOT NULL DEFAULT 'completado'
        )
        """
    )
    conexion.commit()
    return cols_facturas


# ==================== COMPRAS ====================

@router.get("/compras")
def listar_compras():
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cursor.execute("SELECT * FROM compras ORDER BY id DESC")
    filas = [dict(f) for f in cursor.fetchall()]
    conexion.close()
    return filas


@router.get("/compras/{compra_id}")
def obtener_compra(compra_id: int):
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cursor.execute("SELECT * FROM compras WHERE id = ?", (compra_id,))
    fila = cursor.fetchone()
    conexion.close()
    if not fila:
        raise HTTPException(status_code=404, detail="Compra no encontrada")
    return dict(fila)


@router.post("/compras")
def crear_compra(data: CompraCreate):
    if data.cantidad <= 0:
        raise HTTPException(status_code=400, detail="La cantidad debe ser mayor que cero")
    if data.costo_unitario < 0:
        raise HTTPException(status_code=400, detail="El costo unitario no puede ser negativo")

    total = round(data.cantidad * data.costo_unitario, 2)
    conexion = obtener_conexion()
    cursor = conexion.cursor()

    cursor.execute("SELECT id, stock_total FROM vehiculos WHERE id = ?", (data.vehiculo_id,))
    if not cursor.fetchone():
        conexion.close()
        raise HTTPException(status_code=404, detail="Vehículo no encontrado")

    cursor.execute(
        """
        INSERT INTO compras (vehiculo_id, cantidad, costo_unitario, total, proveedor, concepto, fecha, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        """,
        (
            data.vehiculo_id,
            data.cantidad,
            round(data.costo_unitario, 2),
            total,
            data.proveedor,
            data.concepto,
            data.fecha,
            data.estado,
        ),
    )
    nuevo_id = cursor.lastrowid

    if data.estado != "anulada":
        cursor.execute(
            """
            UPDATE vehiculos
            SET stock_total = COALESCE(stock_total, 0) + ?
            WHERE id = ?
            """,
            (data.cantidad, data.vehiculo_id),
        )

    conexion.commit()
    cursor.execute("SELECT * FROM compras WHERE id = ?", (nuevo_id,))
    creada = dict(cursor.fetchone())
    conexion.close()
    return creada


@router.put("/compras/{compra_id}")
def actualizar_compra(compra_id: int, data: CompraUpdate):
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cursor.execute("SELECT * FROM compras WHERE id = ?", (compra_id,))
    actual = cursor.fetchone()
    if not actual:
        conexion.close()
        raise HTTPException(status_code=404, detail="Compra no encontrada")

    actual_dict = dict(actual)
    vehiculo_id = data.vehiculo_id if data.vehiculo_id is not None else actual_dict["vehiculo_id"]
    cantidad = data.cantidad if data.cantidad is not None else actual_dict["cantidad"]
    costo_unitario = data.costo_unitario if data.costo_unitario is not None else actual_dict["costo_unitario"]
    proveedor = data.proveedor if data.proveedor is not None else actual_dict.get("proveedor", "")
    concepto = data.concepto if data.concepto is not None else actual_dict.get("concepto", "")
    fecha = data.fecha if data.fecha is not None else actual_dict["fecha"]
    estado = data.estado if data.estado is not None else actual_dict["estado"]

    if cantidad <= 0 or costo_unitario < 0:
        conexion.close()
        raise HTTPException(status_code=400, detail="Cantidad o costo unitario inválido")

    total = round(cantidad * costo_unitario, 2)

    if actual_dict.get("estado") != "anulada":
        cursor.execute(
            """
            UPDATE vehiculos
            SET stock_total = MAX(0, COALESCE(stock_total, 0) - ?)
            WHERE id = ?
            """,
            (actual_dict["cantidad"], actual_dict["vehiculo_id"]),
        )

    if estado != "anulada":
        cursor.execute(
            """
            UPDATE vehiculos
            SET stock_total = COALESCE(stock_total, 0) + ?
            WHERE id = ?
            """,
            (cantidad, vehiculo_id),
        )

    cursor.execute(
        """
        UPDATE compras
        SET vehiculo_id = ?, cantidad = ?, costo_unitario = ?, total = ?, proveedor = ?, concepto = ?, fecha = ?, estado = ?
        WHERE id = ?
        """,
        (vehiculo_id, cantidad, round(costo_unitario, 2), total, proveedor, concepto, fecha, estado, compra_id),
    )
    conexion.commit()
    cursor.execute("SELECT * FROM compras WHERE id = ?", (compra_id,))
    actualizada = dict(cursor.fetchone())
    conexion.close()
    return actualizada


@router.delete("/compras/{compra_id}")
def eliminar_compra(compra_id: int):
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cursor.execute("SELECT * FROM compras WHERE id = ?", (compra_id,))
    actual = cursor.fetchone()
    if not actual:
        conexion.close()
        raise HTTPException(status_code=404, detail="Compra no encontrada")

    actual_dict = dict(actual)
    if actual_dict.get("estado") != "anulada":
        cursor.execute(
            """
            UPDATE vehiculos
            SET stock_total = MAX(0, COALESCE(stock_total, 0) - ?)
            WHERE id = ?
            """,
            (actual_dict["cantidad"], actual_dict["vehiculo_id"]),
        )

    cursor.execute("DELETE FROM compras WHERE id = ?", (compra_id,))
    conexion.commit()
    conexion.close()
    return {"mensaje": "Compra eliminada", "id": compra_id}


# ==================== FACTURAS ====================

@router.get("/facturas")
def listar_facturas():
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    _asegurar_esquema(conexion, cursor)
    cursor.execute("SELECT * FROM facturas ORDER BY id DESC")
    filas = [dict(f) for f in cursor.fetchall()]
    for f in filas:
        cursor.execute("SELECT * FROM detalle_factura WHERE factura_id = ?", (f["id"],))
        f["detalles"] = [dict(d) for d in cursor.fetchall()]
    conexion.close()
    return filas


@router.get("/facturas/{factura_id}")
def obtener_factura(factura_id: int):
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    _asegurar_esquema(conexion, cursor)
    cursor.execute("SELECT * FROM facturas WHERE id = ?", (factura_id,))
    fila = cursor.fetchone()
    if not fila:
        conexion.close()
        raise HTTPException(status_code=404, detail="Factura no encontrada")
    factura = dict(fila)
    cursor.execute("SELECT * FROM detalle_factura WHERE factura_id = ?", (factura_id,))
    factura["detalles"] = [dict(d) for d in cursor.fetchall()]
    conexion.close()
    return factura


@router.post("/facturas")
def crear_factura(data: FacturaCreate):
    total = round(data.subtotal + data.impuesto, 2) if data.total is None else round(data.total, 2)
    fecha_valor = data.fecha or data.fecha_emision or ""

    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cols = _asegurar_esquema(conexion, cursor)

    campos = []
    valores = []

    if "numero" in cols:
        campos.append("numero")
        valores.append(data.numero)
    if "fecha" in cols:
        campos.append("fecha")
        valores.append(fecha_valor)
    if "fecha_emision" in cols:
        campos.append("fecha_emision")
        valores.append(fecha_valor)
    if "cliente_id" in cols:
        campos.append("cliente_id")
        valores.append(data.cliente_id)
    if "alquiler_id" in cols:
        campos.append("alquiler_id")
        valores.append(data.alquiler_id)
    if "subtotal" in cols:
        campos.append("subtotal")
        valores.append(round(data.subtotal, 2))
    if "impuesto" in cols:
        campos.append("impuesto")
        valores.append(round(data.impuesto, 2))
    if "total" in cols:
        campos.append("total")
        valores.append(total)
    if "estado" in cols:
        campos.append("estado")
        valores.append(data.estado)

    placeholders = ", ".join(["?"] * len(campos))
    cursor.execute(
        f"INSERT INTO facturas ({', '.join(campos)}) VALUES ({placeholders})",
        tuple(valores),
    )
    nuevo_id = cursor.lastrowid
    conexion.commit()
    cursor.execute("SELECT * FROM facturas WHERE id = ?", (nuevo_id,))
    creada = dict(cursor.fetchone())
    creada["detalles"] = []
    conexion.close()
    return creada


@router.put("/facturas/{factura_id}")
def actualizar_factura(factura_id: int, data: FacturaUpdate):
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cols = _asegurar_esquema(conexion, cursor)
    cursor.execute("SELECT * FROM facturas WHERE id = ?", (factura_id,))
    actual = cursor.fetchone()
    if not actual:
        conexion.close()
        raise HTTPException(status_code=404, detail="Factura no encontrada")

    d = dict(actual)

    fecha_actual = d.get("fecha") or d.get("fecha_emision") or ""
    fecha_nueva = data.fecha or data.fecha_emision or fecha_actual
    numero = data.numero if data.numero is not None else d.get("numero")
    cliente_id = data.cliente_id if data.cliente_id is not None else d.get("cliente_id")
    alquiler_id = data.alquiler_id if data.alquiler_id is not None else d.get("alquiler_id")
    subtotal = float(data.subtotal if data.subtotal is not None else d.get("subtotal", 0))
    impuesto = float(data.impuesto if data.impuesto is not None else d.get("impuesto", 0))
    total = float(data.total if data.total is not None else round(subtotal + impuesto, 2))
    estado = data.estado if data.estado is not None else (d.get("estado") or "pendiente")

    sets = []
    valores = []

    if "numero" in cols:
        sets.append("numero = ?")
        valores.append(numero)
    if "fecha" in cols:
        sets.append("fecha = ?")
        valores.append(fecha_nueva)
    if "fecha_emision" in cols:
        sets.append("fecha_emision = ?")
        valores.append(fecha_nueva)
    if "cliente_id" in cols:
        sets.append("cliente_id = ?")
        valores.append(cliente_id)
    if "alquiler_id" in cols:
        sets.append("alquiler_id = ?")
        valores.append(alquiler_id)
    if "subtotal" in cols:
        sets.append("subtotal = ?")
        valores.append(round(subtotal, 2))
    if "impuesto" in cols:
        sets.append("impuesto = ?")
        valores.append(round(impuesto, 2))
    if "total" in cols:
        sets.append("total = ?")
        valores.append(round(total, 2))
    if "estado" in cols:
        sets.append("estado = ?")
        valores.append(estado)

    valores.append(factura_id)
    cursor.execute(
        f"UPDATE facturas SET {', '.join(sets)} WHERE id = ?",
        tuple(valores),
    )
    conexion.commit()
    cursor.execute("SELECT * FROM facturas WHERE id = ?", (factura_id,))
    actualizada = dict(cursor.fetchone())
    cursor.execute("SELECT * FROM detalle_factura WHERE factura_id = ?", (factura_id,))
    actualizada["detalles"] = [dict(det) for det in cursor.fetchall()]
    conexion.close()
    return actualizada


@router.delete("/facturas/{factura_id}")
def eliminar_factura(factura_id: int):
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    cursor.execute("SELECT id FROM facturas WHERE id = ?", (factura_id,))
    if not cursor.fetchone():
        conexion.close()
        raise HTTPException(status_code=404, detail="Factura no encontrada")
    cursor.execute("DELETE FROM detalle_factura WHERE factura_id = ?", (factura_id,))
    cursor.execute("DELETE FROM facturas WHERE id = ?", (factura_id,))
    conexion.commit()
    conexion.close()
    return {"mensaje": "Factura eliminada", "id": factura_id}


# ==================== PAGOS ====================

@router.get("/pagos")
def listar_pagos():
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    _asegurar_esquema(conexion, cursor)
    cursor.execute("SELECT * FROM pagos ORDER BY id DESC")
    filas = [dict(f) for f in cursor.fetchall()]
    conexion.close()
    return filas


@router.get("/pagos/{pago_id}")
def obtener_pago(pago_id: int):
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    _asegurar_esquema(conexion, cursor)
    cursor.execute("SELECT * FROM pagos WHERE id = ?", (pago_id,))
    fila = cursor.fetchone()
    conexion.close()
    if not fila:
        raise HTTPException(status_code=404, detail="Pago no encontrado")
    return dict(fila)


@router.post("/pagos")
def crear_pago(data: PagoCreate):
    if data.monto <= 0:
        raise HTTPException(status_code=400, detail="El monto debe ser mayor que cero")

    conexion = obtener_conexion()
    cursor = conexion.cursor()
    _asegurar_esquema(conexion, cursor)

    cursor.execute("SELECT id FROM alquileres WHERE id = ?", (data.alquiler_id,))
    if not cursor.fetchone():
        conexion.close()
        raise HTTPException(status_code=404, detail="Alquiler no encontrado")

    cursor.execute(
        """
        INSERT INTO pagos (alquiler_id, monto, metodo, fecha, estado)
        VALUES (?, ?, ?, ?, ?)
        """,
        (data.alquiler_id, round(data.monto, 2), data.metodo, data.fecha, data.estado),
    )
    nuevo_id = cursor.lastrowid

    # Marcar como pagada la factura asociada al alquiler si existe
    cursor.execute(
        "UPDATE facturas SET estado = 'pagada' WHERE alquiler_id = ? AND estado = 'pendiente'",
        (data.alquiler_id,),
    )

    conexion.commit()
    cursor.execute("SELECT * FROM pagos WHERE id = ?", (nuevo_id,))
    creado = dict(cursor.fetchone())
    conexion.close()
    return creado


@router.put("/pagos/{pago_id}")
def actualizar_pago(pago_id: int, data: PagoUpdate):
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    _asegurar_esquema(conexion, cursor)
    cursor.execute("SELECT * FROM pagos WHERE id = ?", (pago_id,))
    actual = cursor.fetchone()
    if not actual:
        conexion.close()
        raise HTTPException(status_code=404, detail="Pago no encontrado")

    d = dict(actual)
    alquiler_id = data.alquiler_id if data.alquiler_id is not None else d["alquiler_id"]
    monto = float(data.monto if data.monto is not None else d["monto"])
    metodo = data.metodo if data.metodo is not None else d["metodo"]
    fecha = data.fecha if data.fecha is not None else d["fecha"]
    estado = data.estado if data.estado is not None else d.get("estado", "completado")

    if monto <= 0:
        conexion.close()
        raise HTTPException(status_code=400, detail="El monto debe ser mayor que cero")

    cursor.execute(
        """
        UPDATE pagos
        SET alquiler_id = ?, monto = ?, metodo = ?, fecha = ?, estado = ?
        WHERE id = ?
        """,
        (alquiler_id, round(monto, 2), metodo, fecha, estado, pago_id),
    )
    conexion.commit()
    cursor.execute("SELECT * FROM pagos WHERE id = ?", (pago_id,))
    actualizado = dict(cursor.fetchone())
    conexion.close()
    return actualizado


@router.delete("/pagos/{pago_id}")
def eliminar_pago(pago_id: int):
    conexion = obtener_conexion()
    cursor = conexion.cursor()
    _asegurar_esquema(conexion, cursor)
    cursor.execute("SELECT id, alquiler_id FROM pagos WHERE id = ?", (pago_id,))
    actual = cursor.fetchone()
    if not actual:
        conexion.close()
        raise HTTPException(status_code=404, detail="Pago no encontrado")

    alquiler_id = dict(actual)["alquiler_id"]
    cursor.execute("DELETE FROM pagos WHERE id = ?", (pago_id,))
    cursor.execute(
        "UPDATE facturas SET estado = 'pendiente' WHERE alquiler_id = ? AND estado = 'pagada'",
        (alquiler_id,),
    )
    conexion.commit()
    conexion.close()
    return {"mensaje": "Pago eliminado", "id": pago_id}