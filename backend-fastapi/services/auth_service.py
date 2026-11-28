"""Define la clase 'ServicioAuth'

Métodos:
    login(email: str, password: str) -> dict
    crear_usuario(nombre, email, password, rol, telefono, direccion) -> dict
    registrar(nombre, email, password, telefono, direccion) -> dict
"""

from domain.usuario import Usuario
from domain.cliente import Cliente
from repositories.usuario_repository import UsuarioRepository
from repositories.cliente_repository import ClienteRepository

class ServicioAuth:
    """Gestiona el inicio de sesión y el registro de usuarios y clientes."""

    def __init__(self):
        """Inicializa los repositorios necesarios para la autenticación."""

        self.usuario_repo = UsuarioRepository()
        self.cliente_repo = ClienteRepository()

    def login(self, email: str, password: str) -> dict:
        """Valida las credenciales de un usuario.

        Args:
            email (str): Correo electrónico del usuario.
            password (str): Contraseña del usuario.

        Returns:
            dict: Datos del usuario (sin contraseña) y su cliente_id,
                que es None si el usuario no es cliente.

        Raises:
            ValueError: Si el correo no existe o la contraseña no coincide.
        """

        usuario = self.usuario_repo.buscar_por_email(email)
        if not usuario or not usuario.verificar_password(password):
            raise ValueError("Credenciales incorrectas")

        datos = usuario.to_dict()
        cliente = self.cliente_repo.obtener_por_usuario_id(usuario.id)
        datos["cliente_id"] = cliente.id if cliente else None
        return datos

    def crear_usuario(self, nombre: str, email: str, password: str, rol: str,
                      telefono: str = "", direccion: str = "") -> dict:
        """Crea un usuario y, si su rol es cliente, también su perfil de cliente.

        Si falla la creación del cliente, se elimina el usuario recién creado
        para no dejar datos incompletos.

        Args:
            nombre (str): Nombre completo.
            email (str): Correo electrónico (debe ser único).
            password (str): Contraseña de acceso.
            rol (str): Rol del usuario ("admin" o "cliente").
            telefono (str): Teléfono del cliente. Por defecto, vacío.
            direccion (str): Dirección del cliente. Por defecto, vacía.

        Returns:
            dict: Identificadores del usuario y del cliente (None si no aplica),
                junto con el nombre y el rol asignados.

        Raises:
            ValueError: Si el correo ya está registrado o el rol no es válido.
        """

        if self.usuario_repo.buscar_por_email(email):
            raise ValueError("El correo ya está registrado.")

        usuario = Usuario(nombre=nombre, email=email, password=password, rol=rol)
        usuario_id = self.usuario_repo.crear(usuario)

        cliente_id = None
        if usuario.rol == "cliente":
            try:
                cliente = Cliente(usuario_id=usuario_id, telefono=telefono, direccion=direccion)
                cliente_id = self.cliente_repo.crear(cliente)
            except Exception:
                self.usuario_repo.eliminar(usuario_id)
                raise

        return {
            "usuario_id": usuario_id,
            "cliente_id": cliente_id,
            "nombre": usuario.nombre,
            "rol": usuario.rol
        }

    def registrar(self, nombre: str, email: str, password: str,
                  telefono: str = "", direccion: str = "") -> dict:
        """Registra un cliente por sí mismo; el rol siempre es "cliente"."""

        return self.crear_usuario(nombre, email, password, "cliente", telefono, direccion)
