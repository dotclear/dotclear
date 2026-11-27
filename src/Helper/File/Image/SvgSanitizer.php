<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\File\Image;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * @class SvgSanitizer
 *
 * This class sanitize a SVG source code, from a string or a file.
 * Very light version inspired by https://github.com/darylldoyle/svg-sanitizer
 *
 * Limitations :
 * - les URL externes ordinaires dans des attributs comme href sont conservées
 * - la détection CSS repose sur des chaînes plutôt que sur une analyse complète du CSS
 *
 * Ça devrait néamoins suffire à empêcher des
 */
final class SvgSanitizer
{
    /**
     * Balises XML/HTML dangereuses à supprimer entièrement.
     *
     * @var list<string>
     */
    private const FORBIDDEN_TAGS = [
        'script',
        'foreignobject',
        'iframe',
        'object',
        'embed',
        'applet',
        'meta',
        'link',
        'base',
    ];

    /**
     * Attributs pouvant transporter une URI exécutable ou externe.
     *
     * @var list<string>
     */
    private const URI_ATTRIBUTES = [
        'href',
        'xlink:href',
        'src',
        'action',
        'formaction',
        'data',
        'poster',
    ];

    /**
     * Nettoie une chaîne SVG et retire scripts, gestionnaires d'événements et URI dangereuses.
     *
     * @return string|false Le SVG nettoyé, ou false si le contenu est invalide ou n'est pas un SVG.
     */
    public function sanitize(string $svgContent): string|false
    {
        if ($svgContent === '') {
            // SVG vide
            return false;
        }

        $previousErrors = libxml_use_internal_errors(true);

        $dom    = new DOMDocument();
        $loaded = $dom->loadXML(
            $svgContent,
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        if (!$loaded || !$dom->documentElement instanceof DOMElement) {
            return false;
        }

        $rootName = strtolower($dom->documentElement->localName ?? $dom->documentElement->nodeName);
        if ($rootName !== 'svg') {
            return false;
        }

        $xpath = new DOMXPath($dom);

        $this->removeForbiddenTags($xpath);
        $this->sanitizeElementAttributes($xpath);
        $this->sanitizeStyleTags($xpath);

        return $dom->saveXML($dom->documentElement);
    }

    /**
     * Nettoie un fichier SVG à partir de son chemin.
     * Si $outputPath est omis (null), le fichier source est écrasé avec la version nettoyée.
     *
     * @return string|false Le chemin du fichier écrit, ou false en cas d'échec.
     */
    public function sanitizeFile(string $filePath, ?string $outputPath = null): string|false
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return false;
        }

        $rawContent = file_get_contents($filePath);
        if ($rawContent === false) {
            return false;
        }

        $cleanContent = $this->sanitize($rawContent);
        if ($cleanContent === false) {
            return false;
        }

        $targetPath = $outputPath ?? $filePath;
        $targetDir  = dirname($targetPath);

        if (!is_dir($targetDir) || !is_writable($targetDir)) {
            if (file_exists($targetPath) && !is_writable($targetPath)) {
                return false;
            }

            if (!file_exists($targetPath)) {
                return false;
            }
        }

        $bytesWritten = file_put_contents($targetPath, $cleanContent, LOCK_EX);
        if ($bytesWritten === false) {
            return false;
        }

