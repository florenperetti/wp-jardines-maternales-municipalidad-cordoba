<?php
/*
Plugin Name: Buscador de Jardines Maternales de la Municipalidad de C&oacute;rdoba
Plugin URI: https://github.com/ModernizacionMuniCBA/plugin-wordpress-jardines-maternales-municipales
Description: Este plugin a&ntilde;ade un shortcode que genera un buscador de Jardines Maternales de la Municipalidad de C&oacute;rdoba.
Version: 0.1.2
Author: Florencia Peretti
Author URI: https://github.com/florenperetti/wp-asociados-cooperativas-estacionamiento-municipalidad-cordoba
*/

setlocale(LC_ALL,"es_ES");
date_default_timezone_set('America/Argentina/Cordoba');

add_action('plugins_loaded', array('JardinesMaternalesMuniCordoba', 'get_instancia'));

class JardinesMaternalesMuniCordoba
{
	public static $instancia = null;

	private static $URL_API_GOB = 'https://gobiernoabierto.cordoba.gob.ar/api/v2/entes-privados/jardines/';

	public $nonce_busquedas = '';

	public static function get_instancia()
	{
		if (null == self::$instancia) {
			self::$instancia = new JardinesMaternalesMuniCordoba();
		} 
		return self::$instancia;
	}

	private function __construct()
	{
		add_action('wp_enqueue_scripts', array($this, 'cargar_assets'));

		add_action('wp_ajax_buscar_jardines_maternales', array($this, 'buscar_jardines_maternales')); 
		add_action('wp_ajax_nopriv_buscar_jardines_maternales', array($this, 'buscar_jardines_maternales'));
		
		add_action('wp_ajax_buscar_jardines_maternales_pagina', array($this, 'buscar_jardines_maternales_pagina')); 
		add_action('wp_ajax_nopriv_buscar_jardines_maternales_pagina', array($this, 'buscar_jardines_maternales_pagina'));
		
		add_shortcode('buscador_jardines_maternales_cba', array($this, 'render_shortcode_buscador_jardines_maternales'));

		add_action('init', array($this, 'boton_shortcode_buscador_jardines_maternales'));
	}

	public function render_shortcode_buscador_jardines_maternales($atributos = [], $content = null, $tag = '')
	{
	    $atributos = array_change_key_case((array)$atributos, CASE_LOWER);
	    $atr = shortcode_atts([
            'pag' => 10
        ], $atributos, $tag);

	    $cantidad_por_pagina = $atr['pag'] == 0 ? '' : '?page_size='.$atr['pag'];

	    $url = self::$URL_API_GOB.$cantidad_por_pagina;

    	$api_response = wp_remote_get($url);

    	$resultado = $this->chequear_respuesta($api_response, 'los jardines maternales', 'jardines_maternales_muni_cba');

		echo '<div id="JMM">
	<form>
		<div class="filtros">
			<div class="filtros__columnas">
				<label class="filtros__label" for="nombre">Buscar</label>
				<input type="text" name="nombre">
				<button id="filtros__buscar" type="submit">Buscar</button>
			</div>
			<div class="filtros__columnas">
				<button id="filtros__reset">Todos</button>
			</div>
		</div>
	</form>
	<div class="resultados">';
		echo $this->renderizar_resultados($resultado,$atr['pag'],'');
		echo '</div></div>';
	}
	
