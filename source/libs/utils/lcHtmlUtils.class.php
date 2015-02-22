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
 * @changed $Id: lcHtmlUtils.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
*/

// handy script from tycoonmaster at gmail dot com
// http://fi2.php.net/manual/en/function.http-build-url.php#96335
// I didn't make this but I think it is useful so I store / share it here

if (!function_exists('http_build_url'))
{
	define('HTTP_URL_REPLACE', 1);				// Replace every part of the first URL when there's one of the second URL
	define('HTTP_URL_JOIN_PATH', 2);			// Join relative paths
	define('HTTP_URL_JOIN_QUERY', 4);			// Join query strings
	define('HTTP_URL_STRIP_USER', 8);			// Strip any user authentication information
	define('HTTP_URL_STRIP_PASS', 16);			// Strip any password authentication information
	define('HTTP_URL_STRIP_AUTH', 32);			// Strip any authentication information
	define('HTTP_URL_STRIP_PORT', 64);			// Strip explicit port numbers
	define('HTTP_URL_STRIP_PATH', 128);			// Strip complete path
	define('HTTP_URL_STRIP_QUERY', 256);		// Strip query string
	define('HTTP_URL_STRIP_FRAGMENT', 512);		// Strip any fragments (#identifier)
	define('HTTP_URL_STRIP_ALL', 1024);			// Strip anything but scheme and host

	// Build an URL
	// The parts of the second URL will be merged into the first according to the flags argument.
	//
	// @param	mixed			(Part(s) of) an URL in form of a string or associative array like parse_url() returns
	// @param	mixed			Same as the first argument
	// @param	int				A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
	// @param	array			If set, it will be filled with the parts of the composed url like parse_url() would return
	function http_build_url($url, array $parts = array(), $flags=HTTP_URL_REPLACE, &$new_url=false)
	{
		$parts = !empty($parts) ? $parts : array();

		$keys = array('user','pass','port','path','query','fragment');

		// HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
		if ($flags & HTTP_URL_STRIP_ALL)
		{
			$flags |= HTTP_URL_STRIP_USER;
			$flags |= HTTP_URL_STRIP_PASS;
			$flags |= HTTP_URL_STRIP_PORT;
			$flags |= HTTP_URL_STRIP_PATH;
			$flags |= HTTP_URL_STRIP_QUERY;
			$flags |= HTTP_URL_STRIP_FRAGMENT;
		}
		// HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
		else if ($flags & HTTP_URL_STRIP_AUTH)
		{
			$flags |= HTTP_URL_STRIP_USER;
			$flags |= HTTP_URL_STRIP_PASS;
		}

		// Parse the original URL
		$parse_url = parse_url($url);

		// Scheme and Host are always replaced
		if (isset($parts['scheme']))
		{
			$parse_url['scheme'] = $parts['scheme'];
		}

		if (isset($parts['host']))
		{
			$parse_url['host'] = $parts['host'];
		}

		// (If applicable) Replace the original URL with it's new parts
		if ($flags & HTTP_URL_REPLACE)
		{
			foreach ($keys as $key)
			{
				if (isset($parts[$key]))
				{
					$parse_url[$key] = $parts[$key];
				}
			}
		}
		else
		{
			// Join the original URL path with the new path
			if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH))
			{
				if (isset($parse_url['path']))
				{
					$parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/');
				}
				else
				{
					$parse_url['path'] = $parts['path'];
				}
			}

			// Join the original query string with the new query string
			if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY))
			{
				if (isset($parse_url['query']))
				{
					$parse_url['query'] .= '&' . $parts['query'];
				}
				else
				{
					$parse_url['query'] = $parts['query'];
				}
			}
		}

		// Strips all the applicable sections of the URL
		// Note: Scheme and Host are never stripped
		foreach ($keys as $key)
		{
			if ($flags & (int)constant('HTTP_URL_STRIP_' . strtoupper($key)))
			{
				unset($parse_url[$key]);
			}
		}


		$new_url = $parse_url;

		return
		((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '')
		.((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') .'@' : '')
		.((isset($parse_url['host'])) ? $parse_url['host'] : '')
		.((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
		.((isset($parse_url['path'])) ? $parse_url['path'] : '')
		.((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
		.((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '')
		;
	}
}

class lcHtmlUtils
{
	// source: http://nadeausoftware.com/articles/2008/05/php_tip_how_parse_and_build_urls
	public static function splitUrl( $url, $decode = true)
	{
		$xunressub     = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
		$xpchar        = $xunressub . ':@%';

		$xscheme       = '([a-zA-Z][a-zA-Z\d+-.]*)';

		$xuserinfo     = '((['  . $xunressub . '%]*)' .
				'(:([' . $xunressub . ':%]*))?)';

		$xipv4         = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';

		$xipv6         = '(\[([a-fA-F\d.:]+)\])';

		$xhost_name    = '([a-zA-Z\d-.%]+)';

		$xhost         = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
		$xport         = '(\d*)';
		$xauthority    = '((' . $xuserinfo . '@)?' . $xhost .
		'?(:' . $xport . ')?)';

		$xslash_seg    = '(/[' . $xpchar . ']*)';
		$xpath_authabs = '((//' . $xauthority . ')((/[' . $xpchar . ']*)*))';
		$xpath_rel     = '([' . $xpchar . ']+' . $xslash_seg . '*)';
		$xpath_abs     = '(/(' . $xpath_rel . ')?)';
		$xapath        = '(' . $xpath_authabs . '|' . $xpath_abs .
		'|' . $xpath_rel . ')';

		$xqueryfrag    = '([' . $xpchar . '/?' . ']*)';

		$xurl          = '^(' . $xscheme . ':)?' .  $xapath . '?' .
				'(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';


		$parts = array();

		$m = null;

		// Split the URL into components.
		if (!preg_match('!' . $xurl . '!', $url, $m))
		{
			return false;
		}

		if (!empty($m[2]))
		{
			$parts['scheme']  = strtolower($m[2]);
		}

		if ( !empty($m[7]) )
		{
			if ( isset( $m[9] ) )
			{
				$parts['user']    = $m[9];
			}
			else
			{
				$parts['user']    = '';
			}
		}

		if ( !empty($m[10]) )
		{
			$parts['pass']    = $m[11];
		}

		if ( !empty($m[13]) )
		{
			$h=$parts['host'] = $m[13];
		}
		else if ( !empty($m[14]) )
		{
			$parts['host']    = $m[14];
		}
		else if ( !empty($m[16]) )
		{
			$parts['host']    = $m[16];
		}
		else if ( !empty( $m[5] ) )
		{
			$parts['host']    = '';
		}

		if ( !empty($m[17]) )
		{
			$parts['port']    = $m[18];
		}

		if ( !empty($m[19]) )
		{
			$parts['path']    = $m[19];
		}
		else if ( !empty($m[21]) )
		{
			$parts['path']    = $m[21];
		}
		else if ( !empty($m[25]) )
		{
			$parts['path']    = $m[25];
		}

		if ( !empty($m[27]) )
		{
			$parts['query']   = $m[28];
		}

		if ( !empty($m[29]) )
		{
			$parts['fragment']= $m[30];
		}

		if ( !$decode )
		{
			return $parts;
		}

		if ( !empty($parts['user']) )
		{
			$parts['user']     = rawurldecode($parts['user']);
		}

		if ( !empty($parts['pass']) )
		{
			$parts['pass']     = rawurldecode($parts['pass']);
		}

		if ( !empty($parts['path']) )
		{
			$parts['path']     = rawurldecode($parts['path']);
		}

		if ( isset($h) )
		{
			$parts['host']     = rawurldecode($parts['host']);
		}

		if ( !empty($parts['query']) )
		{
			$parts['query']    = rawurldecode($parts['query']);
		}

		if ( !empty($parts['fragment']) )
		{
			$parts['fragment'] = rawurldecode($parts['fragment']);
		}

		return $parts;
	}

	// source: http://nadeausoftware.com/articles/2008/05/php_tip_how_parse_and_build_urls
	public static function joinUrl( $parts, $encode = true)
	{
		if ( $encode )
		{
			if ( isset( $parts['user'] ) )
			{
				$parts['user']     = rawurlencode($parts['user']);
			}
				
			if ( isset( $parts['pass'] ) )
			{
				$parts['pass']     = rawurlencode($parts['pass']);
			}
				
			if ( isset( $parts['host'] ) &&
					!preg_match('!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host']))
			{
				$parts['host']     = rawurlencode($parts['host']);
			}
				
			if ( !empty( $parts['path'] ) )
			{
				$parts['path']     = preg_replace( '!%2F!ui', '/',
						rawurlencode($parts['path']));
			}
				
			if ( isset( $parts['query'] ) )
			{
				$parts['query']    = rawurlencode($parts['query']);
			}
				
			if ( isset( $parts['fragment'] ) )
			{
				$parts['fragment'] = rawurlencode($parts['fragment']);
			}
		}

		$url = '';

		if ( !empty( $parts['scheme'] ) )
		{
			$url .= $parts['scheme'] . ':';
		}

		if ( isset( $parts['host'] ) )
		{
			$url .= '//';
			if ( isset( $parts['user'] ) )
			{
				$url .= $parts['user'];

				if ( isset( $parts['pass'] ) )
				{
					$url .= ':' . $parts['pass'];
				}

				$url .= '@';
			}
				
			if (preg_match('!^[\da-f]*:[\da-f.:]+$!ui', $parts['host']))
			{
				$url .= '[' . $parts['host'] . ']'; // IPv6
			}
			else
			{
				$url .= $parts['host'];             // IPv4 or name
			}
				
			if ( isset( $parts['port'] ) )
			{
				$url .= ':' . $parts['port'];
			}
				
			if ( !empty( $parts['path'] ) && $parts['path'][0] != '/' )
			{
				$url .= '/';
			}
		}

		if ( !empty( $parts['path'] ) )
		{
			$url .= $parts['path'];
		}

		if ( isset( $parts['query'] ) )
		{
			$url .= '?' . $parts['query'];
		}

		if ( isset( $parts['fragment'] ) )
		{
			$url .= '#' . $parts['fragment'];
		}

		return $url;
	}
	public static function httpBuildUrl($url, $parts = array(), $flags=HTTP_URL_REPLACE, &$new_url = false)
	{
		return http_build_url($url, $parts, $flags, $new_url);
	}

	public static function removeAnchors($content)
	{
		$content = preg_replace("/<a(.*?)>(.*?)<\/a>/", '\\2', $content);

		return $content;
	}

	// retrieve doctype of document
	public static function getDoctype($file)
	{
		$h1tags = preg_match('/<!DOCTYPE (\w.*)dtd">/is',$file,$patterns);

		if (!$h1tags)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[0]);
		array_push($res,count($patterns[0]));

		return $res;
	}

	// retrieve page title
	public static function getDocTitle($file)
	{
		$h1tags = preg_match('/<title> ?.* <\/title>/isx',$file,$patterns);

		if (!$h1tags)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[0]);
		array_push($res,count($patterns[0]));

		return $res;
	}

	// retrieve keywords
	public static function getKeywords($file)
	{
		$h1tags = preg_match('/(<meta name="keywords" content="(.*)" \/>)/i',$file,$patterns);

		if (!$h1tags)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// get rel links in header of the site
	public static function getLinkRel($file)
	{
		$h1tags = preg_match_all('/(rel=)(".*") href=(".*")/im',$file,$patterns);

		if (!$h1tags)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns);
		array_push($res,count($patterns[2]));

		return $res;
	}

	public static function getExternalCss($file)
	{
		$h1tags = preg_match_all('/(href=")(\w.*\.css)"/i',$file,$patterns);

		if (!$h1tags)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve all h1 tags
	public static function getH1($file)
	{
		$h1tags = preg_match_all("/(<h1.*>)(\w.*)(<\/h1>)/isxmU",$file,$patterns);

		if (!$h1tags)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve all h2 tags
	public static function getH2($file)
	{
		$h1tags = preg_match_all("/(<h2.*>)(\w.*)(<\/h2>)/isxmU",$file,$patterns);

		if (!$h1tags)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve all h3 tags
	public static function getH3($file)
	{
		$h1tags = preg_match_all("/(<h3.*>)(\w.*)(<\/h3>)/ismU",$file,$patterns);

		if (!$h1tags)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve all h4 tags
	public static function getH4($file)
	{
		$h1tags = preg_match_all("/(<h4.*>)(\w.*)(<\/h4>)/ismU",$file,$patterns);

		if (!$h1tags)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve all h5 tags
	public static function getH5($file)
	{
		$h1tags = preg_match_all("/(<h5.*>)(\w.*)(<\/h5>)/ismU",$file,$patterns);

		if (!$h1tags)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve all h5 tags
	public static function getH6($file)
	{
		$h1tags = preg_match_all("/(<h6.*>)(\w.*)(<\/h6>)/ismU",$file,$patterns);

		if (!$h1tags)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve p tag contents
	public static function getP($file)
	{
		$h1tags = preg_match_all("/(<p.*>)(\w.*)(<\/p>)/ismU",$file,$patterns);

		if (!$h1tags)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve names of links
	public static function getAContent($file)
	{
		$h1count = preg_match_all("/(<a.*>)(\w.*)(<.*>)/ismU",$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		return $patterns[2];
	}

	// retrieve link destinations
	public static function getAHref($file)
	{
		$h1count = preg_match_all('/(href=")(.*?)(")/i',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		return $patterns[2];
	}

	// get count of href's
	public static function getAHrefCount($file)
	{
		$h1count = preg_match_all('/<(a.*) href=\"(.*?)\"(.*)<\/a>/',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		return count($patterns[0]);
	}

	//get all additional tags inside a link tag
	public static function getAAdditionaltags($file)
	{
		$h1count = preg_match_all('/<(a.*) href="(.*?)"(.*)>(.*)(<\/a>)/',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		return $patterns[3];
	}

	// retrieve span's
	public static function getSpan($file)
	{
		$h1count = preg_match_all('/(<span .*>)(.*)(<\/span>)/',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve spans on the site
	public static function getScript($file)
	{
		$h1count = preg_match_all('/(<script.*>)(.*)(<\/script>)/imxsU',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve content of ul's
	public static function getUl($file)
	{
		$h1count = preg_match_all('/(<ul \w*>)(.*)(<\/ul>)/ismxU',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	//retrieve li contents
	public static function getLi($file)
	{
		$h1count = preg_match_all('/(<li \w*>)(.*)(<\/li>)/ismxU',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve page comments
	public static function getComments($file)
	{
		$h1count = preg_match_all('/(<!--).(.*)(-->)/isU',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve all used id's on the page
	public static function getIds($file)
	{
		$h1count = preg_match_all('/(id="(\w*)")/is',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve all used classes ( inline ) of the document
	public static function getClasses($file)
	{
		$h1count = preg_match_all('/(class="(\w*)")/is',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// get the meta tag contents
	public static function getMetaContent($file)
	{
		$h1count = preg_match_all('/(<meta)(.*="(.*)").\/>/ix',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// get inline styles
	public static function getStyles($file)
	{
		$h1count = preg_match_all('/(style=")(.*?)(")/is',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// get titles of tags
	public static function getTagTitles($file)
	{
		$h1count = preg_match_all('/(title=)"(.*)"(.*)/',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// get image alt descriptions
	public static function getImageAlt($file)
	{
		$h1count = preg_match_all('/(alt=.)([a-zA-Z0-9\s]{1,})/',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[2]);
		array_push($res,count($patterns[2]));

		return $res;
	}

	// retrieve images on the site
	public static function getImages($file)
	{
		$h1count = preg_match_all('/(<img)\s (src="([a-zA-Z0-9\.;:\/\?&=_|\r|\n]{1,})")/isxmU',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[3]);
		array_push($res,count($patterns[3]));

		return $res;
	}

	// retrieve email address of the mailto tag if any
	public static function getMailto($file)
	{
		$h1count = preg_match_all('/(<a\shref=")(mailto:)([a-zA-Z@0-9\.]{1,})"/ims',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[3]);
		array_push($res,count($patterns[3]));

		return $res;
	}

	// retrieve any email
	public static function getEmails($file)
	{
		$h1count = preg_match_all('/[a-zA-Z0-9_-]{1,}@[a-zA-Z0-9-_]{1,}\.[a-zA-Z]{1,4}/',$file,$patterns);

		if (!$h1count)
		{
			return false;
		}

		$res = array();

		array_push($res,$patterns[0]);
		array_push($res,count($patterns[0]));

		return $res;
	}

	// count used keywords
	public static function countKeyword($word,$file)
	{
		$x = preg_match_all("/(.*)($word)(.*)/",$file,$patterns);

		if (!$x)
		{
			return false;
		}

		return count($patterns);
	}

	// retrieve internal site links
	public static function getInternalLinks($array)
	{
		$result = array();
		$count = count($array);

		for($i=0;$i<$count;$i++)
		{
			if(!empty($array[$i]))
			{
				if(strpos($array[$i],"www",0) === false)
				{
					if(strpos($array[$i],"http",0) === false)
					{
						array_push($result,$array[$i]);
					}
				}
			}
		}

		return $result;
	}

	// retrieve external links
	public static function getExternalLinks($array)
	{
		$result = array();
		$count = count($array);

		for($i=0;$i<$count;$i++)
		{
			if(!empty($array[$i]))
			{
				if(strpos($array[$i],"www",0) !== false)
				{
					if(strpos($array[$i],"http",0) !== false)
					{
						array_push($result,$array[$i]);
					}
				}
			}
		}

		return $result;
	}

	// retrieve the main url of the site
	public static function getMainUrl($url)
	{
		$parts = parse_url($url);

		$url = $parts["scheme"] ."://".$parts["host"];

		return $url;
	}

	// retrieve just the name without www and com/eu/de etc
	public static function getDomainNameOnly($url)
	{
		$match = preg_match("/(.*:\/\/)\w{0,}(.*)\.(.*)/",$url,$patterns);

		if (!$match)
		{
			return false;
		}

		$patterns[2] = str_replace(".","",$patterns[2]);

		return $patterns[2];
	}
}


?>