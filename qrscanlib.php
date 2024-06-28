<?php
require_once(__DIR__ . '/phpqrcode/qrlib.php');

class MyQRCode {
    private $text;
    private $outfile;
    private $size;

    public function setText($text) {
        $this->text = $text;
    }

    public function setOutfile($outfile) {
        $this->outfile = $outfile;
    }

    public function setSize($size) {
        $this->size = $size;
    }

    public function generate() {
        // Ensure the directory exists
        $dir = dirname($this->outfile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true); // Create the directory if it doesn't exist
        }

        // Use phpqrcode library to generate the QR code with specified size
        QRcode::png($this->text, $this->outfile, QR_ECLEVEL_L, $this->size);
    }
}
?>
