<?php

class lcMath
{
	public static function bigRand($length)
	{
		$length = (int)$length;
		
		$ret = '';

		srand();

		for($i = 0; $i < $length; $i++)
		{
			$ret .= rand(0,9);
		}

		return $ret;
	}
}


?>