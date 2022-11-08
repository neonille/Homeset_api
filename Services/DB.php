<?php


class DB {


    public static function LOAD_DATA($file, $content, $session){
        try {
            $json = file_get_contents($file);
            $session[$content] = json_decode($json);
        } catch (Exception $ex) {
            echo "Could not read $file file";
        }
    }

    public static function GET_USER_ID($firstname, $session){
        $arr = $session['users']->users;
        $res = array_filter($session['users']->users, function ($user) use ($firstname){
            return strtolower($user->firstname) == strtolower($firstname);
        });
        $res = array_values($res);
        if(empty($res)){
           return null;
        } else {
            return (int)$res[0]->id;
        }
    }

    public static function GET_USER($id, $session){
        $arr = $session['users']->users;
        $res = array_filter($session['users']->users, function ($user) use ($id){
            return $user->id == $id;
        });
        $me = array_values($res);
        $me = $me[0];
        $complexName = array_filter($session['complex']->complex, function ($complex) use ($me){
            return $complex->id == $me->complex;
        });
        if(!$me->landlord){
            $me->complexName = $complexName[0]->name;
        }
        if(empty($res)){
           return null;
        } else {
            return $me;
        }
    }

    public static function GET_CASES($user, $session){
        $res = [];
        if(!$user->landlord){
            $tenant = $user;
            $cases = array_filter($session['cases']->cases, function ($case) use ($tenant){
                return $case->issuer == $tenant->id;
            });
            $cases = array_values($cases);
            $res = $cases;
        } else {
            $landlord = $user;
            foreach ($landlord->complex as $landlord_complex) {
                $casesInComplex = array_filter($session['cases']->cases, function ($case) use ($landlord_complex){
                    return $case->complex == $landlord_complex;
                });
                $res = array_merge($res, $casesInComplex);
            }
        }
        if(empty($res)){
           return null;
        } else {
            return $res;
        }
    }

    public static function GET_CASE($caseId, $session){
        $case = array_filter($session['cases']->cases, function ($case) use ($caseId){
            return $case->id == $caseId;
        });
        $case = array_values($case);
        return $case[0];
    }

    public static function DELETE_CASE($caseId, $session){
        $cases = $session['cases']->cases;
        $index = DB::Get_Index($cases,$caseId);
        unset($cases[$index]);
        $cases = array_values($cases);
        $session['cases']->cases = $cases;
    }

    public static function UPDATE_USER($id,$fieldsToUpdate, $session){
        $users = $session['users']->users;
        $index = DB::Get_Index($users,$id);

        if(isset($fieldsToUpdate['firstname'])){
            $users[$index]->firstname = $fieldsToUpdate['firstname'];
        }
        if(isset($fieldsToUpdate['lastname'])){
            $users[$index]->lastname = $fieldsToUpdate['lastname'];
        }
        if(isset($fieldsToUpdate['phone'])){
            $users[$index]->phone = $fieldsToUpdate['phone'];
        }
        if(isset($fieldsToUpdate['email'])){
            $users[$index]->email = $fieldsToUpdate['email'];
        }

        $session['users']->users = $users; 
    }

    public static function INSERT_NEW_CASE($newCase, $issuerId, $session){
        $idToSet = DB::Max_id($session['cases']->cases) + 10;
        if($idToSet == false){
            $idToSet = 10;
        }
        $newCase->id = $idToSet;
        $newCase->issuer = $issuerId;
        array_push($session['cases']->cases, $newCase);
        
    }


    private static function Get_Index($array, $id){
        foreach ($array as $key=>$obj){
            if($obj->id == $id){
                break;      
            }
        }
        return $key;
    }

    private static function Max_id($arr) {
        return max(array_map(function($object) {
                return $object->id;
            },$arr));
    }
}


?>