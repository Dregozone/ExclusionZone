<?php

class PDA
{

    public function __construct( array $inputs = array() ) {

        echo '
            <section id="pdaBlocker" class="pdaBlocker" onclick="shrinkPDA();"></section>

            <section id="showHidePDA" class="showHidePDA" onclick="hidePDA();">Hide PDA</section>

            <section id="PDA" class="PDA">
                <div id="PDABackground" class="PDABackground">
                    <p class="gameTime">00:00:00 (Night)</p>
                    <p class="userDetails">(user)\'s PDA</p>
                    
                    <hr style="border: 1px solid black;" />
                    
                    <p class="button" onclick="enlargePDA(\'chat\');">Chat</p> <!-- option to show here instead of HUD -->
                    <p class="button" onclick="enlargePDA(\'messages\');">Messages</p>
                    <p class="button" onclick="enlargePDA(\'map\');">Map</p>
                    <p class="button" onclick="enlargePDA(\'settings\');">Settings</p>
                    <p class="button" onclick="enlargePDA(\'manual\');">Manual</p>
                    <p class="button" onclick="enlargePDA(\'quests\');">Quests</p>
                    <p class="button" onclick="enlargePDA(\'faction\');">Faction</p>
                    <p class="button" onclick="enlargePDA(\'forum\');">Forum</p>
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
