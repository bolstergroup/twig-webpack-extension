<?php

namespace Fullpipe\TwigWebpackExtension\TokenParser;

abstract class EntryTokenParser extends \Twig_TokenParser
{
    protected $manifestFile;
    protected $publicPath;

    abstract protected function type();

    abstract protected function generateHtml($entryPath);

    public function __construct($manifestFile, $publicPath)
    {
        $this->manifestFile = $manifestFile;
        $this->publicPath = $publicPath;
    }

    /**
     * Parse Twig string with webpack manifest values.
     */
    public function parse(\Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        $entryName = $stream->expect(\Twig_Token::STRING_TYPE)->getValue();
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        if (!file_exists($this->manifestFile)) {
            throw new \Twig_Error_Loader(
                'Webpack manifest file doesn\'t exist!',
                $token->getLine(),
                $stream->getSourceContext()->getName()
            );
        }

        $manifest = json_decode(file_get_contents($this->manifestFile), true);
        $assets = [];

        if (isset($manifest[$entryName . '.' . $this->type()])) {
            $assetPath = $manifest[$entryName . '.' . $this->type()];
            $assets[] = $this->generateHtml($assetPath);
        } else {
            throw new \Twig_Error_Loader(
                'Webpack ' . $this->type() . ' entry ' . $entryName . ' exists.',
                $token->getLine(),
                $stream->getSourceContext()->getName()
            );
        }

        return new \Twig_Node_Text(implode('', $assets), $token->getLine());
    }

    public function getTag()
    {
        return 'webpack_entry_' . $this->type();
    }
}
