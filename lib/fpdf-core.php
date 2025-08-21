<?php
/*******************************************************************************
* FPDF                                                                         *
*                                                                              *
* Version: 1.85                                                                *
* Date:    2021-08-28                                                          *
* Author:  Olivier PLATHEY                                                     *
*******************************************************************************/

define('FPDF_VERSION','1.85');

class FPDF
{
protected $page;               // current page number
protected $n;                  // current object number
protected $offsets;            // array of object offsets
protected $buffer;             // buffer holding in-memory PDF
protected $pages;              // array containing pages
protected $state;              // current document state
protected $compress;           // compression flag
protected $k;                  // scale factor (number of points in user unit)
protected $DefOrientation;     // default orientation
protected $CurOrientation;     // current orientation
protected $StdPageSizes;       // standard page sizes
protected $DefPageSize;        // default page size
protected $CurPageSize;        // current page size
protected $CurRotation;        // current page rotation
protected $PageInfo;           // page-related data
protected $x, $y;              // current position in user unit
protected $lasth;              // height of last printed cell
protected $LineWidth;          // line width in user unit
protected $fontpath;           // path containing fonts
protected $CoreFonts;          // array of core font names
protected $fonts;              // array of used fonts
protected $FontFiles;          // array of font files
protected $encodings;          // array of encodings
protected $cmaps;              // array of ToUnicode CMaps
protected $FontFamily;         // current font family
protected $FontStyle;          // current font style
protected $underline;          // underlining flag
protected $CurrentFont;        // current font info
protected $FontSizePt;         // current font size in points
protected $FontSize;           // current font size in user unit
protected $DrawColor;          // commands for drawing color
protected $FillColor;          // commands for filling color
protected $TextColor;          // commands for text color
protected $ColorFlag;          // indicates whether fill and text colors are different
protected $WithAlpha;          // indicates whether alpha channel is used
protected $ws;                 // word spacing
protected $images;             // array of used images
protected $PageLinks;          // array of links in pages
protected $links;              // array of internal links
protected $AutoPageBreak;      // automatic page breaking
protected $PageBreakTrigger;   // threshold used to trigger page breaks
protected $InHeader;           // flag set when processing header
protected $InFooter;           // flag set when processing footer
protected $AliasNbPages;       // alias for total number of pages
protected $ZoomMode;           // zoom display mode
protected $LayoutMode;         // layout display mode
protected $metadata;           // document properties
protected $PDFVersion;         // PDF version number

function __construct($orientation='P', $unit='mm', $size='A4')
{
    // Some checks
    $this->_dochecks();
    // Initialization of properties
    $this->state = 0;
    $this->page = 0;
    $this->n = 2;
    $this->buffer = '';
    $this->pages = array();
    $this->PageInfo = array();
    $this->fonts = array();
    $this->FontFiles = array();
    $this->encodings = array();
    $this->cmaps = array();
    $this->images = array();
    $this->links = array();
    $this->InHeader = false;
    $this->InFooter = false;
    $this->lasth = 0;
    $this->FontFamily = '';
    $this->FontStyle = '';
    $this->FontSizePt = 12;
    $this->underline = false;
    $this->DrawColor = '0 G';
    $this->FillColor = '0 g';
    $this->TextColor = '0 g';
    $this->ColorFlag = false;
    $this->WithAlpha = false;
    $this->ws = 0;
    // Font path
    if(defined('FPDF_FONTPATH'))
    {
        $this->fontpath = FPDF_FONTPATH;
        if(substr($this->fontpath,-1)!='/' && substr($this->fontpath,-1)!='\\')
            $this->fontpath .= '/';
    }
    elseif(is_dir(dirname(__FILE__).'/font'))
        $this->fontpath = dirname(__FILE__).'/font/';
    else
        $this->fontpath = '';
    // Core fonts
    $this->CoreFonts = array('courier', 'helvetica', 'times', 'symbol', 'zapfdingbats');
    // Scale factor
    if($unit=='pt')
        $this->k = 1;
    elseif($unit=='mm')
        $this->k = 72/25.4;
    elseif($unit=='cm')
        $this->k = 72/2.54;
    elseif($unit=='in')
        $this->k = 72;
    else
        $this->Error('Incorrect unit: '.$unit);
    // Page sizes
    $this->StdPageSizes = array('a3'=>array(841.89,1190.55), 'a4'=>array(595.28,841.89), 'a5'=>array(420.94,595.28),
        'letter'=>array(612,792), 'legal'=>array(612,1008));
    if(is_string($size))
        $size = $this->_getpagesize($size);
    $this->DefPageSize = $size;
    $this->CurPageSize = $size;
    // Page orientation
    $orientation = strtolower($orientation);
    if($orientation=='p' || $orientation=='portrait')
    {
        $this->DefOrientation = 'P';
        $this->w = $size[0];
        $this->h = $size[1];
    }
    elseif($orientation=='l' || $orientation=='landscape')
    {
        $this->DefOrientation = 'L';
        $this->w = $size[1];
        $this->h = $size[0];
    }
    else
        $this->Error('Incorrect orientation: '.$orientation);
    $this->CurOrientation = $this->DefOrientation;
    $this->wPt = $this->w*$this->k;
    $this->hPt = $this->h*$this->k;
    // Page rotation
    $this->CurRotation = 0;
    // Page margins (1 cm)
    $margin = 28.35/$this->k;
    $this->SetMargins($margin,$margin);
    // Interior cell margin (1 mm)
    $this->cMargin = $margin/10;
    // Line width (0.2 mm)
    $this->LineWidth = .567/$this->k;
    // Automatic page break
    $this->SetAutoPageBreak(true,2*$margin);
    // Default display mode
    $this->SetDisplayMode('default');
    // Enable compression
    $this->SetCompression(true);
    // Set default PDF version number
    $this->PDFVersion = '1.3';
}

function SetMargins($left, $top, $right=null)
{
    // Set left, top and right margins
    $this->lMargin = $left;
    $this->tMargin = $top;
    if($right===null)
        $right = $left;
    $this->rMargin = $right;
}

function SetLeftMargin($margin)
{
    // Set left margin
    $this->lMargin = $margin;
    if($this->page>0 && $this->x<$margin)
        $this->x = $margin;
}

function SetTopMargin($margin)
{
    // Set top margin
    $this->tMargin = $margin;
}

function SetRightMargin($margin)
{
    // Set right margin
    $this->rMargin = $margin;
}

function SetAutoPageBreak($auto, $margin=0)
{
    // Set auto page break mode and triggering margin
    $this->AutoPageBreak = $auto;
    $this->bMargin = $margin;
    $this->PageBreakTrigger = $this->h-$margin;
}

function SetDisplayMode($zoom, $layout='default')
{
    // Set display mode in viewer
    if($zoom=='fullpage' || $zoom=='fullwidth' || $zoom=='real' || $zoom=='default' || !is_string($zoom))
        $this->ZoomMode = $zoom;
    else
        $this->Error('Incorrect zoom display mode: '.$zoom);
    if($layout=='single' || $layout=='continuous' || $layout=='two' || $layout=='default')
        $this->LayoutMode = $layout;
    else
        $this->Error('Incorrect layout display mode: '.$layout);
}

function SetCompression($compress)
{
    // Set page compression
    if(function_exists('gzcompress'))
        $this->compress = $compress;
    else
        $this->compress = false;
}

function SetTitle($title, $isUTF8=false)
{
    // Title of document
    if($isUTF8)
        $title = $this->_UTF8toUTF16($title);
    $this->metadata['Title'] = $title;
}

function SetSubject($subject, $isUTF8=false)
{
    // Subject of document
    if($isUTF8)
        $subject = $this->_UTF8toUTF16($subject);
    $this->metadata['Subject'] = $subject;
}

function SetAuthor($author, $isUTF8=false)
{
    // Author of document
    if($isUTF8)
        $author = $this->_UTF8toUTF16($author);
    $this->metadata['Author'] = $author;
}

function SetKeywords($keywords, $isUTF8=false)
{
    // Keywords of document
    if($isUTF8)
        $keywords = $this->_UTF8toUTF16($keywords);
    $this->metadata['Keywords'] = $keywords;
}

function SetCreator($creator, $isUTF8=false)
{
    // Creator of document
    if($isUTF8)
        $creator = $this->_UTF8toUTF16($creator);
    $this->metadata['Creator'] = $creator;
}

function AliasNbPages($alias='{nb}')
{
    // Define an alias for total number of pages
    $this->AliasNbPages = $alias;
}

function Error($msg)
{
    // Fatal error
    throw new Exception('FPDF error: '.$msg);
}

function Open()
{
    $this->state = 1;
}

function Close()
{
    // Terminate document
    if($this->state==3)
        return;
    if($this->page==0)
        $this->AddPage();
    // Page footer
    $this->InFooter = true;
    $this->Footer();
    $this->InFooter = false;
    // Close page
    $this->_endpage();
    // Close document
    $this->_enddoc();
}

function AddPage($orientation='', $size='', $rotation=0)
{
    // Start a new page
    if($this->state==3)
        $this->Error('The document is closed');
    $family = $this->FontFamily;
    $style = $this->FontStyle.($this->underline ? 'U' : '');
    $fontsize = $this->FontSizePt;
    $lw = $this->LineWidth;
    $dc = $this->DrawColor;
    $fc = $this->FillColor;
    $tc = $this->TextColor;
    $cf = $this->ColorFlag;
    if($this->page>0)
    {
        // Page footer
        $this->InFooter = true;
        $this->Footer();
        $this->InFooter = false;
        // Close page
        $this->_endpage();
    }
    // Start new page
    $this->_beginpage($orientation,$size,$rotation);
    // Set line cap style to square
    $this->_out('2 J');
    // Set line width
    $this->LineWidth = $lw;
    $this->_out(sprintf('%.2F w',$lw*$this->k));
    // Set font
    if($family)
        $this->SetFont($family,$style,$fontsize);
    // Set colors
    $this->DrawColor = $dc;
    if($dc!='0 G')
        $this->_out($dc);
    $this->FillColor = $fc;
    if($fc!='0 g')
        $this->_out($fc);
    $this->TextColor = $tc;
    $this->ColorFlag = $cf;
    // Page header
    $this->InHeader = true;
    $this->Header();
    $this->InHeader = false;
    // Restore line width
    if($this->LineWidth!=$lw)
    {
        $this->LineWidth = $lw;
        $this->_out(sprintf('%.2F w',$lw*$this->k));
    }
    // Restore font
    if($family)
        $this->SetFont($family,$style,$fontsize);
    // Restore colors
    if($this->DrawColor!=$dc)
    {
        $this->DrawColor = $dc;
        $this->_out($dc);
    }
    if($this->FillColor!=$fc)
    {
        $this->FillColor = $fc;
        $this->_out($fc);
    }
    $this->TextColor = $tc;
    $this->ColorFlag = $cf;
}

function Header()
{
    // To be implemented in your own inherited class
}

function Footer()
{
    // To be implemented in your own inherited class
}

function PageNo()
{
    // Get current page number
    return $this->page;
}
