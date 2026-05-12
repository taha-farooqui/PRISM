<?php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;

class TextExtractorService
{
    public function extractText(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $text = match ($extension) {
            'pdf' => $this->extractFromPdf($filePath),
            'docx', 'doc' => $this->extractFromDocx($filePath),
            'pptx' => $this->extractFromPptx($filePath),
            'txt' => file_get_contents($filePath),
            default => '',
        };

        // Truncate to ~15,000 chars to fit AI context window
        return mb_substr(trim($text), 0, 15000);
    }

    private function extractFromPdf(string $filePath): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);
        return $pdf->getText();
    }

    private function extractFromDocx(string $filePath): string
    {
        $phpWord = WordIOFactory::load($filePath);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $child) {
                        if (method_exists($child, 'getText')) {
                            $text .= $child->getText() . "\n";
                        }
                    }
                }
            }
        }

        return $text;
    }

    private function extractFromPptx(string $filePath): string
    {
        $presentation = PresentationIOFactory::load($filePath);
        $text = '';

        foreach ($presentation->getAllSlides() as $slide) {
            foreach ($slide->getShapeCollection() as $shape) {
                if ($shape instanceof \PhpOffice\PhpPresentation\Shape\RichText) {
                    foreach ($shape->getParagraphs() as $paragraph) {
                        foreach ($paragraph->getRichTextElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                $text .= $element->getText();
                            }
                        }
                        $text .= "\n";
                    }
                }
            }
            $text .= "\n\n";
        }

        return $text;
    }
}
