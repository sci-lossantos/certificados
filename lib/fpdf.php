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

function SetDrawColor($r, $g=null, $b=null)
{
    // Set color for all stroking operations
    if(($r==0 && $g==0 && $b==0) || $g===null)
        $this->DrawColor = sprintf('%.3F G',$r/255);
    else
        $this->DrawColor = sprintf('%.3F %.3F %.3F RG',$r/255,$g/255,$b/255);
    if($this->page>0)
        $this->_out($this->DrawColor);
}

function SetFillColor($r, $g=null, $b=null)
{
    // Set color for all filling operations
    if(($r==0 && $g==0 && $b==0) || $g===null)
        $this->FillColor = sprintf('%.3F g',$r/255);
    else
        $this->FillColor = sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255);
    $this->ColorFlag = ($this->FillColor!=$this->TextColor);
    if($this->page>0)
        $this->_out($this->FillColor);
}

function SetTextColor($r, $g=null, $b=null)
{
    // Set color for text
    if(($r==0 && $g==0 && $b==0) || $g===null)
        $this->TextColor = sprintf('%.3F g',$r/255);
    else
        $this->TextColor = sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255);
    $this->ColorFlag = ($this->FillColor!=$this->TextColor);
}

function GetStringWidth($s)
{
    // Get width of a string in the current font
    $s = (string)$s;
    $cw = &$this->CurrentFont['cw'];
    $w = 0;
    $l = strlen($s);
    for($i=0;$i<$l;$i++)
        $w += $cw[$s[$i]];
    return $w*$this->FontSize/1000;
}

function SetLineWidth($width)
{
    // Set line width
    $this->LineWidth = $width;
    if($this->page>0)
        $this->_out(sprintf('%.2F w',$width*$this->k));
}

function Line($x1, $y1, $x2, $y2)
{
    // Draw a line
    $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S',$x1*$this->k,($this->h-$y1)*$this->k,$x2*$this->k,($this->h-$y2)*$this->k));
}

function Rect($x, $y, $w, $h, $style='')
{
    // Draw a rectangle
    if($style=='F')
        $op = 'f';
    elseif($style=='FD' || $style=='DF')
        $op = 'B';
    else
        $op = 'S';
    $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s',$x*$this->k,($this->h-$y)*$this->k,$w*$this->k,-$h*$this->k,$op));
}

function AddFont($family, $style='', $file='')
{
    // Add a TrueType, OpenType or Type1 font
    $family = strtolower($family);
    if($file=='')
        $file = str_replace(' ','',$family).strtolower($style).'.php';
    $style = strtoupper($style);
    if($style=='IB')
        $style = 'BI';
    $fontkey = $family.$style;
    if(isset($this->fonts[$fontkey]))
        return;
    $info = $this->_loadfont($file);
    $info['i'] = count($this->fonts)+1;
    if(!empty($info['file']))
    {
        // Embedded font
        if($info['type']=='TrueType')
            $this->FontFiles[$info['file']] = array('length1'=>$info['originalsize']);
        else
            $this->FontFiles[$info['file']] = array('length1'=>$info['size1'], 'length2'=>$info['size2']);
    }
    $this->fonts[$fontkey] = $info;
}

function SetFont($family, $style='', $size=0)
{
    // Select a font; size given in points
    if($family=='')
        $family = $this->FontFamily;
    else
        $family = strtolower($family);
    $style = strtoupper($style);
    if(strpos($style,'U')!==false)
    {
        $this->underline = true;
        $style = str_replace('U','',$style);
    }
    else
        $this->underline = false;
    if($style=='IB')
        $style = 'BI';
    if($size==0)
        $size = $this->FontSizePt;
    // Test if font is already selected
    if($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size)
        return;
    // Test if font is already loaded
    $fontkey = $family.$style;
    if(!isset($this->fonts[$fontkey]))
    {
        // Check if one of the core fonts
        if($family=='arial')
            $family = 'helvetica';
        if(in_array($family,$this->CoreFonts))
        {
            if($family=='helvetica' || $family=='arial')
                $this->AddCoreFont('helvetica');
            elseif($family=='times')
                $this->AddCoreFont('times');
            elseif($family=='courier')
                $this->AddCoreFont('courier');
            elseif($family=='symbol')
                $this->AddCoreFont('symbol');
            elseif($family=='zapfdingbats')
                $this->AddCoreFont('zapfdingbats');
        }
        else
            $this->Error('Undefined font: '.$family.' '.$style);
    }
    // Select it
    $this->FontFamily = $family;
    $this->FontStyle = $style;
    $this->FontSizePt = $size;
    $this->FontSize = $size/$this->k;
    $this->CurrentFont = &$this->fonts[$fontkey];
    if($this->page>0)
        $this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
}

