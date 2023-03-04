<?php
/*
 * Lightcast - A Complete MVC/PHP/XSLT based Framework
 * Copyright (C) 2005-2008 Nimasystems Ltd
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
 * Address: 17 "Tcanko Diustabanov" Str., 2nd Floor
 * General E-Mail: info@nimasystems.com
 */

/**
 * File Description
 * @package File Category
 * @subpackage File Subcategory
 * @author Nimasystems Ltd <support@nimasystems.com>
 * @version $Revision: 0 $
 */
class HtmlDoc extends lcObj
{
    const HTMLDOCPATH = '/usr/bin/htmldoc';

    const DEFAULT_PAGE_FORMAT = 'a4';
    const DEFAULT_CHARSET = 'cp-1251';

    const DEFAULT_HEADER = '...';
    const DEFAULT_FOOTER = '...';

    # up to 100
    const DEFAULT_JPEG_QUALITY = 80;

    const DEFAULT_LEFT_MARGIN = 10;
    const DEFAULT_RIGHT_MARGIN = 10;
    const DEFAULT_TOP_MARGIN = 10;
    const DEFAULT_BOTTOM_MARGIN = 10;

    # htmldoc command
    private $htmldoc_cmd = self::HTMLDOCPATH;

    # the filename for the inline created page
    private $pdf_filename;

    # the size of the page
    private $page_format;

    # the generated pdf content in a temp file
    private $generated_filename;
    private $generated_fpointer;

    # the input html document
    private $html_doc;

    # the pheader format string
    private $pheader;

    # the pfooter format string
    private $pfooter = 'Business solutions for the internet - NIMASYSTEMS (www.nimasystems.com)';

    # the left margin of the pages
    private $lmargin;

    # the right margin of the pages
    private $rmargin;

    # the top margin of the pages
    private $tmargin;

    # the bottom margin of the pages
    private $bmargin;

    # html doc charset
    private $charset;

    # jpeg quality
    private $jpeg_quality;

    # web referrer
    private $referrer;

    public function __construct($htmldoc_path = self::HTMLDOCPATH)
    {
        parent::__construct();

        if (isset($htmldoc_path)) {
            $this->htmldoc_cmd = $htmldoc_path;
        }

        # init default values
        $this->page_format = self::DEFAULT_PAGE_FORMAT;
        $this->pheader = self::DEFAULT_HEADER;
        $this->pfooter = self::DEFAULT_FOOTER;
        $this->lmargin = self::DEFAULT_LEFT_MARGIN;
        $this->rmargin = self::DEFAULT_RIGHT_MARGIN;
        $this->tmargin = self::DEFAULT_TOP_MARGIN;
        $this->bmargin = self::DEFAULT_BOTTOM_MARGIN;
        $this->charset = self::DEFAULT_CHARSET;
        $this->jpeg_quality = self::DEFAULT_JPEG_QUALITY;
    }

    public function __destruct()
    {
        unset($this->data);
        unset($this->html_doc);

        if ($this->generated_fpointer) {
            fclose($this->generated_fpointer);

            try {
                lcFiles::rm($this->generated_filename);
            } catch (Exception $e) {
            }
        }

        parent::__destruct();
    }

    public function setHtmlDocument($htmlfile, $fname = null)
    {
        $this->html_doc = $htmlfile;

        if (!isset($fname)) {
            $this->pdf_filename = basename($htmlfile);
        } else {
            $this->pdf_filename = $fname;
        }

        $this->pdf_filename = lcFiles::splitFileName($this->pdf_filename);
        $this->pdf_filename = $this->pdf_filename['name'] . '.pdf';
    }

