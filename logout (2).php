<?php
require_once 'includes/functions.php';
$_SESSION = [];
session_destroy();
session_start();
flash('info', 'Anda telah keluar.');
redirect('index.php');