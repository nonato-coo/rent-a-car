"""seed.py"""

from database import obtener_conexion

def ejecutar_seed():
    """Inserta datos de prueba iniciales en la base de datos."""

    conexion = obtener_conexion()
    cursor = conexion.cursor()

    print("Insertando datos iniciales (seed)...")

    # 1. Crear usuarios de prueba (Admin y Cliente)
    usuarios = [
        ("Ana", "ana@rentacar.com", "admin123", "admin"),
        ("Juan", "juan@gmail.com", "cliente123", "cliente")
    ]

    for nombre, email, password, rol in usuarios:
        cursor.execute("""
            INSERT OR IGNORE INTO usuarios (nombre, email, password, rol)
            VALUES (?, ?, ?, ?)
        """, (nombre, email, password, rol))

    cursor.execute("""
        INSERT OR IGNORE INTO clientes (id, usuario_id, telefono, direccion)
        VALUES (1, 2, "555-1234", "Ciudad Principal, Av. Siempre Viva 123")
    """)

    # 3. Crear vehículos de prueba
    vehiculos = [
        ("Toyota", "Corolla", 2023, "Sedán", 45.0, 5, 1),
        ("Honda", "Civic", 2022, "Sedán", 50.0, 3, 1),
        ("Hyundai", "Elantra", 2024, "Sedán", 70.0, 4, 1),
        ("Ford", "Explorer", 2021, "SUV", 90.0, 2, 1),
        ("Chevrolet", "Spark", 2023, "Compacto", 30.0, 6, 1)
    ]

    for marca, modelo, anio, categoria, precio_por_dia, stock_total, habilitado in vehiculos:
        cursor.execute("""
            SELECT id FROM vehiculos WHERE marca = ? AND modelo = ? AND anio = ?
        """, (marca, modelo, anio))

        if not cursor.fetchone():
            cursor.execute("""
                INSERT INTO vehiculos (marca, modelo, anio, categoria, precio_por_dia, stock_total, habilitado)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            """, (marca, modelo, anio, categoria, precio_por_dia, stock_total, habilitado))

    conexion.commit()
    conexion.close()
    print("¡Datos iniciales insertados con éxito!")

if __name__ == "__main__":
    ejecutar_seed()
