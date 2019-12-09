<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ILAB\MediaCloud\Utilities\Misc\Symfony\Component\Translation\Loader;

use ILAB\MediaCloud\Utilities\Misc\Symfony\Component\Config\Resource\FileResource;
use ILAB\MediaCloud\Utilities\Misc\Symfony\Component\Config\Util\XmlUtils;
use ILAB\MediaCloud\Utilities\Misc\Symfony\Component\Translation\Exception\InvalidResourceException;
use ILAB\MediaCloud\Utilities\Misc\Symfony\Component\Translation\Exception\NotFoundResourceException;
use ILAB\MediaCloud\Utilities\Misc\Symfony\Component\Translation\MessageCatalogue;
/**
 * QtFileLoader loads translations from QT Translations XML files.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class QtFileLoader implements \ILAB\MediaCloud\Utilities\Misc\Symfony\Component\Translation\Loader\LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        if (!\stream_is_local($resource)) {
            throw new \ILAB\MediaCloud\Utilities\Misc\Symfony\Component\Translation\Exception\InvalidResourceException(\sprintf('This is not a local file "%s".', $resource));
        }
        if (!\file_exists($resource)) {
            throw new \ILAB\MediaCloud\Utilities\Misc\Symfony\Component\Translation\Exception\NotFoundResourceException(\sprintf('File "%s" not found.', $resource));
        }
        try {
            $dom = \ILAB\MediaCloud\Utilities\Misc\Symfony\Component\Config\Util\XmlUtils::loadFile($resource);
        } catch (\InvalidArgumentException $e) {
            throw new \ILAB\MediaCloud\Utilities\Misc\Symfony\Component\Translation\Exception\InvalidResourceException(\sprintf('Unable to load "%s".', $resource), $e->getCode(), $e);
        }
        $internalErrors = \libxml_use_internal_errors(\true);
        \libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->evaluate('//TS/context/name[text()="' . $domain . '"]');
        $catalogue = new \ILAB\MediaCloud\Utilities\Misc\Symfony\Component\Translation\MessageCatalogue($locale);
        if (1 == $nodes->length) {
            $translations = $nodes->item(0)->nextSibling->parentNode->parentNode->getElementsByTagName('message');
            foreach ($translations as $translation) {
                $translationValue = (string) $translation->getElementsByTagName('translation')->item(0)->nodeValue;
                if (!empty($translationValue)) {
                    $catalogue->set((string) $translation->getElementsByTagName('source')->item(0)->nodeValue, $translationValue, $domain);
                }
                $translation = $translation->nextSibling;
            }
            if (\class_exists('ILAB\\MediaCloud\\Utilities\\Misc\\Symfony\\Component\\Config\\Resource\\FileResource')) {
                $catalogue->addResource(new \ILAB\MediaCloud\Utilities\Misc\Symfony\Component\Config\Resource\FileResource($resource));
            }
        }
        \libxml_use_internal_errors($internalErrors);
        return $catalogue;
    }
}