function SetFontSize($size)
{
    // Set font size in points
    if($this->FontSizePt==$size)
        return;
    $this->FontSizePt = $size;
    $this->FontSize = $size/$this->k;
    if($this->page>0)
        $this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
}

function AddLink()
{
    // Create a new internal link
    $n = count($this->links)+1;
    $this->links[$n] = array(0, 0);
    return $n;
}

function SetLink($link, $y=0, $page=-1)
{
    // Set destination of internal link
    if($y==-1)
        $y = $this->y;
    if($page==-1)
        $page = $this->page;
    $this->links[$link] = array($page, $y);
}

function Link($x, $y, $w, $h, $link)
{
    // Put a link on the page
    $this->PageLinks[$this->page][] = array($x*$this->k, $this->hPt-$y*$this->k, $w*$this->k, $h*$this->k, $link);
}

function Text($x, $y, $txt)
{
    // Output a string
    if(!isset($this->CurrentFont))
        $this->Error('No font has been set');
    $s = sprintf('BT %.2F %.2F Td (%s) Tj ET',$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
    if($this->underline && $txt!='')
        $s .= ' '.$this->_dounderline($x,$y,$txt);
    if($this->ColorFlag)
        $s = 'q '.$this->TextColor.' '.$s.' Q';
    $this->_out($s);
}

function AcceptPageBreak()
{
    // Accept automatic page break or not
    return $this->AutoPageBreak;
}

function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
{
    // Output a cell
    $k = $this->k;
    if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
    {
        // Automatic page break
        $x = $this->x;
        $ws = $this->ws;
        if($ws>0)
        {
            $this->ws = 0;
            $this->_out('0 Tw');
        }
        $this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation);
        $this->x = $x;
        if($ws>0)
        {
            $this->ws = $ws;
            $this->_out(sprintf('%.3F Tw',$ws*$k));
        }
    }
    if($w==0)
        $w = $this->w-$this->rMargin-$this->x;
    $s = '';
    if($fill || $border==1)
    {
        if($fill)
            $op = ($border==1) ? 'B' : 'f';
        else
            $op = 'S';
        $s = sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
    }
    if(is_string($border))
    {
        $x = $this->x;
        $y = $this->y;
        if(strpos($border,'L')!==false)
            $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$this->k,($this->h-$y)*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k);
        if(strpos($border,'T')!==false)
            $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$this->k,($this->h-$y)*$this->k,($x+$w)*$this->k,($this->h-$y)*$this->k);
        if(strpos($border,'R')!==false)
            $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$this->k,($this->h-$y)*$this->k,($x+$w)*$this->k,($this->h-($y+$h))*$this->k);
        if(strpos($border,'B')!==false)
            $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$this->k,($this->h-($y+$h))*$this->k,($x+$w)*$this->k,($this->h-($y+$h))*$this->k);
    }
    if($txt!=='')
    {
        if(!isset($this->CurrentFont))
            $this->Error('No font has been set');
        if($align=='R')
            $dx = $w-$this->cMargin-$this->GetStringWidth($txt);
        elseif($align=='C')
            $dx = ($w-$this->GetStringWidth($txt))/2;
        else
            $dx = $this->cMargin;
        if($this->ColorFlag)
            $s .= 'q '.$this->TextColor.' ';
        $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET',($this->x+$dx)*$this->k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$this->k,$this->_escape($txt));
        if($this->underline)
            $s .= ' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
        if($this->ColorFlag)
            $s .= ' Q';
        if($link)
            $this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$this->GetStringWidth($txt),$this->FontSize,$link);
    }
    if($s)
        $this->_out($s);
    $this->lasth = $h;
    if($ln>0)
    {
        // Go to next line
        $this->y += $h;
        if($ln==1)
            $this->x = $this->lMargin;
    }
    else
        $this->x += $w;
}

