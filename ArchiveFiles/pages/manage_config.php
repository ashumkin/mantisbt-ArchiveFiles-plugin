<?php

# Copyright (c) 2012 Alexey Shumkin
# Licensed under the MIT license

form_security_validate( 'plugin_ArchiveFiles_manage_config' );
auth_reauthenticate();
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_view_threshold = gpc_get_int( 'view_threshold', REPORTER );

plugin_config_set( 'view_threshold', $f_view_threshold );

form_security_purge( 'plugin_ArchiveFiles_manage_config' );

print_successful_redirect( plugin_page( 'config', true ) );

