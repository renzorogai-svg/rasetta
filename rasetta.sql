-- 20/08/2026 desde PC
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-08-2026 a las 21:25:48
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `rasetta`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `Id` int(11) NOT NULL,
  `ID cliente` varchar(20) NOT NULL DEFAULT 'vacio',
  `nombre` varchar(40) NOT NULL,
  `usuario` varchar(30) NOT NULL,
  `telefono` varchar(40) NOT NULL DEFAULT 'vacio',
  `direccion` varchar(200) NOT NULL DEFAULT 'vacio',
  `correo` varchar(150) NOT NULL DEFAULT 'vacio',
  `pedido` int(3) NOT NULL,
  `fecha` date NOT NULL,
  `producto` varchar(300) NOT NULL,
  `precio` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`Id`, `nombre`, `usuario`, `pedido`, `fecha`, `producto`, `precio`) VALUES
(3, 'Pedro Perez', '123456', 2, '2026-08-08', 'esmoquin', 2345),
(4, 'Carlos Luis', '654321', 1, '2026-08-07', '0', 0),
(17, 'Pedro Perez', '123456', 1, '2026-08-09', 'Tela | Articulo: PDA51-4912 | Muestrario: SS26 La Giacca Stagionale | Composicion: 50% WO 30% SE 20%LI | Peso: 260 | Rango: 3 | Pagina: 56(IH)', 0),
(21, 'Pedro Perez', '123456', 3, '2026-08-09', '2 piece Suit (Traje Dos Piezas) | Rango: 1 | Tipo: Dos botones | Precio: 8530', 8530),
(22, 'Pedro Perez', '123456', 3, '2026-08-09', 'Tuxedo (Esmoquin) | Rango: 1 | Precio: 9340', 9340),
(23, 'Pedro Perez', '123456', 3, '2026-08-09', 'Tuxedo Trousers (Esmoquin Pantalon) | Rango: 1 | Precio: 2580', 2580),
(24, 'Pedro Perez', '123456', 3, '2026-08-09', 'Tela | Articulo: P4B61-4100 | Muestrario: SS26 Il Guardaroba Ultimate- A | Composicion: 100% WO Super 180\'s | Peso: 230 | Rango: 8 | Pagina: 8', 0),
(25, 'Pedro Perez', '123456', 4, '2026-08-09', '2 piece Suit (Traje Dos Piezas) | Rango: 1 | Tipo: Dos botones | Precio: 8530', 8530),
(26, 'Pedro Perez', '123456', 4, '2026-08-09', 'Trousers (Pantalones) | Rango: 9 | Precio: 4600', 4600),
(27, 'Pedro Perez', '123456', 4, '2026-08-09', 'Tail Coat (Frac) | Rango: 5 | Precio: 10420', 10420),
(28, 'Pedro Perez', '123456', 4, '2026-08-09', 'Tela | Articulo: PDA51-4912 | Muestrario: SS26 La Giacca Stagionale | Composicion: 50% WO 30% SE 20%LI | Peso: 260 | Rango: 3 | Pagina: 56(IH)', 0),
(30, 'Nelson Bandera', '112233', 1, '2026-08-09', 'Tuxedo (Esmoquin) | Rango: 1 | Precio: 9340', 9340),
(31, 'Nelson Bandera', '112233', 1, '2026-08-09', 'Tuxedo Trousers (Esmoquin Pantalon) | Rango: 1 | Precio: 2580', 2580),
(32, 'Nelson Bandera', '112233', 1, '2026-08-09', 'Tela | Articulo: PZW02-2100 | Muestrario: Il Guardaroba Essential | Composicion: 100% WO Super 150\'s | Peso: 245 | Rango: 3 | Pagina: 9', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ropadia`
--

CREATE TABLE `ropadia` (
  `Id` int(11) NOT NULL,
  `producto` varchar(30) NOT NULL,
  `rango` int(3) NOT NULL,
  `unboton` int(11) NOT NULL,
  `dosbotones` int(11) NOT NULL,
  `especial` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ropadia`
--

INSERT INTO `ropadia` (`Id`, `producto`, `rango`, `unboton`, `dosbotones`, `especial`) VALUES
(1, 'traje dos piezas', 1, 8120, 8530, 9070),
(2, 'traje dos piezas', 2, 8800, 9340, 9880),
(3, 'traje dos piezas', 3, 9880, 10420, 11100),
(4, 'traje tres piezas', 1, 9740, 10550, 10820),
(5, 'traje tres piezas', 2, 10550, 11500, 11640),
(6, 'traje tres piezas', 3, 11910, 12860, 13130),
(7, 'chaqueta', 1, 6640, 7040, 7580),
(8, 'chaqueta', 2, 7310, 7720, 8390),
(9, 'chaqueta', 3, 7850, 8260, 8930),
(10, 'traje dos piezas', 4, 10420, 10960, 11640),
(11, 'traje dos piezas', 5, 10690, 11230, 11910),
(12, 'traje dos piezas', 6, 12050, 12720, 13400),
(13, 'traje dos piezas', 7, 12860, 13530, 14340),
(14, 'traje dos piezas', 8, 13400, 14070, 14880),
(15, 'traje dos piezas', 9, 15420, 16230, 17190),
(16, 'traje dos piezas', 10, 16230, 17050, 18130),
(17, 'traje dos piezas', 11, 16910, 17860, 18810),
(18, 'traje dos piezas', 12, 18270, 19210, 20290),
(19, 'traje dos piezas', 13, 23680, 24890, 26380),
(20, 'traje dos piezas', 14, 28410, 29900, 31650),
(21, 'traje dos piezas', 15, 33820, 35580, 37600),
(22, 'traje dos piezas', 16, 37870, 39770, 42070),
(23, 'traje dos piezas', 17, 44640, 46940, 49640),
(24, 'traje dos piezas', 18, 56810, 59650, 63170),
(25, 'traje dos piezas', 19, 70330, 73850, 78180),
(26, 'traje dos piezas', 20, 79800, 83860, 88600),
(27, 'traje dos piezas', 21, 89270, 93730, 99140),
(28, 'traje tres piezas', 4, 12590, 13670, 13800),
(29, 'traje tres piezas', 5, 12860, 13940, 14210),
(30, 'traje tres piezas', 6, 14480, 15690, 15960),
(31, 'traje tres piezas', 7, 15420, 16780, 17050),
(32, 'traje tres piezas', 8, 16100, 17460, 17730),
(33, 'traje tres piezas', 9, 18540, 20160, 20430),
(34, 'traje tres piezas', 10, 19480, 21100, 21510),
(35, 'traje tres piezas', 11, 20290, 22050, 22320),
(36, 'traje tres piezas', 12, 21910, 23810, 24220),
(37, 'traje tres piezas', 13, 28410, 30840, 31250),
(38, 'traje tres piezas', 14, 34090, 36930, 37600),
(39, 'traje tres piezas', 15, 40580, 43960, 44640),
(40, 'traje tres piezas', 16, 45450, 49230, 50050),
(41, 'traje tres piezas', 17, 53560, 58030, 58970),
(42, 'traje tres piezas', 18, 68170, 73850, 75070),
(43, 'traje tres piezas', 19, 84400, 91430, 92920),
(44, 'traje tres piezas', 20, 95760, 103740, 105370),
(45, 'traje tres piezas', 21, 107120, 116050, 117940),
(46, 'chaqueta', 4, 8120, 8530, 9200),
(47, 'chaqueta', 5, 8390, 8930, 9610),
(48, 'chaqueta', 6, 8800, 9340, 10010),
(49, 'chaqueta', 7, 9340, 9880, 10550),
(50, 'chaqueta', 8, 10150, 10690, 11500),
(51, 'chaqueta', 9, 10820, 11370, 12320),
(52, 'chaqueta', 10, 11500, 12180, 13130),
(53, 'chaqueta', 11, 12050, 12720, 13670),
(54, 'chaqueta', 12, 13400, 14070, 15150),
(55, 'chaqueta', 13, 15560, 16370, 17590),
(56, 'chaqueta', 14, 17590, 18540, 19890),
(57, 'chaqueta', 15, 20290, 21370, 23000),
(58, 'chaqueta', 16, 23000, 24220, 26110),
(59, 'chaqueta', 17, 25700, 27050, 29090),
(60, 'chaqueta', 18, 29760, 31250, 33690),
(61, 'chaqueta', 19, 36520, 38410, 41390),
(62, 'chaqueta', 20, 41930, 44100, 47480),
(63, 'chaqueta', 21, 48690, 51130, 55050);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sobretodo`
--

CREATE TABLE `sobretodo` (
  `Id` int(11) NOT NULL,
  `rango` int(2) NOT NULL,
  `categoria1` int(6) NOT NULL,
  `categoria2` int(6) NOT NULL,
  `categoria3` int(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sobretodo`
--

INSERT INTO `sobretodo` (`Id`, `rango`, `categoria1`, `categoria2`, `categoria3`) VALUES
(1, 1, 6640, 6910, 7450),
(2, 2, 6770, 7040, 7580),
(3, 3, 7310, 7720, 8260),
(4, 4, 7720, 8120, 8660),
(5, 5, 8120, 8530, 9200),
(6, 6, 8530, 8930, 9610),
(7, 7, 9070, 9470, 10280),
(8, 8, 9610, 10010, 10820),
(9, 9, 11370, 11910, 12860),
(10, 10, 12180, 12720, 13670),
(11, 11, 13130, 13670, 14750),
(12, 12, 14880, 15560, 16780),
(13, 13, 18270, 19080, 20560),
(14, 14, 21640, 22600, 24350),
(15, 15, 25700, 26780, 28820),
(16, 16, 31110, 32460, 34900),
(17, 17, 39230, 40850, 43960),
(18, 18, 47340, 49230, 53020),
(19, 19, 58160, 60600, 65190),
(20, 20, 66280, 68980, 74260),
(21, 21, 74390, 77370, 83320);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telas`
--

CREATE TABLE `telas` (
  `Id` int(11) NOT NULL,
  `articulo` varchar(20) NOT NULL,
  `muestrario` varchar(30) NOT NULL,
  `composicion` varchar(30) NOT NULL,
  `pero` int(5) NOT NULL,
  `rango` int(11) NOT NULL,
  `pagina` varchar(10) NOT NULL,
  `foto` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `telas`
--

INSERT INTO `telas` (`Id`, `articulo`, `muestrario`, `composicion`, `pero`, `rango`, `pagina`, `foto`) VALUES
(4, 'PZW02-1100', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '6', '1'),
(5, 'PZW02-1300', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '7', '2'),
(6, 'PZW02-2800', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '8', '3'),
(7, 'PZW02-2100', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '9', '4'),
(8, 'PZW03-4300', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '10', '5'),
(9, 'PZW03-4100', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '11', '6'),
(10, 'PZW03-1400', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '12', '7'),
(11, 'PZW03-1200', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '13', '8'),
(12, 'PZW04-4200', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '14', '9'),
(13, 'PZW04-4100', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '15', '10'),
(14, 'PZW04-1300', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '16', '11'),
(15, 'PZW04-1000', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '17', '12'),
(16, 'PDA70-4341', 'SS26 La Giacca Stagionale', '100% WV', 245, 2, '16(IH)', '13'),
(17, 'PDA51-4912', 'SS26 La Giacca Stagionale', '50% WO 30% SE 20%LI', 260, 3, '56(IH)', '14'),
(18, 'P4B38-1400', 'SS26 La Giacca Stagionale', '100% WV', 255, 6, '15', '15'),
(19, 'P4B39-4900', 'SS26 La Giacca Stagionale', '100% WO Super 150\'s', 270, 6, '17', '16'),
(20, 'P4B39-4700', 'SS26 La Giacca Stagionale', '100% WO Super 150\'s', 270, 6, '18', '17'),
(21, 'P4B40-6100', 'SS26 La Giacca Stagionale', '100% WO Super 150\'s', 270, 6, '19', '18'),
(22, 'PZW17-1300', 'Il Guardaroba Excellence', '100% WO Super 210\'s', 220, 13, '5', '19'),
(23, 'P4B61-4100', 'SS26 Il Guardaroba Ultimate- A', '100% WO Super 180\'s', 230, 8, '8', '20'),
(24, 'P4B63-4100', 'SS26 Il Guardaroba Ultimate- A', '100% WO Super 180\'s', 230, 8, '12', '21'),
(25, 'PZW19-4300', 'Il Guardaroba Excellence', '100% WO Super 200\'s', 255, 10, '9', '22'),
(26, 'P4B63-4200', 'SS26 Il Guardaroba Ultimate- A', '100% WO Super 180\'s', 230, 8, '11', '23'),
(27, 'PZW05-1300', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '19', '24'),
(28, 'PZW31-4100', 'Il Guardaroba Excellence', '95% WO 5% SE', 210, 8, '36', '25'),
(29, 'PZW18-4200', 'Il Guardaroba Excellence', '100%WO Super 210\'s', 220, 13, '7', '26'),
(30, 'PZW21-1400', 'Il Guardaroba Excellence', '100% WO Super 180\'s', 225, 8, '14', '27'),
(31, 'PZW01-4100', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '1', '28'),
(32, 'PZW01-1300', 'Il Guardaroba Essential', '100% WO Super 150\'s', 245, 3, '3', '29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unprecio`
--

CREATE TABLE `unprecio` (
  `Id` int(11) NOT NULL,
  `producto` varchar(30) NOT NULL,
  `rango` int(3) NOT NULL,
  `precio` int(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `unprecio`
--

INSERT INTO `unprecio` (`Id`, `producto`, `rango`, `precio`) VALUES
(1, 'pantalones', 1, 2100),
(2, 'pantalones', 2, 2240),
(3, 'chaleco', 1, 1900),
(4, 'chaleco', 2, 2040),
(5, 'esmoquin', 1, 9340),
(6, 'esmoquin', 2, 10010),
(7, 'esmoquin tres piezas', 1, 11500),
(8, 'esmoquin tres piezas', 2, 12320),
(9, 'esmoquin chaqueta', 1, 7580),
(10, 'esmoquin chaqueta', 2, 7990),
(11, 'esmoquin pantalon', 1, 2580),
(12, 'esmoquin pantalon ', 2, 2710),
(13, 'frac', 1, 8660),
(14, 'frac', 2, 9070),
(15, 'cache', 1, 8260),
(16, 'cache ', 2, 8800),
(17, 'pantalones', 3, 2500),
(18, 'pantalones', 4, 2850),
(19, 'pantalones', 5, 2910),
(20, 'pantalones', 6, 3190),
(21, 'pantalones', 7, 3660),
(22, 'pantalones', 8, 4000),
(23, 'pantalones', 9, 4600),
(24, 'pantalones', 10, 5010),
(25, 'pantalones', 11, 5690),
(26, 'pantalones', 12, 7450),
(27, 'pantalones', 13, 8800),
(28, 'pantalones', 14, 12050),
(29, 'pantalones', 15, 15150),
(30, 'pantalones', 16, 16910),
(31, 'pantalones', 17, 20290),
(32, 'pantalones', 18, 29760),
(33, 'pantalones', 19, 37870),
(34, 'pantalones', 20, 43280),
(35, 'pantalones', 21, 44640),
(36, 'chaleco', 3, 2170),
(37, 'chaleco', 4, 2310),
(38, 'chaleco', 5, 2440),
(39, 'chaleco', 6, 2580),
(40, 'chaleco', 7, 2710),
(41, 'chaleco', 8, 2850),
(42, 'chaleco', 9, 3120),
(43, 'chaleco', 10, 3250),
(44, 'chaleco', 11, 3790),
(45, 'chaleco', 12, 4470),
(46, 'chaleco', 13, 5140),
(47, 'chaleco', 14, 6370),
(48, 'chaleco', 15, 7450),
(49, 'chaleco', 16, 8390),
(50, 'chaleco', 17, 9740),
(51, 'chaleco', 18, 12450),
(52, 'chaleco', 19, 15150),
(53, 'chaleco', 20, 17460),
(54, 'chaleco', 21, 19480),
(55, 'esmoquin', 3, 10690),
(56, 'esmoquin', 4, 11370),
(57, 'esmoquin', 5, 12050),
(58, 'esmoquin', 6, 12860),
(59, 'esmoquin', 7, 14210),
(60, 'esmoquin', 8, 14610),
(61, 'esmoquin', 9, 15830),
(62, 'esmoquin', 10, 16910),
(63, 'esmoquin', 11, 18270),
(64, 'esmoquin', 12, 20160),
(65, 'esmoquin', 13, 25570),
(66, 'esmoquin', 14, 31790),
(67, 'esmoquin', 15, 37870),
(68, 'esmoquin', 16, 45320),
(69, 'esmoquin', 17, 55730),
(70, 'esmoquin', 18, 66960),
(71, 'esmoquin', 19, 75740),
(72, 'esmoquin', 20, 85210),
(73, 'esmoquin', 21, 94680),
(74, 'esmoquin tres piezas', 3, 13130),
(75, 'esmoquin tres piezas', 4, 13940),
(76, 'esmoquin tres piezas', 5, 14750),
(77, 'esmoquin tres piezas', 6, 15690),
(78, 'esmoquin tres piezas', 7, 17460),
(79, 'esmoquin tres piezas', 8, 17860),
(80, 'esmoquin tres piezas', 9, 19350),
(81, 'esmoquin tres piezas', 10, 20700),
(82, 'esmoquin tres piezas', 11, 22320),
(83, 'esmoquin tres piezas', 12, 24620),
(84, 'esmoquin tres piezas', 13, 31250),
(85, 'esmoquin tres piezas', 14, 38820),
(86, 'esmoquin tres piezas', 15, 46260),
(87, 'esmoquin tres piezas', 16, 55320),
(88, 'esmoquin tres piezas', 17, 68040),
(89, 'esmoquin tres piezas', 18, 81690),
(90, 'esmoquin tres piezas', 19, 92510),
(91, 'esmoquin tres piezas', 20, 104010),
(92, 'esmoquin tres piezas', 21, 115500),
(93, 'esmoquin chaqueta', 3, 8800),
(94, 'esmoquin chaqueta', 4, 9070),
(95, 'esmoquin chaqueta', 5, 9340),
(96, 'esmoquin chaqueta', 6, 9880),
(97, 'esmoquin chaqueta', 7, 10420),
(98, 'esmoquin chaqueta', 8, 10960),
(99, 'esmoquin chaqueta', 9, 11640),
(100, 'esmoquin chaqueta', 10, 12180),
(101, 'esmoquin chaqueta', 11, 13130),
(102, 'esmoquin chaqueta', 12, 14750),
(103, 'esmoquin chaqueta', 13, 16910),
(104, 'esmoquin chaqueta', 14, 19620),
(105, 'esmoquin chaqueta', 15, 22320),
(106, 'esmoquin chaqueta', 16, 25030),
(107, 'esmoquin chaqueta', 17, 28410),
(108, 'esmoquin chaqueta', 18, 32460),
(109, 'esmoquin chaqueta', 19, 39230),
(110, 'esmoquin chaqueta', 20, 45990),
(111, 'esmoquin chaqueta', 21, 52750),
(112, 'esmoquin pantalon', 3, 2980),
(113, 'esmoquin pantalon', 4, 3390),
(114, 'esmoquin pantalon', 5, 3660),
(115, 'esmoquin pantalon', 6, 3790),
(116, 'esmoquin pantalon', 7, 4200),
(117, 'esmoquin pantalon', 8, 4470),
(118, 'esmoquin pantalon', 9, 5140),
(119, 'esmoquin pantalon', 10, 5410),
(120, 'esmoquin pantalon', 11, 5960),
(121, 'esmoquin pantalon', 12, 7850),
(122, 'esmoquin pantalon', 13, 9340),
(123, 'esmoquin pantalon', 14, 12720),
(124, 'esmoquin pantalon', 15, 16500),
(125, 'esmoquin pantalon', 16, 18270),
(126, 'esmoquin pantalon', 17, 22050),
(127, 'esmoquin pantalon', 18, 32460),
(128, 'esmoquin pantalon', 19, 41930),
(129, 'esmoquin pantalon', 20, 47340),
(130, 'esmoquin pantalon', 21, 48690),
(131, 'frac', 3, 9610),
(132, 'frac', 4, 9880),
(133, 'frac', 5, 10420),
(134, 'frac', 6, 10960),
(135, 'frac', 7, 11780),
(136, 'frac', 8, 12590),
(137, 'frac', 9, 13260),
(138, 'frac', 10, 14210),
(139, 'frac', 11, 15830),
(140, 'frac', 12, 18940),
(141, 'frac', 13, 21640),
(142, 'frac', 14, 28410),
(143, 'frac', 15, 32460),
(144, 'frac', 16, 36520),
(145, 'frac', 17, 41930),
(146, 'frac', 18, 54100),
(147, 'frac', 19, 67630),
(148, 'frac', 20, 75740),
(149, 'frac', 21, 85210),
(150, 'cache', 3, 9340),
(151, 'cache', 4, 9610),
(152, 'cache', 5, 10150),
(153, 'cache', 6, 10690),
(154, 'cache', 7, 11500),
(155, 'cache', 8, 12180),
(156, 'cache', 9, 12860),
(157, 'cache', 10, 13800),
(158, 'cache', 11, 15560),
(159, 'cache', 12, 18810),
(160, 'cache', 13, 21510),
(161, 'cache', 14, 28410),
(162, 'cache', 15, 32460),
(163, 'cache', 16, 36520),
(164, 'cache', 17, 43280),
(165, 'cache', 18, 54100),
(166, 'cache', 19, 66280),
(167, 'cache', 20, 77100),
(168, 'cache', 21, 86560);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `ropadia`
--
ALTER TABLE `ropadia`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `sobretodo`
--
ALTER TABLE `sobretodo`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `telas`
--
ALTER TABLE `telas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `unprecio`
--
ALTER TABLE `unprecio`
  ADD PRIMARY KEY (`Id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `ropadia`
--
ALTER TABLE `ropadia`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT de la tabla `sobretodo`
--
ALTER TABLE `sobretodo`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `telas`
--
ALTER TABLE `telas`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `unprecio`
--
ALTER TABLE `unprecio`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
