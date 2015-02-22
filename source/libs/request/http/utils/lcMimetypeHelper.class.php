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
 * @changed $Id: lcMimetypeHelper.class.php 1455 2013-10-25 20:29:31Z mkovachev $
* @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

class lcMimetypeHelper
{
	public static function isMimetype($input)
	{
		if (strpos($input,'/') === false) 
		{
			return false;
		}

		if (!in_array($input,array_keys(lcMimetypes::getList()))) 
		{
			return false;
		}

		return true;
	}

	public static function findMimeByExt($ext)
	{
		if (!$ext) 
		{
			return false;
		}

		$ext = lcFiles::fix_file_ext($ext);

		$list = self::full_list();

		foreach ($list as $key=>$exts)
		{
			if (@!in_array($ext,$exts)) 
			{
				continue;
			}
				
			return $key;
		}
	}

	public static function findExtsByMime($mimetype)
	{
		if (!$mimetype) 
		{
			return false;
		}

		$list = self::full_list();

		return $list[$mimetype];
	}

	public static function getList()
	{

		$mimetypes = array(
				'application/andrew-inset' =>
				array(
						0 => 'ez'
				),
				'application/atom+xml' =>
				array(
						0 => 'atom'
				),
				'application/atomcat+xml' =>
				array(
						0 => 'atomcat'
				),
				'application/atomsvc+xml' =>
				array(
						0 => 'atomsvc'
				),
				'application/ccxml+xml' =>
				array(
						0 => 'ccxml'
				),
				'application/davmount+xml' =>
				array(
						0 => 'davmount'
				),
				'application/ecmascript' =>
				array(
						0 => 'ecma'
				),
				'application/font-tdpfr' =>
				array(
						0 => 'pfr'
				),
				'application/hyperstudio' =>
				array(
						0 => 'stk'
				),
				'application/javascript' =>
				array(
						0 => 'js'
				),
				'application/json' =>
				array(
						0 => 'json'
				),
				'application/mac-binhex40' =>
				array(
						0 => 'hqx'
				),
				'application/mac-compactpro' =>
				array(
						0 => 'cpt'
				),
				'application/marc' =>
				array(
						0 => 'mrc'
				),
				'application/mathematica' =>
				array(
						0 => 'ma',
						1 => 'nb',
						2 => 'mb'
				),
				'application/mathml+xml' =>
				array(
						0 => 'mathml'
				),
				'application/mbox' =>
				array(
						0 => 'mbox'
				),
				'application/mediaservercontrol+xml' =>
				array(
						0 => 'mscml'
				),
				'application/mp4' =>
				array(
						0 => 'mp4s'
				),
				'application/msword' =>
				array(
						0 => 'doc',
						1 => 'dot'
				),
				'application/mxf' =>
				array(
						0 => 'mxf'
				),
				'application/oda' =>
				array(
						0 => 'oda'
				),
				'application/ogg' =>
				array(
						0 => 'ogg'
				),
				'application/pdf' =>
				array(
						0 => 'pdf'
				),
				'application/pgp-encrypted' =>
				array(
						0 => 'pgp'
				),
				'application/pgp-signature' =>
				array(
						0 => 'asc',
						1 => 'sig'
				),
				'application/pics-rules' =>
				array(
						0 => 'prf'
				),
				'application/pkcs10' =>
				array(
						0 => 'p10'
				),
				'application/pkcs7-mime' =>
				array(
						0 => 'p7m',
						1 => 'p7c'
				),
				'application/pkcs7-signature' =>
				array(
						0 => 'p7s'
				),
				'application/pkix-cert' =>
				array(
						0 => 'cer'
				),
				'application/pkix-crl' =>
				array(
						0 => 'crl'
				),
				'application/pkix-pkipath' =>
				array(
						0 => 'pkipath'
				),
				'application/pkixcmp' =>
				array(
						0 => 'pki'
				),
				'application/pls+xml' =>
				array(
						0 => 'pls'
				),
				'application/postscript' =>
				array(
						0 => 'ai',
						1 => 'eps',
						2 => 'ps'
				),
				'application/prs.cww' =>
				array(
						0 => 'cww'
				),
				'application/rdf+xml' =>
				array(
						0 => 'rdf'
				),
				'application/reginfo+xml' =>
				array(
						0 => 'rif'
				),
				'application/relax-ng-compact-syntax' =>
				array(
						0 => 'rnc'
				),
				'application/resource-lists+xml' =>
				array(
						0 => 'rl'
				),
				'application/rls-services+xml' =>
				array(
						0 => 'rs'
				),
				'application/rsd+xml' =>
				array(
						0 => 'rsd'
				),
				'application/rss+xml' =>
				array(
						0 => 'rss'
				),
				'application/rtf' =>
				array(
						0 => 'rtf'
				),
				'application/sbml+xml' =>
				array(
						0 => 'sbml'
				),
				'application/scvp-cv-request' =>
				array(
						0 => 'scq'
				),
				'application/scvp-cv-response' =>
				array(
						0 => 'scs'
				),
				'application/scvp-vp-request' =>
				array(
						0 => 'spq'
				),
				'application/scvp-vp-response' =>
				array(
						0 => 'spp'
				),
				'application/sdp' =>
				array(
						0 => 'sdp'
				),
				'application/set-payment-initiation' =>
				array(
						0 => 'setpay'
				),
				'application/set-registration-initiation' =>
				array(
						0 => 'setreg'
				),
				'application/shf+xml' =>
				array(
						0 => 'shf'
				),
				'application/smil+xml' =>
				array(
						0 => 'smi',
						1 => 'smil'
				),
				'application/sparql-query' =>
				array(
						0 => 'rq'
				),
				'application/sparql-results+xml' =>
				array(
						0 => 'srx'
				),
				'application/srgs' =>
				array(
						0 => 'gram'
				),
				'application/srgs+xml' =>
				array(
						0 => 'grxml'
				),
				'application/ssml+xml' =>
				array(
						0 => 'ssml'
				),
				'application/vnd.3gpp.pic-bw-large' =>
				array(
						0 => 'plb'
				),
				'application/vnd.3gpp.pic-bw-small' =>
				array(
						0 => 'psb'
				),
				'application/vnd.3gpp.pic-bw-var' =>
				array(
						0 => 'pvb'
				),
				'application/vnd.3gpp2.tcap' =>
				array(
						0 => 'tcap'
				),
				'application/vnd.3m.post-it-notes' =>
				array(
						0 => 'pwn'
				),
				'application/vnd.accpac.simply.aso' =>
				array(
						0 => 'aso'
				),
				'application/vnd.accpac.simply.imp' =>
				array(
						0 => 'imp'
				),
				'application/vnd.acucobol' =>
				array(
						0 => 'acu'
				),
				'application/vnd.acucorp' =>
				array(
						0 => 'atc',
						1 => 'acutc'
				),
				'application/vnd.adobe.xdp+xml' =>
				array(
						0 => 'xdp'
				),
				'application/vnd.adobe.xfdf' =>
				array(
						0 => 'xfdf'
				),
				'application/vnd.amiga.ami' =>
				array(
						0 => 'ami'
				),
				'application/vnd.anser-web-certificate-issue-initiation' =>
				array(
						0 => 'cii'
				),
				'application/vnd.anser-web-funds-transfer-initiation' =>
				array(
						0 => 'fti'
				),
				'application/vnd.antix.game-component' =>
				array(
						0 => 'atx'
				),
				'application/vnd.apple.installer+xml' =>
				array(
						0 => 'mpkg'
				),
				'application/vnd.audiograph' =>
				array(
						0 => 'aep'
				),
				'application/vnd.blueice.multipass' =>
				array(
						0 => 'mpm'
				),
				'application/vnd.bmi' =>
				array(
						0 => 'bmi'
				),
				'application/vnd.businessobjects' =>
				array(
						0 => 'rep'
				),
				'application/vnd.chemdraw+xml' =>
				array(
						0 => 'cdxml'
				),
				'application/vnd.chipnuts.karaoke-mmd' =>
				array(
						0 => 'mmd'
				),
				'application/vnd.cinderella' =>
				array(
						0 => 'cdy'
				),
				'application/vnd.claymore' =>
				array(
						0 => 'cla'
				),
				'application/vnd.clonk.c4group' =>
				array(
						0 => 'c4g',
						1 => 'c4d',
						2 => 'c4f',
						3 => 'c4p',
						4 => 'c4u'
				),
				'application/vnd.commonspace' =>
				array(
						0 => 'csp',
						1 => 'cst'
				),
				'application/vnd.contact.cmsg' =>
				array(
						0 => 'cdbcmsg'
				),
				'application/vnd.cosmocaller' =>
				array(
						0 => 'cmc'
				),
				'application/vnd.crick.clicker' =>
				array(
						0 => 'clkx'
				),
				'application/vnd.crick.clicker.keyboard' =>
				array(
						0 => 'clkk'
				),
				'application/vnd.crick.clicker.palette' =>
				array(
						0 => 'clkp'
				),
				'application/vnd.crick.clicker.template' =>
				array(
						0 => 'clkt'
				),
				'application/vnd.crick.clicker.wordbank' =>
				array(
						0 => 'clkw'
				),
				'application/vnd.criticaltools.wbs+xml' =>
				array(
						0 => 'wbs'
				),
				'application/vnd.ctc-posml' =>
				array(
						0 => 'pml'
				),
				'application/vnd.cups-ppd' =>
				array(
						0 => 'ppd'
				),
				'application/vnd.curl' =>
				array(
						0 => 'curl'
				),
				'application/vnd.data-vision.rdz' =>
				array(
						0 => 'rdz'
				),
				'application/vnd.denovo.fcselayout-link' =>
				array(
						0 => 'fe_launch'
				),
				'application/vnd.dna' =>
				array(
						0 => 'dna'
				),
				'application/vnd.dolby.mlp' =>
				array(
						0 => 'mlp'
				),
				'application/vnd.dpgraph' =>
				array(
						0 => 'dpg'
				),
				'application/vnd.dreamfactory' =>
				array(
						0 => 'dfac'
				),
				'application/vnd.ecowin.chart' =>
				array(
						0 => 'mag'
				),
				'application/vnd.enliven' =>
				array(
						0 => 'nml'
				),
				'application/vnd.epson.esf' =>
				array(
						0 => 'esf'
				),
				'application/vnd.epson.msf' =>
				array(
						0 => 'msf'
				),
				'application/vnd.epson.quickanime' =>
				array(
						0 => 'qam'
				),
				'application/vnd.epson.salt' =>
				array(
						0 => 'slt'
				),
				'application/vnd.epson.ssf' =>
				array(
						0 => 'ssf'
				),
				'application/vnd.eszigno3+xml' =>
				array(
						0 => 'es3',
						1 => 'et3'
				),
				'application/vnd.ezpix-album' =>
				array(
						0 => 'ez2'
				),
				'application/vnd.ezpix-package' =>
				array(
						0 => 'ez3'
				),
				'application/vnd.fdf' =>
				array(
						0 => 'fdf'
				),
				'application/vnd.flographit' =>
				array(
						0 => 'gph'
				),
				'application/vnd.fluxtime.clip' =>
				array(
						0 => 'ftc'
				),
				'application/vnd.framemaker' =>
				array(
						0 => 'fm',
						1 => 'frame',
						2 => 'maker'
				),
				'application/vnd.frogans.fnc' =>
				array(
						0 => 'fnc'
				),
				'application/vnd.frogans.ltf' =>
				array(
						0 => 'ltf'
				),
				'application/vnd.fsc.weblaunch' =>
				array(
						0 => 'fsc'
				),
				'application/vnd.fujitsu.oasys' =>
				array(
						0 => 'oas'
				),
				'application/vnd.fujitsu.oasys2' =>
				array(
						0 => 'oa2'
				),
				'application/vnd.fujitsu.oasys3' =>
				array(
						0 => 'oa3'
				),
				'application/vnd.fujitsu.oasysgp' =>
				array(
						0 => 'fg5'
				),
				'application/vnd.fujitsu.oasysprs' =>
				array(
						0 => 'bh2'
				),
				'application/vnd.fujixerox.ddd' =>
				array(
						0 => 'ddd'
				),
				'application/vnd.fujixerox.docuworks' =>
				array(
						0 => 'xdw'
				),
				'application/vnd.fujixerox.docuworks.binder' =>
				array(
						0 => 'xbd'
				),
				'application/vnd.fuzzysheet' =>
				array(
						0 => 'fzs'
				),
				'application/vnd.genomatix.tuxedo' =>
				array(
						0 => 'txd'
				),
				'application/vnd.google-earth.kml+xml' =>
				array(
						0 => 'kml'
				),
				'application/vnd.google-earth.kmz' =>
				array(
						0 => 'kmz'
				),
				'application/vnd.grafeq' =>
				array(
						0 => 'gqf',
						1 => 'gqs'
				),
				'application/vnd.groove-account' =>
				array(
						0 => 'gac'
				),
				'application/vnd.groove-help' =>
				array(
						0 => 'ghf'
				),
				'application/vnd.groove-identity-message' =>
				array(
						0 => 'gim'
				),
				'application/vnd.groove-injector' =>
				array(
						0 => 'grv'
				),
				'application/vnd.groove-tool-message' =>
				array(
						0 => 'gtm'
				),
				'application/vnd.groove-tool-template' =>
				array(
						0 => 'tpl'
				),
				'application/vnd.groove-vcard' =>
				array(
						0 => 'vcg'
				),
				'application/vnd.handheld-entertainment+xml' =>
				array(
						0 => 'zmm'
				),
				'application/vnd.hbci' =>
				array(
						0 => 'hbci'
				),
				'application/vnd.hhe.lesson-player' =>
				array(
						0 => 'les'
				),
				'application/vnd.hp-hpgl' =>
				array(
						0 => 'hpgl'
				),
				'application/vnd.hp-hpid' =>
				array(
						0 => 'hpid'
				),
				'application/vnd.hp-hps' =>
				array(
						0 => 'hps'
				),
				'application/vnd.hp-jlyt' =>
				array(
						0 => 'jlt'
				),
				'application/vnd.hp-pcl' =>
				array(
						0 => 'pcl'
				),
				'application/vnd.hp-pclxl' =>
				array(
						0 => 'pclxl'
				),
				'application/vnd.hzn-3d-crossword' =>
				array(
						0 => 'x3d'
				),
				'application/vnd.ibm.minipay' =>
				array(
						0 => 'mpy'
				),
				'application/vnd.ibm.modcap' =>
				array(
						0 => 'afp',
						1 => 'listafp',
						2 => 'list3820'
				),
				'application/vnd.ibm.rights-management' =>
				array(
						0 => 'irm'
				),
				'application/vnd.ibm.secure-container' =>
				array(
						0 => 'sc'
				),
				'application/vnd.igloader' =>
				array(
						0 => 'igl'
				),
				'application/vnd.immervision-ivp' =>
				array(
						0 => 'ivp'
				),
				'application/vnd.immervision-ivu' =>
				array(
						0 => 'ivu'
				),
				'application/vnd.intercon.formnet' =>
				array(
						0 => 'xpw',
						1 => 'xpx'
				),
				'application/vnd.intu.qbo' =>
				array(
						0 => 'qbo'
				),
				'application/vnd.intu.qfx' =>
				array(
						0 => 'qfx'
				),
				'application/vnd.ipunplugged.rcprofile' =>
				array(
						0 => 'rcprofile'
				),
				'application/vnd.irepository.package+xml' =>
				array(
						0 => 'irp'
				),
				'application/vnd.is-xpr' =>
				array(
						0 => 'xpr'
				),
				'application/vnd.jam' =>
				array(
						0 => 'jam'
				),
				'application/vnd.jcp.javame.midlet-rms' =>
				array(
						0 => 'rms'
				),
				'application/vnd.jisp' =>
				array(
						0 => 'jisp'
				),
				'application/vnd.joost.joda-archive' =>
				array(
						0 => 'joda'
				),
				'application/vnd.kahootz' =>
				array(
						0 => 'ktz',
						1 => 'ktr'
				),
				'application/vnd.kde.karbon' =>
				array(
						0 => 'karbon'
				),
				'application/vnd.kde.kchart' =>
				array(
						0 => 'chrt'
				),
				'application/vnd.kde.kformula' =>
				array(
						0 => 'kfo'
				),
				'application/vnd.kde.kivio' =>
				array(
						0 => 'flw'
				),
				'application/vnd.kde.kontour' =>
				array(
						0 => 'kon'
				),
				'application/vnd.kde.kpresenter' =>
				array(
						0 => 'kpr',
						1 => 'kpt'
				),
				'application/vnd.kde.kspread' =>
				array(
						0 => 'ksp'
				),
				'application/vnd.kde.kword' =>
				array(
						0 => 'kwd',
						1 => 'kwt'
				),
				'application/vnd.kenameaapp' =>

				array(
						0 => 'htke'
				),
				'application/vnd.kidspiration' =>
				array(
						0 => 'kia'
				),
				'application/vnd.kinar' =>
				array(
						0 => 'kne',
						1 => 'knp'
				),
				'application/vnd.koan' =>
				array(
						0 => 'skp',
						1 => 'skd',
						2 => 'skt',
						3 => 'skm'
				),
				'application/vnd.llamagraphics.life-balance.desktop' =>
				array(
						0 => 'lbd'
				),
				'application/vnd.llamagraphics.life-balance.exchange+xml' =>
				array(
						0 => 'lbe'
				),
				'application/vnd.lotus-1-2-3' =>
				array(
						0 => '123'
				),
				'application/vnd.lotus-approach' =>
				array(
						0 => 'apr'
				),
				'application/vnd.lotus-freelance' =>
				array(
						0 => 'pre'
				),
				'application/vnd.lotus-notes' =>
				array(
						0 => 'nsf'
				),
				'application/vnd.lotus-organizer' =>
				array(
						0 => 'org'
				),
				'application/vnd.lotus-screencam' =>
				array(
						0 => 'scm'
				),
				'application/vnd.lotus-wordpro' =>
				array(
						0 => 'lwp'
				),
				'application/vnd.macports.portpkg' =>
				array(
						0 => 'portpkg'
				),
				'application/vnd.mcd' =>
				array(
						0 => 'mcd'
				),
				'application/vnd.medcalcdata' =>
				array(
						0 => 'mc1'
				),
				'application/vnd.mediastation.cdkey' =>
				array(
						0 => 'cdkey'
				),
				'application/vnd.mfer' =>
				array(
						0 => 'mwf'
				),
				'application/vnd.mfmp' =>
				array(
						0 => 'mfm'
				),
				'application/vnd.micrografx.flo' =>
				array(
						0 => 'flo'
				),
				'application/vnd.micrografx.igx' =>
				array(
						0 => 'igx'
				),
				'application/vnd.mif' =>
				array(
						0 => 'mif'
				),
				'application/vnd.mobius.daf' =>
				array(
						0 => 'daf'
				),
				'application/vnd.mobius.dis' =>
				array(
						0 => 'dis'
				),
				'application/vnd.mobius.mbk' =>
				array(
						0 => 'mbk'
				),
				'application/vnd.mobius.mqy' =>
				array(
						0 => 'mqy'
				),
				'application/vnd.mobius.msl' =>
				array(
						0 => 'msl'
				),
				'application/vnd.mobius.plc' =>
				array(
						0 => 'plc'
				),
				'application/vnd.mobius.txf' =>
				array(
						0 => 'txf'
				),
				'application/vnd.mophun.application' =>
				array(
						0 => 'mpn'
				),
				'application/vnd.mophun.certificate' =>
				array(
						0 => 'mpc'
				),
				'application/vnd.mozilla.xul+xml' =>
				array(
						0 => 'xul'
				),
				'application/vnd.ms-artgalry' =>
				array(
						0 => 'cil'
				),
				'application/vnd.ms-asf' =>
				array(
						0 => 'asf'
				),
				'application/vnd.ms-cab-compressed' =>
				array(
						0 => 'cab'
				),
				'application/vnd.ms-excel' =>
				array(
						0 => 'xls',
						1 => 'xlm',
						2 => 'xla',
						3 => 'xlc',
						4 => 'xlt',
						5 => 'xlw'
				),
				'application/vnd.ms-fontobject' =>
				array(
						0 => 'eot'
				),
				'application/vnd.ms-htmlhelp' =>
				array(
						0 => 'chm'
				),
				'application/vnd.ms-ims' =>
				array(
						0 => 'ims'
				),
				'application/vnd.ms-lrm' =>
				array(
						0 => 'lrm'
				),
				'application/vnd.ms-powerpoint' =>
				array(
						0 => 'ppt',
						1 => 'pps',
						2 => 'pot'
				),
				'application/vnd.ms-project' =>
				array(
						0 => 'mpp',
						1 => 'mpt'
				),
				'application/vnd.ms-works' =>
				array(
						0 => 'wps',
						1 => 'wks',
						2 => 'wcm',
						3 => 'wdb'
				),
				'application/vnd.ms-wpl' =>
				array(
						0 => 'wpl'
				),
				'application/vnd.ms-xpsdocument' =>
				array(
						0 => 'xps'
				),
				'application/vnd.mseq' =>
				array(
						0 => 'mseq'
				),
				'application/vnd.musician' =>
				array(
						0 => 'mus'
				),
				'application/vnd.muvee.style' =>
				array(
						0 => 'msty'
				),
				'application/vnd.neurolanguage.nlu' =>
				array(
						0 => 'nlu'
				),
				'application/vnd.noblenet-directory' =>
				array(
						0 => 'nnd'
				),
				'application/vnd.noblenet-sealer' =>
				array(
						0 => 'nns'
				),
				'application/vnd.noblenet-web' =>
				array(
						0 => 'nnw'
				),
				'application/vnd.nokia.n-gage.data' =>
				array(
						0 => 'ngdat'
				),
				'application/vnd.nokia.n-gage.symbian.install' =>
				array(
						0 => 'n-gage'
				),
				'application/vnd.nokia.radio-preset' =>
				array(
						0 => 'rpst'
				),
				'application/vnd.nokia.radio-presets' =>
				array(
						0 => 'rpss'
				),
				'application/vnd.novadigm.edm' =>

				array(
						0 => 'edm'
				),
				'application/vnd.novadigm.edx' =>
				array(
						0 => 'edx'
				),
				'application/vnd.novadigm.ext' =>
				array(
						0 => 'ext'
				),
				'application/vnd.oasis.opendocument.chart' =>
				array(
						0 => 'odc'
				),
				'application/vnd.oasis.opendocument.chart-template' =>
				array(
						0 => 'otc'
				),
				'application/vnd.oasis.opendocument.formula' =>
				array(
						0 => 'odf'
				),
				'application/vnd.oasis.opendocument.formula-template' =>
				array(
						0 => 'otf'
				),
				'application/vnd.oasis.opendocument.graphics' =>
				array(
						0 => 'odg'
				),
				'application/vnd.oasis.opendocument.graphics-template' =>
				array(
						0 => 'otg'
				),
				'application/vnd.oasis.opendocument.image' =>
				array(
						0 => 'odi'
				),
				'application/vnd.oasis.opendocument.image-template' =>
				array(
						0 => 'oti'
				),
				'application/vnd.oasis.opendocument.presentation' =>
				array(
						0 => 'odp'
				),
				'application/vnd.oasis.opendocument.spreadsheet' =>
				array(
						0 => 'ods'
				),
				'application/vnd.oasis.opendocument.spreadsheet-template' =>
				array(
						0 => 'ots'
				),
				'application/vnd.oasis.opendocument.text' =>
				array(
						0 => 'odt'
				),
				'application/vnd.oasis.opendocument.text-master' =>
				array(
						0 => 'otm'
				),
				'application/vnd.oasis.opendocument.text-template' =>
				array(
						0 => 'ott'
				),
				'application/vnd.oasis.opendocument.text-web' =>
				array(
						0 => 'oth'
				),
				'application/vnd.olpc-sugar' =>
				array(
						0 => 'xo'
				),
				'application/vnd.oma.dd2+xml' =>
				array(
						0 => 'dd2'
				),
				'application/vnd.openofficeorg.extension' =>
				array(
						0 => 'oxt'
				),
				'application/vnd.osgi.dp' =>
				array(
						0 => 'dp'
				),
				'application/vnd.palm' =>
				array(
						0 => 'prc',
						1 => 'pdb',
						2 => 'pqa',
						3 => 'oprc'
				),
				'application/vnd.pg.format' =>
				array(
						0 => 'str'
				),
				'application/vnd.pg.osasli' =>
				array(
						0 => 'ei6'
				),
				'application/vnd.picsel' =>
				array(
						0 => 'efif'
				),
				'application/vnd.pocketlearn' =>
				array(
						0 => 'plf'
				),
				'application/vnd.powerbuilder6' =>
				array(
						0 => 'pbd'
				),
				'application/vnd.previewsystems.box' =>
				array(
						0 => 'box'
				),
				'application/vnd.proteus.magazine' =>
				array(
						0 => 'mgz'
				),
				'application/vnd.publishare-delta-tree' =>
				array(
						0 => 'qps'
				),
				'application/vnd.pvi.ptid1' =>
				array(
						0 => 'ptid'
				),
				'application/vnd.quark.quarkxpress' =>
				array(
						0 => 'qxd',
						1 => 'qxt',
						2 => 'qwd',
						3 => 'qwt',
						4 => 'qxl',
						5 => 'qxb'
				),
				'application/vnd.recordare.musicxml' =>
				array(
						0 => 'mxl'
				),
				'application/vnd.rn-realmedia' =>
				array(
						0 => 'rm'
				),
				'application/vnd.seemail' =>
				array(
						0 => 'see'
				),
				'application/vnd.sema' =>
				array(
						0 => 'sema'
				),
				'application/vnd.semd' =>
				array(
						0 => 'semd'
				),
				'application/vnd.semf' =>
				array(
						0 => 'semf'
				),
				'application/vnd.shana.informed.formdata' =>
				array(
						0 => 'ifm'
				),
				'application/vnd.shana.informed.formtemplate' =>
				array(
						0 => 'itp'
				),
				'application/vnd.shana.informed.interchange' =>
				array(
						0 => 'iif'
				),
				'application/vnd.shana.informed.package' =>
				array(
						0 => 'ipk'
				),
				'application/vnd.simtech-mindmapper' =>
				array(
						0 => 'twd',
						1 => 'twds'
				),
				'application/vnd.smaf' =>
				array(
						0 => 'mmf'
				),
				'application/vnd.solent.sdkm+xml' =>
				array(
						0 => 'sdkm',
						1 => 'sdkd'
				),
				'application/vnd.spotfire.dxp' =>
				array(
						0 => 'dxp'
				),
				'application/vnd.spotfire.sfs' =>
				array(
						0 => 'sfs'
				),
				'application/vnd.sus-calendar' =>
				array(
						0 => 'sus',
						1 => 'susp'
				),
				'application/vnd.svd' =>
				array(
						0 => 'svd'
				),
				'application/vnd.syncml+xml' =>
				array(
						0 => 'xsm'
				),
				'application/vnd.syncml.dm+wbxml' =>
				array(
						0 => 'bdm'
				),
				'application/vnd.syncml.dm+xml' =>
				array(
						0 => 'xdm'
				),
				'application/vnd.tao.intent-module-archive' =>
				array(
						0 => 'tao'
				),
				'application/vnd.tmobile-livetv' =>
				array(
						0 => 'tmo'
				),
				'application/vnd.trid.tpt' =>
				array(
						0 => 'tpt'
				),
				'application/vnd.triscape.mxs' =>
				array(
						0 => 'mxs'
				),
				'application/vnd.trueapp' =>
				array(
						0 => 'tra'
				),
				'application/vnd.ufdl' =>
				array(
						0 => 'ufd',
						1 => 'ufdl'
				),
				'application/vnd.uiq.theme' =>
				array(
						0 => 'utz'
				),
				'application/vnd.umajin' =>
				array(
						0 => 'umj'
				),
				'application/vnd.unity' =>
				array(
						0 => 'unityweb'
				),
				'application/vnd.uoml+xml' =>
				array(
						0 => 'uoml'
				),
				'application/vnd.vcx' =>
				array(
						0 => 'vcx'
				),
				'application/vnd.visio' =>
				array(
						0 => 'vsd',
						1 => 'vst',
						2 => 'vss',
						3 => 'vsw'
				),
				'application/vnd.visionary' =>
				array(
						0 => 'vis'
				),
				'application/vnd.vsf' =>
				array(
						0 => 'vsf'
				),
				'application/vnd.wap.wbxml' =>
				array(
						0 => 'wbxml'
				),
				'application/vnd.wap.wmlc' =>
				array(
						0 => 'wmlc'
				),
				'application/vnd.wap.wmlscriptc' =>
				array(
						0 => 'wmlsc'
				),
				'application/vnd.webturbo' =>
				array(
						0 => 'wtb'
				),
				'application/vnd.wordperfect' =>
				array(
						0 => 'wpd'
				),
				'application/vnd.wqd' =>
				array(
						0 => 'wqd'
				),
				'application/vnd.wt.stf' =>
				array(
						0 => 'stf'
				),
				'application/vnd.xara' =>
				array(
						0 => 'xar'
				),
				'application/vnd.xfdl' =>
				array(
						0 => 'xfdl'
				),
				'application/vnd.yamaha.hv-dic' =>
				array(
						0 => 'hvd'
				),
				'application/vnd.yamaha.hv-script' =>
				array(
						0 => 'hvs'
				),
				'application/vnd.yamaha.hv-voice' =>
				array(
						0 => 'hvp'
				),
				'application/vnd.yamaha.smaf-audio' =>
				array(
						0 => 'saf'
				),
				'application/vnd.yamaha.smaf-phrase' =>
				array(
						0 => 'spf'
				),
				'application/vnd.yellowriver-custom-menu' =>
				array(
						0 => 'cmp'
				),
				'application/vnd.zzazz.deck+xml' =>
				array(
						0 => 'zaz'
				),
				'application/voicexml+xml' =>
				array(
						0 => 'vxml'
				),
				'application/winhlp' =>
				array(
						0 => 'hlp'
				),
				'application/wsdl+xml' =>
				array(
						0 => 'wsdl'
				),
				'application/wspolicy+xml' =>
				array(
						0 => 'wspolicy'
				),
				'application/x-ace-compressed' =>
				array(
						0 => 'ace'
				),
				'application/x-bcpio' =>
				array(
						0 => 'bcpio'
				),
				'application/x-bittorrent' =>
				array(
						0 => 'torrent'
				),
				'application/x-bzip' =>
				array(
						0 => 'bz'
				),
				'application/x-bzip2' =>
				array(
						0 => 'bz2',
						1 => 'boz'
				),
				'application/x-cdlink' =>
				array(
						0 => 'vcd'
				),
				'application/x-chat' =>
				array(
						0 => 'chat'
				),
				'application/x-chess-pgn' =>
				array(
						0 => 'pgn'
				),
				'application/x-cpio' =>
				array(
						0 => 'cpio'
				),
				'application/x-csh' =>
				array(
						0 => 'csh'
				),
				'application/x-director' =>
				array(
						0 => 'dcr',
						1 => 'dir',
						2 => 'dxr',
						3 => 'fgd'
				),
				'application/x-dvi' =>
				array(
						0 => 'dvi'
				),
				'application/x-futuresplash' =>
				array(
						0 => 'spl'
				),
				'application/x-gtar' =>
				array(
						0 => 'gtar'
				),
				'application/x-hdf' =>
				array(
						0 => 'hdf'
				),
				'application/x-latex' =>
				array(
						0 => 'latex'
				),
				'application/x-ms-wmd' =>
				array(
						0 => 'wmd'
				),
				'application/x-ms-wmz' =>
				array(
						0 => 'wmz'
				),
				'application/x-msaccess' =>
				array(
						0 => 'mdb'
				),
				'application/x-msbinder' =>
				array(
						0 => 'obd'
				),
				'application/x-mscardfile' =>
				array(
						0 => 'crd'
				),
				'application/x-msclip' =>
				array(
						0 => 'clp'
				),
				'application/x-msdownload' =>
				array(
						0 => 'exe',
						1 => 'dll',
						2 => 'com',
						3 => 'bat',
						4 => 'msi'
				),
				'application/x-msmediaview' =>
				array(
						0 => 'mvb',
						1 => 'm13',
						2 => 'm14'
				),
				'application/x-msmetafile' =>
				array(
						0 => 'wmf'
				),
				'application/x-msmoney' =>
				array(
						0 => 'mny'
				),
				'application/x-mspublisher' =>
				array(
						0 => 'pub'
				),
				'application/x-msschedule' =>
				array(
						0 => 'scd'
				),
				'application/x-msterminal' =>
				array(
						0 => 'trm'
				),
				'application/x-mswrite' =>
				array(
						0 => 'wri'
				),
				'application/x-netcdf' =>
				array(
						0 => 'nc',
						1 => 'cdf'
				),
				'application/x-pkcs12' =>
				array(
						0 => 'p12',
						1 => 'pfx'
				),
				'application/x-pkcs7-certificates' =>
				array(
						0 => 'p7b',
						1 => 'spc'
				),
				'application/x-pkcs7-certreqresp' =>
				array(
						0 => 'p7r'
				),
				'application/x-rar-compressed' =>
				array(
						0 => 'rar'
				),
				'application/x-sh' =>
				array(
						0 => 'sh'
				),
				'application/x-shar' =>
				array(
						0 => 'shar'
				),
				'application/x-shockwave-flash' =>
				array(
						0 => 'swf'
				),
				'application/x-stuffit' =>
				array(
						0 => 'sit'
				),
				'application/x-stuffitx' =>
				array(
						0 => 'sitx'
				),
				'application/x-sv4cpio' =>
				array(
						0 => 'sv4cpio'
				),
				'application/x-sv4crc' =>
				array(
						0 => 'sv4crc'
				),
				'application/x-tar' =>
				array(
						0 => 'tar'
				),
				'application/x-tcl' =>
				array(
						0 => 'tcl'
				),
				'application/x-tex' =>
				array(
						0 => 'tex'
				),
				'application/x-texinfo' =>
				array(
						0 => 'texinfo',
						1 => 'texi'
				),
				'application/x-ustar' =>
				array(
						0 => 'ustar'
				),
				'application/x-wais-source' =>
				array(
						0 => 'src'
				),
				'application/x-x509-ca-cert' =>
				array(
						0 => 'der',
						1 => 'crt'
				),
				'application/xenc+xml' =>
				array(
						0 => 'xenc'
				),
				'application/xhtml+xml' =>
				array(
						0 => 'xhtml',
						1 => 'xht'
				),
				'application/xml' =>
				array(
						0 => 'xml',
						1 => 'xsl'
				),
				'application/xml-dtd' =>
				array(
						0 => 'dtd'
				),
				'application/xop+xml' =>
				array(
						0 => 'xop'
				),
				'application/xslt+xml' =>
				array(
						0 => 'xslt'
				),
				'application/xspf+xml' =>
				array(
						0 => 'xspf'
				),
				'application/xv+xml' =>
				array(
						0 => 'mxml',
						1 => 'xhvml',
						2 => 'xvml',
						3 => 'xvm'
				),
				'application/zip' =>
				array(
						0 => 'zip'
				),
				'audio/basic' =>
				array(
						0 => 'au',
						1 => 'snd'
				),
				'audio/midi' =>
				array(
						0 => 'mid',
						1 => 'midi',
						2 => 'kar',
						3 => 'rmi'
				),
				'audio/mp4' =>
				array(
						0 => 'mp4a'
				),
				'audio/mpeg' =>
				array(
						0 => 'mpga',
						1 => 'mp2',
						2 => 'mp2a',
						3 => 'mp3',
						4 => 'm2a',
						5 => 'm3a'
				),
				'audio/vnd.digital-winds' =>
				array(
						0 => 'eol'
				),
				'audio/vnd.lucent.voice' =>
				array(
						0 => 'lvp'
				),
				'audio/vnd.nuera.ecelp4800' =>
				array(
						0 => 'ecelp4800'
				),
				'audio/vnd.nuera.ecelp7470' =>
				array(
						0 => 'ecelp7470'
				),
				'audio/vnd.nuera.ecelp9600' =>
				array(
						0 => 'ecelp9600'
				),
				'audio/wav' =>
				array(
						0 => 'wav'
				),
				'audio/x-aiff' =>
				array(
						0 => 'aif',
						1 => 'aiff',
						2 => 'aifc'
				),
				'audio/x-mpegurl' =>
				array(
						0 => 'm3u'
				),
				'audio/x-ms-wax' =>
				array(
						0 => 'wax'
				),
				'audio/x-ms-wma' =>
				array(
						0 => 'wma'
				),
				'audio/x-pn-realaudio' =>
				array(
						0 => 'ram',
						1 => 'ra'
				),
				'audio/x-pn-realaudio-plugin' =>
				array(
						0 => 'rmp'
				),
				'audio/x-wav' =>
				array(
						0 => 'wav'
				),
				'chemical/x-cdx' =>
				array(
						0 => 'cdx'
				),
				'chemical/x-cif' =>
				array(
						0 => 'cif'
				),
				'chemical/x-cmdf' =>
				array(
						0 => 'cmdf'
				),
				'chemical/x-cml' =>
				array(
						0 => 'cml'
				),
				'chemical/x-csml' =>
				array(
						0 => 'csml'
				),
				'chemical/x-pdb' =>
				array(
						0 => 'pdb'
				),
				'chemical/x-xyz' =>
				array(
						0 => 'xyz'
				),
				'image/bmp' =>
				array(
						0 => 'bmp'
				),
				'image/cgm' =>
				array(
						0 => 'cgm'
				),
				'image/g3fax' =>
				array(
						0 => 'g3'
				),
				'image/gif' =>
				array(
						0 => 'gif'
				),
				'image/ief' =>
				array(
						0 => 'ief'
				),
				'image/jpeg' =>
				array(
						0 => 'jpeg',
						1 => 'jpg',
						2 => 'jpe'
				),
				'image/png' =>
				array(
						0 => 'png'
				),
				'image/prs.btif' =>
				array(
						0 => 'btif'
				),
				'image/svg+xml' =>
				array(
						0 => 'svg',
						1 => 'svgz'
				),
				'image/tiff' =>
				array(
						0 => 'tiff',
						1 => 'tif'
				),
				'image/vnd.adobe.photoshop' =>
				array(
						0 => 'psd'
				),
				'image/vnd.djvu' =>
				array(
						0 => 'djvu',
						1 => 'djv'
				),
				'image/vnd.dwg' =>
				array(
						0 => 'dwg'
				),
				'image/vnd.dxf' =>
				array(
						0 => 'dxf'
				),
				'image/vnd.fastbidsheet' =>
				array(
						0 => 'fbs'
				),
				'image/vnd.fpx' =>
				array(
						0 => 'fpx'
				),
				'image/vnd.fst' =>
				array(
						0 => 'fst'
				),
				'image/vnd.fujixerox.edmics-mmr' =>
				array(
						0 => 'mmr'
				),
				'image/vnd.fujixerox.edmics-rlc' =>
				array(
						0 => 'rlc'
				),
				'image/vnd.ms-modi' =>
				array(
						0 => 'mdi'
				),
				'image/vnd.net-fpx' =>
				array(
						0 => 'npx'
				),
				'image/vnd.wap.wbmp' =>
				array(
						0 => 'wbmp'
				),
				'image/vnd.xiff' =>
				array(
						0 => 'xif'
				),
				'image/x-cmu-raster' =>
				array(
						0 => 'ras'
				),
				'image/x-cmx' =>
				array(
						0 => 'cmx'
				),
				'image/x-icon' =>
				array(
						0 => 'ico'
				),
				'image/x-pcx' =>
				array(
						0 => 'pcx'
				),
				'image/x-pict' =>
				array(
						0 => 'pic',
						1 => 'pct'
				),
				'image/x-portable-anymap' =>
				array(
						0 => 'pnm'
				),
				'image/x-portable-bitmap' =>
				array(
						0 => 'pbm'
				),
				'image/x-portable-graymap' =>
				array(
						0 => 'pgm'
				),
				'image/x-portable-pixmap' =>
				array(
						0 => 'ppm'
				),
				'image/x-rgb' =>
				array(
						0 => 'rgb'
				),
				'image/x-xbitmap' =>
				array(
						0 => 'xbm'
				),
				'image/x-xpixmap' =>
				array(
						0 => 'xpm'
				),
				'image/x-xwindowdump' =>
				array(
						0 => 'xwd'
				),
				'message/rfc822' =>
				array(
						0 => 'eml',
						1 => 'mime'
				),
				'model/iges' =>
				array(
						0 => 'igs',
						1 => 'iges'
				),
				'model/mesh' =>
				array(
						0 => 'msh',
						1 => 'mesh',
						2 => 'silo'
				),
				'model/vnd.dwf' =>
				array(
						0 => 'dwf'
				),
				'model/vnd.gdl' =>
				array(
						0 => 'gdl'
				),
				'model/vnd.gtw' =>
				array(
						0 => 'gtw'
				),
				'model/vnd.mts' =>
				array(
						0 => 'mts'
				),
				'model/vnd.vtu' =>
				array(
						0 => 'vtu'
				),
				'model/vrml' =>
				array(
						0 => 'wrl',
						1 => 'vrml'
				),
				'text/calendar' =>
				array(
						0 => 'ics',
						1 => 'ifb'
				),
				'text/css' =>
				array(
						0 => 'css'
				),
				'text/csv' =>
				array(
						0 => 'csv'
				),
				'text/html' =>
				array(
						0 => 'html',
						1 => 'htm'
				),
				'text/plain' =>
				array(
						0 => 'txt',
						1 => 'text',
						2 => 'conf',
						3 => 'def',
						4 => 'list',
						5 => 'log',
						6 => 'in'
				),
				'text/prs.lines.tag' =>
				array(
						0 => 'dsc'
				),
				'text/richtext' =>
				array(
						0 => 'rtx'
				),
				'text/sgml' =>
				array(
						0 => 'sgml',
						1 => 'sgm'
				),
				'text/tab-separated-values' =>
				array(
						0 => 'tsv'
				),
				'text/troff' =>
				array(
						0 => 't',
						1 => 'tr',
						2 => 'roff',
						3 => 'man',
						4 => 'me',
						5 => 'ms'
				),
				'text/uri-list' =>
				array(
						0 => 'uri',
						1 => 'uris',
						2 => 'urls'
				),
				'text/vnd.fly' =>
				array(
						0 => 'fly'
				),
				'text/vnd.fmi.flexstor' =>
				array(
						0 => 'flx'
				),
				'text/vnd.in3d.3dml' =>
				array(
						0 => '3dml'
				),
				'text/vnd.in3d.spot' =>
				array(
						0 => 'spot'
				),
				'text/vnd.sun.j2me.app-descriptor' =>
				array(
						0 => 'jad'
				),
				'text/vnd.wap.wml' =>
				array(
						0 => 'wml'
				),
				'text/vnd.wap.wmlscript' =>
				array(
						0 => 'wmls'
				),
				'text/x-asm' =>
				array(
						0 => 's',
						1 => 'asm'
				),
				'text/x-c' =>
				array(
						0 => 'c',
						1 => 'cc',
						2 => 'cxx',
						3 => 'cpp',
						4 => 'h',
						5 => 'hh',
						6 => 'dic'
				),
				'text/x-fortran' =>
				array(
						0 => 'f',
						1 => 'for',
						2 => 'f77',
						3 => 'f90'
				),
				'text/x-pascal' =>
				array(
						0 => 'p',
						1 => 'pas'
				),
				'text/x-java-source' =>
				array(
						0 => 'java'
				),
				'text/x-setext' =>
				array(
						0 => 'etx'
				),
				'text/x-uuencode' =>
				array(
						0 => 'uu'
				),
				'text/x-vcalendar' =>
				array(
						0 => 'vcs'
				),
				'text/x-vcard' =>
				array(
						0 => 'vcf'
				),
				'video/3gpp' =>
				array(
						0 => '3gp'
				),
				'video/3gpp2' =>
				array(
						0 => '3g2'
				),
				'video/h261' =>
				array(
						0 => 'h261'
				),
				'video/h263' =>
				array(
						0 => 'h263'
				),
				'video/h264' =>
				array(
						0 => 'h264'
				),
				'video/jpeg' =>
				array(
						0 => 'jpgv'
				),
				'video/jpm' =>
				array(
						0 => 'jpm',
						1 => 'jpgm'
				),
				'video/mj2' =>
				array(
						0 => 'mj2',
						1 => 'mjp2'
				),
				'video/mp4' =>
				array(
						0 => 'mp4',
						1 => 'mp4v',
						2 => 'mpg4'
				),
				'video/mpeg' =>
				array(
						0 => 'mpeg',
						1 => 'mpg',
						2 => 'mpe',
						3 => 'm1v',
						4 => 'm2v'
				),
				'video/quicktime' =>
				array(
						0 => 'qt',
						1 => 'mov'
				),
				'video/vnd.fvt' =>
				array(
						0 => 'fvt'
				),
				'video/vnd.mpegurl' =>
				array(
						0 => 'mxu',
						1 => 'm4u'
				),
				'video/vnd.vivo' =>
				array(
						0 => 'viv'
				),
				'video/x-fli' =>
				array(
						0 => 'fli'
				),
				'video/x-ms-asf' =>
				array(
						0 => 'asf',
						1 => 'asx'
				),
				'video/x-ms-wm' =>
				array(
						0 => 'wm'
				),
				'video/x-ms-wmv' =>
				array(
						0 => 'wmv'
				),
				'video/x-ms-wmx' =>
				array(
						0 => 'wmx'
				),
				'video/x-ms-wvx' =>
				array(
						0 => 'wvx'
				),
				'video/x-msvideo' =>
				array(
						0 => 'avi'
				),
				'video/x-sgi-movie' =>
				array(
						0 => 'movie'
				),
				'x-conference/x-cooltalk' =>
				array(
						0 => 'ice'
				)
		);

		return $mimetypes;
	}

}

?>