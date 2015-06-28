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
 * @changed $Id: lcMoFile.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
* @version $Revision: 1592 $
*/

class lcMoFile extends lcObj
{
	private $fp;
	private $big_endian;

	private $filename;

	private $headers = array();
	private $messages = array();

	public function __construct($filename = null)
	{
		parent::__construct();

		if (isset($filename))
		{
			$this->openFile($filename);
		}
	}

	public function __destruct()
	{
		if ($this->fp)
		{
			@flock($this->fp, LOCK_UN);
			if ($this->fp)
			{
				@fclose($this->fp);
			}
			unset($this->fp);
		}

		parent::__destruct();
	}

	public function openFile($filename)
	{
		$this->filename = $filename;
		$this->fp = null;
		$this->messages = array();
		$this->headers = array();

		try
		{
			$this->fp = fopen($filename, 'rb');

			flock($this->fp, LOCK_SH);

			$unpacked = unpack('c', $this->read(4));
			$magic = array_shift($unpacked);
			$big_endian = false;

			switch($magic)
			{
				case -34:
					{
						$big_endian = false;
						break;
					}
				case -107:
					{
						$big_endian = true;
						break;
					}
				default:
					{
						throw new lcIOException('Invalid MO File. Error on finding endian type');
						break;
					}
			}

			unset($unpacked, $magic);

			$revision = $this->readInt($big_endian);

			if ($revision != 0)
			{
				throw new lcIOException('Unsupported MO format');
			}

			$count = $this->readInt($big_endian);

			$offset_original = $this->readInt($big_endian);
			$offset_translat = $this->readInt($big_endian);

			fseek($this->fp, $offset_original);

			unset($offset_original);

			$original = array();

			for ($i=0; $i<$count;$i++)
			{
				$original[$i] = array(
						'length' => $this->readInt($big_endian),
						'offset' => $this->readInt($big_endian)
				);
			}

			fseek($this->fp, $offset_translat);

			unset($offset_translat);

			$translat = array();

			for($i=0;$i<$count;$i++)
			{
				$translat[$i] = array(
						'length' => $this->readInt($big_endian),
						'offset' => $this->readInt($big_endian)
				);
			}

			for($i=0;$i<$count;$i++)
			{
				$this->messages[$this->readStr($original[$i])] =
				$this->readStr($translat[$i]);
			}

			unset($original, $translat);

			@flock($this->fp, LOCK_UN);
			if ($this->fp) 
			{
				@fclose($this->fp);
			}
			$this->fp = null;

			// headers
			if (isset($this->messages['']))
			{
				$this->headers = $this->convertStrHeadersToArray($this->messages['']);
				unset($this->messages['']);
			}
		}
		catch(Exception $e)
		{
			@flock($this->fp, LOCK_UN);
			if ($this->fp) 
			{
				@fclose($this->fp);
			}
			throw new lcIOException('Error while reading MO file: ' . $e->getMessage(), null, $e);
		}
	}

	public function setMessages(array $messages)
	{
		$this->messages = $messages;
	}

	public function setHeaders(array $headers)
	{
		$this->headers = $headers;
	}

	public function getMessages()
	{
		return (array)$this->messages;
	}

	public function getHeaders()
	{
		return (array)$this->headers;
	}

	public function toPo($filename = null)
	{
		if (!isset($filename) && (!$this->filename))
		{
			throw new lcSystemException('Cannot convert MO to PO - no filename set');
		}

		if (!isset($filename))
		{
			$f = basename($this->filename);
			$f = lcFiles::splitFileName($f);
			$filename = dirname($this->filename) . DS . $f['name'] . '.po';
			unset($f);
		}

		$po = new lcPoFile();
		$po->setMessages($this->messages);
		$po->setHeaders($this->headers);
		$po->save($filename);

		unset($po);
	}

	private function readInt($big_endian = false)
	{
		$tmp = unpack($big_endian ? 'N' : 'V', $this->read(4));
		return array_shift($tmp);
	}

	private function writeInt($int)
	{
		$this->write(pack($this->big_endian ? 'N' : 'V', (int)$int));
	}

	private function read($bytes = 1)
	{
		if (0 < $bytes = abs($bytes))
		{
			return fread($this->fp, $bytes);
		}

		return null;
	}

	private function readStr($params)
	{
		fseek($this->fp, $params['offset']);

		return $this->read($params['length']);
	}

	private function write($data)
	{
		fwrite($this->fp, $data);
	}

	private function writeStr($string)
	{
		return $this->write($string . "\0");
	}

	private function convertStrHeadersToArray($str)
	{
		$tmp = array();
		$ex = explode("\n", $str);

		foreach($ex as $item)
		{
			if (!$item = trim($item)) 
			{
				continue;
			}

			list($key, $value) = explode(':', $item, 2);
			$tmp[trim($key)] = trim($value);
		}

		unset($ex);

		return $tmp;
	}

	public function save($filename = null)
	{
		if (!$filename && !$this->filename)
		{
			throw new lcSystemException('Cannot save MO file - no filename set');
		}

		$file = isset($filename) ? $filename : $this->filename;

		try
		{
			$this->fp = fopen($file, 'wb');

			flock($this->fp, LOCK_EX);

			if ($this->big_endian)
			{
				$this->write(pack('c*', 0x95, 0x04, 0x12, 0xde));
			}
			else
			{
				$this->write(pack('c*', 0xde, 0x12, 0x04, 0x95));
			}

			$this->writeInt(0);

			$count = count($this->messages) + ($headers = (count($this->headers) ? 1 : 0));

			$this->writeInt($count);

			$offset = 28;
			$this->writeInt($offset);

			$offset += ($count * 8);
			$this->writeInt($offset);

			$this->writeInt(0);

			$offset += ($count * 8);
			$this->writeInt($offset);

			if ($headers)
			{
				$headers = '';

				foreach($this->headers as $name => $value)
				{
					$headers .= $name . ': ' . $value . "\n";
					unset($name, $value);
				}

				$messages = array('' => $headers) + $this->messages;
			}
			else
			{
				$messages = $this->messages;
			}

			$ak = array_keys($messages);

			foreach($ak as $tmp)
			{
				$len = strlen($tmp);
				$this->writeInt($len);
				$this->writeInt($offset);
				$offset += $len + 1;

				unset($tmp, $len);
			}

			unset($ak);

			foreach($messages as $tmp)
			{
				$len = strlen($tmp);
				$this->writeInt($len);
				$this->writeInt($offset);
				$offset += $len + 1;

				unset($tmp, $len);
			}

			$ak = array_keys($messages);

			foreach($ak as $tmp)
			{
				$this->writeStr($tmp);
				unset($tmp);
			}

			unset($ak);

			foreach($messages as $tmp)
			{
				$this->writeStr($tmp);
				unset($tmp);
			}

			unset($count, $headers, $offset, $messages);

			@flock($this->fp, LOCK_UN);
			if ($this->fp) 
			{
				@fclose($this->fp);
			}
			$this->fp = null;
		}
		catch(Exception $e)
		{
			@flock($this->fp, LOCK_UN);
			if ($this->fp) 
			{
				@fclose($this->fp);
			}
			$this->fp = null;

			throw new lcIOException('Cannot write to MO file: ' . $e->getMessage(), null, $e);
		}

		unset($file);
	}
}

?>