function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false)
{
    // Output text with automatic or explicit line breaks
    if(!isset($this->CurrentFont))
        $this->Error('No font has been set');
    $cw = &$this->CurrentFont['cw'];
    if($w==0)
        $w = $this->w-$this->rMargin-$this->x;
    $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
    $s = str_replace("\r",'',$txt);
    $nb = strlen($s);
    if($nb>0 && $s[$nb-1]=="\n")
        $nb--;
    $b = 0;
    if($border)
    {
        if($border==1)
        {
            $border = 'LTRB';
            $b = 'LRT';
            $b2 = 'LR';
        }
        else
        {
            $b2 = '';
            if(strpos($border,'L')!==false)
                $b2 .= 'L';
            if(strpos($border,'R')!==false)
                $b2 .= 'R';
            $b = (strpos($border,'T')!==false) ? $b2.'T' : $b2;
        }
    }
    $sep = -1;
    $i = 0;
    $j = 0;
    $l = 0;
    $ns = 0;
    $nl = 1;
    while($i<$nb)
    {
        // Get next character
        $c = $s[$i];
        if($c=="\n")
        {
            // Explicit line break
            if($this->ws>0)
            {
                $this->ws = 0;
                $this->_out('0 Tw');
            }
            $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
            $i++;
            $sep = -1;
            $j = $i;
            $l = 0;
            $ns = 0;
            $nl++;
            if($border && $nl==2)
                $b = $b2;
            continue;
        }
        if($c==' ')
        {
            $sep = $i;
            $ls = $l;
            $ns++;
        }
        $l += $cw[$c];
        if($l>$wmax)
        {
            // Automatic line break
            if($sep==-1)
            {
                if($i==$j)
                    $i++;
                if($this->ws>0)
                {
                    $this->ws = 0;
                    $this->_out('0 Tw');
                }
                $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
            }
            else
            {
                if($align=='J')
                {
                    $this->ws = ($ns>1) ? ($wmax-$ls)/1000*$this->FontSize/($ns-1) : 0;
                    $this->_out(sprintf('%.3F Tw',$this->ws*$this->k));
                }
                $this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
                $i = $sep+1;
            }
            $sep = -1;
            $j = $i;
            $l = 0;
            $ns = 0;
            $nl++;
            if($border && $nl==2)
                $b = $b2;
        }
        else
            $i++;
    }
    // Last chunk
    if($this->ws>0)
    {
        $this->ws = 0;
        $this->_out('0 Tw');
    }
    if($border && strpos($border,'B')!==false)
        $b .= 'B';
    $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
    $this->x = $this->lMargin;
}

function Write($h, $txt, $link='')
{
    // Output text in flowing mode
    if(!isset($this->CurrentFont))
        $this->Error('No font has been set');
    $cw = &$this->CurrentFont['cw'];
    $w = $this->w-$this->rMargin-$this->x;
    $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
    $s = str_replace("\r",'',$txt);
    $nb = strlen($s);
    $sep = -1;
    $i = 0;
    $j = 0;
    $l = 0;
    $nl = 1;
    while($i<$nb)
    {
        // Get next character
        $c = $s[$i];
        if($c=="\n")
        {
            // Explicit line break
            $this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',false,$link);
            $i++;
            $sep = -1;
            $j = $i;
            $l = 0;
            if($nl==1)
            {
                $this->x = $this->lMargin;
                $w = $this->w-$this->rMargin-$this->x;
                $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
            }
            $nl++;
            continue;
        }
        if($c==' ')
            $sep = $i;
        $l += $cw[$c];
        if($l>$wmax)
        {
            // Automatic line break
            if($sep==-1)
            {
                if($this->x>$this->lMargin)
                {
                    // Move to next line
                    $this->x = $this->lMargin;
                    $this->y += $h;
                    $w = $this->w-$this->rMargin-$this->x;
                    $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
                    $i++;
                    $nl++;
                    continue;
                }
                if($i==$j)
                    $i++;
                $this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',false,$link);
            }
            else
            {
                $this->Cell($w,$h,substr($s,$j,$sep-$j),0,2,'',false,$link);
                $i = $sep+1;
            }
            $sep = -1;
            $j = $i;
            $l = 0;
            if($nl==1)
            {
                $this->x = $this->lMargin;
                $w = $this->w-$this->rMargin-$this->x;
                $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
            }
            $nl++;
        }
        else
            $i++;
    }
    // Last chunk
    if($i!=$j)
        $this->Cell($l/1000*$this->FontSize,$h,substr($s,$j),0,0,'',false,$link);
}

function Ln($h=null)
{
    // Line feed; default value is the last cell height
    $this->x = $this->lMargin;
    if($h===null)
        $this->y += $this->lasth;
    else
        $this->y += $h;
}

function Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='')
{
    // Put an image on the page
    if($file=='')
        $this->Error('Image file name is empty');
    if(!isset($this->images[$file]))
    {
        // First use of this image, get info
        if($type=='')
        {
            $pos = strrpos($file,'.');
            if(!$pos)
                $this->Error('Image file has no extension and no type was specified: '.$file);
            $type = substr($file,$pos+1);
        }
        $type = strtolower($type);
        if($type=='jpeg')
            $type = 'jpg';
        $mtd = '_parse'.$type;
        if(!method_exists($this,$mtd))
            $this->Error('Unsupported image type: '.$type);
        $info = $this->$mtd($file);
        $info['i'] = count($this->images)+1;
        $this->images[$file] = $info;
    }
    else
        $info = $this->images[$file];

    // Automatic width and height calculation if needed
    if($w==0 && $h==0)
    {
        // Put image at 96 dpi
        $w = -96;
        $h = -96;
    }
    if($w<0)
        $w = -$info['w']*72/$w/$this->k;
    if($h<0)
        $h = -$info['h']*72/$h/$this->k;
    if($w==0)
        $w = $h*$info['w']/$info['h'];
    if($h==0)
        $h = $w*$info['h']/$info['w'];

    // Flowing mode
    if($x===null)
    {
        if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
        {
            // Automatic page break
            $x2 = $this->x;
            $this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation);
            $this->x = $x2;
        }
        $x = $this->x;
        $this->x += $w;
        if($this->ws>0 && !$this->InHeader && !$this->InFooter)
        {
            $this->ws = 0;
            $this->_out('0 Tw');
        }
    }
    if($y===null)
        $y = $this->y;

    $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
    if($link)
        $this->Link($x,$y,$w,$h,$link);
}

