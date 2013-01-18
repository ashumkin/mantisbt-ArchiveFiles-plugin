<?php

# Copyright (c) 2013 Alexey Shumkin
# Licensed under the MIT license

abstract class ArchiveFilesMantisPlugin extends MantisPlugin {
	public function hooks() {
		return array(
			'EVENT_FILE_SHOW_CONTENT' => 'show_file_content',
			'EVENT_FILE_IS_TO_SHOW_CONTENT' => 'is_to_show_content',
			'EVENT_FILE_UPDATE_PREVIEW_STATE' => 'update_preview_state',
			'EVENT_FILE_CAN_DOWNLOAD' => 'can_download',
			'EVENT_FILE_DOWNLOAD_QUERY' => 'download_query',
			'EVENT_FILE_DELETE' => 'delete'
		);
	}

	abstract protected function handled_extension();

	protected function handled_type_name() {
		return 'archive/' . $this->handled_extension();
	}

	protected function ArchiveHandlerClass() {
		return ArchiveFile;
	}

	protected function extension_loaded() {
		return extension_loaded( $this->handled_extension() );
	}

	public function update_preview_state( $event, $p_attachment ) {
		$t_ext = $p_attachment['alt'];

		if ( $p_attachment['exists']
				&& $p_attachment['can_download']
				&& $p_attachment['size'] != 0
				&& is_null( $p_attachment['preview'] )
				&& false === strpos( $p_attachment['source'], 'plugin' )
				&& 0 == strcasecmp( $t_ext, $this->handled_extension() ) ) {
			$p_attachment['preview'] = true;
			$p_attachment['type'] = $this->handled_type_name();
		}
		return array( $p_attachment );
	}

	public function is_to_show_content( $event, $p_attachment ) {
		if ( $p_attachment['preview'] && ( $p_attachment['type'] == $this->handled_type_name() ) ) {
			$t_user_id = auth_get_current_user_id();
			$t_bug_id = $p_attachment['bug_id'];
			$t_project_id = bug_get_field( $t_bug_id, 'project_id');
			$t_access_level = user_get_access_level( $t_user_id, $t_project_id );

			return $t_access_level >= config_get( 'plugin_ArchiveFiles_view_threshold' );
		}
	}

	public function show_file_content( $event, $p_attachment, $p_content, $p_handled ) {
		if ( !$p_handled && $this->is_to_show_content( $event, $p_attachment ) ) {
			switch( config_get( 'file_upload_method' ) ) {
				case DISK:
					if ( $p_attachment['exists'] ) {
						if ( !$this->extension_loaded() ) {
							echo '<div class="warning">';
							echo '<p><font color="red">' . plugin_lang_get( 'install_' . $this->handled_extension() . '_extension' ) . '</font>';
							echo '</div>';
						} else {
							$t_class = $this->ArchiveHandlerClass();
							$t_rar = new $t_class( $p_attachment );
							$t_rar_attachments = $t_rar->read_files();
							echo '<div><table>';
							event_signal( 'EVENT_FILE_FILES_SHOW', array( $t_rar_attachments ) );
							echo '</table></div>';
						}
					}
			}
			$p_handled = true;
		}
		return array( $p_attachment, $p_content, $p_handled );
	}

	public function download_query( $event, $p_type ) {
		if ( $p_type == ArchiveFile::plugin_id() ) {
			return ArchiveFile::table_name();
		}
	}

	public function can_download( $event, $p_type, $p_file_id, $p_bug_id, $p_user_id ) {
		if ( $p_type == ArchiveFile::plugin_id() ) {
			return true;
		}
	}

	public function delete( $event, $p_file_id, $p_table, $p_bug_id, $p_project_id ) {
		switch( config_get( 'file_upload_method' ) ) {
			case DISK:
				$t_diskfile = file_get_field( $p_file_id, 'diskfile', $p_table ) . '.content';
				$t_diskfile = file_normalize_attachment_path( $t_diskfile, $p_project_id );
				if ( file_exists( $t_diskfile ) ) {
					MantisCoreFilePlugin::delTree( $t_diskfile );
				}
		}
		ArchiveFile::delete( $p_file_id );
		return;
	}
}

class ArchiveFile {
	var $file_id = 0;
	var $files = array();
	var $can_download = false;
	var $attachment;

	public function __construct( $p_attachment ) {
		$this->file_id = $p_attachment['id'];
		$this->can_download = file_can_download_bug_attachments( $p_attachment['bug_id'], $p_attachment['user_id'] );
		$this->attachment= $p_attachment;
	}

	public function plugin_id() {
		return 'plugin/' . get_class();
	}

