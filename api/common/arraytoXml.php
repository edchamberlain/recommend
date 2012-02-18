<?php

function serialise($objectIn) {

$reflectedObject = new ReflectionClass($objectIn);

$myObjName = strtolower($reflectedObject->name);

echo "<$myObjName>";

foreach (array_values($reflectedObject->getProperties()) as $method) {

$name = array();

$name[0] = $method->name;

$value = $objectIn->$name[0];

$valueType = gettype($value);

$name[1] = strtolower($name[0]);

if ($valueType == "array")

{

 echo "<$name[1] type=\"$valueType\" size=\"" . sizeof($objectIn->$name[0]) . "\">";



 foreach($objectIn->$name[0] as $item){

//if item can change to a string do this

$itemType = gettype($item);

if ($itemType == "string"){

 echo "<$name[1] type=\"$itemType\">";

 echo $item;

 echo "$name[1]>";

}else{

 toDataObj($item);

}

 }

 echo "$name[1]>";

}

else

{

 echo "<$name[1] type=\"$valueType\">";

 echo $value;

 echo "$name[1]>";

}

}

echo "$myObjName>";

}

?>