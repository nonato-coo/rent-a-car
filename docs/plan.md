# Proyecto Rent a Car

---

## 1. Reglas

### Git

- `main` solo recibe código que funciona.
- Flujo: `git pull` → trabajar → `git add .` → `git commit -m "mensaje claro"` → `git push`.
- Para unir a `main`: Pull Request en GitHub, y otro integrante lo revisa.
- Cada parte tiene su propia carpeta, así casi no hay conflictos.

### Cómo se ejecuta todo

| Módulo     | Comando                                                      | Dirección                               |
| ---------- | ------------------------------------------------------------ | --------------------------------------- |
| Backend    | `uvicorn main:app --reload` (dentro de `backend-fastapi/`)   | http://localhost:8000 (docs en `/docs`) |
| Admin PHP  | `php -S localhost:8080` (dentro de `admin-portal-php/`)      | http://localhost:8080                   |
| Cliente JS | `python -m http.server 3000` (dentro de `client-portal-js/`) | http://localhost:3000                   |

---

## 2. Backend (Python + FastAPI)

### 2.1 Tecnologías y arquitectura

- **FastAPI:** define rutas con funciones y genera la documentación sola en `/docs`.
- **Pydantic:** clases que validan el JSON que entra y sale.
- **SQLite:** base de datos en un solo archivo. Se usa con el módulo `sqlite3` de Python, que ya viene instalado.
- **Capas:** el router recibe la petición, un servicio aplica las reglas, un repositorio habla con la base. Mantiene el código ordenado.

### 2.2 Estructura sugerida

```
backend-fastapi/
├── main.py                 # crea la app, CORS, registra routers
├── database.py             # conexión SQLite y creación de tablas
├── seed.py                 # datos de prueba
├── requirements.txt        # fastapi, uvicorn
├── schemas/                # modelos Pydantic (entrada/salida de la API)
├── domain/                 # clases POO del negocio
│   ├── vehiculo.py
│   ├── alquiler.py
│   ├── factura.py
│   └── calculadora.py
├── repositories/           # acceso a SQLite (una clase por entidad)
├── services/               # reglas de negocio
├── routers/                # un archivo por recurso
└── utils/funcional.py      # funciones puras reutilizables
```

### 2.3 Tareas

**Preparación**

- Crear entorno virtual (`python -m venv venv`) e instalar `fastapi` y `uvicorn`. Guardar en `requirements.txt`.
- Crear `main.py` con una ruta `/` de prueba. Abrir `/docs`.
- Activar CORS (`CORSMiddleware`, `allow_origins=["*"]` para desarrollo).

**Base de datos**

- `database.py`: función que abre la conexión y crea las tablas `usuarios`, `clientes`, `vehiculos`, `alquileres`, `pagos`, `facturas`, `detalle_factura`, `compras` y `mantenimientos`.
- `seed.py`: 1 admin, 1 cliente, y varios vehículos con marcas y años variados.

**Dominio (POO)**

- Clase `Vehiculo` con atributos privados (`_stock_total`, `_habilitado`) y métodos como `agregar_stock(cantidad)` y `puede_alquilarse(disponibles)`.
- Clase `Alquiler` con `calcular_dias()` y estados controlados.
- Clase `Factura` que compone una lista de `DetalleFactura` y calcula sus totales.
- Clase `CalculadoraCostos` con la tasa de impuesto como constante.
- Herencia: una clase base `Entidad` con `id` y `to_dict()`, de la que heredan las demás.

**Repositorios y servicios**

- Un repositorio por entidad con los métodos `listar`, `obtener`, `crear`, `actualizar`, `eliminar`.
- `ServicioInventario`: calcula `disponibles` y aplica una compra (suma stock).
- `ServicioAlquileres`: valida disponibilidad, cambia estados (activar, devolver, cancelar).
- `ServicioReservas`: crea alquiler + pago simulado + factura en una sola operación.

**Routers (en este orden por las dependencias)**

