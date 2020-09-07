<?php


class Shared
{



    public static function itemBuilder($name, $img, $stat1, $stat2=NULL) {

        $html = '
            <div class="itemSmall">
                <img src="' . $img . '" class="itemSmall" title="' . $name . '" alt="' . $name . '" />
                
                <div style="
                    position: absolute;
                    display: block;
                    bottom: 2px;
                    left: 2px;
                ">
                    ' . $stat1 . '
                </div>
                
                <div class="stat2">' . $stat2 . '</div>
            </div>
        ';

        return $html;
    }
}