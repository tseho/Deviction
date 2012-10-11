Deviction
============

## Basic usage : ##

<code>
	$deviction = new Deviction();</br>
	//This function will return the current device detected or selected.</br>
	$deviction->getDeviceFormat();</br>
	//This function will change the selected device.</br>
	$deviction->setDeviceFormat(Deviction::mobile);</br>
	//This static function will return the real detected device.</br>
	Deviction::getDetectedDeviceFormat();</br>
</code>
