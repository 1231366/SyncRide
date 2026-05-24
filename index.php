<?php
// Legacy root index — forwards to the new public/ front controller.
// The repository's real document root should be /public; this file exists
// purely so deployments that cannot change DocumentRoot keep working.

header('Location: /SRMT/public/');
exit;
