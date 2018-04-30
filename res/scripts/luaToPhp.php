

<?php
/***********************************************************************************
 *
 *   lua2php_array - Converts an WoW-Lua File into a php-Array.		
 *
 *   Author: PattyPur (Patty.Pur@web.de)
 *   Char : Shindara 
 *   Guild: Ehrengarde von Theramore
 *   Realm: Kel'Thuzad (DE-PVP)
 *   
 *   Date: 02.10.2005
 *
 *		Edited by rackover@racknet.noip.me (18.01.2018) to convert Supcom units blueprints.
 *
 *
 **********************************************************************************
 */

// Helper-functions

/*
  function trimval(string)
  
  cuts the leading and tailing quotationmarks and the tailing comma from the value
  Example:
    Input: "Value",
    Output: Value    
*/
function trimval($str)
{
  $str = trim($str);
  if (substr($str,0,1)=="\""){
    
    $str  = trim(substr($str,1,strlen($str)));
  }
  if (substr($str,-1,1)==","){
    $str  = trim(substr($str,0,strlen($str)-1));
  }

  if (substr($str,-1,1)=="\""){
    $str  = trim(substr($str,0,strlen($str)-1));
  }
  
  if ($str =='false') 
  {
    $str = false;
  }
  if ($str =='true') 
  {
    $str = true;
  }
  
  return $str;
}

/*
  function array_id(string)
  
  extracts the Key-Value for array indexing 
  String-Example:
    Input: ["Key"]
    Output: Key    
  Int-Example:
    Input: [0]
    Output: 0    
*/
function array_id($str)
{
	$oStr = $str;
  $id1 = sscanf($str, "[%d]");  
  
  
  if (strlen($id1[0])>0){
    return $id1[0];    
  }
  else
  {
    if (substr($str,0,1)=="[")
    {
      $str  = substr($str,1,strlen($str));
    }
    if (substr($str,0,1)=="\"")
    {
      $str  = substr($str,1,strlen($str));
    }
    if (substr($str,-1,1)=="]")
    {
      $str  = substr($str,0,strlen($str)-1);
    }
    if (substr($str,-1,1)=="\"")
    {
      $str  = substr($str,0,strlen($str)-1);
    }
    return $str;
  } 
}

/*
  function luaparser(array, arrayStartIndex)
  
  recursive Function - it does the main work
*/
function luaparser($lua, &$pos){
	
			//print_r("<font color=blue>==>LUAPARSER FIRED</font><br>");
	$counter = 0;
  $parray = array();
  $stop = false;
  if ($pos < count($lua)) 
  {
    for ($i = $pos;$stop ==false;){
		
		$resetCounter = true;
		  if ($i >= count($lua)) { 
			$stop=true;
		  }
		  if (array_key_exists($i, $lua)) {
				$strs = explode("=",($lua[$i]));
				//var_dump($strs);
		  }
		  if (array_key_exists(1, $strs) && trim($strs[1]) == "{"){
			$i++;
			//print_r("<font color=red>CHILDING INTO ANOTHER LUAPARSER</font><br>");
			$parray[array_id(trim($strs[0]))]=luaparser($lua, $i);
			//print_r("<font color=red>ENDOF</font><br>");
		  } 
		  else if (trim($strs[0]) == "}" || trim($strs[0]) == "},")
		  {
			//$i--;
			$i++;
			$stop = true;
		  }
		  else
		  {
			$i++;
			if (strlen(array_id(trim($strs[0])))>0 && (array_key_exists(1, $strs) && strlen($strs[1])>0)) 
			{
			  $parray[array_id(trim($strs[0]))]=trimval($strs[1]);
				//var_dump('--------- '.array_id(trim($strs[0])).'=>'.trimval($strs[1]).' -----------');
			}
			else if (trim($strs[0]) != '{' && trim($strs[0]) != '['){
				$resetCounter = false;
				$parray[$counter]=trimval($strs[0]);
				//var_dump('--------- Adding keyless array element -----------');
			}
			else{
				$parray[array_id("Property".$i)]=luaparser($lua, $i);
				//echo "<font color=blue>".array_id("Property".$i)."</font>";
			}
		  } 
		  if ($resetCounter){
			  $counter = 0;
		  }
		  else{
			  $counter++;
		  }
    }
	$pos=$i;
  }
  return $parray;
}

/*
  function makePhpArray($input)
  
  thst the thing to call :-)
  
  $input can be 
    - an array with the lines of the LuaFile
    - a String with the whole LuaFile
    - a Filename
  
*/
function makePhpArray($input){
  $start = 0;
  if (is_array($input))
  {    
    return luaparser($input,$start);
  } 
  elseif (is_string($input))
  {
    if (is_file ( $input ))
    {
      return luaparser(file($input),$start);
    }
    else
    {
      return luaparser(explode("\n",$input),$start);
    }
  }  
}
?>
