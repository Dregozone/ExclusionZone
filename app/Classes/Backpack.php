<?php


class Backpack
{

    public function __construct() {
        echo '
            <section id="backpack" class="backpack">
                backpack here...
            </section>
            
            <section id="wearing" class="wearing">
                wearing
            </section>
            
            <!-- Physical, Environment protection -->
            <section id="stats" class="stats">
                Health 100
                Accuracy 0
                Damage 0
                Alertness 0
                PhysProtect 0
                EnviroProtect 0
            </section>
        ';
    }
}
