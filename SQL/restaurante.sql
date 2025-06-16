DROP DATABASE IF EXISTS Restaurante;
CREATE DATABASE Restaurante;
USE Restaurante;

CREATE TABLE Categorias (
    codCategoria INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(40),
    Descripcion VARCHAR(100)
) ENGINE=InnoDB;

/*INSERCIONES QUE PONGO DE PRUEBA PARA VER COMO SE VE LA PAGINA*/
INSERT INTO Categorias (Nombre, Descripcion) VALUES
  ('Tapas', 'Tapas variadas'),
  ('Cafes', 'Cafes y bebidas calientes'),
  ('Refrescos', 'Refrescos y bebidas sin alcohol'),
  ('Bebidas alcoholicas', 'Cervezas, vinos y licores'),
  ('Postre', 'Postres dulces'),
  ('Raciones', 'Raciones para compartir');
  
CREATE TABLE Mesas (
    numMesa VARCHAR(10) PRIMARY KEY,
    estado ENUM('vacia', 'ocupada') NOT NULL
) ENGINE=InnoDB;

INSERT INTO Mesas (numMesa, estado) VALUES
('1', 'vacia'),
('2', 'vacia'),
('3', 'vacia'),
('4', 'vacia');

CREATE TABLE Empleados (
    codEmpleado INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(40),
    Apellidos VARCHAR(60),
    Correo VARCHAR(60)UNIQUE,
    Telefono VARCHAR(10),
    Rol ENUM('administrador', 'camarero','cocinero','barra') NOT NULL,
    Clave VARCHAR(255)/*es importante que este campo tenga 255 caracteres, ya que las contraseñas las hashearee pòr seguridad*/
) ENGINE=InnoDB;

/*INSERCIONES QUE PONGO DE PRUEBA PARA LOS EMPLEADOS*/
INSERT INTO Empleados (Nombre, Apellidos, Correo, Telefono, Rol, Clave)
VALUES 
('Juan', 'Perez Garcia', 'juan.perez@email.com', '600123456', 'administrador', 'admin123'),
('Ana', 'Lopez Ruiz', 'ana.lopez@email.com', '600654321', 'camarero', 'camarero123'),
('Luis', 'Martinez Soto', 'luis.martinez@email.com', '600789123', 'cocinero', 'cocinero123');
('admin', 'admin', 'admin@gmail.com', '600789123', 'administrador', 'admin');

CREATE TABLE Productos (
    codProducto INT PRIMARY KEY AUTO_INCREMENT,
    Foto VARCHAR(70),
    codCategoria INT NOT NULL,
    Nombre VARCHAR(40),
    Descripcion VARCHAR(100),
    Precio DECIMAL(10,2),
    QuienLoAtiende VARCHAR(40),
    Stock INT NOT NULL,
    FOREIGN KEY (codCategoria) REFERENCES Categorias(codCategoria)
) ENGINE=InnoDB;

/* INSERCIONES DE PRUEBA PARA LOS PRODUCTOS */
INSERT INTO Productos (Foto, codCategoria, Nombre, Descripcion, Precio, QuienLoAtiende, Stock) VALUES
  ('tortillaPatatas.jpg', 1, 'Tortilla de patatas', 'Tapa clasica española', 2.50, 'barra', 20),
  ('croquetas.jpg', 1, 'Croquetas caseras', 'Croquetas de jamon', 3.00, 'barra', 15),
  ('cafeExpresso.jpg', 2, 'Cafe solo', 'Cafe expresso', 1.20, 'camarero', 50),
  ('cafeConLeche.jpg', 2, 'Cafe con leche', 'Cafe con leche cremosa', 1.50, 'camarero', 40),
  ('refrescoCocaCola.jpg', 3, 'Coca-Cola', 'Refresco de cola', 1.80, 'barra', 30),
  ('aguaMineral.jpg', 3, 'Agua mineral', 'Botella de agua', 1.00, 'barra', 25),
  ('cerveza.jpg', 4, 'Cerveza', 'Cerveza nacional', 2.00, 'barra', 35),
  ('vinoTinto.jpg', 4, 'Vino tinto', 'Copa de vino tinto', 2.50, 'barra', 20),
  ('tartaQueso.jpg', 5, 'Tarta de queso', 'Porcion de tarta de queso', 3.50, 'camarero', 10),
  ('helado.jpg', 5, 'Helado', 'Helado de vainilla', 2.00, 'camarero', 12),
  ('calamares.jpg', 6, 'Calamares', 'Racion de calamares a la romana', 8.00, 'cocinero', 8),
  ('patatasBravas.jpg', 6, 'Patatas bravas', 'Racion de patatas bravas', 5.00, 'cocinero', 10);

CREATE TABLE Pedidos (
    codPedido INT PRIMARY KEY AUTO_INCREMENT,
    numMesa VARCHAR(10) NOT NULL,
    Estado ENUM('pendiente', 'pagado') NOT NULL DEFAULT 'pendiente',
    Fecha DATETIME,
    Observaciones VARCHAR(200) NULL,
    FOREIGN KEY (numMesa) REFERENCES Mesas(numMesa),
    Total DECIMAL(10,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE DetallePedidos (
    codDetallePedido INT NOT NULL AUTO_INCREMENT,
    codPedido INT NOT NULL,
    codProducto INT,
    Cantidad INT,
    precioUnitario DECIMAL(10,2),
    Estado ENUM('pendiente', 'preparando', 'listo', 'servido', 'cancelado') NOT NULL DEFAULT 'pendiente',
    Observaciones VARCHAR(200) NULL,
    PRIMARY KEY (codDetallePedido),
    FOREIGN KEY (codPedido) REFERENCES Pedidos(codPedido),
    FOREIGN KEY (codProducto) REFERENCES Productos(codProducto)
) ENGINE=InnoDB;

CREATE TABLE EmpleadoDetallesPedidos (
    codEmpleado INT NOT NULL,
    Fecha DATETIME NOT NULL,
    codDetallePedido INT NOT NULL,
    cambioEstado ENUM('pendiente', 'preparando', 'listo', 'servido', 'cancelado') NOT NULL,
    PRIMARY KEY (codEmpleado, Fecha, codDetallePedido),
    FOREIGN KEY (codEmpleado) REFERENCES Empleados(codEmpleado),
    FOREIGN KEY (codDetallePedido) REFERENCES DetallePedidos(codDetallePedido)
) ENGINE=InnoDB;