        return $targetPath;
    }

    /**
     * Nettoie un fichier SVG et enregistre le résultat dans un nouveau chemin.
     */
    public function sanitizeFileToNewPath(string $sourcePath, string $destinationPath): bool
    {
        return $this->sanitizeFile($sourcePath, $destinationPath) !== false;
    }

    /**
     * Suppression des éléments interdits
     */
    private function removeForbiddenTags(DOMXPath $xpath): void
    {
        foreach (self::FORBIDDEN_TAGS as $tag) {
            /*
             * Requête XPath sur le document XML/SVG
             *
             * 1. //* : parcourt tous les éléments du document SVG, à n'importe quel niveau d'imbrication.
             * 2. local-name() : extrait le nom local de chaque élément, sans tenir compte d'un éventuel espace de noms XML.
             * 3. translate(local-name(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') :
             *    convertit le nom de la balise en minuscules en XPath 1.0 (puisque lower-case() n'existe pas en XPath 1.0).
             * 4. = '{$tag}' : ne retient que les éléments dont le nom en minuscules est exactement $tag.
             */
            $query = "//*[translate(local-name(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = '{$tag}']";
            $nodes = $xpath->query($query);

            if ($nodes === false) {
                continue;
            }

            foreach ($nodes as $node) {
                if ($node instanceof DOMNode) {
                    $node->parentNode?->removeChild($node);
                }
            }
        }
    }

    private function sanitizeElementAttributes(DOMXPath $xpath): void
    {
        $elements = $xpath->query('//*');
        if ($elements === false) {
            return;
        }

        foreach ($elements as $element) {
            if (!$element instanceof DOMElement || !$element->hasAttributes()) {
                continue;
            }

            for ($i = $element->attributes->length - 1; $i >= 0; $i--) {
                $attr = $element->attributes->item($i);
                if (!$attr instanceof DOMNode) {
                    continue;
                }

                $attrName  = strtolower($attr->nodeName);
                $attrValue = $attr->nodeValue ?? '';

                if (str_starts_with($attrName, 'on')) {
                    $element->removeAttributeNode($attr);

                    continue;
                }

                if (in_array($attrName, self::URI_ATTRIBUTES, true) || str_ends_with($attrName, ':href')) {
                    if ($this->isDangerousUri($attrValue)) {
                        $element->removeAttributeNode($attr);
                    }

                    continue;
                }

                if ($attrName === 'style' && $this->isDangerousCss($attrValue)) {
                    $element->removeAttributeNode($attr);
                }
            }
        }
    }

    /**
     * Suppression des éléments/attributs CSS interdits
     */
    private function sanitizeStyleTags(DOMXPath $xpath): void
    {
        /*
         * Requête XPath sur le document XML/SVG
         *
         * 1. //* : parcourt tous les éléments du document SVG, à n'importe quel niveau d'imbrication.
         * 2. local-name() : extrait le nom local de chaque élément, sans tenir compte d'un éventuel espace de noms XML.
         * 3. translate(local-name(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') :
         *    convertit le nom de la balise en minuscules en XPath 1.0 (puisque lower-case() n'existe pas en XPath 1.0).
         * 4. = 'style' : ne retient que les éléments dont le nom en minuscules est exactement style.
         */
        $query      = "//*[translate(local-name(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = 'style']";
        $styleNodes = $xpath->query($query);

        if ($styleNodes === false) {
            return;
        }

        foreach ($styleNodes as $styleNode) {
            if ($styleNode instanceof DOMNode && $this->isDangerousCss($styleNode->textContent)) {
                $styleNode->parentNode?->removeChild($styleNode);
            }
        }
    }

    private function isDangerousUri(string $uri): bool
    {
        $clean = $this->normalize($uri);

        if (
            str_starts_with($clean, 'javascript:') || str_starts_with($clean, 'vbscript:') || str_starts_with($clean, 'data:image/svg+xml')
        ) {
            return true;
        }

        if (str_starts_with($clean, 'data:')) {
            return !(bool) preg_match('#^data:image/(png|jpeg|jpg|gif|webp);base64,#i', $clean);
        }

        return false;
    }

    private function isDangerousCss(string $css): bool
    {
        $clean = $this->normalize($css);

        return str_contains($clean, 'javascript:')
            || str_contains($clean, 'vbscript:')
            || str_contains($clean, 'expression(')
            || str_contains($clean, 'behavior:')
            || str_contains($clean, '@import');
    }

    private function normalize(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return strtolower(preg_replace('/[\x00-\x20]+/', '', $decoded) ?? '');
    }
}
