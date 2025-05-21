<?php
session_start();
define("BURL", "http://127.0.0.1/website/e-library/");
define("BURLA", "http://127.0.0.1/website/e-library/admin/");
define("ASSETS", "http://127.0.0.1/website/e-library/assets/");
define("BL", __DIR__ . '/');
define("BLA", __DIR__ . '/admin');

//connect to database

require_once(BL . 'functions/db.php');