	private function renderizar_resultados($datos,$pag = 10, $q)
	{
		$html = '';
		
		if (count($datos['results']) > 0) {
			$html .= '<p class="cantidad-resultados"><small><a href="https://gobiernoabierto.cordoba.gob.ar/data/datos-abiertos/categoria/sociedad/jardines-maternales-habilitados/222" rel="noopener" target="_blank"><b>&#161;Descarg&aacute; toda la informaci&oacute;n&#33;</b></a></small>
				<small>Mostrando '.count($datos['results']).' de '.$datos['count'].' resultados</small></p>';
			foreach ($datos['results'] as $key => $jardin) {

				$estado = $jardin['estado'];
				if (strrpos($estado, 'vencida') !== false) {
					$estado = ' resultado__estado--vencido';
				} elseif (strrpos($estado, 'Vence en') !== false) {
					$estado = ' resultado__estado--por-vencer';
				} else {
					$estado = '';
				}
		
				$plazas = $jardin['plazas_habilitadas'] > 0? '<li><b>Plazas habilitadas:</b> '.$jardin['plazas_habilitadas'].'</li>' : '';
				$html .= '<div class="resultado__container">
						<div class="resultado__cabecera"><span class="resultado__nombre">'.$jardin['nombre'].'</span><span class="resultado__estado'.$estado.'">'.$jardin['estado'].'</span></div>
						<div class="resultado__info">
							<ul>
								<li><b>Titular:</b> '.$jardin['titular'].'</li>
								<li><b>CUIT:</b> '.$jardin['CUIT'].'</li>
								<li><b>Direcci&oacute;n:</b> '.$jardin['direccion'].'</li>
								<li><b>Fecha de inscripci&oacute;n:</b> '.$this->formatear_fecha($jardin['fecha_inscripcion']).'</li>
								'.$plazas.'
							</ul>
						</div>
					</div>';
			}
			
			if ($datos['next'] != 'null' || $datos['previous'] != 'null') {
				$html .= $this->renderizar_paginacion($datos['previous'], $datos['next'], ($pag ? 10 : $pag), $datos['count'], $q);
			}
			
		} else {
			$html .= '<p class="resultados__mensaje">No hay resultados</p>';
		}
		
		return $html;
	}
	
	public function renderizar_paginacion($anterior, $siguiente, $tamanio = 10, $total, $query = '')
	{
		$html = '<div class="paginacion">';
		
		$botones = $total % $tamanio == 0 ? $total / $tamanio : ($total / $tamanio) + 1;

		$actual = 1;
		if ($anterior != null) {
			$actual = $this->obtener_parametro($anterior,'page', 1) + 1;;
		} elseif ($siguiente != null) {
			$actual = $this->obtener_parametro($siguiente,'page', 1) - 1;
		}
		
		$query = '&q='.$query;
		
		for	($i = 1; $i <= $botones; $i++) {
			if ($i == $actual) {
				$html .= '<button type="button" class="paginacion__boton paginacion__boton--activo" disabled>'.$i.'</button>';
			} else {
				$html .= '<button type="button" class="paginacion__boton" data-pagina="'.self::$URL_API_GOB.'?page='.$i.'&page_size='.$tamanio.$query.'">'.$i.'</button>';
			}
		}
		
		$html .= '</div>';
		
		return $html;
	}

	public function boton_shortcode_buscador_jardines_maternales()
	{
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages') && get_user_option('rich_editing') == 'true')
			return;
		add_filter("mce_external_plugins", array($this, "registrar_tinymce_plugin")); 
		add_filter('mce_buttons', array($this, 'agregar_boton_tinymce_shortcode_buscador_jardines_maternales'));
	}

	public function registrar_tinymce_plugin($plugin_array)
	{
		$plugin_array['buscjardinesmaternalescba_button'] = $this->cargar_url_asset('/js/shortcodeJardinesMaternales.js');
	    return $plugin_array;
	}

	public function agregar_boton_tinymce_shortcode_buscador_jardines_maternales($buttons)
	{
	    $buttons[] = "buscjardinesmaternalescba_button";
	    return $buttons;
	}

