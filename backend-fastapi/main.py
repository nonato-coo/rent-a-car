"""main.py"""

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from database import inicializar_base_de_datos
from routers import vehiculos_router, operaciones_router
from routers import vehiculos_router, operaciones_router, gestion_router

inicializar_base_de_datos()

app = FastAPI(
    title="Rent a Car API",
    description="Backend en FastAPI para el sistema de alquiler de autos",
    version="1.0.0"
)

app.add_middleware (
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=["*"],
    allow_headers=["*"],
    allow_methods=["*"]
)

app.include_router(vehiculos_router.router)
app.include_router(operaciones_router.router)
app.include_router(gestion_router.router)

@app.get("/")
def leer_raiz():
    """Devuelve un mensaje de bienvenida para verificar que el backend está funcionando."""
    return {"mensaje": "Backend de Rent a Car - FastAPI funcionando con éxito!"}
