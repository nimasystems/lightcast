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

class lcPropelSchemaValidator
{
    private $xsd_filename;
    private $schema_filename;

    public function __construct($schema_filename, $xsd_filename)
    {
        $this->schema_filename = $schema_filename;
        $this->xsd_filename = $xsd_filename;
    }

    public function validate()
    {
        if (!class_exists('DOMDocument', false)) {
            throw new lcSystemException('You need DOM XML to validate the schema');
        }

        try {
            $xmldata = lcFiles::getFile($this->schema_filename);
        } catch (Exception $e) {
            throw new lcIOException('Cannot open the schema file');
        }

        try {
            $schema = new DOMDocument();
            $schema->loadXML($xmldata);
        } catch (Exception $e) {
            throw new lcSystemException('Error while loading schema xml: ' . $e->getMessage());
        }
        unset($xmldata);

        try {
            if (!$schema->schemaValidate($this->xsd_filename)) {
                throw new Exception('Incorrect schema');
            }
        } catch (Exception $e) {
            throw new lcSystemException('Cannot validate schema: ' . $e->getMessage());
        }

        unset($schema);

        return true;
    }
}