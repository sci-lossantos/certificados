<?php
// Clase TCPDF simplificada para generar PDFs
class SimpleTCPDF {
    private $content = array();
    private $title = '';
    private $pageWidth = 210; // A4 width in mm
    private $pageHeight = 297; // A4 height in mm
    private $margin = 20;
    
    public function __construct($title = 'Documento') {
        $this->title = $title;
    }
    
    public function addPage() {
        $this->content[] = array('type' => 'page_break');
    }
    
    public function addText($text, $x = null, $y = null, $size = 12, $style = '') {
        $this->content[] = array(
            'type' => 'text',
            'text' => $text,
            'x' => $x,
            'y' => $y,
            'size' => $size,
            'style' => $style
        );
    }
    
    public function addImage($path, $x, $y, $width, $height) {
        $this->content[] = array(
            'type' => 'image',
            'path' => $path,
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height
        );
    }
    
    public function output($filename, $dest = 'D') {
        // Generar PDF básico
        $pdf_content = $this->generatePDFContent();
        
        if ($dest === 'D') {
            // Descarga directa
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdf_content));
            echo $pdf_content;
        } else {
            // Guardar archivo
            file_put_contents($filename, $pdf_content);
        }
    }
    
    private function generatePDFContent() {
        // Estructura básica de PDF
        $pdf = "%PDF-1.4\n";
        
        // Objeto 1: Catálogo
        $pdf .= "1 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Type /Catalog\n";
        $pdf .= "/Pages 2 0 R\n";
        $pdf .= ">>\n";
        $pdf .= "endobj\n\n";
        
        // Objeto 2: Páginas
        $pdf .= "2 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Type /Pages\n";
        $pdf .= "/Kids [3 0 R]\n";
        $pdf .= "/Count 1\n";
        $pdf .= ">>\n";
        $pdf .= "endobj\n\n";
        
        // Objeto 3: Página
        $pdf .= "3 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Type /Page\n";
        $pdf .= "/Parent 2 0 R\n";
        $pdf .= "/MediaBox [0 0 595 842]\n"; // A4 size in points
        $pdf .= "/Contents 4 0 R\n";
        $pdf .= "/Resources <<\n";
        $pdf .= "/Font <<\n";
        $pdf .= "/F1 5 0 R\n";
        $pdf .= ">>\n";
        $pdf .= ">>\n";
        $pdf .= ">>\n";
        $pdf .= "endobj\n\n";
        
        // Generar contenido de la página
        $pageContent = $this->generatePageContent();
        
        // Objeto 4: Contenido
        $pdf .= "4 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Length " . strlen($pageContent) . "\n";
        $pdf .= ">>\n";
        $pdf .= "stream\n";
        $pdf .= $pageContent;
        $pdf .= "endstream\n";
        $pdf .= "endobj\n\n";
        
        // Objeto 5: Fuente
        $pdf .= "5 0 obj\n";
        $pdf .= "<<\n";
        $pdf .= "/Type /Font\n";
        $pdf .= "/Subtype /Type1\n";
        $pdf .= "/BaseFont /Helvetica\n";
        $pdf .= ">>\n";
        $pdf .= "endobj\n\n";
        
        // Tabla de referencias cruzadas
        $pdf .= "xref\n";
        $pdf .= "0 6\n";
        $pdf .= "0000000000 65535 f \n";
        $pdf .= "0000000009 00000 n \n";
        $pdf .= "0000000074 00000 n \n";
        $pdf .= "0000000120 00000 n \n";
        $pdf .= "0000000179 00000 n \n";
        $pdf .= "0000000364 00000 n \n";
        
        // Trailer
        $pdf .= "trailer\n";
        $pdf .= "<<\n";
        $pdf .= "/Size 6\n";
        $pdf .= "/Root 1 0 R\n";
        $pdf .= ">>\n";
        $pdf .= "startxref\n";
        $pdf .= "456\n";
        $pdf .= "%%EOF\n";
        
        return $pdf;
    }
    
    private function generatePageContent() {
        $content = "BT\n";
        $content .= "/F1 12 Tf\n";
        $content .= "50 750 Td\n";
        
        foreach ($this->content as $item) {
            if ($item['type'] === 'text') {
                $text = str_replace(array('(', ')'), array('\$$', '\$$'), $item['text']);
                $content .= "(" . $text . ") Tj\n";
                $content .= "0 -20 Td\n";
            }
        }
        
        $content .= "ET\n";
        return $content;
    }
}
?>
