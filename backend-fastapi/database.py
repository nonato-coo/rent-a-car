"""database.py"""

import sqlite3

DB_NOMBRE = "rentacar.db"

def obtener_conexion():
    """Retorna una conexión a la base de datos de SQLlite."""

    conexion = sqlite3.connect(DB_NOMBRE)
    conexion.row_factory = sqlite3.Row
    return conexion

def inicializar_base_de_datos():
    """Crea todas las tablas de la base de datos si no existen."""

    conexion = obtener_conexion()
    cursor = conexion.cursor()

    # Tabla de Usuarios
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL, 
        rol TEXT NOT NULL -- puede ser 'admin' o 'usuario'
    )
    """)

    # Tabla de Clientes
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS clientes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER,
        telefono TEXT,
        direccion TEXT,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    )
    """)

    # Tabla de Vehículos
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS vehiculos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        marca TEXT NOT NULL,
        modelo TEXT NOT NULL,
        anio INTEGER NOT NULL,
        categoria TEXT NOT NULL,
        precio_por_dia REAL NOT NULL,
        stock_total INTEGER NOT NULL DEFAULT 0,
        habilitado INTEGER NOT NULL DEFAULT 1 -- 1 = habilatado, 0 = no habilitado
    )
    """)

    # Tabla de Compras
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS compras (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vehiculo_id INTEGER,
        cantidad INTEGER NOT NULL,
        costo_unitario REAL NOT NULL,
        proveedor TEXT,
        fecha TEXT NOT NULL,
        FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id)
    )
    """)

    # Tabla de Alquileres
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS alquileres (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cliente_id INTEGER,
        vehiculo_id INTEGER,
        fecha_inicio TEXT NOT NULL,
        fecha_fin TEXT NOT NULL,
        estado TEXT NOT NULL DEFAULT 'reservado', -- puede ser 'reservado', 'activo', 'devuelto' o 'cancelado'
        FOREIGN KEY (cliente_id) REFERENCES clientes(id),
        FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id)
    )
    """)

    # Tabla de Pagos
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS pagos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        alquiler_id INTEGER,
        monto REAL NOT NULL,
        fecha TEXT NOT NULL,
        metodo TEXT NOT NULL,
        FOREIGN KEY (alquiler_id) REFERENCES alquileres(id)
    )
    """)

    # Tabla de Facturas
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS facturas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        alquiler_id INTEGER,
        cliente_id INTEGER,
        fecha TEXT NOT NULL,
        subtotal REAL NOT NULL,
        impuesto REAL NOT NULL,
        total REAL NOT NULL,
        FOREIGN KEY (alquiler_id) REFERENCES alquileres(id),
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    )
    """)

    # Tabla de Detalle de Factura
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS detalle_factura (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        factura_id INTEGER,
        descripcion TEXT NOT NULL,
        cantidad INTEGER NOT NULL,
        precio_unitario REAL NOT NULL,
        subtotal REAL NOT NULL,
        FOREIGN KEY (factura_id) REFERENCES facturas(id)
    )
    """)

    # Tabla de Mantenimientos
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS mantenimientos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vehiculo_id INTEGER,
        descripcion TEXT NOT NULL,
        fecha TEXT NOT NULL,
        costo REAL,
        FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id)
    )
    """)

    conexion.commit()
    conexion.close()
    print("Base de datos inicializada correctamente.")
