<?php

class Date
{
    public static function ago ($datetime)
    {
        $estimate_time = time() - $datetime;
        
        if( $estimate_time < 1 )
            return 'right now';
        
        $condition = array(
            12 * 30 * 24 * 60 * 60  =>  'año',
            30 * 24 * 60 * 60       =>  'mes',
            24 * 60 * 60            =>  'dia',
            60 * 60                 =>  'hora',
            60                      =>  'minuto',
            1                       =>  'segundo');
            
        foreach ($condition as $secs => $secs_as_str)
        {
            $time_ago = $estimate_time / $secs;
            
            if ($time_ago >= 1)
            {
                $rounded_time = round ($time_ago);
                return $rounded_time . ' ' . $secs_as_str . ($rounded_time > 1 ? 's' : '') . ' atrás';
            }
        }
    }

	public static function datetime ($datetime)
	{
		return date("Y-m-d\TH:i:s", $datetime);
	}

	public static function title ($datetime)
	{
		return date("M\\ j,\\ Y,\\ g:s\\ A\\ e", $datetime);
	}
    
}

