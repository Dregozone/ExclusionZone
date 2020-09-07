<?php

    // TEMP till autoload
    require_once("app/Model/Debug.php");
    require_once("app/View/AppView.php");
    require_once("app/Classes/Shared.php");

    require_once("app/Classes/PDA.php");
    require_once("app/Classes/Backpack.php");
    require_once("app/Classes/Action.php");


    // Define constants here
    //

    // Create popUp debug area
    echo '
        <div 
            id="blocker"
            style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: darkslategrey;
                z-index: 999998;
                opacity: 0.5;
                visibility: hidden;
            "
        ></div>
        
        <div 
            id="popUp" 
            style="
                position: fixed;
                box-sizing: border-box;
                top: 5%;
                left: 5%;
                width: 90%;
                height: 90%;
                padding: 1%;
                border: 1px solid darkslategrey;
                background: aliceblue;
                z-index: 999999;
                visibility: hidden;        
            "
        >
            popUp area
        </div>
    ';
