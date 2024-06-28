<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * QR Code generation class for QR Completion plugin.
 *
 * @package   local_qrcompletion
 * @copyright 2024 Randy Vermaas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/phpqrcode/qrlib.php');

/**
 * MyQRCode class for generating QR codes.
 *
 * This class provides functionality to generate QR codes with specific text,
 * save them to a specified file, and set the size of the generated QR code.
 *
 */
class MyQRCode {
    /**
     * @var string The text to encode in the QR code.
     */
    private $text;

    /**
     * @var string The file path to save the generated QR code.
     */
    private $outfile;

    /**
     * @var int The size of the QR code.
     */
    private $size;

    /**
     * Sets the text for the QR code.
     *
     * @param string $text The text to encode in the QR code.
     */
    public function settext($text) {
        $this->text = $text;
    }

    /**
     * Sets the output file path for the QR code.
     *
     * @param string $outfile The file path to save the generated QR code.
     */
    public function setoutfile($outfile) {
        $this->outfile = $outfile;
    }

    /**
     * Sets the size of the QR code.
     *
     * @param int $size The size of the QR code.
     */
    public function setsize($size) {
        $this->size = $size;
    }

    /**
     * Generates the QR code and saves it to the specified file.
     */
    public function generate() {
        // Ensure the directory exists.
        $dir = dirname($this->outfile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true); // Create the directory if it doesn't exist.
        }

        // Use phpqrcode library to generate the QR code with specified size.
        QRcode::png($this->text, $this->outfile, QR_ECLEVEL_L, $this->size);
    }
}