function GetX()
{
    // Get x position
    return $this->x;
}

function SetX($x)
{
    // Set x position
    if($x>=0)
        $this->x = $x;
    else
        $this->x = $this->w+$x;
}

function GetY()
{
    // Get y position
    return $this->y;
}

function SetY($y)
{
    // Set y position and reset x
    $this->x = $this->lMargin;
    if($y>=0)
        $this->y = $y;
    else
        $this->y = $this->h+$y;
}

function SetXY($x, $y)
{
    // Set x and y positions
    $this->SetY($y);
    $this->SetX($x);
}

function Output($dest='', $name='', $isUTF8=false)
{
    // Output PDF to some destination
    if($this->state<3)
        $this->Close();
    if($dest=='')
        $dest = 'I';
    if($name=='')
        $name = 'doc.pdf';
    if($isUTF8)
        $name = $this->_UTF8toUTF16($name);
    switch(strtoupper($dest))
    {
        case 'I':
            // Send to standard output
            $this->_checkoutput();
            if(PHP_SAPI!='cli')
            {
                // We send to a browser
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="'.$name.'"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
            }
            echo $this->buffer;
            break;
        case 'D':
            // Download file
            $this->_checkoutput();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.$name.'"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $this->buffer;
            break;
        case 'F':
            // Save to local file
            if(!file_put_contents($name,$this->buffer))
                $this->Error('Unable to create output file: '.$name);
            break;
        case 'S':
            // Return as a string
            return $this->buffer;
        default:
            $this->Error('Incorrect output destination: '.$dest);
    }
    return '';
}

