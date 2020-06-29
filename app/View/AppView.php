<?php


namespace app\View;


class AppView
{


    public function __construct($page) {
        echo $this->startHtml($page);
    }

    public function __destruct() {
        echo $this->endHtml();
    }

    private function startHtml($page) {

        $html = '
            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <title>' . $page . ' - Exclusion Zone</title>
                    
                    <link rel="stylesheet" href="public/css/Shared.css" />
                    <link rel="stylesheet" href="public/css/' . $page . '.css" />
                    
                    <script src="public/js/Shared.js"></script>
                    <script src="public/js/' . $page . '.js"></script>
                </head>
                <body>
                    <main>
        ';

        return $html;
    }

    private function endHtml() {

        $html = '
                    </main>
                </body>
            </html>
        ';

        return $html;
    }
}