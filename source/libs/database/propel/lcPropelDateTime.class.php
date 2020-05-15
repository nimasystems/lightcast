<?php

class lcPropelDateTime extends PropelDateTime
{
    /**
     * Factory method to get a DateTime object from a temporal input
     *
     * @param mixed $value The value to convert (can be a string, a timestamp, or another DateTime)
     * @param DateTimeZone $timeZone (optional) timezone
     * @param string $dateTimeClass The class of the object to create, defaults to DateTime
     *
     * @return mixed null, or an instance of $dateTimeClass
     *
     * @throws PropelException
     */
    public static function newInstance($value, DateTimeZone $timeZone = null, $dateTimeClass = 'DateTime')
    {
        if ($value instanceof DateTime) {
            $value->setTimezone(new DateTimeZone('UTC'));
            return $value;
        }
        if ($value === null || $value === '') {
            // '' is seen as NULL for temporal objects
            // because DateTime('') == DateTime('now') -- which is unexpected
            return null;
        }
        try {
            if (self::isTimestamp($value)) { // if it's a unix timestamp
                $dateTimeObject = new $dateTimeClass('@' . $value, new DateTimeZone('UTC'));
                // timezone must be explicitly specified and then changed
                // because of a DateTime bug: http://bugs.php.net/bug.php?id=43003
                //$dateTimeObject->setTimeZone(new DateTimeZone(date_default_timezone_get()));
                //$value->setTimezone(new DateTimeZone('UTC'));
            } else {
                if ($timeZone === null) {
                    // stupid DateTime constructor signature
                    $dateTimeObject = new $dateTimeClass($value);
                } else {
                    $dateTimeObject = new $dateTimeClass($value, $timeZone);
                }
            }
        } catch (Exception $e) {
            throw new PropelException('Error parsing date/time value: ' . var_export($value, true), $e);
        }

        return $dateTimeObject;
    }
}