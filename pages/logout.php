<?php
# Copyright (c) MantisBT Team - mantisbt-dev@lists.sourceforge.net
# Licensed under the MIT license

require_once('core.php');
require_api('authentication_api.php');

# User is already logged out from Mantis
# TODO: logout from external identity provider

print_header_redirect(auth_login_page(), true, false);