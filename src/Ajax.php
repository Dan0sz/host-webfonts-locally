<?php
/* * * * * * * * * * * * * * * * * * * * *
*
*  ██████╗ ███╗   ███╗ ██████╗ ███████╗
* ██╔═══██╗████╗ ████║██╔════╝ ██╔════╝
* ██║   ██║██╔████╔██║██║  ███╗█████╗
* ██║   ██║██║╚██╔╝██║██║   ██║██╔══╝
* ╚██████╔╝██║ ╚═╝ ██║╚██████╔╝██║
*  ╚═════╝ ╚═╝     ╚═╝ ╚═════╝ ╚═╝
*
* @package  : OMGF
* @author   : Daan van den Bergh
* @copyright: © 2024 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF;

class Ajax {
	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * @return void
	 */
	private function init() {
		add_action( 'wp_ajax_omgf_store_checker_results', [ $this, 'store_checker_results' ] );
		add_action( 'wp_ajax_nopriv_omgf_store_checker_results', [ $this, 'store_checker_results' ] );
	}

	/**
	 * @return void
	 */
	public function store_checker_results() {
		check_ajax_referer( 'omgf_store_checker_results', '_wpnonce' );
	}
}
