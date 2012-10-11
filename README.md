Deviction
============

## Basic usage : ##

<code>
$deviction = new Deviction();

//This function will return the current device detected or selected.

$deviction->getDeviceFormat();

//This function will change the selected device.

$deviction->setDeviceFormat(Deviction::mobile);

//This static function will return the real detected device.

Deviction::getDetectedDeviceFormat();
</code>