// Protected methods for internal use
protected function AddCoreFont($family)
{
    // Add a core font
    if($family=='helvetica' || $family=='arial')
    {
        // Regular Helvetica
        if(!isset($this->fonts['helvetica'])) {
            $this->fonts['helvetica'] = array('i'=>count($this->fonts)+1,'type'=>'core','name'=>'Helvetica','up'=>-100,'ut'=>50,'cw'=>array(
                chr(0)=>278,chr(1)=>278,chr(2)=>278,chr(3)=>278,chr(4)=>278,chr(5)=>278,chr(6)=>278,chr(7)=>278,chr(8)=>278,chr(9)=>278,chr(10)=>278,chr(11)=>278,chr(12)=>278,chr(13)=>278,chr(14)=>278,chr(15)=>278,chr(16)=>278,chr(17)=>278,chr(18)=>278,chr(19)=>278,chr(20)=>278,chr(21)=>278,chr(22)=>278,chr(23)=>278,chr(24)=>278,chr(25)=>278,chr(26)=>278,chr(27)=>278,chr(28)=>278,chr(29)=>278,chr(30)=>278,chr(31)=>278,' '=>278,'!'=>278,'"'=>355,'#'=>556,'$'=>556,'%'=>889,'&'=>667,'\''=>191,'('=>333,')'=>333,'*'=>389,'+'=>584,','=>278,'-'=>333,'.'=>278,'/'=>278,'0'=>556,'1'=>556,'2'=>556,'3'=>556,'4'=>556,'5'=>556,'6'=>556,'7'=>556,'8'=>556,'9'=>556,':'=>278,';'=>278,'<'=>584,'='=>584,'>'=>584,'?'=>556,'@'=>1015,'A'=>667,'B'=>667,'C'=>722,'D'=>722,'E'=>667,'F'=>611,'G'=>778,'H'=>722,'I'=>278,'J'=>500,'K'=>667,'L'=>556,'M'=>833,'N'=>722,'O'=>778,'P'=>667,'Q'=>778,'R'=>722,'S'=>667,'T'=>611,'U'=>722,'V'=>667,'W'=>944,'X'=>667,'Y'=>667,'Z'=>611,'['=>278,'\\'=>278,']'=>278,'^'=>469,'_'=>556,'`'=>333,'a'=>556,'b'=>556,'c'=>500,'d'=>556,'e'=>556,'f'=>278,'g'=>556,'h'=>556,'i'=>222,'j'=>222,'k'=>500,'l'=>222,'m'=>833,'n'=>556,'o'=>556,'p'=>556,'q'=>556,'r'=>333,'s'=>500,'t'=>278,'u'=>556,'v'=>500,'w'=>722,'x'=>500,'y'=>500,'z'=>500,'{'=>334,'|'=>260,'}'=>334,'~'=>584));
        }
        
        // Bold Helvetica
        if(!isset($this->fonts['helveticaB'])) {
            $this->fonts['helveticaB'] = array('i'=>count($this->fonts)+1,'type'=>'core','name'=>'Helvetica-Bold','up'=>-100,'ut'=>50,'cw'=>array(
                chr(0)=>278,chr(1)=>278,chr(2)=>278,chr(3)=>278,chr(4)=>278,chr(5)=>278,chr(6)=>278,chr(7)=>278,chr(8)=>278,chr(9)=>278,chr(10)=>278,chr(11)=>278,chr(12)=>278,chr(13)=>278,chr(14)=>278,chr(15)=>278,chr(16)=>278,chr(17)=>278,chr(18)=>278,chr(19)=>278,chr(20)=>278,chr(21)=>278,chr(22)=>278,chr(23)=>278,chr(24)=>278,chr(25)=>278,chr(26)=>278,chr(27)=>278,chr(28)=>278,chr(29)=>278,chr(30)=>278,chr(31)=>278,' '=>278,'!'=>333,'"'=>474,'#'=>556,'$'=>556,'%'=>889,'&'=>722,'\''=>238,'('=>333,')'=>333,'*'=>389,'+'=>584,','=>278,'-'=>333,'.'=>278,'/'=>278,'0'=>556,'1'=>556,'2'=>556,'3'=>556,'4'=>556,'5'=>556,'6'=>556,'7'=>556,'8'=>556,'9'=>556,':'=>333,';'=>333,'<'=>584,'='=>584,'>'=>584,'?'=>611,'@'=>975,'A'=>722,'B'=>722,'C'=>722,'D'=>722,'E'=>667,'F'=>611,'G'=>778,'H'=>722,'I'=>278,'J'=>556,'K'=>722,'L'=>611,'M'=>833,'N'=>722,'O'=>778,'P'=>667,'Q'=>778,'R'=>722,'S'=>667,'T'=>611,'U'=>722,'V'=>667,'W'=>944,'X'=>667,'Y'=>667,'Z'=>611,'['=>333,'\\'=>278,']'=>333,'^'=>584,'_'=>556,'`'=>333,'a'=>556,'b'=>611,'c'=>556,'d'=>611,'e'=>556,'f'=>333,'g'=>611,'h'=>611,'i'=>278,'j'=>278,'k'=>556,'l'=>278,'m'=>889,'n'=>611,'o'=>611,'p'=>611,'q'=>611,'r'=>389,'s'=>556,'t'=>333,'u'=>611,'v'=>556,'w'=>778,'x'=>556,'y'=>556,'z'=>500,'{'=>389,'|'=>280,'}'=>389,'~'=>584));
        }
    }
    elseif($family=='times')
    {
        if(!isset($this->fonts['times'])) {
            $this->fonts['times'] = array('i'=>count($this->fonts)+1,'type'=>'core','name'=>'Times-Roman','up'=>-100,'ut'=>50,'cw'=>array(
                chr(0)=>250,chr(1)=>250,chr(2)=>250,chr(3)=>250,chr(4)=>250,chr(5)=>250,chr(6)=>250,chr(7)=>250,chr(8)=>250,chr(9)=>250,chr(10)=>250,chr(11)=>250,chr(12)=>250,chr(13)=>250,chr(14)=>250,chr(15)=>250,chr(16)=>250,chr(17)=>250,chr(18)=>250,chr(19)=>250,chr(20)=>250,chr(21)=>250,chr(22)=>250,chr(23)=>250,chr(24)=>250,chr(25)=>250,chr(26)=>250,chr(27)=>250,chr(28)=>250,chr(29)=>250,chr(30)=>250,chr(31)=>250,' '=>250,'!'=>333,'"'=>408,'#'=>500,'$'=>500,'%'=>833,'&'=>778,'\''=>180,'('=>333,')'=>333,'*'=>500,'+'=>564,','=>250,'-'=>333,'.'=>250,'/'=>278,'0'=>500,'1'=>500,'2'=>500,'3'=>500,'4'=>500,'5'=>500,'6'=>500,'7'=>500,'8'=>500,'9'=>500,':'=>278,';'=>278,'<'=>564,'='=>564,'>'=>564,'?'=>444,'@'=>921,'A'=>722,'B'=>667,'C'=>667,'D'=>722,'E'=>611,'F'=>556,'G'=>722,'H'=>722,'I'=>333,'J'=>389,'K'=>722,'L'=>611,'M'=>889,'N'=>722,'O'=>722,'P'=>556,'Q'=>722,'R'=>667,'S'=>556,'T'=>611,'U'=>722,'V'=>722,'W'=>944,'X'=>722,'Y'=>722,'Z'=>611,'['=>333,'\\'=>278,']'=>333,'^'=>469,'_'=>500,'`'=>333,'a'=>444,'b'=>500,'c'=>444,'d'=>500,'e'=>444,'f'=>333,'g'=>500,'h'=>500,'i'=>278,'j'=>278,'k'=>500,'l'=>278,'m'=>778,'n'=>500,'o'=>500,'p'=>500,'q'=>500,'r'=>333,'s'=>389,'t'=>278,'u'=>500,'v'=>500,'w'=>722,'x'=>500,'y'=>500,'z'=>444,'{'=>480,'|'=>200,'}'=>480,'~'=>541));
        }
    }
    elseif($family=='courier')
    {
        if(!isset($this->fonts['courier'])) {
            $this->fonts['courier'] = array('i'=>count($this->fonts)+1,'type'=>'core','name'=>'Courier','up'=>-100,'ut'=>50,'cw'=>array_fill(0,256,600));
        }
    }
}

