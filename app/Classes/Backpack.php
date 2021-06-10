<?php

class Backpack
{

    public function __construct() {

        $action = new Action(null);
        $action->findItems();

        $health = 100;
        $accuracy = 0;
        $damage = 0;
        $range = 0;
        $alertness = 0;
        $physProtect = 0;
        $enviroProtect = 0;

        // Array of items in users backpack
        $items = [
            "P226 Pistol",
            "Hiking Boots",
            "Ballistic Helmet",
            "Damaged P226 Pistol",
            "Money"
        ];

        $wearings = [
            "RPD Light Machine Gun",
            "P226 Pistol",
            "Damaged Hiking Boots",
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
        ';    
                
        // Lookup details and display each item in the users backpack
        foreach ( $wearings as $wearing ) {

            if ( 
                $action->getItems()[$wearing]["Type"] == "weapon" || 
                $action->getItems()[$wearing]["Type"] == "sidearm"
            ) {
                $stat1 = "Damage";
                $stat2 = "Accuracy";
            } else { // This is armour/misc
                $stat1 = "PhysProtect";
                $stat2 = "EnviroProtect";
            }

            echo Shared::itemBuilder(
                $action->getItems()[$wearing]["name"], 
                $action->getItems()[$wearing]["Img"], 
                $action->getItems()[$wearing][$stat1], 
                $action->getItems()[$wearing][$stat2]
            );

            $accuracy += $action->getItems()[$wearing]["Accuracy"];
            $damage += $action->getItems()[$wearing]["Damage"];
            $alertness += $action->getItems()[$wearing]["Alertness"];
            $physProtect += $action->getItems()[$wearing]["PhysProtect"];
            $enviroProtect += $action->getItems()[$wearing]["EnviroProtect"];

            if ( $action->getItems()[$wearing]["Range"] > $range ) { // Record only the maximum ranged item for this
                $range = $action->getItems()[$wearing]["Range"];
            }
        }

        echo '
                </div>
                
                <div class="collapseTab" id="wearingSlider" onclick="hideWearing();"><</div>
            </section>
        ';

        // Update DB values for statistics for use elsewhere in the application
        ////

        echo '
            <!-- Physical, Environment protection -->
            <section id="stats" class="stats">
                <div id="statsContent">
                    <table style="font-size: 1.75vmin; line-height: 1.75vmin;">
                        <tr>
                            <th>Health</th>
                            <td class="right">' . $health . '</td>
                        </tr>
                        <tr>
                            <th>Accuracy</th>
                            <td class="right">' . $accuracy . '</td>
                        </tr>
                        <tr>
                            <th>Damage</th>
                            <td class="right">' . $damage . '</td>
                        </tr>
                        <tr>
                            <th>Range</th>
                            <td class="right">' . $range . '</td>
                        </tr>
                        <tr>
                            <th>Alertness</th>
                            <td class="right">' . $alertness . '</td>
                        </tr>
                        <tr>
                            <th>PhysProtect</th>
                            <td class="right">' . $physProtect . '</td>
                        </tr>
                        <tr>
                            <th>EnviroProtect</th>
                            <td class="right">' . $enviroProtect . '</td>
                        </tr>
                    </table>
                </div>
                
                <div class="collapseTab" id="statsSlider" onclick="hideStats();"><</div>
            </section>
        ';
    }
}
