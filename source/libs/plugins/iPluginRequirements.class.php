<?php

/* Lightcast (TM) - PHP MVC Framework
 *
 * Copyright (C) 2007-2016 Nimasystems Ltd - All Rights Reserved
 *
 * Unauthorized copying of this file, via any medium is strictly prohibited without
 * the explicit knowledge and agreement of the rightful owner of the software - Nimasystems Ltd.
 *
 * Proprietary and confidential
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

interface iPluginRequirements
{
    /**
     * @return array
     */
    public function getRequiredPlugins();
}
