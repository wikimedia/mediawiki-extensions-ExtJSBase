<?php

namespace MediaWiki\Extension\ExtJSBase\ResourceModule;

use MediaWiki\Extension\ExtJSBase;

class ExtJSAll extends \ResourceLoaderFileModule {

	/**
	 *
	 * @var ExtJSBase\ITheme
	 */
	protected $extjsTheme = null;

	/**
	 *
	 * @param array $options
	 * @param string $localBasePath
	 * @param string $remoteBasePath
	 */
	public function __construct( $options = array(), $localBasePath = null, $remoteBasePath = null ) {
		parent::__construct( $options, $localBasePath, $remoteBasePath );
		$config = \MediaWiki\MediaWikiServices::getInstance()
				->getConfigFactory()->makeConfig( 'extjsbase' );
		$themeClass = $config->get( ExtJSBase\Config::THEME );
		$this->extjsTheme = new $themeClass();
	}

	/**
	 *
	 * @param \ResourceLoaderContext $context
	 * @return array
	 */
	public function getStyleFiles( \ResourceLoaderContext $context ) {
		return [
			$this->extjsTheme->getStyleFiles()
		];
	}

	/**
	 *
	 * @param \ResourceLoaderContext $context
	 * @return array
	 */
	protected function getScriptFiles( \ResourceLoaderContext $context ) {
		$this->scripts = array_merge(
			[ 'extjs/ext-all.js', 'ext.extjsbase.init.js' ],
			$this->extjsTheme->getScriptFiles()
		);
		$files = parent::getScriptFiles( $context );
		return $files;
	}

	/**
	 *
	 * @return array
	 */
	public function getTargets() {
		return [ "mobile", "desktop" ];
	}
}
