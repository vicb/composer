<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Deprecation;

use JMS\PhpManipulator\TokenStream;
use Symfony\Component\Finder\Adapter\PhpAdapter;
use Symfony\Component\Finder\Finder;

/**
 * DeprecationGenerator
 */
class DeprecationGenerator
{
    /**
     * @param string $inputDir  Directories or a single path to search in
     * @param string $outputDir The name of the class map file
     */
    public static function dump($inputDir, $outputDir)
    {
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $finder = Finder::create()
            ->exclude($outputDir)
            ->name('*.php')
            ->files()
        ;

        $stream = new TokenStream();
        $stream->setIgnoreComments(false);
        $stream->setIgnoreWhitespace(false);

        foreach ((array)$inputDir as $dir) {
            foreach ($finder->in($dir) as $file) {
                $processedFile = "";
                $deprecation = false;
                $stream->setCode($file->getContents());
                while ($stream->moveNext()) {
                    $token = $stream->token;
                    if ($token->matches(T_DOC_COMMENT)) {
                        if (false !== strpos($token->getContent(), '@deprecated')) {
                            $deprecation = true;
                            for (; $stream->token && !$stream->token->matches('{'); $stream->moveNext()) {
                                $processedFile .= $stream->token->getContent();
                            }
                            if ($stream->token) {
                                $processedFile .= "{\ntrigger_error(\"deprecated\", E_USER_DEPRECATED);\n";
                            }
                        }
                    } else {
                        $processedFile .= $token->getContent();
                    }
                }
                if ($deprecation) {
                    $outputFile = $outputDir . '/' . $file->getRelativePathname();
                    $folder = dirname($outputFile);
                    if (!is_dir($folder)) {
                        mkdir ($folder, 0777, true);
                    }
                    file_put_contents($outputFile, $processedFile);
                }
            }
        }

    }
}
