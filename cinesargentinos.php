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

			$fechaEstreno = $divPelicula->find('span[class=def]', 0);
			echo $fechaEstreno;
			echo "<br/>";
			echo "---------------";
			echo "<br/>";

			/*******************/
			//	Info del detalle de la pelicula
			$urlDetalle = 'http://www.cinesargentinos.com.ar' .  $divPelicula->find('h2', 0)->find('a', 0)->href . "datoscompletos";	//	armo la url del detalle de la pelicula
			$htmlPeliculaCompleto = file_get_html($urlDetalle);

			
			
			//	$peliculaDatosCompletos = $htmlPeliculaCompleto->find('div[class=PeliculaDatosCompletos]');
			$sinopsis			= utf8_decode($htmlPeliculaCompleto->find('p[class=Sinopsis]', 0));	//	Esto anda
			$infoCruda			= utf8_decode($htmlPeliculaCompleto->find('div[class=PeliculaDatosCompletos]', 0));	//	Esto anda
			
			$infoSinParagraphTag	= preg_replace('#\<p class\=\"Texto\">#', '', $infoCruda);
			$infoSinParagraphTag	= preg_replace('#\<\/p\>#', '<br/>', $infoSinParagraphTag);
			$infoConSoloBRs			= strip_tags($infoSinParagraphTag, "<br>");
//			echo $infoConSoloBRs;
			echo "<br/>";
			
			$reparto			= preg_grep('#ACTORES:(.)+ \.#', $infoConSoloBRs);
			echo $reparto;
/*			$reparto			= utf8_decode($htmlPeliculaCompleto->find('p[class=Texto]', 3));
			$repartoSecundario	= utf8_decode($htmlPeliculaCompleto->find('p[class=Texto]', 4));
			$directores			= utf8_decode($htmlPeliculaCompleto->find('p[class=Texto]', 5));
			$fotografia			= utf8_decode($htmlPeliculaCompleto->find('p[class=Texto]', 6));
			$guion				= utf8_decode($htmlPeliculaCompleto->find('p[class=Texto]', 7));
			$musica				= utf8_decode($htmlPeliculaCompleto->find('p[class=Texto]', 8));
			$genero				= utf8_decode($htmlPeliculaCompleto->find('p[class=Texto]', 9));
			$duracion			= utf8_decode($htmlPeliculaCompleto->find('p[class=Texto]', 10));
			$calificacion		= utf8_decode($htmlPeliculaCompleto->find('p[class=Texto]', 11));
			$estrenoBaires		= utf8_decode($htmlPeliculaCompleto->find('p[class=Texto]', 16));
			$estrenoCba			= utf8_decode($htmlPeliculaCompleto->find('p[class=Texto]', 26));
			$estrenoUSA			= utf8_decode($htmlPeliculaCompleto->find('p[class=Texto]', 27));

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
			 /**/
			echo "===================";
			echo "<br/>";
		}
		$i++;
	}
}
echo "</body></html>";
?>
