<?php

class Action
{
    public static $validActions = [
        "shop",
        "hunting",
        "cooking",
        "patrol"
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
             "Money" => [
                "id" => 0
                ,"name" => "Money"
                ,"skillToUse" => ""
                ,"levelToUse" => ""
                ,"Type" => ""
                ,"Accuracy" => ""
                ,"Damage" => ""
                ,"Alertness" => ""
                ,"PhysProtect" => ""
                ,"EnviroProtect" => ""
                ,"Durability" => "1"
                ,"BuyValue" => "1"
                ,"SellValue" => "1"
                ,"Weight" => "0.1"
                ,"Img" => "public/img/item/Money.png"
                ,"Range" => "0"
             ],
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
                ,"Img" => "public/img/item/HazmatSuit.png"
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
                ,"Img" => "public/img/item/HazmatSuit.png"
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
                ,"Img" => "public/img/item/Kevlar.png"
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
                ,"Img" => "public/img/item/Kevlar.png"
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
                ,"Img" => "public/img/item/s10.png"
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
                ,"Img" => "public/img/item/s10.png"
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
                ,"Img" => "public/img/item/BallisticHelmet.png"
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
                ,"Img" => "public/img/item/BallisticHelmet.png"
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
                ,"BuyValue" => "700"
                ,"SellValue" => "450"
                ,"Weight" => "12"
                ,"Img" => "public/img/item/HikingBoots.png"
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
                ,"BuyValue" => "500"
                ,"SellValue" => "250"
                ,"Weight" => "12"
                ,"Img" => "public/img/item/HikingBoots.png"
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
                ,"BuyValue" => "900"
                ,"SellValue" => "700"
                ,"Weight" => "15"
                ,"Img" => "public/img/item/CombatBoots.png"
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
                ,"BuyValue" => "700"
                ,"SellValue" => "450"
                ,"Weight" => "15"
                ,"Img" => "public/img/item/CombatBoots.png"
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
                ,"Durability" => "1400"
                ,"BuyValue" => "4250"
                ,"SellValue" => "3100"
                ,"Weight" => "65"
                ,"Img" => "public/img/item/RPD.png"
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
                ,"Durability" => "230"
                ,"BuyValue" => "2750"
                ,"SellValue" => "1100"
                ,"Weight" => "65"
                ,"Img" => "public/img/item/RPD.png"
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
                ,"Durability" => "1000"
                ,"BuyValue" => "800"
                ,"SellValue" => "600"
                ,"Weight" => "10"
                ,"Img" => "public/img/item/P226Pistol.png"
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
                ,"Durability" => "200"
                ,"BuyValue" => "325"
                ,"SellValue" => "175"
                ,"Weight" => "10"
                ,"Img" => "public/img/item/P226Pistol.png"
                ,"Range" => "30"
                )
            );
    }

    /** Get users current location */
    public static function findLocation() {

        $location = 'Tutorial camp'; //// from db, select location where userid=?

        $actions = [];

        // Populate list of actions that can be started here
        if ( $location == 'Tutorial camp' ) {

            $actions = [
                "shop",
                "cooking"
            ];
        } else if ( $location == "Camp wall" ) {

            $actions = [
                "patrol",
                "hunting"
            ];
        }

        return [
            $location,
            $actions
        ];
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

            case "cooking": 
                // DB using $this->location and $this->action
                $this->actionName = 'Cook over campfire';
                $this->actionType = 'cooking'; ////
                $this->actionTimer = 100;
                $this->actionExp = 10;
                break;

            case "patrol": 
                // DB using $this->location and $this->action
                $this->actionName = 'Patrol the area';
                $this->actionType = 'combat'; ////
                $this->actionTimer = 110;
                $this->actionExp = 26;
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

        } else if ( $this->action == "cooking" ) {

            $name = "Cook";
            $img = "public/img/npc/Stalker1.png";

        } else { // There is no NPC set up for this action

            $name = false;
            $image = false;
        }

        if ( $name !== false ) {
            $this->npcName = $name;

            $spacer = "";
            $npc = Shared::npcBuilder($name, $img);
        } else {
            $spacer = "<br /><br />";
            $npc = '';
        }

        $html = '
            <section class="npc">
                ' . $spacer . '
                ' . $npc . '
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
                <div style="display: flex; align-items: center; justify-content: center;">
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

                // Skip displaying these items
                $skipItems = [
                    "Money"
                ];
                if ( in_array( $name, $skipItems ) ) {
                    continue;
                }

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

            $doing = $this->npcName !== null ? "You go {$this->actionType} with {$this->npcName}." : "";

            $html .= "
                <p>
                    {$doing}
                    <img src='public/img/action/{$this->actionType}.png' class='action' title='{$this->actionType}' alt='{$this->actionType}' />
                    This takes you {$this->actionTimer} seconds and you will gain {$this->actionExp} experience in {$this->actionType}.
                </p>
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
