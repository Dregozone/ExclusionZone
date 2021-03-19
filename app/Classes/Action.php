<?php

class Action
{
    public static $validActions = [
        "shop",
        "hunting"
    ];

    private $action;
    private $location;
    private $actions = [];
    private $actionName;
    private $actionType;
    private $actionTimer;
    private $actionExp;
    private $items;

    private $npcName;

    public function __construct($action) {
        $this->action = $action;

        $this->findLocation();
        $this->findActionInfo($action);

        if ( $this->actionType == "shop" ) {
            $this->findItems();            
        }

        if ( $action != null ) { // Backpack etc
            echo $this->displayNpc();
            echo $this->displayAction();
        }
    }


    public function findItems() {

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
                ,"Range" => "0"
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
                ,"Range" => "0"
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
                ,"Range" => "0"
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
                ,"Range" => "0"
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
                ,"Range" => "0"
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
                ,"Range" => "0"
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
                ,"Img" => "public/img/BallisticHelmet.png"
                ,"Range" => "0"
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
                ,"Range" => "0"
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
                ,"Img" => "public/img/HikingBoots.png"
                ,"Range" => "0"
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
                ,"Range" => "0"
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
                ,"Range" => "0"
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
                ,"Range" => "0"
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
                ,"Range" => "110"
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
                ,"Range" => "90"
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
                ,"Img" => "public/img/P226Pistol.png"
                ,"Range" => "40"
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
                ,"Range" => "30"
                )
            );
    }

    /** Get users current location */
    public static function findLocation() {
        $location = 'Tutorial location'; //// from db, select location where userid=?

        // Populate list of actions that can be started here
        if ( $location == 'Tutorial location' ) {

            return [
                $location,
                [
                    "shop",
                    "hunting"
                ]
            ];
        }
    }

    /** Get action info based on user location and action */
    private function findActionInfo($action) {
        
        switch ( $action ) {
            case "shop":
                // DB using $this->location and $this->action
                $this->actionName = 'Tutorial shop';
                $this->actionType = 'shop'; ////
                $this->actionTimer = false;
                $this->actionExp = false;
                break;

            case "hunting": 
                // DB using $this->location and $this->action
                $this->actionName = 'Wild hunting';
                $this->actionType = 'hunting'; ////
                $this->actionTimer = 60;
                $this->actionExp = 12;
                break;

            case null:
                return; // Nothing to be calculated or displayed

            default: 
                // Unknown action, log this////
                break;
        }
    }

    private function displayNpc() {

        if ( $this->action == "shop" ) {

            $name = "Shop keeper";
            $img = "public/img/npc/ShopKeeper1.png";

        } else if ( $this->action == "hunting" ) {

            $name = "Hunter";
            $img = "public/img/npc/Stalker1.png";

        }

        $this->npcName = $name;

        $html = '
            <section class="npc">
                ' . Shared::npcBuilder($name, $img) . '
                <p class="npc" id="npcText">
                    <a href="?action=none">
                        Leave
                    </a>
                </p>
            </section>
        ';

        return $html;
    }

    private function displayAction() {

        $html = '
            <section class="action">
                <div style="display: flex;">
                    <h2>' . $this->actionName . '</h2>
                </div>
        ';

        if ( $this->actionType == "shop" ) {

            $html .= '
                <table>
                    <thead>
                        <tr>
                            <th class="shop item">Item</th>
                            <th class="shop stock">Stock</th>
                            <th class="shop action">Action</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
            ';

            foreach ( $this->items as $name => $details ) {

                if ( 
                    $details["Type"] == "weapon" || 
                    $details["Type"] == "sidearm"
                ) {
                    $stat1 = "Damage";
                    $stat2 = "Accuracy";
                } else { // This is armour/misc
                    $stat1 = "PhysProtect";
                    $stat2 = "EnviroProtect";
                }

                $html .= '
                    <tr>
                        <td>
                            ' . Shared::itemBuilder(
                                $details["name"], 
                                $details["Img"], 
                                $details[$stat1],
                                $details[$stat2]
                            ) . '
                        </td>
                        <td class="shop">
                            1
                        </td>
                        <td class="shop">
                            <div class="btn btn-light buySell">Buy (' . $details["BuyValue"] . ')</div>
                            <div class="btn btn-light buySell">Sell (' . $details["SellValue"] . ')</div>
                        </td>
                        <td></td>
                    </tr>
                ';
            }

            $html .= '
                    </tbody>
                </table>
            ';
        } else {
            
            $html .= "
                You go {$this->actionType} with {$this->npcName}.
                <img src='public/img/action/{$this->actionType}.png' class='action' title='{$this->actionType}' alt='{$this->actionType}' />
                This takes you {$this->actionTimer} seconds and you will gain {$this->actionExp} experience in {$this->actionType}. 
            ";

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

        public function getActions() {

            return $this->actions;
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

        public function getItems() {

            return $this->items;
        }
    // End Getters
}
