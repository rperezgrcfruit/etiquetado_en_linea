-- ============================================================
-- Tabla: gramajes
-- Proyecto: grcfruit_grc (VPS 45.239.108.34)
-- Propósito: Almacenar rangos de peso por fruto (gramajes)
--            asociados a cada calibre, por empresa y mercado.
-- Concepto: Norma de Calidad (Paltas Hass)
--   - Calibre = peso por fruto en gramos (Tabla A USA / Tabla B Europa)
--   - Gramaje = min_gramaje y max_gramaje: rango de peso por fruto
--   - Cada empresa/cliente entrega sus rangos por calibre + mercado
--   - La misma calibre tiene rangos distintos segun mercado (USA vs Europa)
-- Convenciones: sigue el patrón de la tabla `calibres`
--   - empresa VARCHAR(200) = R_SOCIAL (denormalizado)
--   - calibre  VARCHAR(45)  = valor del calibre (ej: '32', 'A', 'S')
--   - estado ENUM = como embalajes
-- ============================================================

CREATE TABLE IF NOT EXISTS gramajes (
    id              INT(11)         NOT NULL AUTO_INCREMENT COMMENT 'PK',
    empresa         VARCHAR(200)    NOT NULL COMMENT 'R_SOCIAL de la empresa/cliente',
    min_gramaje     DECIMAL(6,2)    NOT NULL COMMENT 'Peso minimo por fruto en gramos (ej: 333.00)',
    max_gramaje     DECIMAL(6,2)    NOT NULL COMMENT 'Peso maximo por fruto en gramos (ej: 450.00)',
    calibre         VARCHAR(45)     NOT NULL COMMENT 'Valor del calibre de la tabla calibres (ej: 10, 12, 32)',
    descripcion     VARCHAR(200)    DEFAULT NULL COMMENT 'Etiqueta descriptiva (ej: Rango Europa Calibre 32)',
    mercado         VARCHAR(50)     NOT NULL COMMENT 'USA o EUROPA (los rangos varían por mercado)',
    estado          ENUM('HABILITADO','DESHABILITADO') DEFAULT 'HABILITADO' COMMENT 'Estado activo/inactivo',
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_gramaje_empresa_calibre_mercado (min_gramaje, max_gramaje, empresa, calibre, mercado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE gramajes ADD INDEX idx_estado (estado);

-- ============================================================
-- Datos semilla: AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA
-- Fuente: Norma de Calidad.pdf — Tablas A (USA) y B (Europa)
-- Calibres reales (PALTA): 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32
-- Total: 12 Europa (Tabla B) + 1 USA (Tabla A, calibre 32) = 13 registros
-- ============================================================
INSERT INTO gramajes (min_gramaje, max_gramaje, empresa, calibre, descripcion, mercado, estado) VALUES
    -- Tabla A (USA)
    (333.00, 450.00, 'AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA', '32', 'Rango USA Calibre 32', 'USA', 'HABILITADO'),
    -- Tabla B (Europa, +3% deshidratación)
    (364.00, 462.00, 'AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA', '10', 'Rango Europa Calibre 10', 'EUROPA', 'HABILITADO'),
    (300.00, 371.00, 'AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA', '12', 'Rango Europa Calibre 12', 'EUROPA', 'HABILITADO'),
    (258.00, 313.00, 'AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA', '14', 'Rango Europa Calibre 14', 'EUROPA', 'HABILITADO'),
    (227.00, 274.00, 'AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA', '16', 'Rango Europa Calibre 16', 'EUROPA', 'HABILITADO'),
    (203.00, 243.00, 'AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA', '18', 'Rango Europa Calibre 18', 'EUROPA', 'HABILITADO'),
    (184.00, 217.00, 'AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA', '20', 'Rango Europa Calibre 20', 'EUROPA', 'HABILITADO'),
    (165.00, 196.00, 'AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA', '22', 'Rango Europa Calibre 22', 'EUROPA', 'HABILITADO'),
    (151.00, 175.00, 'AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA', '24', 'Rango Europa Calibre 24', 'EUROPA', 'HABILITADO'),
    (144.00, 157.00, 'AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA', '26', 'Rango Europa Calibre 26', 'EUROPA', 'HABILITADO'),
    (134.00, 147.00, 'AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA', '28', 'Rango Europa Calibre 28', 'EUROPA', 'HABILITADO'),
    (123.00, 137.00, 'AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA', '30', 'Rango Europa Calibre 30', 'EUROPA', 'HABILITADO'),
    ( 80.00, 130.00, 'AGRICOLA Y EXPORTADORA LOS MOLINOS LIMITADA', '32', 'Rango Europa Calibre 32', 'EUROPA', 'HABILITADO');
