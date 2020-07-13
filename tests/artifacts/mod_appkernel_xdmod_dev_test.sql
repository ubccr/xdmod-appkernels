-- MySQL dump 10.13  Distrib 5.7.30, for Linux (x86_64)
--
-- Host: localhost    Database: mod_appkernel_xdmod_dev
-- ------------------------------------------------------
-- Server version	5.7.30-0ubuntu0.18.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `a_data`
--

DROP TABLE IF EXISTS `a_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `a_data` (
  `ak_name` varchar(64) NOT NULL COMMENT '		',
  `resource` varchar(128) NOT NULL,
  `metric` varchar(128) NOT NULL,
  `num_units` int(10) unsigned NOT NULL DEFAULT '1',
  `processor_unit` enum('node','core') DEFAULT NULL,
  `collected` int(10) NOT NULL DEFAULT '0',
  `env_version` varchar(64) DEFAULT NULL,
  `unit` varchar(32) DEFAULT NULL,
  `metric_value` varchar(255) DEFAULT NULL,
  `ak_def_id` int(10) unsigned NOT NULL DEFAULT '0',
  `resource_id` int(10) unsigned NOT NULL DEFAULT '0',
  `metric_id` int(10) unsigned NOT NULL DEFAULT '0',
  `status` enum('success','failure','error','queued') DEFAULT NULL,
  KEY `ak_def_id` (`ak_def_id`,`resource_id`,`metric_id`,`num_units`),
  KEY `ak_name` (`ak_name`,`resource`,`metric`,`num_units`),
  KEY `resource_id` (`resource_id`),
  KEY `metric_id` (`metric_id`),
  KEY `num_units` (`num_units`),
  KEY `env_version` (`env_version`),
  KEY `collected` (`collected`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `a_data`
--

LOCK TABLES `a_data` WRITE;
/*!40000 ALTER TABLE `a_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `a_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `a_data2`
--

DROP TABLE IF EXISTS `a_data2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `a_data2` (
  `ak_name` varchar(64) NOT NULL COMMENT '		',
  `resource` varchar(128) NOT NULL,
  `metric` varchar(128) NOT NULL,
  `num_units` int(10) unsigned NOT NULL DEFAULT '1',
  `processor_unit` enum('node','core') DEFAULT NULL,
  `collected` int(10) NOT NULL DEFAULT '0',
  `env_version` varchar(64) DEFAULT NULL,
  `unit` varchar(32) DEFAULT NULL,
  `metric_value` varchar(255) DEFAULT NULL,
  `running_average` double DEFAULT NULL,
  `control` double DEFAULT NULL,
  `controlStart` double DEFAULT NULL,
  `controlEnd` double DEFAULT NULL,
  `controlMin` double DEFAULT NULL,
  `controlMax` double DEFAULT NULL,
  `ak_def_id` int(10) unsigned NOT NULL DEFAULT '0',
  `resource_id` int(10) unsigned NOT NULL DEFAULT '0',
  `metric_id` int(10) unsigned NOT NULL DEFAULT '0',
  `status` enum('success','failure','error','queued') DEFAULT NULL,
  `controlStatus` enum('undefined','control_region_time_interval','in_contol','under_performing','over_performing','failed') DEFAULT NULL,
  KEY `ak_def_id` (`ak_def_id`,`resource_id`,`metric_id`,`num_units`),
  KEY `ak_name` (`ak_name`,`resource`,`metric`,`num_units`),
  KEY `ak_collected` (`ak_def_id`,`collected`,`status`),
  KEY `resource_id` (`resource_id`),
  KEY `metric_id` (`metric_id`),
  KEY `num_units` (`num_units`),
  KEY `env_version` (`env_version`),
  KEY `collected` (`collected`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `a_data2`
--

LOCK TABLES `a_data2` WRITE;
/*!40000 ALTER TABLE `a_data2` DISABLE KEYS */;
/*!40000 ALTER TABLE `a_data2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `a_tree`
--

DROP TABLE IF EXISTS `a_tree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `a_tree` (
  `ak_name` varchar(64) NOT NULL COMMENT '		',
  `resource` varchar(128) NOT NULL,
  `metric` varchar(128) NOT NULL,
  `unit` varchar(32) DEFAULT NULL,
  `processor_unit` enum('node','core') DEFAULT NULL,
  `num_units` int(10) unsigned NOT NULL DEFAULT '1',
  `ak_def_id` int(10) unsigned NOT NULL DEFAULT '0',
  `resource_id` int(10) unsigned NOT NULL,
  `metric_id` int(10) unsigned NOT NULL DEFAULT '0',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('success','failure','error','queued') DEFAULT NULL,
  KEY `ak_def_id` (`ak_def_id`,`resource_id`,`metric_id`,`num_units`),
  KEY `resource_id` (`resource_id`),
  KEY `start_time` (`start_time`),
  KEY `end_time` (`end_time`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `a_tree`
--

LOCK TABLES `a_tree` WRITE;
/*!40000 ALTER TABLE `a_tree` DISABLE KEYS */;
/*!40000 ALTER TABLE `a_tree` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `a_tree2`
--

DROP TABLE IF EXISTS `a_tree2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `a_tree2` (
  `ak_name` varchar(64) NOT NULL COMMENT '		',
  `resource` varchar(128) NOT NULL,
  `metric` varchar(128) NOT NULL,
  `unit` varchar(32) DEFAULT NULL,
  `processor_unit` enum('node','core') DEFAULT NULL,
  `num_units` int(10) unsigned NOT NULL DEFAULT '1',
  `ak_def_id` int(10) unsigned NOT NULL DEFAULT '0',
  `resource_id` int(10) unsigned NOT NULL,
  `metric_id` int(10) unsigned NOT NULL DEFAULT '0',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('success','failure','error','queued') DEFAULT NULL,
  KEY `ak_def_id` (`ak_def_id`,`resource_id`,`metric_id`,`num_units`),
  KEY `resource_id` (`resource_id`),
  KEY `start_time` (`start_time`),
  KEY `end_time` (`end_time`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `a_tree2`
--

LOCK TABLES `a_tree2` WRITE;
/*!40000 ALTER TABLE `a_tree2` DISABLE KEYS */;
/*!40000 ALTER TABLE `a_tree2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ak_has_metric`
--

DROP TABLE IF EXISTS `ak_has_metric`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ak_has_metric` (
  `ak_id` int(10) unsigned NOT NULL,
  `metric_id` int(10) unsigned NOT NULL,
  `num_units` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ak_id`,`metric_id`,`num_units`),
  KEY `fk_reporter_has_metric_metric` (`metric_id`),
  KEY `fk_reporter_has_metric_reporter` (`ak_id`,`num_units`),
  CONSTRAINT `ak_has_metric_ibfk_1` FOREIGN KEY (`ak_id`) REFERENCES `app_kernel` (`ak_id`),
  CONSTRAINT `fk_reporter_has_metric_metric` FOREIGN KEY (`metric_id`) REFERENCES `metric` (`metric_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_reporter_has_metric_reporter` FOREIGN KEY (`ak_id`, `num_units`) REFERENCES `app_kernel` (`ak_id`, `num_units`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Association between app kernels and metrics';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ak_has_metric`
--

LOCK TABLES `ak_has_metric` WRITE;
/*!40000 ALTER TABLE `ak_has_metric` DISABLE KEYS */;
/*!40000 ALTER TABLE `ak_has_metric` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ak_has_parameter`
--

DROP TABLE IF EXISTS `ak_has_parameter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ak_has_parameter` (
  `ak_id` int(10) unsigned NOT NULL,
  `parameter_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ak_id`,`parameter_id`),
  KEY `fk_reporter_has_parameter_parameter` (`parameter_id`),
  KEY `fk_reporter_has_parameter_reporter` (`ak_id`),
  CONSTRAINT `fk_reporter_has_parameter_parameter` FOREIGN KEY (`parameter_id`) REFERENCES `parameter` (`parameter_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_reporter_has_parameter_reporter` FOREIGN KEY (`ak_id`) REFERENCES `app_kernel` (`ak_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Association between app kernels and parameters';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ak_has_parameter`
--

LOCK TABLES `ak_has_parameter` WRITE;
/*!40000 ALTER TABLE `ak_has_parameter` DISABLE KEYS */;
/*!40000 ALTER TABLE `ak_has_parameter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ak_instance`
--

DROP TABLE IF EXISTS `ak_instance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ak_instance` (
  `ak_id` int(10) unsigned NOT NULL COMMENT '	',
  `collected` datetime NOT NULL,
  `resource_id` int(10) unsigned NOT NULL,
  `instance_id` int(11) DEFAULT NULL,
  `job_id` varchar(32) DEFAULT NULL COMMENT 'resource mgr job id',
  `status` enum('success','failure','error','queued') DEFAULT NULL,
  `ak_def_id` int(10) unsigned NOT NULL,
  `env_version` varchar(64) DEFAULT NULL,
  `controlStatus` enum('undefined','control_region_time_interval','in_contol','under_performing','over_performing','failed') NOT NULL DEFAULT 'undefined',
  PRIMARY KEY (`ak_id`,`collected`,`resource_id`),
  KEY `fk_reporter_instance_reporter` (`ak_id`),
  KEY `fk_reporter_instance_resource` (`resource_id`),
  KEY `ak_def_id` (`ak_def_id`,`collected`,`resource_id`,`env_version`),
  KEY `instance_id` (`instance_id`),
  CONSTRAINT `fk_reporter_instance_reporter` FOREIGN KEY (`ak_id`) REFERENCES `app_kernel` (`ak_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_reporter_instance_resource` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Execution instance';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ak_instance`
--

LOCK TABLES `ak_instance` WRITE;
/*!40000 ALTER TABLE `ak_instance` DISABLE KEYS */;
/*!40000 ALTER TABLE `ak_instance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ak_instance_debug`
--

DROP TABLE IF EXISTS `ak_instance_debug`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ak_instance_debug` (
  `ak_id` int(10) unsigned NOT NULL,
  `collected` datetime NOT NULL,
  `resource_id` int(10) unsigned NOT NULL,
  `instance_id` int(11) DEFAULT NULL,
  `message` blob,
  `stderr` blob,
  `walltime` float DEFAULT NULL,
  `cputime` float DEFAULT NULL,
  `memory` float DEFAULT NULL,
  `ak_error_cause` blob,
  `ak_error_message` blob,
  `ak_queue_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`ak_id`,`collected`,`resource_id`),
  KEY `instance_id` (`instance_id`),
  CONSTRAINT `fk_ak_debug_ak_instance1` FOREIGN KEY (`ak_id`, `collected`, `resource_id`) REFERENCES `ak_instance` (`ak_id`, `collected`, `resource_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Debugging information for application kernels.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ak_instance_debug`
--

LOCK TABLES `ak_instance_debug` WRITE;
/*!40000 ALTER TABLE `ak_instance_debug` DISABLE KEYS */;
/*!40000 ALTER TABLE `ak_instance_debug` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ak_supremm_metrics`
--

DROP TABLE IF EXISTS `ak_supremm_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ak_supremm_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ak_def_id` int(11) NOT NULL,
  `supremm_metric_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ak_supremm_metrics`
--

LOCK TABLES `ak_supremm_metrics` WRITE;
/*!40000 ALTER TABLE `ak_supremm_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `ak_supremm_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_kernel`
--

DROP TABLE IF EXISTS `app_kernel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_kernel` (
  `ak_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `num_units` int(10) unsigned NOT NULL DEFAULT '1',
  `ak_def_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(128) NOT NULL,
  `type` varchar(64) DEFAULT NULL,
  `parser` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`ak_id`,`num_units`),
  UNIQUE KEY `index_unique` (`num_units`,`ak_def_id`),
  KEY `fk_reporter_app_kernel` (`ak_def_id`),
  CONSTRAINT `fk_reporter_app_kernel` FOREIGN KEY (`ak_def_id`) REFERENCES `app_kernel_def` (`ak_def_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=latin1 COMMENT='Application kernel info including num processing units';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_kernel`
--

LOCK TABLES `app_kernel` WRITE;
/*!40000 ALTER TABLE `app_kernel` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_kernel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_kernel_def`
--

DROP TABLE IF EXISTS `app_kernel_def`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_kernel_def` (
  `ak_def_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL COMMENT '		',
  `ak_base_name` varchar(128) NOT NULL,
  `processor_unit` enum('node','core') DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `description` text,
  `visible` tinyint(1) NOT NULL,
  `control_criteria` double DEFAULT NULL,
  PRIMARY KEY (`ak_def_id`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  UNIQUE KEY `reporter_base` (`ak_base_name`),
  KEY `visible` (`visible`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=latin1 COMMENT='App kernel definition.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_kernel_def`
--

LOCK TABLES `app_kernel_def` WRITE;
/*!40000 ALTER TABLE `app_kernel_def` DISABLE KEYS */;
INSERT INTO `app_kernel_def` VALUES (1,'Amber (cores)','amber.core','core',1,'<a href=\"http://ambermd.org\" target=\"_blank\" alt=\"amber\">Amber</a> is a molecular dynamics simulation package originally developed by University of California, San Francisco. \r\n<p>\r\nThe input to the benchmark runs is the <a href=\"http://ambermd.org/amber10.bench1.html\" target=\"_blank\">JAC (Joint Amber-Charmm) Benchmark Input</a>, which consists of 23,558 atoms (protein: 159 residues, 2489 atoms, and water: 7,023 molecules TIP3P, 21,069 atoms), uses 2 fs step size, 10,000 steps, and uses the NPT ensemble.\r\n<p>\r\nThe program being benchmarked is the PMEMD component of Amber version 9. PMEMD is a feature-limited but faster implementation of the original main Amber component SANDER.',1,NULL),(2,'BLAS','densela.blas','node',0,'BLAS (Basic Linear Algebra Subroutine) measures the floating-point performance of a single SMP node using the DGEMM (Double-Precision General Matrix Multiplication) routine. The <a href=\"http://icl.cs.utk.edu/projects/llcbench/\">code</a> is compiled to use Intel Math Kernel Library and runs in multi-threaded mode.',0,NULL),(3,'CHARMM (cores)','charmm.core','core',1,'<a href=\"http://www.charmm.org\" target=\"_blank\" alt=\"charmm\">CHARMM</a> is a molecular dynamics simulation package developed by Professor Martin Karplus\'s research group at Harvard University.\r\n<p>\r\nThe input to the benchmark runs is the <a href=\"http://ambermd.org/amber10.bench1.html\" target=\"_blank\">JAC (Joint Amber-Charmm) Benchmark Input</a>, which consists of 23,558 atoms (protein: 159 residues, 2489 atoms, and water: 7,023 molecules TIP3P, 21,069 atoms), uses 1 fs step size, 1,000 steps, and uses the NVT ensemble.\r\n<p>\r\nThe version of CHARMM being benchmarked is 35b1.',1,NULL),(4,'CPMD (cores)','cpmd.core','core',1,'<a href=\"http://www.cpmd.org\" target=\"_blank\">CPMD</a> is an ab-initio quantum mechanical molecular dynamics \r\nbased on the Car-Parrinello method. CPMD is developed by IBM and MPI Stuttgart.\r\n<p>\r\nThe input to the benchmark runs is <a href=\"https://computecanada.org/?pageId=156\">32 water molecules</a> molecular dynamics simulation with 0.4 fs step size and 50 steps.\r\n<p>\r\nThe version of CPMD being benchmarked is 3.11.1.',1,NULL),(5,'GAMESS (cores)','gamess.core','core',1,'<a href=\"http://www.msg.chem.iastate.edu/gamess/\" target=\"_blank\" alt=\"gamess\">GAMESS</a> is an ab initio computational chemistry software package developed by Professor Mark Gordon\'s research group at Iowa State University.\r\n<p>\r\nThe input to the benchmark runs is restricted Hartree-Fock energy calculation of C8H10 with MP2 correction.\r\n<p>\r\nThe version of GAMESS being benchmarked is 12 JAN 2009 (R3).\r\n',1,NULL),(6,'HPCC (cores)','hpcc.core','core',1,'<a href=\"http://icl.cs.utk.edu/hpcc/\" target=\"_blank\" alt=\"hpcc\">HPC Challenge Benchmark</a> suite. It consists of a) High Performance LINPACK, which solves a linear system of equations and measures the floating-point performance, b) Parallel Matrix Transpose (PTRANS), which measures total communications capacity of the interconnect, c) MPI Random Access, which measures the rate of random updates of remote memory, d) Fast Fourier Transform, which measures the floating-point performance of double-precision complex one-dimensional Discrete Fourier Transform. ',1,NULL),(7,'IMB','imb','node',1,'<a href=\"http://www.intel.com/software/imb/\" target=\"_blank\" alt=\"imb\">Intel MPI Benchmark</a> (formally Pallas MPI Benchmark) suite. The suite measures the interconnect\'s latency, bandwidth, bidirectional bandwidth, and various MPI collective operations\' latencies (Broadcast, AllToAll, AllGather, AllReduce, etc). It also measures the MPI-2 Remote Direct Memory Access (RDMA) performance.\r\n<p>\r\nThe benchmarks are run with one process (single-threaded mode) per node.',1,NULL),(8,'LAMMPS (cores)','lammps.core','core',1,'<a href=\"http://lammps.sandia.gov\" target=\"_blank\">LAMMPS</a> is a molecular dynamics simulation package developed at Sandia National Laboratories.\r\n<p>\r\nThe input to the benchmark runs is the <a href=\"http://lammps.sandia.gov/bench/in.protein.txt\">rhodopsin protein</a> benchmark input, which consists of 32,000 atoms, uses 2 fs step size, 4,000 steps, and uses the NPT ensemble.\r\n<p>\r\nThe version of LAMMPS being benchmarked is 1 May 2010',1,NULL),(9,'NAMD (cores)','namd.core','core',1,'<a href=\"http://www.ks.uiuc.edu/Research/namd/\" target=\"_blank\" alt=\"namd\">NAMD</a> is a molecular dynamics simulation package developed by the Theoretical and Computational Biophysics Group in the Beckman Institute for Advanced Science and Technology at the University of Illinois at Urbana-Champaign.\r\n<p>\r\nThe input to the benchmark runs is the Apolipoprotein A1 benchmark input, which consists of 92,224 atoms, uses 2 fs step size, 1,200 steps, and uses the NVE ensemble.\r\n<p>\r\nThe version of NAMD being benchmarked is 2.7b2',1,NULL),(10,'NPB (cores)','npb.core','core',1,'<a href=\"http://www.nas.nasa.gov/Resources/Software/npb.html\" target=\"_blank\" alt=\"npb\">NAS Parallel Benchmark</a> (NPB) suite. It consists of computation kernels derived from computational fluid dynamics (CFD) applications. The kernels are: a) Conjugate Gradient (CG), which calculates the smallest eigenvalue of a sparse symmetric matrix using the conjugate gradient method, b) Fast Fourier Transform (FT), which solves a 3D Poisson PDE using the fast Fourier transform, c) Block Tridiagonal (BT), Scalar Pentadiagonal (SP), Lower-Upper Solver (LU), which are three different algorithms to solve the 3D Navier-Stokes equation, d) Multi Grid, which uses V-cycle multigrid algorithm to solve a 3D discrete Poisson problem. <p>Certain kernels, such as BT and SP, will only run on square-numbered processors.',1,NULL),(11,'NWChem (cores)','nwchem.core','core',1,'<a href=\"http://www.nwchem-sw.org\" target=\"_blank\" alt=\"nwchem\">NWChem</a> is an ab initio computational chemistry software package developed by Pacific Northwest National Laboratory.\r\n<p>\r\nThe input to the benchmark runs is the Hartree-Fock energy calculation of Au+ with MP2 and Coupled Cluster corrections.\r\n<p>\r\nThe version of NWChem being benchmarked is 5.1.1.\r\n<p>\r\nThe metrics we show here contain NWChem\'s self-collected Global Arrays statistics. The Global Arrays toolkit is the communication library used by NWChem to manipulate large arrays distributed across compute nodes. The Global Arrays toolkit has three basic operations: Get (fetch values from remote memory), Put (store values to remote memory), and Accumulate (update values in remote memory). NWChem measures the numbers of these operations and the amount of data affected by them.',1,NULL),(12,'OMB','omb','node',1,'<a href=\"http://mvapich.cse.ohio-state.edu/benchmarks/\" target=\"_blank\" alt=\"omb\">Ohio State University\'s MPI Benchmark</a> (OMB) suite. The suite measures the interconnect\'s latency, bandwidth, bidirectional bandwidth, multiple bandwidth / message rate test, multi-pair latency and broadcast latency. \r\n<p>\r\nThe benchmarks are run with one process (single-threaded mode) per node.',1,NULL),(13,'QuantumESPRESSO (cores)','quantum_espresso.core','core',1,'<a href=\"http://www.quantum-espresso.org\" target=\"_blank\" alt=\"qe\">Quantum ESPRESSO</a> (formally PWSCF) is an electronic structure computation package developed by DEMOCRITOS National Simulation Center (Trieste) and its partners.\r\n<p>\r\nThe input to the benchmark runs is the single-point SCF energy calculation of ZnO, which consists of 564 electrons distributed in 9 cells.\r\n<p>\r\nThe version of Quantum ESPRESSO being benchmarked is 4.2.1',1,NULL),(14,'STREAM (cores)','stream.core','core',0,'<a href=\"http://www.cs.virginia.edu/stream/\" target=\"_blank\" alt=\"stream\">STREAM benchmark</a> measures sustainable memory bandwidth and the corresponding computation rate of simple vector operations. It consists of four microbenchmarks: a) Copy, which is a[i]=b[i], b) Scale, which is a[i]=b[i]*c, c) Add, which is c[i]=a[i]+b[i], and d) Triad, which is c[i]=a[i]+b[i]*d',0,NULL),(15,'Graph500 (cores)','graph500.core','core',1,'<a href=\"http://www.graph500.org\" target=\"_blank\" alt=\"graph500\">Graph 500</a> is a benchmark designed to measure the performance of graph algorithms, an increasingly important workload in the data-intensive analytics applications.\r\n<p>\r\nCurrently Graph 500 benchmark contains one computational kernel: the breadth-first search. The input is a pre-generated graph created with the following parameters:  SCALE=16 and edgefactor=16. These translate to, on a per MPI process basis,  2^SCALE=65536 vertices and 65536*edgefactor=1.04 million edges.',1,NULL),(16,'OSJitter (cores)','osjitter.core','core',1,'<a href=\"http://www.unixer.de/research/netgauge\" target=\"_blank\" alt=\"graph500\">OS Jitter</a> is a microbenchmark designed to measure the runtime environment noise induced by background daemon processes and operating system\'s own overhead.\r\n<p>\r\nThis microbenchmark is adapted from the NetGauge benchmark suite. It reports the noise as a percentage of extra time spent in a tight loop of repeatedly reading the CPU timestamp counter. It also reports the number of involuntary context switches the microbenchmark experienced during the aforementioned loop.',1,NULL),(17,'MPI-Tile-IO (cores)','mpi-tile-io.core','core',1,'MPI-Tile-IO measures the performance of a storage system under a noncontiguous access workload. It uses the MPI IO library to read and write a 2D and 3D grid (which is distributed evenly among all MPI processes) against a single file, and it reports the aggregate I/O throughput.',1,NULL),(18,'IOR (cores)','ior.core','core',1,'IOR (Interleaved-Or-Random) measures the performance of a storage system under simple access patterns. It uses four different I/O interfaces: POSIX, MPI IO, HDF (Hierarchical Data Format), and Parallel NetCDF (Network Common Data Form) to read and write contiguous chunks of data against either a single file (N-to-1 mode) or N files (N-to-N mode), and it reports the aggregate I/O throughput.',1,NULL),(19,'CESM (cores)','cesm.core','core',1,'<a href=\"http://www.cesm.ucar.edu\" target=\"_blank\" alt=\"cesm\">Community Earth System Model</a>, formerly Community Climate System Model (CCSM4.0), is a fully-coupled, global climate model for simulating Earth\'s past, present, and future climate states. It brings together five separate models to simultaneously simulate the Earth\'s atmosphere, ocean, land, glacier (land-ice), and sea-ice.\r\n<p>\r\nThe atmosphere component is simulated by Community Atmophere Model (CAM), the land by Community Land Model (CLM), the ocean by Parallel Ocean Program (POP), the glacier by Glimmer ice sheet model (CISM), and the sea-ice by CICE. The results from separate models/components are coupled together by the Model Coupling Toolkit (MCT) developed at the Argonne National Laboratory.\r\n<p>\r\nCESM is an unstructured-grid, data-intensive code. The input dataset used is \"B_2000\" component set (all models active, present day scenario) with resolution 1.9x2.5_gx1v6 (1.9x2.5 for atmosphere and land components and gx1v6 for ocean and ice.) The number of simulated days is 5.',1,NULL),(20,'WRF (cores)','wrf.core','core',1,'<a href=\"http://www.wrf-model.org\" target=\"_blank\" alt=\"wrf\">Weather Research and Forecasting Model</a> is a mesoscale numerical weather prediction system used by many meteorological services worldwide.\r\n<p>\r\nThe WRF model used here is ARW (Advanced Research WRF). The input dataset is the standard 2.5km Continental US (CONUS) benchmark.',1,NULL),(22,'GAMESS','gamess','node',1,'<a href=\"http://www.msg.chem.iastate.edu/gamess/\" target=\"_blank\" alt=\"gamess\">GAMESS</a> is an ab initio computational chemistry software package developed by Professor Mark Gordon\'s research group at Iowa State University.\r\n<p>\r\nThe input to the benchmark runs is restricted Hartree-Fock energy calculation of C8H10 with MP2 correction.\r\n<p>\r\nThe version of GAMESS being benchmarked is 1 MAY 2012 (R1).\r\n',1,NULL),(23,'NAMD','namd','node',1,'<a href=\"http://www.ks.uiuc.edu/Research/namd/\" target=\"_blank\" alt=\"namd\">NAMD</a> is a molecular dynamics simulation package developed by the Theoretical and Computational Biophysics Group in the Beckman Institute for Advanced Science and Technology at the University of Illinois at Urbana-Champaign.\r\n<p>\r\nThe input to the benchmark runs is the Apolipoprotein A1 benchmark input, which consists of 92,224 atoms, uses 2 fs step size, 1,200 steps, and uses the NVE ensemble.\r\n<p>\r\nThe version of NAMD being benchmarked is 2.7b2',1,NULL),(24,'NWChem','nwchem','node',1,'<a href=\"http://www.nwchem-sw.org\" target=\"_blank\" alt=\"nwchem\">NWChem</a> is an ab initio computational chemistry software package developed by Pacific Northwest National Laboratory.\r\n<p>\r\nThe input to the benchmark runs is the Hartree-Fock energy calculation of Au+ with MP2 and Coupled Cluster corrections.\r\n<p>\r\nThe version of NWChem being benchmarked is 5.1.1.\r\n<p>\r\nThe metrics we show here contain NWChem\'s self-collected Global Arrays statistics. The Global Arrays toolkit is the communication library used by NWChem to manipulate large arrays distributed across compute nodes. The Global Arrays toolkit has three basic operations: Get (fetch values from remote memory), Put (store values to remote memory), and Accumulate (update values in remote memory). NWChem measures the numbers of these operations and the amount of data affected by them.',1,NULL),(25,'HPCC','hpcc','node',1,'<a href=\"http://icl.cs.utk.edu/hpcc/\" target=\"_blank\" alt=\"hpcc\">HPC Challenge Benchmark</a> suite. It consists of a) High Performance LINPACK, which solves a linear system of equations and measures the floating-point performance, b) Parallel Matrix Transpose (PTRANS), which measures total communications capacity of the interconnect, c) MPI Random Access, which measures the rate of random updates of remote memory, d) Fast Fourier Transform, which measures the floating-point performance of double-precision complex one-dimensional Discrete Fourier Transform. ',1,NULL),(26,'MPI-Tile-IO','mpi-tile-io','node',1,'MPI-Tile-IO measures the performance of a storage system under a noncontiguous access workload. It uses the MPI IO library to read and write a 2D and 3D grid (which is distributed evenly among all MPI processes) against a single file, and it reports the aggregate I/O throughput.',1,NULL),(27,'IOR','ior','node',1,'IOR (Interleaved-Or-Random) measures the performance of a storage system under simple access patterns. It uses four different I/O interfaces: POSIX, MPI IO, HDF (Hierarchical Data Format), and Parallel NetCDF (Network Common Data Form) to read and write contiguous chunks of data against either a single file (N-to-1 mode) or N files (N-to-N mode), and it reports the aggregate I/O throughput.',1,NULL),(28,'Graph500','graph500','node',1,'<a href=\"http://www.graph500.org\" target=\"_blank\" alt=\"graph500\">Graph 500</a> is a benchmark designed to measure the performance of graph algorithms, an increasingly important workload in the data-intensive analytics applications.\r\n<p>\r\nCurrently Graph 500 benchmark contains one computational kernel: the breadth-first search. The input is a pre-generated graph created with the following parameters:  SCALE=16 and edgefactor=16. These translate to, on a per MPI process basis,  2^SCALE=65536 vertices and 65536*edgefactor=1.04 million edges.',1,NULL),(29,'Enzo','enzo','node',1,'<a href=\"http://enzo-project.org/\" target=\"_blank\" alt=\"Enzo\">Enzo:</a> an Adaptive Mesh Refinement Code for Astrophysics\r\n<p>',1,NULL),(30,'xdmod.bundle','bundle','node',0,'xdmod.bundle',0,NULL),(31,'xdmod.app.md.namd2','namd2','node',0,'xdmod.app.md.namd2',0,NULL),(32,'mdtest','mdtest','node',1,'File system Metadata Benchmark',1,NULL),(33,'HPCG','hpcg','node',0,'<a href=\"http://www.hpcg-benchmark.org/index.html\" target=\"_blank\" alt=\"hpcg\">The High Performance Conjugate Gradients (HPCG) Benchmark</a> project is an effort to create a new metric for ranking HPC systems.',0,NULL),(34,'GROMACS-micro','gromacs_micro','node',0,'<a href=\"http://www.gromacs.org/\" target=\"_blank\" alt=\"GROMACS\">GROMACS:</a> based micro-benchmark for testing purposes\r\n<p>',0,NULL);
/*!40000 ALTER TABLE `app_kernel_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `control_region_def`
--

DROP TABLE IF EXISTS `control_region_def`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `control_region_def` (
  `control_region_def_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(10) unsigned NOT NULL,
  `ak_def_id` int(10) unsigned NOT NULL,
  `control_region_type` enum('date_range','data_points') DEFAULT 'data_points',
  `control_region_starts` datetime NOT NULL COMMENT 'Beginning of control region',
  `control_region_ends` datetime DEFAULT NULL COMMENT 'End of control region',
  `control_region_points` int(10) unsigned DEFAULT NULL COMMENT 'Number of points for control region',
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`control_region_def_id`),
  UNIQUE KEY `resource_id__ak_def_id__num_units__control_region_starts` (`resource_id`,`ak_def_id`,`control_region_starts`),
  KEY `fk_ak_def_id` (`ak_def_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `control_region_def`
--

LOCK TABLES `control_region_def` WRITE;
/*!40000 ALTER TABLE `control_region_def` DISABLE KEYS */;
/*!40000 ALTER TABLE `control_region_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `control_regions`
--

DROP TABLE IF EXISTS `control_regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `control_regions` (
  `control_region_id` int(11) NOT NULL AUTO_INCREMENT,
  `control_region_def_id` int(10) unsigned NOT NULL,
  `ak_id` int(10) unsigned DEFAULT NULL,
  `metric_id` int(10) unsigned NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Is the control region already completed',
  `controlStart` double DEFAULT NULL,
  `controlEnd` double DEFAULT NULL,
  `controlMin` double DEFAULT NULL,
  `controlMax` double DEFAULT NULL,
  PRIMARY KEY (`control_region_id`),
  UNIQUE KEY `control_region_def_id__metric_id` (`control_region_def_id`,`ak_id`,`metric_id`),
  KEY `fk_metric_id` (`metric_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `control_regions`
--

LOCK TABLES `control_regions` WRITE;
/*!40000 ALTER TABLE `control_regions` DISABLE KEYS */;
/*!40000 ALTER TABLE `control_regions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `control_set`
--

DROP TABLE IF EXISTS `control_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `control_set` (
  `metric_id` int(10) NOT NULL,
  `ak_id` int(10) NOT NULL,
  `resource_id` int(10) NOT NULL,
  `min_collected` datetime NOT NULL,
  `max_collected` datetime NOT NULL COMMENT 'This remembers the control region used for each dataset.',
  PRIMARY KEY (`metric_id`,`ak_id`,`resource_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `control_set`
--

LOCK TABLES `control_set` WRITE;
/*!40000 ALTER TABLE `control_set` DISABLE KEYS */;
/*!40000 ALTER TABLE `control_set` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingester_log`
--

DROP TABLE IF EXISTS `ingester_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ingester_log` (
  `source` varchar(64) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `num` int(11) DEFAULT NULL,
  `last_update` datetime NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `success` tinyint(1) DEFAULT NULL,
  `message` varchar(2048) DEFAULT NULL,
  `reportobj` blob COMMENT 'Compressed serialized php object with counters',
  KEY `source` (`source`,`last_update`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingester_log`
--

LOCK TABLES `ingester_log` WRITE;
/*!40000 ALTER TABLE `ingester_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `ingester_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_id_seq`
--

DROP TABLE IF EXISTS `log_id_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_id_seq` (
  `sequence` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`sequence`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_id_seq`
--

LOCK TABLES `log_id_seq` WRITE;
/*!40000 ALTER TABLE `log_id_seq` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_id_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_table`
--

DROP TABLE IF EXISTS `log_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_table` (
  `id` int(11) DEFAULT NULL,
  `logtime` datetime DEFAULT NULL,
  `ident` text,
  `priority` text,
  `message` longtext,
  KEY `unique_id_idx` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_table`
--

LOCK TABLES `log_table` WRITE;
/*!40000 ALTER TABLE `log_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metric`
--

DROP TABLE IF EXISTS `metric`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metric` (
  `metric_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `short_name` varchar(32) NOT NULL,
  `name` varchar(128) NOT NULL,
  `unit` varchar(32) DEFAULT NULL,
  `guid` varchar(64) NOT NULL,
  PRIMARY KEY (`metric_id`),
  UNIQUE KEY `unique_guid` (`guid`)
) ENGINE=InnoDB AUTO_INCREMENT=325 DEFAULT CHARSET=latin1 COMMENT='Individual metric definitions';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metric`
--

LOCK TABLES `metric` WRITE;
/*!40000 ALTER TABLE `metric` DISABLE KEYS */;
/*!40000 ALTER TABLE `metric` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metric_attribute`
--

DROP TABLE IF EXISTS `metric_attribute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metric_attribute` (
  `metric_id` int(10) NOT NULL,
  `larger` tinyint(1) NOT NULL,
  PRIMARY KEY (`metric_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metric_attribute`
--

LOCK TABLES `metric_attribute` WRITE;
/*!40000 ALTER TABLE `metric_attribute` DISABLE KEYS */;
/*!40000 ALTER TABLE `metric_attribute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metric_data`
--

DROP TABLE IF EXISTS `metric_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metric_data` (
  `metric_id` int(10) unsigned NOT NULL,
  `ak_id` int(10) unsigned NOT NULL,
  `collected` datetime NOT NULL COMMENT '	',
  `resource_id` int(10) unsigned NOT NULL,
  `value_string` varchar(255) DEFAULT NULL,
  `running_average` double DEFAULT NULL,
  `control` double DEFAULT NULL,
  `controlStart` double DEFAULT NULL,
  `controlEnd` double DEFAULT NULL,
  `controlMin` double DEFAULT NULL,
  `controlMax` double DEFAULT NULL,
  `controlStatus` enum('undefined','control_region_time_interval','in_contol','under_performing','over_performing','failed') DEFAULT NULL,
  PRIMARY KEY (`metric_id`,`ak_id`,`collected`,`resource_id`),
  KEY `fk_metric_data_metric` (`metric_id`),
  KEY `fk_metric_data_reporter_instance` (`ak_id`,`collected`,`resource_id`),
  CONSTRAINT `fk_metric_data_metric` FOREIGN KEY (`metric_id`) REFERENCES `metric` (`metric_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_metric_data_reporter_instance` FOREIGN KEY (`ak_id`, `collected`, `resource_id`) REFERENCES `ak_instance` (`ak_id`, `collected`, `resource_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Collected application kernel data fact table';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metric_data`
--

LOCK TABLES `metric_data` WRITE;
/*!40000 ALTER TABLE `metric_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `metric_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parameter`
--

DROP TABLE IF EXISTS `parameter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parameter` (
  `parameter_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(64) DEFAULT NULL,
  `name` varchar(128) NOT NULL,
  `unit` varchar(64) DEFAULT NULL,
  `guid` varchar(64) NOT NULL,
  PRIMARY KEY (`parameter_id`),
  UNIQUE KEY `unique_guid` (`guid`)
) ENGINE=InnoDB AUTO_INCREMENT=180 DEFAULT CHARSET=latin1 COMMENT='Individual parameter definitions';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parameter`
--

LOCK TABLES `parameter` WRITE;
/*!40000 ALTER TABLE `parameter` DISABLE KEYS */;
/*!40000 ALTER TABLE `parameter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parameter_data`
--

DROP TABLE IF EXISTS `parameter_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parameter_data` (
  `ak_id` int(10) unsigned NOT NULL,
  `collected` datetime NOT NULL,
  `resource_id` int(10) unsigned NOT NULL,
  `parameter_id` int(10) unsigned NOT NULL,
  `value_string` longblob,
  `value_md5` varchar(32) NOT NULL,
  PRIMARY KEY (`ak_id`,`collected`,`resource_id`,`parameter_id`),
  KEY `fk_parameter_data_reporter_instance` (`ak_id`,`collected`,`resource_id`),
  KEY `fk_parameter_data_parameter` (`parameter_id`),
  KEY `md5sum` (`value_md5`),
  CONSTRAINT `fk_parameter_data_parameter` FOREIGN KEY (`parameter_id`) REFERENCES `parameter` (`parameter_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_parameter_data_reporter_instance` FOREIGN KEY (`ak_id`, `collected`, `resource_id`) REFERENCES `ak_instance` (`ak_id`, `collected`, `resource_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Collected application kernel parameters fact table';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parameter_data`
--

LOCK TABLES `parameter_data` WRITE;
/*!40000 ALTER TABLE `parameter_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `parameter_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report`
--

DROP TABLE IF EXISTS `report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report` (
  `user_id` int(11) NOT NULL,
  `send_report_daily` int(11) NOT NULL DEFAULT '0',
  `send_report_weekly` int(11) NOT NULL DEFAULT '0' COMMENT 'Negative-None, otherwise days of the week, i.e. 2 - Monday',
  `send_report_monthly` int(11) NOT NULL DEFAULT '0' COMMENT 'negative is none, otherwise day of the month',
  `settings` text NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report`
--

LOCK TABLES `report` WRITE;
/*!40000 ALTER TABLE `report` DISABLE KEYS */;
/*!40000 ALTER TABLE `report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resource`
--

DROP TABLE IF EXISTS `resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resource` (
  `resource_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource` varchar(128) NOT NULL,
  `nickname` varchar(64) NOT NULL,
  `description` text,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `visible` tinyint(1) NOT NULL,
  `xdmod_resource_id` int(11) DEFAULT NULL,
  `xdmod_cluster_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`resource_id`),
  KEY `visible` (`visible`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=latin1 COMMENT='Resource definitions';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource`
--

LOCK TABLES `resource` WRITE;
/*!40000 ALTER TABLE `resource` DISABLE KEYS */;
INSERT INTO `resource` VALUES (1,'UBHPC_8core','UBHPC_8core','<ul>\r\n<li> 1024 total cores Gainestown @ 2.27GHz (8 cores, 24 GB per node)\r\n<li> 1024 total cores Gulftown @ 2.13GHz (8 cores, 24 GB per node)\r\n</ul>',1,1,1,NULL),(28,'UBHPC_32core','UBHPC_32core','UBHPC',1,1,1,NULL);
/*!40000 ALTER TABLE `resource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supremm_metrics`
--

DROP TABLE IF EXISTS `supremm_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supremm_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  `formula` text NOT NULL,
  `label` text NOT NULL,
  `units` text,
  `info` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `id_2` (`id`),
  UNIQUE KEY `name_2` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supremm_metrics`
--

LOCK TABLES `supremm_metrics` WRITE;
/*!40000 ALTER TABLE `supremm_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `supremm_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_ak_metrics`
--

DROP TABLE IF EXISTS `v_ak_metrics`;
/*!50001 DROP VIEW IF EXISTS `v_ak_metrics`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_ak_metrics` AS SELECT
 1 AS `name`,
 1 AS `enabled`,
 1 AS `num_units`,
 1 AS `ak_id`,
 1 AS `metric_id`,
 1 AS `guid`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ak_parameters`
--

DROP TABLE IF EXISTS `v_ak_parameters`;
/*!50001 DROP VIEW IF EXISTS `v_ak_parameters`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_ak_parameters` AS SELECT
 1 AS `name`,
 1 AS `enabled`,
 1 AS `num_units`,
 1 AS `ak_id`,
 1 AS `parameter_id`,
 1 AS `guid`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_tree_debug`
--

DROP TABLE IF EXISTS `v_tree_debug`;
/*!50001 DROP VIEW IF EXISTS `v_tree_debug`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_tree_debug` AS SELECT
 1 AS `ak_name`,
 1 AS `resource`,
 1 AS `processor_unit`,
 1 AS `num_units`,
 1 AS `ak_def_id`,
 1 AS `resource_id`,
 1 AS `collected`,
 1 AS `status`,
 1 AS `instance_id`*/;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_ak_metrics`
--

/*!50001 DROP VIEW IF EXISTS `v_ak_metrics`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`xdmod`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_ak_metrics` AS select `app_kernel_def`.`ak_base_name` AS `name`,`app_kernel_def`.`enabled` AS `enabled`,`app_kernel`.`num_units` AS `num_units`,`app_kernel`.`ak_id` AS `ak_id`,`ak_has_metric`.`metric_id` AS `metric_id`,`metric`.`guid` AS `guid` from (((`app_kernel_def` join `app_kernel` on((`app_kernel_def`.`ak_def_id` = `app_kernel`.`ak_def_id`))) join `ak_has_metric` on(((`app_kernel`.`ak_id` = `ak_has_metric`.`ak_id`) and (`app_kernel`.`num_units` = `ak_has_metric`.`num_units`)))) join `metric` on((`ak_has_metric`.`metric_id` = `metric`.`metric_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ak_parameters`
--

/*!50001 DROP VIEW IF EXISTS `v_ak_parameters`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`xdmod`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_ak_parameters` AS select `app_kernel_def`.`ak_base_name` AS `name`,`app_kernel_def`.`enabled` AS `enabled`,`app_kernel`.`num_units` AS `num_units`,`app_kernel`.`ak_id` AS `ak_id`,`ak_has_parameter`.`parameter_id` AS `parameter_id`,`parameter`.`guid` AS `guid` from (((`app_kernel_def` join `app_kernel` on((`app_kernel_def`.`ak_def_id` = `app_kernel`.`ak_def_id`))) join `ak_has_parameter` on((`app_kernel`.`ak_id` = `ak_has_parameter`.`ak_id`))) join `parameter` on((`ak_has_parameter`.`parameter_id` = `parameter`.`parameter_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_tree_debug`
--

/*!50001 DROP VIEW IF EXISTS `v_tree_debug`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`xdmod`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_tree_debug` AS select `def`.`name` AS `ak_name`,`r`.`resource` AS `resource`,`def`.`processor_unit` AS `processor_unit`,`ak`.`num_units` AS `num_units`,`def`.`ak_def_id` AS `ak_def_id`,`ai`.`resource_id` AS `resource_id`,unix_timestamp(`ai`.`collected`) AS `collected`,`ai`.`status` AS `status`,`ai`.`instance_id` AS `instance_id` from (((`app_kernel_def` `def` join `app_kernel` `ak` on((`def`.`ak_def_id` = `ak`.`ak_def_id`))) join `ak_instance` `ai` on((`ak`.`ak_id` = `ai`.`ak_id`))) join `resource` `r` on((`ai`.`resource_id` = `r`.`resource_id`))) where ((`def`.`visible` = 1) and (`r`.`visible` = 1)) order by `def`.`name`,`r`.`resource`,`ak`.`num_units`,`ai`.`collected` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-06-04 14:59:37
