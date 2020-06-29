<?php

class Debug
{

    public static function log($msg) {

        echo '
            <script>
                console.log("' . $msg . '");
            </script>
        ';
    }

    public static function alert($msg) {

        echo '
            <script>
                alert("' . $msg . '");
            </script>
        ';
    }

    public static function popUp($msg) {

        echo '
            <script>
                // Set new debug value
                document.getElementById("popUp").innerHTML = "' . $msg . '";
                
                // Make popUp visible
                document.getElementById("blocker").style.visibility = "visible";
                document.getElementById("popUp").style.visibility = "visible";
            </script>
        ';
    }
}
