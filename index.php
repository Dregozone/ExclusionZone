<?php

    require_once(__DIR__ . "/config/setup.php");

    // Check if user is logged in
    //$page = isset($_SESSION["user"]) ? "game" : "login";

    $user = "Dreggie";
    $isLoggedIn = true;

    if ( $isLoggedIn ) {
        if (
            isset($_GET["p"]) &&
            file_exists("app/{$_GET["p"]}.php")
        ) {
            $page = strtolower(htmlspecialchars(trim($_GET["p"])));
        } else {
            $page = "game";
        }
        
    } else {
        $page = "login";
    }

    // Prep for MVC
    $page = ucfirst($page);
    $mPage = "app\\Model\\$page";
    $cPage = "app\\Controller\\$page";
    $vPage = "app\\View\\$page";

    // TEMP till autoload
    require_once(__DIR__ . "/" . str_replace("\\", "/", $mPage) . ".php");
    require_once(__DIR__ . "/" . str_replace("\\", "/", $cPage) . ".php");
    require_once(__DIR__ . "/" . str_replace("\\", "/", $vPage) . ".php");

    // Set up page specific MVC
    $model = new $mPage();
    $controller = new $cPage($model);
    $view = new $vPage($model, $controller, $page, $user);

    // Main page logic
    require_once(__DIR__ . "/app/$page.php");
