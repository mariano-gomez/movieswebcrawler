<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	</head>
	<body>
		
<?php
require_once("ezSQL/shared/ez_sql_core.php");
require_once("ezSQL/mysql/ez_sql_mysql.php");
require_once("simplehtmldom_1_5/simple_html_dom.php");

global $_sql;
$_sql = new ezSQL_mysql();

if ($_SERVER['HTTP_HOST']=="usurpator.localhost") {
	$_sql->connect('root','','localhost');
	$_sql->select('peliculascasa');
}
$_sql->query("SET character_set_client = utf8;");
$_sql->query("SET character_set_connection = utf8;");
$_sql->query("SET character_set_results = utf8;");
$_sql->query("SET collation_connection = utf8_bin;");

$meses = array(
	"Enero" => "01", "Febrero" => "02", "Marzo" => "03", "Abril" => "04", "Mayo" => "05", "Junio" => "06",
	"Julio" => "07", "Agosto" => "08", "Septiembre" => "09", "Octubre" => "10", "Noviembre" => "11", "Diciembre" => "12"
);

$pagsPorAño = array(
				"2013" => 13,"2012" => 12,"2011" => 13,"2010" => 13,"2009" => 11,"2008" => 10,"2007" => 8,"2006" => 7,"2005" => 7,
				"2004" => 7,"2003" => 7,"2002" => 7,"2001" => 7,"2000" => 6,"1999" => 2,"1998" => 1,"1997" => 2,"1996" => 1,"1995" => 1,
				"1994" => 1,"1993" => 1,"1992" => 1,"1991" => 1,"1990" => 1,"1989" => 1,"1988" => 1,"1987" => 1,"1986" => 1,"1985" => 1,
				"1984" => 1,"1983" => 1,"1982" => 1,"1981" => 1,"1980" => 2
			);

