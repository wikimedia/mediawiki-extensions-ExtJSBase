<?php

namespace MediaWiki\Extension\ExtJSBase\ResourceModule;

use MediaWiki\Languages\LanguageFallback;
use MediaWiki\MediaWikiServices;
use OutputPage;
use ResourceLoaderContext;
use RuntimeException;

/**
 * This class basically contains copies of some methods from the base class that have been changed
 * from 'protected' to 'private' in https://gerrit.wikimedia.org/r/#/c/mediawiki/core/+/442008/
 * It needs to be kept in sync with the base class in future releases.
 * Even better: The custom RL classes in this extension should not build on
 * \ResourceLoaderFileModule but on \ResourceLoaderModule
 */
class Base extends \ResourceLoaderFileModule {

	/**
	 * Gets all scripts for a given context concatenated together.
	 *
	 * @param ResourceLoaderContext $context Context in which to generate script
	 * @return string|array JavaScript code for $context, or package files data structure
	 */
	public function getScript( ResourceLoaderContext $context ) {
		$deprecationScript = $this->getDeprecationInformation( $context );
		if ( $this->packageFiles !== null ) {
			$packageFiles = $this->getPackageFiles( $context );
			foreach ( $packageFiles['files'] as &$file ) {
				if ( $file['type'] === 'script+style' ) {
					$file['content'] = $file['content']['script'];
					$file['type'] = 'script';
				}
			}
			if ( $deprecationScript ) {
				$mainFile =& $packageFiles['files'][$packageFiles['main']];
				$mainFile['content'] = $deprecationScript . $mainFile['content'];
			}
			return $packageFiles;
		}

		$files = $this->getScriptFiles( $context );
		return $deprecationScript . $this->readScriptFiles( $files );
	}

	/**
	 * @param ResourceLoaderContext $context
	 * @return array
	 */
	public function getScriptURLsForDebug( ResourceLoaderContext $context ) {
		$urls = [];
		foreach ( $this->getScriptFiles( $context ) as $file ) {
			$urls[] = OutputPage::transformResourcePath(
				$this->getConfig(),
				$this->getRemotePath( $file )
			);
		}
		return $urls;
	}

	/**
	 * Get a list of script file paths for this module, in order of proper execution.
	 *
	 * @param ResourceLoaderContext $context
	 * @return array List of file paths
	 */
	protected function getScriptFiles( ResourceLoaderContext $context ) {
		$files = array_merge(
			$this->scripts,
			$this->getLanguageScripts( $context->getLanguage() ),
			self::tryForKey( $this->skinScripts, $context->getSkin(), 'default' )
		);
		if ( $context->getDebug() ) {
			$files = array_merge( $files, $this->debugScripts );
		}

		return array_unique( $files, SORT_REGULAR );
	}

	/**
	 * Get the contents of a list of JavaScript files. Helper for getScript().
	 *
	 * @param array $scripts List of file paths to scripts to read, remap and concetenate
	 * @return string Concatenated JavaScript data from $scripts
	 * @throws RuntimeException
	 */
	private function readScriptFiles( array $scripts ) {
		if ( empty( $scripts ) ) {
			return '';
		}
		$js = '';
		foreach ( array_unique( $scripts, SORT_REGULAR ) as $fileName ) {
			$localPath = $this->getLocalPath( $fileName );
			$contents = $this->getFileContents( $localPath, 'script' );
			$js .= $contents . "\n";
		}
		return $js;
	}

	/**
	 * Helper method for getting a file.
	 *
	 * @param string $localPath The path to the resource to load
	 * @param string $type The type of resource being loaded (for error reporting only)
	 * @throws RuntimeException If the supplied path is not found, or not a path
	 * @return string
	 */
	private function getFileContents( $localPath, $type ) {
		if ( !is_file( $localPath ) ) {
			throw new RuntimeException(
				__METHOD__ . ": $type file not found, or is not a file: \"$localPath\""
			);
		}
		return $this->stripBom( file_get_contents( $localPath ) );
	}

	/**
	 * Get the set of language scripts for the given language,
	 * possibly using a fallback language.
	 *
	 * @param string $lang
	 * @return array
	 */
	private function getLanguageScripts( $lang ) {
		$scripts = self::tryForKey( $this->languageScripts, $lang );
		if ( $scripts ) {
			return $scripts;
		}
		$fallbacks = MediaWikiServices::getInstance()->getLanguageFallback()
			->getAll( $lang, LanguageFallback::MESSAGES );
		foreach ( $fallbacks as $lang ) {
			$scripts = self::tryForKey( $this->languageScripts, $lang );
			if ( $scripts ) {
				return $scripts;
			}
		}

		return [];
	}

}
