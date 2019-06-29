<?php 
    function calculateFireCycle($weapon){
        $mss = $weapon->MuzzleSalvoSize;
        if ($mss == 1){
            $bones = 0;
            foreach($weapon->RackBones as $rack){
                $bones += count((array)$rack->MuzzleBones);
            }
            return $bones;
        }
        else{
            return $mss * count((array)$weapon->RackBones);            
        }
    }
    
    // Source : https://github.com/spooky/unitdb/blob/master/app/js/dps.js
    // (calculations provided by Exotic_retard)
    function calculateDps($stdClassWeapon, $unitID){
        
        $weapon = arrayCastRecursive($stdClassWeapon); // StdClass are a PAIN to use in PHP
        
        // Hardcoded exceptions
        $specials = [
            'UEL0103', // lobo
            'XSL0103', // zthuee
            'DAA0206', // mercy
            'XAA0306' // solace
        ];
        
        $isSpecial = in_array($unitID, $specials);
        
        $shots = 1;
        
        if (isset($weapon["MuzzleSalvoSize"])) $shots = calculateFireCycle($stdClassWeapon);
        
        
        // fall back to the old calculation formula for the special snowflakes
        
        if ($isSpecial) {
            return ($shots * $weapon["Damage"] * ($weapon["DoTPulses"] ?? 1) ) / (1/($weapon["RateOfFire"]));
        }

        
        //    the rof is rounded to the nearest tick since the game runs in ticks.
        //    some weapons also have separate charge and reload times which results in them firing less often. yeah.
        //    in theory if your total MuzzleSalvoDelay is longer than the reload time your weapon waits for the reload time twice,
        //    but thats pretty much a bug so not taken into account here
        
        
        $trueReload = max(0.1*floor((10 / $weapon["RateOfFire"]) + 0.5), 0.1); 
        $trueReload = max(
                ($weapon["RackSalvoChargeTime"] ?? 0) + ($weapon["RackSalvoReloadTime"] ?? 0) + 
                    ($weapon["MuzzleSalvoDelay"] ?? 0)*(($weapon["MuzzleSalvoSize"] ?? 1)-1), 
                $trueReload
        );

        $trueSalvoSize = 1;
        if (($weapon["MuzzleSalvoDelay"] ?? 0) > 0) { // if theres no muzzle delay, all muzzles fire at the same time
            $trueSalvoSize = ($weapon["MuzzleSalvoSize"] ?? 1);
        } else if ($weapon["RackBones"] && count($weapon["RackBones"]) > 0) { // dummy weapons dont have racks
            if ($weapon["RackFireTogether"]) {
              $trueSalvoSize = count($weapon["RackBones"]) * count($weapon["RackBones"][0]["MuzzleBones"]);
            } else if (count($weapon["RackBones"]) > 0) {
              $trueSalvoSize = count($weapon["RackBones"][0]["MuzzleBones"]);
            }
        }

        $trueDamage = $weapon["Damage"]*($weapon["DoTPulses"] ?? 1) + ($weapon["InitialDamage"] ?? 0);
        
        // beam weapons are a thing and do their own thing. yeah good luck working out that.
        $trueDamage = max((floor(($weapon["BeamLifetime"] ?? 0) / (($weapon["BeamCollisionDelay"] ?? 0)+0.1))+1)*$weapon["Damage"], $trueDamage);
        $salvoDamage = $trueSalvoSize * $trueDamage * ($isSpecial ? $shots : 1);
        $trueDPS = ($salvoDamage / $trueReload);

        return $trueDPS;
    }
    
    function arrayCastRecursive($array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = arrayCastRecursive($value);
                }
                if ($value instanceof stdClass) {
                    $array[$key] = arrayCastRecursive((array)$value);
                }
            }
        }
        if ($array instanceof stdClass) {
            return arrayCastRecursive((array)$array);
        }
        return $array;
    }

?>