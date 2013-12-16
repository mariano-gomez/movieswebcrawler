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

$año = 2013;
$paginas = 1;
echo "<html><head></head><body>";
for($pagina=1;$pagina<=$paginas; $pagina++) {
	$url = 'http://www.cinesargentinos.com.ar/estrenos/' . $año . '/' . $pagina;
	
	//echo $url . "<br/>";
	//$htmlCompleto = file_get_contents($url);
	$htmlCompleto = file_get_html($url);
	
	//$ret = $htmlCompleto->find('div[class=pelicula]')->find('');
	$i = 0;
	foreach($htmlCompleto->find('div[class=pelicula]') as $divPelicula) {
		if($i== 0) {
			
		
		//	Info del listado
		echo utf8_decode($divPelicula->find('h2', 0)->find('a', 0)->innertext);	//	Titulo argentino, sin link
		echo "<br/>";
		echo utf8_decode($divPelicula->find('h2', 0)->find('a', 0)->href);		//	link (relativo)
		echo "<br/>";
		$ambosTitulos = utf8_decode($divPelicula->find('h2', 0)->plaintext);				//	El titulo completo bilingue (sin link)
		list($titulo, $title) = preg_split("#\(#", $ambosTitulos, 2);
		echo $titulo . " " . substr($title, 0, -1);
		echo "<br/>";
		echo "---------------";
		
		/*******************/
		//	Info del detalle de la pelicula
		$urlDetalle = 'http://www.cinesargentinos.com.ar' .  $divPelicula->find('h2', 0)->find('a', 0)->href . "datoscompletos";	//	armo la url del detalle de la pelicula
		$htmlPeliculaCompleto = file_get_html($urlDetalle);
		
		$peliculaDatosCompletos = $htmlPeliculaCompleto->find('div[class=PeliculaDatosCompletos]');
		echo $htmlPeliculaCompleto->find('div[class=PeliculaDatosCompletos]')->plaintext;
		
		$htmlPeliculaCompleto->dump();
		
		/*
		$sinopsis = utf8_decode($peliculaDatosCompletos->find('div[class=Sinopsis]')->plaintext);
		echo $sinopsis;
		

		
		/*
		$sinopsis = utf8_decode($peliculaDatosCompletos->find('p[class=Sinopsis]')->plaintext);
		$detallesPelicula = $peliculaDatosCompletos->find('p[class=Texto]');
		$reparto = utf8_decode($detallesPelicula->children(3)->plaintext);
		$repartoSecundario = utf8_decode($detallesPelicula->children(4)->plaintext);
		$directores = utf8_decode($detallesPelicula->children(5)->plaintext);
		$fotografia = utf8_decode($detallesPelicula->children(6)->plaintext);
		$guion = utf8_decode($detallesPelicula->children(7)->plaintext);
		$musica = utf8_decode($detallesPelicula->children(8)->plaintext);
		$genero = utf8_decode($detallesPelicula->children(9)->plaintext);
		$duracion = utf8_decode($detallesPelicula->children(10)->plaintext);
		$calificacion = utf8_decode($detallesPelicula->children(11)->plaintext);
		$estrenoBaires = utf8_decode($detallesPelicula->children(16)->plaintext);
		$estrenoCba = utf8_decode($detallesPelicula->children(26)->plaintext);
		$estrenoUSA = utf8_decode($detallesPelicula->children(27)->plaintext);
		
		echo $sinopsis;
		echo "<br/>";
		echo $reparto;
		echo "<br/>";
		echo $repartoSecundario;
		echo "<br/>";
		echo $directores;
		echo "<br/>";
		echo $fotografia;
		echo "<br/>";
		echo $guion;
		echo "<br/>";
		echo $musica;
		echo "<br/>";
		echo $genero;
		echo "<br/>";
		echo $duracion;
		echo "<br/>";
		echo $calificacion;
		echo "<br/>";
		echo $estrenoBaires;
		echo "<br/>";
		echo $estrenoCba;
		echo "<br/>";
		echo $estrenoUSA;
		echo "<br/>";
		 */
		echo "===================";
		}
		$i++;
	}
}
echo "</body></html>";
?>
