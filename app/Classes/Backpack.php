<?php

class Backpack
{

    public function __construct() {
        echo '
            <section id="backpack" class="backpack">
                <div id="backpackContent">
                    backpack here...
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
