<?php

# Copyright (c) 2013 Alexey Shumkin
# Licensed under the MIT license

form_security_validate( 'plugin_SevenZip_manage_config' );
auth_reauthenticate();
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_exec_path = gpc_get_string( 'exec_path' );
$f_exec_env_LANG = gpc_get_string( 'exec_env_LANG' );

plugin_config_set( 'exec_path', $f_exec_path );
plugin_config_set( 'exec_env_LANG', $f_exec_env_LANG );

form_security_purge( 'plugin_SevenZip_manage_config' );

print_successful_redirect( plugin_page( 'config', true ) );

