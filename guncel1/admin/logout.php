<?php
require_once '../includes/config.php';

adminLogout();
redirect(siteUrl('admin/login.php'));
