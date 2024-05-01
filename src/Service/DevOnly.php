<?php
namespace App\Service;

class DevOnly
{

    public function displayError($text) {
        if (getenv('APP_ENV') === "prod"){
            return "Une erreur est survenue.";
        }
        return $text;
    }

}