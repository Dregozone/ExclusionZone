<?php 

    $pda = new PDA(); // array("height" => "50", "width" => "50")
    $backpack = new Backpack(); // Acts as inventory

    if ( isset($_GET["action"]) && in_array($_GET["action"], Action::$validActions) ) {
        $action = strtolower(htmlspecialchars(trim($_GET["action"])));

        $action = new Action($action);
    }

    // Find location info
    [$locationName, $actions] = Action::findLocation();

    // Display base city menu
    echo "<h1>{$locationName}</h1>";

    echo "
        City menu:
        
        <ul>
    ";

    foreach ( $actions as $action ) {
        echo "
            <li>
                <a href='?action={$action}'>
                    {$action}
                </a>            
            </li>
        ";
    }
    
    echo "
        </ul>
    ";
