<?php

# Copyright (c) 2012 Alexey Shumkin
# Licensed under the MIT license
if ( false === @include_once( config_get( 'plugin_path' ) . 'ArchiveFiles/ArchiveFile.class.php' ) ) {
        return;
}

class SevenZipFilesPlugin extends ArchiveFilesMantisPlugin {
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
		$this->page = 'config';
	}

	public function config() {
		return array(
			'manage_threshold' => DEVELOPER,
			'exec_path' => '/usr/bin/7za',
			'exec_env_LANG' => '',
		);
	}

	public function hooks() {
		$hooks = array_merge( parent::hooks(), array(
				'EVENT_CORE_READY' => 'core_ready'
			)
		);
		return $hooks;
	}

	public function core_ready( $event ) {
		SevenZipArchive::set_exec( plugin_config_get( 'exec_path' ) );
		SevenZipArchive::set_exec_LANG( plugin_config_get( 'exec_env_LANG' ) );
	}

	public function init() {
		require_once ( 'SevenZipFiles.API.php' );
	}

	protected function handled_extension() {
		return '7z';
	}

	protected function ArchiveHandlerClass() {
		return SevenZipFile;
	}

	protected function extension_loaded() {
		$t_error_code = 0;
		$t_exec_path = plugin_config_get( 'exec_path' );
		exec( $t_exec_path, $t_out, $t_error_code);
		return $t_error_code == 0;
	}
}
