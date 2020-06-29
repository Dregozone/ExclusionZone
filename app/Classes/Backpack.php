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
            
            <section id="stats" class="stats">
                Stats: Accuracy 0 | Damage 0 | Alertness 0
            </section>
        ';
    }
}
