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

    private $items = array(
         1 => array(
              "id" => ""
             ,"name" => "Hazmat Suit"
             ,"skillToUse" => ""
             ,"levelToUse" => ""
             ,"Type" => "body"
             ,"Accuracy" => "0"
             ,"Damage" => "0"
             ,"Alertness" => "0"
             ,"PhysProtect" => "0"
             ,"EnviroProtect" => "45"
             ,"Durability" => "800"
             ,"BuyValue" => "5000"
             ,"SellValue" => "3500"
             ,"Weight" => "10"
             ,"Img" => ""
         )
        ,"Damaged Hazmat Suit" => array(
             "Type" => "body"
            ,"Accuracy" => "0"
            ,"Damage" => "0"
            ,"Alertness" => "0"
            ,"PhysProtect" => "0"
            ,"EnviroProtect" => "15"
            ,"Durability" => "200"
            ,"BuyValue" => "850"
            ,"SellValue" => "250"
            ,"Weight" => "10"
            ,"Img" => ""
        )
        ,"Kevlar" => array(
             "Type" => "body"
            ,"Accuracy" => "0"
            ,"Damage" => "0"
            ,"Alertness" => "0"
            ,"PhysProtect" => "55"
            ,"EnviroProtect" => "0"
            ,"Durability" => "1600"
            ,"BuyValue" => "4000"
            ,"SellValue" => "2750"
            ,"Weight" => "30"
            ,"Img" => ""
        )
        ,"Damaged Kevlar" => array(
             "Type" => "body"
            ,"Accuracy" => "0"
            ,"Damage" => "0"
            ,"Alertness" => "0"
            ,"PhysProtect" => "35"
            ,"EnviroProtect" => "0"
            ,"Durability" => "800"
            ,"BuyValue" => "1500"
            ,"SellValue" => "700"
            ,"Weight" => "30"
            ,"Img" => ""
        )
        ,"S10 Gas Mask" => array(
             "Type" => "head"
            ,"Accuracy" => "0"
            ,"Damage" => "0"
            ,"Alertness" => "0"
            ,"PhysProtect" => "0"
            ,"EnviroProtect" => "25"
            ,"Durability" => "600"
            ,"BuyValue" => "3000"
            ,"SellValue" => "2250"
            ,"Weight" => "5"
            ,"Img" => ""
        )
        ,"Damaged S10 Gas Mask" => array(
             "Type" => "head"
            ,"Accuracy" => "0"
            ,"Damage" => "0"
            ,"Alertness" => "0"
            ,"PhysProtect" => "0"
            ,"EnviroProtect" => "10"
            ,"Durability" => "300"
            ,"BuyValue" => "750"
            ,"SellValue" => "300"
            ,"Weight" => "5"
            ,"Img" => ""
        )
        ,"Ballistic Helmet" => array(
             "Type" => "head"
            ,"Accuracy" => "0"
            ,"Damage" => "0"
            ,"Alertness" => "0"
            ,"PhysProtect" => "25"
            ,"EnviroProtect" => "0"
            ,"Durability" => "1000"
            ,"BuyValue" => "2000"
            ,"SellValue" => "1200"
            ,"Weight" => "10"
            ,"Img" => ""
        )
        ,"Damaged Ballistic Helmet" => array(
             "Type" => "head"
            ,"Accuracy" => "0"
            ,"Damage" => "0"
            ,"Alertness" => "0"
            ,"PhysProtect" => "15"
            ,"EnviroProtect" => "0"
            ,"Durability" => "600"
            ,"BuyValue" => "800"
            ,"SellValue" => "300"
            ,"Weight" => "10"
            ,"Img" => ""
        )
        ,"Hiking Boots" => array(
             "Type" => "feet"
            ,"Accuracy" => "0"
            ,"Damage" => "0"
            ,"Alertness" => "0"
            ,"PhysProtect" => "5"
            ,"EnviroProtect" => "7"
            ,"Durability" => "800"
            ,"BuyValue" => ""
            ,"SellValue" => ""
            ,"Weight" => ""
            ,"Img" => ""
        )
        ,"Damaged Hiking Boots" => array(
             "Type" => "feet"
            ,"Accuracy" => "0"
            ,"Damage" => "0"
            ,"Alertness" => "0"
            ,"PhysProtect" => "3"
            ,"EnviroProtect" => "3"
            ,"Durability" => "500"
            ,"BuyValue" => ""
            ,"SellValue" => ""
            ,"Weight" => ""
            ,"Img" => ""
        )
        ,"Combat Boots" => array(
             "Type" => "feet"
            ,"Accuracy" => "0"
            ,"Damage" => "0"
            ,"Alertness" => "0"
            ,"PhysProtect" => "10"
            ,"EnviroProtect" => "0"
            ,"Durability" => "800"
            ,"BuyValue" => ""
            ,"SellValue" => ""
            ,"Weight" => ""
            ,"Img" => ""
        )
        ,"Damaged Combat Boots" => array(
             "Type" => "feet"
            ,"Accuracy" => "0"
            ,"Damage" => "0"
            ,"Alertness" => "0"
            ,"PhysProtect" => "7"
            ,"EnviroProtect" => "0"
            ,"Durability" => "500"
            ,"BuyValue" => ""
            ,"SellValue" => ""
            ,"Weight" => ""
            ,"Img" => ""
        )
        ,"RPD Light Machine Gun" => array(
             "Type" => "weapon"
            ,"Accuracy" => "65"
            ,"Damage" => "60"
            ,"Alertness" => "0"
            ,"PhysProtect" => "0"
            ,"EnviroProtect" => "0"
            ,"Durability" => ""
            ,"BuyValue" => ""
            ,"SellValue" => ""
            ,"Weight" => ""
            ,"Img" => ""
        )
        ,"Damaged RPD Light Machine Gun" => array(
             "Type" => "weapon"
            ,"Accuracy" => "40"
            ,"Damage" => "30"
            ,"Alertness" => "0"
            ,"PhysProtect" => "0"
            ,"EnviroProtect" => "0"
            ,"Durability" => ""
            ,"BuyValue" => ""
            ,"SellValue" => ""
            ,"Weight" => ""
            ,"Img" => ""
        )
        ,"P226 Pistol" => array(
             "Type" => "sidearm"
            ,"Accuracy" => "70"
            ,"Damage" => "40"
            ,"Alertness" => "0"
            ,"PhysProtect" => "0"
            ,"EnviroProtect" => "0"
            ,"Durability" => ""
            ,"BuyValue" => ""
            ,"SellValue" => ""
            ,"Weight" => ""
            ,"Img" => ""
        )
        ,"Damaged P226 Pistol" => array(
             "Type" => "sidearm"
            ,"Accuracy" => "40"
            ,"Damage" => "25"
            ,"Alertness" => "0"
            ,"PhysProtect" => "0"
            ,"EnviroProtect" => "0"
            ,"Durability" => ""
            ,"BuyValue" => ""
            ,"SellValue" => ""
            ,"Weight" => ""
            ,"Img" => ""
        )
    );



    // Start Getters
        public function getItems() {

            return $this->items;
        }

        public function getItem($index) {

            return $this->items[$index];
        }
    // End Getters
}
