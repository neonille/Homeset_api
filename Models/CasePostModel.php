<?php
class CasePostModel {

        public $id;
        public $date;
        public $issuer;
        public $complex;
        public $apartId;
        public $status;
        public $headline;
        public $description;

    function __construct($newCase){
        try {
            $this->date = $newCase['date'];
            $this->complex = $newCase['complex'];
            $this->apartId = $newCase['apartId'];
            $this->status = $newCase['status'];
            $this->headline = $newCase['headline'];
            $this->description = $newCase['description'];
        } catch (Exception $ex) {
            echo $ex;
        }
        
    }

}



?>