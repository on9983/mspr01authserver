<?php
namespace App\Service;

class DevOnly
{

    public function displayError($text) {
        // if ($_ENV["APP_ENV"] === "prod"){
        //     return "Votre requête n'a pas pu être traité due à une erreur interne.";
        // }
        return $text;
    }

}