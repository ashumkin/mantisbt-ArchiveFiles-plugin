<?php

# Copyright (c) 2013 Alexey Shumkin
# Licensed under the MIT license

class ArchiveFilesPlugin extends MantisPlugin {
	public function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = '0.1';
		$this->requires = array(
			'MantisCore' => '1.2.13',
			'MantisCoreFile' => '0.1'
		);

		$this->author = 'Alexey Shumkin';
		$this->contact = 'Alex.Crezoff@gmail.com';
		$this->url = 'http://github.com/ashumkin';
		$this->page = 'config';
	}

	public function init() {
		require_once ( 'ArchiveFile.class.php' );
	}

	public function config() {
		return array(
			'manage_threshold' => DEVELOPER,
			'view_threshold' => REPORTER,
		);
	}

	public function schema() {
		return array (
			array( 'CreateTableSQL', array( plugin_table( 'files' ), "
				id			I  UNSIGNED NOTNULL PRIMARY AUTOINCREMENT,
				file_id		I	NOTNULL UNSIGNED,
				file_index	I	NOTNULL UNSIGNED,
				title 		C(250) NOTNULL DEFAULT \" '' \",
				description C(250) NOTNULL DEFAULT \" '' \",
				diskfile 	C(250) NOTNULL DEFAULT \" '' \",
				filename 	C(250) NOTNULL DEFAULT \" '' \",
				folder 		C(250) NOTNULL DEFAULT \" '' \",
				filesize 	I NOTNULL DEFAULT '0',
				file_type 	C(250) NOTNULL DEFAULT \" '' \",
				date_added 	T NULL
			",
			array('mysql' => 'DEFAULT CHARSET=utf8' ) ) )
		);
	}
}
