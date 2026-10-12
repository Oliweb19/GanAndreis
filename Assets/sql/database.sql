-- Copia de database.sql
-- Importar este archivo en phpMyAdmin de cPanel
CREATE TABLE IF NOT EXISTS `administradores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `creado_el` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sorteos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(200) NOT NULL,
  `descripcion` TEXT,
  `fecha_sorteo` DATE NOT NULL,
  `hora_sorteo` VARCHAR(20) NOT NULL,
  `precio_unitario` DECIMAL(10,2) NOT NULL DEFAULT 3.50,
  `imagen` VARCHAR(255) DEFAULT 'Assets/img/Prueba.jpg',
  `activo` TINYINT(1) DEFAULT 1,
  `creado_el` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `metodos_pago` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(50) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `icono` VARCHAR(50) NOT NULL,
  `datos_html` TEXT NOT NULL,
  `activo` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `compras` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_orden` VARCHAR(20) NOT NULL UNIQUE,
  `cedula` VARCHAR(30) NOT NULL,
  `nombre_completo` VARCHAR(150) NOT NULL,
  `correo` VARCHAR(150) NOT NULL,
  `cod_pais` VARCHAR(10) NOT NULL DEFAULT '+58',
  `telefono` VARCHAR(30) NOT NULL,
  `cantidad_tickets` INT NOT NULL DEFAULT 1,
  `total_monto` DECIMAL(10,2) NOT NULL,
  `metodo_pago` VARCHAR(50) NOT NULL,
  `comprobante_url` VARCHAR(255) DEFAULT NULL,
  `estado` ENUM('pendiente', 'validado', 'rechazado') DEFAULT 'pendiente',
  `notas` TEXT NULL,
  `creado_el` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `compra_id` INT NOT NULL,
  `sorteo_id` INT NOT NULL,
  `numero_ticket` VARCHAR(20) NOT NULL,
  `estado` ENUM('pendiente', 'validado', 'rechazado') DEFAULT 'pendiente',
  `creado_el` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`numero_ticket`),
  INDEX (`compra_id`),
  FOREIGN KEY (`compra_id`) REFERENCES `compras`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `administradores` (`id`, `nombre`, `email`, `password`)
VALUES (1, 'Administrador GanAndreis', 'admin@ganandreis.com', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

INSERT INTO `sorteos` (`id`, `titulo`, `descripcion`, `fecha_sorteo`, `hora_sorteo`, `precio_unitario`, `imagen`, `activo`)
VALUES (1, 'Gran Premio iPhone 15 Pro Max', 'Participa por este increíble premio. Tu oportunidad de ganar es ahora.', '2026-12-25', '08:00 PM', 3.50, 'Assets/img/Prueba.jpg', 1)
ON DUPLICATE KEY UPDATE `titulo` = VALUES(`titulo`);

INSERT INTO `metodos_pago` (`id`, `codigo`, `nombre`, `icono`, `datos_html`, `activo`) VALUES
(1, 'pago-movil', 'Pago Móvil', 'fa-solid fa-mobile-screen-button', '<h4>Pago Móvil Venezuela</h4><p><strong>C.I:</strong> 12.345.678</p><p><strong>Teléfono:</strong> 0414-1234567</p><p><strong>Banco:</strong> Banco de Venezuela (0102)</p>', 1),
(2, 'binance', 'Binance', 'fa-brands fa-bitcoin', '<h4>Binance Pay</h4><p><strong>ID Pay:</strong> 123456789</p><p><strong>Correo:</strong> pagos@ganandreis.com</p>', 1),
(3, 'zelle', 'Zelle', 'fa-solid fa-z', '<h4>Zelle</h4><p><strong>Correo:</strong> zelle@ganandreis.com</p><p><strong>Titular:</strong> Inversiones GanAndreis</p>', 1),
(4, 'paypal', 'PayPal', 'fa-brands fa-paypal', '<h4>PayPal</h4><p><strong>Usuario:</strong> @ganandreis_oficial</p>', 1)
ON DUPLICATE KEY UPDATE `codigo` = VALUES(`codigo`);
