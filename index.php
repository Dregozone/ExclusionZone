<?php

    require_once("config/setup.php");

    // Check if user is logged in
    //$page = isset($_SESSION["user"]) ? "game" : "login";

    $page = "game";

    // Prep for MVC
    $page = ucfirst($page);
    $mPage = "app\\Model\\$page";
    $cPage = "app\\Controller\\$page";
    $vPage = "app\\View\\$page";

    // TEMP till autoload
    require_once("$mPage.php");
    require_once("$cPage.php");
    require_once("$vPage.php");

    // Set up page specific MVC
    $model = new $mPage();
    $controller = new $cPage($model);
    $view = new $vPage($model, $controller, $page);

    // Main page logic
    require_once("app/$page.php");
