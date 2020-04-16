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

class lcMimetypeHelper
{
    public static function isMimetype($input)
    {
        if (strpos($input, '/') === false) {
            return false;
        }

        if (!in_array($input, array_keys(lcMimetypeHelper::getList()))) {
            return false;
        }

        return true;
    }

    public static function getList()
    {

        return [
            'application/andrew-inset' =>
                [
                    0 => 'ez',
                ],
            'application/atom+xml' =>
                [
                    0 => 'atom',
                ],
            'application/atomcat+xml' =>
                [
                    0 => 'atomcat',
                ],
            'application/atomsvc+xml' =>
                [
                    0 => 'atomsvc',
                ],
            'application/ccxml+xml' =>
                [
                    0 => 'ccxml',
                ],
            'application/davmount+xml' =>
                [
                    0 => 'davmount',
                ],
            'application/ecmascript' =>
                [
                    0 => 'ecma',
                ],
            'application/font-tdpfr' =>
                [
                    0 => 'pfr',
                ],
            'application/hyperstudio' =>
                [
                    0 => 'stk',
                ],
            'application/javascript' =>
                [
                    0 => 'js',
                ],
            'application/json' =>
                [
                    0 => 'json',
                ],
            'application/mac-binhex40' =>
                [
                    0 => 'hqx',
                ],
            'application/mac-compactpro' =>
                [
                    0 => 'cpt',
                ],
            'application/marc' =>
                [
                    0 => 'mrc',
                ],
            'application/mathematica' =>
                [
                    0 => 'ma',
                    1 => 'nb',
                    2 => 'mb',
                ],
            'application/mathml+xml' =>
                [
                    0 => 'mathml',
                ],
            'application/mbox' =>
                [
                    0 => 'mbox',
                ],
            'application/mediaservercontrol+xml' =>
                [
                    0 => 'mscml',
                ],
            'application/mp4' =>
                [
                    0 => 'mp4s',
                ],
            'application/msword' =>
                [
                    0 => 'doc',
                    1 => 'dot',
                ],
            'application/mxf' =>
                [
                    0 => 'mxf',
                ],
            'application/oda' =>
                [
                    0 => 'oda',
                ],
            'application/ogg' =>
                [
                    0 => 'ogg',
                ],
            'application/pdf' =>
                [
                    0 => 'pdf',
                ],
            'application/pgp-encrypted' =>
                [
                    0 => 'pgp',
                ],
            'application/pgp-signature' =>
                [
                    0 => 'asc',
                    1 => 'sig',
                ],
            'application/pics-rules' =>
                [
                    0 => 'prf',
                ],
            'application/pkcs10' =>
                [
                    0 => 'p10',
                ],
            'application/pkcs7-mime' =>
                [
                    0 => 'p7m',
                    1 => 'p7c',
                ],
            'application/pkcs7-signature' =>
                [
                    0 => 'p7s',
                ],
            'application/pkix-cert' =>
                [
                    0 => 'cer',
                ],
            'application/pkix-crl' =>
                [
                    0 => 'crl',
                ],
            'application/pkix-pkipath' =>
                [
                    0 => 'pkipath',
                ],
            'application/pkixcmp' =>
                [
                    0 => 'pki',
                ],
            'application/pls+xml' =>
                [
                    0 => 'pls',
                ],
            'application/postscript' =>
                [
                    0 => 'ai',
                    1 => 'eps',
                    2 => 'ps',
                ],
            'application/prs.cww' =>
                [
                    0 => 'cww',
                ],
            'application/rdf+xml' =>
                [
                    0 => 'rdf',
                ],
            'application/reginfo+xml' =>
                [
                    0 => 'rif',
                ],
            'application/relax-ng-compact-syntax' =>
                [
                    0 => 'rnc',
                ],
            'application/resource-lists+xml' =>
                [
                    0 => 'rl',
                ],
            'application/rls-services+xml' =>
                [
                    0 => 'rs',
                ],
            'application/rsd+xml' =>
                [
                    0 => 'rsd',
                ],
            'application/rss+xml' =>
                [
                    0 => 'rss',
                ],
            'application/rtf' =>
                [
                    0 => 'rtf',
                ],
            'application/sbml+xml' =>
                [
                    0 => 'sbml',
                ],
            'application/scvp-cv-request' =>
                [
                    0 => 'scq',
                ],
            'application/scvp-cv-response' =>
                [
                    0 => 'scs',
                ],
            'application/scvp-vp-request' =>
                [
                    0 => 'spq',
                ],
            'application/scvp-vp-response' =>
                [
                    0 => 'spp',
                ],
            'application/sdp' =>
                [
                    0 => 'sdp',
                ],
            'application/set-payment-initiation' =>
                [
                    0 => 'setpay',
                ],
            'application/set-registration-initiation' =>
                [
                    0 => 'setreg',
                ],
            'application/shf+xml' =>
                [
                    0 => 'shf',
                ],
            'application/smil+xml' =>
                [
                    0 => 'smi',
                    1 => 'smil',
                ],
            'application/sparql-query' =>
                [
                    0 => 'rq',
                ],
            'application/sparql-results+xml' =>
                [
                    0 => 'srx',
                ],
            'application/srgs' =>
                [
                    0 => 'gram',
                ],
            'application/srgs+xml' =>
                [
                    0 => 'grxml',
                ],
            'application/ssml+xml' =>
                [
                    0 => 'ssml',
                ],
            'application/vnd.3gpp.pic-bw-large' =>
                [
                    0 => 'plb',
                ],
            'application/vnd.3gpp.pic-bw-small' =>
                [
                    0 => 'psb',
                ],
            'application/vnd.3gpp.pic-bw-var' =>
                [
                    0 => 'pvb',
                ],
            'application/vnd.3gpp2.tcap' =>
                [
                    0 => 'tcap',
                ],
            'application/vnd.3m.post-it-notes' =>
                [
                    0 => 'pwn',
                ],
            'application/vnd.accpac.simply.aso' =>
                [
                    0 => 'aso',
                ],
            'application/vnd.accpac.simply.imp' =>
                [
                    0 => 'imp',
                ],
            'application/vnd.acucobol' =>
                [
                    0 => 'acu',
                ],
            'application/vnd.acucorp' =>
                [
                    0 => 'atc',
                    1 => 'acutc',
                ],
            'application/vnd.adobe.xdp+xml' =>
                [
                    0 => 'xdp',
                ],
            'application/vnd.adobe.xfdf' =>
                [
                    0 => 'xfdf',
                ],
            'application/vnd.amiga.ami' =>
                [
                    0 => 'ami',
                ],
            'application/vnd.anser-web-certificate-issue-initiation' =>
                [
                    0 => 'cii',
                ],
            'application/vnd.anser-web-funds-transfer-initiation' =>
                [
                    0 => 'fti',
                ],
            'application/vnd.antix.game-component' =>
                [
                    0 => 'atx',
                ],
            'application/vnd.apple.installer+xml' =>
                [
                    0 => 'mpkg',
                ],
            'application/vnd.audiograph' =>
                [
                    0 => 'aep',
                ],
            'application/vnd.blueice.multipass' =>
                [
                    0 => 'mpm',
                ],
            'application/vnd.bmi' =>
                [
                    0 => 'bmi',
                ],
            'application/vnd.businessobjects' =>
                [
                    0 => 'rep',
                ],
            'application/vnd.chemdraw+xml' =>
                [
                    0 => 'cdxml',
                ],
            'application/vnd.chipnuts.karaoke-mmd' =>
                [
                    0 => 'mmd',
                ],
            'application/vnd.cinderella' =>
                [
                    0 => 'cdy',
                ],
            'application/vnd.claymore' =>
                [
                    0 => 'cla',
                ],
            'application/vnd.clonk.c4group' =>
                [
                    0 => 'c4g',
                    1 => 'c4d',
                    2 => 'c4f',
                    3 => 'c4p',
                    4 => 'c4u',
                ],
            'application/vnd.commonspace' =>
                [
                    0 => 'csp',
                    1 => 'cst',
                ],
            'application/vnd.contact.cmsg' =>
                [
                    0 => 'cdbcmsg',
                ],
            'application/vnd.cosmocaller' =>
                [
                    0 => 'cmc',
                ],
            'application/vnd.crick.clicker' =>
                [
                    0 => 'clkx',
                ],
            'application/vnd.crick.clicker.keyboard' =>
                [
                    0 => 'clkk',
                ],
            'application/vnd.crick.clicker.palette' =>
                [
                    0 => 'clkp',
                ],
            'application/vnd.crick.clicker.template' =>
                [
                    0 => 'clkt',
                ],
            'application/vnd.crick.clicker.wordbank' =>
                [
                    0 => 'clkw',
                ],
            'application/vnd.criticaltools.wbs+xml' =>
                [
                    0 => 'wbs',
                ],
            'application/vnd.ctc-posml' =>
                [
                    0 => 'pml',
                ],
            'application/vnd.cups-ppd' =>
                [
                    0 => 'ppd',
                ],
            'application/vnd.curl' =>
                [
                    0 => 'curl',
                ],
            'application/vnd.data-vision.rdz' =>
                [
                    0 => 'rdz',
                ],
            'application/vnd.denovo.fcselayout-link' =>
                [
                    0 => 'fe_launch',
                ],
            'application/vnd.dna' =>
                [
                    0 => 'dna',
                ],
            'application/vnd.dolby.mlp' =>
                [
                    0 => 'mlp',
                ],
            'application/vnd.dpgraph' =>
                [
                    0 => 'dpg',
                ],
            'application/vnd.dreamfactory' =>
                [
                    0 => 'dfac',
                ],
            'application/vnd.ecowin.chart' =>
                [
                    0 => 'mag',
                ],
            'application/vnd.enliven' =>
                [
                    0 => 'nml',
                ],
            'application/vnd.epson.esf' =>
                [
                    0 => 'esf',
                ],
            'application/vnd.epson.msf' =>
                [
                    0 => 'msf',
                ],
            'application/vnd.epson.quickanime' =>
                [
                    0 => 'qam',
                ],
            'application/vnd.epson.salt' =>
                [
                    0 => 'slt',
                ],
            'application/vnd.epson.ssf' =>
                [
                    0 => 'ssf',
                ],
            'application/vnd.eszigno3+xml' =>
                [
                    0 => 'es3',
                    1 => 'et3',
                ],
            'application/vnd.ezpix-album' =>
                [
                    0 => 'ez2',
                ],
            'application/vnd.ezpix-package' =>
                [
                    0 => 'ez3',
                ],
            'application/vnd.fdf' =>
                [
                    0 => 'fdf',
                ],
            'application/vnd.flographit' =>
                [
                    0 => 'gph',
                ],
            'application/vnd.fluxtime.clip' =>
                [
                    0 => 'ftc',
                ],
            'application/vnd.framemaker' =>
                [
                    0 => 'fm',
                    1 => 'frame',
                    2 => 'maker',
                ],
            'application/vnd.frogans.fnc' =>
                [
                    0 => 'fnc',
                ],
            'application/vnd.frogans.ltf' =>
                [
                    0 => 'ltf',
                ],
            'application/vnd.fsc.weblaunch' =>
                [
                    0 => 'fsc',
                ],
            'application/vnd.fujitsu.oasys' =>
                [
                    0 => 'oas',
                ],
            'application/vnd.fujitsu.oasys2' =>
                [
                    0 => 'oa2',
                ],
            'application/vnd.fujitsu.oasys3' =>
                [
                    0 => 'oa3',
                ],
            'application/vnd.fujitsu.oasysgp' =>
                [
                    0 => 'fg5',
                ],
            'application/vnd.fujitsu.oasysprs' =>
                [
                    0 => 'bh2',
                ],
            'application/vnd.fujixerox.ddd' =>
                [
                    0 => 'ddd',
                ],
            'application/vnd.fujixerox.docuworks' =>
                [
                    0 => 'xdw',
                ],
            'application/vnd.fujixerox.docuworks.binder' =>
                [
                    0 => 'xbd',
                ],
            'application/vnd.fuzzysheet' =>
                [
                    0 => 'fzs',
                ],
            'application/vnd.genomatix.tuxedo' =>
                [
                    0 => 'txd',
                ],
            'application/vnd.google-earth.kml+xml' =>
                [
                    0 => 'kml',
                ],
            'application/vnd.google-earth.kmz' =>
                [
                    0 => 'kmz',
                ],
            'application/vnd.grafeq' =>
                [
                    0 => 'gqf',
                    1 => 'gqs',
                ],
            'application/vnd.groove-account' =>
                [
                    0 => 'gac',
                ],
            'application/vnd.groove-help' =>
                [
                    0 => 'ghf',
                ],
            'application/vnd.groove-identity-message' =>
                [
                    0 => 'gim',
                ],
            'application/vnd.groove-injector' =>
                [
                    0 => 'grv',
                ],
            'application/vnd.groove-tool-message' =>
                [
                    0 => 'gtm',
                ],
            'application/vnd.groove-tool-template' =>
                [
                    0 => 'tpl',
                ],
            'application/vnd.groove-vcard' =>
                [
                    0 => 'vcg',
                ],
            'application/vnd.handheld-entertainment+xml' =>
                [
                    0 => 'zmm',
                ],
            'application/vnd.hbci' =>
                [
                    0 => 'hbci',
                ],
            'application/vnd.hhe.lesson-player' =>
                [
                    0 => 'les',
                ],
            'application/vnd.hp-hpgl' =>
                [
                    0 => 'hpgl',
                ],
            'application/vnd.hp-hpid' =>
                [
                    0 => 'hpid',
                ],
            'application/vnd.hp-hps' =>
                [
                    0 => 'hps',
                ],
            'application/vnd.hp-jlyt' =>
                [
                    0 => 'jlt',
                ],
            'application/vnd.hp-pcl' =>
                [
                    0 => 'pcl',
                ],
            'application/vnd.hp-pclxl' =>
                [
                    0 => 'pclxl',
                ],
            'application/vnd.hzn-3d-crossword' =>
                [
                    0 => 'x3d',
                ],
            'application/vnd.ibm.minipay' =>
                [
                    0 => 'mpy',
                ],
            'application/vnd.ibm.modcap' =>
                [
                    0 => 'afp',
                    1 => 'listafp',
                    2 => 'list3820',
                ],
            'application/vnd.ibm.rights-management' =>
                [
                    0 => 'irm',
                ],
            'application/vnd.ibm.secure-container' =>
                [
                    0 => 'sc',
                ],
            'application/vnd.igloader' =>
                [
                    0 => 'igl',
                ],
            'application/vnd.immervision-ivp' =>
                [
                    0 => 'ivp',
                ],
            'application/vnd.immervision-ivu' =>
                [
                    0 => 'ivu',
                ],
            'application/vnd.intercon.formnet' =>
                [
                    0 => 'xpw',
                    1 => 'xpx',
                ],
            'application/vnd.intu.qbo' =>
                [
                    0 => 'qbo',
                ],
            'application/vnd.intu.qfx' =>
                [
                    0 => 'qfx',
                ],
            'application/vnd.ipunplugged.rcprofile' =>
                [
                    0 => 'rcprofile',
                ],
            'application/vnd.irepository.package+xml' =>
                [
                    0 => 'irp',
                ],
            'application/vnd.is-xpr' =>
                [
                    0 => 'xpr',
                ],
            'application/vnd.jam' =>
                [
                    0 => 'jam',
                ],
            'application/vnd.jcp.javame.midlet-rms' =>
                [
                    0 => 'rms',
                ],
            'application/vnd.jisp' =>
                [
                    0 => 'jisp',
                ],
            'application/vnd.joost.joda-archive' =>
                [
                    0 => 'joda',
                ],
            'application/vnd.kahootz' =>
                [
                    0 => 'ktz',
                    1 => 'ktr',
                ],
            'application/vnd.kde.karbon' =>
                [
                    0 => 'karbon',
                ],
            'application/vnd.kde.kchart' =>
                [
                    0 => 'chrt',
                ],
            'application/vnd.kde.kformula' =>
                [
                    0 => 'kfo',
                ],
            'application/vnd.kde.kivio' =>
                [
                    0 => 'flw',
                ],
            'application/vnd.kde.kontour' =>
                [
                    0 => 'kon',
                ],
            'application/vnd.kde.kpresenter' =>
                [
                    0 => 'kpr',
                    1 => 'kpt',
                ],
            'application/vnd.kde.kspread' =>
                [
                    0 => 'ksp',
                ],
            'application/vnd.kde.kword' =>
                [
                    0 => 'kwd',
                    1 => 'kwt',
                ],
            'application/vnd.kenameaapp' =>

                [
                    0 => 'htke',
                ],
            'application/vnd.kidspiration' =>
                [
                    0 => 'kia',
                ],
            'application/vnd.kinar' =>
                [
                    0 => 'kne',
                    1 => 'knp',
                ],
            'application/vnd.koan' =>
                [
                    0 => 'skp',
                    1 => 'skd',
                    2 => 'skt',
                    3 => 'skm',
                ],
            'application/vnd.llamagraphics.life-balance.desktop' =>
                [
                    0 => 'lbd',
                ],
            'application/vnd.llamagraphics.life-balance.exchange+xml' =>
                [
                    0 => 'lbe',
                ],
            'application/vnd.lotus-1-2-3' =>
                [
                    0 => '123',
                ],
            'application/vnd.lotus-approach' =>
                [
                    0 => 'apr',
                ],
            'application/vnd.lotus-freelance' =>
                [
                    0 => 'pre',
                ],
            'application/vnd.lotus-notes' =>
                [
                    0 => 'nsf',
                ],
            'application/vnd.lotus-organizer' =>
                [
                    0 => 'org',
                ],
            'application/vnd.lotus-screencam' =>
                [
                    0 => 'scm',
                ],
            'application/vnd.lotus-wordpro' =>
                [
                    0 => 'lwp',
                ],
            'application/vnd.macports.portpkg' =>
                [
                    0 => 'portpkg',
                ],
            'application/vnd.mcd' =>
                [
                    0 => 'mcd',
                ],
            'application/vnd.medcalcdata' =>
                [
                    0 => 'mc1',
                ],
            'application/vnd.mediastation.cdkey' =>
                [
                    0 => 'cdkey',
                ],
            'application/vnd.mfer' =>
                [
                    0 => 'mwf',
                ],
            'application/vnd.mfmp' =>
                [
                    0 => 'mfm',
                ],
            'application/vnd.micrografx.flo' =>
                [
                    0 => 'flo',
                ],
            'application/vnd.micrografx.igx' =>
                [
                    0 => 'igx',
                ],
            'application/vnd.mif' =>
                [
                    0 => 'mif',
                ],
            'application/vnd.mobius.daf' =>
                [
                    0 => 'daf',
                ],
            'application/vnd.mobius.dis' =>
                [
                    0 => 'dis',
                ],
            'application/vnd.mobius.mbk' =>
                [
                    0 => 'mbk',
                ],
            'application/vnd.mobius.mqy' =>
                [
                    0 => 'mqy',
                ],
            'application/vnd.mobius.msl' =>
                [
                    0 => 'msl',
                ],
            'application/vnd.mobius.plc' =>
                [
                    0 => 'plc',
                ],
            'application/vnd.mobius.txf' =>
                [
                    0 => 'txf',
                ],
            'application/vnd.mophun.application' =>
                [
                    0 => 'mpn',
                ],
            'application/vnd.mophun.certificate' =>
                [
                    0 => 'mpc',
                ],
            'application/vnd.mozilla.xul+xml' =>
                [
                    0 => 'xul',
                ],
            'application/vnd.ms-artgalry' =>
                [
                    0 => 'cil',
                ],
            'application/vnd.ms-asf' =>
                [
                    0 => 'asf',
                ],
            'application/vnd.ms-cab-compressed' =>
                [
                    0 => 'cab',
                ],
            'application/vnd.ms-excel' =>
                [
                    0 => 'xls',
                    1 => 'xlm',
                    2 => 'xla',
                    3 => 'xlc',
                    4 => 'xlt',
                    5 => 'xlw',
                ],
            'application/vnd.ms-fontobject' =>
                [
                    0 => 'eot',
                ],
            'application/vnd.ms-htmlhelp' =>
                [
                    0 => 'chm',
                ],
            'application/vnd.ms-ims' =>
                [
                    0 => 'ims',
                ],
            'application/vnd.ms-lrm' =>
                [
                    0 => 'lrm',
                ],
            'application/vnd.ms-powerpoint' =>
                [
                    0 => 'ppt',
                    1 => 'pps',
                    2 => 'pot',
                ],
            'application/vnd.ms-project' =>
                [
                    0 => 'mpp',
                    1 => 'mpt',
                ],
            'application/vnd.ms-works' =>
                [
                    0 => 'wps',
                    1 => 'wks',
                    2 => 'wcm',
                    3 => 'wdb',
                ],
            'application/vnd.ms-wpl' =>
                [
                    0 => 'wpl',
                ],
            'application/vnd.ms-xpsdocument' =>
                [
                    0 => 'xps',
                ],
            'application/vnd.mseq' =>
                [
                    0 => 'mseq',
                ],
            'application/vnd.musician' =>
                [
                    0 => 'mus',
                ],
            'application/vnd.muvee.style' =>
                [
                    0 => 'msty',
                ],
            'application/vnd.neurolanguage.nlu' =>
                [
                    0 => 'nlu',
                ],
            'application/vnd.noblenet-directory' =>
                [
                    0 => 'nnd',
                ],
            'application/vnd.noblenet-sealer' =>
                [
                    0 => 'nns',
                ],
            'application/vnd.noblenet-web' =>
                [
                    0 => 'nnw',
                ],
            'application/vnd.nokia.n-gage.data' =>
                [
                    0 => 'ngdat',
                ],
            'application/vnd.nokia.n-gage.symbian.install' =>
                [
                    0 => 'n-gage',
                ],
            'application/vnd.nokia.radio-preset' =>
                [
                    0 => 'rpst',
                ],
            'application/vnd.nokia.radio-presets' =>
                [
                    0 => 'rpss',
                ],
            'application/vnd.novadigm.edm' =>

                [
                    0 => 'edm',
                ],
            'application/vnd.novadigm.edx' =>
                [
                    0 => 'edx',
                ],
            'application/vnd.novadigm.ext' =>
                [
                    0 => 'ext',
                ],
            'application/vnd.oasis.opendocument.chart' =>
                [
                    0 => 'odc',
                ],
            'application/vnd.oasis.opendocument.chart-template' =>
                [
                    0 => 'otc',
                ],
            'application/vnd.oasis.opendocument.formula' =>
                [
                    0 => 'odf',
                ],
            'application/vnd.oasis.opendocument.formula-template' =>
                [
                    0 => 'otf',
                ],
            'application/vnd.oasis.opendocument.graphics' =>
                [
                    0 => 'odg',
                ],
            'application/vnd.oasis.opendocument.graphics-template' =>
                [
                    0 => 'otg',
                ],
            'application/vnd.oasis.opendocument.image' =>
                [
                    0 => 'odi',
                ],
            'application/vnd.oasis.opendocument.image-template' =>
                [
                    0 => 'oti',
                ],
            'application/vnd.oasis.opendocument.presentation' =>
                [
                    0 => 'odp',
                ],
            'application/vnd.oasis.opendocument.spreadsheet' =>
                [
                    0 => 'ods',
                ],
            'application/vnd.oasis.opendocument.spreadsheet-template' =>
                [
                    0 => 'ots',
                ],
            'application/vnd.oasis.opendocument.text' =>
                [
                    0 => 'odt',
                ],
            'application/vnd.oasis.opendocument.text-master' =>
                [
                    0 => 'otm',
                ],
            'application/vnd.oasis.opendocument.text-template' =>
                [
                    0 => 'ott',
                ],
            'application/vnd.oasis.opendocument.text-web' =>
                [
                    0 => 'oth',
                ],
            'application/vnd.olpc-sugar' =>
                [
                    0 => 'xo',
                ],
            'application/vnd.oma.dd2+xml' =>
                [
                    0 => 'dd2',
                ],
            'application/vnd.openofficeorg.extension' =>
                [
                    0 => 'oxt',
                ],
            'application/vnd.osgi.dp' =>
                [
                    0 => 'dp',
                ],
            'application/vnd.palm' =>
                [
                    0 => 'prc',
                    1 => 'pdb',
                    2 => 'pqa',
                    3 => 'oprc',
                ],
            'application/vnd.pg.format' =>
                [
                    0 => 'str',
                ],
            'application/vnd.pg.osasli' =>
                [
                    0 => 'ei6',
                ],
            'application/vnd.picsel' =>
                [
                    0 => 'efif',
                ],
            'application/vnd.pocketlearn' =>
                [
                    0 => 'plf',
                ],
            'application/vnd.powerbuilder6' =>
                [
                    0 => 'pbd',
                ],
            'application/vnd.previewsystems.box' =>
                [
                    0 => 'box',
                ],
            'application/vnd.proteus.magazine' =>
                [
                    0 => 'mgz',
                ],
            'application/vnd.publishare-delta-tree' =>
                [
                    0 => 'qps',
                ],
            'application/vnd.pvi.ptid1' =>
                [
                    0 => 'ptid',
                ],
            'application/vnd.quark.quarkxpress' =>
                [
                    0 => 'qxd',
                    1 => 'qxt',
                    2 => 'qwd',
                    3 => 'qwt',
                    4 => 'qxl',
                    5 => 'qxb',
                ],
            'application/vnd.recordare.musicxml' =>
                [
                    0 => 'mxl',
                ],
            'application/vnd.rn-realmedia' =>
                [
                    0 => 'rm',
                ],
            'application/vnd.seemail' =>
                [
                    0 => 'see',
                ],
            'application/vnd.sema' =>
                [
                    0 => 'sema',
                ],
            'application/vnd.semd' =>
                [
                    0 => 'semd',
                ],
            'application/vnd.semf' =>
                [
                    0 => 'semf',
                ],
            'application/vnd.shana.informed.formdata' =>
                [
                    0 => 'ifm',
                ],
            'application/vnd.shana.informed.formtemplate' =>
                [
                    0 => 'itp',
                ],
            'application/vnd.shana.informed.interchange' =>
                [
                    0 => 'iif',
                ],
            'application/vnd.shana.informed.package' =>
                [
                    0 => 'ipk',
                ],
            'application/vnd.simtech-mindmapper' =>
                [
                    0 => 'twd',
                    1 => 'twds',
                ],
            'application/vnd.smaf' =>
                [
                    0 => 'mmf',
                ],
            'application/vnd.solent.sdkm+xml' =>
                [
                    0 => 'sdkm',
                    1 => 'sdkd',
                ],
            'application/vnd.spotfire.dxp' =>
                [
                    0 => 'dxp',
                ],
            'application/vnd.spotfire.sfs' =>
                [
                    0 => 'sfs',
                ],
            'application/vnd.sus-calendar' =>
                [
                    0 => 'sus',
                    1 => 'susp',
                ],
            'application/vnd.svd' =>
                [
                    0 => 'svd',
                ],
            'application/vnd.syncml+xml' =>
                [
                    0 => 'xsm',
                ],
            'application/vnd.syncml.dm+wbxml' =>
                [
                    0 => 'bdm',
                ],
            'application/vnd.syncml.dm+xml' =>
                [
                    0 => 'xdm',
                ],
            'application/vnd.tao.intent-module-archive' =>
                [
                    0 => 'tao',
                ],
            'application/vnd.tmobile-livetv' =>
                [
                    0 => 'tmo',
                ],
            'application/vnd.trid.tpt' =>
                [
                    0 => 'tpt',
                ],
            'application/vnd.triscape.mxs' =>
                [
                    0 => 'mxs',
                ],
            'application/vnd.trueapp' =>
                [
                    0 => 'tra',
                ],
            'application/vnd.ufdl' =>
                [
                    0 => 'ufd',
                    1 => 'ufdl',
                ],
            'application/vnd.uiq.theme' =>
                [
                    0 => 'utz',
                ],
            'application/vnd.umajin' =>
                [
                    0 => 'umj',
                ],
            'application/vnd.unity' =>
                [
                    0 => 'unityweb',
                ],
            'application/vnd.uoml+xml' =>
                [
                    0 => 'uoml',
                ],
            'application/vnd.vcx' =>
                [
                    0 => 'vcx',
                ],
            'application/vnd.visio' =>
                [
                    0 => 'vsd',
                    1 => 'vst',
                    2 => 'vss',
                    3 => 'vsw',
                ],
            'application/vnd.visionary' =>
                [
                    0 => 'vis',
                ],
            'application/vnd.vsf' =>
                [
                    0 => 'vsf',
                ],
            'application/vnd.wap.wbxml' =>
                [
                    0 => 'wbxml',
                ],
            'application/vnd.wap.wmlc' =>
                [
                    0 => 'wmlc',
                ],
            'application/vnd.wap.wmlscriptc' =>
                [
                    0 => 'wmlsc',
                ],
            'application/vnd.webturbo' =>
                [
                    0 => 'wtb',
                ],
            'application/vnd.wordperfect' =>
                [
                    0 => 'wpd',
                ],
            'application/vnd.wqd' =>
                [
                    0 => 'wqd',
                ],
            'application/vnd.wt.stf' =>
                [
                    0 => 'stf',
                ],
            'application/vnd.xara' =>
                [
                    0 => 'xar',
                ],
            'application/vnd.xfdl' =>
                [
                    0 => 'xfdl',
                ],
            'application/vnd.yamaha.hv-dic' =>
                [
                    0 => 'hvd',
                ],
            'application/vnd.yamaha.hv-script' =>
                [
                    0 => 'hvs',
                ],
            'application/vnd.yamaha.hv-voice' =>
                [
                    0 => 'hvp',
                ],
            'application/vnd.yamaha.smaf-audio' =>
                [
                    0 => 'saf',
                ],
            'application/vnd.yamaha.smaf-phrase' =>
                [
                    0 => 'spf',
                ],
            'application/vnd.yellowriver-custom-menu' =>
                [
                    0 => 'cmp',
                ],
            'application/vnd.zzazz.deck+xml' =>
                [
                    0 => 'zaz',
                ],
            'application/voicexml+xml' =>
                [
                    0 => 'vxml',
                ],
            'application/winhlp' =>
                [
                    0 => 'hlp',
                ],
            'application/wsdl+xml' =>
                [
                    0 => 'wsdl',
                ],
            'application/wspolicy+xml' =>
                [
                    0 => 'wspolicy',
                ],
            'application/x-ace-compressed' =>
                [
                    0 => 'ace',
                ],
            'application/x-bcpio' =>
                [
                    0 => 'bcpio',
                ],
            'application/x-bittorrent' =>
                [
                    0 => 'torrent',
                ],
            'application/x-bzip' =>
                [
                    0 => 'bz',
                ],
            'application/x-bzip2' =>
                [
                    0 => 'bz2',
                    1 => 'boz',
                ],
            'application/x-cdlink' =>
                [
                    0 => 'vcd',
                ],
            'application/x-chat' =>
                [
                    0 => 'chat',
                ],
            'application/x-chess-pgn' =>
                [
                    0 => 'pgn',
                ],
            'application/x-cpio' =>
                [
                    0 => 'cpio',
                ],
            'application/x-csh' =>
                [
                    0 => 'csh',
                ],
            'application/x-director' =>
                [
                    0 => 'dcr',
                    1 => 'dir',
                    2 => 'dxr',
                    3 => 'fgd',
                ],
            'application/x-dvi' =>
                [
                    0 => 'dvi',
                ],
            'application/x-futuresplash' =>
                [
                    0 => 'spl',
                ],
            'application/x-gtar' =>
                [
                    0 => 'gtar',
                ],
            'application/x-hdf' =>
                [
                    0 => 'hdf',
                ],
            'application/x-latex' =>
                [
                    0 => 'latex',
                ],
            'application/x-ms-wmd' =>
                [
                    0 => 'wmd',
                ],
            'application/x-ms-wmz' =>
                [
                    0 => 'wmz',
                ],
            'application/x-msaccess' =>
                [
                    0 => 'mdb',
                ],
            'application/x-msbinder' =>
                [
                    0 => 'obd',
                ],
            'application/x-mscardfile' =>
                [
                    0 => 'crd',
                ],
            'application/x-msclip' =>
                [
                    0 => 'clp',
                ],
            'application/x-msdownload' =>
                [
                    0 => 'exe',
                    1 => 'dll',
                    2 => 'com',
                    3 => 'bat',
                    4 => 'msi',
                ],
            'application/x-msmediaview' =>
                [
                    0 => 'mvb',
                    1 => 'm13',
                    2 => 'm14',
                ],
            'application/x-msmetafile' =>
                [
                    0 => 'wmf',
                ],
            'application/x-msmoney' =>
                [
                    0 => 'mny',
                ],
            'application/x-mspublisher' =>
                [
                    0 => 'pub',
                ],
            'application/x-msschedule' =>
                [
                    0 => 'scd',
                ],
            'application/x-msterminal' =>
                [
                    0 => 'trm',
                ],
            'application/x-mswrite' =>
                [
                    0 => 'wri',
                ],
            'application/x-netcdf' =>
                [
                    0 => 'nc',
                    1 => 'cdf',
                ],
            'application/x-pkcs12' =>
                [
                    0 => 'p12',
                    1 => 'pfx',
                ],
            'application/x-pkcs7-certificates' =>
                [
                    0 => 'p7b',
                    1 => 'spc',
                ],
            'application/x-pkcs7-certreqresp' =>
                [
                    0 => 'p7r',
                ],
            'application/x-rar-compressed' =>
                [
                    0 => 'rar',
                ],
            'application/x-sh' =>
                [
                    0 => 'sh',
                ],
            'application/x-shar' =>
                [
                    0 => 'shar',
                ],
            'application/x-shockwave-flash' =>
                [
                    0 => 'swf',
                ],
            'application/x-stuffit' =>
                [
                    0 => 'sit',
                ],
            'application/x-stuffitx' =>
                [
                    0 => 'sitx',
                ],
            'application/x-sv4cpio' =>
                [
                    0 => 'sv4cpio',
                ],
            'application/x-sv4crc' =>
                [
                    0 => 'sv4crc',
                ],
            'application/x-tar' =>
                [
                    0 => 'tar',
                ],
            'application/x-tcl' =>
                [
                    0 => 'tcl',
                ],
            'application/x-tex' =>
                [
                    0 => 'tex',
                ],
            'application/x-texinfo' =>
                [
                    0 => 'texinfo',
                    1 => 'texi',
                ],
            'application/x-ustar' =>
                [
                    0 => 'ustar',
                ],
            'application/x-wais-source' =>
                [
                    0 => 'src',
                ],
            'application/x-x509-ca-cert' =>
                [
                    0 => 'der',
                    1 => 'crt',
                ],
            'application/xenc+xml' =>
                [
                    0 => 'xenc',
                ],
            'application/xhtml+xml' =>
                [
                    0 => 'xhtml',
                    1 => 'xht',
                ],
            'application/xml' =>
                [
                    0 => 'xml',
                    1 => 'xsl',
                ],
            'application/xml-dtd' =>
                [
                    0 => 'dtd',
                ],
            'application/xop+xml' =>
                [
                    0 => 'xop',
                ],
            'application/xslt+xml' =>
                [
                    0 => 'xslt',
                ],
            'application/xspf+xml' =>
                [
                    0 => 'xspf',
                ],
            'application/xv+xml' =>
                [
                    0 => 'mxml',
                    1 => 'xhvml',
                    2 => 'xvml',
                    3 => 'xvm',
                ],
            'application/zip' =>
                [
                    0 => 'zip',
                ],
            'audio/basic' =>
                [
                    0 => 'au',
                    1 => 'snd',
                ],
            'audio/midi' =>
                [
                    0 => 'mid',
                    1 => 'midi',
                    2 => 'kar',
                    3 => 'rmi',
                ],
            'audio/mp4' =>
                [
                    0 => 'mp4a',
                ],
            'audio/mpeg' =>
                [
                    0 => 'mpga',
                    1 => 'mp2',
                    2 => 'mp2a',
                    3 => 'mp3',
                    4 => 'm2a',
                    5 => 'm3a',
                ],
            'audio/vnd.digital-winds' =>
                [
                    0 => 'eol',
                ],
            'audio/vnd.lucent.voice' =>
                [
                    0 => 'lvp',
                ],
            'audio/vnd.nuera.ecelp4800' =>
                [
                    0 => 'ecelp4800',
                ],
            'audio/vnd.nuera.ecelp7470' =>
                [
                    0 => 'ecelp7470',
                ],
            'audio/vnd.nuera.ecelp9600' =>
                [
                    0 => 'ecelp9600',
                ],
            'audio/wav' =>
                [
                    0 => 'wav',
                ],
            'audio/x-aiff' =>
                [
                    0 => 'aif',
                    1 => 'aiff',
                    2 => 'aifc',
                ],
            'audio/x-mpegurl' =>
                [
                    0 => 'm3u',
                ],
            'audio/x-ms-wax' =>
                [
                    0 => 'wax',
                ],
            'audio/x-ms-wma' =>
                [
                    0 => 'wma',
                ],
            'audio/x-pn-realaudio' =>
                [
                    0 => 'ram',
                    1 => 'ra',
                ],
            'audio/x-pn-realaudio-plugin' =>
                [
                    0 => 'rmp',
                ],
            'audio/x-wav' =>
                [
                    0 => 'wav',
                ],
            'chemical/x-cdx' =>
                [
                    0 => 'cdx',
                ],
            'chemical/x-cif' =>
                [
                    0 => 'cif',
                ],
            'chemical/x-cmdf' =>
                [
                    0 => 'cmdf',
                ],
            'chemical/x-cml' =>
                [
                    0 => 'cml',
                ],
            'chemical/x-csml' =>
                [
                    0 => 'csml',
                ],
            'chemical/x-pdb' =>
                [
                    0 => 'pdb',
                ],
            'chemical/x-xyz' =>
                [
                    0 => 'xyz',
                ],
            'image/bmp' =>
                [
                    0 => 'bmp',
                ],
            'image/cgm' =>
                [
                    0 => 'cgm',
                ],
            'image/g3fax' =>
                [
                    0 => 'g3',
                ],
            'image/gif' =>
                [
                    0 => 'gif',
                ],
            'image/ief' =>
                [
                    0 => 'ief',
                ],
            'image/jpeg' =>
                [
                    0 => 'jpeg',
                    1 => 'jpg',
                    2 => 'jpe',
                ],
            'image/png' =>
                [
                    0 => 'png',
                ],
            'image/prs.btif' =>
                [
                    0 => 'btif',
                ],
            'image/svg+xml' =>
                [
                    0 => 'svg',
                    1 => 'svgz',
                ],
            'image/tiff' =>
                [
                    0 => 'tiff',
                    1 => 'tif',
                ],
            'image/vnd.adobe.photoshop' =>
                [
                    0 => 'psd',
                ],
            'image/vnd.djvu' =>
                [
                    0 => 'djvu',
                    1 => 'djv',
                ],
            'image/vnd.dwg' =>
                [
                    0 => 'dwg',
                ],
            'image/vnd.dxf' =>
                [
                    0 => 'dxf',
                ],
            'image/vnd.fastbidsheet' =>
                [
                    0 => 'fbs',
                ],
            'image/vnd.fpx' =>
                [
                    0 => 'fpx',
                ],
            'image/vnd.fst' =>
                [
                    0 => 'fst',
                ],
            'image/vnd.fujixerox.edmics-mmr' =>
                [
                    0 => 'mmr',
                ],
            'image/vnd.fujixerox.edmics-rlc' =>
                [
                    0 => 'rlc',
                ],
            'image/vnd.ms-modi' =>
                [
                    0 => 'mdi',
                ],
            'image/vnd.net-fpx' =>
                [
                    0 => 'npx',
                ],
            'image/vnd.wap.wbmp' =>
                [
                    0 => 'wbmp',
                ],
            'image/vnd.xiff' =>
                [
                    0 => 'xif',
                ],
            'image/x-cmu-raster' =>
                [
                    0 => 'ras',
                ],
            'image/x-cmx' =>
                [
                    0 => 'cmx',
                ],
            'image/x-icon' =>
                [
                    0 => 'ico',
                ],
            'image/x-pcx' =>
                [
                    0 => 'pcx',
                ],
            'image/x-pict' =>
                [
                    0 => 'pic',
                    1 => 'pct',
                ],
            'image/x-portable-anymap' =>
                [
                    0 => 'pnm',
                ],
            'image/x-portable-bitmap' =>
                [
                    0 => 'pbm',
                ],
            'image/x-portable-graymap' =>
                [
                    0 => 'pgm',
                ],
            'image/x-portable-pixmap' =>
                [
                    0 => 'ppm',
                ],
            'image/x-rgb' =>
                [
                    0 => 'rgb',
                ],
            'image/x-xbitmap' =>
                [
                    0 => 'xbm',
                ],
            'image/x-xpixmap' =>
                [
                    0 => 'xpm',
                ],
            'image/x-xwindowdump' =>
                [
                    0 => 'xwd',
                ],
            'message/rfc822' =>
                [
                    0 => 'eml',
                    1 => 'mime',
                ],
            'model/iges' =>
                [
                    0 => 'igs',
                    1 => 'iges',
                ],
            'model/mesh' =>
                [
                    0 => 'msh',
                    1 => 'mesh',
                    2 => 'silo',
                ],
            'model/vnd.dwf' =>
                [
                    0 => 'dwf',
                ],
            'model/vnd.gdl' =>
                [
                    0 => 'gdl',
                ],
            'model/vnd.gtw' =>
                [
                    0 => 'gtw',
                ],
            'model/vnd.mts' =>
                [
                    0 => 'mts',
                ],
            'model/vnd.vtu' =>
                [
                    0 => 'vtu',
                ],
            'model/vrml' =>
                [
                    0 => 'wrl',
                    1 => 'vrml',
                ],
            'text/calendar' =>
                [
                    0 => 'ics',
                    1 => 'ifb',
                ],
            'text/css' =>
                [
                    0 => 'css',
                ],
            'text/csv' =>
                [
                    0 => 'csv',
                ],
            'text/html' =>
                [
                    0 => 'html',
                    1 => 'htm',
                ],
            'text/plain' =>
                [
                    0 => 'txt',
                    1 => 'text',
                    2 => 'conf',
                    3 => 'def',
                    4 => 'list',
                    5 => 'log',
                    6 => 'in',
                ],
            'text/prs.lines.tag' =>
                [
                    0 => 'dsc',
                ],
            'text/richtext' =>
                [
                    0 => 'rtx',
                ],
            'text/sgml' =>
                [
                    0 => 'sgml',
                    1 => 'sgm',
                ],
            'text/tab-separated-values' =>
                [
                    0 => 'tsv',
                ],
            'text/troff' =>
                [
                    0 => 't',
                    1 => 'tr',
                    2 => 'roff',
                    3 => 'man',
                    4 => 'me',
                    5 => 'ms',
                ],
            'text/uri-list' =>
                [
                    0 => 'uri',
                    1 => 'uris',
                    2 => 'urls',
                ],
            'text/vnd.fly' =>
                [
                    0 => 'fly',
                ],
            'text/vnd.fmi.flexstor' =>
                [
                    0 => 'flx',
                ],
            'text/vnd.in3d.3dml' =>
                [
                    0 => '3dml',
                ],
            'text/vnd.in3d.spot' =>
                [
                    0 => 'spot',
                ],
            'text/vnd.sun.j2me.app-descriptor' =>
                [
                    0 => 'jad',
                ],
            'text/vnd.wap.wml' =>
                [
                    0 => 'wml',
                ],
            'text/vnd.wap.wmlscript' =>
                [
                    0 => 'wmls',
                ],
            'text/x-asm' =>
                [
                    0 => 's',
                    1 => 'asm',
                ],
            'text/x-c' =>
                [
                    0 => 'c',
                    1 => 'cc',
                    2 => 'cxx',
                    3 => 'cpp',
                    4 => 'h',
                    5 => 'hh',
                    6 => 'dic',
                ],
            'text/x-fortran' =>
                [
                    0 => 'f',
                    1 => 'for',
                    2 => 'f77',
                    3 => 'f90',
                ],
            'text/x-pascal' =>
                [
                    0 => 'p',
                    1 => 'pas',
                ],
            'text/x-java-source' =>
                [
                    0 => 'java',
                ],
            'text/x-setext' =>
                [
                    0 => 'etx',
                ],
            'text/x-uuencode' =>
                [
                    0 => 'uu',
                ],
            'text/x-vcalendar' =>
                [
                    0 => 'vcs',
                ],
            'text/x-vcard' =>
                [
                    0 => 'vcf',
                ],
            'video/3gpp' =>
                [
                    0 => '3gp',
                ],
            'video/3gpp2' =>
                [
                    0 => '3g2',
                ],
            'video/h261' =>
                [
                    0 => 'h261',
                ],
            'video/h263' =>
                [
                    0 => 'h263',
                ],
            'video/h264' =>
                [
                    0 => 'h264',
                ],
            'video/jpeg' =>
                [
                    0 => 'jpgv',
                ],
            'video/jpm' =>
                [
                    0 => 'jpm',
                    1 => 'jpgm',
                ],
            'video/mj2' =>
                [
                    0 => 'mj2',
                    1 => 'mjp2',
                ],
            'video/mp4' =>
                [
                    0 => 'mp4',
                    1 => 'mp4v',
                    2 => 'mpg4',
                ],
            'video/mpeg' =>
                [
                    0 => 'mpeg',
                    1 => 'mpg',
                    2 => 'mpe',
                    3 => 'm1v',
                    4 => 'm2v',
                ],
            'video/quicktime' =>
                [
                    0 => 'qt',
                    1 => 'mov',
                ],
            'video/vnd.fvt' =>
                [
                    0 => 'fvt',
                ],
            'video/vnd.mpegurl' =>
                [
                    0 => 'mxu',
                    1 => 'm4u',
                ],
            'video/vnd.vivo' =>
                [
                    0 => 'viv',
                ],
            'video/x-fli' =>
                [
                    0 => 'fli',
                ],
            'video/x-ms-asf' =>
                [
                    0 => 'asf',
                    1 => 'asx',
                ],
            'video/x-ms-wm' =>
                [
                    0 => 'wm',
                ],
            'video/x-ms-wmv' =>
                [
                    0 => 'wmv',
                ],
            'video/x-ms-wmx' =>
                [
                    0 => 'wmx',
                ],
            'video/x-ms-wvx' =>
                [
                    0 => 'wvx',
                ],
            'video/x-msvideo' =>
                [
                    0 => 'avi',
                ],
            'video/x-sgi-movie' =>
                [
                    0 => 'movie',
                ],
            'x-conference/x-cooltalk' =>
                [
                    0 => 'ice',
                ],
        ];
    }

    public static function findMimeByExt($ext)
    {
        if (!$ext) {
            return null;
        }

        $ext = lcFiles::fixFileExt($ext);

        $list = self::getList();

        foreach ($list as $key => $exts) {
            if (@!in_array($ext, $exts)) {
                continue;
            }

            return $key;
        }

        return null;
    }

    public static function findExtsByMime($mimetype)
    {
        if (!$mimetype) {
            return false;
        }

        $list = self::getList();

        return $list[$mimetype];
    }
}
