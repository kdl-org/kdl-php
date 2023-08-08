<?php

declare(strict_types=1);

namespace Kdl\Kdl;

final class Xik
{
    private function __construct()
    {
    }

    /**
     * Parse a KDL string that uses the XML-in-KDL mapping, and produce a DOM document.
     *
     * Note: This conversion does not preserve comments or whitespace.
     */
    public static function parseString(string $kdl): \DOMDocument
    {
        return self::parse(Kdl::parse($kdl));
    }

    /**
     * Parse a KDL document that uses the XML-in-KDL mapping, and produce a DOM document.
     *
     * Note: This conversion does not preserve comments or whitespace.
     */
    public static function parse(Document $in): \DOMDocument
    {
        // Remove doctype
        $nodes = $in->getNodes();
        if (count($nodes) > 0 && $nodes[0]->getName() === '!doctype') {
            array_shift($nodes);
        }

        // Parse XML declaration
        $xmlDecl = [];
        if (count($nodes) > 0 && $nodes[0]->getName() === '?xml') {
            $xmlDecl = array_shift($nodes)->getProperties();
        }

        // Create the XML DOM document
        $doc = new \DOMDocument(
            $xmlDecl['version'] ?? '1.0',
            $xmlDecl['encoding'] ?? ''
        );

        // Parse remaining document nodes
        $namespaces = ['' => null];
        foreach ($nodes as $node) {
            self::parseNode($node, $doc, $doc, $namespaces);
        }

        return $doc;
    }

    /**
     * Parse a KDL node and create a DOM node.
     *
     * @param array<string, string> $namespaces
     */
    private static function parseNode(
        NodeInterface $in,
        \DOMParentNode $parent,
        \DOMDocument $doc,
        array $namespaces,
    ): void {
        $name = $in->getName();

        // Skip any processing instructions
        if (str_starts_with($name, '?') || str_starts_with($name, '!')) {
            return;
        }

        // Parse any new namespace declarations at this level.
        self::parseNamespaceDeclarations($in, $namespaces);

        // Create the node.
        [$ns, $name] = self::parseName($name, $namespaces);
        $out = $doc->createElementNS($ns, $name);

        // Add values as a single text node.
        $out->appendChild($doc->createTextNode(implode(' ', $in->getValues())));

        // Add attributes.
        foreach ($in->getProperties() as $name => $value) {
            // Namespace declarations were already handled by `parseNamespaceDeclarations`
            if ($name === 'xmlns' || str_starts_with($name, 'xmlns:')) {
                continue;
            }

            [$ns, $name] = self::parseName($name, $namespaces);
            $out->setAttributeNS($ns, $name, (string) $value);
        }

        // Add children.
        foreach ($in->getChildren() as $node) {
            self::parseNode($node, $out, $doc, $namespaces);
        }

        // Attach to parent.
        $parent->append($out);
    }

    /**
     * Parse XML namespace declarations from a KDL node.
     *
     * @param array<string, ?string> $namespaces
     */
    private static function parseNamespaceDeclarations(NodeInterface $node, array &$namespaces): void
    {
        foreach ($node->getProperties() as $prop => $value) {
            if ($prop === 'xmlns') {
                $namespaces[''] = $value;
            } elseif (str_starts_with($prop, 'xmlns:')) {
                $namespaces[substr($prop, 6)] = $value;
            }
        }
    }

    /**
     * Parse an XML element or attribute name into namespace and name.
     *
     * @param array<string, ?string> $ns
     * @return array{?string, string}
     */
    private static function parseName(string $name, array &$namespaces): array
    {
        $ns = '';
        $sepIdx = strpos($name, ':');
        if ($sepIdx !== false) {
            $ns = substr($name, 0, $sepIdx);
            $name = substr($name, $sepIdx + 1);
        }
        if (!array_key_exists($ns, $namespaces)) {
            throw new \Exception('Invalid namespace in element name: ' . $ns);
        }
        $ns = $namespaces[$ns];

        return [$ns, $name];
    }
}
