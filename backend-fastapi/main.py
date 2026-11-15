"""main.py"""

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from database import inicializar_base_de_datos

inicializar_base_de_datos()

app = FastAPI(
    title="Rent A Car API",
    description="Backend en FastAPI para el sistema de alquiler de vehiculos",
    version="1.0.0"
)

app.add_middleware (
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=["*"],
    allow_headers=["*"],
    allow_methods=["*"]
)

@app.get("/")
def leer_raiz():
    """Devuelve un mensaje de bienvenida para verificar que el backend está funcionando."""

    return {"mensaje": "Backend de Rent a Car - FastAPI funcionando con éxito!"}