    public function generatePDF()
    {
        $configuration = lcApp::getInstance()->getConfiguration();

        $this->generated_filename = $configuration->getTempDir() . DS .
            lcStrings::randomString(20) . '.pdf';

        $cmd = [];
        $cmd[] = $this->htmldoc_cmd;
        $cmd[] = '--no-compression';
        $cmd[] = '--bodyfont times';
        $cmd[] = '-t pdf14';
        $cmd[] = '--quiet';
        $cmd[] = '--jpeg=' . $this->jpeg_quality;
        $cmd[] = '--charset ' . $this->charset;
        $cmd[] = '--webpage';
        $cmd[] = '--header ' . $this->pheader;
        $cmd[] = '--footer ' . $this->pfooter;
        $cmd[] = '--referer' . $this->referrer;
        $cmd[] = '--size ' . $this->page_format;
        $cmd[] = '--left ' . $this->lmargin . 'mm';
        $cmd[] = '--right ' . $this->rmargin . 'mm';
        $cmd[] = '--top ' . $this->tmargin . 'mm';
        $cmd[] = '--bottom ' . $this->bmargin . 'mm';
        $cmd[] = $this->html_doc;
        $cmd[] = ' > ' . $this->generated_filename;

        $cmd = implode(' ', $cmd);

        # generate it
        passthru($cmd, $res);

        if (!$res) {
            throw new lcSystemException('Cannot generated the PDF - Parser returned errors');
        }

        # get the generated data
        if (!$this->generated_fpointer = fopen($this->generated_filename, 'rb')) {
            throw new lcSystemException('Cannot write generated PDF file');
        }

        return $this->generated_fpointer;
    }

    public function getPDFPointer()
    {
        return $this->generated_fpointer;
    }

    public function setTopMargin($value)
    {
        $this->tmargin = $value;
    }

    public function setBottomMargin($value)
    {
        $this->bmargin = $value;
    }

    public function setLeftMargin($value)
    {
        $this->lmargin = $value;
    }

    public function setRightMargin($value)
    {
        $this->rmargin = $value;
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    public function HtmldocCommand($cmdline)
    {
        $this->htmldoc_cmd = $cmdline;
    }

    public function setJPEGQuality($quality_level)
    {
        $this->jpeg_quality = $quality_level;
    }

    public function setPageFormat($page_format)
    {
        $this->page_format = $page_format;
    }

    public function setPageFooter($value)
    {
        if (!$this->checkHeaderFooterSyntax($value)) {
            throw new lcSystemException('Invalid Page Footer');
        }

        $this->pfooter = $value;
    }

    public function setPageHeader($value)
    {
        if (!$this->checkHeaderFooterSyntax($value)) {
            throw new lcSystemException('Invalid Page Header');
        }

        $this->pheader = $value;
    }

    public function setOutputFilename($filename)
    {
        $this->pdf_filename = $filename;
    }

    private function checkHeaderFooterSyntax($value)
    {
        # Defines the valid characters for the format string
        $validchars = './:1aAcCdDhiIltT';

        # The format string must have a length of 3 chars
        if (strlen($value) <> 3) {
            return false;
        }

        if (!strstr($validchars, substr($value, 0, 1)) ||
            !strstr($validchars, substr($value, 1, 1)) ||
            !strstr($validchars, substr($value, 2, 1))) {
            return false;
        }

        return true;
    }

    public function outputPdf($download = false)
    {
        if (!$this->generated_fpointer) $this->generatePDF();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=' . $this->pdf_filename);

        fpassthru($this->generated_fpointer);
    }

    public function savePdf($fname = null)
    {
        if (!$fname) $fname = $this->pdf_filename;
        $configuration = lcApp::getInstance()->getConfiguration();

        $this->generated_filename = $configuration->getTempDir() . DS . $fname;

        $cmd = [];
        $cmd[] = $this->htmldoc_cmd;
        $cmd[] = '--no-compression';
        $cmd[] = '--bodyfont times';
        $cmd[] = '-t pdf14';
        $cmd[] = '--quiet';
        $cmd[] = '--jpeg=' . $this->jpeg_quality;
        $cmd[] = '--charset ' . $this->charset;
        $cmd[] = '--webpage';
        $cmd[] = '--header ' . $this->pheader;
        $cmd[] = '--footer ' . $this->pfooter;
        $cmd[] = '--referer' . $this->referrer;
        $cmd[] = '--size ' . $this->page_format;
        $cmd[] = '--left ' . $this->lmargin . 'mm';
        $cmd[] = '--right ' . $this->rmargin . 'mm';
        $cmd[] = '--top ' . $this->tmargin . 'mm';
        $cmd[] = '--bottom ' . $this->bmargin . 'mm';
        $cmd[] = $this->html_doc;
        $cmd[] = ' > ' . $this->generated_filename;

        $cmd = implode(' ', $cmd);

        # generate it
        passthru($cmd, $res);

        if (!$res) {
            throw new lcSystemException('Cannot generated the PDF - Parser returned errors');
        }

        # check the generated flle
        if (!lcFiles::exists($this->generated_filename)) {
            throw new lcSystemException('Cannot write generated PDF file');
        }

        return $this->generated_filename;
    }
}
