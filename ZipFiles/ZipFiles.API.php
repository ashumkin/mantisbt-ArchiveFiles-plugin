<?php

# Copyright (c) 2013 Alexey Shumkin
# Licensed under the MIT license

class ZipFile extends ArchiveFile {

	protected function unpack() {
		parent::unpack();
		$t_zip = new ZipArchive();
		$t_zip->open( $this->attachment['diskfile'] );
		$this->files = array();
		for ( $i = 0; $i < $t_zip->numFiles; $i++ ) {
			$t_file = $t_zip->statIndex( $i );

			$t_zip_attachment = $this->zip_to_attachment( $t_file, $i, $this->attachment );
			$t_dir = dirname( $t_zip_attachment['diskfile'] ) . DIRECTORY_SEPARATOR;
			$t_zip->extractTo( $t_dir, array( $t_file['name'] ) );
			$this->save_extracted( $t_dir . $t_file['name'], $t_zip_attachment );
		}
	}

	protected function zip_to_attachment( $p_zip_file, $p_index, $p_attachment ) {
		$t_zip_attachment = $this->obj_to_attachment( $p_zip_file, $p_index, $p_attachment );
		$t_zip_attachment['display_name'] = $p_zip_file['name'];
		$t_zip_attachment['size'] = $p_zip_file['size'];
		$t_zip_attachment['date_added'] = $p_zip_file['mtime'];
		return $t_zip_attachment;
	}
}
