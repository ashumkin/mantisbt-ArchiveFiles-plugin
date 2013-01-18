<?php

# Copyright (c) 2013 Alexey Shumkin
# Licensed under the MIT license

class RarFile extends ArchiveFile {

	protected function unpack() {
		parent::unpack();
		$t_rar = RarArchive::open( $this->attachment['diskfile'] );
		$this->files = array();
		$t_entries = $t_rar->getEntries();
		$t_count = 0;
		foreach ( $t_entries as $t_entry ) {
			$t_rar_attachment = $this->rar_to_attachment( $t_entry, $t_count++, $this->attachment );
			$t_dir = dirname( $t_rar_attachment['diskfile'] ) . DIRECTORY_SEPARATOR;
			$t_entry->extract( $t_dir );
			$this->save_extracted( $t_dir . $t_entry->getName(), $t_rar_attachment );
		}
	}

	protected function rar_to_attachment( $p_rar_file, $p_index, $p_attachment ) {
		$t_rar_attachment = $this->obj_to_attachment( $p_rar_file, $p_index, $p_attachment );
		$t_rar_attachment['display_name'] = $p_rar_file->getName();
		$t_rar_attachment['size'] = $p_rar_file->getUnpackedSize();
		$t_rar_attachment['date_added'] = strtotime( $p_rar_file->getFileTime() );
		return $t_rar_attachment;
	}
}
