<?php

class Backpack
{

    public function __construct() {

        $action = new Action(null);
        $action->findItems();

        // Array of items in users backpack
        $items = [
            "P226 Pistol",
            "Hiking Boots",
            "Ballistic Helmet"
        ];

        echo '
            <section id="backpack" class="backpack">
                <div id="backpackContent">
        ';

        // Lookup details and display each item in the users backpack
        foreach ( $items as $item ) {

            if ( 
                $action->getItems()[$item]["Type"] == "weapon" || 
                $action->getItems()[$item]["Type"] == "sidearm"
            ) {
                $stat1 = "Damage";
                $stat2 = "Accuracy";
            } else { // This is armour/misc
                $stat1 = "PhysProtect";
                $stat2 = "EnviroProtect";
            }

            echo Shared::itemBuilder(
                $action->getItems()[$item]["name"], 
                $action->getItems()[$item]["Img"], 
                $action->getItems()[$item][$stat1], 
                $action->getItems()[$item][$stat2]
            );
        }

        echo '
                </div>
                
                <div class="collapseTab" id="backpackSlider" onclick="hideBackpack();"><</div>
            </section>
            
            <section id="wearing" class="wearing">
                <div id="wearingContent">
                    wearing
                </div>
                
                <div class="collapseTab" id="wearingSlider" onclick="hideWearing();"><</div>
            </section>
            
            <!-- Physical, Environment protection -->
            <section id="stats" class="stats">
                <div id="statsContent">
                    <table style="font-size: 1.75vmin; line-height: 1.75vmin;">
                        <tr>
                            <th>Health</th>
                            <td>100</td>
                        </tr>
                        <tr>
                            <th>Accuracy</th>
                            <td>0</td>
                        </tr>
                        <tr>
                            <th>Damage</th>
                            <td>0</td>
                        </tr>
                        <tr>
                            <th>Range</th>
                            <td>0</td>
                        </tr>
                        <tr>
                            <th>Alertness</th>
                            <td>0</td>
                        </tr>
                        <tr>
                            <th>PhysProtect</th>
                            <td>0</td>
                        </tr>
                        <tr>
                            <th>EnviroProtect</th>
                            <td>0</td>
                        </tr>
                    </table>
                </div>
                
                <div class="collapseTab" id="statsSlider" onclick="hideStats();"><</div>
            </section>
        ';
    }
}