- `/login` y `/registro`.
- `/vehiculos` con CRUD y filtros (`marca`, `año`, `categoria`, `solo_disponibles`), más `/marcas` y `/ años`.
- `/usuarios` y `/clientes` (CRUD).
- `/cotizar`.
- `/compras` (crea compra y aumenta stock).
- `/alquileres` (CRUD + `activar`, `devolver`, `cancelar`) y `/reservas`.
- `/pagos` y `/facturas` (CRUD + filtros `cliente_id` y `alquiler_id`).
- `/clientes/{id}/alquileres`.
- _(opcional)_ `/mantenimientos`.

**Cierre**

- Probar todo desde `/docs` y corregir códigos de error y mensajes.
- Diagrama de BD (ver sección 5).

---

## 3. Portal administrativo (PHP)

### 3.1 Tecnologías y arquitectura

- PHP corre **en el servidor**: el portal llama a la API desde PHP (con cURL), no desde el navegador. Por eso no tiene problemas de CORS.
- **Sesiones:** `session_start()` y `$_SESSION` guardan quién inició sesión.
- El portal solo habla con la API. No debe abrir la base de datos.
- Interfaz sugerida: HTML + Bootstrap por CDN, para avanzar rápido.

### 3.2 Estructura sugerida

```
admin-portal-php/
├── index.php              # panel principal (dashboard)
├── login.php / logout.php
├── config.php             # URL base de la API
├── src/
│   ├── ApiClient.php      # clase que hace GET/POST/PUT/DELETE con cURL
│   ├── Recurso.php        # clase base CRUD (listar, obtener, crear, ...)
│   ├── Auth.php           # manejo de sesión
│   └── Helpers.php        # funciones puras (formato de dinero, filtros)
├── vistas/                # plantillas compartidas (header, menú, footer)
└── paginas/
    ├── usuarios.php
    ├── clientes.php
    ├── vehiculos.php
    ├── alquileres.php
    ├── pagos.php
    ├── facturas.php
    ├── compras.php
    └── mantenimientos.php (opcional)
```

### 3.3 Tareas

- Verificar que PHP funciona: `php -v` y `php -S localhost:8080`.
- `config.php` con la URL base.
- `ApiClient`: método `request($metodo, $ruta, $datos)` con cURL que devuelve el JSON decodificado y maneja errores.
- `Recurso` (clase base) y una clase por recurso que hereda de ella (`Vehiculos`, `Clientes`, etc.).
- Login admin con `/login`, sesión y protección de páginas (si no hay sesión, redirigir).
- Plantilla común con menú y Bootstrap.
- Página **Vehículos**: tabla, crear, editar, eliminar, mostrar stock y `habilitado`.
- Página **Clientes** y **Usuarios**: CRUD completo.
- Página **Compras**: formulario (vehículo, cantidad, costo, proveedor) y lista. Mostrar el stock actualizado.
- Página **Alquileres**: lista, crear, editar, y botones Activar / Devolver / Cancelar.
- Páginas **Pagos** y **Facturas**: CRUD y vista de detalle de factura.
- _(opcional)_ **Mantenimientos** y marcar un vehículo como no habilitado.
- Dashboard con totales (vehículos, alquileres activos, facturado) usando `array_map` y `array_reduce`.
- Mensajes de éxito y error en cada operación.

---

## 4. Portal de clientes (JavaScript)

### 4.1 Tecnologías y arquitetura

- JavaScript corre **en el navegador** y llama a la API con `fetch`. Por eso el backend necesita CORS.
- `async/await` espera la respuesta de la API.
- Guarda al usuario con sesión iniciada en `localStorage` (`JSON.stringify` / `JSON.parse`).
- Sugerencia: JS puro (vanilla), HTML5, CSS3 y Bootstrap por CDN. Es lo que muestra el diagrama del PDF.

### 4.2 Estructura sugerida

