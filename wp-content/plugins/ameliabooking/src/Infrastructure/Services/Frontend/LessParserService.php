<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Frontend;

use Less_Parser;

/**
 * Class LessParserService
 */
class LessParserService
{
    private $inputCssScript;

    private $outputPath;

    private $outputCssScript;

    /**
     * LessParserService constructor.
     *
     * @param string $inputCssScript
     * @param string $outputCssScript
     * @param string $outputPath
     */
    public function __construct($inputCssScript, $outputCssScript, $outputPath)
    {
        $this->inputCssScript = $inputCssScript;
        $this->outputCssScript = $outputCssScript;
        $this->outputPath = $outputPath;
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws \Exception
     * @throws \Less_Exception_Parser
     */
    public function compileAndSave($data)
    {
        $parser = new Less_Parser();

        $parser->parseFile($this->inputCssScript);

        $parser->ModifyVars($data);

        !is_dir($this->outputPath) && !mkdir($this->outputPath, 0755, true) && !is_dir($this->outputPath);

        file_put_contents($this->outputPath . '/' . $this->outputCssScript, $parser->getCss());
    }
}