for($año = 1980; $año>=1980; $año--) {
//for($año = 2013; $año>=1980; $año--) {
	$paginas = $pagsPorAño[$año];
	for($pagina=1; $pagina<=$paginas; $pagina++) {
		echo "=================================";
		echo "<H1>INICIANDO PAGINA ".$pagina." de año " . $año . "</H1>";
		$url = 'http://www.cinesargentinos.com.ar/estrenos/' . $año . '/' . $pagina;
		$htmlCompleto = file_get_html($url);

		foreach($htmlCompleto->find('div[class=pelicula]') as $divPelicula) {
			//	Info del listado

			$ambosTitulos = ($divPelicula->find('h2', 0)->plaintext);			//	El titulo completo bilingue (sin link)
			list($titulo, $title) = preg_split("#\(#", $ambosTitulos, 2);
			$titulo = mysql_real_escape_string($titulo);
			$title = mysql_real_escape_string(substr($title, 0, -1));

			//	Info del detalle de la pelicula
			$urlDetalle = 'http://www.cinesargentinos.com.ar' .  $divPelicula->find('h2', 0)->find('a', 0)->href . "datoscompletos";	//	armo la url del detalle de la pelicula
			$htmlPeliculaCompleto = file_get_html($urlDetalle);

			$sinopsis			= mysql_real_escape_string(strip_tags(($htmlPeliculaCompleto->find('p[class=Sinopsis]', 0))));		//	Esto anda
			$infoCruda			= ($htmlPeliculaCompleto->find('div[class=PeliculaDatosCompletos]', 0));	//	Esto anda

			$infoCruda				= preg_replace('#de '.$año.'#', 'de '.$año.' <br/>', $infoCruda);
			$infoSinParagraphTag	= preg_replace('#\<p class\=\"Texto\">#', '', $infoCruda);
			$infoSinParagraphTag	= preg_replace('#\<\/p\>#', '<br>', $infoSinParagraphTag);

			$infoConSoloBRs			= (strip_tags($infoSinParagraphTag, "<br>"));


			$reparto = '';
			if(preg_match(('#ACTORES\: ([[:alpha:], áéíóúÁÉÍÓÚñÑ]+)\.#'), $infoConSoloBRs, $resultado)) {
				$reparto			= mysql_real_escape_string($resultado[1]);
			}
			$repartoSecundario	= '';
			if(preg_match(('#ACTORES SECUNDARIOS\: ([[:alpha:], áéíóúÁÉÍÓÚñÑ]+)\.#'), $infoConSoloBRs, $resultado)) {
				$repartoSecundario	= mysql_real_escape_string($resultado[1]);
			}
			$directores = '';
			if(preg_match(('#DIRECTOR\: ([[:alpha:], áéíóúÁÉÍÓÚñÑ]+)\.#'), $infoConSoloBRs, $resultado)) {
				$directores			= mysql_real_escape_string($resultado[1]);
			} else if (preg_match(utf8_decode('#DIRECCION\: ([[:alpha:], áéíóúÁÉÍÓÚñÑ]+)\.#'), $infoConSoloBRs, $resultado)) {
				$directores			= mysql_real_escape_string($resultado[1]);
			}
			$fotografia = '';
			if(preg_match(('#FOTOGRAFIA\: ([[:alpha:], áéíóúÁÉÍÓÚñÑ]+)\.#'), $infoConSoloBRs, $resultado)) {
				$fotografia			= mysql_real_escape_string($resultado[1]);
			}
			$guion = '';
			if(preg_match(('#GUION\: ([[:alpha:], áéíóúÁÉÍÓÚñÑ]+)\.#'), $infoConSoloBRs, $resultado)) {
				$guion				= mysql_real_escape_string($resultado[1]);
			}
			$musica = '';
			if(preg_match(('#MúSICA\: ([[:alpha:], áéíóúÁÉÍÓÚñÑ]+)\.#'), $infoConSoloBRs, $resultado)) {
				$musica				= mysql_real_escape_string($resultado[1]);
			}
			$genero = '';
			if(preg_match(('#GENERO\: ([[:alpha:], áéíóúÁÉÍÓÚñÑ]+)\.#'), $infoConSoloBRs, $resultado)) {
				$genero				= mysql_real_escape_string(($resultado[1]));
			}
			$duracion = '';
			if(preg_match(utf8_decode('#DURACION\: ([[:alnum:], áéíóúÁÉÍÓÚñÑ]+)#'), $infoConSoloBRs, $resultado)) {
				$duracion			= mysql_real_escape_string($resultado[1]);
			}
			$calificacion = '';
			if(preg_match(('#CALIFICACION\: ([[:alnum:], áéíóúÁÉÍÓÚñÑ]+)#'), $infoConSoloBRs, $resultado)) {
				$calificacion		= (($resultado[1]));
			}
			$estrenoBaires		= "";
			if(preg_match(utf8_decode('#ESTRENO EN BUENOS AIRES\:\s+([[:digit:]]+)\s+de\s+([[:alpha:]]+)\s+de\s+([[:digit:]]+)\s+#'), $infoConSoloBRs, $resultado)) {
				if(count($resultado>1)) {
					$estrenoBaires		= $resultado[3] . "-" . $meses[$resultado[2]] . "-" . $resultado[1];
				}
			}
			$estrenoCba = '';
			if(preg_match(utf8_decode('#CóRDOBA\:\s+([[:digit:]]+)\s+de\s+([[:alpha:]]+)\s+de\s+([[:digit:]]+)\s+#'), $infoConSoloBRs, $resultado)) {
				if(count($resultado>1)) {
					//$estrenoCba		= utf8_decode($resultado[1]);
					$estrenoCba		= $resultado[3] . "-" . $meses[$resultado[2]] . "-" . $resultado[1];
				}
			}

			$query = ("INSERT INTO cinesargentinos (titulo,			title,		sinopsis,			reparto,		reparto_secundario,		directores,		fotografia,		guion,	musica,		genero,		duracion,		calificacion,		estreno_bsas,		estreno_cba,	url_detalle) VALUES ".
												"('".$titulo."', '".$title."', '".$sinopsis."', '".$reparto."', '".$repartoSecundario."',	'".$directores."', '".$fotografia."', '".$guion."', '".$musica."', '".$genero."', '".$duracion."', '".$calificacion."', '".$estrenoBaires."', '".$estrenoCba."', '".$urlDetalle."')");

			echo "<br/>";
			echo $query;
			echo "<br/>";
			$_sql->query($query);
		}
		echo "<H1>TERMINADA PAGINA ".$pagina." </H1>";
		echo "=================================";
	}
}
?>
	</body>
</html>