protected function _dochecks()
{
    // Check availability of %F
    if(sprintf('%.1F',1.0)!='1.0')
        $this->Error('This version of PHP is not supported');
    // Check mbstring overloading
    if(ini_get('mbstring.func_overload') & 2)
        $this->Error('mbstring overloading must be disabled');
}

protected function _getpagesize($size)
{
    if(is_string($size))
    {
        $size = strtolower($size);
        if(!isset($this->StdPageSizes[$size]))
            $this->Error('Unknown page size: '.$size);
        $a = $this->StdPageSizes[$size];
        return array($a[0]/$this->k, $a[1]/$this->k);
    }
    else
    {
        if($size[0]>$size[1])
            return array($size[1], $size[0]);
        else
            return $size;
    }
}

protected function _beginpage($orientation, $size, $rotation)
{
    $this->page++;
    $this->pages[$this->page] = '';
    $this->state = 2;
    $this->x = $this->lMargin;
    $this->y = $this->tMargin;
    $this->FontFamily = '';
    // Check page size and orientation
    if($orientation=='')
        $orientation = $this->DefOrientation;
    else
    {
        $orientation = strtoupper($orientation[0]);
        if($orientation!=$this->DefOrientation)
            $this->OrientationChanges[$this->page] = true;
    }
    if($size=='')
        $size = $this->DefPageSize;
    else
        $size = $this->_getpagesize($size);
    if($orientation!=$this->CurOrientation || $size[0]!=$this->CurPageSize[0] || $size[1]!=$this->CurPageSize[1])
    {
        // New size or orientation
        if($orientation=='P')
        {
            $this->w = $size[0];
            $this->h = $size[1];
        }
        else
        {
            $this->w = $size[1];
            $this->h = $size[0];
        }
        $this->wPt = $this->w*$this->k;
        $this->hPt = $this->h*$this->k;
        $this->PageBreakTrigger = $this->h-$this->bMargin;
        $this->CurOrientation = $orientation;
        $this->CurPageSize = $size;
    }
    if($orientation!=$this->DefOrientation || $size[0]!=$this->DefPageSize[0] || $size[1]!=$this->DefPageSize[1])
        $this->PageInfo[$this->page]['size'] = array($this->wPt, $this->hPt);
    if($rotation!=0)
    {
        if($rotation%90!=0)
            $this->Error('Incorrect rotation value: '.$rotation);
        $this->CurRotation = $rotation;
        $this->PageInfo[$this->page]['rotation'] = $rotation;
    }
}

protected function _endpage()
{
    $this->state = 1;
}

protected function _escape($s)
{
    // Escape special characters
    if(strpos($s,'(')!==false || strpos($s,')')!==false || strpos($s,'\\')!==false || strpos($s,"\r")!==false)
        return str_replace(array('\\','(',')',"\r"), array('\\\\','\$$','\$$','\\r'), $s);
    else
        return $s;
}

protected function _textstring($s)
{
    // Format a text string
    if(!$this->_isascii($s))
        $s = $this->_UTF8toUTF16($s);
    return '('.$this->_escape($s).')';
}

protected function _isascii($s)
{
    // Test if string is ASCII
    $nb = strlen($s);
    for($i=0;$i<$nb;$i++)
    {
        if(ord($s[$i])>127)
            return false;
    }
    return true;
}

