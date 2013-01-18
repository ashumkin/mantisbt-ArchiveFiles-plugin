<?php

# Copyright (c) 2012 Alexey Shumkin
# Licensed under the MIT license
if ( false === @include_once( config_get( 'plugin_path' ) . 'ArchiveFiles/ArchiveFile.class.php' ) ) {
        return;
}

class ZipFilesPlugin extends ArchiveFilesMantisPlugin {
	public function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = '0.1';
		$this->requires = array(
			'MantisCore' => '1.2.13',
			'MantisCoreFile' => '0.1',
			'ArchiveFiles' => '0.1'
		);

		$this->author = 'Alexey Shumkin';
		$this->contact = 'Alex.Crezoff@gmail.com';
		$this->url = 'http://github.com/ashumkin';
	}

	public function init() {
		require_once ( 'ZipFiles.API.php' );
	}

	protected function handled_extension() {
		return 'zip';
	}

	protected function ArchiveHandlerClass() {
		return ZipFile;
	}

}