```
client-portal-js/
├── index.html              # catálogo + filtros
├── login.html / registro.html
├── reserva.html            # calculadora y confirmación
├── factura.html
├── historial.html          # mis alquileres
├── css/estilos.css
└── js/
    ├── config.js           # URL base de la API
    ├── api.js              # clase ApiClient con fetch
    ├── auth.js             # clase Sesion (guardar/leer usuario)
    ├── modelos/Vehiculo.js, Factura.js
    ├── utils/funcional.js  # funciones puras
    └── paginas/            # un archivo JS por página
```

### 4.3 Tareas

- Servir la carpeta con `python -m http.server 3000` o Live Server.
- `config.js` y `api.js` con una clase `ApiClient` (GET y POST con `fetch`).
- `registro.html` + `login.html` + clase `Sesion`. Si no hay sesión, redirigir al login.
- Catálogo: pedir `/vehiculos` y dibujar tarjetas (marca, modelo, año, precio, disponibles).
- Filtros por marca y año: llenar los `<select>` con `/marcas` y `/ años` y filtrar.
- _(opcional pero recomendado)_ Filtro por categoría y detalle de un vehículo.
- Calculadora de gastos: elegir fechas, llamar a `/cotizar`, mostrar días, subtotal, impuesto y total en vivo.
- Reserva simulada: botón "Reservar" que llama a `/reservas` y redirige a la factura.
- Página de factura: número, fecha, cliente, detalle, subtotal, impuesto y total. Botón de imprimir (`window.print()`).
- _(opcional)_ Historial de alquileres del cliente (`/clientes/{id}/alquileres`).
- Validaciones (fechas coherentes, campos vacíos) y mensajes claros.
- Revisión en celular (diseño responsivo con Bootstrap).

---

## 5. Documentación

| Entregable                   | Contenido                                                                             |
| ---------------------------- | ------------------------------------------------------------------------------------- |
| Diagrama de base de datos    | Tablas, campos, llaves primarias y foráneas (en draw.io o dbdiagram.io).              |
| Diagrama de clases           | Clases, atributos, métodos y relaciones (herencia/composición) de los tres lenguajes. |
| Manual de usuario - admin    | Cómo iniciar sesión y usar cada pantalla, con capturas.                               |
| Manual de usuario - cliente  | Registro, búsqueda, cotización, reserva y factura, con capturas.                      |
| Instrucciones de instalación | Cómo correr los tres módulos (sección 0 de este archivo). Va en el `README.md`.       |

Guardar todo en `docs/`.

---

## 6. Integración y pruebas

- Admin inicia sesión en el portal PHP.
- Admin crea un vehículo (stock 0) y registra una **compra** → el stock sube.
- Cliente se registra y entra en el portal JS.
- Cliente filtra por marca y año, y ve el vehículo con su disponibilidad.
- Cliente cotiza y **reserva** → se genera la factura con número, fecha, detalle, subtotal, impuesto y total.
- Admin ve el alquiler, la factura y el pago en PHP.
- Admin pasa el alquiler a **activo** y luego a **devuelto**; la disponibilidad vuelve a subir.
- Admin marca un vehículo como no habilitado → desaparece de los disponibles del cliente.
- Probar errores: login incorrecto, fechas inválidas, reservar sin disponibilidad.
- Los totales de la calculadora y de la factura coinciden.

---

## 8. Rúbrica

| Criterio               | Peso | Verificar                                                           |
| ---------------------- | ---- | ------------------------------------------------------------------- |
| Backend REST           | 20 % | Todos los CRUD, login, compras con stock automático, JSON correcto  |
| Portal administrativo  | 20 % | Todas las gestiones, solo consume la API                            |
| Tienda virtual         | 20 % | Registro/login, catálogo, filtros, calculadora, reserva con factura |
| Integración            | 10 % | Flujo completo sin errores entre los tres módulos                   |
| POO + funcional        | 15 % | Visible y explicable en cada lenguaje                               |
| Presentación y defensa | 10 % | Demo fluida y dominio del proyecto                                  |
| Documentación          | 5 %  | Diagramas, manual, instrucciones                                    |
