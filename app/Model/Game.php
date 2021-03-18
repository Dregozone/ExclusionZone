<?php

    namespace app\Model;

    class Game
    {
        private $skills = array(
            "Stealth"
            ,"Gunslinger"
            ,"Melee"
            ,"Crafting"
            ,"Strength"
        );

        public function __construct() {
            //
        }


        // Start Getters
            public function getItem($index) {

                return $this->items[$index];
            }
        // End Getters
    }
