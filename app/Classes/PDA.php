<?php

class PDA
{

    public function __construct( array $inputs = array() ) {

        // Set user name for resetting the PDA after shrinking
        echo '
            <script>
                user = "' . $inputs["user"] . '";
            </script>
        ';

        echo '
            <section id="pdaBlocker" class="pdaBlocker" onclick="shrinkPDA();"></section>
            <section id="showHidePDA" class="showHidePDA" onclick="hidePDA();">Hide PDA</section>

            <section id="PDA" class="PDA">
                <div id="PDABackground" class="PDABackground">
                    <p class="gameTime">00:00:00 (Night)</p>
                    <p class="userDetails">
                        ' . $inputs["user"] . '\'s PDA 
                        <a href="?p=Login">
                            <img src="public/img/logout.png" style="width: 2vmin;" title="Logout" alt="Logout" />
                        </a>
                    </p>
                    
                    <hr style="border: 1px solid darkslategrey; padding: 0; margin: 2% 0;" />
                    
                    <div id="PDAContent">
                        <p class="button" onclick="enlargePDA(\'chat\');">Chat</p> <!-- option to show here instead of HUD -->
                        <p class="button" onclick="enlargePDA(\'messages\');">Messages</p>
                        <p class="button" onclick="enlargePDA(\'map\');">Map</p>
                        <p class="button" onclick="enlargePDA(\'settings\');">Settings</p>
                        <p class="button" onclick="enlargePDA(\'manual\');">Manual</p>
                        <p class="button" onclick="enlargePDA(\'quests\');">Quests</p>
                        <p class="button" onclick="enlargePDA(\'faction\');">Faction</p>
                        <p class="button" onclick="enlargePDA(\'forum\');">Forum</p>
                    </div>
                </div>
            </section>
        ';

        // Update width
        if ( array_key_exists("width", $inputs) ) {
            echo '
                <script>
                    document.getElementById("PDA").style.width = "' . $inputs["width"] . 'vmin";            
                </script>   
            ';
        }

        // Update height
        if ( array_key_exists("width", $inputs) ) {
            echo '
                <script>
                    document.getElementById("PDA").style.height = "' . $inputs["height"] . 'vmin";            
                </script>   
            ';
        }
    }

    public function render() {
        // Update display according to request
        ////
    }

    public function show() {

        echo '
            <script>
                document.getElementById("PDA").style.visibility = "visible";
            </script>
        ';
    }

    public function hide() {

        echo '
            <script>
                document.getElementById("PDA").style.visibility = "hidden";
            </script>
        ';
    }
}