	public static function table_name() {
		return plugin_table( 'files', 'ArchiveFiles' );
	}

	public static function delete( $p_file_id ) {
		$t_files_table = self::table_name();
		$query = "DELETE FROM $t_files_table WHERE file_id=" . db_param();
		db_query_bound( $query, array( $p_file_id ) );
	}

	public function exists() {
		$t_files_table = self::table_name();
		$query = "SELECT COUNT(*) FROM $t_files_table WHERE file_id=" . db_param();
		$result = db_query_bound( $query, array( $this->file_id ) );
		$t_count = db_result( $result );
		return $t_count > 0;
	}

	public function read_files() {
		if ( !$this->exists() ) {
			$this->unpack();
		}
		return $this->do_read_files();
	}

	protected function unpack() {
		$this->set_extraction_path( $this->attachment );
	}

	protected function save_extracted( $p_file, $p_attachment ) {
		if ( rename( $p_file, $p_attachment['diskfile'] ) ) {
			$this->save( $p_attachment );
		}
	}
	protected function do_read_files() {
		$t_files_table = self::table_name();
		$query = "SELECT * FROM $t_files_table WHERE file_id=" . db_param() . ' ORDER BY file_index';
		$result = db_query_bound( $query, array ( $this->file_id ) );
		$this->files = array();
		while ( $t_row = db_fetch_array( $result ) ) {
			$t_file = array();
			$t_file['source'] = self::plugin_id();
			$t_file['id'] = $t_row['id'];
			$t_file['diskfile'] = $t_row['diskfile'];
			$t_file['display_name'] = file_get_display_name( $t_row['filename'] );
			$t_file['size'] = $t_row['filesize'];
			$t_file['date_added'] = strtotime( $t_row['date_added'] );
			$t_file['can_download'] = $this->can_download;
			$t_file['can_delete'] = false;

			if( $t_file['can_download'] ) {
				$t_file['download_url'] = file_get_download_url( $t_file['id'], self::plugin_id() );
			}

			$t_file['exists'] = true;
			$t_file['icon'] = file_get_icon_url( $t_file['display_name'] );

			//$t_file['preview'] = false;
			$t_file['type'] = '';

			$t_ext = strtolower( file_get_extension( $t_file['display_name'] ) );
			$t_file['alt'] = $t_ext;

			$this->files[] = $t_file;
		};
		for( $i = 0; $i < count( $this->files ); $i++ ) {
			$t_file = $this->files[$i];
			list( $t_file ) = event_signal( 'EVENT_FILE_UPDATE_PREVIEW_STATE', array( $t_file ) );
			$this->files[$i] = $t_file;
		}
		return $this->files;
	}

	protected function save( $p_attachment ) {
		$t_files_table = self::table_name();
		$c_file_id = $p_attachment['file_id'];
		$c_file_index = $p_attachment['file_index'];
		$c_title = $p_attachment['title'];
		$c_desc = $p_attachment['description'];
		$c_unique_name = $p_attachment['diskfile'];
		$c_new_file_name = $p_attachment['display_name'];
		$c_file_path = '';
		$c_file_size = $p_attachment['size'];
		$c_file_type = mime_content_type( $p_attachment['diskfile'] );
		$c_file_date = date( 'Y-m-d H:i:s', $p_attachment['date_added'] );

		$query = "INSERT INTO $t_files_table
					(file_id, file_index, title, description, diskfile, filename, folder, filesize, file_type, date_added)
				  VALUES
					($c_file_id, $c_file_index, '$c_title', '$c_desc', '$c_unique_name', '$c_new_file_name', '$c_file_path', $c_file_size, '$c_file_type', '$c_file_date')";
		db_query( $query );
	}

	protected function set_extraction_path() {
		$t_bug_id = $this->attachment['bug_id'];
		$this->attachment['extraction_path'] = file_normalize_attachment_path( $this->attachment['diskfile'],
			bug_get_field( $t_bug_id, 'project_id' ) ). '.content' . DIRECTORY_SEPARATOR;
	}

	protected function obj_to_attachment( $p_obj_file, $p_index, $p_attachment ) {
		$t_bug_id = $p_attachment['bug_id'];
		$t_user_id = $p_attachment['user_id'];
		$t_id = $p_attachment['id'];

		$t_obj_attachment = array();
		$t_i = sprintf( '%03d', $p_index );
		$t_obj_attachment['file_id'] = $t_id;
		$t_obj_attachment['file_index'] = $t_i;
		$t_obj_attachment['bug_id'] = $t_bug_id;
		$t_obj_attachment['diskfile'] = $p_attachment['extraction_path'] . $t_i;

		return $t_obj_attachment;
	}
}