	public function cargar_assets()
	{
		$urlJSBuscador = $this->cargar_url_asset('/js/buscadorJardinesMaternales.js');
		$urlCSSBuscador = $this->cargar_url_asset('/css/shortcodeJardinesMaternales.css');

		wp_register_style('buscador_jardines_maternales_cba.css', $urlCSSBuscador);
		wp_register_script('buscador_jardines_maternales_cba.js', $urlJSBuscador);

		global $post;
	    if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'buscador_jardines_maternales_cba') ) {
			
			wp_enqueue_script(
				'buscar_jardines_maternales_ajax', 
				$urlJSBuscador, 
				array('jquery'), 
				'1.0.0',
				TRUE
			);
			wp_enqueue_style('buscador_jardines_maternales.css', $this->cargar_url_asset('/css/shortcodeJardinesMaternales.css'));
			
			$nonce_busquedas = wp_create_nonce("buscar_jardines_maternales_nonce");
			
			wp_localize_script(
				'buscar_jardines_maternales_ajax', 
				'buscarJardinesMaternales', 
				array(
					'url'   => admin_url('admin-ajax.php'),
					'nonce' => $nonce_busquedas
				)
			);
		}
	}
	
	public function buscar_jardines_maternales()
	{
		$nombre = $_REQUEST['nombre'];
		check_ajax_referer('buscar_jardines_maternales_nonce', 'nonce');

		if(true && $nombre !== '') {
			$api_response = wp_remote_get(self::$URL_API_GOB.'?page_size=10&q='.$nombre);
			$api_data = json_decode(wp_remote_retrieve_body($api_response), true);
			
			wp_send_json_success($this->renderizar_resultados($api_data, 10, $nombre));
		} elseif (true && $nombre == '') {
			$api_response = wp_remote_get(self::$URL_API_GOB);
			$api_data = json_decode(wp_remote_retrieve_body($api_response), true);
			wp_send_json_success($this->renderizar_resultados($api_data, 10,''));
		} else {
			wp_send_json_error($api_data);
		}
		
		die();
	}
	
	public function buscar_jardines_maternales_pagina()
	{
		$pagina = $_REQUEST['pagina'];
		$nombre = $_REQUEST['nombre'];
		check_ajax_referer('buscar_jardines_maternales_nonce', 'nonce');

		if(true && $pagina !== '') {
			$api_response = wp_remote_get($pagina);
			$api_data = json_decode(wp_remote_retrieve_body($api_response), true);
			
			wp_send_json_success($this->renderizar_resultados($api_data, 10, $nombre));
		} else {
			wp_send_json_error($api_data);
		}
		
		die();
	}

	/*
	* Mira si la respuesta es un error, si no lo es, cachea por una hora el resultado.
	*/
	private function chequear_respuesta($api_response, $tipoObjeto)
	{
		if (is_null($api_response)) {
			return [ 'results' => [] ];
		} else if (is_wp_error($api_response)) {
			$mensaje = WP_DEBUG ? ' '.$this->mostrar_error($api_response) : '';
			return [ 'results' => [], 'error' => 'Ocurri&oacute; un error al cargar '.$tipoObjeto.'.'.$mensaje];
		} else {
			return json_decode(wp_remote_retrieve_body($api_response), true);
		}
	}


	/* Funciones de utilidad */

	private function mostrar_error($error)
	{
		if (WP_DEBUG === true) {
			return $error->get_error_message();
		}
	}

	private function formatear_fecha($original)
	{
		return date("d/m/Y", strtotime($original));
	}

	private function cargar_url_asset($ruta_archivo)
	{
		return plugins_url($this->minified($ruta_archivo), __FILE__);
	}

	// Se usan archivos minificados en producción.
	private function minified($ruta_archivo)
	{
		if (WP_DEBUG === true) {
			return $ruta_archivo;
		} else {
			$extension = strrchr($ruta_archivo, '.');
			return substr_replace($ruta_archivo, '.min'.$extension, strrpos($ruta_archivo, $extension), strlen($extension));
		}
	}
	
	private function obtener_parametro($url, $param, $fallback)
	{
		$partes = parse_url($url);
		parse_str($partes['query'], $query);
		$resultado = $query[$param] ? $query[$param] : $fallback;
		return $resultado;
	}
}