protected function _UTF8toUTF16($s)
{
    // Convert UTF-8 to UTF-16BE with BOM
    $res = "\xFE\xFF";
    $nb = strlen($s);
    $i = 0;
    while($i<$nb)
    {
        $c1 = ord($s[$i++]);
        if($c1>=224)
        {
            // 3-byte character
            $c2 = ord($s[$i++]);
            $c3 = ord($s[$i++]);
            $res .= chr((($c1 & 0x0F)<<4) + (($c2 & 0x3C)>>2));
            $res .= chr((($c2 & 0x03)<<6) + ($c3 & 0x3F));
        }
        elseif($c1>=192)
        {
            // 2-byte character
            $c2 = ord($s[$i++]);
            $res .= chr(($c1 & 0x1C)>>2);
            $res .= chr((($c1 & 0x03)<<6) + ($c2 & 0x3F));
        }
        else
        {
            // Single-byte character
            $res .= "\0".chr($c1);
        }
    }
    return $res;
}

protected function _dounderline($x, $y, $txt)
{
    // Underline text
    $up = $this->CurrentFont['up'];
    $ut = $this->CurrentFont['ut'];
    $w = $this->GetStringWidth($txt)+$this->ws*substr_count($txt,' ');
    return sprintf('%.2F %.2F %.2F %.2F re f',$x*$this->k,($this->h-($y-$up/1000*$this->FontSize))*$this->k,$w*$this->k,-$ut/1000*$this->FontSizePt);
}

protected function _out($s)
{
    // Add a line to the document
    if($this->state==2)
        $this->pages[$this->page] .= $s."\n";
    else
        $this->buffer .= $s."\n";
}

protected function _checkoutput()
{
    if(PHP_SAPI!='cli')
    {
        if(headers_sent($file,$line))
            $this->Error("Some data has already been output, can't send PDF file (output started at $file:$line)");
    }
    if(ob_get_length())
    {
        // The output buffer is not empty
        if(preg_match('/^(\xEF\xBB\xBF)?\s*$/',ob_get_contents()))
        {
            // It contains only a UTF-8 BOM and/or whitespace, let's clean it
            ob_clean();
        }
        else
            $this->Error("Some data has already been output, can't send PDF file");
    }
}

protected function _enddoc()
{
    $this->state = 3;
    $this->_putheader();
    $this->_putpages();
    $this->_putresources();
    // Info
    $this->_newobj();
    $this->_out('<<');
    $this->_putinfo();
    $this->_out('>>');
    $this->_out('endobj');
    // Catalog
    $this->_newobj();
    $this->_out('<<');
    $this->_putcatalog();
    $this->_out('>>');
    $this->_out('endobj');
    // Cross-ref
    $o = $this->_getoffset();
    $this->_out('xref');
    $this->_out('0 '.($this->n+1));
    $this->_out('0000000000 65535 f ');
    for($i=1;$i<=$this->n;$i++)
        $this->_out(sprintf('%010d 00000 n ',$this->offsets[$i]));
    // Trailer
    $this->_out('trailer');
    $this->_out('<<');
    $this->_puttrailer();
    $this->_out('>>');
    $this->_out('startxref');
    $this->_out($o);
    $this->_out('%%EOF');
}

protected function _newobj($n=null)
{
    // Begin a new object
    if($n===null)
        $n = ++$this->n;
    $this->offsets[$n] = $this->_getoffset();
    $this->_out($n.' 0 obj');
    return $n;
}

protected function _getoffset()
{
    return strlen($this->buffer);
}

protected function _putheader()
{
    $this->_out('%PDF-'.$this->PDFVersion);
}

protected function _putpages()
{
    $nb = $this->page;
    for($n=1;$n<=$nb;$n++)
        $this->PageInfo[$n]['n'] = $this->_newobj();
    for($n=1;$n<=$nb;$n++)
        $this->_putpage($n);
}

protected function _putpage($n)
{
    $this->_out('<<');
    $this->_out('/Type /Page');
    $this->_out('/Parent 1 0 R');
    if(isset($this->PageInfo[$n]['size']))
        $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]',$this->PageInfo[$n]['size'][0],$this->PageInfo[$n]['size'][1]));
    if(isset($this->PageInfo[$n]['rotation']))
        $this->_out('/Rotate '.$this->PageInfo[$n]['rotation']);
    $this->_out('/Resources 2 0 R');
    if(isset($this->PageLinks[$n]))
    {
        // Links
        $annots = '/Annots [';
        foreach($this->PageLinks[$n] as $pl)
        {
            $rect = sprintf('%.2F %.2F %.2F %.2F',$pl[0],$pl[1],$pl[0]+$pl[2],$pl[1]-$pl[3]);
            $annots .= '<</Type /Annot /Subtype /Link /Rect ['.$rect.'] /Border [0 0 0] ';
            if(is_string($pl[4]))
                $annots .= '/A <</S /URI /URI '.$this->_textstring($pl[4]).'>>>>';
            else
            {
                $l = $this->links[$pl[4]];
                if(isset($this->PageInfo[$l[0]]['size']))
                    $h = $this->PageInfo[$l[0]]['size'][1];
                else
                    $h = ($this->DefOrientation=='P') ? $this->DefPageSize[1]*$this->k : $this->DefPageSize[0]*$this->k;
                $annots .= sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]>>',$this->PageInfo[$l[0]]['n'],$h-$l[1]*$this->k);
            }
        }
        $this->_out($annots.']');
    }
    if($this->WithAlpha)
        $this->_out('/Group <</Type /Group /S /Transparency /CS /DeviceRGB>>');
    $this->_out('/Contents '.($this->n+1).' 0 R>>');
    $this->_out('endobj');
    // Page content
    if(!empty($this->AliasNbPages))
        $this->pages[$n] = str_replace($this->AliasNbPages,$this->page,$this->pages[$n]);
    $p = ($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];
    $this->_newobj();
    $this->_out('<<');
    $this->_out('/Length '.strlen($p));
    if($this->compress)
        $this->_out('/Filter /FlateDecode');
    $this->_out('>>');
    $this->_putstream($p);
    $this->_out('endobj');
}

