<?php


class Shared
{



    public static function itemBuilder($name, $img, $stat1, $stat2=NULL) {

        $broken = strpos($name, "Damaged") !== false ? '<img src="public/img/Damaged.png" style="width: 50%;" />' : "";

        $html = '
            <div class="itemSmall">
                <img src="' . $img . '" class="itemSmall" title="' . $name . '" alt="' . $name . '" />
                
                <div class="stat stat1" style="
                    position: absolute;
                    display: block;
                    bottom: 2px;
                    left: 2px;
                ">' . $stat1 . '</div>
                
                <div class="stat stat2" style="
                    position: absolute;
                    display: block;
                    top: 0;
                    left: 2px;
                ">' . $stat2 . '</div>

                <div class="stat stat2" style="
                    position: absolute;
                    display: block;
                    bottom: 2px;
                    right: 3px;
                    text-align: right;
                ">' . $broken . '</div>
            </div>
        ';

        return $html;
    }

    public static function npcBuilder($name, $img) {

        $html = '
            <p style="padding: 0; margin: 0; text-align: center;">
                ' . $name . '
            </p>

            <img src="' . $img . '" class="npc" title="' . $name . '" alt="' . $name . '" />

            <p style="padding: 0; margin: 0; text-align: center;">
                npc actions
            </p>
        ';

        return $html;
    }
}
