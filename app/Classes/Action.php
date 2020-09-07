<?php

class Action
{
    private $action;
    private $location;
    private $actionName;
    private $actionType;
    private $actionTimer;
    private $actionExp;
    private $items;

    public function __construct($action) {
        $this->action = $action;

        $this->findLocation();
        $this->findActionInfo();

        if ( $this->actionType == "shop" ) {
            $this->findItems();

            echo $this->displayNpc();
        }

        echo $this->displayAction();
    }


    private function findItems() {

        $this->items = array(
             "Hazmat Suit" => array(
                 "id" => 1
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
                 "id" => 2
                ,"name" => "Damaged Hazmat Suit"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "body"
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
                 "id" => 3
                ,"name" => "Kevlar"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "body"
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
                "id" => 4
                ,"name" => "Damaged Kevlar"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "body"
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
                "id" => 5
                ,"name" => "S10 Gas Mask"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "head"
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
                "id" => 6
                ,"name" => "Damaged S10 Gas Mask"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "head"
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
                "id" => 7
                ,"name" => "Ballistic Helmet"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "head"
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
                "id" => 8
                ,"name" => "Damaged Ballistic Helmet"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "head"
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
                "id" => 9
                ,"name" => "Hiking Boots"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "feet"
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
                "id" => 10
                ,"name" => "Damaged Hiking Boots"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "feet"
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
                "id" => 11
                ,"name" => "Combat Boots"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "feet"
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
                "id" => 12
                ,"name" => "Damaged Combat Boots"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "feet"
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
                "id" => 13
                ,"name" => "RPD Light Machine Gun"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "weapon"
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
                "id" => 14
                ,"name" => "Damaged RPD Light Machine Gun"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "weapon"
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
                "id" => 15
                ,"name" => "P226 Pistol"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "sidearm"
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
                 "id" => 16
                ,"name" => "Damaged P226 Pistol"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => "sidearm"
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
    }

    /** Get users current location */
    private function findLocation() {
        $this->location = ''; //// from db, select location where userid=?
    }

    /** Get action info based on user location and action */
    private function findActionInfo() {

        // DB using $this->location and $this->action
        $this->actionName = 'Tutorial shop';

        $this->actionType = 'shop'; ////

        $this->actionTimer = '';
        $this->actionExp = 0;
    }

    private function displayNpc() {

        $html = '
            <section class="npc">
                NPC
            </section>
        ';

        return $html;
    }

    private function displayAction() {

        $html = '
            <section class="action">
                <!-- flexbox for action info: title -->
                <div style="display: flex;">
                    <h2>' . $this->actionName . '</h2>
                </div>
        ';

        if ( $this->actionType == "shop" ) {

            $html .= '
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Stock</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
            ';

            foreach ( $this->items as $name => $details ) {

                $html .= '
                    <tr>
                        <td>
                            ' . Shared::itemBuilder($details["name"], $details["Img"], 10) . '
                        </td>
                        <td>
                            1
                        </td>
                        <td>
                            <div class="btn btn-light">Buy (' . $details["BuyValue"] . ')</div><br />
                            <div class="btn btn-light">Sell (' . $details["SellValue"] . ')</div>
                        </td>
                    </tr>
                ';
            }

            $html .= '
                    </tbody>
                </table>
            ';
        }


        $html .= '
            </section>
        ';

        return $html;
    }

    // Start Getters
        public function getAction() {

            return $this->action;
        }

        public function getLocation() {

            return $this->location;
        }

        public function getActionName() {

            return $this->actionName;
        }

        public function getActionType() {

            return $this->actionType;
        }

        public function getActionTimer() {

            return $this->actionTimer;
        }

        public function getActionExp() {

            return $this->actionExp;
        }
    // End Getters
}