protected function _putstream($data)
{
    $this->_out('stream');
    $this->_out($data);
    $this->_out('endstream');
}

protected function _putresources()
{
    $this->_putfonts();
    $this->_putimages();
    // Resource dictionary
    $this->_newobj(2);
    $this->_out('<<');
    $this->_putresourcedict();
    $this->_out('>>');
    $this->_out('endobj');
}

protected function _putfonts()
{
    foreach($this->fonts as $k=>$font)
    {
        // Font object
        $this->fonts[$k]['n'] = $this->_newobj();
        $this->_out('<<');
        $this->_out('/Type /Font');
        if($font['type']=='core')
            $this->_putcorefont($font);
        $this->_out('>>');
        $this->_out('endobj');
    }
}

protected function _putcorefont($font)
{
    $this->_out('/Subtype /Type1');
    $this->_out('/BaseFont /'.$font['name']);
    if($font['name']!='Symbol' && $font['name']!='ZapfDingbats')
        $this->_out('/Encoding /WinAnsiEncoding');
}

protected function _putinfo()
{
    $this->metadata['Producer'] = 'FPDF '.FPDF_VERSION;
    $this->metadata['CreationDate'] = 'D:'.@date('YmdHis');
    foreach($this->metadata as $key=>$value)
        $this->_out('/'.$key.' '.$this->_textstring($value));
}

protected function _putcatalog()
{
    $n = $this->PageInfo[1]['n'];
    $this->_out('/Type /Catalog');
    $this->_out('/Pages 1 0 R');
    if($this->ZoomMode=='fullpage')
        $this->_out('/OpenAction ['.$n.' 0 R /Fit]');
    elseif($this->ZoomMode=='fullwidth')
        $this->_out('/OpenAction ['.$n.' 0 R /FitH null]');
    elseif($this->ZoomMode=='real')
        $this->_out('/OpenAction ['.$n.' 0 R /XYZ null null 1]');
    elseif(!is_string($this->ZoomMode))
        $this->_out('/OpenAction ['.$n.' 0 R /XYZ null null '.sprintf('%.2F',$this->ZoomMode/100).']');
    if($this->LayoutMode=='single')
        $this->_out('/PageLayout /SinglePage');
    elseif($this->LayoutMode=='continuous')
        $this->_out('/PageLayout /OneColumn');
    elseif($this->LayoutMode=='two')
        $this->_out('/PageLayout /TwoColumnLeft');
}

protected function _puttrailer()
{
    $this->_out('/Size '.($this->n+1));
    $this->_out('/Root '.$this->n.' 0 R');
    $this->_out('/Info '.($this->n-1).' 0 R');
}

protected function _putresourcedict()
{
    $this->_out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
    $this->_out('/Font <<');
    foreach($this->fonts as $font)
        $this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
    $this->_out('>>');
    $this->_out('/XObject <<');
    $this->_putxobjectdict();
    $this->_out('>>');
}

protected function _putxobjectdict()
{
    foreach($this->images as $image)
        $this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
}

protected function _putimages()
{
    foreach(array_keys($this->images) as $file)
    {
        $this->_putimage($this->images[$file]);
        unset($this->images[$file]['data']);
        unset($this->images[$file]['smask']);
    }
}

protected function _putimage($info)
{
    $this->_newobj();
    $this->images[$info['i']]['n'] = $this->n;
    $this->_out('<<');
    $this->_out('/Type /XObject');
    $this->_out('/Subtype /Image');
    $this->_out('/Width '.$info['w']);
    $this->_out('/Height '.$info['h']);
    $this->_out('/ColorSpace /DeviceRGB');
    $this->_out('/BitsPerComponent 8');
    $this->_out('/Length '.strlen($info['data']));
    $this->_out('>>');
    $this->_putstream($info['data']);
    $this->_out('endobj');
}

} // End of FPDF class
?>
