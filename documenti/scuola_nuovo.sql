-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 06, 2026 at 12:09 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `scuola`
--

-- --------------------------------------------------------

--
-- Table structure for table `candidature`
--

CREATE TABLE `candidature` (
  `nome` varchar(100) NOT NULL,
  `cognome` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `ID_richiesta` int(11) NOT NULL,
  `data_iscrizione` date NOT NULL,
  `stato_iscrizione` set('"in_elaborazione"','"confermato"','"rifiutato"','') NOT NULL,
  `ID_corso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `corsi`
--

CREATE TABLE `corsi` (
  `ID_corso` int(11) NOT NULL,
  `cod_edizione` char(10) NOT NULL,
  `cod_corso` char(4) NOT NULL,
  `nome_corso` varchar(150) NOT NULL,
  `attivo` tinyint(1) NOT NULL,
  `accetta_iscrizioni` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `corsi`
--

INSERT INTO `corsi` (`ID_corso`, `cod_edizione`, `cod_corso`, `nome_corso`, `attivo`, `accetta_iscrizioni`) VALUES
(1, 'Aprile', 'C001', 'Operatore di Database', 1, 1),
(2, 'Gennaio', 'C002', 'Tecniche di Disegno e Progettazione Industriale', 0, 1),
(3, 'Novembre', 'C001', 'Operatore di Database', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `lezioni`
--

CREATE TABLE `lezioni` (
  `ID_lezione` int(11) NOT NULL,
  `TS_inizio` int(6) NOT NULL,
  `TS_fine` int(6) NOT NULL,
  `cod_aula` int(4) NOT NULL,
  `sede` int(50) NOT NULL,
  `cod_materia` char(4) NOT NULL,
  `ID_corso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `materie`
--

CREATE TABLE `materie` (
  `cod_materia` char(4) NOT NULL,
  `ID_corso` int(11) DEFAULT NULL,
  `nome_materia` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materie`
--

INSERT INTO `materie` (`cod_materia`, `ID_corso`, `nome_materia`) VALUES
('M001', 1, 'Database'),
('M002', NULL, 'Programmazione ad oggetti'),
('M003', NULL, 'Reti');

-- --------------------------------------------------------

--
-- Table structure for table `presenze`
--

CREATE TABLE `presenze` (
  `ID_lezione` int(11) NOT NULL,
  `matricola` char(8) NOT NULL,
  `ora_entrata` time NOT NULL,
  `ora_uscita` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `codice_staff` char(8) NOT NULL,
  `nome` varchar(250) NOT NULL,
  `cognome` varchar(250) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`codice_staff`, `nome`, `cognome`, `email`, `password`) VALUES
('ADM00001', 'Francesco', 'Riccardini', '', 0),
('ADM00002', 'Luigi', 'Bianchi', '', 0),
('ADM00003', 'Anna', 'Verdi', '', 0),
('ADM00004', 'Giulia', 'Ferrari', '', 0),
('ADM00005', 'Marco', 'Esposito', '', 0),
('ADM00006', 'Sara', 'Romano', '', 0),
('ADM00007', 'Luca', 'Conti', '', 0),
('ADM00008', 'Elena', 'Gallo', '', 0),
('ADM00009', 'Davide', 'Moretti', '', 0),
('ADM00010', 'Chiara', 'Ricci', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `studenti`
--

CREATE TABLE `studenti` (
  `matricola` char(8) NOT NULL,
  `nome` varchar(250) NOT NULL,
  `cognome` varchar(250) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` int(10) DEFAULT NULL,
  `ID_corso` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studenti`
--

INSERT INTO `studenti` (`matricola`, `nome`, `cognome`, `email`, `password`, `ID_corso`) VALUES
('10000001', 'Mario', 'Rossi', '0', 0, 1),
('10000002', 'Luigi', 'Verdi', '0', 0, 1),
('10000003', 'Giuseppe', 'Ferrari', '0', 0, 1),
('10000004', 'Anna', 'Esposito', '0', 0, 1),
('10000005', 'Giovanni', 'Bianchi', '0', 0, 1),
('10000006', 'Francesca', 'Romano', '0', 0, 1),
('10000007', 'Antonio', 'Colombo', '0', 0, 1),
('10000008', 'Maria', 'Ricci', '0', 0, 1),
('10000009', 'Alessandro', 'Marino', '0', 0, 1),
('10000010', 'Laura', 'Greco', '0', 0, 1),
('10000011', 'Francesco', 'Bruno', '0', 0, 1),
('10000012', 'Lucia', 'Gallo', '0', 0, 1),
('10000013', 'Andrea', 'Conti', '0', 0, 1),
('10000014', 'Stefano', 'De Luca', '0', 0, 1),
('10000015', 'Paolo', 'Costa', '0', 0, 1),
('10000016', 'Marta', 'Giordano', '0', 0, 1),
('10000017', 'Marco', 'Mancini', '0', 0, 1),
('10000018', 'Davide', 'Rizzo', '0', 0, 1),
('10000019', 'Elena', 'Moretti', '0', 0, 1),
('10000020', 'Roberto', 'Lombardi', '0', 0, 1),
('10000021', 'Simone', 'Barbieri', '0', 0, 1),
('10000022', 'Chiara', 'Fontana', '0', 0, 1),
('10000023', 'Silvia', 'Santoro', '0', 0, 1),
('10000024', 'Michele', 'Mariani', '0', 0, 1),
('10000025', 'Daniela', 'Russo', '0', 0, 1),
('10000026', 'Federico', 'De Rosa', '0', 0, 1),
('10000027', 'Giorgio', 'Farina', '0', 0, 1),
('10000028', 'Sara', 'Ranieri', '0', 0, 1),
('10000029', 'Matteo', 'Gatti', '0', 0, 1),
('10000030', 'Valentina', 'Sorrentino', '0', 0, 1),
('10000031', 'Claudio', 'Pellegrini', '0', 0, 1),
('10000032', 'Edoardo', 'Villa', '0', 0, 1),
('10000033', 'Gianluca', 'Vitali', '0', 0, 1),
('10000034', 'Emanuele', 'Serra', '0', 0, 1),
('10000035', 'Barbara', 'Palumbo', '0', 0, 1),
('10000036', 'Alessia', 'Messina', '0', 0, 1),
('10000037', 'Daniele', 'Monti', '0', 0, 1),
('10000038', 'Luca', 'Marchetti', '0', 0, 1),
('10000039', 'Giulia', 'Fabbri', '0', 0, 1),
('10000040', 'Christian', 'Guidi', '0', 0, 1),
('10000041', 'Martina', 'Fiorini', '0', 0, 1),
('10000042', 'Fabio', 'Caruso', '0', 0, 1),
('10000043', 'Alice', 'Villa', '0', 0, 1),
('10000044', 'Giacomo', 'Riva', '0', 0, 1),
('10000045', 'Filippo', 'Donati', '0', 0, 1),
('10000046', 'Tommaso', 'Piras', '0', 0, 1),
('10000047', 'Nicole', 'Pini', '0', 0, 1),
('10000048', 'Valerio', 'Benedetti', '0', 0, 1),
('10000049', 'Lorenzo', 'Valenti', '0', 0, 1),
('10000050', 'Beatrice', 'Sala', '0', 0, 1),
('10000051', 'Samuele', 'Bini', '0', 0, 1),
('10000052', 'Gabriele', 'Neri', '0', 0, 1),
('10000053', 'Roberta', 'Silvestri', '0', 0, 1),
('10000054', 'Domenico', 'Grassi', '0', 0, 1),
('10000055', 'Salvatore', 'Pellegrino', '0', 0, 1),
('10000056', 'Vincenzo', 'Carbone', '0', 0, 1),
('10000057', 'Raffaele', 'Bernardi', '0', 0, 1),
('10000058', 'Camilla', 'Rinaldi', '0', 0, 1),
('10000059', 'Manuel', 'Ferri', '0', 0, 1),
('10000060', 'Mirko', 'D-Amico', '0', 0, 1),
('10000061', 'Erika', 'Pari', '0', 0, 1),
('10000062', 'Cristian', 'Testa', '0', 0, 1),
('10000063', 'Veronica', 'Valente', '0', 0, 1),
('10000064', 'Fabrizio', 'Festa', '0', 0, 1),
('10000065', 'Alessio', 'Sanna', '0', 0, 1),
('10000066', 'Giancarlo', 'Guerra', '0', 0, 1),
('10000067', 'Manuela', 'Leone', '0', 0, 1),
('10000068', 'Patrizia', 'Longhi', '0', 0, 1),
('10000069', 'Pasquale', 'Coppola', '0', 0, 1),
('10000070', 'Cinzia', 'Mele', '0', 0, 1),
('10000071', 'Ivan', 'Basso', '0', 0, 1),
('10000072', 'Ilaria', 'Piazza', '0', 0, 1),
('10000073', 'Monica', 'Amato', '0', 0, 1),
('10000074', 'Gianni', 'Bellini', '0', 0, 1),
('10000075', 'Massimo', 'Beretta', '0', 0, 1),
('10000076', 'Enrico', 'Milani', '0', 0, 1),
('10000077', 'Nadia', 'Pagano', '0', 0, 1),
('10000078', 'Stefania', 'Martini', '0', 0, 1),
('10000079', 'Tiziana', 'Galli', '0', 0, 1),
('10000080', 'Renato', 'Mazza', '0', 0, 1),
('10000081', 'Attilio', 'Orlando', '0', 0, 1),
('10000082', 'Sergio', 'Negri', '0', 0, 1),
('10000083', 'Gino', 'Crippa', '0', 0, 1),
('10000084', 'Antonella', 'Carli', '0', 0, 1),
('10000085', 'Sabrina', 'Castelli', '0', 0, 1),
('10000086', 'Alberto', 'Gatti', '0', 0, 1),
('10000087', 'Luana', 'Rossetti', '0', 0, 1),
('10000088', 'Cristiano', 'Raimondi', '0', 0, 1),
('10000089', 'Gennaro', 'Giugliano', '0', 0, 1),
('10000090', 'Rossella', 'De Angelis', '0', 0, 1),
('10000091', 'Carmine', 'Napolitano', '0', 0, 1),
('10000092', 'Rocco', 'Barone', '0', 0, 1),
('10000093', 'Caterina', 'Cattaneo', '0', 0, 1),
('10000094', 'Diego', 'Molinari', '0', 0, 1),
('10000095', 'Walter', 'Franchi', '0', 0, 1),
('10000096', 'Cesare', 'Moretti', '0', 0, 1),
('10000097', 'Flavio', 'Longo', '0', 0, 1),
('10000098', 'Loredana', 'Costantini', '0', 0, 1),
('10000099', 'Annamaria', 'Belli', '0', 0, 1),
('10000100', 'Marcello', 'Pinto', '0', 0, 1),
('10000101', 'Dario', 'Conte', '0', 0, 2),
('10000102', 'Evelyn', 'Montanari', '0', 0, 2),
('10000103', 'Alex', 'Mazza', '0', 0, 2),
('10000104', 'Deborah', 'Guerini', '0', 0, 2),
('10000105', 'Gianfranco', 'Rizzi', '0', 0, 2),
('10000106', 'Adriana', 'Poli', '0', 0, 2),
('10000107', 'Igor', 'Marconi', '0', 0, 2),
('10000108', 'Tania', 'Berti', '0', 0, 2),
('10000109', 'Lucrezia', 'Basile', '0', 0, 2),
('10000110', 'Orazio', 'D-Angelo', '0', 0, 2),
('10000111', 'Zoe', 'Guglielmi', '0', 0, 2),
('10000112', 'Leonardo', 'Damico', '0', 0, 2),
('10000113', 'Gemma', 'Sartori', '0', 0, 2),
('10000114', 'Nicoletta', 'Pellegrini', '0', 0, 2),
('10000115', 'Orlando', 'Gatti', '0', 0, 2),
('10000116', 'Pietro', 'Vecchi', '0', 0, 2),
('10000117', 'Agata', 'Bona', '0', 0, 2),
('10000118', 'Denis', 'Valli', '0', 0, 2),
('10000119', 'Rinaldo', 'Santi', '0', 0, 2),
('10000120', 'Isabella', 'Martelli', '0', 0, 2),
('10000121', 'Eugenio', 'Marra', '0', 0, 2),
('10000122', 'Marianna', 'Nobile', '0', 0, 2),
('10000123', 'Aurelio', 'Guerra', '0', 0, 2),
('10000124', 'Livia', 'Russo', '0', 0, 2),
('10000125', 'Nino', 'Castiglione', '0', 0, 2),
('10000126', 'Alfonso', 'Lombardi', '0', 0, 2),
('10000127', 'Letizia', 'Pugliese', '0', 0, 2),
('10000128', 'Ezio', 'Giuliani', '0', 0, 2),
('10000129', 'Stella', 'Marchetti', '0', 0, 2),
('10000130', 'Tobia', 'Rinaldi', '0', 0, 2),
('10000131', 'Lodovico', 'Vitali', '0', 0, 2),
('10000132', 'Mirella', 'Neri', '0', 0, 2),
('10000133', 'Guido', 'Grossi', '0', 0, 2),
('10000134', 'Angelica', 'Piras', '0', 0, 2),
('10000135', 'Vittorio', 'D-Orazio', '0', 0, 2),
('10000136', 'Eleonora', 'Gentile', '0', 0, 2),
('10000137', 'Damiano', 'Amato', '0', 0, 2),
('10000138', 'Miriam', 'Fiore', '0', 0, 2),
('10000139', 'Moreno', 'Mancini', '0', 0, 2),
('10000140', 'Clara', 'Pagano', '0', 0, 2),
('10000141', 'Benedetta', 'Gallo', '0', 0, 2),
('10000142', 'Arturo', 'Ferraro', '0', 0, 2),
('10000143', 'Carla', 'Barbieri', '0', 0, 2),
('10000144', 'Romeo', 'Riva', '0', 0, 2),
('10000145', 'Luisa', 'Donati', '0', 0, 2),
('10000146', 'Gastone', 'Poli', '0', 0, 2),
('10000147', 'Sonia', 'Betti', '0', 0, 2),
('10000148', 'Boris', 'Marini', '0', 0, 2),
('10000149', 'Dino', 'Sala', '0', 0, 2),
('10000150', 'Lara', 'Fabbri', '0', 0, 2),
('10000151', 'Marica', 'Guerri', '0', 0, 2),
('10000152', 'Bruno', 'Fonti', '0', 0, 2),
('10000153', 'Rina', 'Zani', '0', 0, 2),
('10000154', 'Aldo', 'Cesari', '0', 0, 2),
('10000155', 'Silvano', 'Meli', '0', 0, 2),
('10000156', 'Gisella', 'Caporali', '0', 0, 2),
('10000157', 'Terenzio', 'Ruggiero', '0', 0, 2),
('10000158', 'Emilia', 'Abate', '0', 0, 2),
('10000159', 'Gualtiero', 'Sanna', '0', 0, 2),
('10000160', 'Sandro', 'De Santis', '0', 0, 2),
('10000161', 'Anita', 'Basso', '0', 0, 2),
('10000162', 'Maurizio', 'Piazza', '0', 0, 2),
('10000163', 'Liliana', 'Coppola', '0', 0, 2),
('10000164', 'Manlio', 'Trotta', '0', 0, 2),
('10000165', 'Milena', 'Sanna', '0', 0, 2),
('10000166', 'Ugo', 'Palumbo', '0', 0, 2),
('10000167', 'Ada', 'Grassi', '0', 0, 2),
('10000168', 'Fedele', 'Parisi', '0', 0, 2),
('10000169', 'Glauco', 'Caputo', '0', 0, 2),
('10000170', 'Nilde', 'Ruggiero', '0', 0, 2),
('10000171', 'Amedeo', 'Ferrara', '0', 0, 2),
('10000172', 'Gemma', 'De Angelis', '0', 0, 2),
('10000173', 'Livio', 'Santoro', '0', 0, 2),
('10000174', 'Nico', 'Marchetti', '0', 0, 2),
('10000175', 'Aurora', 'Caruso', '0', 0, 2),
('10000176', 'Ivano', 'Leone', '0', 0, 2),
('10000177', 'Gilda', 'Galli', '0', 0, 2),
('10000178', 'Duccio', 'Guerra', '0', 0, 2),
('10000179', 'Alba', 'Barone', '0', 0, 2),
('10000180', 'Elio', 'Vincenzi', '0', 0, 2),
('10000181', 'Miriana', 'Pellegrini', '0', 0, 2),
('10000182', 'Guglielmo', 'Marra', '0', 0, 2),
('10000183', 'Viola', 'Ferri', '0', 0, 2),
('10000184', 'Ciro', 'Esposito', '0', 0, 2),
('10000185', 'Rossana', 'Rinaldi', '0', 0, 2),
('10000186', 'Ermanno', 'Molinari', '0', 0, 2),
('10000187', 'Flora', 'Monti', '0', 0, 2),
('10000188', 'Alvise', 'Riva', '0', 0, 2),
('10000189', 'Tiziano', 'Donati', '0', 0, 2),
('10000190', 'Elda', 'Grassi', '0', 0, 2),
('10000191', 'Saverio', 'Pellegrino', '0', 0, 2),
('10000192', 'Lina', 'Carbone', '0', 0, 2),
('10000193', 'Adolfo', 'Bernardi', '0', 0, 2),
('10000194', 'Nesta', 'Rinaldi', '0', 0, 2),
('10000195', 'Vito', 'Ferri', '0', 0, 2),
('10000196', 'Nello', 'D-Amico', '0', 0, 2),
('10000197', 'Olga', 'Pari', '0', 0, 2),
('10000198', 'Elia', 'Testa', '0', 0, 2),
('10000199', 'Asia', 'Valente', '0', 0, 2),
('10000200', 'Remo', 'Festa', '0', 0, 2),
('10000201', 'Dante', 'Alighieri', '0', 0, 3),
('10000202', 'Beatrice', 'Portinari', '0', 0, 3),
('10000203', 'Francesco', 'Petrarca', '0', 0, 3),
('10000204', 'Giovanni', 'Boccaccio', '0', 0, 3),
('10000205', 'Giacomo', 'Leopardi', '0', 0, 3),
('10000206', 'Alessandro', 'Manzoni', '0', 0, 3),
('10000207', 'Ugo', 'Foscolo', '0', 0, 3),
('10000208', 'Ludovico', 'Ariosto', '0', 0, 3),
('10000209', 'Torquato', 'Tasso', '0', 0, 3),
('10000210', 'Niccolò', 'Machiavelli', '0', 0, 3),
('10000211', 'Galileo', 'Galilei', '0', 0, 3),
('10000212', 'Leonardo', 'Da Vinci', '0', 0, 3),
('10000213', 'Michelangelo', 'Buonarroti', '0', 0, 3),
('10000214', 'Raffaello', 'Sanzio', '0', 0, 3),
('10000215', 'Donatello', 'Bardi', '0', 0, 3),
('10000216', 'Sandro', 'Botticelli', '0', 0, 3),
('10000217', 'Giotto', 'Bondone', '0', 0, 3),
('10000218', 'Caravaggio', 'Merisi', '0', 0, 3),
('10000219', 'Filippo', 'Brunelleschi', '0', 0, 3),
('10000220', 'Donato', 'Bramante', '0', 0, 3),
('10000221', 'Guglielmo', 'Marconi', '0', 0, 3),
('10000222', 'Alessandro', 'Volta', '0', 0, 3),
('10000223', 'Enrico', 'Fermi', '0', 0, 3),
('10000224', 'Rita', 'Levi-Montalcini', '0', 0, 3),
('10000225', 'Evangelista', 'Torricelli', '0', 0, 3),
('10000226', 'Amedeo', 'Avogadro', '0', 0, 3),
('10000227', 'Camillo', 'Benso', '0', 0, 3),
('10000228', 'Giuseppe', 'Garibaldi', '0', 0, 3),
('10000229', 'Giuseppe', 'Mazzini', '0', 0, 3),
('10000230', 'Vittorio', 'Emanuele', '0', 0, 3),
('10000231', 'Marco', 'Polo', '0', 0, 3),
('10000232', 'Cristoforo', 'Colombo', '0', 0, 3),
('10000233', 'Amerigo', 'Vespucci', '0', 0, 3),
('10000234', 'Giovanni', 'Caboto', '0', 0, 3),
('10000235', 'Antonio', 'Meucci', '0', 0, 3),
('10000236', 'Grazia', 'Deledda', '0', 0, 3),
('10000237', 'Luigi', 'Pirandello', '0', 0, 3),
('10000238', 'Giosuè', 'Carducci', '0', 0, 3),
('10000239', 'Eugenio', 'Montale', '0', 0, 3),
('10000240', 'Salvatore', 'Quasimodo', '0', 0, 3),
('10000241', 'Dario', 'Fo', '0', 0, 3),
('10000242', 'Giacomo', 'Puccini', '0', 0, 3),
('10000243', 'Giuseppe', 'Verdi', '0', 0, 3),
('10000244', 'Antonio', 'Vivaldi', '0', 0, 3),
('10000245', 'Gioachino', 'Rossini', '0', 0, 3),
('10000246', 'Gaetano', 'Donizetti', '0', 0, 3),
('10000247', 'Vincenzo', 'Bellini', '0', 0, 3),
('10000248', 'Claudio', 'Monteverdi', '0', 0, 3),
('10000249', 'Ennio', 'Morricone', '0', 0, 3),
('10000250', 'Nino', 'Rota', '0', 0, 3),
('10000251', 'Federico', 'Fellini', '0', 0, 3),
('10000252', 'Vittorio', 'De Sica', '0', 0, 3),
('10000253', 'Roberto', 'Rossellini', '0', 0, 3),
('10000254', 'Luchino', 'Visconti', '0', 0, 3),
('10000255', 'Pier', 'Pasolini', '0', 0, 3),
('10000256', 'Michelangelo', 'Antonioni', '0', 0, 3),
('10000257', 'Bernardo', 'Bertolucci', '0', 0, 3),
('10000258', 'Sergio', 'Leone', '0', 0, 3),
('10000259', 'Roberto', 'Benigni', '0', 0, 3),
('10000260', 'Sophia', 'Loren', '0', 0, 3),
('10000261', 'Marcello', 'Mastroianni', '0', 0, 3),
('10000262', 'Vittorio', 'Gassman', '0', 0, 3),
('10000263', 'Alberto', 'Sordi', '0', 0, 3),
('10000264', 'Totò', 'De Curtis', '0', 0, 3),
('10000265', 'Anna', 'Magnani', '0', 0, 3),
('10000266', 'Gina', 'Lollobrigida', '0', 0, 3),
('10000267', 'Monica', 'Vitti', '0', 0, 3),
('10000268', 'Giancarlo', 'Giannini', '0', 0, 3),
('10000269', 'Ugo', 'Tognazzi', '0', 0, 3),
('10000270', 'Nanni', 'Moretti', '0', 0, 3),
('10000271', 'Primo', 'Levi', '0', 0, 3),
('10000272', 'Italo', 'Calvino', '0', 0, 3),
('10000273', 'Umberto', 'Eco', '0', 0, 3),
('10000274', 'Piergiorgio', 'Odifreddi', '0', 0, 3),
('10000275', 'Carlo', 'Rovelli', '0', 0, 3),
('10000276', 'Margherita', 'Hack', '0', 0, 3),
('10000277', 'Samantha', 'Cristoforetti', '0', 0, 3),
('10000278', 'Luca', 'Parmitano', '0', 0, 3),
('10000279', 'Walter', 'Bonatti', '0', 0, 3),
('10000280', 'Reinhold', 'Messner', '0', 0, 3),
('10000281', 'Fausto', 'Coppi', '0', 0, 3),
('10000282', 'Gino', 'Bartali', '0', 0, 3),
('10000283', 'Marco', 'Pantani', '0', 0, 3),
('10000284', 'Valentino', 'Rossi', '0', 0, 3),
('10000285', 'Giacomo', 'Agostini', '0', 0, 3),
('10000286', 'Ayrton', 'Senna', '0', 0, 3),
('10000287', 'Alberto', 'Ascari', '0', 0, 3),
('10000288', 'Enzo', 'Ferrari', '0', 0, 3),
('10000289', 'Ferruccio', 'Lamborghini', '0', 0, 3),
('10000290', 'Giovanni', 'Agnelli', '0', 0, 3),
('10000291', 'Giorgio', 'Armani', '0', 0, 3),
('10000292', 'Gianni', 'Versace', '0', 0, 3),
('10000293', 'Miuccia', 'Prada', '0', 0, 3),
('10000294', 'Valentino', 'Garavani', '0', 0, 3),
('10000295', 'Renzo', 'Piano', '0', 0, 3),
('10000296', 'Gualtiero', 'Marchesi', '0', 0, 3),
('10000297', 'Massimo', 'Bottura', '0', 0, 3),
('10000298', 'Carlo', 'Cracco', '0', 0, 3),
('10000299', 'Antonino', 'Cannavacciuolo', '0', 0, 3),
('10000300', 'Bruno', 'Barbieri', '0', 0, 3),
('43000560', 'Paolino', 'Paperino', '0', 0, 3),
('50288126', 'Topolino', 'Mouse', '0', 0, 3),
('63245826', 'Goffy', 'Pippo', '0', 0, 3);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vista_registro_presenze`
--
CREATE TABLE `vista_registro_presenze` (
  `matricola` char(8),
  `cognome` varchar(250),
  `nome` varchar(250),
  `nome_materia` varchar(100),
  `TS_inizio` int(6),
  `ora_entrata` time,
  `ora_uscita` time
);

-- --------------------------------------------------------

--
-- Structure for view `vista_registro_presenze`
--
DROP TABLE IF EXISTS `vista_registro_presenze`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_registro_presenze` AS 
SELECT `studenti`.`matricola` AS `matricola`, `studenti`.`cognome` AS `cognome`, `studenti`.`nome` AS `nome`, `materie`.`nome_materia` AS `nome_materia`, `lezioni`.`TS_inizio` AS `TS_inizio`, `presenze`.`ora_entrata` AS `ora_entrata`, `presenze`.`ora_uscita` AS `ora_uscita` 
FROM (((`presenze` 
JOIN `lezioni` ON(`lezioni`.`ID_lezione` = `presenze`.`ID_lezione`)) 
JOIN `materie` ON(`lezioni`.`cod_materia` = `materie`.`cod_materia`)) 
JOIN `studenti` ON(`studenti`.`matricola` = `presenze`.`matricola`));

--
-- Indexes for dumped tables
--

--
-- Indexes for table `candidature`
--
ALTER TABLE `candidature`
  ADD PRIMARY KEY (`ID_richiesta`),
  ADD KEY `fk_registrazioni_corsi` (`ID_corso`);

--
-- Indexes for table `corsi`
--
ALTER TABLE `corsi`
  ADD PRIMARY KEY (`ID_corso`),
  ADD UNIQUE KEY `uk_corso_edizione` (`cod_edizione`,`cod_corso`);

--
-- Indexes for table `lezioni`
--
ALTER TABLE `lezioni`
  ADD PRIMARY KEY (`ID_lezione`),
  ADD KEY `fk_lezioni_materie` (`cod_materia`),
  ADD KEY `fk_lezioni_corsi` (`ID_corso`);

--
-- Indexes for table `materie`
--
ALTER TABLE `materie`
  ADD PRIMARY KEY (`cod_materia`),
  ADD KEY `fk_materie_corsi` (`ID_corso`);

--
-- Indexes for table `presenze`
--
ALTER TABLE `presenze`
  ADD PRIMARY KEY (`ID_lezione`,`matricola`),
  ADD KEY `fk_presenze_studenti` (`matricola`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`codice_staff`);

--
-- Indexes for table `studenti`
--
ALTER TABLE `studenti`
  ADD PRIMARY KEY (`matricola`),
  ADD KEY `fk_studente_corso` (`ID_corso`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `candidature`
--
ALTER TABLE `candidature`
  MODIFY `ID_richiesta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `corsi`
--
ALTER TABLE `corsi`
  MODIFY `ID_corso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lezioni`
--
ALTER TABLE `lezioni`
  MODIFY `ID_lezione` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `candidature`
--
ALTER TABLE `candidature`
  ADD CONSTRAINT `fk_registrazioni_corsi` FOREIGN KEY (`ID_corso`) REFERENCES `corsi` (`ID_corso`) ON UPDATE CASCADE;

--
-- Constraints for table `lezioni`
--
ALTER TABLE `lezioni`
  ADD CONSTRAINT `fk_lezioni_corsi` FOREIGN KEY (`ID_corso`) REFERENCES `corsi` (`ID_corso`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lezioni_materie` FOREIGN KEY (`cod_materia`) REFERENCES `materie` (`cod_materia`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `materie`
--
ALTER TABLE `materie`
  ADD CONSTRAINT `fk_materie_corsi` FOREIGN KEY (`ID_corso`) REFERENCES `corsi` (`ID_corso`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `presenze`
--
ALTER TABLE `presenze`
  ADD CONSTRAINT `fk_presenze_lezioni` FOREIGN KEY (`ID_lezione`) REFERENCES `lezioni` (`ID_lezione`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presenze_studenti` FOREIGN KEY (`matricola`) REFERENCES `studenti` (`matricola`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `studenti`
--
ALTER TABLE `studenti`
  ADD CONSTRAINT `fk_studente_corso` FOREIGN KEY (`ID_corso`) REFERENCES `corsi` (`ID_corso`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;