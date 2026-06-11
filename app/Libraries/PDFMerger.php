<?php
/**
 * @file
 * PDFMerger — versión basada en TCPDI (Paul Nicholls) sobre TCPDF.
 *
 * A diferencia de FPDI (parser libre), TCPDI soporta PDF 1.5/1.6/1.7 con
 * cross-reference / object streams comprimidos (p. ej. salida de tc-lib-pdf),
 * por eso se usa esta variante.
 *
 * API: addPDF($filepath, $pages='all'|'1,3,4'|'1-2') -> merge($modo, $nombre).
 * Modos de salida: 'browser' | 'download' | 'string' | 'file'.
 *
 * @uses TCPDI (app/Libraries/tcpdf/tcpdi.php) sobre tecnickcom/tcpdf (composer)
 * @link https://github.com/pauln/tcpdi
 */
namespace App\Libraries;

class PDFMerger
{
	private $_files;	// [ [filepath, pages], ... ]  pages: 'all' | [1,2,4,...]

	public function __construct()
	{
		// TCPDI bundleado. Sus requires internos (fpdf_tpl.php, tcpdi_parser.php,
		// include/tcpdf_filters.php) se resuelven desde app/Libraries/tcpdf/.
		require_once __DIR__ . '/tcpdf/tcpdi.php';
	}

	/**
	 * Agrega un PDF al merge. $pages: 'all' o '1,3,6, 12-16'.
	 * @return $this
	 */
	public function addPDF($filepath, $pages = 'all')
	{
		if (file_exists($filepath)) {
			if (strtolower($pages) != 'all') {
				$pages = $this->_rewritepages($pages);
			}
			$this->_files[] = array($filepath, $pages);
		} else {
			throw new \Exception("Could not locate PDF on '$filepath'");
		}

		return $this;
	}

	/**
	 * Combina los PDFs agregados y los entrega según el modo.
	 * @param string $outputmode  browser | download | string | file
	 * @param string $outputpath  nombre/ruta de salida
	 * @return string|bool  string del PDF (modo 'string'); true en otros modos
	 */
	public function merge($outputmode = 'browser', $outputpath = 'newfile.pdf')
	{
		if (!isset($this->_files) || !is_array($this->_files)) {
			throw new \Exception("No PDFs to merge.");
		}

		$fpdi = new \TCPDI();
		$fpdi->setPrintHeader(false);
		$fpdi->setPrintFooter(false);

		foreach ($this->_files as $file) {
			$filename  = $file[0];
			$filepages = $file[1];

			$count = $fpdi->setSourceFile($filename);

			if ($filepages == 'all') {
				for ($i = 1; $i <= $count; $i++) {
					$this->_importPage($fpdi, $filename, $i);
				}
			} else {
				foreach ($filepages as $page) {
					$this->_importPage($fpdi, $filename, $page);
				}
			}
		}

		$mode = $this->_switchmode($outputmode);

		if ($mode == 'S') {
			return $fpdi->Output($outputpath, 'S');
		} elseif ($mode == 'F') {
			$fpdi->Output($outputpath, $mode);
			return true;
		} else {
			if ($fpdi->Output($outputpath, $mode) == '') {
				return true;
			}
			throw new \Exception("Error outputting PDF to '$outputmode'.");
		}
	}

	/**
	 * Importa una página del PDF origen como template y la agrega al documento.
	 */
	private function _importPage(\TCPDI $fpdi, $filename, $page)
	{
		$template = $fpdi->importPage($page);
		if (!$template) {
			throw new \Exception("Could not load page '$page' in PDF '$filename'. Check that the page exists.");
		}

		$size = $fpdi->getTemplateSize($template);
		$orientation = ($size['h'] > $size['w']) ? 'P' : 'L';

		$fpdi->AddPage($orientation, array($size['w'], $size['h']));
		$fpdi->useTemplate($template);
	}

	/**
	 * Convierte el modo descriptivo al carácter que usa TCPDF::Output().
	 */
	private function _switchmode($mode)
	{
		switch (strtolower($mode)) {
			case 'download':
				return 'D';
			case 'browser':
				return 'I';
			case 'file':
				return 'F';
			case 'string':
				return 'S';
			default:
				return 'I';
		}
	}

	/**
	 * Toma '1,3,4,16-50' y devuelve el array de páginas expandido.
	 */
	private function _rewritepages($pages)
	{
		$pages = str_replace(' ', '', $pages);
		$part = explode(',', $pages);
		$newpages = array();

		foreach ($part as $i) {
			$ind = explode('-', $i);

			if (count($ind) == 2) {
				$x = (int) $ind[0];
				$y = (int) $ind[1];

				if ($x > $y) {
					throw new \Exception("Starting page, '$x' is greater than ending page '$y'.");
				}

				while ($x <= $y) {
					$newpages[] = (int) $x;
					$x++;
				}
			} else {
				$newpages[] = (int) $ind[0];
			}
		}

		return $newpages;
	}
}
