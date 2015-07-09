<?php
/*
 * Lightcast - A PHP MVC Framework
* Copyright (C) 2005 Nimasystems Ltd
*
* This program is NOT free software; you cannot redistribute and/or modify
* it's sources under any circumstances without the explicit knowledge and
* agreement of the rightful owner of the software - Nimasystems Ltd.
*
* This program is distributed WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
* PURPOSE.  See the LICENSE.txt file for more information.
*
* You should have received a copy of LICENSE.txt file along with this
* program; if not, write to:
* NIMASYSTEMS LTD
* Plovdiv, Bulgaria
* ZIP Code: 4000
* Address: 95 "Kapitan Raycho" Str.
* E-Mail: info@nimasystems.com


*/

/**
 * File Description
 * @package File Category
 * @subpackage File Subcategory
 * @changed $Id: lcSpatialUtils.class.php 1587 2015-05-07 15:18:38Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1587 $
 */
/*SELECT
 SQL_NO_CACHE
prop.prop_id, title, loc_lat, loc_lon,
111.045* DEGREES(ACOS(COS(RADIANS(latpoint))
		* COS(RADIANS(loc_lat))
		* COS(RADIANS(longpoint) - RADIANS(loc_lon))
		+ SIN(RADIANS(latpoint))
		* SIN(RADIANS(loc_lat)))) AS distance_in_km
FROM prop
LEFT JOIN prop_lang ON prop.prop_id = prop_lang.prop_id AND prop_lang.lang_id = 1
JOIN (
		SELECT  42.14044465642033  AS latpoint,  24.749234430491924 AS longpoint
) AS p
WHERE loc_lat
BETWEEN latpoint  - 24.6874363347888
AND latpoint  + 42.200114268428344
AND loc_lon
BETWEEN longpoint - 24.811032190918922
AND longpoint + 42.08071876222995
ORDER BY distance_in_km
LIMIT 15
*/

class lcSpatialUtils
{
    const EARTH_RADIUS = 6371;
    const DEG_KM = 111.045;

    const MILE_KM = 0.621371192;

    /**
     * @param lcLatLng[] $points
     * @return lcLatLng|null
     */
    public static function getCenter(array $points)
    {
        if (!$points) {
            return null;
        }

        $minlat = false;
        $minlng = false;
        $maxlat = false;
        $maxlng = false;

        foreach ($points as $data_element) {
            $data_coords = array($data_element->getLatitude(), $data_element->getLongtitude());

            if (isset($data_coords[1])) {
                if ($minlat === false) {
                    $minlat = $data_coords[0];
                } else {
                    $minlat = ($data_coords[0] < $minlat) ? $data_coords[0] : $minlat;
                }
                if ($maxlat === false) {
                    $maxlat = $data_coords[0];
                } else {
                    $maxlat = ($data_coords[0] > $maxlat) ? $data_coords[0] : $maxlat;
                }
                if ($minlng === false) {
                    $minlng = $data_coords[1];
                } else {
                    $minlng = ($data_coords[1] < $minlng) ? $data_coords[1] : $minlng;
                }
                if ($maxlng === false) {
                    $maxlng = $data_coords[1];
                } else {
                    $maxlng = ($data_coords[1] > $maxlng) ? $data_coords[1] : $maxlng;
                }
            }
        }

        $lat = $maxlat - (($maxlat - $minlat) / 2);
        $lng = $maxlng - (($maxlng - $minlng) / 2);

        $pt = new lcLatLng($lat, $lng);

        return $pt;
    }

    public static function calcDistance(lcLatLng $p1, lcLatLng $p2)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($p1->getLatitude());
        $lonFrom = deg2rad($p1->getLongtitude());
        $latTo = deg2rad($p2->getLatitude());
        $lonTo = deg2rad($p2->getLongtitude());

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        $ret = $angle * self::EARTH_RADIUS;
        return $ret;
    }

    public static function generateRandomPointKm($centre, $radius)
    {
        // 1km = 0.621371192 miles
        $radius_miles = $radius * self::MILE_KM;

        $ret = self::generateRandomPoint($centre, $radius_miles);

        return $ret;
    }

    /**
     * Given a $centre (latitude, longitude) co-ordinates and a
     * distance $radius (miles), returns a random point (latitude,longtitude)
     * which is within $radius miles of $centre.
     *
     * @param  array $centre Numeric array of floats. First element is
     *                       latitude, second is longitude.
     * @param  float $radius The radius (in miles).
     * @return array         Numeric array of floats (lat/lng). First
     *                       element is latitude, second is longitude.
     */
    public static function generateRandomPoint($centre, $radius)
    {

        $radius_earth = 3959; //miles

        //Pick random distance within $distance;
        $distance = lcg_value() * $radius;

        //Convert degrees to radians.
        $centre_rads = array_map('deg2rad', $centre);

        //First suppose our point is the north pole.
        //Find a random point $distance miles away
        $lat_rads = (pi() / 2) - $distance / $radius_earth;
        $lng_rads = lcg_value() * 2 * pi();


        //($lat_rads,$lng_rads) is a point on the circle which is
        //$distance miles from the north pole. Convert to Cartesian
        $x1 = cos($lat_rads) * sin($lng_rads);
        $y1 = cos($lat_rads) * cos($lng_rads);
        $z1 = sin($lat_rads);


        //Rotate that sphere so that the north pole is now at $centre.

        //Rotate in x axis by $rot = (pi()/2) - $centre_rads[0];
        $rot = (pi() / 2) - $centre_rads[0];
        $x2 = $x1;
        $y2 = $y1 * cos($rot) + $z1 * sin($rot);
        $z2 = -$y1 * sin($rot) + $z1 * cos($rot);

        //Rotate in z axis by $rot = $centre_rads[1]
        $rot = $centre_rads[1];
        $x3 = $x2 * cos($rot) + $y2 * sin($rot);
        $y3 = -$x2 * sin($rot) + $y2 * cos($rot);
        $z3 = $z2;


        //Finally convert this point to polar co-ords
        $lng_rads = atan2($x3, $y3);
        $lat_rads = asin($z3);

        return array_map('rad2deg', array($lat_rads, $lng_rads));
    }
}
