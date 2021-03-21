<?php


namespace app\View;


class AppView
{
    protected $user;

    public function __construct($page, $user) {
        $this->user = $user;
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
                    
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css " />
                    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
                    
                    <link rel="stylesheet" href="public/css/Shared.css" />
                    <link rel="stylesheet" href="public/css/' . $page . '.css" />
                    
                    <script src="public/js/Shared.js"></script>
                    <script src="public/js/' . $page . '.js"></script>
                </head>
                <body>
                    <main>
        ';

        //$html .= $this->logoutOptions();

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

    private function logoutOptions() {

        $html = '
            <section id="logout" class="logout">
                
                Welcome, ' . $this->user . '. 
            
                <a href="?p=Login">
                    Logout
                </a>
            </section>
        ';

        return $html;
    }
}
