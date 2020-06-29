<?php


class PDA
{

    public function __construct( array $inputs = array() ) {

        echo '
            <section id="PDA" class="PDA">
                PDA here...
            </section>
        ';

        // Update width
        if ( array_key_exists("width", $inputs) ) {
            echo '
                <script>
                    document.getElementById("PDA").style.width = "' . $inputs["width"] . 'vmin";            
                </script>   
            ';
        }

        // Update height
        if ( array_key_exists("width", $inputs) ) {
            echo '
                <script>
                    document.getElementById("PDA").style.height = "' . $inputs["height"] . 'vmin";            
                </script>   
            ';
        }
    }

    public function render() {
        // Update display according to request
        ////
    }

    public function show() {

        echo '
            <script>
                document.getElementById("PDA").style.visibility = "visible";
            </script>
        ';
    }

    public function hide() {

        echo '
            <script>
                document.getElementById("PDA").style.visibility = "hidden";
            </script>
        ';
    }
}
