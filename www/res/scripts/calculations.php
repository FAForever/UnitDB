<?php 
    function calculateFireCycle($weapon, $unitID){

    ///     Muzzle Salvo Size and Muzzle Count
    $mss = 0;
    $MuzzleCount = 0;
    $firecycle = 0;

    ///  Bring in the trueSalvoSize calculation from the DPS to this function.  It should only be in 1 place!
    if (property_exists($weapon, 'RackBones')) { // dummy weapons dont have racks

        ///     OK, this is a legit weapon, let's set our most basic values.
        $mss = $weapon->MuzzleSalvoSize;

        /// 	Now we need determine if the weapon fires from multiple muzzles simultaneously (or close to it).
        if (property_exists($weapon, 'RackFireTogether') && ($weapon->RackFireTogether == 1)) {
            /// Count up all the muzzles in all the racks
            foreach ($weapon->RackBones as $rack) {
                $MuzzleCount += count((array)$rack->MuzzleBones);
            }
            $firecycle = $mss * $MuzzleCount;
        }
        //  Do all real weapons have MuzzleSalvoDelay?
        //	        else if (property_exists($weapon, 'MuzzleSalvoDelay') && $weapon->MuzzleSalvoDelay == 0) {
        else if ($weapon->MuzzleSalvoDelay == 0) {
            /// Count only the muzzles in the first rack, since RackFireTogether is false
            /// We do not use MuzzleSalvoSize (mss) when MuzzleSalvoDelay is 0.
            /// Cast to an array, in case it is only a string instead of an array, (PHP 8)
            $MuzzleCount = count((array)$weapon->RackBones[0]->MuzzleBones);
            $firecycle =  $MuzzleCount;
        } else {
            $firecycle = $mss;
        }
    }

	return $firecycle;

    }
    
    // Source : https://github.com/spooky/unitdb/blob/master/app/js/dps.js
    // (calculations provided by Exotic_retard)
    function calculateDps($stdClassWeapon, $unitID, $Projectile){
        
        $weapon = arrayCastRecursive($stdClassWeapon); // StdClass are a PAIN to use in PHP
        
        // Hardcoded exceptions
        $specials = [
            'DAA0206', // mercy
            'XAA0306' // solace
        ];
        
        $isSpecial = in_array($unitID, $specials);
        
        $shots = 1;
        
        if (isset($weapon["MuzzleSalvoSize"])) $shots = calculateFireCycle($stdClassWeapon, $unitID);
        
        
        // fall back to the old calculation formula for the special snowflakes
        
        if ($isSpecial) {
            return ($shots * $weapon["Damage"] * ($weapon["DoTPulses"] ?? 1) ) / (1/($weapon["RateOfFire"]));
        }

        
        //    some weapons have separate charge and reload times which results in them firing less often.
        //    in theory if your total MuzzleSalvoDelay is longer than the reload time your weapon waits for the reload time twice,
        //    but thats pretty much a bug so not taken into account here

        // Supcom rounding tie breaks towards evens
        $trueReload = max(0.1*round(10 / ($weapon["RateOfFire"] ?? 1), 0, PHP_ROUND_HALF_EVEN), 0.1); 
        $trueReload = max(
                ($weapon["RackSalvoChargeTime"] ?? 0) + ($weapon["RackSalvoReloadTime"] ?? 0) + 
                    ($weapon["MuzzleSalvoDelay"] ?? 0)*(($weapon["MuzzleSalvoSize"] ?? 1)-1), 
                $trueReload
        );

/*
	Code for calculating missile/projectile count should only be done in calculateFireCycle.
	We don't want to calculate fire cycles in two places. Just use the value returned from that function.
*/
        $trueSalvoSize = $shots;

        $trueDamage = $weapon["Damage"] * ($weapon["DoTPulses"] ?? 1) + ($weapon["InitialDamage"] ?? 0);

        /// For weapons with fragmentation shells (Lobo, Zthuee, Salvation).
        if (isset($Projectile) && property_exists($Projectile->Physics, 'Fragments')) {
            $trueSalvoSize = $trueSalvoSize * $Projectile->Physics->Fragments;
            /// Exception for Salvation
            if ($unitID == "XAB2307") {
                /// Salvation uses a shell that fragments into 6 shells, which then fragments into 6 more.
                /// Only first fragmentation is accounted for above.  Hard code the 2nd one by multiplying by 6.
                $trueSalvoSize = $trueSalvoSize * 6;
            }

        }

        // beam weapons are a thing and do their own thing. yeah good luck working out that.
        $trueDamage = max((floor(($weapon["BeamLifetime"] ?? 0) / (($weapon["BeamCollisionDelay"] ?? 0) + 0.1)) + 1) * $weapon["Damage"], $trueDamage);
        if ($trueSalvoSize == 0) $trueSalvoSize = 1;  // Adjustment needed for beam weapons

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
