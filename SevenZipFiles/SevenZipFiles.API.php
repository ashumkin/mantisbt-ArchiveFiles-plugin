<?php

# Copyright (c) 2013 Alexey Shumkin
# Licensed under the MIT license

class SevenZipArchive {
	protected static $exec = '7za';
	protected static $files_block_begin = '----------';
	protected static $env_LANG = '';
	public $entries = array();
	protected $file;

	public function __construct( $p_file ) {
		$this->file =$p_file;
		$t_out = '';
		if ( 0 == $this->exec7z( '-slt l', $t_out ) ) {
			$t_files = $this->parse_output( $t_out );
		}
	}

	protected function exec7z( $p_command, &$p_out ) {
		$t_result = 0;
		$t_old_env_LANG = getenv( 'LANG' );
		putenv( 'LANG=' . self::$env_LANG );
		exec(self::$exec . ' -y ' . $p_command . ' ' . $this->file, $p_out, $t_result );
		if ( $t_old_env_LANG ) {
			putenv( 'LANG=' . $t_old_env_LANG );
		}
		return $t_result;
	}

	protected function parse_output( $p_output ) {
		$t_files_block = false;
		$t_file = array();
		foreach( $p_output as $line ) {
			if (! $t_files_block ) {
				if ( $line == self::$files_block_begin ) {
					$t_files_block = true;
				}
				continue;
			} elseif ( $line == '' ) {
				$t_file['Name'] = $t_file['Path'];
				$t_file['is_dir'] = $t_file['Attributes'][0] == 'D';
				$this->entries[] = $t_file;
				$t_file = array();
			} else {
				if ( preg_match( '/^(\w+)\s*=\s*(.+)$/i', $line, $m ) ) {
					$t_file[$m[1]] = $m[2];
				}
			}
		}
		return $this->entries;
	}

	public function extract( $p_dir ) {
		$t_result = $this->exec7z( "x -o'$p_dir'", $t_out );
		return $t_result;
	}

	public static function set_exec_LANG( $p_lang ) {
		self::$env_LANG = $p_lang;
		return self::$env_LANG;
	}

	public static function set_exec( $p_exec ) {
		self:$exec = $p_exec;
		return self::$exec;
	}

}

class SevenZipFile extends ArchiveFile {

	protected function unpack() {
		parent::unpack();
		$t_7z = new SevenZipArchive( $this->attachment['diskfile'] );
		$this->files = array();
		$t_entries = $t_7z->entries;
		$t_count = 0;
		$t_7z->extract( $this->attachment['extraction_path'] );
		foreach ( $t_entries as $t_entry ) {
			if ( $t_entry['is_dir'] ) {
				continue;
			}
			$t_7z_attachment = $this->seven_zip_to_attachment( $t_entry, $t_count++, $this->attachment );
			$t_dir = dirname( $t_7z_attachment['diskfile'] ) . DIRECTORY_SEPARATOR;
			$this->save_extracted( $t_dir . $t_entry['Name'], $t_7z_attachment );
		}
	}

	protected function seven_zip_to_attachment( $p_7z_file, $p_index, $p_attachment ) {
		$t_7z_attachment = $this->obj_to_attachment( $p_7z_file, $p_index, $p_attachment );
		$t_7z_attachment['display_name'] = $p_7z_file['Name'];
		$t_7z_attachment['size'] = $p_7z_file['Size'];
		$t_7z_attachment['date_added'] = strtotime( $p_7z_file['Modified'] );
		return $t_7z_attachment;
	}
}
